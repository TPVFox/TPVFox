<?php

/**
 * PosstockQueryRepository — Capa de acceso a datos para ClasePosstock.
 *
 * Extraído de ClasePosstock en Fase 2 del Plan de Refactorización (3 Apr 2026).
 * Contiene todos los métodos SQL puros: helpers de WHERE, queries de movimientos,
 * stock, ventas por caso (C1–C9) y consultas de proveedor.
 *
 * Todos los métodos son públicos para permitir inyección y tests de integración
 * directos sobre la BD de test.
 */
class PosstockQueryRepository
{
    public function __construct(private mysqli $db) {}

    // ══════════════════════════════════════════════════════════════════════════
    // SQL HELPERS — Construcción de cláusulas WHERE
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Expande una lista de idFamilia a todos sus descendientes usando
     * vw_jerarquias_familias (idN1, idN2 capturan hijos de nivel 1 y 2).
     * Devuelve el IN-clause listo para SQL, o '' si la lista está vacía.
     */
    public function expandirFamilias(array $ids): string
    {
        if (empty($ids)) return '';
        $in = implode(',', array_map('intval', $ids));
        $sentencia = $this->db->query("
            SELECT DISTINCT idFamilia
            FROM vw_jerarquias_familias
            WHERE idFamilia IN ($in)
               OR idN1      IN ($in)
               OR idN2      IN ($in)
        ");
        if (!$sentencia) return $in; // fallback: usar IDs originales
        $expanded = [];
        while ($fila = $sentencia->fetch_assoc()) $expanded[] = (int)$fila['idFamilia'];
        if (empty($expanded)) return $in; // fallback: familia no encontrada en jerarquía
        return implode(',', $expanded);
    }

    /** Construye las cláusulas WHERE de familia (incluir/excluir) para el alias dado. */
    public function familiaWhere(array $incluir, array $excluir, string $alias = 'l'): string
    {
        $w = '';
        if (!empty($incluir)) {
            $ids = $this->expandirFamilias($incluir);
            if ($ids) $w .= " AND $alias.idArticulo IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids))";
        }
        if (!empty($excluir)) {
            $ids = $this->expandirFamilias($excluir);
            if ($ids) $w .= " AND $alias.idArticulo NOT IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids))";
        }
        return $w;
    }

    /** Construye la cláusula AND idArticulo IN (...) o '' si la lista está vacía. */
    public function idsWhere(array $ids_filter, string $alias = 'l'): string
    {
        if (empty($ids_filter)) return '';
        return " AND $alias.idArticulo IN (" . implode(',', array_map('intval', $ids_filter)) . ")";
    }

    // ══════════════════════════════════════════════════════════════════════════
    // T4.1 / T4.2
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * T4.1 — UNION ALL de los 3 tipos de movimiento físico en el periodo.
     * Agrupado por (tipo, artículo, fecha) para reducir filas en memoria.
     *
     * @return array  Filas raw o ['error' => ...]
     */
    public function queryMovimientosPeriodo(
        string $fechaInicioEsc,
        string $fechaFinEsc,
        string $where_familia,
        string $where_ids
    ): array {
        $sql = "
            SELECT tipo_movimiento, idArticulo, SUM(nunidades) AS nunidades, fecha, NULL AS idDocumento
            FROM (
                SELECT
                    'entrada_proveedor'    AS tipo_movimiento,
                    l.idArticulo,
                    l.nunidades,
                    DATE(c.Fecha)          AS fecha
                FROM albprolinea l
                INNER JOIN albprot      c ON c.id         = l.idalbpro
                INNER JOIN articulos    a ON a.idArticulo  = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado       IN ('Guardado', 'Facturado')
                  AND l.estadoLinea  = 'Activo'
                  $where_familia
                  $where_ids

                UNION ALL

                SELECT
                    'salida_ticket'        AS tipo_movimiento,
                    l.idArticulo,
                    l.nunidades,
                    DATE(c.Fecha)          AS fecha
                FROM ticketslinea l
                INNER JOIN ticketst     c ON c.id         = l.idticketst
                INNER JOIN articulos    a ON a.idArticulo  = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado       = 'Cerrado'
                  AND l.estadoLinea  = 'Activo'
                  $where_familia
                  $where_ids

                UNION ALL

                SELECT
                    'salida_albcli'        AS tipo_movimiento,
                    l.idArticulo,
                    l.nunidades,
                    DATE(c.Fecha)          AS fecha
                FROM albclilinea l
                INNER JOIN albclit      c ON c.id         = l.idalbcli
                INNER JOIN articulos    a ON a.idArticulo  = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado       IN ('Guardado', 'Procesado')
                  AND l.estadoLinea  = 'Activo'
                  $where_familia
                  $where_ids
            ) AS all_movs
            GROUP BY tipo_movimiento, idArticulo, fecha
            ORDER BY idArticulo, fecha
        ";
        $sentencia = $this->db->query($sql);
        if (!$sentencia) return ['error' => $this->db->error, 'consulta' => $sql];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        return $filas;
    }

    /**
     * T4.2 — UNION ALL de movimientos en el rango de stock base.
     * Filtra solo los idArticulo indicados.
     *
     * @return array  Filas raw (idArticulo, saldo_acumulado, ultima_compra, ultima_venta) o ['error' => ...]
     */
    public function queryStockBase(string $fechaInicioEsc, string $fechaFinEsc, string $ids_str): array
    {
        $sql = "
            SELECT
                idArticulo,
                SUM(nunidades_signo)                        AS saldo_acumulado,
                MAX(CASE WHEN tipo_mov = 'entrada'
                         THEN fecha END)                AS ultima_compra,
                MAX(CASE WHEN tipo_mov = 'salida'
                         THEN fecha END)                AS ultima_venta
            FROM (

                -- Entradas proveedor (positivo)
                SELECT
                    l.idArticulo,
                     l.nunidades                            AS nunidades_signo,
                    'entrada'                           AS tipo_mov,
                    DATE(c.Fecha)                       AS fecha
                FROM albprolinea l
                INNER JOIN albprot c ON c.id = l.idalbpro
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado      IN ('Guardado', 'Facturado', 'Exportado', 'Importado')
                  AND l.estadoLinea = 'Activo'
                  AND l.idArticulo  IN ($ids_str)

                UNION ALL

                -- Salidas tickets (negativo)
                SELECT
                    l.idArticulo,
                    -l.nunidades                            AS nunidades_signo,
                    'salida'                            AS tipo_mov,
                    DATE(c.Fecha)                       AS fecha
                FROM ticketslinea l
                INNER JOIN ticketst c ON c.id = l.idticketst
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado      = 'Cerrado'
                  AND l.estadoLinea = 'Activo'
                  AND l.idArticulo  IN ($ids_str)

                UNION ALL

                -- Salidas albaranes cliente (negativo)
                SELECT
                    l.idArticulo,
                    -l.nunidades                            AS nunidades_signo,
                    'salida'                            AS tipo_mov,
                    DATE(c.Fecha)                       AS fecha
                FROM albclilinea l
                INNER JOIN albclit c ON c.id = l.idalbcli
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado      IN ('Guardado', 'Procesado')
                  AND l.estadoLinea = 'Activo'
                  AND l.idArticulo  IN ($ids_str)

            ) AS movimientos_stock
            GROUP BY idArticulo
        ";
        $sentencia = $this->db->query($sql);
        if (!$sentencia) return ['error' => $this->db->error, 'consulta' => $sql];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        return $filas;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // C4
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * C4 paso 1 — Todos los idArticulo físicos que cumplen el filtro de familia.
     *
     * @return array  Filas raw (idArticulo) o ['error' => ...]
     */
    public function queryArticulosFisicos(string $where_familia): array
    {
        $sentencia = $this->db->query(
            "SELECT idArticulo FROM articulos a $where_familia"
        );
        if (!$sentencia) return ['error' => $this->db->error];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        return $filas;
    }

    /**
     * C4 paso 2 — idArticulo con cualquier movimiento (los 3 tipos) en [fi, ff].
     *
     * @return array  Filas raw (idArticulo) o ['error' => ...]
     */
    public function queryIdsConMovimientoC4(string $fechaInicioEsc, string $fechaFinEsc): array
    {
        $sentencia = $this->db->query("
            SELECT DISTINCT idArticulo FROM (
                SELECT l.idArticulo FROM albprolinea l
                INNER JOIN albprot c ON c.id = l.idalbpro
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado IN ('Guardado', 'Facturado', 'Exportado', 'Importado')
                  AND l.estadoLinea = 'Activo'
                UNION
                SELECT l.idArticulo FROM ticketslinea l
                INNER JOIN ticketst c ON c.id = l.idticketst
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado = 'Cerrado'
                  AND l.estadoLinea = 'Activo'
                UNION
                SELECT l.idArticulo FROM albclilinea l
                INNER JOIN albclit c ON c.id = l.idalbcli
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado IN ('Guardado', 'Procesado')
                  AND l.estadoLinea = 'Activo'
            ) AS movs_año
        ");
        if (!$sentencia) return ['error' => $this->db->error];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        return $filas;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Stock rebobinado (C4, C5, C6, C7)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * C4/C5 — Stock rebobinado desde articulosStocks.stockOn hasta ff_esc.
     *
     * @param string $ids_str        IN-clause de idArticulo ya preparado
     * @param string $ff_esc         Fecha fin escapada ('YYYY-MM-DD')
     * @param bool   $solo_positivos Si true, filtra HAVING stock > 0 (usado en C4)
     *
     * @return array  Filas raw (idArticulo, stock_en_periodo) o ['error' => ...]
     */
    public function queryStockRebobinado(
        string $ids_str,
        string $ff_esc,
        bool   $solo_positivos = false
    ): array {
        $having = $solo_positivos ? 'HAVING stock_en_periodo > 0' : '';
        $sentencia = $this->db->query("
            SELECT
                base.idArticulo,
                base.total_stockOn - COALESCE(post.net_posterior, 0) AS stock_en_periodo
            FROM (
                SELECT idArticulo, SUM(stockOn) AS total_stockOn
                FROM articulosStocks
                WHERE idArticulo IN ($ids_str)
                GROUP BY idArticulo
            ) AS base
            LEFT JOIN (
                SELECT idArticulo, SUM(nunidades_signo) AS net_posterior
                FROM (
                    SELECT l.idArticulo,  l.nunidades AS nunidades_signo
                    FROM albprolinea l INNER JOIN albprot c ON c.id = l.idalbpro
                    WHERE DATE(c.Fecha) > '$ff_esc'
                      AND c.estado IN ('Guardado','Facturado','Exportado','Importado')
                      AND l.estadoLinea = 'Activo'
                      AND l.idArticulo IN ($ids_str)
                    UNION ALL
                    SELECT l.idArticulo, -l.nunidades AS nunidades_signo
                    FROM ticketslinea l INNER JOIN ticketst c ON c.id = l.idticketst
                    WHERE DATE(c.Fecha) > '$ff_esc'
                      AND c.estado = 'Cerrado'
                      AND l.estadoLinea = 'Activo'
                      AND l.idArticulo IN ($ids_str)
                    UNION ALL
                    SELECT l.idArticulo, -l.nunidades AS nunidades_signo
                    FROM albclilinea l INNER JOIN albclit c ON c.id = l.idalbcli
                    WHERE DATE(c.Fecha) > '$ff_esc'
                      AND c.estado IN ('Guardado','Procesado')
                      AND l.estadoLinea = 'Activo'
                      AND l.idArticulo IN ($ids_str)
                ) AS post_movs
                GROUP BY idArticulo
            ) AS post ON post.idArticulo = base.idArticulo
            $having
        ");
        if (!$sentencia) return ['error' => $this->db->error];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        return $filas;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // C5
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * C5 paso 1 — Fechas de venta únicas por artículo físico.
     *
     * @return array  Filas raw (idArticulo, fecha) o ['error' => ...]
     */
    public function queryVentasFechasC5(
        string $fechaInicioEsc,
        string $fechaFinEsc,
        string $where_fam,
        string $where_ids,
        bool   $incluir_albcli = false
    ): array {
        $union_albcli = $incluir_albcli ? "
                UNION
                SELECT DISTINCT l.idArticulo, DATE(c.Fecha) AS fecha
                FROM albclilinea l
                INNER JOIN albclit   c ON c.id = l.idalbcli
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado IN ('Guardado','Procesado')
                  AND l.estadoLinea = 'Activo'
                  $where_fam
                  $where_ids" : '';
        $sentencia = $this->db->query("
            SELECT idArticulo, fecha FROM (
                SELECT DISTINCT l.idArticulo, DATE(c.Fecha) AS fecha
                FROM ticketslinea l
                INNER JOIN ticketst  c ON c.id = l.idticketst
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado = 'Cerrado'
                  AND l.estadoLinea = 'Activo'
                  $where_fam
                  $where_ids
                $union_albcli
            ) AS ventas
            ORDER BY idArticulo, fecha
        ");
        if (!$sentencia) return ['error' => $this->db->error];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        return $filas;
    }

    /**
     * C5 anti-falso-positivo — Primera venta post-periodo por artículo.
     *
     * @return array  [idArticulo => 'YYYY-MM-DD'] primera venta post-periodo
     */
    public function queryVentasPostPeriodoC5(
        string $ids_str,
        string $fi_esc,
        string $ff_esc,
        bool   $incluir_albcli = false
    ): array {
        $union_albcli = $incluir_albcli ? "
                UNION ALL
                SELECT l.idArticulo, DATE(c.Fecha) AS fecha
                FROM albclilinea l
                INNER JOIN albclit c ON c.id = l.idalbcli
                WHERE l.idArticulo IN ($ids_str)
                  AND DATE(c.Fecha) BETWEEN '$fi_esc' AND '$ff_esc'
                  AND c.estado IN ('Guardado','Procesado')
                  AND l.estadoLinea = 'Activo'" : '';

        $sentencia = $this->db->query("
            SELECT idArticulo, MIN(fecha) AS primera_venta_post
            FROM (
                SELECT l.idArticulo, DATE(c.Fecha) AS fecha
                FROM ticketslinea l
                INNER JOIN ticketst c ON c.id = l.idticketst
                WHERE l.idArticulo IN ($ids_str)
                  AND DATE(c.Fecha) BETWEEN '$fi_esc' AND '$ff_esc'
                  AND c.estado = 'Cerrado'
                  AND l.estadoLinea = 'Activo'
                $union_albcli
            ) AS ventas_post
            GROUP BY idArticulo
        ");
        if (!$sentencia) return [];
        $result = [];
        while ($fila = $sentencia->fetch_assoc()) {
            $result[(int)$fila['idArticulo']] = $fila['primera_venta_post'];
        }
        return $result;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // C6
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * C6 paso 1 — Cantidad vendida por día por artículo físico.
     *
     * @return array  Filas raw (idArticulo, fecha, nunidades_dia) o ['error' => ...]
     */
    public function queryVentasCantidadesC6(
        string $fechaInicioEsc,
        string $fechaFinEsc,
        string $where_fam,
        string $where_ids,
        bool   $incluir_albcli = false,
        bool   $incluir_todos_tipos = false
    ): array {
        if ($incluir_albcli) {
            $sentencia = $this->db->query("
                SELECT idArticulo, fecha, SUM(nunidades) AS nunidades_dia
                FROM (
                    SELECT l.idArticulo, DATE(c.Fecha) AS fecha, l.nunidades
                    FROM ticketslinea l
                    INNER JOIN ticketst  c ON c.id = l.idticketst
                    INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                                        WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                                            AND c.estado = 'Cerrado'
                                            AND l.estadoLinea = 'Activo'
                                            $where_fam
                                            $where_ids
                    UNION ALL
                    SELECT l.idArticulo, DATE(c.Fecha) AS fecha, l.nunidades
                    FROM albclilinea l
                    INNER JOIN albclit   c ON c.id = l.idalbcli
                    INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                                        WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                                            AND c.estado IN ('Guardado','Procesado')
                                            AND l.estadoLinea = 'Activo'
                                            $where_fam
                                            $where_ids
                ) AS ventas
                GROUP BY idArticulo, fecha
                ORDER BY idArticulo, fecha
            ");
        } else {
            $sentencia = $this->db->query("
                SELECT l.idArticulo, DATE(c.Fecha) AS fecha, SUM(l.nunidades) AS nunidades_dia
                                FROM ticketslinea l
                                INNER JOIN ticketst  c ON c.id = l.idticketst
                                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                                    AND c.estado = 'Cerrado'
                                    AND l.estadoLinea = 'Activo'
                                    $where_fam
                                    $where_ids
                GROUP BY l.idArticulo, DATE(c.Fecha)
                ORDER BY l.idArticulo, DATE(c.Fecha)
            ");
        }
        if (!$sentencia) return ['error' => $this->db->error];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        return $filas;
    }

    /**
     * C6 — Fechas únicas de albarán por proveedor en el rango dado.
     *
     * @return array  Filas raw (idProveedor, fecha_albaran) o ['error' => ...]
     */
    public function queryFechasAlbaranesByProveedores(string $fechaInicioEsc, string $fechaFinEsc, string $ids_prov): array
    {
        $sentencia = $this->db->query("
            SELECT idProveedor, DATE(Fecha) AS fecha_albaran
            FROM albprot
            WHERE DATE(Fecha) BETWEEN '$fi' AND '$ff'
              AND estado       IN ('Guardado','Facturado','Exportado','Importado')
              AND idProveedor  IN ($ids_prov)
            GROUP BY idProveedor, DATE(Fecha)
            ORDER BY idProveedor, DATE(Fecha)
        ");
        if (!$sentencia) return ['error' => $this->db->error];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        return $filas;
    }

    /**
     * C6 — Reconstrucción de stock desde la última entrada de proveedor.
     *
     * @return array  [idArticulo => stock_reconstituido] o ['error' => ...]
     */
    public function queryStockReconstituido(string $ids_str, string $ff_esc): array
    {
        $sentencia = $this->db->query("
            SELECT e.idArticulo,
                   e.nunidades_entrada - COALESCE(SUM(v.nunidades), 0) AS stock_reconstituido
            FROM (
                -- Última línea de albarán de proveedor (ROW_NUMBER garantiza exactamente una por artículo)
                SELECT ult.idArticulo, ult.nunidades AS nunidades_entrada, DATE(cab.Fecha) AS fecha_entrada
                FROM (
                    SELECT l.idArticulo, l.nunidades, l.idalbpro,
                           ROW_NUMBER() OVER (PARTITION BY l.idArticulo ORDER BY h.Fecha DESC, l.id DESC) AS rn
                    FROM albprolinea l
                    INNER JOIN albprot h ON h.id = l.idalbpro
                    WHERE l.idArticulo IN ($ids_str)
                      AND h.estado IN ('Guardado','Facturado','Exportado','Importado')
                      AND l.estadoLinea = 'Activo'
                ) ult
                INNER JOIN albprot cab ON cab.id = ult.idalbpro
                WHERE ult.rn = 1
            ) e
            LEFT JOIN (
                -- Ventas por ticket hasta ff_esc (sin albcli: solo salidas reales de caja)
                SELECT l.idArticulo, DATE(c.Fecha) AS fecha_venta, l.nunidades
                FROM ticketslinea l
                INNER JOIN ticketst c ON c.id = l.idticketst
                WHERE l.idArticulo IN ($ids_str)
                  AND c.estado = 'Cerrado'
                  AND l.estadoLinea = 'Activo'
                  AND DATE(c.Fecha) <= '$ff_esc'
            ) v ON v.idArticulo = e.idArticulo
                AND v.fecha_venta >= e.fecha_entrada
            GROUP BY e.idArticulo, e.nunidades_entrada
        ");
        if (!$sentencia) return ['error' => $this->db->error];
        $result = [];
        while ($fila = $sentencia->fetch_assoc()) {
            $result[(int)$fila['idArticulo']] = (float)$fila['stock_reconstituido'];
        }
        return $result;
    }

    /**
     * C6 — Calcula el lead time medio (días entre albaranes consecutivos) para proveedores dados.
     *
     * @return int  Días de LT calculado, o 0 si no hay datos suficientes.
     */
    public function calcularLeadTimeProveedores(string $fi_stock, string $ff_mov, array $proveedores_incluir): int
    {
        if (empty($proveedores_incluir)) return 0;

        $fechaInicioEsc       = $this->db->real_escape_string($fi_stock);
        $fechaFinEsc       = $this->db->real_escape_string($ff_mov);
        $ids_prov = implode(',', array_map('intval', $proveedores_incluir));

        $filas = $this->queryFechasAlbaranesByProveedores($fechaInicioEsc, $fechaFinEsc, $ids_prov);
        if (isset($filas['error']) || empty($filas)) return 0;

        $por_proveedor = [];
        foreach ($filas as $fila) {
            $por_proveedor[(int)$fila['idProveedor']][] = $fila['fecha_albaran'];
        }

        $lts = [];
        foreach ($por_proveedor as $fechas) {
            if (count($fechas) < 2) continue;
            $ts = array_map('strtotime', $fechas);
            sort($ts);
            $intervalos = [];
            for ($i = 1, $np = count($ts); $i < $np; $i++) {
                $dias = (int)(($ts[$i] - $ts[$i - 1]) / 86400);
                if ($dias > 0) $intervalos[] = $dias;
            }
            if (!empty($intervalos)) {
                $lts[] = array_sum($intervalos) / count($intervalos);
            }
        }

        if (empty($lts)) return 0;
        return max(1, (int)round(array_sum($lts) / count($lts)));
    }

    // ══════════════════════════════════════════════════════════════════════════
    // C1
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * C1 — Detalle de actividad en el periodo para los artículos ya identificados.
     *
     * @return array  ['idArticulo' => ['n_entradas'=>int, 'ultima_entrada'=>string|null, 'n_ventas'=>int]]
     */
    public function queryDetalleC1(string $ids, string $fechaInicioEsc, string $fechaFinEsc): array
    {
        $detalle = [];

        $sentencia = $this->db->query("
            SELECT l.idArticulo,
                   COUNT(*)           AS n_entradas,
                   MAX(DATE(c.Fecha)) AS ultima_entrada
            FROM albprolinea l
            INNER JOIN albprot c ON c.id = l.idalbpro
            WHERE l.idArticulo IN ($ids)
              AND DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
              AND c.estado      IN ('Guardado','Facturado')
              AND l.estadoLinea = 'Activo'
            GROUP BY l.idArticulo
        ");
        if ($sentencia) {
            while ($fila = $sentencia->fetch_assoc()) {
                $id = (int)$fila['idArticulo'];
                $detalle[$id] = [
                    'n_entradas'     => (int)$fila['n_entradas'],
                    'ultima_entrada' => $fila['ultima_entrada'],
                    'n_ventas'       => 0,
                ];
            }
        }

        $smt2 = $this->db->query("
            SELECT idArticulo, SUM(cnt) AS n_ventas
            FROM (
                SELECT l.idArticulo, COUNT(*) AS cnt
                FROM ticketslinea l
                INNER JOIN ticketst c ON c.id = l.idticketst
                WHERE l.idArticulo IN ($ids)
                  AND DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado      = 'Cerrado'
                  AND l.estadoLinea = 'Activo'
                GROUP BY l.idArticulo
                UNION ALL
                SELECT l.idArticulo, COUNT(*) AS cnt
                FROM albclilinea l
                INNER JOIN albclit c ON c.id = l.idalbcli
                WHERE l.idArticulo IN ($ids)
                  AND DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado      IN ('Guardado','Procesado')
                  AND l.estadoLinea = 'Activo'
                GROUP BY l.idArticulo
            ) v
            GROUP BY idArticulo
        ");
        if ($smt2) {
            while ($fila = $smt2->fetch_assoc()) {
                $id = (int)$fila['idArticulo'];
                if (!isset($detalle[$id])) {
                    $detalle[$id] = ['n_entradas' => 0, 'ultima_entrada' => null];
                }
                $detalle[$id]['n_ventas'] = (int)$fila['n_ventas'];
            }
        }

        return $detalle;
    }

    /**
     * C1b — Confirma hipótesis de timing: entrada de proveedor dentro de $ventana_dias
     * días posteriores a la fecha en que el balance fue mínimo.
     *
     * @param array $id_fecha_map  [idArticulo => 'YYYY-MM-DD' (fecha_minimo), ...]
     * @return array  Set de idArticulo con timing confirmado [idArticulo => true]
     */
    public function queryTimingC1b(array $id_fecha_map, int $ventana_dias = 1): array
    {
        if (empty($id_fecha_map)) return [];

        $conditions = [];
        foreach ($id_fecha_map as $id => $fecha) {
            if (!$fecha) continue;
            $id_esc    = (int)$id;
            $f_esc     = $this->db->real_escape_string($fecha);
            $f_fin_esc = $this->db->real_escape_string(
                date('Y-m-d', strtotime($fecha . " +{$ventana_dias} days"))
            );
            $conditions[] = "(l.idArticulo = $id_esc AND DATE(c.Fecha) BETWEEN '$f_esc' AND '$f_fin_esc')";
        }

        if (empty($conditions)) return [];

        $where_or = implode(' OR ', $conditions);
        $sentencia = $this->db->query("
            SELECT DISTINCT l.idArticulo
            FROM albprolinea l
            INNER JOIN albprot c ON c.id = l.idalbpro
            WHERE ($where_or)
              AND c.estado      IN ('Guardado','Facturado')
              AND l.estadoLinea = 'Activo'
        ");
        if (!$sentencia) return [];

        $resultado = [];
        while ($fila = $sentencia->fetch_assoc()) {
            $resultado[(int)$fila['idArticulo']] = true;
        }
        return $resultado;
    }

    /**
     * C1 — Proveedor habitual y último proveedor para una lista de artículos.
     *
     * @return array  [idArticulo => [...]] o vacío si no hay datos
     */
    public function queryProveedorArticulos(string $ids_str, string $fi_stock, string $fechaFinEsc): array
    {
        if (empty($ids_str)) return [];

        $sentencia = $this->db->query("
            SELECT
                l.idArticulo,
                c.idProveedor,
                p.nombrecomercial           AS nombre,
                COUNT(DISTINCT c.id)        AS n_albaranes,
                SUM(ABS(l.nunidades))           AS cantidad_total,
                MAX(DATE(c.Fecha))          AS ultima_fecha
            FROM albprolinea l
            INNER JOIN albprot     c ON c.id          = l.idalbpro
            INNER JOIN proveedores p ON p.idProveedor = c.idProveedor
            WHERE l.idArticulo IN ($ids_str)
              AND DATE(c.Fecha) BETWEEN '$fi_stock' AND '$ff'
              AND c.estado      IN ('Guardado','Facturado','Exportado','Importado')
              AND l.estadoLinea = 'Activo'
            GROUP BY l.idArticulo, c.idProveedor
            ORDER BY l.idArticulo, n_albaranes DESC, cantidad_total DESC, ultima_fecha DESC
        ");
        if (!$sentencia) return [];

        $por_art = [];
        while ($fila = $sentencia->fetch_assoc()) {
            $id = (int)$fila['idArticulo'];
            if (!isset($por_art[$id])) {
                $por_art[$id] = [
                    'prov_habitual_id'     => (int)$fila['idProveedor'],
                    'prov_habitual_nombre' => $fila['nombre'],
                    'prov_habitual_n'      => (int)$fila['n_albaranes'],
                    'prov_ultimo_id'       => (int)$fila['idProveedor'],
                    'prov_ultimo_nombre'   => $fila['nombre'],
                    'prov_ultima_fecha'    => $fila['ultima_fecha'],
                ];
            } else {
                if ($fila['ultima_fecha'] > $por_art[$id]['prov_ultima_fecha']) {
                    $por_art[$id]['prov_ultimo_id']     = (int)$fila['idProveedor'];
                    $por_art[$id]['prov_ultimo_nombre'] = $fila['nombre'];
                    $por_art[$id]['prov_ultima_fecha']  = $fila['ultima_fecha'];
                }
            }
        }

        foreach ($por_art as &$d) {
            $d['prov_es_mismo'] = ($d['prov_habitual_id'] === $d['prov_ultimo_id']);
        }
        unset($d);

        return $por_art;
    }

    /**
     * C7b-011: precio medio ponderado de compra por artículo en la ventana dada.
     * Devuelve [idArticulo => precio_medio_compra].
     */
    public function queryPrecioMedioCompra(string $ids_str, string $fechaInicioEsc, string $fechaFinEsc): array
    {
        if (empty($ids_str)) return [];

        $sentencia = $this->db->query("
            SELECT
                l.idArticulo,
                SUM(l.costeSiva * l.nunidades) / NULLIF(SUM(l.nunidades), 0) AS precio_medio
            FROM albprolinea l
            INNER JOIN albprot c ON c.id = l.idalbpro
            WHERE l.idArticulo IN ($ids_str)
              AND DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
              AND c.estado      IN ('Guardado','Facturado','Exportado','Importado')
              AND l.estadoLinea = 'Activo'
              AND l.nunidades       > 0
            GROUP BY l.idArticulo
        ");
        if (!$sentencia) return [];

        $result = [];
        while ($fila = $sentencia->fetch_assoc()) {
            if ($fila['precio_medio'] !== null) {
                $result[(int)$fila['idArticulo']] = (float)$fila['precio_medio'];
            }
        }
        return $result;
    }

    /**
     * C1 — Delta total y mínimo de la suma acumulada por artículo.
     * Solo devuelve artículos donde MIN(cum_sum) < 0 OR SUM(day_delta) < 0.
     *
     * @return array  Filas raw (idArticulo, delta_total, min_running, fecha_minimo, ...) o ['error' => ...]
     */
    public function queryDeltasC1(
        string $fechaInicioEsc,
        string $fechaFinEsc,
        string $wf,
        string $wi
    ): array {
        $sql = "
            SELECT idArticulo, SUM(day_delta) AS delta_total, MIN(cum_sum) AS min_running,
                   MIN(CASE WHEN rn_min = 1 THEN fecha END) AS fecha_minimo,
                   SUM(CASE WHEN cum_sum = min_per_art THEN 1 ELSE 0 END) AS dias_en_minimo,
                   SUM(CASE WHEN cum_sum < 0 THEN 1 ELSE 0 END) AS dias_en_negativo
            FROM (
                SELECT idArticulo, fecha, day_delta, cum_sum, min_per_art,
                       ROW_NUMBER() OVER (PARTITION BY idArticulo
                                          ORDER BY cum_sum ASC, fecha ASC) AS rn_min
                FROM (
                    SELECT idArticulo, fecha, day_delta, cum_sum,
                           MIN(cum_sum) OVER (PARTITION BY idArticulo) AS min_per_art
                    FROM (
                        SELECT idArticulo, fecha, day_delta,
                               SUM(day_delta) OVER (PARTITION BY idArticulo ORDER BY fecha
                                                    ROWS UNBOUNDED PRECEDING) AS cum_sum
                        FROM (
                            SELECT idArticulo, fecha, SUM(delta) AS day_delta
                            FROM (
                                SELECT l.idArticulo, DATE(c.Fecha) AS fecha, l.nunidades AS delta
                                FROM albprolinea l
                                INNER JOIN albprot    c ON c.id        = l.idalbpro
                                INNER JOIN articulos  a ON a.idArticulo = l.idArticulo
                                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                                  AND c.estado      IN ('Guardado','Facturado')
                                  AND l.estadoLinea = 'Activo'
                                  $wf $wi
                                UNION ALL
                                SELECT l.idArticulo, DATE(c.Fecha) AS fecha, -l.nunidades AS delta
                                FROM ticketslinea l
                                INNER JOIN ticketst  c ON c.id        = l.idticketst
                                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                                  AND c.estado      = 'Cerrado'
                                  AND l.estadoLinea = 'Activo'
                                  $wf $wi
                                UNION ALL
                                SELECT l.idArticulo, DATE(c.Fecha) AS fecha, -l.nunidades AS delta
                                FROM albclilinea l
                                INNER JOIN albclit   c ON c.id        = l.idalbcli
                                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                                  AND c.estado      IN ('Guardado','Procesado')
                                  AND l.estadoLinea = 'Activo'
                                  $wf $wi
                            ) AS all_movs
                            GROUP BY idArticulo, fecha
                        ) AS daily
                    ) AS windowed_inner
                ) AS with_min
            ) AS windowed
            GROUP BY idArticulo
            HAVING MIN(cum_sum) < 0 OR SUM(day_delta) < 0
        ";
        $sentencia = $this->db->query($sql);
        if (!$sentencia) return ['error' => $this->db->error];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        return $filas;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // C2
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * C2 — Entradas de proveedor con sum acumulada (window function).
     *
     * @return array  Filas raw (idArticulo, fecha, nunidades, cum_before) o ['error' => ...]
     */
    public function queryEntradasC2(
        string $fechaInicioEsc,
        string $fechaFinEsc,
        string $wf,
        string $wi
    ): array {
        $sql = "
            SELECT e.idArticulo, e.fecha, e.nunidades,
                   COALESCE(r.cum_before, 0) AS cum_before
            FROM (
                SELECT l.idArticulo, DATE(c.Fecha) AS fecha, SUM(l.nunidades) AS nunidades
                FROM albprolinea l
                INNER JOIN albprot   c ON c.id        = l.idalbpro
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado      IN ('Guardado','Facturado')
                  AND l.estadoLinea = 'Activo'
                  AND l.nunidades       > 0
                  $wf $wi
                GROUP BY l.idArticulo, DATE(c.Fecha)
            ) AS e
            LEFT JOIN (
                SELECT idArticulo, fecha,
                       COALESCE(SUM(day_delta) OVER (
                           PARTITION BY idArticulo ORDER BY fecha
                           ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING
                       ), 0) AS cum_before
                FROM (
                    SELECT idArticulo, fecha, SUM(delta) AS day_delta
                    FROM (
                        SELECT l.idArticulo, DATE(c.Fecha) AS fecha, l.nunidades AS delta
                        FROM albprolinea l
                        INNER JOIN albprot    c ON c.id        = l.idalbpro
                        INNER JOIN articulos  a ON a.idArticulo = l.idArticulo
                        WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                          AND c.estado      IN ('Guardado','Facturado')
                          AND l.estadoLinea = 'Activo'
                          $wf $wi
                        UNION ALL
                        SELECT l.idArticulo, DATE(c.Fecha) AS fecha, -l.nunidades AS delta
                        FROM ticketslinea l
                        INNER JOIN ticketst  c ON c.id        = l.idticketst
                        INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                        WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                          AND c.estado      = 'Cerrado'
                          AND l.estadoLinea = 'Activo'
                          $wf $wi
                        UNION ALL
                        SELECT l.idArticulo, DATE(c.Fecha) AS fecha, -l.nunidades AS delta
                        FROM albclilinea l
                        INNER JOIN albclit   c ON c.id        = l.idalbcli
                        INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                        WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                          AND c.estado      IN ('Guardado','Procesado')
                          AND l.estadoLinea = 'Activo'
                          $wf $wi
                    ) AS all_movs
                    GROUP BY idArticulo, fecha
                ) AS daily
            ) AS r ON r.idArticulo = e.idArticulo AND r.fecha = e.fecha
        ";
        $sentencia = $this->db->query($sql);
        if (!$sentencia) return ['error' => $this->db->error];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        return $filas;
    }

    /**
     * Ventas por ticket en el periodo para los artículos candidatos de C2.
     * Devuelve [idArticulo => ventas_total].
     */
    public function queryVentasC2(string $ids, string $fechaInicioEsc, string $fechaFinEsc): array
    {
        if (empty($ids)) return [];
        $sentencia = $this->db->query("
            SELECT l.idArticulo, SUM(l.nunidades) AS ventas_total
            FROM ticketslinea l
            INNER JOIN ticketst c ON c.id = l.idticketst
            WHERE l.idArticulo IN ($ids)
              AND DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
              AND c.estado      = 'Cerrado'
              AND l.estadoLinea = 'Activo'
            GROUP BY l.idArticulo
        ");
        if (!$sentencia) return [];
        $map = [];
        while ($fila = $sentencia->fetch_assoc()) {
            $map[(int)$fila['idArticulo']] = (float)$fila['ventas_total'];
        }
        return $map;
    }

    /**
     * Enriquece cada incidencia C2 con datos de la recepción anterior al evento.
     * Modifica el array por referencia.
     */
    public function queryDetalleC2(array &$incidencias, string $fechaInicioEsc, string $fechaFinEsc): void
    {
        if (empty($incidencias)) return;

        $ids     = array_unique(array_column($incidencias, 'idArticulo'));
        $ids_str = implode(',', $ids);
        $fi_ext  = $this->db->real_escape_string(date('Y-m-d', strtotime($fechaInicioEsc . ' -90 days')));

        $sentencia = $this->db->query("
            SELECT l.idArticulo, DATE(c.Fecha) AS fecha, SUM(l.nunidades) AS nunidades
            FROM albprolinea l
            INNER JOIN albprot c ON c.id = l.idalbpro
            WHERE l.idArticulo IN ($ids_str)
              AND DATE(c.Fecha) BETWEEN '$fi_ext' AND '$fi'
              AND c.estado      IN ('Guardado','Facturado')
              AND l.estadoLinea = 'Activo'
              AND l.nunidades       > 0
            GROUP BY l.idArticulo, DATE(c.Fecha)
            ORDER BY l.idArticulo, DATE(c.Fecha)
        ");
        $rec_por_art = [];
        if ($sentencia) {
            while ($fila = $sentencia->fetch_assoc()) {
                $rec_por_art[(int)$fila['idArticulo']][] = ['fecha' => $fila['fecha'], 'nunidades' => (float)$fila['nunidades']];
            }
        }

        $smt2 = $this->db->query("
            SELECT l.idArticulo, DATE(c.Fecha) AS fecha, SUM(l.nunidades) AS qty
            FROM ticketslinea l
            INNER JOIN ticketst c ON c.id = l.idticketst
            WHERE l.idArticulo IN ($ids_str)
              AND DATE(c.Fecha) BETWEEN '$fi_ext' AND '$ff'
              AND c.estado      = 'Cerrado'
              AND l.estadoLinea = 'Activo'
            GROUP BY l.idArticulo, DATE(c.Fecha)
        ");
        $vtas_por_art = [];
        if ($smt2) {
            while ($fila = $smt2->fetch_assoc()) {
                $vtas_por_art[(int)$fila['idArticulo']][] = ['fecha' => $fila['fecha'], 'qty' => (float)$fila['qty']];
            }
        }

        foreach ($incidencias as &$inc) {
            $id           = $inc['idArticulo'];
            $fecha_actual = $inc['fecha'];

            $anterior = null;
            foreach ($rec_por_art[$id] ?? [] as $rec) {
                if ($rec['fecha'] < $fecha_actual) $anterior = $rec;
                elseif ($rec['fecha'] >= $fecha_actual) break;
            }

            if ($anterior !== null) {
                $dias = (int)((strtotime($fecha_actual) - strtotime($anterior['fecha'])) / 86400);
                $inc['dias_desde_anterior'] = $dias;
                $inc['nunidades_anterior']      = $anterior['nunidades'];

                $ventas_entre = 0.0;
                foreach ($vtas_por_art[$id] ?? [] as $v) {
                    if ($v['fecha'] > $anterior['fecha'] && $v['fecha'] < $fecha_actual) {
                        $ventas_entre += $v['qty'];
                    }
                }
                $inc['ventas_entre_recepciones'] = $ventas_entre;
            } else {
                $inc['dias_desde_anterior']      = null;
                $inc['nunidades_anterior']            = null;
                $inc['ventas_entre_recepciones']  = null;
            }
        }
        unset($inc);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // C3
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * C3 — Artículos con entrada en la ventana y su última venta conocida.
     *
     * @return array  Filas raw (idArticulo, ultima_venta, n_ventas_historico, ...) o ['error' => ...]
     */
    public function queryUltimaVentaC3(
        string $fi_m,
        string $ff_m,
        string $fi_s,
        string $wf,
        string $wi,
        int    $min_u,
        int    $dias_post = 14,
        float  $multiplicador_cadencia = 3.0
    ): array {
        $c3a_floor_dias = max(3, (int)ceil($multiplicador_cadencia));
        $sql = "
            SELECT ent.idArticulo,
                   MAX(sal.fecha)              AS ultima_venta,
                   COUNT(DISTINCT sal.fecha)   AS n_ventas_historico,
                   MIN(sal_post.fecha)         AS primera_venta_post,
                   ent.n_entradas,
                   ent.cantidad_recibida,
                   ent.fecha_primera_entrada,
                   ent.n_devoluciones,
                   ent.cantidad_devuelta
            FROM (
                SELECT l.idArticulo,
                       COUNT(DISTINCT CASE WHEN l.nunidades > 0 THEN c.id END)        AS n_entradas,
                       SUM(CASE WHEN l.nunidades > 0 THEN l.nunidades ELSE 0 END)         AS cantidad_recibida,
                       MIN(CASE WHEN l.nunidades > 0 THEN DATE(c.Fecha) END)          AS fecha_primera_entrada,
                       COUNT(DISTINCT CASE WHEN l.nunidades < 0 THEN c.id END)        AS n_devoluciones,
                       SUM(CASE WHEN l.nunidades < 0 THEN ABS(l.nunidades) ELSE 0 END)   AS cantidad_devuelta
                FROM albprolinea l
                INNER JOIN albprot   c ON c.id        = l.idalbpro
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi_m' AND '$ff_m'
                  AND c.estado      IN ('Guardado','Facturado')
                  AND l.estadoLinea = 'Activo'
                  $wf $wi
                GROUP BY l.idArticulo
                HAVING COUNT(DISTINCT CASE WHEN l.nunidades > 0 THEN c.id END) > 0
            ) AS ent
            LEFT JOIN (
                SELECT l.idArticulo, DATE(c.Fecha) AS fecha
                FROM ticketslinea l
                INNER JOIN ticketst c ON c.id = l.idticketst
                WHERE DATE(c.Fecha) BETWEEN '$fi_s' AND '$ff_m'
                  AND c.estado      = 'Cerrado'
                  AND l.estadoLinea = 'Activo'
                UNION ALL
                SELECT l.idArticulo, DATE(c.Fecha) AS fecha
                FROM albclilinea l
                INNER JOIN albclit c ON c.id = l.idalbcli
                WHERE DATE(c.Fecha) BETWEEN '$fi_s' AND '$ff_m'
                  AND c.estado      IN ('Guardado','Procesado')
                  AND l.estadoLinea = 'Activo'
            ) AS sal ON sal.idArticulo = ent.idArticulo
            LEFT JOIN (
                SELECT l.idArticulo, DATE(c.Fecha) AS fecha
                FROM ticketslinea l
                INNER JOIN ticketst c ON c.id = l.idticketst
                WHERE DATE(c.Fecha) BETWEEN DATE_ADD('$ff_m', INTERVAL 1 DAY)
                                        AND DATE_ADD('$ff_m', INTERVAL $dias_post DAY)
                  AND c.estado      = 'Cerrado'
                  AND l.estadoLinea = 'Activo'
                UNION ALL
                SELECT l.idArticulo, DATE(c.Fecha) AS fecha
                FROM albclilinea l
                INNER JOIN albclit c ON c.id = l.idalbcli
                WHERE DATE(c.Fecha) BETWEEN DATE_ADD('$ff_m', INTERVAL 1 DAY)
                                        AND DATE_ADD('$ff_m', INTERVAL $dias_post DAY)
                  AND c.estado      IN ('Guardado','Procesado')
                  AND l.estadoLinea = 'Activo'
            ) AS sal_post ON sal_post.idArticulo = ent.idArticulo
            GROUP BY ent.idArticulo
            HAVING MAX(sal.fecha) IS NULL
                OR DATEDIFF('$ff_m', MAX(sal.fecha)) / 7.0 >= $min_u
                OR DATEDIFF('$ff_m', MAX(sal.fecha)) >= $c3a_floor_dias
        ";
        $sentencia = $this->db->query($sql);
        if (!$sentencia) return ['error' => $this->db->error];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        return $filas;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // C7
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * C7 — Fechas de recepción de proveedor por artículo físico en el periodo.
     *
     * @return array  Filas [{idArticulo, fecha, cantidad}] o ['error' => ...]
     */
    public function queryRecepcionesFechasC7(
        string $fechaInicioEsc,
        string $fechaFinEsc,
        string $wf,
        string $wi
    ): array {
        $sql = "
            SELECT l.idArticulo, DATE(c.Fecha) AS fecha, SUM(l.nunidades) AS cantidad
            FROM albprolinea l
            INNER JOIN albprot c  ON c.id = l.idalbpro
            INNER JOIN articulos a ON a.idArticulo = l.idArticulo
            WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
              AND c.estado IN ('Guardado','Facturado','Exportado','Importado')
              AND l.estadoLinea = 'Activo'
              AND l.nunidades > 0
              $wf $wi
            GROUP BY l.idArticulo, DATE(c.Fecha)
            ORDER BY l.idArticulo, DATE(c.Fecha)
        ";
        $sentencia = $this->db->query($sql);
        if ($sentencia === false) return ['error' => 'C7 recepciones: ' . $this->db->error];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        $sentencia->free();
        return $filas;
    }

    /**
     * Artículos con al menos un albarán de cliente en la ventana fi–ff.
     *
     * @return array  Set de idArticulo (int) con actividad albcli, o ['error'=>...]
     */
    public function queryHasAlbcliC7(string $fechaInicioEsc, string $fechaFinEsc, string $ids_str): array
    {
        if (empty($ids_str)) return [];
        $sql = "
            SELECT DISTINCT l.idArticulo
            FROM albclilinea l
            INNER JOIN albclit a ON a.id = l.idalbcli
            WHERE DATE(a.Fecha) BETWEEN '$fi' AND '$ff'
              AND a.estado IN ('Guardado','Procesado')
              AND l.idArticulo IN ($ids_str)
        ";
        $sentencia = $this->db->query($sql);
        if ($sentencia === false) return ['error' => 'C7 albcli check: ' . $this->db->error];
        $ids = [];
        while ($fila = $sentencia->fetch_assoc()) $ids[(int)$fila['idArticulo']] = true;
        $sentencia->free();
        return $ids;
    }

    /**
     * C7 — Timeline de movimientos diarios (delta neto) para los artículos candidatos.
     *
     * @return array  Filas [{idArticulo, fecha, day_delta}] o ['error' => ...]
     */
    public function queryTimelineMovimientosC7(
        string $fechaInicioEsc,
        string $fechaFinEsc,
        string $ids_str
    ): array {
        if (empty($ids_str)) return [];
        $sql = "
            SELECT idArticulo, fecha, SUM(delta) AS day_delta
            FROM (
                SELECT l.idArticulo, DATE(c.Fecha) AS fecha, l.nunidades AS delta
                FROM albprolinea l
                INNER JOIN albprot c ON c.id = l.idalbpro
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado IN ('Guardado','Facturado','Exportado','Importado')
                  AND l.estadoLinea = 'Activo'
                  AND l.idArticulo IN ($ids_str)
                UNION ALL
                SELECT l.idArticulo, DATE(t.Fecha) AS fecha, -l.nunidades AS delta
                FROM ticketslinea l
                INNER JOIN ticketst t ON t.id = l.idticketst
                WHERE DATE(t.Fecha) BETWEEN '$fi' AND '$ff'
                  AND t.estado = 'Cerrado'
                  AND l.estadoLinea = 'Activo'
                  AND l.idArticulo IN ($ids_str)
                UNION ALL
                SELECT l.idArticulo, DATE(a.Fecha) AS fecha, -l.nunidades AS delta
                FROM albclilinea l
                INNER JOIN albclit a ON a.id = l.idalbcli
                WHERE DATE(a.Fecha) BETWEEN '$fi' AND '$ff'
                  AND a.estado IN ('Guardado','Procesado')
                  AND l.estadoLinea = 'Activo'
                  AND l.idArticulo IN ($ids_str)
            ) AS all_movs
            GROUP BY idArticulo, fecha
            ORDER BY idArticulo, fecha
        ";
        $sentencia = $this->db->query($sql);
        if ($sentencia === false) return ['error' => 'C7 timeline: ' . $this->db->error];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        $sentencia->free();
        return $filas;
    }

    /**
     * C7c — Metadatos de artículos para la detección de cruces.
     * Devuelve [idArticulo => ['nombre' => string, 'tipo' => string, 'familias' => int[]]].
     */
    public function queryMetaC7c(string $ids_str): array
    {
        if (empty($ids_str)) return [];
        $meta = [];

        $sentencia = $this->db->query(
            "SELECT idArticulo, articulo_name, tipo FROM articulos WHERE idArticulo IN ($ids_str)"
        );
        if ($sentencia) {
            while ($fila = $sentencia->fetch_assoc()) {
                $meta[(int)$fila['idArticulo']] = [
                    'nombre'      => (string)$fila['articulo_name'],
                    'tipo'        => (string)$fila['tipo'],
                    'familias'    => [],
                    'familias_n2' => [],
                    'familias_n1' => [],
                ];
            }
        }

        $sentencia = $this->db->query("
            SELECT af.idArticulo,
                   vj.idFamilia,
                   vj.idN1,
                   vj.idN2
            FROM   articulosFamilias af
            JOIN   vw_jerarquias_familias vj ON vj.idFamilia = af.idFamilia
            WHERE  af.idArticulo IN ($ids_str)
        ");
        if ($sentencia) {
            while ($fila = $sentencia->fetch_assoc()) {
                $id = (int)$fila['idArticulo'];
                if (!isset($meta[$id])) continue;
                $meta[$id]['familias'][]    = (int)$fila['idFamilia'];
                if (!empty($fila['idN2'])) $meta[$id]['familias_n2'][] = (int)$fila['idN2'];
                if (!empty($fila['idN1'])) $meta[$id]['familias_n1'][] = (int)$fila['idN1'];
            }
        }

        foreach ($meta as &$m) {
            $m['familias']    = array_unique($m['familias']);
            $m['familias_n2'] = array_unique($m['familias_n2']);
            $m['familias_n1'] = array_unique($m['familias_n1']);
        }
        unset($m);

        return $meta;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // C9
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * C9 paso 1 — Recepciones reales (proveedor no especial) por artículo.
     *
     * @return array  Filas [{idArticulo, fecha, cantidad, es_post_periodo}] o ['error'=>...]
     */
    public function queryRecepcionesC9(
        string $fechaInicioEsc,
        string $ff_mov,
        string $wf,
        string $wi,
        int    $dias_post = 60
    ): array {
        $ff_post = $this->db->real_escape_string(
            date('Y-m-d', strtotime("$ff_mov +$dias_post days"))
        );
        $ff_esc = $this->db->real_escape_string($ff_mov);
        $sql = "
            SELECT
                l.idArticulo,
                DATE(c.Fecha)            AS fecha,
                SUM(l.nunidades)         AS cantidad,
                DATE(c.Fecha) > '$ff_esc' AS es_post_periodo
            FROM albprolinea  l
            INNER JOIN albprot     c ON c.id          = l.idalbpro
            INNER JOIN articulos   a ON a.idArticulo  = l.idArticulo
            INNER JOIN proveedores p ON p.idProveedor = c.idProveedor
            WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff_post'
              AND c.estado      IN ('Guardado','Facturado','Exportado','Importado')
              AND l.estadoLinea  = 'Activo'
              AND l.nunidades    > 0
              AND p.estado      != 'Especial'
              $wf $wi
            GROUP BY l.idArticulo, DATE(c.Fecha)
            ORDER BY l.idArticulo, DATE(c.Fecha)
        ";
        $sentencia = $this->db->query($sql);
        if ($sentencia === false) return ['error' => 'C9 recepciones: ' . $this->db->error];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        $sentencia->free();
        return $filas;
    }

    /**
     * C9 paso 1b — Devoluciones ordinarias a proveedor (nunidades < 0, proveedor no especial).
     *
     * @return array  Filas [{idArticulo, fecha, devolucion}] o ['error'=>...]
     */
    public function queryDevolucionesProvC9(
        string $fechaInicioEsc,
        string $ff_post,
        string $ids_str
    ): array {
        if (empty($ids_str)) return [];
        $sql = "
            SELECT
                l.idArticulo,
                DATE(c.Fecha)          AS fecha,
                SUM(ABS(l.nunidades))  AS devolucion
            FROM albprolinea  l
            INNER JOIN albprot     c ON c.id          = l.idalbpro
            INNER JOIN proveedores p ON p.idProveedor = c.idProveedor
            WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff_post'
              AND c.estado      IN ('Guardado','Facturado','Exportado','Importado')
              AND l.estadoLinea  = 'Activo'
              AND l.nunidades    < 0
              AND p.estado      != 'Especial'
              AND l.idArticulo   IN ($ids_str)
            GROUP BY l.idArticulo, DATE(c.Fecha)
            ORDER BY l.idArticulo, DATE(c.Fecha)
        ";
        $sentencia = $this->db->query($sql);
        if ($sentencia === false) return ['error' => 'C9 devoluciones: ' . $this->db->error];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        $sentencia->free();
        return $filas;
    }

    /**
     * C9 paso 2 — Timeline de SALIDAS diarias (solo ventas/albaranes cliente no especiales).
     *
     * @return array  Filas [{idArticulo, fecha, day_delta}] o ['error'=>...]
     */
    public function queryTimelineC9(
        string $fechaInicioEsc,
        string $fechaFinEsc,
        string $ids_str
    ): array {
        if (empty($ids_str)) return [];
        $sql = "
            SELECT idArticulo, fecha, SUM(delta) AS day_delta
            FROM (
                SELECT l.idArticulo, DATE(t.Fecha) AS fecha, l.nunidades AS delta
                FROM ticketslinea l
                INNER JOIN ticketst t ON t.id = l.idticketst
                WHERE DATE(t.Fecha) BETWEEN '$fi' AND '$ff'
                  AND t.estado       = 'Cerrado'
                  AND l.estadoLinea  = 'Activo'
                  AND l.idArticulo  IN ($ids_str)
                UNION ALL
                SELECT l.idArticulo, DATE(a.Fecha) AS fecha, l.nunidades AS delta
                FROM albclilinea l
                INNER JOIN albclit  a  ON a.id         = l.idalbcli
                INNER JOIN clientes cl ON cl.idClientes = a.idCliente
                WHERE DATE(a.Fecha) BETWEEN '$fi' AND '$ff'
                  AND a.estado       IN ('Guardado','Procesado')
                  AND l.estadoLinea  = 'Activo'
                  AND cl.estado     != 'Especial'
                  AND l.idArticulo  IN ($ids_str)
            ) AS all_salidas
            GROUP BY idArticulo, fecha
            ORDER BY idArticulo, fecha
        ";
        $sentencia = $this->db->query($sql);
        if ($sentencia === false) return ['error' => 'C9 timeline: ' . $this->db->error];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        $sentencia->free();
        return $filas;
    }

    /**
     * C9 Q5a — Líneas de albaranes de proveedores con estado='Especial'.
     *
     * @return array  Filas [{idArticulo, fecha, nunidades, idAlbaran}] o ['error'=>...]
     */
    public function queryAlbaranesProvEspecialesC9(
        string $fechaInicioEsc,
        string $fechaFinEsc,
        string $ids_str
    ): array {
        if (empty($ids_str)) return [];
        $sql = "
            SELECT
                l.idArticulo,
                DATE(c.Fecha) AS fecha,
                l.nunidades    AS nunidades,
                c.id          AS idAlbaran
            FROM albprolinea  l
            INNER JOIN albprot     c ON c.id          = l.idalbpro
            INNER JOIN proveedores p ON p.idProveedor = c.idProveedor
            WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
              AND c.estado      IN ('Guardado','Facturado','Exportado','Importado')
              AND l.estadoLinea  = 'Activo'
              AND p.estado       = 'Especial'
              AND c.id IN (
                  SELECT DISTINCT l2.idalbpro
                  FROM albprolinea l2
                  WHERE l2.idArticulo IN ($ids_str)
                    AND l2.estadoLinea = 'Activo'
              )
            ORDER BY c.id, l.idArticulo
        ";
        $sentencia = $this->db->query($sql);
        if ($sentencia === false) return ['error' => 'C9 prov especiales: ' . $this->db->error];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        $sentencia->free();
        return $filas;
    }

    /**
     * C9 Q5b — Albaranes de clientes con estado='Especial' (todos son merma declarada).
     *
     * @return array  Filas [{idArticulo, fecha, nunidades, idAlbaran}] o ['error'=>...]
     */
    public function queryAlbaranesCliEspecialesC9(
        string $fechaInicioEsc,
        string $fechaFinEsc,
        string $ids_str
    ): array {
        if (empty($ids_str)) return [];
        $sql = "
            SELECT l.idArticulo, DATE(a.Fecha) AS fecha, l.nunidades AS nunidades, a.id AS idAlbaran
            FROM albclilinea l
            INNER JOIN albclit  a  ON a.id         = l.idalbcli
            INNER JOIN clientes cl ON cl.idClientes = a.idCliente
            WHERE DATE(a.Fecha) BETWEEN '$fi' AND '$ff'
              AND a.estado      IN ('Guardado','Procesado')
              AND l.estadoLinea  = 'Activo'
              AND cl.estado      = 'Especial'
              AND l.idArticulo  IN ($ids_str)
            ORDER BY a.id, l.idArticulo
        ";
        $sentencia = $this->db->query($sql);
        if ($sentencia === false) return ['error' => 'C9 cli especiales: ' . $this->db->error];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        $sentencia->free();
        return $filas;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Proveedores — Paginación y actividad
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Proveedor — idArticulo vinculados a los proveedores indicados (estado Activo).
     *
     * @return array  Filas raw (idArticulo) o ['error' => ...]
     */
    public function queryIdsArticulosByProveedores(string $ids_prov): array
    {
        $sentencia = $this->db->query(
            "SELECT DISTINCT idArticulo FROM articulosProveedores
             WHERE idProveedor IN ($ids_prov)
               AND estado = 'Activo'"
        );
        if (!$sentencia) return ['error' => $this->db->error];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        return $filas;
    }

    /**
     * Proveedor — idArticulo vinculados a los proveedores indicados (sin filtro de estado).
     *
     * @return array  Filas raw (idArticulo) o ['error' => ...]
     */
    public function queryIdsArticulosByProveedoresTodos(string $ids_prov): array
    {
        $sentencia = $this->db->query(
            "SELECT DISTINCT idArticulo FROM articulosProveedores
             WHERE idProveedor IN ($ids_prov)"
        );
        if (!$sentencia) return ['error' => $this->db->error];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        return $filas;
    }

    /**
     * Paginación de artículos de un proveedor sin filtro de actividad.
     *
     * @return int[]  Array de idArticulo, o array con clave 'error'.
     */
    public function queryArticulosProveedorPaginados(string $ids_prov, int $offset, int $limit): array
    {
        $sentencia = $this->db->query(
            "SELECT DISTINCT ap.idArticulo
             FROM articulosProveedores ap
             INNER JOIN articulos a ON a.idArticulo = ap.idArticulo
             WHERE ap.idProveedor IN ($ids_prov)
               AND ap.estado = 'Activo'
             ORDER BY ap.idArticulo
             LIMIT $limit OFFSET $offset"
        );
        if (!$sentencia) return ['error' => $this->db->error];
        $ids = [];
        while ($fila = $sentencia->fetch_assoc()) $ids[] = (int)$fila['idArticulo'];
        return $ids;
    }

    /**
     * Paginación — DISTINCT idArticulo con actividad en el rango, con LIMIT/OFFSET.
     *
     * @return array  Filas raw (idArticulo) o ['error' => ...]
     */
    public function queryIdsConActividad(
        string $fechaInicioEsc,
        string $fechaFinEsc,
        string $where_fam,
        string $limit_clause,
        string $where_prov = ''
    ): array {
        $sentencia = $this->db->query("
            SELECT DISTINCT idArticulo FROM (
                SELECT l.idArticulo FROM albprolinea l
                INNER JOIN albprot c ON c.id = l.idalbpro
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado IN ('Guardado','Facturado','Exportado','Importado')
                  AND l.estadoLinea = 'Activo'
                  $where_fam $where_prov
                UNION
                SELECT l.idArticulo FROM ticketslinea l
                INNER JOIN ticketst c ON c.id = l.idticketst
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado = 'Cerrado'
                  AND l.estadoLinea = 'Activo'
                  $where_fam $where_prov
                UNION
                SELECT l.idArticulo FROM albclilinea l
                INNER JOIN albclit c ON c.id = l.idalbcli
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado IN ('Guardado','Procesado')
                  AND l.estadoLinea = 'Activo'
                  $where_fam $where_prov
            ) AS sub
            ORDER BY idArticulo
            $limit_clause
        ");
        if (!$sentencia) return ['error' => $this->db->error];
        $filas = [];
        while ($fila = $sentencia->fetch_assoc()) $filas[] = $fila;
        return $filas;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Enriquecimiento
    // ══════════════════════════════════════════════════════════════════════════

    /** Añade el campo 'nombre' (articulo_name) a cada fila de incidencias. */
    public function anadirNombres(array $incidencias): array
    {
        if (empty($incidencias)) return $incidencias;
        try {
            if (!$this->db->ping()) return $incidencias;
        } catch (\mysqli_sql_exception $e) {
            return $incidencias;
        }
        $ids_inc = implode(',', array_unique(array_column($incidencias, 'idArticulo')));
        $sentencia = $this->db->query(
            "SELECT idArticulo, articulo_name FROM articulos WHERE idArticulo IN ($ids_inc)"
        );
        $nombres = [];
        if ($sentencia) {
            while ($fila = $sentencia->fetch_assoc()) {
                $nombres[(int)$fila['idArticulo']] = $fila['articulo_name'];
            }
        }
        foreach ($incidencias as &$inc) {
            $inc['nombre'] = $nombres[$inc['idArticulo']] ?? '';
        }
        unset($inc);
        return $incidencias;
    }
}
