<?php

/**
 * ClasePosstock — Lógica de datos para el informe POSStock.
 *
 * ─── ARQUITECTURA ────────────────────────────────────────────────────────────
 *
 * El cálculo se divide en tres fases que se encadenan:
 *
 *   1. getMovimientosPeriodo($fi, $ff)          [T4.1 — SQL]
 *      UNION ALL de los 3 bloques de movimientos físicos en la ventana:
 *        · entrada_proveedor : albprolinea + albprot
 *        · salida_ticket     : ticketslinea + ticketst
 *        · salida_albcli     : albclilinea + albclit
 *      Devuelve todas las filas individuales ordenadas por (idArticulo, fecha).
 *
 *   2. getStockBase($ids, $fi_stock, $ff_stock)  [T4.2 — SQL]
 *      Mismo UNION ALL pero sobre el rango 01-Ene → día anterior al periodo,
 *      filtrando solo los idArticulo encontrados en el paso 1.
 *      Devuelve saldo_acumulado + ultima_compra + ultima_venta por artículo.
 *
 *   3. calcularStockPrevio($movimientos, $stock_base)  [T4.3 — PHP]
 *      Combina los resultados de 1 y 2 en memoria para calcular, por cada
 *      entrada_proveedor de la ventana:
 *        · stock_previo             = saldo al inicio del día anterior a la entrada
 *        · stock_tras_ultimo_albaran = saldo acumulado tras la última entrada del periodo
 *
 * ─── DECISIÓN: T4.3 EN PHP, NO EN SQL ───────────────────────────────────────
 *
 * MariaDB 10.11 (verificado con SELECT VERSION()) soporta window functions
 * (SUM() OVER, ROW_NUMBER(), etc. disponibles desde 10.2).
 * Se eligió PHP porque T4.1 y T4.2 ya están en memoria y una tercera consulta
 * SQL con CTE + window frame habría replicado la misma lógica con más complejidad
 * y sin ganancia de rendimiento para el volumen de datos esperado (~4.000 filas).
 *
 * ─── ESTADOS DE DOCUMENTO VÁLIDOS ───────────────────────────────────────────
 *
 *   albprot  (entradas proveedor) : Guardado, Facturado, Exportado, Importado
 *     · Exportado: era Guardado y se exportó a XML (sigue siendo movimiento real)
 *     · Importado: creado por importación XML (ClaseAlbaranCompraXML)
 *     · Excluido : Sin Guardar (borrador)
 *
 *   ticketst (salidas ticket)     : Cerrado
 *     · Excluido : Abierto (en curso)
 *
 *   albclit  (salidas cliente)    : Guardado, Procesado
 *     · Excluido : Sin guardar (borrador)
 *
 *
 * ─── REGLA "DÍA ANTERIOR" PARA stock_previo ─────────────────────────────────
 *
 *   stock_previo de una entrada en fecha D = saldo_base + Σ movimientos con fecha < D
 *   Si varias entradas caen el mismo día D, todas comparten el mismo stock_previo
 *   (estado al inicio de D, antes de que llegue ningún albarán de ese día).
 *   Las ventas del mismo día D se excluyen del stock_previo (se contabilizarán
 *   en el stock_previo de entradas de días posteriores).
 *
 * ─── USO ─────────────────────────────────────────────────────────────────────
 *
 *   $p   = new ClasePosstock($BDTpv);
 *   $mov = $p->getMovimientosPeriodo('2025-02-01', '2025-02-28');
 *   $ids = array_unique(array_column($mov, 'idArticulo'));
 *   $base = $p->getStockBase($ids, '2025-01-01', '2025-01-31');
 *   $calc = $p->calcularStockPrevio($mov, $base);
 *   // $calc: array de entradas con stock_previo y stock_tras_ultimo_albaran
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */
class ClasePosstock
{
    private $db;

    // Umbral de stock negativo a partir del cual C6 reconstruye el stock
    // desde la última entrada de proveedor (más fiable que el stockOn acumulado).
    const STOCK_NEGATIVO_UMBRAL = -2.0;


    public function __construct($conexion)
    {
        $this->db = $conexion;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // SQL HELPERS — Construcción de cláusulas WHERE
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Expande una lista de idFamilia a todos sus descendientes usando
     * vw_jerarquias_familias (idN1, idN2 capturan hijos de nivel 1 y 2).
     * Devuelve el IN-clause listo para SQL, o '' si la lista está vacía.
     */
    private function expandirFamilias(array $ids): string
    {
        if (empty($ids)) return '';
        $in = implode(',', array_map('intval', $ids));
        $smt = $this->db->query("
            SELECT DISTINCT idFamilia
            FROM vw_jerarquias_familias
            WHERE idFamilia IN ($in)
               OR idN1      IN ($in)
               OR idN2      IN ($in)
        ");
        if (!$smt) return $in; // fallback: usar IDs originales
        $expanded = [];
        while ($r = $smt->fetch_assoc()) $expanded[] = (int)$r['idFamilia'];
        return implode(',', $expanded);
    }

    /** Construye las cláusulas WHERE de familia (incluir/excluir) para el alias dado. */
    private function _familiaWhere(array $incluir, array $excluir, string $alias = 'l'): string
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
    private function _idsWhere(array $ids_filter, string $alias = 'l'): string
    {
        if (empty($ids_filter)) return '';
        return " AND $alias.idArticulo IN (" . implode(',', array_map('intval', $ids_filter)) . ")";
    }

    // ══════════════════════════════════════════════════════════════════════════
    // SQL QUERIES — Métodos privados de solo consulta, sin lógica de negocio.
    // Reciben strings ya escapados y cláusulas WHERE ya construidas.
    // Devuelven array de filas raw o ['error' => ...] si falla la consulta.
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * T4.1 — UNION ALL de los 3 tipos de movimiento físico en el periodo.
     * Agrupado por (tipo, artículo, fecha) para reducir filas en memoria.
     *
     * @return array  Filas raw o ['error' => ...]
     */
    private function _queryMovimientosPeriodo(
        string $fi,
        string $ff,
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
        $smt = $this->db->query($sql);
        if (!$smt) return ['error' => $this->db->error, 'consulta' => $sql];
        $rows = [];
        while ($row = $smt->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    /**
     * T4.2 — UNION ALL de movimientos en el rango de stock base.
     * Filtra solo los idArticulo indicados.
     *
     * @return array  Filas raw (idArticulo, nunidades_signo, tipo_mov, fecha) o ['error' => ...]
     */
    private function _queryStockBase(string $fi, string $ff, string $ids_str): array
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
        $smt = $this->db->query($sql);
        if (!$smt) return ['error' => $this->db->error, 'consulta' => $sql];
        $rows = [];
        while ($row = $smt->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    /**
     * C4 paso 1 — Todos los idArticulo físicos que cumplen el filtro de familia.
     *
     * @return array  Filas raw (idArticulo) o ['error' => ...]
     */
    private function _queryArticulosFisicos(string $where_familia): array
    {
        $smt = $this->db->query(
            "SELECT idArticulo FROM articulos a $where_familia"
        );
        if (!$smt) return ['error' => $this->db->error];
        $rows = [];
        while ($r = $smt->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    /**
     * C4 paso 2 — idArticulo con cualquier movimiento (los 3 tipos) en [fi, ff].
     * Sin filtro de familia (se aplica en PHP mediante diff con los físicos filtrados).
     *
     * @return array  Filas raw (idArticulo) o ['error' => ...]
     */
    private function _queryIdsConMovimientoC4(string $fi, string $ff): array
    {
        $smt = $this->db->query("
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
        if (!$smt) return ['error' => $this->db->error];
        $rows = [];
        while ($r = $smt->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    /**
     * C4/C5 — Stock rebobinado desde articulosStocks.stockOn hasta ff_esc.
     *
     * stock_en_ff = stockOn_hoy − net_posterior
     * net_posterior = Σ entradas_post_ff − Σ salidas_post_ff
     *
     * @param string $ids_str        IN-clause de idArticulo ya preparado
     * @param string $ff_esc         Fecha fin escapada ('YYYY-MM-DD')
     * @param bool   $solo_positivos Si true, filtra HAVING stock > 0 (usado en C4)
     *
     * @return array  Filas raw (idArticulo, stock) o ['error' => ...]
     */
    private function _queryStockRebobinado(
        string $ids_str,
        string $ff_esc,
        bool   $solo_positivos = false
    ): array {
        $having = $solo_positivos ? 'HAVING stock_en_periodo > 0' : '';
        $smt = $this->db->query("
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
        if (!$smt) return ['error' => $this->db->error];
        $rows = [];
        while ($r = $smt->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    /**
     * C5 paso 1 — Fechas de venta únicas por artículo físico.
     *
     * Por defecto solo incluye tickets de caja. Cuando $incluir_albcli = true
     * se añaden también los albaranes de cliente (útil si representan ventas
     * reales recurrentes y no regularizaciones de stock).
     *
     * @return array  Filas raw (idArticulo, fecha) o ['error' => ...]
     */
    private function _queryVentasFechasC5(
        string $fi,
        string $ff,
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
        $smt = $this->db->query("
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
        if (!$smt) return ['error' => $this->db->error];
        $rows = [];
        while ($r = $smt->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    /**
     * C5 anti-falso-positivo — Primera venta post-periodo por artículo.
     *
     * Para stockouts en curso detectados al final del periodo, comprueba si el
     * artículo vendió en los $dias_post días siguientes a ff_mov. Si es así,
     * la rotura se considera recuperada y se actualiza en PHP sin nueva consulta SQL.
     *
     * @param string $ids_str       IN-clause ya preparado (idArticulo)
     * @param string $fi_esc        Primer día post-periodo escapado (ff_mov + 1)
     * @param string $ff_esc        Último día de la ventana post-periodo escapado
     * @param bool   $incluir_albcli Incluir albaranes de cliente como ventas
     * @return array  [idArticulo => 'YYYY-MM-DD'] primera venta post-periodo
     */
    private function _queryVentasPostPeriodoC5(
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

        $smt = $this->db->query("
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
        if (!$smt) return [];
        $result = [];
        while ($r = $smt->fetch_assoc()) {
            $result[(int)$r['idArticulo']] = $r['primera_venta_post'];
        }
        return $result;
    }

    /**
     * C6 paso 1 — Cantidad vendida por día por artículo físico.
     *
     * Por defecto solo incluye tickets de caja. Cuando $incluir_albcli = true
     * se suman también los albaranes de cliente (útil si representan ventas
     * reales recurrentes y no regularizaciones de stock).
     *
     * @return array  Filas raw (idArticulo, fecha, nunidades_dia) o ['error' => ...]
     */
    private function _queryVentasCantidadesC6(
        string $fi,
        string $ff,
        string $where_fam,
        string $where_ids,
        bool   $incluir_albcli = false,
        bool   $incluir_todos_tipos = false
    ): array {
        if ($incluir_albcli) {
            $smt = $this->db->query("
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
            $smt = $this->db->query("
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
        if (!$smt) return ['error' => $this->db->error];
        $rows = [];
        while ($r = $smt->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    /**
     * C1 — Detalle de actividad en el periodo para los artículos ya identificados con stock negativo.
     * Devuelve n_entradas, ultima_entrada y n_ventas para cada idArticulo del listado.
     * Usado para enriquecer el detalle de C1a con señales de diagnóstico.
     *
     * @param  string $ids  Lista de IDs separados por coma (ya validados como enteros)
     * @return array  ['idArticulo' => ['n_entradas'=>int, 'ultima_entrada'=>string|null, 'n_ventas'=>int]]
     */
    private function _queryDetalleC1(string $ids, string $fi, string $ff): array
    {
        $detalle = [];

        // Recepciones de proveedor en el periodo
        $smt = $this->db->query("
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
        if ($smt) {
            while ($r = $smt->fetch_assoc()) {
                $id = (int)$r['idArticulo'];
                $detalle[$id] = [
                    'n_entradas'     => (int)$r['n_entradas'],
                    'ultima_entrada' => $r['ultima_entrada'],
                    'n_ventas'       => 0,
                ];
            }
        }

        // Líneas de venta en el periodo (tickets + albcli)
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
            while ($r = $smt2->fetch_assoc()) {
                $id = (int)$r['idArticulo'];
                if (!isset($detalle[$id])) {
                    $detalle[$id] = ['n_entradas' => 0, 'ultima_entrada' => null];
                }
                $detalle[$id]['n_ventas'] = (int)$r['n_ventas'];
            }
        }

        return $detalle;
    }

    /**
     * C1b — Confirma hipótesis de timing: comprueba si hay una entrada de proveedor
     * dentro de los $ventana_dias días posteriores a la fecha en que el balance fue mínimo.
     *
     * Solo dispara el badge "Timing recepción" cuando la entrada es temporalmente
     * próxima al negativo, descartando el ruido en periodos largos.
     *
     * @param array $id_fecha_map  [idArticulo => 'YYYY-MM-DD' (fecha_minimo), ...]
     * @param int   $ventana_dias  Días máximos tras fecha_minimo para considerar timing (default 3)
     * @return array  Set de idArticulo con timing confirmado [idArticulo => true]
     */
    private function _queryTimingC1b(array $id_fecha_map, int $ventana_dias = 1): array
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
        $smt = $this->db->query("
            SELECT DISTINCT l.idArticulo
            FROM albprolinea l
            INNER JOIN albprot c ON c.id = l.idalbpro
            WHERE ($where_or)
              AND c.estado      IN ('Guardado','Facturado')
              AND l.estadoLinea = 'Activo'
        ");
        if (!$smt) return [];

        $resultado = [];
        while ($r = $smt->fetch_assoc()) {
            $resultado[(int)$r['idArticulo']] = true;
        }
        return $resultado;
    }

    /**
     * C1 — Proveedor habitual y último proveedor para una lista de artículos.
     *
     * Busca en el rango anual (fi_stock → ff) para tener datos suficientes incluso
     * en períodos de análisis cortos (semanal, quincenal…).
     *
     * Devuelve por artículo:
     *   prov_habitual_nombre  — nombre del proveedor con más albaranes distintos
     *   prov_habitual_n       — número de albaranes de ese proveedor
     *   prov_ultimo_nombre    — nombre del proveedor del último albarán recibido
     *   prov_ultima_fecha     — fecha del último albarán (YYYY-MM-DD)
     *   prov_es_mismo         — true si habitual == último (mismo idProveedor)
     *
     * @param string $ids_str   IDs de artículo separados por coma (ya validados)
     * @param string $fi_stock  Inicio del rango anual (escapado)
     * @param string $ff        Fin del período de análisis (escapado)
     * @return array  [idArticulo => [...]] o vacío si no hay datos
     */
    private function _queryProveedorArticulos(string $ids_str, string $fi_stock, string $ff): array
    {
        if (empty($ids_str)) return [];

        // Frecuencia: proveedor con más albaranes distintos que incluyen el artículo.
        // Desempate: mayor volumen total comprado (SUM nunidades); segundo desempate: albarán más reciente.
        $smt = $this->db->query("
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
        if (!$smt) return [];

        // Por artículo: el primer resultado es el habitual (más albaranes); buscar también el último
        $por_art = [];
        while ($r = $smt->fetch_assoc()) {
            $id = (int)$r['idArticulo'];
            if (!isset($por_art[$id])) {
                // Primer resultado = proveedor habitual
                $por_art[$id] = [
                    'prov_habitual_id'     => (int)$r['idProveedor'],
                    'prov_habitual_nombre' => $r['nombre'],
                    'prov_habitual_n'      => (int)$r['n_albaranes'],
                    'prov_ultimo_id'       => (int)$r['idProveedor'],
                    'prov_ultimo_nombre'   => $r['nombre'],
                    'prov_ultima_fecha'    => $r['ultima_fecha'],
                ];
            } else {
                // Comprobar si este proveedor tiene una fecha más reciente
                if ($r['ultima_fecha'] > $por_art[$id]['prov_ultima_fecha']) {
                    $por_art[$id]['prov_ultimo_id']     = (int)$r['idProveedor'];
                    $por_art[$id]['prov_ultimo_nombre'] = $r['nombre'];
                    $por_art[$id]['prov_ultima_fecha']  = $r['ultima_fecha'];
                }
            }
        }

        // Marcar si habitual == último
        foreach ($por_art as &$d) {
            $d['prov_es_mismo'] = ($d['prov_habitual_id'] === $d['prov_ultimo_id']);
        }
        unset($d);

        return $por_art;
    }

    /**
     * C7b-011: precio medio ponderado de compra por artículo en la ventana dada.
     * Fórmula: SUM(costeSiva × nunidades) / SUM(nunidades) sobre albaranes confirmados.
     * Devuelve [idArticulo => precio_medio_compra].
     */
    private function _queryPrecioMedioCompra(string $ids_str, string $fi, string $ff): array
    {
        if (empty($ids_str)) return [];

        $smt = $this->db->query("
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
        if (!$smt) return [];

        $result = [];
        while ($r = $smt->fetch_assoc()) {
            if ($r['precio_medio'] !== null) {
                $result[(int)$r['idArticulo']] = (float)$r['precio_medio'];
            }
        }
        return $result;
    }

    /**
     * C1 — Delta total y mínimo de la suma acumulada por artículo mediante window function.
     * Una fila por artículo con delta_total (saldo del periodo) y min_running (mínimo acumulado).
     * Solo devuelve artículos donde MIN(cum_sum) < 0 OR SUM(day_delta) < 0.
     *
     * @return array  Filas raw (idArticulo, delta_total, min_running) o ['error' => ...]
     */
    private function _queryDeltasC1(
        string $fi,
        string $ff,
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
        $smt = $this->db->query($sql);
        if (!$smt) return ['error' => $this->db->error];
        $rows = [];
        while ($r = $smt->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    /**
     * C2 — Entradas de proveedor con su suma acumulada de movimientos anteriores
     * calculada mediante window function (SUM OVER ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING).
     *
     * @return array  Filas raw (idArticulo, fecha, nunidades, cum_before) o ['error' => ...]
     */
    private function _queryEntradasC2(
        string $fi,
        string $ff,
        string $wf,
        string $wi
    ): array {
        $sql = "
            SELECT e.idArticulo, e.fecha, e.nunidades,
                   COALESCE(r.cum_before, 0) AS cum_before
            FROM (
                -- Entradas positivas de proveedor agrupadas por artículo y fecha
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
                -- Running sum de todos los movimientos hasta la fecha anterior (exclusive)
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
        $smt = $this->db->query($sql);
        if (!$smt) return ['error' => $this->db->error];
        $rows = [];
        while ($r = $smt->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    /**
     * C3 — Artículos con entrada en la ventana y su última venta conocida.
     * Pre-filtrado SQL: solo artículos cuya última venta es >= min_umbral semanas atrás (o nula).
     *
     * @return array  Filas raw (idArticulo, ultima_venta) o ['error' => ...]
     */
    private function _queryUltimaVentaC3(
        string $fi_m,
        string $ff_m,
        string $fi_s,
        string $wf,
        string $wi,
        int    $min_u,
        int    $dias_post = 14,
        float  $multiplicador_cadencia = 3.0
    ): array {
        // Mínimo floor SQL para C3a: al menos ceil(multiplicador) días sin venta
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
                -- Ventas en los $dias_post días posteriores al periodo: detecta falsos positivos
                -- de C3b-nunca cuando el artículo entra al final del rango y aún no ha vendido
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
        $smt = $this->db->query($sql);
        if (!$smt) return ['error' => $this->db->error];
        $rows = [];
        while ($r = $smt->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    // ── C7 ────────────────────────────────────────────────────────────────────

    /**
     * C7 — Fechas de recepción de proveedor por artículo físico en el periodo.
     *
     * Devuelve una fila por (idArticulo, fecha) de recepción.
     * Se usa en PHP para filtrar artículos con ≥ N fechas distintas de recepción.
     *
     * @return array  Filas [{idArticulo, fecha}] o ['error' => ...]
     */
    private function _queryRecepcionesFechasC7(
        string $fi,
        string $ff,
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
        $res = $this->db->query($sql);
        if ($res === false) return ['error' => 'C7 recepciones: ' . $this->db->error];
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        $res->free();
        return $rows;
    }

    /**
     * C7 — Timeline de movimientos diarios (entradas + salidas de ticket + salidas albcli)
     * para los artículos candidatos. Devuelve el delta neto por (idArticulo, fecha).
     *
     * @param  string $ids_str  IN-clause de idArticulo ya preparado
     * @return array  Filas [{idArticulo, fecha, day_delta}] o ['error' => ...]
     */
    /**
     * Artículos con al menos un albarán de cliente en la ventana fi–ff.
     * Usado en C7a para enriquecer posible_causa cuando hay transferencias
     * inter-tienda registradas (un albaran no marcado inflaría el floor).
     *
     * @return array  Set de idArticulo (int) con actividad albcli, o ['error'=>...]
     */
    private function _queryHasAlbcliC7(string $fi, string $ff, string $ids_str): array
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
        $res = $this->db->query($sql);
        if ($res === false) return ['error' => 'C7 albcli check: ' . $this->db->error];
        $ids = [];
        while ($row = $res->fetch_assoc()) $ids[(int)$row['idArticulo']] = true;
        $res->free();
        return $ids;
    }

    private function _queryTimelineMovimientosC7(
        string $fi,
        string $ff,
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
        $res = $this->db->query($sql);
        if ($res === false) return ['error' => 'C7 timeline: ' . $this->db->error];
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        $res->free();
        return $rows;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  C9 — Merma por backstaging (queries)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * C9 paso 1 — Recepciones reales (proveedor no especial, nunidades>0) por artículo.
     * Variante de _queryRecepcionesFechasC7 que excluye proveedores especiales y amplía
     * el rango hasta ff_post para capturar la primera recepción post-periodo (cierre del
     * último ciclo analizable).
     *
     * @param  string $fi          Inicio del periodo analizado (fi_mov)
     * @param  string $ff_mov      Último día del periodo analizado
     * @param  string $wf          WHERE de familia
     * @param  string $wi          WHERE de idArticulo
     * @param  int    $dias_post   Días a ampliar tras ff_mov (default 60)
     * @return array  Filas [{idArticulo, fecha, cantidad, es_post_periodo}] o ['error'=>...]
     */
    private function _queryRecepcionesC9(
        string $fi,
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
        $res = $this->db->query($sql);
        if ($res === false) return ['error' => 'C9 recepciones: ' . $this->db->error];
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        $res->free();
        return $rows;
    }

    /**
     * C9 paso 1b — Devoluciones ordinarias a proveedor (nunidades < 0, proveedor no especial).
     * Devuelve la cantidad devuelta (valor absoluto) por artículo y fecha.
     * Se usa en _calcularLotesC9 para netear E_t de cada lote.
     *
     * @param  string $fi        Inicio del periodo analizado (fi_mov)
     * @param  string $ff_post   Fin extendido (ff_mov + dias_post)
     * @param  string $ids_str   Lista de IDs artículo para WHERE IN
     * @return array  Filas [{idArticulo, fecha, devolucion}] o ['error'=>...]
     */
    private function _queryDevolucionesProvC9(
        string $fi,
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
        $res = $this->db->query($sql);
        if ($res === false) return ['error' => 'C9 devoluciones: ' . $this->db->error];
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        $res->free();
        return $rows;
    }

    /**
     * C9 paso 2 — Timeline de SALIDAS diarias (solo ventas/albaranes cliente).
     * NO incluye recepciones de proveedor: E_t viene de _queryRecepcionesC9.
     * Excluye clientes con estado='Especial' (merma declarada, gestionada en Q5b).
     *
     * @return array  Filas [{idArticulo, fecha, day_delta}] (day_delta siempre >= 0) o ['error'=>...]
     */
    private function _queryTimelineC9(
        string $fi,
        string $ff,
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
        $res = $this->db->query($sql);
        if ($res === false) return ['error' => 'C9 timeline: ' . $this->db->error];
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        $res->free();
        return $rows;
    }

    /**
     * C9 paso Q5a — Líneas de albaranes de proveedores con estado='Especial'.
     * Cada fila lleva idAlbaran para que en PHP se clasifique:
     *   - Mezcla de signos en el mismo albarán → cruce (excluir del flujo).
     *   - Todas las líneas negativas → merma declarada (acumular aparte).
     *
     * @return array  Filas [{idArticulo, fecha, nunidades, idAlbaran}] o ['error'=>...]
     */
    private function _queryAlbaranesProvEspecialesC9(
        string $fi,
        string $ff,
        string $ids_str
    ): array {
        if (empty($ids_str)) return [];
        // Devuelve TODAS las líneas de los albaranes donde algún artículo del lote aparece,
        // no solo las líneas de ids_str. Esto permite detectar cruces (signos mixtos en el
        // mismo albarán para distintos artículos) incluso cuando el lote analizado es pequeño.
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
        $res = $this->db->query($sql);
        if ($res === false) return ['error' => 'C9 prov especiales: ' . $this->db->error];
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        $res->free();
        return $rows;
    }

    /**
     * C9 paso Q5b — Albaranes de clientes con estado='Especial' (todos son merma declarada).
     * Se excluyen de V_t y se acumulan aparte como merma_declarada.
     *
     * @return array  Filas [{idArticulo, fecha, cantidad}] o ['error'=>...]
     */
    private function _queryAlbaranesCliEspecialesC9(
        string $fi,
        string $ff,
        string $ids_str
    ): array {
        if (empty($ids_str)) return [];
        // Devuelve líneas individuales con idAlbaran para poder detectar cruces
        // intra-albarán (signos mixtos por artículo) igual que el lado proveedor.
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
        $res = $this->db->query($sql);
        if ($res === false) return ['error' => 'C9 cli especiales: ' . $this->db->error];
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        $res->free();
        return $rows;
    }

    /**
     * Proveedor — idArticulo vinculados a los proveedores indicados (estado Activo).
     *
     * @param  string $ids_prov  IN-clause de idProveedor ya preparado
     * @return array  Filas raw (idArticulo) o ['error' => ...]
     */
    private function _queryIdsArticulosByProveedores(string $ids_prov): array
    {
        $smt = $this->db->query(
            "SELECT DISTINCT idArticulo FROM articulosProveedores
             WHERE idProveedor IN ($ids_prov)
               AND estado = 'Activo'"
        );
        if (!$smt) return ['error' => $this->db->error];
        $rows = [];
        while ($r = $smt->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    /**
     * Proveedor — idArticulo vinculados a los proveedores indicados (sin filtro de estado).
     * Usado por C6b: un artículo puede estar inactivo en la relación proveedor
     * pero seguir en stock y vendiendo, y debe evaluarse para el punto de pedido.
     *
     * @param  string $ids_prov  IN-clause de idProveedor ya preparado
     * @return array  Filas raw (idArticulo) o ['error' => ...]
     */
    private function _queryIdsArticulosByProveedoresTodos(string $ids_prov): array
    {
        $smt = $this->db->query(
            "SELECT DISTINCT idArticulo FROM articulosProveedores
             WHERE idProveedor IN ($ids_prov)"
        );
        if (!$smt) return ['error' => $this->db->error];
        $rows = [];
        while ($r = $smt->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    /**
     * Paginación de artículos de un proveedor sin filtro de actividad en el periodo.
     * Usado cuando proveedor_todos_productos=true para analizar todos los artículos
     * del proveedor independientemente del rango analizado.
     *
     * @return int[]  Array de idArticulo, o array con clave 'error'.
     */
    private function _queryArticulosProveedorPaginados(string $ids_prov, int $offset, int $limit): array
    {
        $smt = $this->db->query(
            "SELECT DISTINCT ap.idArticulo
             FROM articulosProveedores ap
             INNER JOIN articulos a ON a.idArticulo = ap.idArticulo
             WHERE ap.idProveedor IN ($ids_prov)
               AND ap.estado = 'Activo'
             ORDER BY ap.idArticulo
             LIMIT $limit OFFSET $offset"
        );
        if (!$smt) return ['error' => $this->db->error];
        $ids = [];
        while ($r = $smt->fetch_assoc()) $ids[] = (int)$r['idArticulo'];
        return $ids;
    }

    /**
     * C6 — Fechas únicas de albarán por proveedor en el rango dado.
     * Usado para estimar el lead time desde el intervalo entre albaranes consecutivos.
     *
     * @return array  Filas raw (idProveedor, fecha_albaran) o ['error' => ...]
     */
    private function _queryFechasAlbaranesByProveedores(string $fi, string $ff, string $ids_prov): array
    {
        $smt = $this->db->query("
            SELECT idProveedor, DATE(Fecha) AS fecha_albaran
            FROM albprot
            WHERE DATE(Fecha) BETWEEN '$fi' AND '$ff'
              AND estado       IN ('Guardado','Facturado','Exportado','Importado')
              AND idProveedor  IN ($ids_prov)
            GROUP BY idProveedor, DATE(Fecha)
            ORDER BY idProveedor, DATE(Fecha)
        ");
        if (!$smt) return ['error' => $this->db->error];
        $rows = [];
        while ($r = $smt->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    /**
     * C6 — Reconstrucción de stock desde la última entrada de proveedor.
     *
     * Cuando el stock rebobinado está muy por debajo de -2 (errores de inventario,
     * pesajes mal registrados, etc.) este método ofrece una estimación más fiable:
     *   stock_reconstituido = nunidades_última_entrada − ventas_desde_esa_entrada_hasta_ff_esc
     *
     * Solo se aplica a los artículos cuyo stock rebobinado < STOCK_NEGATIVO_UMBRAL.
     *
     * @param  string $ids_str  IDs de artículo separados por coma (ya validados)
     * @param  string $ff_esc   Fecha tope de ventas (= hoy para C6b), ya escapada
     * @return array  [idArticulo => stock_reconstituido] o ['error' => ...]
     */
    private function _queryStockReconstituido(string $ids_str, string $ff_esc): array
    {
        $smt = $this->db->query("
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
        if (!$smt) return ['error' => $this->db->error];
        $result = [];
        while ($r = $smt->fetch_assoc()) {
            $result[(int)$r['idArticulo']] = (float)$r['stock_reconstituido'];
        }
        return $result;
    }

    /**
     * C6 — Calcula el lead time medio (días entre albaranes consecutivos)
     * para los proveedores indicados en el rango anual [fi_stock, ff_mov].
     *
     * Si hay varios proveedores se promedia el LT individual de cada uno.
     * Devuelve 0 si no hay suficientes albaranes (< 2 por proveedor) en ninguno.
     *
     * @return int  Días de LT calculado, o 0 si no hay datos suficientes.
     */
    private function _calcularLeadTimeProveedores(string $fi_stock, string $ff_mov, array $proveedores_incluir): int
    {
        if (empty($proveedores_incluir)) return 0;

        $fi       = $this->db->real_escape_string($fi_stock);
        $ff       = $this->db->real_escape_string($ff_mov);
        $ids_prov = implode(',', array_map('intval', $proveedores_incluir));

        $rows = $this->_queryFechasAlbaranesByProveedores($fi, $ff, $ids_prov);
        if (isset($rows['error']) || empty($rows)) return 0;

        // Agrupar fechas por proveedor
        $por_proveedor = [];
        foreach ($rows as $r) {
            $por_proveedor[(int)$r['idProveedor']][] = $r['fecha_albaran'];
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

    /**
     * Paginación — DISTINCT idArticulo con actividad en el rango, con LIMIT/OFFSET.
     *
     * @return array  Filas raw (idArticulo) o ['error' => ...]
     */
    private function _queryIdsConActividad(
        string $fi,
        string $ff,
        string $where_fam,
        string $limit_clause,
        string $where_prov = ''   // cláusula AND idArticulo IN (...) de proveedores
    ): array {
        $smt = $this->db->query("
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
        if (!$smt) return ['error' => $this->db->error];
        $rows = [];
        while ($r = $smt->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // LÓGICA — Métodos de negocio y helpers de transformación
    // ══════════════════════════════════════════════════════════════════════════

    /** Añade el campo 'nombre' (articulo_name) a cada fila de incidencias. */
    private function _anadirNombres(array $incidencias): array
    {
        if (empty($incidencias)) return $incidencias;
        // La conexión puede haberse perdido durante el cálculo estadístico pesado de C7.
        // En PHP 8.1+ con MYSQLI_REPORT_STRICT, ping() puede lanzar mysqli_sql_exception
        // si la conexión está completamente cerrada; se captura para no bloquear al usuario.
        try {
            if (!$this->db->ping()) return $incidencias;
        } catch (\mysqli_sql_exception $e) {
            return $incidencias; // devolver filas sin nombre; no bloquear
        }
        $ids_inc = implode(',', array_unique(array_column($incidencias, 'idArticulo')));
        $smt = $this->db->query(
            "SELECT idArticulo, articulo_name FROM articulos WHERE idArticulo IN ($ids_inc)"
        );
        $nombres = [];
        if ($smt) {
            while ($r = $smt->fetch_assoc()) {
                $nombres[(int)$r['idArticulo']] = $r['articulo_name'];
            }
        }
        foreach ($incidencias as &$inc) {
            $inc['nombre'] = $nombres[$inc['idArticulo']] ?? '';
        }
        unset($inc);
        return $incidencias;
    }

    /** Convierte el resultado de getArticulosSinMovimiento en filas de incidencia caso4. */
    private function _formatearCaso4(array $articulos): array
    {
        $rows = [];
        foreach ($articulos as $id => $art) {
            $rows[] = [
                'idArticulo'    => (int)$id,
                'tipo'          => 'Stock Inactivo en Periodo',
                'severidad'     => 'BAJA',
                'stock_actual'  => $art['saldo_acumulado'],
                'posible_causa' => 'Stock sin actividad en el periodo',
            ];
        }
        return $rows;
    }

    /**
     * T4.1 — Movimientos en la ventana de análisis.
     *
     * Devuelve todos los movimientos (entradas de proveedor + salidas de ticket
     * + salidas de albarán cliente) de artículos físicos en el periodo indicado.
     *
     * @param string $fecha_inicio  'YYYY-MM-DD'
     * @param string $fecha_fin     'YYYY-MM-DD'
     *
     * @return array  Filas con:
     *   tipo_movimiento ('entrada_proveedor' | 'salida_ticket' | 'salida_albcli'),
     *   idArticulo, nunidades, fecha (DATE), idDocumento
     *   — o array con clave 'error' si falla la consulta.
     */
    public function getMovimientosPeriodo($fecha_inicio, $fecha_fin, array $familias_incluir = [], array $familias_excluir = [], array $ids_filter = [])
    {
        $fi = $this->db->real_escape_string($fecha_inicio);
        $ff = $this->db->real_escape_string($fecha_fin);

        $where_familia = '';
        if (!empty($familias_incluir)) {
            $ids = $this->expandirFamilias($familias_incluir);
            if ($ids) $where_familia .= " AND l.idArticulo IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids))";
        }
        if (!empty($familias_excluir)) {
            $ids = $this->expandirFamilias($familias_excluir);
            if ($ids) $where_familia .= " AND l.idArticulo NOT IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids))";
        }

        $where_ids = empty($ids_filter)
            ? ''
            : " AND l.idArticulo IN (" . implode(',', array_map('intval', $ids_filter)) . ")";

        return $this->_queryMovimientosPeriodo($fi, $ff, $where_familia, $where_ids);
    }

    /**
     * T4.2 — Stock base acumulado desde inicio del ejercicio hasta fecha_fin_stock.
     *
     * Solo se calcula para los artículos que tuvieron movimiento en la ventana (T4.1),
     * reduciendo el coste de la consulta.
     *
     * @param array  $ids_articulos   Array de idArticulo obtenidos en T4.1
     * @param string $fecha_inicio    'YYYY-MM-DD'  (01-Ene del ejercicio)
     * @param string $fecha_fin       'YYYY-MM-DD'  (día anterior al inicio de movimientos)
     *
     * @return array  Indexado por idArticulo con:
     *   saldo_acumulado (DECIMAL), ultima_compra (DATE|null), ultima_venta (DATE|null)
     *   — o array con clave 'error' si falla la consulta.
     */
    public function getStockBase(array $ids_articulos, $fecha_inicio, $fecha_fin)
    {
        if (empty($ids_articulos)) {
            return [];
        }

        $fi  = $this->db->real_escape_string($fecha_inicio);
        $ff  = $this->db->real_escape_string($fecha_fin);
        $ids = implode(',', array_map('intval', $ids_articulos));

        $rows = $this->_queryStockBase($fi, $ff, $ids);
        if (isset($rows['error'])) return $rows;

        $resultado = [];
        foreach ($rows as $row) {
            $resultado[(int)$row['idArticulo']] = [
                'saldo_acumulado' => (float)$row['saldo_acumulado'],
                'ultima_compra'   => $row['ultima_compra'],
                'ultima_venta'    => $row['ultima_venta'],
            ];
        }
        return $resultado;
    }

    /**
     * T4.3 — Calcula stock_previo y stock_tras_ultimo_albaran para cada entrada de proveedor.
     *
     * Combina en PHP los resultados de T4.1 y T4.2, evitando una consulta SQL adicional.
     * MariaDB 10.11 soporta window functions, pero los datos ya están en memoria.
     *
     * Regla "día anterior":
     *   stock_previo de una entrada en fecha D = saldo_base + suma de todos los
     *   movimientos (entradas y salidas) cuya fecha < D.
     *   Si hay varias entradas el mismo día, todas comparten el mismo stock_previo
     *   (estado al inicio del día, antes de que llegue ningún albarán de ese día).
     *
     * stock_tras_ultimo_albaran (por artículo, no por movimiento):
     *   saldo_base + suma de todos los movimientos con fecha <= fecha del último albaran
     *   de entrada en la ventana.
     *
     * @param array $movimientos  Resultado de getMovimientosPeriodo()
     * @param array $stock_base   Resultado de getStockBase(), indexado por idArticulo
     *
     * @return array  Una entrada por cada fila entrada_proveedor con:
     *   idArticulo, idDocumento, fecha, nunidades,
     *   stock_previo, stock_tras_ultimo_albaran
     */
    public function calcularStockPrevio(array $movimientos, array $stock_base): array
    {
        // Agrupar movimientos por idArticulo
        $por_articulo = [];
        foreach ($movimientos as $m) {
            $por_articulo[(int)$m['idArticulo']][] = $m;
        }

        $resultado = [];

        foreach ($por_articulo as $idArticulo => $movs) {
            // Filtrar solo las entradas de proveedor de este artículo
            $entradas = array_filter($movs, fn($m) => $m['tipo_movimiento'] === 'entrada_proveedor');
            if (empty($entradas)) {
                continue;
            }

            $saldo_base_art = isset($stock_base[$idArticulo])
                ? $stock_base[$idArticulo]['saldo_acumulado']
                : 0.0;

            // Acumular delta firmado por fecha (signo: entradas +, salidas -)
            $delta_por_fecha = [];
            foreach ($movs as $m) {
                $signo = ($m['tipo_movimiento'] === 'entrada_proveedor') ? 1.0 : -1.0;
                $delta_por_fecha[$m['fecha']] = ($delta_por_fecha[$m['fecha']] ?? 0.0)
                    + $signo * (float)$m['nunidades'];
            }
            ksort($delta_por_fecha); // orden cronológico

            // Para cada fecha, calcular el saldo AL INICIO del día (antes de sus movimientos)
            $saldo_inicio_dia = [];
            $acum = $saldo_base_art;
            foreach ($delta_por_fecha as $fecha => $delta) {
                $saldo_inicio_dia[$fecha] = $acum; // antes de aplicar el delta del día
                $acum += $delta;
            }

            // stock_tras_ultimo_albaran: acumulado hasta fin del día de la última entrada
            $ultima_fecha_entrada = max(array_column(array_values($entradas), 'fecha'));
            $saldo_tras_ultima = $saldo_base_art;
            foreach ($delta_por_fecha as $fecha => $delta) {
                if ($fecha <= $ultima_fecha_entrada) {
                    $saldo_tras_ultima += $delta;
                }
            }

            // Asignar stock_previo a cada entrada
            foreach ($entradas as $entrada) {
                $resultado[] = [
                    'idArticulo'               => $idArticulo,
                    'idDocumento'              => $entrada['idDocumento'],
                    'fecha'                    => $entrada['fecha'],
                    'nunidades'                    => (float)$entrada['nunidades'],
                    'stock_previo'             => $saldo_inicio_dia[$entrada['fecha']] ?? $saldo_base_art,
                    'stock_tras_ultimo_albaran' => $saldo_tras_ultima,
                ];
            }
        }

        return $resultado;
    }

    /**
     * T4.4 / T5 — Método principal: orquesta T4.1→T4.2→T4.3 y aplica reglas de detección.
     *
     * Estructura de resultados: UNA FILA POR CASO.
     * Un artículo puede generar múltiples filas si dispara varios casos.
     * Esto permite ordenar por severidad y filtrar por tipo independientemente.
     *
     * @param array $params  Claves requeridas:
     *   fecha_inicio_movimientos, fecha_fin_movimientos,
     *   fecha_inicio_stock,       fecha_fin_stock
     *   Claves opcionales:
     *   casos_incluir                (array,  default [] = todos excepto caso4)
     *   umbral_sobrestock            (float,  default 0.5)
     *   umbral_caducidad_semanas     (int,    default 24)
     *   umbral_sin_rotacion_semanas  (int,    default 12)
     *
     * casos_incluir acepta: 'caso1','caso2','caso3a','caso3b','caso4','caso5'.
     * [] o ausente = C1+C2+C3a+C3b+C5 (todos los activos; C4 excluido salvo indicación explícita).
     *
     * @return array  Filas ordenadas CRITICA→ALTA→MEDIA(C2→C5→C3a)→BAJA(C3b→C4),
     *               o array con clave 'error' si falla.
     */
    public function getIncidencias(array $params): array
    {
        $fi_mov   = $params['fecha_inicio_movimientos'];
        $ff_mov   = $params['fecha_fin_movimientos'];
        $fi_stock = $params['fecha_inicio_stock'];
        $ff_stock = $params['fecha_fin_stock'];
        // Ventana estadística ampliada para semana/quincena/mes (±1 periodo)
        $fi_stats = $params['fecha_inicio_stats'] ?? $fi_stock;
        $ff_stats = $params['fecha_fin_stats']    ?? $ff_mov;

        $umbral_sobrestock         = (float)  ($params['umbral_sobrestock']           ?? 0.5);
        $umbral_caducidad          = (int)    ($params['umbral_caducidad_semanas']    ?? 24);
        $umbral_sin_rotacion       = (int)    ($params['umbral_sin_rotacion_semanas'] ?? 12);
        $c3b_dias_post             = (int)    ($params['c3b_dias_post_periodo']       ?? 14);
        $c3a_multiplicador         = (float)  ($params['c3a_multiplicador_cadencia']  ?? 3.0);
        $umbral_rop_mult           = max(3.0, (float)($params['umbral_reconstituir_rop'] ?? 10.0));
        $umbral_stock_neg          = max(0.0, (float)($params['umbral_stock_negativo']   ?? 2.0));
        $min_ventas_c5          = (int)    ($params['min_ventas_c5']               ?? 3);
        $modelo_rotura_c5           = (string) ($params['modelo_rotura_c5']              ?? 'automatico');
        $umbral_confianza_c5        = (float)  ($params['umbral_confianza_poisson']      ?? 0.05);
        $c5_incluir_stock_negativo  = (bool)   ($params['c5_incluir_stock_negativo']     ?? false);
        $binomial_sigma_mult        = (float)  ($params['binomial_sigma_mult']           ?? 3.0);
        $c1_umbral_fraccionado      = (float)  ($params['c1_umbral_fraccionado']         ?? 0.05);
        $c1_umbral_magnitud         = (float)  ($params['c1_umbral_magnitud']            ?? 0.5);
        $c1_umbral_por_venta        = (float)  ($params['c1_umbral_por_venta']           ?? 0.010);
        $c1_timing_ventana_dias     = (int)    ($params['c1_timing_ventana_dias']        ?? 1);
        $c7b_min_recepciones        = (int)    ($params['c7b_min_recepciones']           ?? 3);
        $c7b_umbral_cv              = (float)  ($params['c7b_umbral_cv']                 ?? 0.5);
        $c7b_umbral_cv_peso         = (float)  ($params['c7b_umbral_cv_peso']            ?? 0.75);
        $c7b_umbral_iqr_peso        = (float)  ($params['c7b_umbral_iqr_peso']           ?? 2.0);
        $c7b_umbral_ruido_peso      = (float)  ($params['c7b_umbral_ruido_peso']         ?? 0.5);
        $c7b_umbral_severidad_unidad = (int)   ($params['c7b_umbral_severidad_unidad']   ?? 5);
        $c7b_umbral_severidad_peso  = (float)  ($params['c7b_umbral_severidad_peso']     ?? 2.5);
        $c7b_cascada_exhaustiva     = (bool)   ($params['c7b_cascada_exhaustiva']        ?? false);
        $c7a_umbral_delta_unidad    = (float)  ($params['c7a_umbral_delta_unidad']       ?? 2.0);
        $c7a_umbral_delta_peso      = (float)  ($params['c7a_umbral_delta_peso']         ?? 1.0);
        $c7a_umbral_pvalue          = (float)  ($params['c7a_umbral_pvalue']             ?? 0.10);
        $c7a_umbral_pvalue_alta     = (float)  ($params['c7a_umbral_pvalue_alta']        ?? 0.05);
        $c7a_umbral_alta_delta_unidad = (float)($params['c7a_umbral_alta_delta_unidad']  ?? 10.0);
        $c7a_umbral_alta_delta_peso = (float)  ($params['c7a_umbral_alta_delta_peso']    ?? 5.0);
        $c7a_umbral_alta_slope_unidad = (float)($params['c7a_umbral_alta_slope_unidad']  ?? 2.0);
        $c7a_umbral_alta_slope_peso = (float)  ($params['c7a_umbral_alta_slope_peso']    ?? 1.0);
        $c7a_umbral_snr             = (float)  ($params['c7a_umbral_snr']                ?? 0.15);
        $c7a_cascada_exhaustiva     = (bool)   ($params['c7a_cascada_exhaustiva']        ?? false);
        $familias_incluir    = (array) ($params['familias_incluir'] ?? []);
        $familias_excluir    = (array) ($params['familias_excluir'] ?? []);
        $ids_filter          = (array) ($params['ids_filter']       ?? []);

        // ── Filtro de proveedores → intersectar article IDs ───────────────────
        // ids_proveedor_filter: pre-resueltos por getIncidenciasBatch (evita doble query).
        // proveedores_incluir: IDs de proveedor raw (cuando se llama directamente).
        $ids_proveedor_filter = (array)($params['ids_proveedor_filter'] ?? []);
        if (empty($ids_proveedor_filter)) {
            $proveedores_incluir = (array)($params['proveedores_incluir'] ?? []);
            if (!empty($proveedores_incluir)) {
                $ids_str_prov = implode(',', array_map('intval', $proveedores_incluir));
                $rows_prov = $this->_queryIdsArticulosByProveedores($ids_str_prov);
                if (isset($rows_prov['error'])) return $rows_prov;
                $ids_proveedor_filter = array_column($rows_prov, 'idArticulo');
                if (empty($ids_proveedor_filter)) return []; // ningún artículo para esos proveedores
            }
        }
        if (!empty($ids_proveedor_filter)) {
            if (!empty($ids_filter)) {
                $ids_filter = array_values(array_intersect($ids_filter, $ids_proveedor_filter));
                if (empty($ids_filter)) return []; // intersección vacía
            } else {
                $ids_filter = $ids_proveedor_filter;
            }
        }

        // casos_incluir [] = todos los casos activos excepto C4
        $validos_todos = ['caso1', 'caso2', 'caso3a', 'caso3b', 'caso5', 'caso6a', 'caso6b', 'caso7a', 'caso7b', 'caso9'];
        $casos_raw     = (array)($params['casos_incluir'] ?? []);
        $casos_set     = array_flip(
            empty($casos_raw)
                ? $validos_todos
                : array_intersect($casos_raw, [...$validos_todos, 'caso4'])
        );

        $incidencias = [];

        // ── Precalcular stock_base compartido para C1, C2, C7 y C9 ──────────
        $sb_shared = [];
        if (!empty($ids_filter) && (isset($casos_set['caso1']) || isset($casos_set['caso2']) || isset($casos_set['caso7a']) || isset($casos_set['caso7b']) || isset($casos_set['caso9']))) {
            $sb_shared = $this->getStockBase($ids_filter, $fi_stock, $ff_stock);
            if (isset($sb_shared['error'])) return $sb_shared;
        }

        // ── C1 ───────────────────────────────────────────────────────────────
        if (isset($casos_set['caso1'])) {
            $c1 = $this->getIncidenciasC1(
                $fi_mov,
                $ff_mov,
                $fi_stock,
                $ff_stock,
                $familias_incluir,
                $familias_excluir,
                $ids_filter,
                $sb_shared,
                $c1_umbral_fraccionado,
                $c1_umbral_magnitud,
                $c1_umbral_por_venta,
                $c1_timing_ventana_dias
            );
            if (isset($c1['error'])) return $c1;
            $incidencias = array_merge($incidencias, $c1);
        }

        // IDs con C1a activo — usados para marcar stock_no_fiable en C5/C6
        $ids_con_c1a = [];
        foreach ($incidencias as $inc) {
            if ($inc['tipo'] === 'Inventario en negativo') {
                $ids_con_c1a[$inc['idArticulo']] = true;
            }
        }

        // ── C2 ───────────────────────────────────────────────────────────────
        if (isset($casos_set['caso2'])) {
            $c2 = $this->getIncidenciasC2(
                $fi_mov,
                $ff_mov,
                $fi_stock,
                $ff_stock,
                $umbral_sobrestock,
                $params['c2_umbral_duplicado']         ?? 1.0,
                $params['c2_umbral_sobrestock_severo'] ?? 6.0,
                $params['c2_umbral_cobertura_dias']    ?? 21,
                $familias_incluir,
                $familias_excluir,
                $ids_filter,
                $sb_shared
            );
            if (isset($c2['error'])) return $c2;
            $incidencias = array_merge($incidencias, $c2);
        }

        // ── C3 (3a y/o 3b — una sola query, filtrar resultado por sub-caso) ──
        if (isset($casos_set['caso3a']) || isset($casos_set['caso3b'])) {
            $c3 = $this->getIncidenciasC3(
                $fi_mov,
                $ff_mov,
                $fi_stock,
                $umbral_caducidad,
                $umbral_sin_rotacion,
                $familias_incluir,
                $familias_excluir,
                $ids_filter,
                $c3b_dias_post,
                $c3a_multiplicador
            );
            if (isset($c3['error'])) return $c3;
            // Filtrar sub-casos si no se piden ambos
            if (!isset($casos_set['caso3a']) || !isset($casos_set['caso3b'])) {
                $tipos_c3 = [];
                if (isset($casos_set['caso3a'])) $tipos_c3[] = 'Caída de rotación';
                if (isset($casos_set['caso3b'])) $tipos_c3[] = 'Entrada sin rotación previa';
                $c3 = array_values(array_filter($c3, fn($inc) => in_array($inc['tipo'], $tipos_c3, true)));
            }
            $incidencias = array_merge($incidencias, $c3);
        }

        $incluir_albcli = (bool)($params['incluir_albcli_ventas'] ?? false);

        // ── C5 ───────────────────────────────────────────────────────────────
        if (isset($casos_set['caso5'])) {
            $c5 = $this->getIncidenciasCaso5(
                $fi_mov,
                $ff_mov,
                $fi_stats,
                $umbral_sobrestock,
                $familias_incluir,
                $familias_excluir,
                $ids_filter,
                $min_ventas_c5,
                $modelo_rotura_c5,
                $umbral_confianza_c5,
                $c5_incluir_stock_negativo,
                $binomial_sigma_mult,
                $incluir_albcli,
                $c3b_dias_post,
                $ff_stats
            );
            if (isset($c5['error'])) return $c5;
            $incidencias = array_merge($incidencias, $c5);
        }

        $c6_proveedores  = (array)($params['proveedores_incluir']  ?? []);
        $c6_lead_time    = (int)  ($params['c6_lead_time_defecto'] ?? 14);
        $c6_nivel_serv   = (float)($params['c6_nivel_servicio']    ?? 0.95);

        // ── C6a — ROP estacional (ventana ligada al periodo ±1) ──────────────
        if (isset($casos_set['caso6a'])) {
            $c6a = $this->getIncidenciasCaso6(
                $fi_mov,
                $ff_mov,
                $fi_stock,
                $familias_incluir,
                $familias_excluir,
                $ids_filter,
                $c6_proveedores,
                $c6_lead_time,
                $c6_nivel_serv,
                $min_ventas_c5,
                $modelo_rotura_c5,
                $umbral_confianza_c5,
                $binomial_sigma_mult,
                $incluir_albcli,
                $fi_stats,
                $ff_stats,
                'Agotamiento Estimado',
                '',
                $umbral_rop_mult,
                $umbral_stock_neg
            );
            if (isset($c6a['error'])) return $c6a;
            $incidencias = array_merge($incidencias, $c6a);
        }

        // ── C6b — ROP operacional (ventana histórica fija anclada en hoy) ───────
        // A diferencia de C6a (ligada al periodo analizado), C6b siempre mide
        // la demanda de los últimos N días desde hoy. Así:
        //   · El stock reflejado es el real actual (no rebobinado a ff_mov).
        //   · La muestra estadística corresponde a la demanda reciente real.
        //   · Productos de temporada no generan falsas alarmas fuera de su época.
        // Resultado: responde directamente a "¿debo pedir hoy?".
        //
        // IMPORTANTE: C6b resuelve sus propios IDs de artículo sin filtro de estado,
        // independientemente de cómo se haya llamado (batch, lote único o directo).
        // Un artículo con estado='Tarifa' o cualquier otro estado distinto de 'Activo'
        // en articulosProveedores puede seguir en stock y vendiendo; C6b debe evaluarlo.
        if (isset($casos_set['caso6b'])) {
            $c6b_dias    = (int)($params['c6b_dias_historico'] ?? 90);
            $ff_hoy      = date('Y-m-d');
            $anio_hoy    = (int)substr($ff_hoy, 0, 4);
            $fi_stats_6b = max(
                date('Y-m-d', strtotime("$ff_hoy -{$c6b_dias} days")),
                "{$anio_hoy}-01-01"   // límite BD anualizada; resoluble con mod_api
            );
            // min_ventas propio de C6b: anclado a su ventana temporal, no al periodo analizado.
            // Evita que un min_ventas_c5 alto (ej. 30 en anual) elimine productos válidos
            // que en 90 días solo registran 10-15 días con venta.
            $min_ventas_c6b = (int)($params['min_ventas_c6b'] ?? max(5, min(20, (int)round($c6b_dias * 0.15))));
            // Resolver IDs para C6b directamente, sin filtro de estado en articulosProveedores.
            // No se reutiliza ids_proveedor_filter (que sí filtra estado='Activo') ni se delega
            // en getIncidenciasBatch, ya que esa resolución ocurre antes de llegar aquí.
            // IDs activos del proveedor (estado='Activo'): para marcar proveedor_es_principal en cada incidencia.
            $ids_activos_c6b = array_flip($ids_proveedor_filter ?: []);
            if (!empty($proveedores_incluir)) {
                $ids_str_prov_c6b = implode(',', array_map('intval', $proveedores_incluir));
                $rows_c6b = $this->_queryIdsArticulosByProveedoresTodos($ids_str_prov_c6b);
                $ids_c6b  = isset($rows_c6b['error']) ? $ids_proveedor_filter : array_column($rows_c6b, 'idArticulo');
                // ids_proveedor_filter ya contiene solo estado='Activo' (resuelto al inicio de getIncidencias)
                $ids_activos_c6b = array_flip($ids_proveedor_filter ?: []);
            } else {
                $ids_c6b = $ids_proveedor_filter;  // sin filtro de proveedor: comportamiento normal
            }
            $c6b = $this->getIncidenciasCaso6(
                $ff_hoy,
                $ff_hoy,
                $fi_stock,
                $familias_incluir,
                $familias_excluir,
                $ids_c6b,
                $c6_proveedores,
                $c6_lead_time,
                $c6_nivel_serv,
                $min_ventas_c6b,
                $modelo_rotura_c5,
                $umbral_confianza_c5,
                $binomial_sigma_mult,
                $incluir_albcli,
                $fi_stats_6b,
                $ff_hoy,
                'Punto de Pedido',
                $ff_hoy,
                $umbral_rop_mult,
                $umbral_stock_neg
            );
            if (isset($c6b['error'])) return $c6b;
            // Marcar si el proveedor seleccionado es el proveedor principal (estado='Activo') de cada artículo
            foreach ($c6b as &$inc_c6b) {
                $inc_c6b['proveedor_es_principal'] = isset($ids_activos_c6b[$inc_c6b['idArticulo']]);
            }
            unset($inc_c6b);
            $incidencias = array_merge($incidencias, $c6b);
        }

        // ── C7a / C7b — Offset sistemático de inventario ────────────────────
        // Se ejecutan con una sola pasada SQL si ambos están activos.
        $c7_subcasos = [];
        if (isset($casos_set['caso7a'])) $c7_subcasos[] = 'C7a';
        if (isset($casos_set['caso7b'])) $c7_subcasos[] = 'C7b';
        if (!empty($c7_subcasos)) {
            $c7 = $this->getIncidenciasC7(
                $fi_mov,
                $ff_mov,
                $fi_stock,
                $ff_stock,
                $familias_incluir,
                $familias_excluir,
                $ids_filter,
                $sb_shared,
                $c7_subcasos,
                $c7b_min_recepciones,
                $c7b_umbral_cv,
                $c7b_umbral_cv_peso,
                $c7b_umbral_iqr_peso,
                $c7b_umbral_ruido_peso,
                $c7b_umbral_severidad_unidad,
                $c7b_umbral_severidad_peso,
                $c7b_cascada_exhaustiva,
                (bool)($params['skip_c7_cde'] ?? false),
                $c7a_umbral_delta_unidad,
                $c7a_umbral_delta_peso,
                $c7a_umbral_pvalue,
                $c7a_umbral_pvalue_alta,
                $c7a_umbral_alta_delta_unidad,
                $c7a_umbral_alta_delta_peso,
                $c7a_umbral_alta_slope_unidad,
                $c7a_umbral_alta_slope_peso,
                $c7a_umbral_snr,
                $c7a_cascada_exhaustiva
            );
            if (isset($c7['error'])) return $c7;
            $incidencias = array_merge($incidencias, $c7);
        }

        // ── C9 — Merma por backstaging LIFO inverso ──────────────────────────
        if (isset($casos_set['caso9'])) {
            $c9 = $this->getIncidenciasC9(
                $fi_mov,
                $ff_mov,
                $fi_stock,
                $familias_incluir,
                $familias_excluir,
                $ids_filter,
                $sb_shared,
                (int)  ($params['c9_profundidad_k']     ?? 4),
                (float)($params['c9_beta']              ?? 0.15),
                (float)($params['c9_lambda']            ?? 1.5),
                (float)($params['c9_epsilon']           ?? 1.0),
                (int)  ($params['c9_min_recepciones']   ?? 3),
                (float)($params['c9_umbral_merma_unidad'] ?? 2.0),
                (float)($params['c9_umbral_merma_peso']   ?? 1.0),
                (int)  ($params['c9_dias_post']         ?? 60)
            );
            if (isset($c9['error'])) return $c9;
            $incidencias = array_merge($incidencias, $c9);
        }

        // ── C4 — solo si solicitado explícitamente (no paginable por actividad) ─
        if (isset($casos_set['caso4'])) {
            $articulos_sin_mov = $this->getArticulosSinMovimiento(
                $fi_mov,
                $ff_mov,
                $familias_incluir,
                $familias_excluir,
                $c5_incluir_stock_negativo
            );
            if (isset($articulos_sin_mov['error'])) return $articulos_sin_mov;
            foreach ($this->_formatearCaso4($articulos_sin_mov) as $inc) {
                $incidencias[] = $inc;
            }
        }

        // ── Marcar stock_no_fiable en C5/C6 cuando el artículo tiene C1a activo ──
        if (!empty($ids_con_c1a)) {
            foreach ($incidencias as &$inc) {
                if (
                    isset($ids_con_c1a[$inc['idArticulo']]) &&
                    in_array($inc['tipo'], ['Rotura de Stock', 'Agotamiento Estimado', 'Punto de Pedido'], true)
                ) {
                    $inc['stock_no_fiable'] = true;
                }
            }
            unset($inc);
        }

        // ── Ordenar: CRITICA → ALTA → MEDIA (C2→C5→C3a) → BAJA (C3b sin-rot→C3b nunca→C4) ─
        $orden_sev = ['CRITICA' => 0, 'ALTA' => 1, 'MEDIA' => 2, 'BAJA' => 3];

        $orden_tipo_media = [
            'Entrada con stock alto'             => 0,  // C2
            'Venta Cero (Posible Rotura Física)' => 1,  // C5
            'Agotamiento Estimado'               => 2,  // C6a MEDIA (BN, stock suficiente)
            'Punto de Pedido'                    => 2,  // C6b MEDIA (mismo nivel que C6a)
            'Riesgo de caducidad teórica'        => 3,  // C3a
            'Entrada no registrada'              => 4,  // C7b
            'Merma acumulada'                    => 4,  // C7a
            'Merma backstaging'                  => 4,  // C9
        ];

        // C3b con ultima_salida (Sin rotación) antes que sin ultima_salida (Nunca salidas)
        $subtipo_baja = static function (array $inc): int {
            if ($inc['tipo'] === 'Entrada sin rotación previa') {
                return (isset($inc['ultima_salida']) && $inc['ultima_salida'] !== null) ? 0 : 1;
            }
            return 2; // 'Stock Inactivo en Periodo' (C4)
        };

        usort($incidencias, static function ($a, $b) use ($orden_sev, $orden_tipo_media, $subtipo_baja) {
            $cmp = ($orden_sev[$a['severidad']] ?? 99) <=> ($orden_sev[$b['severidad']] ?? 99);
            if ($cmp !== 0) return $cmp;

            // Dentro del mismo nivel de severidad, ordenación específica por tipo
            if ($a['severidad'] === 'MEDIA') {
                $cmp = ($orden_tipo_media[$a['tipo']] ?? 99) <=> ($orden_tipo_media[$b['tipo']] ?? 99);
                if ($cmp !== 0) return $cmp;
            }
            if ($a['severidad'] === 'BAJA') {
                $cmp = $subtipo_baja($a) <=> $subtipo_baja($b);
                if ($cmp !== 0) return $cmp;
            }

            return 0;
        });

        // Añadir orden_clave lexicográfica para que el frontend reordene entre lotes
        // sin duplicar la lógica de negocio de la ordenación.
        $orden_tipo_media_clave = [
            'Entrada con stock alto'             => '0',
            'Venta Cero (Posible Rotura Física)' => '1',
            'Agotamiento Estimado'               => '2',  // C6a
            'Punto de Pedido'                    => '2',  // C6b
            'Riesgo de caducidad teórica'        => '3',
            'Entrada no registrada'              => '4',  // C7b
            'Merma acumulada'                    => '4',  // C7a
        ];

        // C5: pre-calcular la fecha de inicio de rotura más reciente por artículo
        // para ordenar los grupos de producto de más reciente a más antiguo.
        $c5_tipo = 'Venta Cero (Posible Rotura Física)';
        $max_fecha_c5 = [];
        foreach ($incidencias as $inc) {
            if ($inc['tipo'] === $c5_tipo) {
                $id = $inc['idArticulo'];
                $f  = $inc['fecha_inicio_rotura'] ?? '0000-00-00';
                if (!isset($max_fecha_c5[$id]) || $f > $max_fecha_c5[$id]) {
                    $max_fecha_c5[$id] = $f;
                }
            }
        }

        foreach ($incidencias as &$inc) {
            $sev_idx = $orden_sev[$inc['severidad']] ?? 9;
            if ($inc['tipo'] === $c5_tipo) {
                // Agrupar por artículo ordenando grupos por fecha de última rotura (asc: más antigua primero).
                // Dentro de cada artículo: orden cronológico de fecha_inicio_rotura (asc).
                $max_f = $max_fecha_c5[$inc['idArticulo']] ?? '0000-00-00';
                $fi_r  = $inc['fecha_inicio_rotura'] ?? '0000-00-00';
                $inc['orden_clave'] = $sev_idx . '1' . $max_f . sprintf('%08d', $inc['idArticulo']) . $fi_r;
            } elseif ($inc['tipo'] === 'Agotamiento Estimado' || $inc['tipo'] === 'Punto de Pedido') {
                // C6a/C6b CRITICA/ALTA: ordenar por dias_autonomia ascendente (más urgente primero).
                // C6a/C6b MEDIA: se ordena por tipo dentro del bloque MEDIA (ya cubierto por orden_tipo_media).
                // C6b (Punto de Pedido) se desplaza un sub-nivel respecto a C6a dentro de la misma severidad.
                $dias_pad = str_pad((int)($inc['dias_autonomia'] * 10), 8, '0', STR_PAD_LEFT);
                $c6b_shift = ($inc['tipo'] === 'Punto de Pedido') ? '1' : '0';
                $sub       = ($inc['severidad'] === 'MEDIA') ? '2' : '0';
                $inc['orden_clave'] = $sev_idx . $sub . $c6b_shift . $dias_pad . sprintf('%08d', $inc['idArticulo']);
            } elseif ($inc['severidad'] === 'MEDIA') {
                $sub = $orden_tipo_media_clave[$inc['tipo']] ?? '9';
                $inc['orden_clave'] = $sev_idx . $sub . sprintf('%08d', $inc['idArticulo']);
            } elseif ($inc['severidad'] === 'BAJA') {
                if ($inc['tipo'] === 'Entrada sin rotación previa') {
                    $sub = (isset($inc['ultima_salida']) && $inc['ultima_salida'] !== null) ? '0' : '1';
                } else {
                    $sub = '2'; // Stock Inactivo en Periodo (C4)
                }
                $inc['orden_clave'] = $sev_idx . $sub . sprintf('%08d', $inc['idArticulo']);
            } elseif ($inc['tipo'] === 'Inventario en negativo') {
                // C1a: stock_actual desc (más negativo primero), desempate por días en negativo desc
                $inv_stock = str_pad(max(0, 9999999999 - (int)(abs((float)($inc['stock_actual'] ?? 0)) * 100)), 10, '0', STR_PAD_LEFT);
                $inv_dias  = str_pad(max(0, 9999 - (int)($inc['dias_en_negativo'] ?? 0)), 4, '0', STR_PAD_LEFT);
                $inc['orden_clave'] = $sev_idx . '0' . $inv_stock . $inv_dias;
            } elseif ($inc['tipo'] === 'Desajuste Puntual de Stock') {
                // C1b: abs(min_balance) desc — el mínimo más profundo primero
                $inv_min = str_pad(max(0, 9999999999 - (int)(abs((float)($inc['min_balance'] ?? 0)) * 100)), 10, '0', STR_PAD_LEFT);
                $inc['orden_clave'] = $sev_idx . '0' . $inv_min;
            } elseif (in_array($inc['c7_subcaso'] ?? '', ['C7a', 'C7a_posible'], true)) {
                // C7a: coste_estimado_merma desc → delta_acumulado desc → tendencia desc
                $coste_inv  = str_pad(max(0, 9999999 - (int)(abs((float)($inc['coste_estimado_merma'] ?? 0)) * 100)), 7, '0', STR_PAD_LEFT);
                $delta_inv  = str_pad(max(0, 99999 - (int)(abs((float)($inc['delta_acumulado'] ?? 0)) * 10)), 5, '0', STR_PAD_LEFT);
                $slope_inv  = str_pad(max(0, 9999 - (int)(abs((float)($inc['tendencia'] ?? 0)) * 10)), 4, '0', STR_PAD_LEFT);
                $coste_null = ($inc['coste_estimado_merma'] ?? null) === null ? '1' : '0';
                $inc['orden_clave'] = $sev_idx . '0' . $coste_null . $coste_inv . $delta_inv . $slope_inv;
            } elseif (in_array($inc['c7_subcaso'] ?? '', ['C7b', 'C7b_posible', 'C7b_ruido_peso'], true)) {
                // C7b: coste_estimado desc → n_recepciones desc → déficit abs desc
                $coste_inv = str_pad(max(0, 9999999 - (int)(abs((float)($inc['coste_estimado'] ?? 0)) * 100)), 7, '0', STR_PAD_LEFT);
                $rec_inv   = str_pad(max(0, 9999 - (int)($inc['n_recepciones'] ?? 0)), 4, '0', STR_PAD_LEFT);
                $def_inv   = str_pad(max(0, 99999 - (int)(abs((float)($inc['offset_estimado'] ?? 0)) * 10)), 5, '0', STR_PAD_LEFT);
                // Nulls de coste al final
                $coste_null = ($inc['coste_estimado'] ?? null) === null ? '1' : '0';
                $inc['orden_clave'] = $sev_idx . '0' . $coste_null . $coste_inv . $rec_inv . $def_inv;
            } elseif ($inc['tipo'] === 'Merma backstaging') {
                // C9: pct_merma desc → merma_total_kg desc
                $pct_inv   = str_pad(max(0, 99999 - (int)(abs((float)($inc['pct_merma']     ?? 0)) * 100)), 5, '0', STR_PAD_LEFT);
                $merma_inv = str_pad(max(0, 9999999 - (int)(abs((float)($inc['merma_total_kg'] ?? 0)) * 100)), 7, '0', STR_PAD_LEFT);
                $inc['orden_clave'] = $sev_idx . '0' . $pct_inv . $merma_inv;
            } else {
                $inc['orden_clave'] = $sev_idx . '0' . sprintf('%08d', $inc['idArticulo']);
            }
        }
        unset($inc);

        return $this->_anadirNombres($incidencias);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Detecta todos los gaps anómalos (> media+3σ) para un artículo.
     *
     * Recibe la fecha_map (date => true), calcula estadísticas de intervalos
     * y devuelve 0 o más incidencias — una por brecha anómala detectada.
     * 'fecha_fin_rotura' null = rotura en curso; string = rotura recuperada.
     *
     * IMPORTANTE — comprobación de stock:
     *   · Roturas RECUPERADAS (gap entre dos ventas consecutivas): no necesitan
     *     stock > 0 al cierre. Si hubo una segunda venta, había stock durante
     *     el hueco. Se reportan siempre → visibles en vistas anuales aunque el
     *     artículo se haya agotado posteriormente.
     *   · Rotura EN CURSO (desde última venta hasta ff_mov): solo se reporta
     *     si stock_actual > 0 al cierre del periodo — confirma que el artículo
     *     sigue en el sistema y la brecha no es por discontinuación.
     *
     * @param int   $id           idArticulo
     * @param array $fechas_map   [date => true]
     * @param float $stock_actual Stock al cierre del periodo (solo para gap en curso)
     * @param int   $ff_ts        Timestamp de fecha_fin_movimientos
     *
     * @return array  Filas de incidencia (sin campo 'nombre')
     */
    /**
     * Cuantil de la distribución normal estándar (función probit).
     * Aproximación racional de Acklam — error máximo < 1.15e-9.
     */
    private function _normalQuantile(float $p): float
    {
        $p = max(1e-15, min(1 - 1e-15, $p));
        $a = [
            -3.969683028665376e+01,
            2.209460984245205e+02,
            -2.759285104469687e+02,
            1.383577518672690e+02,
            -3.066479806614716e+01,
            2.506628277459239e+00
        ];
        $b = [
            -5.447609879822406e+01,
            1.615858368580409e+02,
            -1.556989798598866e+02,
            6.680131188771972e+01,
            -1.328068155288572e+01
        ];
        $c = [
            -7.784894002430293e-03,
            -3.223964580411365e-01,
            -2.400758277161838e+00,
            -2.549732539343734e+00,
            4.374664141464968e+00,
            2.938163982698783e+00
        ];
        $d = [
            7.784695709041462e-03,
            3.224671290700398e-01,
            2.445134137142996e+00,
            3.754408661907416e+00
        ];

        $p_low = 0.02425;
        if ($p < $p_low) {
            $q = sqrt(-2.0 * log($p));
            return (((((($c[0] * $q + $c[1]) * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5]) /
                ((((($d[0] * $q + $d[1]) * $q + $d[2]) * $q + $d[3]) * $q + 1.0)));
        } elseif ($p <= 1.0 - $p_low) {
            $q = $p - 0.5;
            $r = $q * $q;
            return (((((($a[0] * $r + $a[1]) * $r + $a[2]) * $r + $a[3]) * $r + $a[4]) * $r + $a[5]) * $q /
                (((((($b[0] * $r + $b[1]) * $r + $b[2]) * $r + $b[3]) * $r + $b[4]) * $r + 1.0)));
        } else {
            $q = sqrt(-2.0 * log(1.0 - $p));
            return - (((((($c[0] * $q + $c[1]) * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5]) /
                ((((($d[0] * $q + $d[1]) * $q + $d[2]) * $q + $d[3]) * $q + 1.0)));
        }
    }

    /**
     * Cuantil de la distribución Gamma(α, θ) mediante la aproximación Wilson-Hilferty.
     *
     * X ~ Gamma(α, scale=θ)  →  E[X] = α·θ,  Var[X] = α·θ²
     * Cuantil_p ≈ α·θ · max(0, 1 − 1/(9α) + z_p/√(9α))³
     *
     * Error < 1 % para α > 0.5; exacto en el límite α → ∞ (normal).
     */
    private function _gammaQuantileWH(float $alpha, float $theta, float $p): float
    {
        if ($alpha <= 0.0 || $theta <= 0.0) return 0.0;
        $z    = $this->_normalQuantile($p);
        $term = 1.0 - 1.0 / (9.0 * $alpha) + $z / sqrt(9.0 * $alpha);
        if ($term <= 0.0) return 0.0;
        return $alpha * $theta * ($term ** 3);
    }

    /**
     * Modelo Gamma para detección de roturas (C5).
     *
     * Ajusta una distribución Gamma a los gaps observados entre días de venta
     * y usa el cuantil (1 − umbral_prob) como umbral de anomalía:
     *
     *   α = μ² / σ²   (shape)
     *   θ = σ² / μ    (scale)
     *   umbral = Gamma_quantile(1 − p, α, θ)  [Wilson-Hilferty]
     *
     * La distribución Gamma es más adecuada que la exponencial (Poisson) cuando los
     * inter-arrivals presentan coeficiente de variación ≠ 1 (artículos con ventas
     * agrupadas o muy regulares).
     */
    private function _calcularRoturasC5Gamma(
        int    $id,
        array  $fechas_map,
        float  $stock_actual,
        int    $ff_ts,
        int    $min_ventas,
        float  $umbral_prob,
        bool   $incluir_stock_negativo = false,
        string $label = 'Gamma'   // 'Gamma' | 'GammaReg' | dejado 'Normal' internamente si degenerado
    ): array {
        $fechas = array_keys($fechas_map);
        sort($fechas);
        $n = count($fechas);
        if ($n < $min_ventas) return [];

        $ts = array_map('strtotime', $fechas);

        $gaps = [];
        for ($i = 1; $i < $n; $i++) {
            $gap = (int)(($ts[$i] - $ts[$i - 1]) / 86400);
            if ($gap > 0) $gaps[] = (float)$gap;
        }
        if (count($gaps) < 2) return [];

        $n_gaps  = count($gaps);
        $avg_gap = array_sum($gaps) / $n_gaps;
        $var_gap = array_sum(array_map(fn($g) => ($g - $avg_gap) ** 2, $gaps)) / ($n_gaps - 1);
        $sd_gap  = sqrt(max(0.0, $var_gap));

        // Ajuste gamma; si la varianza es prácticamente nula, usar media+3σ como fallback
        if ($var_gap > 1e-9 && $avg_gap > 1e-9) {
            $alpha_gap    = ($avg_gap ** 2) / $var_gap;
            $theta_gap    = $var_gap / $avg_gap;
            $umbral_gap   = $this->_gammaQuantileWH($alpha_gap, $theta_gap, 1.0 - $umbral_prob);
            $modelo_usado = $label;          // 'Gamma' | 'GammaReg' según el contexto de llamada
        } else {
            $umbral_gap   = $avg_gap + 3.0 * $sd_gap;
            $modelo_usado = 'Normal';        // varianza ≈ 0: umbral = media + 3σ (Normal)
        }

        $umbral_ceil  = (int)ceil($umbral_gap);
        $avg_gap_real = $avg_gap;

        $campos = [
            'idArticulo'            => $id,
            'tipo'                  => 'Venta Cero (Posible Rotura Física)',
            'stock_actual'          => $stock_actual,
            'avg_dias_entre_ventas' => round($avg_gap, 1),
            'sd_dias'               => round($sd_gap, 1),
            'umbral_dias'           => round($umbral_gap, 1),
            'posible_causa'         => 'Hueco en lineal o merma no registrada',
            'modelo_usado'          => $modelo_usado,
        ];

        $incidencias = [];
        for ($i = 1; $i < $n; $i++) {
            $gap = (int)(($ts[$i] - $ts[$i - 1]) / 86400);
            if ($gap > $umbral_gap) {
                $inicio            = date('Y-m-d', $ts[$i - 1] + $umbral_ceil * 86400);
                $fin               = $fechas[$i];
                $dias_confirmados  = $gap - $umbral_ceil;
                $rotura_confirmada = $inicio < $fin;
                $cr                = $rotura_confirmada && ($dias_confirmados > $umbral_ceil);
                $dias_real         = (int)((strtotime($fin) - strtotime($inicio)) / 86400);
                $rk                = $rotura_confirmada && !$cr && $dias_real >= (int)ceil($avg_gap_real);
                $sev               = $cr ? 'ALTA' : 'MEDIA';
                $incidencias[] = $campos + [
                    'severidad'           => $sev,
                    'ultima_venta'        => $fechas[$i - 1],
                    'fecha_inicio_rotura' => $inicio,
                    'fecha_fin_rotura'    => $fin,
                    'dias_rotura'         => $gap,
                    'rotura_confirmada'   => $rotura_confirmada,
                    'cr'                  => $cr,
                    'rk'                  => $rk,
                    'ko'                  => false,
                ];
            }
        }

        // Rotura en curso: desde última venta hasta ff_mov.
        // El filtro de inclusión (c5_incluir_stock_negativo) ya se aplicó upstream;
        // aquí se detecta para todos los artículos incluidos sin restricción adicional.
        $dias_final = (int)(($ff_ts - $ts[$n - 1]) / 86400);
        if ($dias_final > $umbral_gap) {
            $inicio_ko    = date('Y-m-d', $ts[$n - 1] + $umbral_ceil * 86400);
            $hoy_ts       = time();
            $fin_virtual  = date('Y-m-d', min($ff_ts, $hoy_ts));
            $dias_conf_ko = $dias_final - $umbral_ceil;
            $rot_conf_ko  = $inicio_ko < $fin_virtual;
            $cr_ko        = $rot_conf_ko && ($dias_conf_ko > $umbral_ceil);
            $dias_real_ko = $rot_conf_ko
                ? (int)((min($ff_ts, $hoy_ts) - strtotime($inicio_ko)) / 86400)
                : 0;
            $rk_ko  = $rot_conf_ko && !$cr_ko && $dias_real_ko >= (int)ceil($avg_gap_real);
            // KO: stock agotado (≤ 0) durante rotura en curso → inventario en descubierto
            $ko_val = $stock_actual <= 0;
            $sev_ko = $ko_val ? 'CRITICA' : ($cr_ko ? 'ALTA' : 'MEDIA');
            $incidencias[] = $campos + [
                'severidad'           => $sev_ko,
                'ultima_venta'        => $fechas[$n - 1],
                'fecha_inicio_rotura' => $inicio_ko,
                'fecha_fin_rotura'    => null,
                'dias_rotura'         => $dias_final,
                'rotura_confirmada'   => false,
                'cr'                  => $cr_ko,
                'rk'                  => $rk_ko,
                'ko'                  => $ko_val,
            ];
        }
        return $incidencias;
    }

    /**
     * Modo automático C5: selecciona el modelo estadístico más adecuado para cada artículo.
     *
     * Árbol de decisión:
     *
     * RAMA 1 — Alta rotación (>80 % de días con venta):
     *   · Gaps muy regulares (CV < umbral) → Gamma (cuantil de gap; cubre Normal/Gamma B3 del árbol).
     *   · Gaps más variables              → Poisson (gaps ~ Exponencial, B4 del árbol).
     *
     * RAMA 2 — Rotación media/baja (≤80 %):
     *   · Demanda en rachas (s²_chunk > μ_chunk) → Binomial Negativa (via _calcularRoturasC5Poisson, B6).
     *   · Muy esporádico (rotación < 15 %)        → Poisson (eventos raros, B8).
     *   · General o tipo-peso                     → Gamma (ajuste a gaps, cuantil, B9).
     *
     * Heurística "tipo peso": fracción decimal significativa en los gaps (cantidades
     * no enteras en ventas) → umbral Gamma más permisivo (0.65 → 0.45) para alta rotación
     * y fuerza Gamma en la rama general (B9) aunque la rotación sea moderada.
     */
    private function _autoDispatchC5(
        int   $id,
        array $fechas_map,
        float $stock_actual,
        int   $ff_ts,
        int   $min_ventas,
        float $umbral_prob,
        int   $periodo_dias,
        bool  $incluir_stock_negativo,
        int   $ff_stats_ts = 0   // extremo de la ventana estadística (0 = usar $ff_ts)
    ): array {
        $fechas = array_keys($fechas_map);
        sort($fechas);
        $ts  = array_map('strtotime', $fechas);
        $n   = count($ts);
        if ($n < $min_ventas) return [];

        $rotation = $n / max(1, $periodo_dias);   // fracción de días con venta

        // ── Gaps entre ventas consecutivas ──────────────────────────────────
        $gaps = [];
        for ($i = 1; $i < $n; $i++) {
            $g = (int)(($ts[$i] - $ts[$i - 1]) / 86400);
            if ($g > 0) $gaps[] = (float)$g;
        }

        $cv_g = 1.0;
        if (count($gaps) >= 2) {
            $n_g   = count($gaps);
            $mu_g  = array_sum($gaps) / $n_g;
            $var_g = array_sum(array_map(fn($g) => ($g - $mu_g) ** 2, $gaps)) / ($n_g - 1);
            $cv_g  = $mu_g > 1e-9 ? sqrt(max(0.0, $var_g)) / $mu_g : 1.0;
        }

        // ── Sobredispersión de chunks (ventas en rachas) ─────────────────────
        // fi_period_ts: usar ff_stats_ts (extremo estadístico) para que las chunks
        // cubran exactamente [fi_stats, ff_stats] y no queden chunks vacíos artificiales.
        $ff_for_chunks = $ff_stats_ts ?: $ff_ts;
        $fi_period_ts = $ff_for_chunks - ($periodo_dias - 1) * 86400;
        if ($periodo_dias < 40)       $chunk_days = 1;
        elseif ($periodo_dias <= 130) $chunk_days = 5;
        else                          $chunk_days = 10;
        $n_chunks     = (int)ceil($periodo_dias / $chunk_days);
        $chunk_counts = array_fill(0, $n_chunks, 0);
        foreach ($ts as $t) {
            $offset = (int)(($t - $fi_period_ts) / 86400);
            $chunk  = min($n_chunks - 1, max(0, (int)floor($offset / $chunk_days)));
            $chunk_counts[$chunk]++;
        }
        $mu_chunk = $n / $n_chunks;
        $s2_chunk = 0.0;
        if ($n_chunks > 1) {
            foreach ($chunk_counts as $c) $s2_chunk += ($c - $mu_chunk) ** 2;
            $s2_chunk /= ($n_chunks - 1);
        }
        $overdispersed = $n_chunks >= 4 && $mu_chunk > 1e-9 && $s2_chunk > $mu_chunk + 1e-9;

        // ── Heurística "tipo peso" ────────────────────────────────────────────
        // Fracción decimal significativa en gaps → cantidades no enteras (peso/líquido)
        $is_peso = false;
        if (count($gaps) >= 2) {
            $n_g      = count($gaps);
            $frac_sum = 0.0;
            $frac_sq  = 0.0;
            foreach ($gaps as $g) {
                $f = $g - floor($g);
                $frac_sum += $f;
                $frac_sq  += $f * $f;
            }
            $frac_mean = $frac_sum / $n_g;
            $frac_var  = $n_g > 1 ? ($frac_sq - $n_g * $frac_mean ** 2) / ($n_g - 1) : 0.0;
            $is_peso   = $frac_mean > 0.05 && $frac_var > 0.001;
        }

        // ── Árbol de decisión ────────────────────────────────────────────────
        // RAMA 1: Alta rotación (>80 % días con venta)
        if ($rotation > 0.80) {
            // Tipo peso o gaps muy regulares → Gamma (cuantil directo)
            $cv_th = $is_peso ? 0.65 : 0.45;
            if ($cv_g < $cv_th) {
                return $this->_calcularRoturasC5Gamma(
                    $id,
                    $fechas_map,
                    $stock_actual,
                    $ff_ts,
                    $min_ventas,
                    $umbral_prob,
                    $incluir_stock_negativo,
                    'GammaReg'
                );
            }
            // Alta rotación, gaps más variables → Poisson (inter-arrivals ~ Exponencial)
            return $this->_calcularRoturasC5Poisson(
                $id,
                $fechas_map,
                $stock_actual,
                $ff_ts,
                $min_ventas,
                $umbral_prob,
                $periodo_dias,
                $incluir_stock_negativo,
                $ff_stats_ts
            );
        }

        // RAMA 2: Rotación media/baja (≤80 %)
        // 2a. Demanda en rachas → Binomial Negativa (activa internamente en _calcularRoturasC5Poisson)
        if ($overdispersed) {
            return $this->_calcularRoturasC5Poisson(
                $id,
                $fechas_map,
                $stock_actual,
                $ff_ts,
                $min_ventas,
                $umbral_prob,
                $periodo_dias,
                $incluir_stock_negativo,
                $ff_stats_ts
            );
        }

        // 2b. Muy esporádico (no tipo peso) → Poisson (proceso de eventos raros)
        if (!$is_peso && $rotation < 0.15) {
            return $this->_calcularRoturasC5Poisson(
                $id,
                $fechas_map,
                $stock_actual,
                $ff_ts,
                $min_ventas,
                $umbral_prob,
                $periodo_dias,
                $incluir_stock_negativo,
                $ff_stats_ts
            );
        }

        // 2c. General / tipo peso → Gamma (ajuste a distribución de gaps, cuantil de rotura)
        return $this->_calcularRoturasC5Gamma(
            $id,
            $fechas_map,
            $stock_actual,
            $ff_ts,
            $min_ventas,
            $umbral_prob,
            $incluir_stock_negativo
        );
    }

    /**
     * Modelo Poisson / Binomial Negativa adaptativo para detección de roturas (C5).
     *
     * El modelo se selecciona automáticamente en función de la dispersión real
     * de las ventas del artículo dentro del periodo:
     *
     *   1. Se divide el periodo en sub-ventanas semanales (o diarias si ≤ 14 días)
     *      y se cuenta el número de días con venta por sub-ventana.
     *   2. Se calculan μ (media) y s² (varianza muestral) de esos conteos.
     *   3. Si s² > μ  → sobredispersión → Binomial Negativa (ventas en "rachas")
     *      Si s² ≤ μ  → dispersión normal → Poisson puro
     *
     * Poisson (umbral de gap):
     *   gap > −ln(p) / λ_día
     *
     * Binomial Negativa:
     *   r = μ² / (s² − μ)          [parámetro de forma]
     *   P(0 en d días) = (r/(r+μ))^(r·d/chunk_days) < p
     *   → gap > ln(p) · chunk_days / (r · ln(r/(r+μ)))
     *
     * La fórmula BN converge a Poisson cuando r → ∞ (s² → μ).
     */
    private function _calcularRoturasC5Poisson(
        int   $id,
        array $fechas_map,
        float $stock_actual,
        int   $ff_ts,
        int   $min_ventas,
        float $umbral_prob,
        int   $periodo_dias,
        bool  $incluir_stock_negativo = false,
        int   $ff_stats_ts = 0   // extremo de la ventana estadística (0 = usar $ff_ts)
    ): array {
        $fechas = array_keys($fechas_map);
        sort($fechas);
        $n = count($fechas);
        if ($n < $min_ventas) return [];

        $ts         = array_map('strtotime', $fechas);
        $lambda_dia = $n / max(1, $periodo_dias);
        if ($lambda_dia <= 0) return [];

        // ── Detectar sobredispersión mediante sub-ventanas ───────────────────
        // chunk_days: granularidad de las sub-ventanas (diaria ≤14 días, semanal el resto)
        $fi_period_ts = ($ff_stats_ts ?: $ff_ts) - ($periodo_dias - 1) * 86400;
        // Ajuste de granularidad de sub-ventanas según duración del periodo:
        // - <= 31 días: ventanas diarias (semanal/quincenal/mensual)
        // - 32..120 días: ventanas de 5 días (trimestral, cuatrimestral)
        // - > 120 días: ventanas de 10 días (semestral, anual)
        if ($periodo_dias < 40) {
            $chunk_days = 1;
        } elseif ($periodo_dias <= 130) {
            $chunk_days = 5;
        } else {
            $chunk_days = 10;
        }
        $n_chunks     = (int)ceil($periodo_dias / $chunk_days);

        $chunk_counts = array_fill(0, $n_chunks, 0);
        foreach ($ts as $t) {
            $offset = (int)(($t - $fi_period_ts) / 86400);
            $chunk  = min($n_chunks - 1, max(0, (int)floor($offset / $chunk_days)));
            $chunk_counts[$chunk]++;
        }

        $mu_chunk = $n / $n_chunks;
        $s2_chunk = 0.0;
        if ($n_chunks > 1) {
            foreach ($chunk_counts as $c) {
                $s2_chunk += ($c - $mu_chunk) ** 2;
            }
            $s2_chunk /= ($n_chunks - 1);   // varianza muestral insesgada
        }

        // ── Selección del modelo y cálculo del umbral de gap ────────────────
        $modelo_usado = 'Poisson';
        if ($n_chunks >= 4 && $s2_chunk > $mu_chunk + 1e-9 && $mu_chunk > 1e-9) {
            // Sobredispersión confirmada → Binomial Negativa
            // r = μ² / (s² − μ);  cuanto más pequeño r, más volátil el artículo
            $r        = max(0.001, ($mu_chunk ** 2) / ($s2_chunk - $mu_chunk));
            $ln_ratio = log($r / ($r + $mu_chunk));   // siempre < 0
            // d > ln(p)·chunk_days / (r·ln(r/(r+μ)))  — ambos ln negativos → d > 0
            $umbral_gap   = log($umbral_prob) * $chunk_days / ($r * $ln_ratio);
            $modelo_usado = 'BN';
        } else {
            // Poisson puro: gap > −ln(p) / λ_día
            $umbral_gap = -log($umbral_prob) / $lambda_dia;
        }

        $umbral_ceil = (int)ceil($umbral_gap);

        $campos = [
            'idArticulo'            => $id,
            'tipo'                  => 'Venta Cero (Posible Rotura Física)',
            'stock_actual'          => $stock_actual,
            'avg_dias_entre_ventas' => round($periodo_dias / $n, 1),
            'sd_dias'               => null,
            'umbral_dias'           => round($umbral_gap, 1),
            'posible_causa'         => 'Hueco en lineal o merma no registrada',
            'modelo_usado'          => $modelo_usado,
        ];

        $avg_gap_real = $n > 1 ? $periodo_dias / $n : 1.0;
        $incidencias  = [];
        for ($i = 1; $i < $n; $i++) {
            $gap = (int)(($ts[$i] - $ts[$i - 1]) / 86400);
            if ($gap > $umbral_gap) {
                $inicio            = date('Y-m-d', $ts[$i - 1] + $umbral_ceil * 86400);
                $fin               = $fechas[$i];
                $dias_confirmados  = $gap - $umbral_ceil;
                $rotura_confirmada = $inicio < $fin;
                $cr                = $rotura_confirmada && ($dias_confirmados > $umbral_ceil);
                $dias_real         = (int)((strtotime($fin) - strtotime($inicio)) / 86400);
                $rk                = $rotura_confirmada && !$cr && $dias_real >= (int)ceil($avg_gap_real);
                // KO no aplica en roturas recuperadas (no tenemos el stock en el momento del gap)
                // Severidad: CR→ALTA; resto→MEDIA (RK y detecciones básicas se muestran igual)
                $sev = $cr ? 'ALTA' : 'MEDIA';
                $incidencias[] = $campos + [
                    'severidad'           => $sev,
                    'ultima_venta'        => $fechas[$i - 1],
                    'fecha_inicio_rotura' => $inicio,
                    'fecha_fin_rotura'    => $fin,
                    'dias_rotura'         => $gap,
                    'rotura_confirmada'   => $rotura_confirmada,
                    'cr'                  => $cr,
                    'rk'                  => $rk,
                    'ko'                  => false,
                ];
            }
        }
        $dias_final = (int)(($ff_ts - $ts[$n - 1]) / 86400);
        if ($dias_final > $umbral_gap) {
            $inicio_ko    = date('Y-m-d', $ts[$n - 1] + $umbral_ceil * 86400);
            // Capamos a hoy: si el periodo analizado termina en el futuro (trimestral, etc.),
            // la rotura estimada puede no haber comenzado aún → CR/RK no aplican
            $hoy_ts       = time();
            $fin_virtual  = date('Y-m-d', min($ff_ts, $hoy_ts));
            $dias_conf_ko = $dias_final - $umbral_ceil;
            $rot_conf_ko  = $inicio_ko < $fin_virtual;
            $cr_ko        = $rot_conf_ko && ($dias_conf_ko > $umbral_ceil);
            $dias_real_ko = $rot_conf_ko
                ? (int)((min($ff_ts, $hoy_ts) - strtotime($inicio_ko)) / 86400)
                : 0;
            $rk_ko  = $rot_conf_ko && !$cr_ko && $dias_real_ko >= (int)ceil($avg_gap_real);
            // KO: stock agotado (≤ 0) durante rotura en curso → inventario en descubierto
            $ko_val = $stock_actual <= 0;
            $sev_ko = $ko_val ? 'CRITICA' : ($cr_ko ? 'ALTA' : 'MEDIA');
            $incidencias[] = $campos + [
                'severidad'           => $sev_ko,
                'ultima_venta'        => $fechas[$n - 1],
                'fecha_inicio_rotura' => $inicio_ko,
                'fecha_fin_rotura'    => null,
                'dias_rotura'         => $dias_final,
                'rotura_confirmada'   => false,
                'cr'                  => $cr_ko,
                'rk'                  => $rk_ko,
                'ko'                  => $ko_val,
            ];
        }
        return $incidencias;
    }

    /**
     * Modelo clásico media + n·σ para detección de roturas (C5).
     * $sigma_mult determina el número de desviaciones típicas del umbral (defecto: 3).
     */
    private function _calcularRoturasC5(int $id, array $fechas_map, float $stock_actual, int $ff_ts, int $min_ventas, bool $incluir_stock_negativo = false, float $sigma_mult = 3.0): array
    {
        $fechas = array_keys($fechas_map);
        sort($fechas);
        $n = count($fechas);
        if ($n < $min_ventas) return [];
        $ts = array_map('strtotime', $fechas);
        $gaps = [];
        for ($i = 1; $i < $n; $i++) {
            $gap = (int)(($ts[$i] - $ts[$i - 1]) / 86400);
            if ($gap > 0) $gaps[] = $gap;
        }
        if (count($gaps) < 2) return [];
        $n_gaps  = count($gaps);
        $avg_gap = array_sum($gaps) / $n_gaps;
        $var     = array_sum(array_map(fn($g) => ($g - $avg_gap) ** 2, $gaps)) / ($n_gaps - 1);
        $sd      = sqrt($var);
        $umbral  = $avg_gap + $sigma_mult * $sd;
        $umbral_ceil = (int)ceil($umbral);
        $campos = [
            'idArticulo'            => $id,
            'tipo'                  => 'Venta Cero (Posible Rotura Física)',
            'stock_actual'          => $stock_actual,
            'avg_dias_entre_ventas' => round($avg_gap, 1),
            'sd_dias'               => round($sd, 1),
            'umbral_dias'           => round($umbral, 1),
            'posible_causa'         => 'Hueco en lineal o merma no registrada',
        ];
        $incidencias = [];
        for ($i = 1; $i < $n; $i++) {
            $gap = (int)(($ts[$i] - $ts[$i - 1]) / 86400);
            if ($gap > $umbral) {
                $inicio            = date('Y-m-d', $ts[$i - 1] + $umbral_ceil * 86400);
                $fin               = $fechas[$i];
                $dias_confirmados  = $gap - $umbral_ceil;
                $rotura_confirmada = $inicio < $fin;
                $cr                = $rotura_confirmada && ($dias_confirmados > $umbral_ceil);
                $dias_real         = (int)((strtotime($fin) - strtotime($inicio)) / 86400);
                $rk                = $rotura_confirmada && !$cr && $dias_real >= (int)ceil($avg_gap);
                // KO no aplica en roturas recuperadas (no disponemos del stock durante el gap)
                // Severidad: CR→ALTA; resto→MEDIA (RK y detecciones básicas se muestran igual)
                $sev = $cr ? 'ALTA' : 'MEDIA';
                $incidencias[] = $campos + [
                    'severidad'           => $sev,
                    'ultima_venta'        => $fechas[$i - 1],
                    'fecha_inicio_rotura' => $inicio,
                    'fecha_fin_rotura'    => $fin,
                    'dias_rotura'         => $gap,
                    'rotura_confirmada'   => $rotura_confirmada,
                    'cr'                  => $cr,
                    'rk'                  => $rk,
                    'ko'                  => false,
                ];
            }
        }
        // Rotura en curso: desde última venta hasta ff_mov.
        // CR/RK capados a hoy para evitar marcar roturas que aún no han empezado.
        // El filtro de inclusión (c5_incluir_stock_negativo) ya se aplicó upstream.
        $dias_final   = (int)(($ff_ts - $ts[$n - 1]) / 86400);
        if ($dias_final > $umbral) {
            $inicio_ko    = date('Y-m-d', $ts[$n - 1] + $umbral_ceil * 86400);
            $hoy_ts       = time();
            $fin_virtual  = date('Y-m-d', min($ff_ts, $hoy_ts));
            $dias_conf_ko = $dias_final - $umbral_ceil;
            $rot_conf_ko  = $inicio_ko < $fin_virtual;
            $cr_ko        = $rot_conf_ko && ($dias_conf_ko > $umbral_ceil);
            $dias_real_ko = $rot_conf_ko
                ? (int)((min($ff_ts, $hoy_ts) - strtotime($inicio_ko)) / 86400)
                : 0;
            $rk_ko  = $rot_conf_ko && !$cr_ko && $dias_real_ko >= (int)ceil($avg_gap);
            // KO: stock agotado (≤ 0) durante rotura en curso → inventario en descubierto
            $ko_val = $stock_actual <= 0;
            $sev_ko = $ko_val ? 'CRITICA' : ($cr_ko ? 'ALTA' : 'MEDIA');
            $incidencias[] = $campos + [
                'severidad'           => $sev_ko,
                'ultima_venta'        => $fechas[$n - 1],
                'fecha_inicio_rotura' => $inicio_ko,
                'fecha_fin_rotura'    => null,
                'dias_rotura'         => $dias_final,
                'rotura_confirmada'   => false,
                'cr'                  => $cr_ko,
                'rk'                  => $rk_ko,
                'ko'                  => $ko_val,
            ];
        }
        return $incidencias;
    }

    /**
     * T5.5 — Artículos físicos con stock positivo pero sin movimiento en todo el año (Caso 4).
     *
     * Proceso en tres pasos para garantizar que "sin movimiento" significa cero filas
     * en cualquiera de los tres tipos de movimiento durante [fi_año, ff_mov]:
     *
     *   1. Obtener todos los idArticulo físicos que coincidan con el filtro de familias.
     *   2. Obtener todos los idArticulo con CUALQUIER movimiento (entrada, ticket o albcli)
     *      en el rango [fi_año, ff_mov]. Diff en PHP → sin_movimiento_año.
     *   3. Calcular el stock en el momento del análisis (ff_mov) rebobinando desde
     *      articulosStocks.stockOn: stock_en_ff = stockOn − net_movimientos_posteriores.
     *      Esto da el stock real en el periodo analizado independientemente de lo que
     *      haya pasado después. Solo los con stock_en_ff > 0 se reportan.
     *
     * @param string $fi_año           Primer día del ejercicio ('YYYY-01-01')
     * @param string $ff_mov           Último día del periodo analizado ('YYYY-MM-DD')
     * @param array  $familias_incluir
     * @param array  $familias_excluir
     *
     * @return array  Indexado por idArticulo con saldo_acumulado, ultima_compra, ultima_venta
     *                — o array con clave 'error' si falla alguna consulta.
     */
    public function getArticulosSinMovimiento(
        string $fi_año,
        string $ff_mov,
        array $familias_incluir = [],
        array $familias_excluir = [],
        bool $c5_incluir_stock_negativo = false
    ): array {
        $fi    = $this->db->real_escape_string($fi_año);
        $ff    = $this->db->real_escape_string($ff_mov);

        // Filtro de familias sobre la tabla articulos (alias 'a')
        $where_familia = '';
        if (!empty($familias_incluir)) {
            $ids_fam = $this->expandirFamilias($familias_incluir);
            if ($ids_fam) $where_familia .= " AND a.idArticulo IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids_fam))";
        }
        if (!empty($familias_excluir)) {
            $ids_fam = $this->expandirFamilias($familias_excluir);
            if ($ids_fam) $where_familia .= " AND a.idArticulo NOT IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids_fam))";
        }

        // Paso 1: todos los artículos físicos (con filtro de familia)
        $rows_fisicos = $this->_queryArticulosFisicos($where_familia);
        if (isset($rows_fisicos['error'])) return $rows_fisicos;

        $todos_ids = array_map(fn($r) => (int)$r['idArticulo'], $rows_fisicos);
        if (empty($todos_ids)) return [];

        // Paso 2: artículos con cualquier movimiento (los 3 tipos) en [fi_año, ff_mov]
        $rows_con_mov = $this->_queryIdsConMovimientoC4($fi, $ff);
        if (isset($rows_con_mov['error'])) return $rows_con_mov;

        $con_movimiento = [];
        foreach ($rows_con_mov as $r) $con_movimiento[(int)$r['idArticulo']] = true;

        // Diff en PHP: artículos físicos (con familia) sin ningún movimiento en el año
        $sin_movimiento = array_values(array_filter($todos_ids, fn($id) => !isset($con_movimiento[$id])));
        if (empty($sin_movimiento)) return [];

        // Paso 3: stock en el momento del análisis (fecha ff_mov) via rebobinado
        $ids_str = implode(',', array_map('intval', $sin_movimiento));
        $rows_stock = $this->_queryStockRebobinado($ids_str, $ff, $c5_incluir_stock_negativo);
        if (isset($rows_stock['error'])) return $rows_stock;

        $resultado = [];
        foreach ($rows_stock as $row) {
            $resultado[(int)$row['idArticulo']] = [
                'saldo_acumulado' => (float)$row['stock_en_periodo'],
                'ultima_compra'   => null,
                'ultima_venta'    => null,
            ];
        }
        return $resultado;
    }

    /**
     * Ruta optimizada para Caso 5 (Venta Cero / Posible Rotura Física).
     *
     * Evita cargar getMovimientosPeriodo (que para periodos largos puede agotar
     * la memoria). En su lugar:
     *   1. Query DISTINCT (idArticulo, fecha) solo de ventas en [fi_mov, ff_mov].
     *   2. Rebobinado de stockOn para obtener stock en ff_mov por artículo.
     *   3. Cálculo de media+3σ sobre los gaps entre días de venta.
     *
     * Funciona para cualquier longitud de periodo (semana, mes, anual…).
     * La ventana de análisis estadístico arranca en $fi_stock (inicio del rango anual)
     * para garantizar suficiente histórico en vistas cortas (semanal, quincenal, mensual).
     */
    private function getIncidenciasCaso5(
        string $fi_mov,
        string $ff_mov,
        string $fi_stats,            // inicio de la ventana estadística (±1 periodo para semana/quincena/mes)
        float  $umbral_sobrestock,   // no usado en C5, recibido por firma uniforme
        array  $familias_incluir,
        array  $familias_excluir,
        array  $ids_filter              = [],
        int    $min_ventas              = 3,
        string $modelo                    = 'automatico', // 'automatico' | 'binomial' | 'poisson_bn' | 'gamma'
        float  $umbral_prob               = 0.05,        // auto/poisson_bn/gamma: P(gap > umbral) < p
        bool   $c5_incluir_stock_negativo = false,       // si false, excluye stock_actual < 0
        float  $binomial_sigma_mult       = 3.0,         // multiplicador σ para modo binomial
        bool   $incluir_albcli            = false,        // incluir albaranes de cliente como ventas
        int    $dias_post               = 14,            // días post-periodo para validar stockouts en curso
        string $ff_stats                = ''             // fin de la ventana estadística; vacío = ff_mov
    ): array {
        // fi_stats/ff_stats: ventana de datos para estadísticas (gaps, distribución).
        // Para semana/quincena/mes se amplía ±1 periodo preservando estacionalidad.
        // ff_mov sigue siendo el límite de detección y rebobinado de stock.
        $ff_stats = $ff_stats ?: $ff_mov;
        $fi   = $this->db->real_escape_string($fi_stats);
        $ff   = $this->db->real_escape_string($ff_mov);
        $ff_stats_esc = $this->db->real_escape_string($ff_stats);

        $where_fam = $this->_familiaWhere($familias_incluir, $familias_excluir);
        $where_ids = $this->_idsWhere($ids_filter);

        // Paso 1: fechas de venta únicas por artículo físico (ventana estadística fi_stats→ff_stats)
        $rows_ventas = $this->_queryVentasFechasC5($fi, $ff_stats_esc, $where_fam, $where_ids, $incluir_albcli);
        if (isset($rows_ventas['error'])) return $rows_ventas;
        if (empty($rows_ventas)) return [];

        $ventas_fechas = [];
        foreach ($rows_ventas as $r) {
            $ventas_fechas[(int)$r['idArticulo']][$r['fecha']] = true;
        }

        // Paso 2: stock en ff_mov via rebobinado desde articulosStocks.stockOn
        $ids_str = implode(',', array_keys($ventas_fechas));
        $rows_stock = $this->_queryStockRebobinado($ids_str, $ff, false);
        if (isset($rows_stock['error'])) return $rows_stock;

        $stock_actual = [];
        foreach ($rows_stock as $r) {
            $stock_actual[(int)$r['idArticulo']] = (float)$r['stock_en_periodo'];
        }

        // Si no se incluye stock negativo, excluir artículos con stock_actual < 0
        // (ya aparecen en C1 como stock negativo; KO requiere stock > 0 de todas formas)
        if (!$c5_incluir_stock_negativo) {
            $ventas_fechas = array_filter(
                $ventas_fechas,
                function ($_, $id) use ($stock_actual) {
                    return ($stock_actual[$id] ?? 0.0) >= 0;
                },
                ARRAY_FILTER_USE_BOTH
            );
        }

        // Paso 3: lógica Caso 5 — detecta TODAS las roturas (binomial o Poisson)
        // periodo_dias usa la ventana estadística completa (fi_stats→ff_stats) para que
        // la media y σ sean representativos incluso en vistas cortas (semana/quincena/mes).
        $ff_ts        = strtotime($ff_mov);    // límite de detección (no cambia)
        $periodo_dias = max(1, (int)round((strtotime($ff_stats) - strtotime($fi_stats)) / 86400) + 1);
        $incidencias  = [];

        foreach ($ventas_fechas as $id => $fechas_map) {
            $sa = $stock_actual[$id] ?? 0.0;
            $ff_stats_ts = strtotime($ff_stats);
            $roturas = match ($modelo) {
                'automatico' => $this->_autoDispatchC5(
                    $id,
                    $fechas_map,
                    $sa,
                    $ff_ts,
                    $min_ventas,
                    $umbral_prob,
                    $periodo_dias,
                    $c5_incluir_stock_negativo,
                    $ff_stats_ts
                ),
                'gamma' => $this->_calcularRoturasC5Gamma(
                    $id,
                    $fechas_map,
                    $sa,
                    $ff_ts,
                    $min_ventas,
                    $umbral_prob,
                    $c5_incluir_stock_negativo
                ),
                'poisson_bn', 'poisson' => $this->_calcularRoturasC5Poisson(
                    $id,
                    $fechas_map,
                    $sa,
                    $ff_ts,
                    $min_ventas,
                    $umbral_prob,
                    $periodo_dias,
                    $c5_incluir_stock_negativo,
                    $ff_stats_ts
                ),
                default => $this->_calcularRoturasC5(  // 'binomial'
                    $id,
                    $fechas_map,
                    $sa,
                    $ff_ts,
                    $min_ventas,
                    $c5_incluir_stock_negativo,
                    $binomial_sigma_mult
                ),
            };
            foreach ($roturas as $r) $incidencias[] = $r;
        }

        // Paso 4: anti-falso-positivo — validar stockouts en curso con ventas post-periodo.
        // Si el artículo vendió en los $dias_post días siguientes a ff_mov, la rotura
        // se considera recuperada (igual que el mecanismo C3b "primera_venta_post").
        if ($dias_post > 0 && !empty($incidencias)) {
            $ids_en_curso = [];
            foreach ($incidencias as $inc) {
                if ($inc['fecha_fin_rotura'] === null) {
                    $ids_en_curso[$inc['idArticulo']] = true;
                }
            }
            if (!empty($ids_en_curso)) {
                $ids_str_curso = implode(',', array_keys($ids_en_curso));
                $fi_post_esc   = $this->db->real_escape_string(
                    date('Y-m-d', strtotime($ff_mov . ' +1 day'))
                );
                $ff_post_esc   = $this->db->real_escape_string(
                    date('Y-m-d', strtotime($ff_mov . " +{$dias_post} days"))
                );
                $ventas_post = $this->_queryVentasPostPeriodoC5(
                    $ids_str_curso,
                    $fi_post_esc,
                    $ff_post_esc,
                    $incluir_albcli
                );
                foreach ($incidencias as &$inc) {
                    if ($inc['fecha_fin_rotura'] !== null) continue;
                    $fecha_post = $ventas_post[$inc['idArticulo']] ?? null;
                    if ($fecha_post === null) continue;
                    // Rotura recuperada: actualizar campos
                    $dias_total              = (int)((strtotime($fecha_post) - strtotime($inc['ultima_venta'])) / 86400);
                    $inc['fecha_fin_rotura'] = $fecha_post;
                    $inc['dias_rotura']      = $dias_total;
                    $inc['ko']               = false;
                    // Recalcular CR y RK con el gap real (ahora extendido al post-periodo)
                    $umbral_rec  = (float)($inc['umbral_dias'] ?? 0);
                    $dias_conf   = $dias_total - $umbral_rec;
                    $avg_gap_rec = (float)($inc['avg_dias_entre_ventas'] ?? PHP_INT_MAX);
                    $inc['cr']        = $umbral_rec > 0 && $dias_conf > $umbral_rec;
                    $inc['rk']        = !$inc['cr'] && $umbral_rec > 0 && $dias_total >= $avg_gap_rec;
                    $inc['severidad'] = $inc['cr'] ? 'ALTA' : 'MEDIA';
                }
                unset($inc);
            }
        }

        // Filtrar: solo reportar roturas cuyo inicio cae dentro del periodo seleccionado.
        // El histórico desde fi_stock se usó solo para el cálculo estadístico interno.
        $incidencias = array_values(array_filter(
            $incidencias,
            fn($inc) => isset($inc['fecha_inicio_rotura']) && $inc['fecha_inicio_rotura'] >= $fi_mov
        ));

        // Añadir nombres
        if (!empty($incidencias)) {
            $ids_inc = implode(',', array_unique(array_column($incidencias, 'idArticulo')));
            $smt = $this->db->query(
                "SELECT idArticulo, articulo_name FROM articulos WHERE idArticulo IN ($ids_inc)"
            );
            $nombres = [];
            if ($smt) {
                while ($r = $smt->fetch_assoc()) {
                    $nombres[(int)$r['idArticulo']] = $r['articulo_name'];
                }
            }
            foreach ($incidencias as &$inc) {
                $inc['nombre'] = $nombres[$inc['idArticulo']] ?? '';
            }
            unset($inc);
        }

        return $incidencias;
    }

    /**
     * Caso 6 — Agotamiento Estimado / Punto de Pedido (ROP).
     *
     * Modelos disponibles (parámetro $modelo):
     *   · 'binomial'  — Compound Binomial: p = fracción de días con venta; en días de venta la
     *                   cantidad sigue su propia distribución. σ_d = √(p·(1−p)·q̄² + p·s²_q).
     *   · 'gamma'     — Gamma sobre la demanda diaria (incluyendo días cero). Ajusta Gamma(α,θ)
     *                   y calcula el ROP directamente como cuantil de Gamma(L·α, θ) mediante la
     *                   aproximación Wilson-Hilferty (error < 1 % para α > 0.5).
     *   · 'poisson'   — (auto-detección) Poisson o Binomial Negativa según sobredispersión.
     *
     * Proyecta hacia el futuro:
     *   · Estima la demanda diaria (d = unidades_vendidas / periodo_dias) y su dispersión
     *     a partir de las cantidades vendidas por día en [fi_mov, ff_mov].
     *   · Lead time (L): si hay proveedores seleccionados se calcula como el intervalo
     *     medio entre albaranes consecutivos de esos proveedores en el rango anual
     *     [fi_stock, ff_mov]. Si no hay datos suficientes (< 2 albaranes por proveedor),
     *     o si no hay ningún proveedor seleccionado, se usa lead_time_defecto.
     *     Con varios proveedores se promedia el LT individual de cada uno.
     *   · Calcula SS = z × σ_d × √L  y  ROP = d × L + SS.
     *   · Clasifica por severidad según la relación entre stock_actual y ROP:
     *       CRITICA — dias_autonomia < L  (agotamiento antes del próximo pedido)
     *       ALTA    — stock < ROP         (por debajo del punto de pedido)
     *       MEDIA   — stock ≥ ROP y modelo BN (stock suficiente pero alta variabilidad)
     *   · Solo devuelve artículos accionables (CRITICA, ALTA, o MEDIA+BN).
     *
     * @param string $fi_mov              Inicio del periodo de análisis de ventas
     * @param string $ff_mov              Fin del periodo de análisis (= fecha actual del informe)
     * @param string $fi_stock            Inicio del rango anual (para calcular LT desde albaranes)
     * @param array  $familias_incluir
     * @param array  $familias_excluir
     * @param array  $ids_filter
     * @param array  $proveedores_incluir IDs de proveedor seleccionados ([] = sin filtro)
     * @param int    $lead_time_defecto   Días usados si no hay proveedor o datos insuficientes
     * @param float  $nivel_servicio      0.90 | 0.95 | 0.99  (z = 1.28 | 1.65 | 2.33)
     * @param int    $min_ventas          Mínimo de días únicos con venta (base estadística del modelo)
     * @param string $modelo              'binomial' | 'gamma' | 'poisson' (ver descripción arriba)
     * @param float  $umbral_prob         Umbral de probabilidad (solo usado en modelo 'gamma' C5)
     *
     * @return array  Filas de incidencia o ['error' => ...]
     */
    private function getIncidenciasCaso6(
        string $fi_mov,
        string $ff_mov,
        string $fi_stock,              // inicio del rango anual — para lead time desde albaranes
        array  $familias_incluir,
        array  $familias_excluir,
        array  $ids_filter,
        array  $proveedores_incluir,
        int    $lead_time_defecto,
        float  $nivel_servicio,
        int    $min_ventas,
        string $modelo,
        float  $umbral_prob,           // reservado — no usado en C6 (compatibilidad de firma)
        float  $binomial_sigma_mult = 3.0,
        bool   $incluir_albcli      = false,  // incluir albaranes de cliente como ventas
        string $fi_stats            = '',     // inicio ventana estadística (±1 periodo para semana/quincena/mes)
        string $ff_stats            = '',     // fin ventana estadística; vacío = ff_mov
        string $tipo_override       = '',     // tipo de incidencia ('Agotamiento Estimado' | 'Punto de Pedido')
        string $stock_anchor        = '',     // fecha de rebobinado de stock; vacío = ff_mov (C6a). C6b pasa date('Y-m-d')
        float  $umbral_rop_mult     = 10.0,  // umbral configurable: stock > N×ROP → reconstruir
        float  $umbral_stock_neg    = 2.0    // umbral configurable: stock < -N → reconstruir
    ): array {
        $fi_stats = $fi_stats ?: $fi_mov;
        $ff_stats = $ff_stats ?: $ff_mov;
        $fi = $this->db->real_escape_string($fi_stats);
        $ff = $this->db->real_escape_string($stock_anchor ?: $ff_mov);  // rebobinar a hoy (C6b) o ff_mov (C6a)
        $ff_stats_esc = $this->db->real_escape_string($ff_stats);

        $where_fam = $this->_familiaWhere($familias_incluir, $familias_excluir);
        $where_ids = $this->_idsWhere($ids_filter);

        // Umbrales de reconstrucción (recibidos como parámetros desde getIncidencias)
        $umbral_rop_mult  = max(3.0, $umbral_rop_mult);
        $umbral_stock_neg = max(0.0, $umbral_stock_neg);

        // Paso 1 — Cantidades vendidas por día y artículo en el periodo
        // (no fechas únicas: necesitamos unidades para d = unidades/día)
        // Ventana estadística fi_stats→ff_stats para mayor muestra en vistas cortas
        // Si se filtra por proveedores (C6b) incluir todos los tipos de artículo
        $incluir_todos_tipos = !empty($proveedores_incluir);
        $rows_ventas = $this->_queryVentasCantidadesC6($fi, $ff_stats_esc, $where_fam, $where_ids, $incluir_albcli, $incluir_todos_tipos);
        if (isset($rows_ventas['error'])) return $rows_ventas;
        if (empty($rows_ventas)) return [];

        // $ventas_cant[idArticulo][fecha] = nunidades_dia  (float)
        $ventas_cant = [];
        foreach ($rows_ventas as $r) {
            $ventas_cant[(int)$r['idArticulo']][$r['fecha']] = (float)$r['nunidades_dia'];
        }

        // Paso 2 — Stock actual en ff_mov por rebobinado (igual que C5)
        $ids_str    = implode(',', array_keys($ventas_cant));
        $rows_stock = $this->_queryStockRebobinado($ids_str, $ff, false);
        if (isset($rows_stock['error'])) return $rows_stock;

        $stock_actual = [];
        foreach ($rows_stock as $r) {
            $stock_actual[(int)$r['idArticulo']] = (float)$r['stock_en_periodo'];
        }

        // ── Stock reconstruido para artículos muy negativos ───────────────────
        // Cuando el stockOn acumula errores (pesajes parciales, ajustes no registrados…)
        // el stock rebobinado puede ser irreal. Si cae por debajo del umbral se usa:
        //   stock_reconstituido = última_entrada − ventas_desde_esa_entrada
        // Esto da un valor orientativo más seguro para calcular el pedido.
        $ids_muy_negativos = array_keys(
            array_filter($stock_actual, fn($s) => $s < -$umbral_stock_neg)
        );
        $stock_reconstituido_set = [];
        if (!empty($ids_muy_negativos)) {
            $ids_neg_str = implode(',', $ids_muy_negativos);
            $rows_rec    = $this->_queryStockReconstituido($ids_neg_str, $ff);
            if (!isset($rows_rec['error'])) {
                foreach ($rows_rec as $id_rec => $stock_rec) {
                    $stock_actual[$id_rec]             = $stock_rec;
                    $stock_reconstituido_set[$id_rec]  = 'negativo';
                }
            }
        }

        // Lead time: desde albaranes del proveedor si hay selección; defecto en caso contrario
        $lead_fuente = 'defecto';
        $L           = max(1, $lead_time_defecto);
        if (!empty($proveedores_incluir)) {
            $lt_prov = $this->_calcularLeadTimeProveedores($fi_stock, $ff_mov, $proveedores_incluir);
            if ($lt_prov > 0) {
                $L           = $lt_prov;
                $lead_fuente = 'proveedor';
            }
        }

        // Z-score según nivel de servicio
        $z_map = ['0.90' => 1.28, '0.95' => 1.65, '0.99' => 2.33];
        $z     = $z_map[number_format($nivel_servicio, 2)] ?? 1.65;

        // Periodos no finalizados (trimestral, semestral, anual…): usar solo los días
        // transcurridos hasta hoy. Sin este ajuste, d_diaria = n_ventas / días_totales
        // se diluye artificialmente (ej. anual en marzo → n/365 en lugar de n/74),
        // produciendo ROP y cantidad de pedido muy por debajo de la realidad.
        // Se usa la ventana estadística (fi_stats→ff_stats) para mayor muestra en periodos cortos.
        $ff_stats_ts  = strtotime($ff_stats);
        $ff_efectivo  = min($ff_stats_ts, time());
        $periodo_dias = max(1, (int)round(($ff_efectivo - strtotime($fi_stats)) / 86400) + 1);

        // Granularidad de sub-ventanas (misma lógica que _calcularRoturasC5Poisson)
        if ($periodo_dias < 40)       $chunk_days = 1;
        elseif ($periodo_dias <= 130) $chunk_days = 5;
        else                          $chunk_days = 10;

        // fi_period_ts: usar ff_stats_ts (no ff_efectivo) para alinear las chunks
        // con la ventana estadística real y evitar chunks vacíos al inicio.
        $fi_period_ts = $ff_stats_ts - ($periodo_dias - 1) * 86400;

        $incidencias          = [];
        $pending_reconstruction = []; // artículos con stock > 10×ROP: parámetros estadísticos ya calculados

        // En C6b (filtrado por proveedor) no aplicar el umbral mínimo de días con venta
        $min_ventas_effective = !empty($proveedores_incluir) ? 0 : $min_ventas;

        foreach ($ventas_cant as $id => $fechas_map) {
            // $n  = días únicos con venta (base estadística del modelo)
            // $total_units = unidades totales vendidas (base de la demanda media)
            $n           = count($fechas_map);
            $total_units = array_sum($fechas_map);
            if ($n < $min_ventas_effective) continue;

            $d = $total_units / $periodo_dias;   // demanda diaria en unidades
            if ($d <= 0) continue;

            // ── Parámetros estadísticos sobre cantidades por sub-ventana ──────────
            // chunk_counts acumula unidades (no días de presencia) para que
            // la varianza refleje la dispersión real de la demanda.
            $n_chunks     = (int)ceil($periodo_dias / $chunk_days);
            $chunk_counts = array_fill(0, $n_chunks, 0.0);
            foreach ($fechas_map as $fecha => $qty) {
                $t      = strtotime($fecha);
                $offset = (int)(($t - $fi_period_ts) / 86400);
                $chunk  = min($n_chunks - 1, max(0, (int)floor($offset / $chunk_days)));
                $chunk_counts[$chunk] += $qty;
            }

            $mu_chunk = $total_units / $n_chunks;   // unidades medias por chunk
            $s2_chunk = 0.0;
            if ($n_chunks > 1) {
                foreach ($chunk_counts as $c) $s2_chunk += ($c - $mu_chunk) ** 2;
                $s2_chunk /= ($n_chunks - 1);
            }

            // ── σ_d / ROP según modelo seleccionado ──────────────────────────
            $sigma_d      = null;
            $SS           = null;
            $ROP          = null;
            $modelo_usado = $modelo;

            if ($modelo === 'gamma') {
                // Gamma: ajuste sobre demanda diaria incluyendo días sin venta (qty = 0).
                // Var(demanda_día) = (Σqty² − n_dias·d²) / (n_dias − 1)
                $sum_sq = array_sum(array_map(fn($q) => $q ** 2, $fechas_map));
                $var_d  = $periodo_dias > 1
                    ? max(0.0, ($sum_sq - $periodo_dias * $d * $d) / ($periodo_dias - 1))
                    : 0.0;

                if ($var_d > 1e-9 && $d > 1e-9) {
                    $alpha_dia    = ($d ** 2) / $var_d;
                    $theta_dia    = $var_d / $d;
                    // Demanda durante L días ~ Gamma(L·α, θ) → cuantil directo
                    $ROP          = $this->_gammaQuantileWH((float)$L * $alpha_dia, $theta_dia, $nivel_servicio);
                    $SS           = max(0.0, $ROP - $d * $L);
                    $modelo_usado = 'Gamma';
                } else {
                    // Demanda prácticamente constante → Poisson como fallback
                    $sigma_d      = sqrt($d);
                    $modelo_usado = 'Poisson';
                }
            } elseif ($modelo === 'binomial') {
                // Compound Binomial: cada día ~ Bernoulli(p); en días con venta
                // la cantidad sigue su propia distribución.
                // Var(demanda_día) = p·(1−p)·q̄² + p·s²_q  [ley de varianza total]
                $p_sale = $n / $periodo_dias;
                $q_mean = $n > 0 ? $total_units / $n : 0.0;
                $s2_q   = 0.0;
                if ($n > 1) {
                    foreach ($fechas_map as $qty) $s2_q += ($qty - $q_mean) ** 2;
                    $s2_q /= ($n - 1);
                }
                $sigma_d      = sqrt(max(0.0, $p_sale * (1.0 - $p_sale) * $q_mean ** 2 + $p_sale * $s2_q));
                $modelo_usado = 'Binomial';
            } elseif ($modelo === 'automatico') {
                // ── Árbol de decisión C6 ─────────────────────────────────────────
                // Métricas base
                $sum_sq_auto   = array_sum(array_map(fn($q) => $q ** 2, $fechas_map));
                $var_d_auto    = $periodo_dias > 1
                    ? max(0.0, ($sum_sq_auto - $periodo_dias * $d * $d) / ($periodo_dias - 1))
                    : 0.0;
                $cv_d_auto     = $d > 1e-9 && $var_d_auto > 0 ? sqrt($var_d_auto) / $d : 1.0;
                $rotation_auto = $n / max(1, $periodo_dias);

                // Sobredispersión fuerte de la demanda en chunks (umbral más estricto que C5)
                $overdispersed_auto = $n_chunks >= 4 && $mu_chunk > 1e-9
                    && $s2_chunk > $mu_chunk * 1.5;

                // RAMA 1: Rotación media/alta + demanda regular (gran consumo)
                //   → Normal: ROP = d·L + z·σ_empírico  (varianza real de la demanda)
                if ($rotation_auto > 0.40 && $cv_d_auto < 0.70) {
                    $sigma_d      = sqrt(max(0.0, $var_d_auto));
                    $modelo_usado = 'Normal';

                    // RAMA 2: Demanda en rachas/lotes (sobredispersión fuerte)
                    //   → BN: σ² ajustada al parámetro r de la Binomial Negativa
                } elseif ($overdispersed_auto) {
                    $r_bn         = max(0.001, ($mu_chunk ** 2) / ($s2_chunk - $mu_chunk));
                    $sigma_d      = sqrt($d * (1.0 + $d / $r_bn));
                    $modelo_usado = 'BN';

                    // RAMA 3: Demanda muy baja o esporádica (especias, ferretería lenta)
                    //   → Poisson: varianza ≈ media (proceso de eventos raros)
                } elseif ($rotation_auto < 0.10 || $d < 0.3) {
                    $sigma_d      = sqrt($d);
                    $modelo_usado = 'Poisson';

                    // RAMA 4: Demanda muy asimétrica (CV alto) o lead time muy variable
                    //   → Gamma: cuantil directo desde ajuste Gamma a la demanda en L
                } elseif ($cv_d_auto > 1.0 && $var_d_auto > 1e-9 && $d > 1e-9) {
                    $alpha_auto   = ($d ** 2) / $var_d_auto;
                    $theta_auto   = $var_d_auto / $d;
                    $ROP          = $this->_gammaQuantileWH((float)$L * $alpha_auto, $theta_auto, $nivel_servicio);
                    $SS           = max(0.0, $ROP - $d * $L);
                    $modelo_usado = 'Gamma';

                    // RAMA 5: General — Normal si volumen suficiente (d ≥ 1), Poisson si bajo
                } else {
                    if ($d >= 1.0) {
                        $sigma_d      = sqrt(max(0.0, $var_d_auto));
                        $modelo_usado = 'Normal';
                    } else {
                        $sigma_d      = sqrt($d);
                        $modelo_usado = 'Poisson';
                    }
                }
            } else {
                // 'poisson_bn' / 'poisson' — BN si sobredispersado, Poisson si no
                if ($n_chunks >= 4 && $s2_chunk > $mu_chunk + 1e-9 && $mu_chunk > 1e-9) {
                    $r_bn         = max(0.001, ($mu_chunk ** 2) / ($s2_chunk - $mu_chunk));
                    $sigma_d      = sqrt($d * (1.0 + $d / $r_bn));
                    $modelo_usado = 'BN';
                } else {
                    $sigma_d      = sqrt($d);
                    $modelo_usado = 'Poisson';
                }
            }

            // Si el modelo devolvió σ_d (no cuantil directo), calcular SS y ROP
            if ($sigma_d !== null) {
                // Binomial: usa el multiplicador σ del usuario; otros: z del nivel de servicio
                $z_eff = ($modelo_usado === 'Binomial') ? $binomial_sigma_mult : $z;
                $SS    = $z_eff * $sigma_d * sqrt((float)$L);
                $ROP   = $d * $L + $SS;
            }
            if ($ROP === null || $SS === null) continue;

            $stock = $stock_actual[$id] ?? 0.0;
            $dias_autonomia = $d > 0 ? max(0.0, $stock / $d) : PHP_FLOAT_MAX;

            // Modelos con alta variabilidad intrínseca también reportan en MEDIA
            $alta_variabilidad = in_array($modelo_usado, ['BN', 'Gamma', 'Binomial']);

            // Stock muy superior al ROP (> 10×): posible error en stockOn,
            // independientemente del modelo (incluye BN/Gamma/Binomial con alta_variabilidad).
            // Se guarda para reconstrucción posterior; nunca se genera incidencia con stock irreal.
            if ($ROP > 0 && $stock > $umbral_rop_mult * $ROP) {
                $pending_reconstruction[$id] = [
                    'd'                => $d,
                    'ROP'              => $ROP,
                    'SS'               => $SS,
                    'modelo_usado'     => $modelo_usado,
                    'alta_variabilidad' => $alta_variabilidad,
                ];
                continue;
            }

            // Solo artículos accionables: bajo ROP, o modelo de alta variabilidad
            if ($stock >= $ROP && !$alta_variabilidad) continue;

            // ── Severidad ─────────────────────────────────────────────────────
            if ($dias_autonomia < $L) {
                $severidad     = 'CRITICA';
                $posible_causa = 'Agotamiento estimado antes del próximo pedido';
            } elseif ($stock < $ROP) {
                $severidad     = 'ALTA';
                $posible_causa = 'Stock por debajo del punto de pedido (ROP)';
            } else {
                $severidad     = 'MEDIA';
                $posible_causa = 'Stock suficiente pero con alta variabilidad de demanda';
            }

            $incidencias[] = [
                'idArticulo'      => $id,
                'tipo'            => $tipo_override ?: 'Agotamiento Estimado',
                'severidad'       => $severidad,
                'stock_actual'    => $stock,
                'dias_autonomia'  => round($dias_autonomia, 1),
                'lead_time_dias'  => $L,
                'lead_time_fuente' => $lead_fuente,
                'd_diaria'           => round($d, 4),
                'stock_seguridad'    => round($SS, 2),
                'rop'                => round($ROP, 2),
                'modelo_usado'            => $modelo_usado,
                'posible_causa'           => $posible_causa,
                'stock_reconstituido'     => isset($stock_reconstituido_set[$id]),
                'stock_rec_motivo'        => $stock_reconstituido_set[$id] ?? null,
            ];
        }

        // ── Reconstrucción post-bucle para sobrestock sospechoso (> 10×ROP) ─────
        // Los artículos en $pending_reconstruction tienen stock > 10×ROP pero sus
        // parámetros estadísticos (d, ROP, SS) ya están calculados.
        // Se reconstruye el stock desde la última entrada y se re-evalúa si son accionables.
        if (!empty($pending_reconstruction)) {
            $ids_pend_str = implode(',', array_keys($pending_reconstruction));
            $rows_rec_alt = $this->_queryStockReconstituido($ids_pend_str, $ff);
            if (!isset($rows_rec_alt['error'])) {
                foreach ($rows_rec_alt as $id_rec => $stock_rec) {
                    if (!isset($pending_reconstruction[$id_rec])) continue;
                    $pr            = $pending_reconstruction[$id_rec];
                    $d_rec         = (float)$pr['d'];
                    $ROP_rec       = (float)$pr['ROP'];
                    $SS_rec        = (float)$pr['SS'];
                    $modelo_rec    = (string)$pr['modelo_usado'];
                    $alta_var_rec  = (bool)$pr['alta_variabilidad'];

                    // Solo añadir si el stock reconstruido hace al artículo accionable
                    if ($stock_rec >= $ROP_rec && !$alta_var_rec) continue;

                    $dias_auto_rec = $d_rec > 0 ? max(0.0, $stock_rec / $d_rec) : PHP_FLOAT_MAX;

                    if ($dias_auto_rec < $L) {
                        $sev_rec   = 'CRITICA';
                        $causa_rec = 'Agotamiento estimado antes del próximo pedido';
                    } elseif ($stock_rec < $ROP_rec) {
                        $sev_rec   = 'ALTA';
                        $causa_rec = 'Stock por debajo del punto de pedido (ROP)';
                    } else {
                        $sev_rec   = 'MEDIA';
                        $causa_rec = 'Stock suficiente pero con alta variabilidad de demanda';
                    }

                    $incidencias[] = [
                        'idArticulo'          => $id_rec,
                        'tipo'                => $tipo_override ?: 'Agotamiento Estimado',
                        'severidad'           => $sev_rec,
                        'stock_actual'        => $stock_rec,
                        'dias_autonomia'      => round($dias_auto_rec, 1),
                        'lead_time_dias'      => $L,
                        'lead_time_fuente'    => $lead_fuente,
                        'd_diaria'            => round($d_rec, 4),
                        'stock_seguridad'     => round($SS_rec, 2),
                        'rop'                 => round($ROP_rec, 2),
                        'modelo_usado'        => $modelo_rec,
                        'posible_causa'       => $causa_rec,
                        'stock_reconstituido' => true,
                    ];
                }
            }
        }

        // Añadir nombres y tipo (peso / unidad)
        if (!empty($incidencias)) {
            $ids_inc = implode(',', array_unique(array_column($incidencias, 'idArticulo')));
            $smt     = $this->db->query(
                "SELECT idArticulo, articulo_name, tipo FROM articulos WHERE idArticulo IN ($ids_inc)"
            );
            $meta = [];
            if ($smt) {
                while ($r = $smt->fetch_assoc()) {
                    $meta[(int)$r['idArticulo']] = ['nombre' => $r['articulo_name'], 'tipo' => $r['tipo']];
                }
            }
            foreach ($incidencias as &$inc) {
                $inc['nombre']        = $meta[$inc['idArticulo']]['nombre'] ?? '';
                $inc['tipo_articulo'] = $meta[$inc['idArticulo']]['tipo']   ?? 'unidad';
            }
            unset($inc);
        }

        return $incidencias;
    }

    /**
     * Caso 1 — Stock Negativo / Desajuste Puntual.
     *
     * Usa window function SQL (SUM OVER) para calcular el balance mínimo intra-periodo
     * y el delta total por artículo, sin cargar todos los movimientos en PHP.
     *
     * C1a CRITICA — stock_actual < 0  (inventario cierra en negativo)
     * C1b ALTA    — min_running < 0 pero stock_actual >= 0  (negativo puntual recuperado)
     */
    private function getIncidenciasC1(
        string $fi_mov,
        string $ff_mov,
        string $fi_stock,
        string $ff_stock,
        array  $familias_incluir,
        array  $familias_excluir,
        array  $ids_filter          = [],
        array  $stock_base_cache    = [],
        float  $umbral_fraccionado  = 0.05,
        float  $umbral_magnitud     = 0.5,
        float  $umbral_por_venta    = 0.010,
        int    $timing_ventana_dias = 1
    ): array {
        $fi     = $this->db->real_escape_string($fi_mov);
        $ff     = $this->db->real_escape_string($ff_mov);
        $fi_stk = $this->db->real_escape_string($fi_stock);
        $wf     = $this->_familiaWhere($familias_incluir, $familias_excluir);
        $wi     = $this->_idsWhere($ids_filter);

        $rows = $this->_queryDeltasC1($fi, $ff, $wf, $wi);
        if (isset($rows['error'])) return $rows;
        if (empty($rows)) return [];

        $delta_map = [];
        $ids       = [];
        foreach ($rows as $r) {
            $delta_map[(int)$r['idArticulo']] = [
                'delta_total'    => (float)$r['delta_total'],
                'min_running'    => (float)$r['min_running'],
                'fecha_minimo'   => $r['fecha_minimo'],
                'dias_en_minimo' => (int)$r['dias_en_minimo'],
                'dias_en_negativo' => (int)$r['dias_en_negativo'],
            ];
            $ids[] = (int)$r['idArticulo'];
        }

        $stock_base = empty($stock_base_cache)
            ? $this->getStockBase($ids, $fi_stock, $ff_stock)
            : $stock_base_cache;
        if (isset($stock_base['error'])) return $stock_base;

        $incidencias    = [];
        $ids_c1a        = [];   // negativos al cierre (detalle enriquecido)
        $ids_c1b        = [];   // negativos puntuales recuperados (detalle enriquecido)

        foreach ($delta_map as $id => $data) {
            $saldo_base   = $stock_base[$id]['saldo_acumulado'] ?? 0.0;
            $stock_actual = $saldo_base + $data['delta_total'];
            $min_balance  = $saldo_base + $data['min_running'];

            if ($stock_actual < 0) {
                // Señales derivables sin query extra
                $ya_negativo_inicio  = $saldo_base < 0;
                $frac                = abs($stock_actual - round($stock_actual));
                $es_fraccionado      = $frac > $umbral_fraccionado;
                // Badge "Stock decimal" solo cuando el negativo es pequeño (< umbral_magnitud): drift de redondeo plausible
                $fraccionado_es_causa = $es_fraccionado && abs($stock_actual) < $umbral_magnitud && abs($min_balance) < $umbral_magnitud;

                $incidencias[] = [
                    'idArticulo'          => $id,
                    'tipo'                => 'Inventario en negativo',
                    'severidad'           => ($fraccionado_es_causa || $ya_negativo_inicio) ? 'ALTA' : 'CRITICA',
                    'stock_actual'        => $stock_actual,
                    'min_balance'         => $min_balance,
                    'ya_negativo_inicio'  => $ya_negativo_inicio,
                    'fraccionado_es_causa' => $fraccionado_es_causa,
                    'saldo_base'          => $ya_negativo_inicio ? $saldo_base : null,
                    'dias_en_negativo'    => $data['dias_en_negativo'],
                    'posible_causa'       => '',   // se sobreescribe en el bloque de enriquecimiento
                ];
                $ids_c1a[] = $id;
            } elseif ($min_balance < 0) {
                $frac_c1b             = abs($min_balance - round($min_balance));
                $es_fraccionado       = $frac_c1b > $umbral_fraccionado;
                // Badge "Stock decimal" solo cuando el mínimo negativo es pequeño (< umbral_magnitud)
                $fraccionado_es_causa = $es_fraccionado && abs($min_balance) < $umbral_magnitud;

                $incidencias[] = [
                    'idArticulo'           => $id,
                    'tipo'                 => 'Desajuste Puntual de Stock',
                    'severidad'            => 'ALTA',
                    'stock_actual'         => $stock_actual,
                    'min_balance'          => $min_balance,
                    'fraccionado_es_causa' => $fraccionado_es_causa,
                    'fecha_minimo'         => $data['fecha_minimo'],
                    'dias_en_minimo'       => $data['dias_en_minimo'],
                    'posible_causa'        => 'Negativo puntual, recuperado al cierre',
                ];
                $ids_c1b[] = $id;
            }
        }

        // Enriquecer C1a y C1b con actividad del periodo (una sola consulta compartida)
        $ids_todos = array_merge($ids_c1a, $ids_c1b);
        $detalle   = !empty($ids_todos)
            ? $this->_queryDetalleC1(implode(',', $ids_todos), $fi, $ff)
            : [];

        // Proveedor habitual y último para C1a (rango anual para tener datos suficientes)
        $prov_map = !empty($ids_c1a)
            ? $this->_queryProveedorArticulos(implode(',', $ids_c1a), $fi_stk, $ff)
            : [];

        if (!empty($ids_c1a)) {
            foreach ($incidencias as &$inc) {
                if ($inc['tipo'] !== 'Inventario en negativo') continue;
                $d     = $detalle[$inc['idArticulo']] ?? null;
                $n_ent = $d['n_entradas'] ?? 0;
                $inc['n_entradas']     = $n_ent;
                $inc['ultima_entrada'] = $d['ultima_entrada'] ?? null;
                $inc['n_ventas']       = $d['n_ventas']       ?? 0;

                // Proveedor habitual y último (rango anual)
                $prov = $prov_map[$inc['idArticulo']] ?? null;
                $inc['prov_habitual_nombre'] = $prov['prov_habitual_nombre'] ?? null;
                $inc['prov_habitual_n']      = $prov['prov_habitual_n']      ?? null;
                $inc['prov_ultimo_nombre']   = $prov['prov_ultimo_nombre']   ?? null;
                $inc['prov_ultima_fecha']    = $prov['prov_ultima_fecha']    ?? null;
                $inc['prov_es_mismo']        = $prov['prov_es_mismo']        ?? null;

                // Refinar fraccionado_es_causa con n_ventas: umbral dinámico por operación de pesaje
                if ($inc['fraccionado_es_causa']) {
                    $umbral_frac = $umbral_por_venta * max(1, $inc['n_ventas']);
                    if (abs($inc['stock_actual']) > $umbral_frac || abs($inc['min_balance']) > $umbral_frac) {
                        $inc['fraccionado_es_causa'] = false;
                        $inc['severidad']            = $inc['ya_negativo_inicio'] ? 'ALTA' : 'CRITICA';
                    }
                }

                if ($inc['ya_negativo_inicio']) {
                    $inc['posible_causa'] = 'Stock ya negativo al inicio del periodo: el problema viene de antes, revisar inventario anterior';
                } elseif (!empty($inc['fraccionado_es_causa'])) {
                    // Causa: imprecisión de pesaje. Badge "Sin recepciones" puede seguir visible (es un hecho).
                    $inc['posible_causa'] = 'Stock decimal dentro del margen de pesaje: probable venta de últimos restos en balanza o imprecisión acumulada';
                    $inc['severidad']     = 'MEDIA';
                } elseif ($n_ent === 0 && $inc['n_ventas'] > 0) {
                    $inc['posible_causa'] = 'Ventas registradas sin ninguna recepción en el periodo: comprobar si falta dar entrada de mercancía';
                } elseif ($n_ent > 0) {
                    $inc['posible_causa'] = 'Entradas registradas pero el stock sigue negativo: revisar si falta alguna recepción o si hay ventas duplicadas';
                } else {
                    $inc['posible_causa'] = 'Stock negativo sin movimientos en el periodo: revisar el saldo inicial del artículo o si hay ajustes no registrados';
                }
            }
            unset($inc);
        }

        // Enriquecer C1b con causa dinámica + timing
        if (!empty($ids_c1b)) {
            // Confirmar timing: entrada dentro de 3 días tras la fecha del mínimo
            $id_fecha_map = [];
            foreach ($incidencias as $inc) {
                if ($inc['tipo'] === 'Desajuste Puntual de Stock' && !empty($inc['fecha_minimo'])) {
                    $id_fecha_map[$inc['idArticulo']] = $inc['fecha_minimo'];
                }
            }
            $timing_set = $this->_queryTimingC1b($id_fecha_map, $timing_ventana_dias);

            foreach ($incidencias as &$inc) {
                if ($inc['tipo'] !== 'Desajuste Puntual de Stock') continue;
                $d     = $detalle[$inc['idArticulo']] ?? null;
                $n_ent = $d['n_entradas'] ?? 0;
                $inc['n_entradas']     = $n_ent;
                $inc['ultima_entrada'] = $d['ultima_entrada'] ?? null;
                $inc['n_ventas']       = $d['n_ventas']       ?? 0;
                // Refinar fraccionado_es_causa con n_ventas: umbral dinámico por operación de pesaje
                if ($inc['fraccionado_es_causa']) {
                    $umbral_frac = $umbral_por_venta * max(1, $inc['n_ventas']);
                    if (abs($inc['min_balance']) > $umbral_frac) {
                        $inc['fraccionado_es_causa'] = false;
                    }
                }

                // fraccionado_es_causa tiene prioridad sobre timing:
                // la balanza vendió los últimos restos — el timing es consecuencia, no el error.
                if (!empty($inc['fraccionado_es_causa'])) {
                    $inc['timing_proximo'] = false; // suprimir badge timing — no aplica
                    $inc['severidad']      = 'MEDIA';
                    $inc['posible_causa']  = 'Mínimo negativo dentro del margen de pesaje: probable venta de últimos restos en balanza';
                } else {
                    $inc['timing_proximo'] = isset($timing_set[$inc['idArticulo']]);
                    if ($inc['timing_proximo']) {
                        $inc['posible_causa'] = 'Probable venta registrada antes que la recepción (timing de entrada)';
                    } elseif ($n_ent > 0) {
                        $inc['posible_causa'] = 'Entradas en el periodo pero no coinciden con el momento del negativo: revisar si hay un desajuste de inventario puntual';
                    } else {
                        $inc['posible_causa'] = 'Sin recepciones en el periodo: revisar movimientos duplicados o ajustes manuales';
                    }
                }
            }
            unset($inc);
        }

        return $incidencias;
    }

    /**
     * Caso 2 — Entrada con stock alto.
     *
     * Usa window function SQL (SUM OVER ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING)
     * para calcular el stock antes de cada fecha de entrada, sin calcularStockPrevio en PHP.
     *
     * C2 MEDIA — stock_previo >= nunidades * umbral_sobrestock
     */
    /**
     * Ventas por ticket en el periodo para los artículos candidatos de C2.
     * Usado para calcular cobertura_dias = stock_previo / ventas_diarias.
     */
    private function _queryVentasC2(string $ids, string $fi, string $ff): array
    {
        if (empty($ids)) return [];
        $smt = $this->db->query("
            SELECT l.idArticulo, SUM(l.nunidades) AS ventas_total
            FROM ticketslinea l
            INNER JOIN ticketst c ON c.id = l.idticketst
            WHERE l.idArticulo IN ($ids)
              AND DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
              AND c.estado      = 'Cerrado'
              AND l.estadoLinea = 'Activo'
            GROUP BY l.idArticulo
        ");
        if (!$smt) return [];
        $map = [];
        while ($r = $smt->fetch_assoc()) {
            $map[(int)$r['idArticulo']] = (float)$r['ventas_total'];
        }
        return $map;
    }

    /**
     * Enriquece cada incidencia C2 con datos de la recepción anterior al evento:
     *   - dias_desde_anterior  : días entre la recepción anterior y la actual (null si no hay)
     *   - nunidades_anterior       : cantidad de la recepción anterior (null si no hay)
     *   - ventas_entre_recepciones : unidades vendidas por ticket entre ambas recepciones (null si no hay anterior)
     * Busca recepciones hasta 90 días antes de fi para cubrir el primer pedido del periodo.
     */
    private function _queryDetalleC2(array &$incidencias, string $fi, string $ff): void
    {
        if (empty($incidencias)) return;

        $ids     = array_unique(array_column($incidencias, 'idArticulo'));
        $ids_str = implode(',', $ids);
        $fi_ext  = $this->db->real_escape_string(date('Y-m-d', strtotime($fi . ' -90 days')));

        // ── Todas las recepciones del periodo extendido ───────────────────────
        $smt = $this->db->query("
            SELECT l.idArticulo, DATE(c.Fecha) AS fecha, SUM(l.nunidades) AS nunidades
            FROM albprolinea l
            INNER JOIN albprot c ON c.id = l.idalbpro
            WHERE l.idArticulo IN ($ids_str)
              AND DATE(c.Fecha) BETWEEN '$fi_ext' AND '$ff'
              AND c.estado      IN ('Guardado','Facturado')
              AND l.estadoLinea = 'Activo'
              AND l.nunidades       > 0
            GROUP BY l.idArticulo, DATE(c.Fecha)
            ORDER BY l.idArticulo, DATE(c.Fecha)
        ");
        $rec_por_art = [];
        if ($smt) {
            while ($r = $smt->fetch_assoc()) {
                $rec_por_art[(int)$r['idArticulo']][] = ['fecha' => $r['fecha'], 'nunidades' => (float)$r['nunidades']];
            }
        }

        // ── Ventas por ticket, por día, del periodo extendido ─────────────────
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
            while ($r = $smt2->fetch_assoc()) {
                $vtas_por_art[(int)$r['idArticulo']][] = ['fecha' => $r['fecha'], 'qty' => (float)$r['qty']];
            }
        }

        // ── Enriquecer cada incidencia ────────────────────────────────────────
        foreach ($incidencias as &$inc) {
            $id           = $inc['idArticulo'];
            $fecha_actual = $inc['fecha'];

            // Recepción anterior más reciente (la última antes de fecha_actual)
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

    private function getIncidenciasC2(
        string $fi_mov,
        string $ff_mov,
        string $fi_stock,
        string $ff_stock,
        float  $umbral_sobrestock,
        float  $umbral_duplicado,
        float  $umbral_severo,
        int    $umbral_cobertura_dias,
        array  $familias_incluir,
        array  $familias_excluir,
        array  $ids_filter = [],
        array  $stock_base_cache = []   // pre-calculado por el caller para evitar doble consulta
    ): array {
        $fi    = $this->db->real_escape_string($fi_mov);
        $ff    = $this->db->real_escape_string($ff_mov);
        $wf    = $this->_familiaWhere($familias_incluir, $familias_excluir);
        $wi    = $this->_idsWhere($ids_filter);

        $filas_sql = $this->_queryEntradasC2($fi, $ff, $wf, $wi);
        if (isset($filas_sql['error'])) return $filas_sql;
        if (empty($filas_sql)) return [];

        $ids = [];
        foreach ($filas_sql as $e) $ids[(int)$e['idArticulo']] = true;
        $ids = array_keys($ids);

        $stock_base = empty($stock_base_cache)
            ? $this->getStockBase($ids, $fi_stock, $ff_stock)
            : $stock_base_cache;
        if (isset($stock_base['error'])) return $stock_base;

        // ── Paso 1: recoger candidatos (filtro ratio) ────────────────────────
        $candidatos = [];
        foreach ($filas_sql as $e) {
            $id           = (int)$e['idArticulo'];
            $saldo_base   = $stock_base[$id]['saldo_acumulado'] ?? 0.0;
            $stock_previo = $saldo_base + (float)$e['cum_before'];
            $nunidades        = (float)$e['nunidades'];
            if ($nunidades <= 0 || $stock_previo < $nunidades * $umbral_sobrestock) continue;

            $ratio = $stock_previo / $nunidades;
            if ($ratio <= $umbral_duplicado) {
                $categoria     = 'duplicado';
                $posible_causa = 'Stock disponible similar a la entrada recibida: el pedido podría no estar justificado';
            } elseif ($ratio >= $umbral_severo) {
                $categoria     = 'severo';
                $posible_causa = 'Sobrestock significativo: el stock previo superaba ampliamente la cantidad recibida';
            } else {
                $categoria     = 'elevado';
                $posible_causa = 'Sobrestock moderado: el stock disponible superaba el umbral establecido antes de recibir la entrada';
            }

            $candidatos[] = [
                'idArticulo'    => $id,
                'tipo'          => 'Entrada con stock alto',
                'severidad'     => 'MEDIA',
                'nunidades'         => $nunidades,
                'stock_previo'  => $stock_previo,
                'ratio'         => round($ratio, 2),
                'c2_categoria'  => $categoria,
                'fecha'         => $e['fecha'],
                'posible_causa' => $posible_causa,
            ];
        }
        if (empty($candidatos)) return [];

        // ── Paso 2: filtro de cobertura ───────────────────────────────────────
        $ids_cands  = array_unique(array_column($candidatos, 'idArticulo'));
        $ventas_map = $this->_queryVentasC2(implode(',', $ids_cands), $fi, $ff);
        $n_dias     = max(1, (int)((strtotime($ff_mov) - strtotime($fi_mov)) / 86400) + 1);

        $incidencias = [];
        foreach ($candidatos as $cand) {
            $ventas = $ventas_map[$cand['idArticulo']] ?? 0.0;
            if ($ventas > 0) {
                $cobertura = (int)round($cand['stock_previo'] / ($ventas / $n_dias));
                if ($cobertura <= $umbral_cobertura_dias) continue;  // rotación suficiente, falso positivo
                $cand['cobertura_dias'] = $cobertura;
            } else {
                $cand['cobertura_dias'] = null;  // sin ventas = cobertura infinita, siempre flagear
            }
            $incidencias[] = $cand;
        }
        if (empty($incidencias)) return [];

        // ── Paso 3: enriquecer con recepción anterior ─────────────────────────
        $this->_queryDetalleC2($incidencias, $fi_mov, $ff_mov);

        // ── Paso 4: reasignar severidad y posible_causa con todas las señales ─
        foreach ($incidencias as &$inc) {
            $dias    = $inc['dias_desde_anterior'];
            $nunidades_a = $inc['nunidades_anterior'];
            $vtr     = $inc['ventas_entre_recepciones'];
            $cob     = $inc['cobertura_dias'];

            // Duplicado probable: mismo día o día anterior + cantidad similar (diferencia < 15%)
            $es_duplicado_probable = $dias !== null && $dias <= 1 && $nunidades_a !== null
                && (abs($inc['nunidades'] - $nunidades_a) / max($inc['nunidades'], $nunidades_a)) < 0.15;

            // Posible duplicado: hasta 3 días + cantidad similar (señal más débil)
            $es_duplicado_posible = !$es_duplicado_probable && $dias !== null && $dias <= 3 && $nunidades_a !== null
                && (abs($inc['nunidades'] - $nunidades_a) / max($inc['nunidades'], $nunidades_a)) < 0.15;

            // Severidad
            if ($cob === null || $es_duplicado_probable) {
                $inc['severidad'] = 'ALTA';
            }

            // Posible causa (prioridad de señales)
            if ($es_duplicado_probable) {
                $inc['posible_causa'] = "Recepción de cantidad similar hace {$dias} día(s): probable albarán registrado dos veces";
            } elseif ($es_duplicado_posible) {
                $inc['posible_causa'] = "Recepción de cantidad similar hace {$dias} días: verificar si el albarán se registró dos veces";
            } elseif ($cob === null) {
                $inc['posible_causa'] = 'Sobrestock sin salida: el artículo no registra ventas en el periodo analizado';
            } elseif ($dias !== null && $dias <= 14 && $vtr !== null && $vtr < 1) {
                $inc['posible_causa'] = "Sobrestock por acumulación: no hubo ventas entre la recepción anterior y esta nueva entrada ({$dias} días)";
            } elseif ($cob > 180) {
                $inc['posible_causa'] = 'Sobrestock crónico: el stock disponible cubre más de 6 meses al ritmo de ventas actual';
            }
            // Si ninguna condición override, se mantiene la posible_causa basada en ratio del paso 1
        }
        unset($inc);

        // ── Paso 5: consolidar secuencias de acumulación por artículo ─────────
        // Cuando un artículo acumula ≥3 incidencias con cero ventas entre recepciones
        // (≥70% del grupo), las colapsa en una sola fila. Aplica independientemente
        // del gap entre recepciones: captura desde ciclo diario (periódicos) hasta
        // ciclo semanal (pan, revistas, artículos de servicio recurrentes).
        $grupos = [];
        foreach ($incidencias as $inc) {
            $grupos[$inc['idArticulo']][] = $inc;
        }

        $incidencias_final = [];

        foreach ($grupos as $idArt => $lista) {
            usort($lista, fn($a, $b) => strcmp($a['fecha'], $b['fecha']));

            $n_total = count($lista);
            if ($n_total >= 3) {
                $n_acum = 0;
                foreach ($lista as $inc) {
                    $vtr = $inc['ventas_entre_recepciones'];
                    $cob = $inc['cobertura_dias'];
                    // Sin ventas entre las dos recepciones adyacentes, o sin
                    // datos de recepción anterior pero tampoco ventas globales
                    $es_acum = ($vtr !== null && $vtr < 1)
                        || ($vtr === null && $cob === null);
                    if ($es_acum) $n_acum++;
                }
                if ($n_acum / $n_total >= 0.70) {
                    $primero   = $lista[0];
                    $ultimo    = end($lista);
                    $stock_max = max(array_column($lista, 'stock_previo'));

                    // Calcular gap medio entre recepciones para adaptar el diagnóstico
                    $gaps = [];
                    foreach ($lista as $inc) {
                        if ($inc['dias_desde_anterior'] !== null) $gaps[] = $inc['dias_desde_anterior'];
                    }
                    $gap_medio = !empty($gaps) ? (int)round(array_sum($gaps) / count($gaps)) : null;

                    if ($gap_medio !== null && $gap_medio <= 3) {
                        // Ciclo diario: no se puede distinguir automáticamente entre prensa (devoluciones)
                        // y carnicería/bollería (merma/ventas no escaneadas) sin datos de familia.
                        // Se presentan los tres motivos sin ranking para que el responsable evalúe.
                        $causa = "Verificar: merma no registrada · devoluciones no gestionadas · ventas no escaneadas en mostrador";
                    } elseif ($gap_medio !== null && $gap_medio <= 10) {
                        $causa = "(1) Merma no registrada — descartes sin movimiento de baja · (2) Devoluciones pendientes — {$n_total} recepciones semanales sin retorno · (3) Cruce de artículo — verificar si se vende bajo referencia similar";
                    } else {
                        $causa = "(1) Cruce de artículo — verificar si se vende bajo referencia similar · (2) Merma no registrada · (3) Stock inmovilizado — {$n_total} pedidos acumulados sin salida registrada";
                    }

                    $incidencias_final[] = [
                        'idArticulo'               => $idArt,
                        'tipo'                     => 'Entrada con stock alto',
                        'severidad'                => 'ALTA',
                        'nunidades'                    => $ultimo['nunidades'],
                        'stock_previo'             => $stock_max,
                        'ratio'                    => $ultimo['ratio'],
                        'c2_categoria'             => 'acumulacion',
                        'fecha'                    => $ultimo['fecha'],
                        'fecha_inicio'             => $primero['fecha'],
                        'n_eventos'                => $n_total,
                        'gap_medio'                => $gap_medio,
                        'cobertura_dias'           => $ultimo['cobertura_dias'],
                        'dias_desde_anterior'      => $ultimo['dias_desde_anterior'],
                        'nunidades_anterior'           => $ultimo['nunidades_anterior'],
                        'ventas_entre_recepciones' => $ultimo['ventas_entre_recepciones'],
                        'posible_causa'            => $causa,
                    ];
                    continue;
                }
            }
            foreach ($lista as $inc) {
                $incidencias_final[] = $inc;
            }
        }

        // ── Paso 6: C2b — sobrestock progresivo (cobertura creciente con ventas) ─
        // Artículos con ≥3 eventos individuales donde la cobertura creció ≥50%
        // y la cobertura final supera los 45 días. Distinto de acumulación sin ventas:
        // el artículo SÍ vende, pero los pedidos superan sistemáticamente el ritmo.
        $consolidadas = [];
        $individuales = [];
        foreach ($incidencias_final as $inc) {
            if (($inc['c2_categoria'] ?? '') === 'acumulacion') {
                $consolidadas[] = $inc;
            } else {
                $individuales[] = $inc;
            }
        }

        $grupos_b = [];
        foreach ($individuales as $inc) {
            $grupos_b[$inc['idArticulo']][] = $inc;
        }

        $resultado_b = [];
        foreach ($grupos_b as $idArt => $lista) {
            usort($lista, fn($a, $b) => strcmp($a['fecha'], $b['fecha']));
            $n = count($lista);

            if ($n >= 3) {
                $cobs = array_values(array_filter(
                    array_column($lista, 'cobertura_dias'),
                    fn($c) => $c !== null
                ));

                if (count($cobs) >= 3) {
                    $cob_ini = (int)$cobs[0];
                    $cob_fin = (int)end($cobs);

                    if ($cob_ini > 0 && ($cob_fin / $cob_ini) >= 1.5 && $cob_fin >= 45) {
                        $primero   = $lista[0];
                        $ultimo    = end($lista);
                        $stock_max = max(array_column($lista, 'stock_previo'));
                        $resultado_b[] = [
                            'idArticulo'               => $idArt,
                            'tipo'                     => 'Entrada con stock alto',
                            'severidad'                => 'ALTA',
                            'nunidades'                    => $ultimo['nunidades'],
                            'stock_previo'             => $stock_max,
                            'ratio'                    => $ultimo['ratio'],
                            'c2_categoria'             => 'tendencia',
                            'fecha'                    => $ultimo['fecha'],
                            'fecha_inicio'             => $primero['fecha'],
                            'n_eventos'                => $n,
                            'cobertura_inicio'         => $cob_ini,
                            'cobertura_dias'           => $cob_fin,
                            'dias_desde_anterior'      => $ultimo['dias_desde_anterior'],
                            'nunidades_anterior'           => $ultimo['nunidades_anterior'],
                            'ventas_entre_recepciones' => $ultimo['ventas_entre_recepciones'],
                            'posible_causa'            => "Sobrestock progresivo: la cobertura creció de {$cob_ini} a {$cob_fin} días en {$n} entregas — el ritmo de pedidos supera sistemáticamente las ventas",
                        ];
                        continue;
                    }
                }
            }
            foreach ($lista as $inc) {
                $resultado_b[] = $inc;
            }
        }

        return array_merge($consolidadas, $resultado_b);
    }

    /**
     * Casos 3a y 3b — Riesgo de caducidad teórica / Entrada sin rotación previa.
     *
     * Devuelve incidencias C3a y C3b mezcladas (una fila por caso detectado).
     * Usa una sola query SQL: artículos con entrada en la ventana + última venta conocida.
     *
     * C3a MEDIA — tiene última venta Y semanas_sin_venta >= umbral_caducidad
     * C3b BAJA  — no tiene última venta  OR semanas_sin_rot >= umbral_sin_rotacion
     *
     * El rango de ventas va desde fi_stock hasta ff_mov para capturar el mismo
     * "historial conocido" que el cálculo original (stock_base + periodo actual).
     */
    private function getIncidenciasC3(
        string $fi_mov,
        string $ff_mov,
        string $fi_stock,
        int    $umbral_caducidad,
        int    $umbral_sin_rotacion,
        array  $familias_incluir,
        array  $familias_excluir,
        array  $ids_filter           = [],
        int    $dias_post            = 14,
        float  $multiplicador_cadencia = 3.0
    ): array {
        $fi_m  = $this->db->real_escape_string($fi_mov);
        $ff_m  = $this->db->real_escape_string($ff_mov);
        $fi_s  = $this->db->real_escape_string($fi_stock);
        $wf    = $this->_familiaWhere($familias_incluir, $familias_excluir);
        $wi    = $this->_idsWhere($ids_filter);
        $min_u = min($umbral_caducidad, $umbral_sin_rotacion);

        $rows = $this->_queryUltimaVentaC3($fi_m, $ff_m, $fi_s, $wf, $wi, $min_u, $dias_post, $multiplicador_cadencia);
        if (isset($rows['error'])) return $rows;

        // Stock al cierre del periodo para los artículos afectados
        $stock_map = [];
        if (!empty($rows)) {
            $ids_c3    = implode(',', array_unique(array_map(fn($r) => (int)$r['idArticulo'], $rows)));
            $rows_stk  = $this->_queryStockRebobinado($ids_c3, $ff_m, false);
            if (!isset($rows_stk['error'])) {
                foreach ($rows_stk as $rs) {
                    $stock_map[(int)$rs['idArticulo']] = (float)$rs['stock_en_periodo'];
                }
            }
        }

        $fecha_fin_dt = new DateTime($ff_mov);
        // Días totales del historial de ventas consultado (fi_stock → ff_mov)
        $periodo_dias = (new DateTime($fi_stock))->diff(new DateTime($ff_mov))->days + 1;
        $incidencias  = [];

        foreach ($rows as $r) {
            $id                    = (int)$r['idArticulo'];
            $ultima_venta          = $r['ultima_venta'];
            $stock_actual          = $stock_map[$id] ?? null;
            $n_entradas            = (int)$r['n_entradas'];
            $cantidad_recibida     = (float)$r['cantidad_recibida'];
            $fecha_primera_entrada = $r['fecha_primera_entrada'];
            $n_devoluciones        = (int)$r['n_devoluciones'];
            $cantidad_devuelta     = (float)$r['cantidad_devuelta'];
            $tiene_devolucion      = $n_devoluciones > 0;

            if ($ultima_venta !== null) {
                $semanas = (new DateTime($ultima_venta))->diff($fecha_fin_dt)->days / 7.0;
            } else {
                $semanas = null;
            }

            // C3a: caída de rotación (umbral dinámico: avg_cadencia × multiplicador)
            $n_ventas_historico = (int)$r['n_ventas_historico'];
            $avg_cadencia_dias  = $n_ventas_historico > 0
                ? round($periodo_dias / $n_ventas_historico, 1)
                : null;
            $umbral_efectivo_dias = $avg_cadencia_dias !== null
                ? $avg_cadencia_dias * $multiplicador_cadencia
                : PHP_INT_MAX;

            // Referente de "días sin venta":
            // Si el pedido llegó DESPUÉS de la última venta (el artículo se agotó y se
            // repuso), los días de stockout no son imputables a rotación caída — el
            // artículo rotó bien hasta agotar el stock.
            // En ese caso se cuenta desde fecha_primera_entrada, no desde ultima_venta.
            $desde_reposicion = $fecha_primera_entrada !== null
                && $ultima_venta !== null
                && strcmp($fecha_primera_entrada, $ultima_venta) > 0;
            $ref_c3a     = $desde_reposicion ? $fecha_primera_entrada : $ultima_venta;
            $semanas_c3a = $ref_c3a !== null
                ? (new DateTime($ref_c3a))->diff($fecha_fin_dt)->days / 7.0
                : null;

            // Stock > 0 requerido: sin stock no hay inventario inmovilizado.
            // n_ventas_historico >= 3: cadencia mínimamente fiable.
            $c3a_ok = $semanas_c3a !== null
                && $n_ventas_historico >= 3
                && ($stock_actual === null || $stock_actual > 0)
                && ($semanas_c3a * 7) >= $umbral_efectivo_dias;
            if ($c3a_ok) {
                $ratio_a   = ($semanas_c3a * 7) / max(1, $umbral_efectivo_dias);
                $severidad = $ratio_a >= 2.0 ? 'ALTA' : 'MEDIA';
                $wsem_c3a  = round($semanas_c3a, 1);
                $es_fast   = $avg_cadencia_dias !== null && $avg_cadencia_dias <= 7.0;
                if ($desde_reposicion) {
                    // Artículo agotó stock (rotó bien) y el nuevo pedido no gira.
                    // Las causas apuntan al nuevo stock, no al historial general.
                    if ($es_fast) {
                        if ($ratio_a <= 1.5) {
                            $causa = "Recibido sin venta desde la última recepción — verificar ubicación en sala, EAN y precio";
                        } elseif ($ratio_a <= 2.5) {
                            $causa = "Nuevo pedido sin rotación en artículo de alta rotación — posible merma no registrada o problema de EAN";
                        } else {
                            $causa = "Nuevo stock paralizado desde la recepción — revisión urgente: exposición, estado del producto y precio";
                        }
                    } else {
                        if ($ratio_a <= 1.5) {
                            $causa = "Repuesto tras agotamiento sin rotación posterior — verificar si hay demanda activa antes del próximo pedido";
                        } elseif ($ratio_a <= 2.5) {
                            $causa = "Artículo repuesto pero sin demanda activa — posible artículo estacional o referencia sustituida";
                        } else {
                            $causa = "Nuevo stock sin movimiento desde la recepción — valorar devolución al proveedor o liquidación";
                        }
                    }
                } elseif ($es_fast) {
                    if ($ratio_a <= 1.5) {
                        $causa = "Artículo de alta rotación con caída reciente — verificar ubicación en sala, EAN y precio";
                    } elseif ($ratio_a <= 2.5) {
                        $causa = "Alta rotación interrumpida — posible merma no registrada, problema de EAN o artículo agotado en lineal";
                    } else {
                        $causa = "Artículo de alta rotación sin ventas desde hace {$wsem_c3a} sem. — revisión urgente de exposición y estado del producto";
                    }
                } else {
                    if ($ratio_a <= 1.5) {
                        $causa = "Posible artículo estacional — revisar ventas en el mismo periodo del año anterior";
                    } elseif ($ratio_a <= 2.5) {
                        $causa = "Posible referencia sustituida — verificar si hay artículo similar activo con rotación";
                    } else {
                        $causa = "Sin demanda desde hace {$wsem_c3a} sem. — valorar liquidación o baja de referencia";
                    }
                }
                $incidencias[] = [
                    'idArticulo'                 => $id,
                    'tipo'                       => 'Caída de rotación',
                    'severidad'                  => $severidad,
                    'stock_actual'               => $stock_actual,
                    'ultima_venta'               => $ultima_venta,
                    'fecha_primera_entrada'      => $fecha_primera_entrada,
                    'desde_reposicion'           => $desde_reposicion,
                    'semanas_desde_ultima_venta' => $wsem_c3a,
                    'avg_cadencia_dias'          => $avg_cadencia_dias,
                    'n_entradas'                 => $n_entradas,
                    'cantidad_recibida'          => $cantidad_recibida,
                    'posible_causa'              => $causa,
                ];
            }

            // C3b: entrada sin rotación previa
            if ($ultima_venta === null) {
                // Seguridad anti-falso-positivo: solo aplica cuando hay una única entrada
                // en el periodo (artículo que acaba de llegar y aún no ha tenido tiempo
                // de vender). Con n_entradas >= 2 el patrón es una incidencia real
                // independientemente de si vende justo después del periodo.
                if ($n_entradas <= 1 && $r['primera_venta_post'] !== null) {
                    continue;
                }
                // posible_causa diferenciada según patrón de entradas y devoluciones
                if ($tiene_devolucion) {
                    $ratio_dev = $cantidad_recibida > 0 ? $cantidad_devuelta / $cantidad_recibida : 0;
                    if ($ratio_dev >= 0.8) {
                        $causa_nunca = "Artículo recibido y devuelto casi en su totalidad — verificar si el pedido fue rechazado o si hubo un error en el albarán";
                    } else {
                        $causa_nunca = "Artículo con recepciones y devoluciones parciales sin ventas — posible problema de calidad o pedido incorrecto";
                    }
                } elseif ($n_entradas >= 3) {
                    $causa_nunca = "Recibido {$n_entradas} veces sin ninguna venta registrada — prioritario: verificar código de barras o referencia duplicada";
                } elseif ($n_entradas >= 2) {
                    $causa_nunca = "Recibido {$n_entradas} veces sin ninguna venta — verificar si el código de barras es correcto o si las ventas se registran bajo otra referencia";
                } else {
                    $causa_nunca = "Sin ventas registradas — verificar si el código de barras es correcto o si las ventas se registran bajo otra referencia";
                }
                // Severidad: MEDIA si se ha pedido varias veces sin ninguna venta (problema sistémico)
                $sev_nunca = ($n_entradas >= 2 || $tiene_devolucion) ? 'MEDIA' : 'BAJA';
                $incidencias[] = [
                    'idArticulo'                  => $id,
                    'tipo'                        => 'Entrada sin rotación previa',
                    'severidad'                   => $sev_nunca,
                    'stock_actual'                => $stock_actual,
                    'ultima_salida'               => null,
                    'semanas_desde_ultima_salida' => null,
                    'n_entradas'                  => $n_entradas,
                    'cantidad_recibida'           => $cantidad_recibida,
                    'fecha_primera_entrada'       => $fecha_primera_entrada,
                    'n_devoluciones'              => $n_devoluciones,
                    'cantidad_devuelta'           => $cantidad_devuelta,
                    'posible_causa'               => $causa_nunca,
                ];
            } elseif ($semanas >= $umbral_sin_rotacion) {
                $ratio_b  = $semanas / $umbral_sin_rotacion;
                $wsem     = round($semanas, 1);
                // Severidad: MEDIA si se sigue reponiendo con alta inmovilización
                $sev_largo = ($n_entradas >= 2 || $ratio_b >= 2.0) ? 'MEDIA' : 'BAJA';
                if ($n_entradas >= 2) {
                    if ($ratio_b <= 1.5) {
                        $causa_b = "Pedido {$n_entradas} veces sin rotación activa — verificar si el comprador tiene visibilidad del stock disponible";
                    } elseif ($ratio_b <= 2.5) {
                        $causa_b = "Pedido {$n_entradas} veces pese a {$wsem} sem. sin movimiento — posible referencia sustituida sin darse de baja";
                    } else {
                        $causa_b = "Artículo inmovilizado con reposición activa ({$n_entradas} pedidos, {$wsem} sem. sin venta) — revisar proceso de compra";
                    }
                } else {
                    if ($ratio_b <= 1.5) {
                        $causa_b = "Sin movimiento en {$wsem} sem. — verificar si hay demanda estacional o si el artículo está bien ubicado en sala";
                    } elseif ($ratio_b <= 2.5) {
                        $causa_b = "Posible referencia sustituida o sin demanda activa — revisar si las ventas se registran bajo otra referencia similar";
                    } else {
                        $causa_b = "Artículo inmovilizado ({$wsem} sem. sin movimiento) — valorar eliminar del surtido activo o liquidar";
                    }
                }
                $incidencias[] = [
                    'idArticulo'                  => $id,
                    'tipo'                        => 'Entrada sin rotación previa',
                    'severidad'                   => $sev_largo,
                    'stock_actual'                => $stock_actual,
                    'ultima_salida'               => $ultima_venta,
                    'semanas_desde_ultima_salida' => round($semanas, 1),
                    'n_entradas'                  => $n_entradas,
                    'cantidad_recibida'           => $cantidad_recibida,
                    'fecha_primera_entrada'       => $fecha_primera_entrada,
                    'n_devoluciones'              => $n_devoluciones,
                    'cantidad_devuelta'           => $cantidad_devuelta,
                    'posible_causa'               => $causa_b,
                ];
            }
        }
        return $incidencias;
    }

    /**
     * C7 helper — Estadísticos básicos de un array de suelos (valores float).
     *
     * @param  float[] $floors  Valores de suelo (pueden ser negativos para C7b)
     * @return array  [media, desv_típica_muestral, n]
     */
    private function _statsFloors(array $floors): array
    {
        $n = count($floors);
        if ($n === 0) return [0.0, 0.0, 0];
        $mean = array_sum($floors) / $n;
        $var  = 0.0;
        foreach ($floors as $v) $var += ($v - $mean) ** 2;
        $sd = $n > 1 ? sqrt($var / ($n - 1)) : 0.0;
        return [$mean, $sd, $n];
    }

    /**
     * Correlación de Pearson entre dos arrays de igual longitud.
     * Devuelve 0.0 si n < 2 o si alguna serie es constante.
     */
    private function _pearsonCorr(array $x, array $y): float
    {
        $n = count($x);
        if ($n < 2 || $n !== count($y)) return 0.0;
        $mx = array_sum($x) / $n;
        $my = array_sum($y) / $n;
        $num = 0.0;
        $dx2 = 0.0;
        $dy2 = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $dx  = $x[$i] - $mx;
            $dy  = $y[$i] - $my;
            $num += $dx * $dy;
            $dx2 += $dx * $dx;
            $dy2 += $dy * $dy;
        }
        $denom = sqrt($dx2 * $dy2);
        return $denom > 0.0 ? $num / $denom : 0.0;
    }

    /**
     * Regresión lineal simple OLS sobre y[0..n-1] con x = 0, 1, …, n-1.
     *
     * Devuelve:
     *   'slope'   — pendiente β̂₁  (ud/recepción)
     *   'r2'      — coeficiente de determinación R²
     *   'p_value' — p-valor bilateral aproximado para el test t sobre β̂₁
     *               (0.05 si significativa al 10 %, 0.15 si no lo es)
     *
     * Devuelve p_value = 1.0 si n < 3 (grados de libertad insuficientes).
     */
    private function _regressionStats(array $y): array
    {
        $n = count($y);
        if ($n < 3) return ['slope' => 0.0, 'r2' => 0.0, 'p_value' => 1.0, 'se' => 0.0];

        $sum_x  = 0;
        $sum_y  = 0.0;
        $sum_xy = 0.0;
        $sum_xx = 0;
        for ($i = 0; $i < $n; $i++) {
            $sum_x  += $i;
            $sum_y  += $y[$i];
            $sum_xy += $i * $y[$i];
            $sum_xx += $i * $i;
        }
        $denom = $n * $sum_xx - $sum_x * $sum_x;
        if ($denom == 0.0) return ['slope' => 0.0, 'r2' => 0.0, 'p_value' => 1.0, 'se' => 0.0];

        $slope     = ($n * $sum_xy - $sum_x * $sum_y) / $denom;
        $intercept = ($sum_y - $slope * $sum_x) / $n;
        $mean_y    = $sum_y / $n;

        $ss_tot = 0.0;
        $ss_res = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $y_hat   = $intercept + $slope * $i;
            $ss_res += ($y[$i] - $y_hat) ** 2;
            $ss_tot += ($y[$i] - $mean_y) ** 2;
        }
        $r2 = ($ss_tot > 0.0) ? max(0.0, 1.0 - $ss_res / $ss_tot) : 0.0;

        // t-estadístico: β̂₁ / se(β̂₁), df = n-2
        $sxx = (float)$sum_xx - (float)($sum_x * $sum_x) / $n;
        // C7a-014: guard near-zero SSE (floors perfectamente colineales o casi).
        // <= 0.0 solo captura cero exacto; < 1e-9 cubre ruido float que produce t → ∞.
        // Se devuelve se=0.0 para que el Nivel 4 de la cascada C7a no ejecute (guard $se_corr_c7a > 0).
        // p_value=0.01 (no 0.0) evita la "certeza perfecta" artificial: Mann-Kendall tomará la decisión.
        if ($ss_res < 1e-9 || $sxx <= 0.0) {
            return ['slope' => $slope, 'r2' => 1.0, 'p_value' => ($slope > 0.0 ? 0.01 : 1.0), 'se' => 0.0];
        }
        $se = sqrt(($ss_res / ($n - 2)) / $sxx);
        $t  = abs($slope / $se);

        // Valores críticos t_{df, 0.95} (bilateral α = 0.10) — lookup + interpolación lineal
        static $t_tab = [
            1 => 6.314,
            2 => 2.920,
            3 => 2.353,
            4 => 2.132,
            5 => 2.015,
            6 => 1.943,
            7 => 1.895,
            8 => 1.860,
            9 => 1.833,
            10 => 1.812,
            11 => 1.796,
            12 => 1.782,
            13 => 1.771,
            14 => 1.761,
            15 => 1.753,
            16 => 1.746,
            17 => 1.740,
            18 => 1.734,
            19 => 1.729,
            20 => 1.725,
            25 => 1.708,
            30 => 1.697,
            40 => 1.684,
            60 => 1.671,
            120 => 1.658,
        ];
        $df = $n - 2;
        if ($df > 120) {
            $t_crit = 1.645;
        } elseif (isset($t_tab[$df])) {
            $t_crit = $t_tab[$df];
        } else {
            $keys = array_keys($t_tab);
            $lo = $hi = null;
            foreach ($keys as $k) {
                if ($k <= $df) $lo = $k;
                if ($k >= $df && $hi === null) $hi = $k;
            }
            $t_crit = ($lo !== null && $hi !== null && $lo !== $hi)
                ? $t_tab[$lo] + ($df - $lo) / ($hi - $lo) * ($t_tab[$hi] - $t_tab[$lo])
                : ($lo !== null ? $t_tab[$lo] : 1.645);
        }
        return ['slope' => $slope, 'r2' => $r2, 'p_value' => ($t >= $t_crit ? 0.05 : 0.15), 'se' => $se];
    }

    /**
     * C7c — Metadatos de artículos para la detección de cruces.
     * Devuelve [idArticulo => ['nombre' => string, 'tipo' => string, 'familias' => int[]]].
     */
    private function _queryMetaC7c(string $ids_str): array
    {
        if (empty($ids_str)) return [];
        $meta = [];

        // Nombre y tipo de venta
        $smt = $this->db->query(
            "SELECT idArticulo, articulo_name, tipo FROM articulos WHERE idArticulo IN ($ids_str)"
        );
        if ($smt) {
            while ($r = $smt->fetch_assoc()) {
                $meta[(int)$r['idArticulo']] = [
                    'nombre'      => (string)$r['articulo_name'],
                    'tipo'        => (string)$r['tipo'],
                    'familias'    => [],   // familia directa del artículo
                    'familias_n2' => [],   // nivel 2 de la jerarquía
                    'familias_n1' => [],   // nivel 1 (departamento)
                ];
            }
        }

        // Familia directa + N1/N2 desde la vista de jerarquía (una sola query)
        $smt = $this->db->query("
            SELECT af.idArticulo,
                   vj.idFamilia,
                   vj.idN1,
                   vj.idN2
            FROM   articulosFamilias af
            JOIN   vw_jerarquias_familias vj ON vj.idFamilia = af.idFamilia
            WHERE  af.idArticulo IN ($ids_str)
        ");
        if ($smt) {
            while ($r = $smt->fetch_assoc()) {
                $id = (int)$r['idArticulo'];
                if (!isset($meta[$id])) continue;
                $meta[$id]['familias'][]    = (int)$r['idFamilia'];
                if (!empty($r['idN2'])) $meta[$id]['familias_n2'][] = (int)$r['idN2'];
                if (!empty($r['idN1'])) $meta[$id]['familias_n1'][] = (int)$r['idN1'];
            }
        }

        // Deduplica los arrays de jerarquía
        foreach ($meta as &$m) {
            $m['familias']    = array_unique($m['familias']);
            $m['familias_n2'] = array_unique($m['familias_n2']);
            $m['familias_n1'] = array_unique($m['familias_n1']);
        }
        unset($m);

        return $meta;
    }

    /**
     * Similitud de Jaccard sobre bolsa de palabras (insensible a mayúsculas).
     * Devuelve 0.0–1.0; 1.0 = mismas palabras exactas.
     */
    private function _jaccardNombre(string $a, string $b): float
    {
        $wa = array_filter(preg_split('/\s+/', mb_strtolower($a)));
        $wb = array_filter(preg_split('/\s+/', mb_strtolower($b)));
        if (empty($wa) || empty($wb)) return 0.0;
        $inter = count(array_intersect($wa, $wb));
        $union = count(array_unique(array_merge($wa, $wb)));
        return $union > 0 ? $inter / $union : 0.0;
    }

    /**
     * C7 — Offset sistemático de inventario.
     *
     * Detecta artículos cuyo suelo de stock post-recepción sigue un patrón consistente
     * a través de 3 o más recepciones en el periodo:
     *
     *  · C7b — Offset negativo sistemático: el suelo se estabiliza en un nivel negativo
     *    y estable (σ/|μ| < 0.5). Señal: el sistema lleva N unidades "de más" registradas
     *    que no existen físicamente (inventario inicial erróneo o devoluciones no descontadas).
     *
     *  · C7a — Merma sistemática no registrada: el suelo sube acumulativamente de recepción
     *    en recepción (pendiente positiva ≥ 0.5 ud/recepción, acumulado ≥ 2 ud.).
     *    Señal: merma/caducidad no registrada o ventas no escaneadas sistemáticamente.
     *
     * Algoritmo:
     *  1. Obtener fechas de recepción por artículo; descartar los que tienen < 3.
     *  2. Construir el timeline de stock (saldo_base + Σ deltas diarios) para los candidatos.
     *  3. Para cada intervalo [recepción_i, recepción_{i+1}) calcular el mínimo de stock.
     *  4. Analizar estadísticamente el array de suelos: media, desviación típica, pendiente
     *     de regresión lineal. Clasificar según los umbrales definidos.
     *
     * @param string $fi_mov
     * @param string $ff_mov
     * @param string $fi_stock        Inicio ventana stock base (Jan 1 del año del periodo)
     * @param string $ff_stock        Fin ventana stock base (día anterior al inicio del periodo)
     * @param array  $familias_incluir
     * @param array  $familias_excluir
     * @param array  $ids_filter
     * @param array  $stock_base_cache  Pre-calculado por getIncidencias si C1/C2 también activos
     *
     * @return array  Incidencias detectadas (sin campo 'nombre'; lo añade getIncidencias)
     */
    private function getIncidenciasC7(
        string $fi_mov,
        string $ff_mov,
        string $fi_stock,
        string $ff_stock,
        array  $familias_incluir,
        array  $familias_excluir,
        array  $ids_filter            = [],
        array  $stock_base_cache      = [],
        array  $subcasos              = ['C7a', 'C7b'],
        int    $c7b_min_recepciones    = 3,
        float  $c7b_umbral_cv          = 0.5,
        float  $c7b_umbral_cv_peso     = 0.75,
        float  $c7b_umbral_iqr_peso    = 2.0,
        float  $c7b_umbral_ruido_peso  = 0.5,
        int    $c7b_umbral_sev_unidad  = 5,
        float  $c7b_umbral_sev_peso    = 2.5,
        bool   $c7b_cascada_exhaustiva = false,
        bool   $skip_cde              = false,   // true en lotes parciales; C7c/d/e requiere el conjunto completo
        float  $c7a_umbral_delta_unidad     = 2.0,
        float  $c7a_umbral_delta_peso       = 1.0,
        float  $c7a_umbral_pvalue           = 0.10,
        float  $c7a_umbral_pvalue_alta      = 0.05,
        float  $c7a_umbral_alta_delta_unidad = 10.0,
        float  $c7a_umbral_alta_delta_peso  = 5.0,
        float  $c7a_umbral_alta_slope_unidad = 2.0,
        float  $c7a_umbral_alta_slope_peso  = 1.0,
        float  $c7a_umbral_snr              = 0.15,
        bool   $c7a_cascada_exhaustiva      = false
    ): array {
        $subcasos_set    = array_flip($subcasos);
        $min_recepciones = 2;   // mínimo global para C7b_posible (2 floors análisis); el test IC95 requiere $c7b_min_recepciones

        $fi     = $this->db->real_escape_string($fi_mov);
        $ff     = $this->db->real_escape_string($ff_mov);
        $fi_stk = $this->db->real_escape_string($fi_stock);  // inicio ventana extendida (1-Ene)
        $wf     = $this->_familiaWhere($familias_incluir, $familias_excluir);
        $wi     = $this->_idsWhere($ids_filter);

        // ── Paso 1: recepciones en ventana extendida fi_stock→ff_mov ─────────
        // Se amplía el rango al periodo base (fi_stock→fi_mov-1) para disponer de
        // más floors históricos y poder confirmar el patrón antes del análisis.
        $rows_rec = $this->_queryRecepcionesFechasC7($fi_stk, $ff, $wf, $wi);
        if (isset($rows_rec['error'])) return $rows_rec;
        if (empty($rows_rec)) return [];

        // Agrupar por artículo; filtrar los que tienen ≥ $min_recepciones fechas distintas
        $recepciones_map = [];
        $cantidades_map  = [];   // [idArticulo][fecha] = cantidad recibida ese día
        foreach ($rows_rec as $r) {
            $aid = (int)$r['idArticulo'];
            $recepciones_map[$aid][]             = $r['fecha'];
            $cantidades_map[$aid][$r['fecha']]   = (float)$r['cantidad'];
        }

        $candidatos_ids = [];
        foreach ($recepciones_map as $id => $fechas) {
            $fechas_unicas = array_values(array_unique($fechas));
            sort($fechas_unicas);
            if (count($fechas_unicas) >= $min_recepciones) {
                $recepciones_map[$id] = $fechas_unicas;
                $candidatos_ids[]     = $id;
            } else {
                unset($recepciones_map[$id]);
            }
        }

        if (empty($candidatos_ids)) return [];

        // ── Paso 2: timeline de movimientos para los candidatos ──────────────
        $ids_str       = implode(',', array_map('intval', $candidatos_ids));

        // Tipo de artículo por ID (unidad | peso) — necesario para C7b-001
        // Umbrales de severidad diferenciados: peso tiene mayor tolerancia al ruido
        $tipos_map = [];
        $smt_tipo = $this->db->query(
            "SELECT idArticulo, tipo FROM articulos WHERE idArticulo IN ($ids_str)"
        );
        if ($smt_tipo) {
            while ($r = $smt_tipo->fetch_assoc()) {
                $tipos_map[(int)$r['idArticulo']] = (string)$r['tipo'];
            }
        }
        // ── Paso 2: timeline fi_stock→ff_mov (ventana extendida) ────────────
        // El sistema es anual (fi_stock = 1-Ene): stock(d) = Σ deltas(fi_stock→d).
        // Matemáticamente equivale al enfoque anterior (saldo_base + Σ deltas(fi_mov→d))
        // pero permite calcular floors también en el periodo base previo a fi_mov.
        // $stock_base_cache se conserva en la firma por compatibilidad pero no se usa.
        $rows_timeline = $this->_queryTimelineMovimientosC7($fi_stk, $ff, $ids_str);
        if (isset($rows_timeline['error'])) return $rows_timeline;

        // Indexar delta diario por [idArticulo][fecha]
        $daily_map = [];
        foreach ($rows_timeline as $r) {
            $daily_map[(int)$r['idArticulo']][$r['fecha']] = (float)$r['day_delta'];
        }

        // ── Artículos con actividad albcli en el período (C7a-025) ───────────
        // Set idArticulo → true para los que tienen al menos un albarán de cliente.
        // Una salida inter-tienda no registrada inflaría el floor artificialmente.
        $has_albcli_ids = $this->_queryHasAlbcliC7($fi_stk, $ff, $ids_str);
        if (isset($has_albcli_ids['error'])) $has_albcli_ids = []; // no bloquear si falla

        // ── Paso 3: calcular suelos inter-recepción y detectar patrón ────────
        $incidencias     = [];
        $ping_cada_n     = 20;   // hacer ping a MySQL cada N artículos para evitar wait_timeout
        $ping_contador   = 0;

        foreach ($candidatos_ids as $id) {
            // Keepalive: evitar que MySQL cierre la conexión durante el cálculo estadístico.
            // El bootstrap O(n²) puede tardar segundos por artículo; con muchos artículos
            // el tiempo total supera el wait_timeout del servidor.
            if (++$ping_contador % $ping_cada_n === 0) {
                try {
                    $this->db->ping();
                } catch (\mysqli_sql_exception $e) { /* ignorar */
                }
            }
            $fechas_rec = $recepciones_map[$id];
            $daily      = $daily_map[$id] ?? [];
            $n_rec      = count($fechas_rec);

            // Construir stock acumulado por fecha en la ventana extendida fi_stock→ff_mov.
            // stock(d) = Σ day_delta desde fi_stock hasta d (parte de 0 al inicio del año).
            $cum_delta     = 0.0;
            $stock_by_date = [];
            $all_dates     = array_keys($daily);
            sort($all_dates);
            foreach ($all_dates as $d) {
                $cum_delta         += $daily[$d];
                $stock_by_date[$d]  = $cum_delta;
            }

            if (empty($stock_by_date)) continue;   // sin movimientos registrados

            // Calcular suelo (mínimo de stock) para cada intervalo inter-recepción.
            // El intervalo i cubre [fecha_rec_i, fecha_rec_{i+1}), es decir:
            //   · incluye el día de la recepción i (post-recepción)
            //   · excluye el día de la recepción i+1 (se calcula en el intervalo i+1)
            // El último intervalo va hasta ff_mov (fin del periodo).
            //
            // C7b-002: se registra también la duración (días) de cada intervalo para
            // normalizar los floors antes del análisis estadístico.  Intervalos más
            // largos producen floors más negativos por simple acumulación de demanda;
            // sin normalización la media se sesga hacia los períodos de entregas espaciadas.
            $floors          = [];
            $dias_intervalos = [];
            $fechas_floors   = [];   // fecha de recepción correspondiente a cada floor
            for ($i = 0; $i < $n_rec; $i++) {
                $fecha_ini      = $fechas_rec[$i];
                $fecha_fin      = ($i + 1 < $n_rec) ? $fechas_rec[$i + 1] : null; // null = hasta ff
                $fecha_fin_real = $fecha_fin ?? $ff_mov;
                $dias_intervalo = max(1, (int)(
                    (strtotime($fecha_fin_real) - strtotime($fecha_ini)) / 86400
                ));

                $min_floor = null;
                foreach ($stock_by_date as $d => $stock) {
                    if ($d < $fecha_ini) continue;
                    if ($fecha_fin !== null && $d >= $fecha_fin) continue;
                    if ($min_floor === null || $stock < $min_floor) {
                        $min_floor = $stock;
                    }
                }

                if ($min_floor !== null) {
                    $floors[]          = $min_floor;
                    $dias_intervalos[] = $dias_intervalo;
                    $fechas_floors[]   = $fecha_ini;
                }
            }

            $n_floors = count($floors);

            if ($n_floors < $min_recepciones) continue;   // suelos insuficientes

            // Mapa fecha_recepción → suelo (para la validación con periodo común en C7c)
            $floors_map = [];
            for ($i = 0; $i < $n_floors; $i++) {
                $floors_map[$fechas_floors[$i]] = $floors[$i];
            }

            // ── Split base / análisis ─────────────────────────────────────────
            // Periodo base:     [fi_stock, fi_mov)  — histórico anual previo al análisis.
            // Periodo análisis: [fi_mov,   ff_mov]  — ventana de análisis solicitada.
            // Prioridad del test IC95: base (más largo, libre del evento analizado);
            // si base < 3 floors se usa el periodo de análisis.
            $floors_base     = [];
            $dias_base     = [];
            $floors_analysis = [];
            $dias_analysis = [];
            foreach ($fechas_floors as $idx => $fd) {
                if ($fd < $fi_mov) {
                    $floors_base[]    = $floors[$idx];
                    $dias_base[]      = $dias_intervalos[$idx];
                } else {
                    $floors_analysis[] = $floors[$idx];
                    $dias_analysis[]   = $dias_intervalos[$idx];
                }
            }
            $n_base     = count($floors_base);
            $n_analysis = count($floors_analysis);

            $tipo_art     = $tipos_map[$id] ?? 'unidad';
            $mean_raw     = array_sum($floors) / $n_floors;
            $abs_mean_raw = abs($mean_raw);

            // Regresión lineal sobre todos los floors (usada por C7a)
            // C7a-001: normalizar por duración del intervalo (igual que C7b-002) para que
            // la pendiente no mezcle señal real con efecto de escala del intervalo.
            $dias_intervalo_medio_all = array_sum($dias_intervalos) / $n_floors;
            $floors_norm_all = [];
            for ($i = 0; $i < $n_floors; $i++) {
                $floors_norm_all[] = $floors[$i] / $dias_intervalos[$i];
            }
            $reg_norm        = $this->_regressionStats($floors_norm_all);
            $slope_norm      = $reg_norm['slope'];
            // tendencia_visible se calcula más abajo usando Theil-Sen (C7a-019/C7a-012)

            // C7a-011 + C7a-019: preparar r₁ y se_corr para la cascada estadística.
            // r₁ se usa en Nivel 1 (corrección Hamed & Rao de Mann-Kendall)
            // y en Nivel 4 (IC95 Newey-West como último recurso).
            $autocorr_lag1_c7a = 0.0;
            $se_corr_c7a       = $reg_norm['se'];   // se sin corregir; se actualiza si r₁ > 0
            if ($n_floors >= 4 && $reg_norm['se'] > 0.0) {
                $mean_norm_all = array_sum($floors_norm_all) / $n_floors;
                $cov_lag1_c7a  = 0.0;
                $var_norm_all  = 0.0;
                for ($i = 1; $i < $n_floors; $i++) {
                    $cov_lag1_c7a += ($floors_norm_all[$i] - $mean_norm_all) * ($floors_norm_all[$i - 1] - $mean_norm_all);
                }
                foreach ($floors_norm_all as $fnv) {
                    $var_norm_all += ($fnv - $mean_norm_all) ** 2;
                }
                $cov_lag1_c7a /= ($n_floors - 1);
                $var_norm_all /= ($n_floors - 1);
                if ($var_norm_all > 1e-12) {
                    $r1_c7a = max(-1.0, min(1.0, $cov_lag1_c7a / $var_norm_all));
                    if ($r1_c7a > 0.0) {
                        $autocorr_lag1_c7a = $r1_c7a;
                        $se_corr_c7a       = $reg_norm['se'] * sqrt(1.0 + 2.0 * $r1_c7a);
                    }
                }
            }

            // mantener reg/slope brutos por si otras partes los usan
            $reg   = $this->_regressionStats($floors);
            $slope = $reg['slope'];

            // ── Tabla t_{df, 0.975} ──────────────────────────────────────────
            static $t_975_tab = [
                1 => 12.706,
                2 => 4.303,
                3 => 3.182,
                4 => 2.776,
                5 => 2.571,
                6 => 2.447,
                7 => 2.365,
                8 => 2.306,
                9 => 2.262,
                10 => 2.228,
                15 => 2.131,
                20 => 2.086,
                30 => 2.042,
                60 => 2.000,
                120 => 1.980,
            ];

            // ── Selección del conjunto de floors para el test IC95 ───────────
            if ($n_base >= $c7b_min_recepciones) {
                $test_floors = $floors_base;
                $test_dias   = $dias_base;
                $test_period = 'base';
            } elseif ($n_analysis >= $c7b_min_recepciones) {
                $test_floors = $floors_analysis;
                $test_dias   = $dias_analysis;
                $test_period = 'analysis';
            } else {
                $test_floors = null;
                $test_period = null;
            }

            // C7a — merma sistemática no registrada (suelos positivos en alza)
            // C7a-019: cascada de 5 niveles sobre floors normalizados.
            //
            // Pre-filtros globales:
            //   · mean_raw ≥ 0: suelos positivos en media (negativos → C7b)
            //   · delta_total ≥ umbral: variación acumulada mínima observable (configurable por tipo)
            //
            // NOTA: este bloque se ejecuta ANTES del bloque C7b para evitar que los
            // continue() internos de C7b (que descartan artículos no-C7b) salten
            // también C7a. C7a y C7b son mutuamente excluyentes por diseño:
            // C7a requiere mean_raw ≥ 0, C7b trabaja con floors negativos.
            // Nivel 5 (n_floors == 2): eliminado (C7a-020).
            // Niveles 1–4: requieren n_floors ≥ 3 y β_TS > 0.
            $delta_total = $floors[$n_floors - 1] - $floors[0];
            $c7a_umbral_delta = ($tipo_art === 'peso') ? $c7a_umbral_delta_peso : $c7a_umbral_delta_unidad;
            if (isset($subcasos_set['C7a']) && $mean_raw >= 0 && $delta_total >= $c7a_umbral_delta) {

                // ── Theil-Sen slope sobre floors_norm_all (C7a-012) ──────────
                // Estimador usado por la cascada estadística (test OLS, Bootstrap, MK).
                // Opera sobre floors normalizados (ud/día) para que la pendiente no se vea
                // afectada por la duración variable de los intervalos entre recepciones.
                // O(n²) — negligible para n típico 3-15.
                $ts_pairs = [];
                for ($i = 0; $i < $n_floors; $i++) {
                    for ($j = $i + 1; $j < $n_floors; $j++) {
                        $ts_pairs[] = ($floors_norm_all[$j] - $floors_norm_all[$i]) / ($j - $i);
                    }
                }
                sort($ts_pairs);
                $n_ts    = count($ts_pairs);
                $beta_ts = $n_ts > 0
                    ? ($n_ts % 2 === 1
                        ? $ts_pairs[intdiv($n_ts, 2)]
                        : ($ts_pairs[$n_ts / 2 - 1] + $ts_pairs[$n_ts / 2]) / 2.0)
                    : 0.0;

                // ── Theil-Sen sobre floors BRUTOS para el campo de visualización ──
                // Mide directamente la variación de suelo entre recepciones consecutivas
                // en las unidades originales (ud. o kg por paso de recepción).
                // NO se usa en la cascada estadística — solo para mostrar al usuario.
                $ts_pairs_raw = [];
                for ($i = 0; $i < $n_floors; $i++) {
                    for ($j = $i + 1; $j < $n_floors; $j++) {
                        $ts_pairs_raw[] = ($floors[$j] - $floors[$i]) / ($j - $i);
                    }
                }
                sort($ts_pairs_raw);
                $n_ts_raw         = count($ts_pairs_raw);
                $beta_ts_raw      = $n_ts_raw > 0
                    ? ($n_ts_raw % 2 === 1
                        ? $ts_pairs_raw[intdiv($n_ts_raw, 2)]
                        : ($ts_pairs_raw[$n_ts_raw / 2 - 1] + $ts_pairs_raw[$n_ts_raw / 2]) / 2.0)
                    : 0.0;
                $tendencia_visible = $beta_ts_raw;   // ud. o kg por recepción, sin conversión

                // dispersión bruta para el campo 'dispersion' del array de salida
                $variance_raw_c7a = 0.0;
                foreach ($floors as $f) {
                    $variance_raw_c7a += ($f - $mean_raw) ** 2;
                }
                $std_dev_raw_c7a = $n_floors > 1 ? sqrt($variance_raw_c7a / ($n_floors - 1)) : 0.0;

                $snr_c7a = ($std_dev_raw_c7a > 0.0)
                    ? abs($tendencia_visible) / $std_dev_raw_c7a
                    : PHP_FLOAT_MAX;

                // Nivel 5 eliminado (C7a-020): con n=2 floors positivos no hay test estadístico
                // válido porque el sobrestock es el estado normal del inventario. Dos floors
                // positivos en alza son compatibles con la variabilidad habitual del negocio.
                // La asimetría con C7b (donde n=2 negativos SÍ es evidencia) es intencional:
                // un floor negativo viola la hipótesis nula por definición; uno positivo no.
                if ($n_floors >= 3 && $beta_ts > 0.0) {
                    // Pre-filtro de dirección: β_TS > 0 (señal de alza)

                    $c7a_confianza        = null;
                    $c7a_cascade_nivel    = 0;
                    $p_mk_c7a             = null;
                    $c7a_fallback_reason  = '';
                    $cascade_nivel_act    = 1;

                    while ($cascade_nivel_act > 0 && $c7a_confianza === null) {

                        // ═══════════════════════════════════════════════════════
                        // NIVEL 1 · OLS + NEWEY-WEST IC95 (C7a-019a)
                        // Mayor potencia para floors aproximadamente normales con
                        // tendencia lineal (merma acumulada = δ constante/recepción).
                        // Gauss-Markov: BLUE bajo normalidad. NW corrige autocorr.
                        // y heterocedasticidad. CLT aplica para n ≥ 10.
                        // ═══════════════════════════════════════════════════════
                        if ($cascade_nivel_act === 1) {
                            if ($n_floors < 10 || $se_corr_c7a <= 0.0) {
                                // n < 10: pocos gl para NW; CLT no garantiza normalidad asintótica.
                                // se_corr = 0: OLS degenerado (datos colineales o constantes).
                                $c7a_fallback_reason = $n_floors < 10 ? 'n<10_skip_ols' : 'se_invalido_skip_ols';
                                $cascade_nivel_act = 2;
                            } else {
                                static $t_tab_nw = [
                                    1 => 6.314,
                                    2 => 2.920,
                                    3 => 2.353,
                                    4 => 2.132,
                                    5 => 2.015,
                                    6 => 1.943,
                                    7 => 1.895,
                                    8 => 1.860,
                                    9 => 1.833,
                                    10 => 1.812,
                                    15 => 1.753,
                                    20 => 1.725,
                                    30 => 1.697,
                                    60 => 1.671,
                                    120 => 1.658,
                                ];
                                $df_nw = max(1, $n_floors - 2);
                                if ($df_nw > 120) {
                                    $t_crit_nw = 1.645;
                                } elseif (isset($t_tab_nw[$df_nw])) {
                                    $t_crit_nw = $t_tab_nw[$df_nw];
                                } else {
                                    $keys_nw = array_keys($t_tab_nw);
                                    $lo_nw = $hi_nw = null;
                                    foreach ($keys_nw as $k) {
                                        if ($k <= $df_nw) $lo_nw = $k;
                                        if ($k >= $df_nw && $hi_nw === null) $hi_nw = $k;
                                    }
                                    $t_crit_nw = ($lo_nw !== null && $hi_nw !== null && $lo_nw !== $hi_nw)
                                        ? $t_tab_nw[$lo_nw] + ($df_nw - $lo_nw) / ($hi_nw - $lo_nw) * ($t_tab_nw[$hi_nw] - $t_tab_nw[$lo_nw])
                                        : ($lo_nw !== null ? $t_tab_nw[$lo_nw] : 6.314);
                                }
                                $ic95_low_nw = $slope_norm - $t_crit_nw * $se_corr_c7a;
                                if ($ic95_low_nw > 0.0) {
                                    $c7a_confianza = 'alta';
                                    $c7a_cascade_nivel = 1;
                                    $cascade_nivel_act = 0;
                                } else {
                                    // IC95 incluye 0 → Bootstrap (robusto a posible no-normalidad)
                                    if ($c7a_cascada_exhaustiva) {
                                        $c7a_fallback_reason = ($c7a_fallback_reason ? $c7a_fallback_reason . '; ' : '')
                                            . 'ols_nw_ns(ic95_low=' . round($ic95_low_nw, 3) . ')';
                                        $cascade_nivel_act = 2;
                                    } else {
                                        $cascade_nivel_act = 0;   // resultado válido negativo: NO C7a
                                    }
                                }
                            }

                            // ═══════════════════════════════════════════════════════
                            // NIVEL 2 · BOOTSTRAP THEIL-SEN IC99 (C7a-019b)
                            // No paramétrico; no asume distribución ni linealidad.
                            // Robusto a outliers. Requiere n ≥ 8 para IC estable.
                            // ═══════════════════════════════════════════════════════
                        } elseif ($cascade_nivel_act === 2) {
                            if ($n_floors < 8) {
                                $c7a_fallback_reason = ($c7a_fallback_reason ? $c7a_fallback_reason . '; ' : '')
                                    . 'n<8_skip_bootstrap';
                                $cascade_nivel_act = 3;
                            } else {
                                // Iteraciones adaptativas: reducir B para n grande ahorra tiempo
                                // sin perder precisión relevante (SE_boot ∝ 1/√B).
                                $B_boot     = $n_floors >= 30 ? 299 : ($n_floors >= 20 ? 499 : 999);
                                $boot_betas = [];
                                for ($b = 0; $b < $B_boot; $b++) {
                                    $idx_b = [];
                                    for ($k = 0; $k < $n_floors; $k++) {
                                        $idx_b[] = mt_rand(0, $n_floors - 1);
                                    }
                                    sort($idx_b);
                                    $f_b = [];
                                    foreach ($idx_b as $ki) {
                                        $f_b[] = $floors_norm_all[$ki];
                                    }
                                    $bp = [];
                                    $nb = count($f_b);
                                    for ($i = 0; $i < $nb; $i++) {
                                        for ($j = $i + 1; $j < $nb; $j++) {
                                            $bp[] = ($f_b[$j] - $f_b[$i]) / ($j - $i);
                                        }
                                    }
                                    if (!empty($bp)) {
                                        sort($bp);
                                        $np = count($bp);
                                        $boot_betas[] = $np % 2 === 1
                                            ? $bp[intdiv($np, 2)]
                                            : ($bp[$np / 2 - 1] + $bp[$np / 2]) / 2.0;
                                    }
                                }
                                if (!empty($boot_betas)) {
                                    sort($boot_betas);
                                    $nb_s     = count($boot_betas);
                                    $ic99_low = $boot_betas[(int)round(0.005 * ($nb_s - 1))];
                                    // detectar colapso de varianza (Tipo B)
                                    $mean_boot = array_sum($boot_betas) / $nb_s;
                                    $var_boot  = 0.0;
                                    foreach ($boot_betas as $bs) {
                                        $var_boot += ($bs - $mean_boot) ** 2;
                                    }
                                    $var_boot /= $nb_s;

                                    if ($var_boot < 1e-12) {
                                        $cascade_nivel_act = 3;   // Tipo B: varianza bootstrap colapsa
                                    } elseif ($ic99_low > 0.0) {
                                        $c7a_confianza = 'media';
                                        $c7a_cascade_nivel = 2;
                                        $cascade_nivel_act = 0;
                                    } else {
                                        if ($c7a_cascada_exhaustiva) {
                                            $c7a_fallback_reason = ($c7a_fallback_reason ? $c7a_fallback_reason . '; ' : '')
                                                . 'bootstrap_ic99_ns(ic99_low=' . round($ic99_low, 3) . ')';
                                            $cascade_nivel_act = 3;
                                        } else {
                                            $cascade_nivel_act = 0;   // resultado válido negativo: NO C7a
                                        }
                                    }
                                } else {
                                    $cascade_nivel_act = 3;       // sin slopes bootstrap → Tipo B
                                }
                            } // end else n >= 8

                            // ═══════════════════════════════════════════════════════
                            // NIVEL 3 · MANN-KENDALL con Hamed & Rao (C7a-019c)
                            // No paramétrico; corrige autocorrelación lag-1.
                            // Válido para n ∈ [4, 20]: fuera de ese rango MK pierde
                            // discriminación (n<4: p_mín=0.333; n>20: z_mk inflado O(n^1.5)).
                            // ═══════════════════════════════════════════════════════
                        } elseif ($cascade_nivel_act === 3) {
                            if ($n_floors < 4 || $n_floors > 20) {
                                $c7a_fallback_reason = ($c7a_fallback_reason ? $c7a_fallback_reason . '; ' : '')
                                    . ($n_floors < 4 ? 'n<4_skip_mk' : 'n>20_mk_overpowered');
                                $cascade_nivel_act = 4;
                            } else {
                                $S_mk = 0;
                                for ($i = 0; $i < $n_floors; $i++) {
                                    for ($j = $i + 1; $j < $n_floors; $j++) {
                                        $d = $floors_norm_all[$j] - $floors_norm_all[$i];
                                        if ($d > 0.0) $S_mk++;
                                        elseif ($d < 0.0) $S_mk--;
                                    }
                                }
                                // Var_MK estándar (sin empates)
                                $var_mk = (float)$n_floors * ($n_floors - 1) * (2 * $n_floors + 5) / 18.0;
                                // Corrección Hamed & Rao lag-1: Var_HR = Var_MK × (1 + 2r₁)
                                if ($autocorr_lag1_c7a > 0.0) {
                                    $var_mk *= (1.0 + 2.0 * $autocorr_lag1_c7a);
                                }
                                if ($var_mk > 0.0) {
                                    $S_adj = $S_mk > 0 ? $S_mk - 1 : ($S_mk < 0 ? $S_mk + 1 : 0);
                                    $z_mk  = $S_adj / sqrt($var_mk);
                                    // Φ(z) — Abramowitz & Stegun 26.2.17
                                    $az     = abs($z_mk);
                                    $t_phi  = 1.0 / (1.0 + 0.2316419 * $az);
                                    $p_tail = 0.3989423 * exp(-$az * $az / 2.0)
                                        * $t_phi * (0.3193815 + $t_phi * (-0.3565638
                                            + $t_phi * (1.7814779 + $t_phi * (-1.8212560
                                                + $t_phi * 1.3302744))));
                                    $p_mk_c7a = min(1.0, max(0.0, 2.0 * $p_tail));
                                } else {
                                    $p_mk_c7a = 1.0;
                                }
                                if ($p_mk_c7a < $c7a_umbral_pvalue_alta) {
                                    $c7a_confianza = 'posible';
                                    $c7a_cascade_nivel = 3;
                                    $cascade_nivel_act = 0;
                                } elseif ($p_mk_c7a >= $c7a_umbral_pvalue) {
                                    if ($c7a_cascada_exhaustiva) {
                                        $c7a_fallback_reason = ($c7a_fallback_reason ? $c7a_fallback_reason . '; ' : '')
                                            . 'mk_ns(p=' . round($p_mk_c7a, 3) . ')';
                                        $cascade_nivel_act = 4;
                                    } else {
                                        $cascade_nivel_act = 0;   // resultado válido negativo: NO C7a
                                    }
                                } else {
                                    $cascade_nivel_act = 4;   // Tipo B: p ∈ [pvalue_alta, pvalue)
                                }
                            } // end else n ∈ [4, 20]

                            // ═══════════════════════════════════════════════════════
                            // NIVEL 4 · TEST DE SIGNO BINOMIAL (C7a-019d)
                            // No paramétrico; prácticamente sin suposiciones distribucionales.
                            // Fallback final: baja potencia estadística → confianza='posible'.
                            // ═══════════════════════════════════════════════════════
                        } elseif ($cascade_nivel_act === 4) {
                            $n_diffs = $n_floors - 1;
                            if ($n_diffs > 0) {
                                $k_pos = 0;
                                for ($i = 0; $i < $n_diffs; $i++) {
                                    if ($floors_norm_all[$i + 1] > $floors_norm_all[$i]) $k_pos++;
                                }
                                // P(Bin(n_diffs, 0.5) ≥ k_pos) exacto
                                $p_signo  = 0.0;
                                $bcoef    = 1.0;
                                $pow_half = pow(0.5, $n_diffs);
                                for ($k = 0; $k <= $n_diffs; $k++) {
                                    if ($k > 0) $bcoef *= ($n_diffs - $k + 1) / $k;
                                    if ($k >= $k_pos) $p_signo += $bcoef * $pow_half;
                                }
                                $p_signo = min(1.0, $p_signo);
                                if ($p_signo < 0.05) {
                                    $c7a_confianza = 'posible';
                                    $c7a_cascade_nivel = 4;
                                }
                            }
                            $cascade_nivel_act = 0;   // nivel 4 siempre es terminal

                        } else {
                            $cascade_nivel_act = 0;
                        }
                    } // while cascade

                    // ── Emitir incidencia si la cascada confirmó C7a ──────────
                    if ($c7a_confianza !== null) {
                        // c7_subcaso: C7a_posible para niveles ≥ 3 (no alimenta C7c/C7d/C7e)
                        $subcaso_c7a = ($c7a_confianza === 'posible') ? 'C7a_posible' : 'C7a';

                        // ── C7a-015: cobertura temporal (base vs análisis) ────
                        // Theil-Sen sobre cada subperiodo para determinar si la tendencia
                        // es histórica (solo en base), nueva (solo en análisis) o persistente.
                        $beta_ts_base     = null;
                        $beta_ts_analysis = null;
                        $test_period      = null;

                        $fn_ts_median = function (array $vals): ?float {
                            if (empty($vals)) return null;
                            sort($vals);
                            $n = count($vals);
                            return $n % 2 === 1
                                ? $vals[intdiv($n, 2)]
                                : ($vals[$n / 2 - 1] + $vals[$n / 2]) / 2.0;
                        };

                        if ($n_base >= 2) {
                            $fn_base = [];
                            for ($i = 0; $i < $n_base; $i++) {
                                $fn_base[] = $floors_base[$i] / max(1.0, $dias_base[$i]);
                            }
                            $tsp_b = [];
                            for ($i = 0; $i < $n_base; $i++) {
                                for ($j = $i + 1; $j < $n_base; $j++) {
                                    $tsp_b[] = ($fn_base[$j] - $fn_base[$i]) / ($j - $i);
                                }
                            }
                            $beta_ts_base = $fn_ts_median($tsp_b);
                        }

                        if ($n_analysis >= 2) {
                            $fn_ana = [];
                            for ($i = 0; $i < $n_analysis; $i++) {
                                $fn_ana[] = $floors_analysis[$i] / max(1.0, $dias_analysis[$i]);
                            }
                            $tsp_a = [];
                            for ($i = 0; $i < $n_analysis; $i++) {
                                for ($j = $i + 1; $j < $n_analysis; $j++) {
                                    $tsp_a[] = ($fn_ana[$j] - $fn_ana[$i]) / ($j - $i);
                                }
                            }
                            $beta_ts_analysis = $fn_ts_median($tsp_a);
                        }

                        $base_up     = $beta_ts_base     !== null ? $beta_ts_base     > 0.0 : null;
                        $analysis_up = $beta_ts_analysis !== null ? $beta_ts_analysis > 0.0 : null;
                        if ($base_up !== null && $analysis_up !== null) {
                            if ($base_up  && $analysis_up)  $test_period = 'both';
                            elseif ($base_up  && !$analysis_up) $test_period = 'base';
                            elseif (!$base_up && $analysis_up)  $test_period = 'analysis';
                            // ninguno positivo → null (tendencia global sin subperiodo confirmado)
                        } elseif ($base_up !== null) {
                            $test_period = $base_up ? 'base' : null;
                        } elseif ($analysis_up !== null) {
                            $test_period = $analysis_up ? 'analysis' : null;
                        }

                        // ── C7a-021: analysis_consistent — contrasta la tendencia con el periodo base ──
                        $analysis_consistent_c7a = ($beta_ts_base !== null) ? ($beta_ts_base > 0.0) : null;

                        // ── Severidad: tabla 2D confianza × test_period + modulador magnitud ──
                        // Columna null/desconocida → 'analysis' (conservador: sin historial confirmado).
                        //
                        // confianza    | both  | analysis | base  | null(→analysis)
                        // 'alta'       | ALTA  | ALTA     | MEDIA | ALTA
                        // 'media'      | ALTA  | MEDIA    | MEDIA | MEDIA
                        // 'posible'    | MEDIA | MEDIA    | BAJA  | MEDIA
                        // C7a_posible  | BAJA  | BAJA     | BAJA  | BAJA  (siempre)
                        static $sev_tab_c7a = [
                            'alta'    => ['both' => 'ALTA',  'analysis' => 'ALTA',  'base' => 'MEDIA'],
                            'media'   => ['both' => 'ALTA',  'analysis' => 'MEDIA', 'base' => 'MEDIA'],
                            'posible' => ['both' => 'MEDIA', 'analysis' => 'MEDIA', 'base' => 'BAJA'],
                        ];
                        static $sev_num_c7a  = ['ALTA' => 2, 'MEDIA' => 1, 'BAJA' => 0];
                        static $sev_name_c7a = [2 => 'ALTA', 1 => 'MEDIA', 0 => 'BAJA'];

                        $c7a_umbral_alta_delta = ($tipo_art === 'peso') ? $c7a_umbral_alta_delta_peso : $c7a_umbral_alta_delta_unidad;
                        $c7a_umbral_alta_slope = ($tipo_art === 'peso') ? $c7a_umbral_alta_slope_peso : $c7a_umbral_alta_slope_unidad;

                        if ($subcaso_c7a === 'C7a_posible') {
                            $severidad = 'BAJA';
                        } else {
                            $col = $test_period ?? 'analysis'; // null → 'analysis' (sin historial confirmado)
                            $sev_base_c7a  = $sev_tab_c7a[$c7a_confianza][$col];
                            $sev_nivel_c7a = $sev_num_c7a[$sev_base_c7a];
                            // Modulador magnitud: si delta y slope están ambos bajo el umbral ALTA, bajar un nivel
                            if ($delta_total < $c7a_umbral_alta_delta && $tendencia_visible < $c7a_umbral_alta_slope) {
                                $sev_nivel_c7a--;
                            }
                            $severidad = $sev_name_c7a[max(0, $sev_nivel_c7a)];
                        }

                        // Cobertura: etiqueta legible para la vista
                        $cobertura_c7a = $test_period ?? 'analysis';

                        // ── C7a-010: tendencia_reciente — estado actual de la merma ────
                        // Análogo a C7b: 'activo', 'resuelto', 'mejorando', 'sin_datos'
                        if ($test_period === 'base') {
                            // La tendencia existe en el histórico; ¿continúa en el análisis?
                            if ($analysis_consistent_c7a === true) {
                                $tendencia_reciente_c7a = 'activo';    // pendiente positiva también en análisis
                            } elseif ($n_analysis === 0) {
                                $tendencia_reciente_c7a = 'sin_datos'; // sin recepciones en el análisis
                            } elseif ($beta_ts_analysis !== null && $beta_ts_analysis < 0.0) {
                                $tendencia_reciente_c7a = 'resuelto';  // pendiente negativa en análisis
                            } else {
                                $tendencia_reciente_c7a = 'mejorando'; // positiva pero no confirma C7a-015
                            }
                        } else {
                            // 'analysis' o 'both' o null → la merma está activa en el período reciente
                            $tendencia_reciente_c7a = 'activo';
                        }

                        // ── C7a-024/025: discriminar merma vs. compra con stock ──────────
                        // ratio_i = floor[i-1] / cantidad_recepción[i]: alto sistemáticamente → compra con stock
                        // Spearman(floor[i-1], cantidad[i]): negativo → comprador ajusta pedidos al stock
                        $tiene_albcli_c7a  = isset($has_albcli_ids[$id]);
                        $compra_con_stock  = false;
                        $comprador_ajusta  = false;
                        $r_spearman_c7a    = null;
                        $ratio_mediano_c7a = null;

                        // Cantidades por fecha de recepción (de $cantidades_map construido junto con $recepciones_map)
                        $cantidades_rec = [];
                        foreach ($fechas_floors as $fd) {
                            $cantidades_rec[] = $cantidades_map[$id][$fd] ?? 0.0;
                        }

                        if ($n_floors >= 3) {
                            $x_floors_prev = [];   // floor[i-1]
                            $y_quant       = [];   // cantidad[i]
                            $ratios_cob    = [];   // floor[i-1] / cantidad[i]
                            for ($i = 1; $i < $n_floors; $i++) {
                                $qty = $cantidades_rec[$i];
                                if ($qty > 0.0 && $floors[$i - 1] >= 0.0) {
                                    $x_floors_prev[] = $floors[$i - 1];
                                    $y_quant[]       = $qty;
                                    $ratios_cob[]    = $floors[$i - 1] / $qty;
                                }
                            }
                            $n_pares = count($ratios_cob);

                            if ($n_pares >= 2) {
                                // Mediana de ratios
                                $ratios_sorted     = $ratios_cob;
                                sort($ratios_sorted);
                                $ratio_mediano_c7a = $n_pares % 2 === 1
                                    ? $ratios_sorted[intdiv($n_pares, 2)]
                                    : ($ratios_sorted[$n_pares / 2 - 1] + $ratios_sorted[$n_pares / 2]) / 2.0;

                                // Test binomial one-sided: ¿mayoría de ratios > 0.3?
                                $umbral_ratio = 0.3;
                                $k_alto       = 0;
                                foreach ($ratios_cob as $rv) {
                                    if ($rv > $umbral_ratio) $k_alto++;
                                }
                                $p_ratio  = 0.0;
                                $bcoef    = 1.0;
                                $phalf    = pow(0.5, $n_pares);
                                for ($k = 0; $k <= $n_pares; $k++) {
                                    if ($k > 0) $bcoef *= ($n_pares - $k + 1) / $k;
                                    if ($k >= $k_alto) $p_ratio += $bcoef * $phalf;
                                }
                                $compra_con_stock = (min(1.0, $p_ratio) < 0.10);

                                // Spearman: ¿comprador reduce pedidos cuando el stock es alto?
                                if ($n_pares >= 3) {
                                    $fn_rank_c7a = static function (array $arr): array {
                                        $n   = count($arr);
                                        $idx = range(0, $n - 1);
                                        usort($idx, static fn($a, $b) => $arr[$a] <=> $arr[$b]);
                                        $ranks = array_fill(0, $n, 0.0);
                                        for ($i = 0; $i < $n;) {
                                            $j = $i;
                                            while ($j < $n && $arr[$idx[$j]] === $arr[$idx[$i]]) $j++;
                                            $avg = ($i + $j - 1) / 2.0 + 1.0;
                                            for ($k = $i; $k < $j; $k++) $ranks[$idx[$k]] = $avg;
                                            $i = $j;
                                        }
                                        return $ranks;
                                    };
                                    $rx       = $fn_rank_c7a($x_floors_prev);
                                    $ry       = $fn_rank_c7a($y_quant);
                                    $n_sp     = $n_pares;
                                    $mean_rx  = array_sum($rx) / $n_sp;
                                    $mean_ry  = array_sum($ry) / $n_sp;
                                    $cov_sp   = $var_rx_sp = $var_ry_sp = 0.0;
                                    for ($i = 0; $i < $n_sp; $i++) {
                                        $cov_sp    += ($rx[$i] - $mean_rx) * ($ry[$i] - $mean_ry);
                                        $var_rx_sp += ($rx[$i] - $mean_rx) ** 2;
                                        $var_ry_sp += ($ry[$i] - $mean_ry) ** 2;
                                    }
                                    $denom_sp       = sqrt($var_rx_sp * $var_ry_sp);
                                    $r_spearman_c7a = $denom_sp > 0.0 ? $cov_sp / $denom_sp : 0.0;
                                    $comprador_ajusta = ($r_spearman_c7a < -0.3);
                                }
                            }
                        }

                        // ── C7a-010: posible_causa accionable según estado ───────────
                        $unidad_c7a    = ($tipo_art === 'peso') ? 'kg' : 'ud.';
                        $slope_fmt_c7a = number_format(abs($tendencia_visible), 1, '.', '');
                        $delta_fmt_c7a = number_format(abs($delta_total),       1, '.', '');
                        $prefijo_c7a   = sprintf(
                            'El suelo mínimo sube ~%s %s por recepción (%s %s acumulados). ',
                            $slope_fmt_c7a,
                            $unidad_c7a,
                            $delta_fmt_c7a,
                            $unidad_c7a
                        );
                        switch ($tendencia_reciente_c7a) {
                            case 'resuelto':
                                $posible_causa_c7a = $prefijo_c7a . 'Tendencia histórica no confirmada en el período reciente. Verificar si se realizó un ajuste de inventario o corrección de merma.';
                                break;
                            case 'mejorando':
                                $posible_causa_c7a = $prefijo_c7a . 'La tendencia puede estar reduciéndose — pendiente reciente sin confirmación estadística. Monitorizar en el próximo informe.';
                                break;
                            case 'sin_datos':
                                $posible_causa_c7a = $prefijo_c7a . 'Sin recepciones en el período de análisis — no es posible confirmar si la merma sigue activa. Revisar entradas pendientes.';
                                break;
                            default: // 'activo'
                                if ($comprador_ajusta) {
                                    $posible_causa_c7a = $prefijo_c7a . 'El comprador reduce los pedidos cuando el stock es alto — menos probable merma sistemática. Posible política de buffer o entrega en calendario fijo.';
                                } elseif ($compra_con_stock) {
                                    $posible_causa_c7a = $prefijo_c7a . 'Stock sistemáticamente alto antes de cada recepción — posible compra anticipada o entrega en calendario fijo. Verificar si existe merma real no registrada.';
                                } else {
                                    $posible_causa_c7a = $prefijo_c7a . 'Posible merma, caducidad no registrada o salida sin documentar.';
                                }
                        }
                        // ── C7a-025: aviso condicional si hay albaranes de cliente ──────
                        if ($tiene_albcli_c7a) {
                            $posible_causa_c7a .= ' · Hay albaranes de cliente en el período: verificar consumo interno o albaranes pendientes.';
                        }

                        $incidencias[] = [
                            'idArticulo'       => $id,
                            'tipo'             => 'Merma acumulada',
                            'severidad'        => $severidad,
                            'c7_subcaso'       => $subcaso_c7a,
                            'confianza'        => $c7a_confianza,
                            'cascade_nivel'    => $c7a_cascade_nivel,
                            'n_recepciones'    => $n_rec,
                            'offset_estimado'  => round($mean_raw, 1),
                            'dispersion'       => round($std_dev_raw_c7a, 1),
                            'tendencia'        => round($tendencia_visible, 1),
                            'tendencia_norm'   => round($beta_ts, 4),
                            'autocorr_lag1'    => round($autocorr_lag1_c7a, 2),
                            'p_mk'             => $p_mk_c7a !== null ? round($p_mk_c7a, 4) : null,
                            'tau_mk'           => isset($tau_mk) ? round($tau_mk, 3) : null,
                            'snr'                  => round($snr_c7a, 3),
                            'cascade_fallback_reason' => $c7a_fallback_reason ?: null,
                            'test_period'         => $test_period,
                            'tendencia_reciente'  => $tendencia_reciente_c7a,
                            'analysis_consistent' => $analysis_consistent_c7a,
                            'cobertura'           => $cobertura_c7a,
                            'n_base'              => $n_base,
                            'n_analysis'          => $n_analysis,
                            'slope_base'       => $beta_ts_base     !== null ? round($beta_ts_base     * $dias_intervalo_medio_all, 1) : null,
                            'slope_analysis'   => $beta_ts_analysis !== null ? round($beta_ts_analysis * $dias_intervalo_medio_all, 1) : null,
                            'delta_acumulado'  => round($delta_total, 1),
                            'fecha_primera'    => $fechas_rec[0],
                            'fecha_ultima'     => $fechas_rec[$n_rec - 1],
                            '_floors_raw'      => $floors_map,
                            'posible_causa'    => $posible_causa_c7a,
                            'tiene_albcli'     => $tiene_albcli_c7a,
                            'r_spearman'       => $r_spearman_c7a !== null ? round($r_spearman_c7a, 3) : null,
                            'ratio_mediano'    => $ratio_mediano_c7a !== null ? round($ratio_mediano_c7a, 3) : null,
                        ];
                    }
                } // n_floors >= 3 && beta_ts > 0
            } // isset C7a && mean_raw >= 0 && delta_total >= 2

            if ($test_floors !== null && isset($subcasos_set['C7b'])) {
                // ── Estadísticos sobre el conjunto de test ───────────────────
                $n = count($test_floors);

                // C7b-002: normalizar floors por duración del intervalo (déficit/día).
                $floors_norm = [];
                for ($i = 0; $i < $n; $i++) {
                    $floors_norm[] = $test_floors[$i] / $test_dias[$i];
                }
                $mean = array_sum($floors_norm) / $n;

                $variance = 0.0;
                foreach ($floors_norm as $fnv) {
                    $variance += ($fnv - $mean) ** 2;
                }
                $std_dev = $n > 1 ? sqrt($variance / ($n - 1)) : 0.0;
                $cv      = $mean != 0.0 ? $std_dev / abs($mean) : PHP_FLOAT_MAX;

                $dias_intervalo_medio = (int)round(array_sum($test_dias) / $n);

                // C7b-025: heterogeneidad de duraciones
                $mean_dias  = array_sum($test_dias) / $n;
                $var_dias   = 0.0;
                foreach ($test_dias as $d) {
                    $var_dias += ($d - $mean_dias) ** 2;
                }
                $std_dias   = $n > 1 ? sqrt($var_dias / ($n - 1)) : 0.0;
                $cv_dias    = $mean_dias > 0.0 ? $std_dias / $mean_dias : 0.0;

                // C7b-024: guard varianza nula — IC95 colapsa con "certeza perfecta" falsa.
                // Nivel 5 de la cascada (C7b-026): determinista constante.
                // Umbral 1e-9 en lugar de == 0.0 para capturar near-zero por ruido float
                // (floors casi idénticos que muestran ±0.0 pero no son exactamente iguales).
                if ($std_dev < 1e-9) {
                    if ($mean < 0.0) {
                        $incidencias[] = [
                            'idArticulo'               => $id,
                            'tipo'                     => 'Entrada no registrada',
                            'severidad'                => 'BAJA',
                            'c7_subcaso'               => 'C7b',
                            'confianza'                => 'posible',
                            'test_period'              => $test_period,
                            'analysis_consistent'      => false,
                            'tipo_articulo'            => $tipo_art,
                            'n_recepciones'            => $n_rec,
                            'offset_estimado'          => round($mean_raw, 1),
                            'offset_norm'              => round($mean, 3),
                            'dispersion'               => 0.0,
                            'autocorr_lag1'            => 0.0,
                            'ic95_upper'               => round($mean, 4),
                            'n_intervalos_negativos'   => $n,
                            'pct_intervalos_negativos' => 100,
                            'dias_intervalo_medio'     => $dias_intervalo_medio,
                            'fecha_primera'            => $fechas_rec[0],
                            'fecha_ultima'             => $fechas_rec[$n_rec - 1],
                            '_floors_raw'              => $floors_map,
                            'test_type'                => 'determinista_constante',
                            'posible_causa'            => sprintf(
                                'El stock cae siempre exactamente %d %s entre recepciones (varianza cero). Patrón constante, pero con pocos datos para test estadístico.',
                                (int)round($abs_mean_raw),
                                $tipo_art === 'peso' ? 'kg' : 'ud.'
                            ),
                        ];
                    }
                    continue;
                }

                // ── Filtros de estabilidad — condición previa a todos los niveles (C7b-009) ──
                // Artículos de peso usan umbrales más tolerantes: la variabilidad de pesaje
                // y la irregularidad en cantidad por entrega elevan CV e IQR de forma legítima.
                $umbral_cv_efectivo  = ($tipo_art === 'peso') ? $c7b_umbral_cv_peso : $c7b_umbral_cv;
                $umbral_iqr_mult     = ($tipo_art === 'peso') ? $c7b_umbral_iqr_peso : 1.5;
                $sf = $floors_norm;
                sort($sf);
                $q1_idx = (int)floor(($n - 1) * 0.25);
                $q3_idx = (int)ceil(($n - 1) * 0.75);
                $iqr    = $sf[$q3_idx] - $sf[$q1_idx];
                if ($n === 3) {
                    $iqr *= 0.7;
                }
                if (!($cv < $umbral_cv_efectivo && $iqr < $umbral_iqr_mult * abs($mean))) {
                    continue;
                }

                // ── Estado de la cascada ─────────────────────────────────────
                $test_type            = null;
                $test_pvalue          = null;
                $test_fallback_reason = null;
                $c7b_confirmed        = false;
                $confianza_c7b        = 'posible';
                $ic95_upper           = null;
                $r1                   = 0.0;

                // ═══════════════════════════════════════════════════════════
                // NIVEL 1 · WILCOXON SIGNED-RANK (C7b-020)
                // Más potente para distribuciones asimétricas (Gumbel).
                // confianza si confirma: 'alta'
                // ═══════════════════════════════════════════════════════════
                $ratio_distinct  = count(array_unique($floors_norm)) / $n;
                $wilcoxon_tipo_a = ($n < 4 || $ratio_distinct < 0.75);
                if (!$wilcoxon_tipo_a) {
                    // Valores no-cero con sus signos
                    $nz_vals = [];
                    $nz_signs = [];
                    foreach ($floors_norm as $fv) {
                        if ($fv != 0.0) {
                            $nz_vals[] = abs($fv);
                            $nz_signs[] = ($fv < 0 ? -1 : 1);
                        }
                    }
                    $n_w = count($nz_vals);
                    if ($n_w >= 4) {
                        // Rangos con corrección de empates (rango promedio)
                        $order = range(0, $n_w - 1);
                        usort($order, fn($a, $b) => $nz_vals[$a] <=> $nz_vals[$b]);
                        $rnks = array_fill(0, $n_w, 0.0);
                        $i = 0;
                        while ($i < $n_w) {
                            $j = $i;
                            while ($j + 1 < $n_w && $nz_vals[$order[$j + 1]] == $nz_vals[$order[$i]]) {
                                $j++;
                            }
                            $avg_rank = ($i + $j + 2) / 2.0;
                            for ($k2 = $i; $k2 <= $j; $k2++) {
                                $rnks[$order[$k2]] = $avg_rank;
                            }
                            $i = $j + 1;
                        }
                        $n_ties_ranks = $n_w - count(array_unique($rnks));
                        if ($n_ties_ranks / $n_w > 0.5) {
                            // Tipo B: demasiados empates en rangos — resultado degradado
                            $test_fallback_reason = 'wilcoxon_tipo_b_empates';
                        } else {
                            // T+ = suma de rangos de valores positivos (H1: mediana < 0 → T+ pequeño)
                            $t_plus = 0.0;
                            for ($i = 0; $i < $n_w; $i++) {
                                if ($nz_signs[$i] > 0) {
                                    $t_plus += $rnks[$i];
                                }
                            }
                            // Aproximación normal con corrección de continuidad (Abramowitz & Stegun 7.1.26)
                            $mu_w  = $n_w * ($n_w + 1) / 4.0;
                            $var_w = $n_w * ($n_w + 1) * (2 * $n_w + 1) / 24.0;
                            $z_w   = ($t_plus + 0.5 - $mu_w) / sqrt($var_w);
                            $az    = abs($z_w);
                            $t_as  = 1.0 / (1.0 + 0.2316419 * $az);
                            $poly  = $t_as * (0.319381530 + $t_as * (-0.356563782 + $t_as * (1.781477937 + $t_as * (-1.821255978 + $t_as * 1.330274429))));
                            $ncdf  = 1.0 - exp(-$az * $az / 2.0) / sqrt(2.0 * M_PI) * $poly;
                            $p_wilcoxon  = $z_w < 0 ? 1.0 - $ncdf : $ncdf;
                            $test_type   = 'wilcoxon_signed_rank';
                            $test_pvalue = round($p_wilcoxon, 4);
                            if ($p_wilcoxon < 0.05) {
                                $c7b_confirmed = true;
                                $confianza_c7b = 'alta';
                            } elseif (!$c7b_cascada_exhaustiva) {
                                continue; // resultado válido: no C7b
                            } else {
                                $test_fallback_reason = ($test_fallback_reason ? $test_fallback_reason . '; ' : '')
                                    . 'wilcoxon_p=' . round($p_wilcoxon, 3) . '_ns';
                                $test_type = null;
                                $test_pvalue = null;
                            }
                        }
                    } else {
                        $wilcoxon_tipo_a = true; // n_w < 4 tras excluir ceros
                    }
                }
                if ($wilcoxon_tipo_a && $test_fallback_reason === null) {
                    $rs = [];
                    if ($n < 4)                 $rs[] = 'n<4';
                    if ($ratio_distinct < 0.75) $rs[] = 'ratio_distinct<0.75';
                    $test_fallback_reason = 'wilcoxon_tipo_a:' . implode(',', $rs);
                }

                // ═══════════════════════════════════════════════════════════
                // NIVEL 2 · BOOTSTRAP PERCENTILE (C7b-023)
                // Sin supuesto distribucional; especialmente útil para n=3.
                // confianza si confirma: 'media'
                // ═══════════════════════════════════════════════════════════
                if (!$c7b_confirmed) {
                    $n_distinct_boot = count(array_unique($floors_norm));
                    if ($std_dev > 0.0 && $n_distinct_boot > 1) {
                        $mu_boot = [];
                        for ($b = 0; $b < 999; $b++) {
                            $s = 0.0;
                            for ($j = 0; $j < $n; $j++) {
                                $s += $floors_norm[random_int(0, $n - 1)];
                            }
                            $mu_boot[] = $s / $n;
                        }
                        sort($mu_boot);
                        $ic95_sup_boot = $mu_boot[(int)(999 * 0.975)]; // índice 974 → percentil 97.5
                        $test_type = 'bootstrap';
                        if ($ic95_sup_boot < 0.0) {
                            $c7b_confirmed = true;
                            $confianza_c7b = 'media';
                        } elseif (!$c7b_cascada_exhaustiva) {
                            continue; // resultado válido: no C7b
                        } else {
                            $test_fallback_reason = ($test_fallback_reason ? $test_fallback_reason . '; ' : '')
                                . 'bootstrap_ic95_sup=' . round($ic95_sup_boot, 3) . '_ns';
                            $test_type = null;
                        }
                    } else {
                        $reason_boot = ($n_distinct_boot == 1) ? 'bootstrap_tipo_b_n_distinct_1' : 'bootstrap_tipo_a_std0';
                        $test_fallback_reason = ($test_fallback_reason ? $test_fallback_reason . '; ' : '') . $reason_boot;
                    }
                }

                // ═══════════════════════════════════════════════════════════
                // NIVEL 3 · TEST DE SIGNO BINOMIAL (C7b-021)
                // Válido para cualquier distribución, continua o discreta.
                // confianza si confirma: 'media' — test exacto sin supuestos distribucionales;
                // menor potencia que Wilcoxon pero p<0.05 tiene las mismas garantías cuando alcanza significancia.
                // ═══════════════════════════════════════════════════════════
                if (!$c7b_confirmed) {
                    $floors_eff = array_values(array_filter($floors_norm, fn($f) => $f != 0.0));
                    $n_eff      = count($floors_eff);
                    if ($n_eff >= 3) {
                        $b_minus = count(array_filter($floors_eff, fn($f) => $f < 0.0));
                        // p = P(Binom(n_eff, 0.5) >= b_minus) — cola superior exacta
                        $p_sign = 0.0;
                        $coeff = 1.0;
                        for ($k = 0; $k <= $n_eff; $k++) {
                            if ($k >= $b_minus) {
                                $p_sign += $coeff;
                            }
                            if ($k < $n_eff) {
                                $coeff *= ($n_eff - $k) / ($k + 1.0);
                            }
                        }
                        $p_sign /= pow(2.0, $n_eff);
                        $test_type   = 'sign_binomial';
                        $test_pvalue = round($p_sign, 4);
                        if ($p_sign < 0.05) {
                            $c7b_confirmed = true;
                            $confianza_c7b = 'media';
                        } elseif (!$c7b_cascada_exhaustiva) {
                            continue; // resultado válido: no C7b
                        } else {
                            $test_fallback_reason = ($test_fallback_reason ? $test_fallback_reason . '; ' : '')
                                . 'sign_p=' . round($p_sign, 3) . '_ns';
                            $test_type = null;
                            $test_pvalue = null;
                        }
                    } else {
                        $test_fallback_reason = ($test_fallback_reason ? $test_fallback_reason . '; ' : '') . 'sign_tipo_a_n_efectivo<3';
                    }
                }

                // ═══════════════════════════════════════════════════════════
                // NIVEL 4 · T-TEST + NEWEY-WEST (C7b-004, implementación original)
                // Último recurso paramétrico; asume normalidad (violada — Gumbel).
                // confianza si confirma: 'posible'
                // ═══════════════════════════════════════════════════════════
                if (!$c7b_confirmed) {
                    // std_dev > 0 garantizado (guard C7b-024 ya salió antes de este bloque)
                    $df_ic = $n - 1;
                    if ($df_ic > 120) {
                        $t_975 = 1.960;
                    } elseif (isset($t_975_tab[$df_ic])) {
                        $t_975 = $t_975_tab[$df_ic];
                    } else {
                        $keys_ic = array_keys($t_975_tab);
                        $lo_ic = $hi_ic = null;
                        foreach ($keys_ic as $k) {
                            if ($k <= $df_ic) $lo_ic = $k;
                            if ($k >= $df_ic && $hi_ic === null) $hi_ic = $k;
                        }
                        $t_975 = ($lo_ic !== null && $hi_ic !== null && $lo_ic !== $hi_ic)
                            ? $t_975_tab[$lo_ic] + ($df_ic - $lo_ic) / ($hi_ic - $lo_ic) * ($t_975_tab[$hi_ic] - $t_975_tab[$lo_ic])
                            : ($lo_ic !== null ? $t_975_tab[$lo_ic] : 1.960);
                    }
                    $se_base    = $std_dev / sqrt($n);
                    $ic95_upper = $mean + $t_975 * $se_base;
                    // C7b-004: corrección Newey-West para autocorrelación lag-1
                    if ($n >= 4) {
                        $cov_lag1 = 0.0;
                        for ($i = 1; $i < $n; $i++) {
                            $cov_lag1 += ($floors_norm[$i] - $mean) * ($floors_norm[$i - 1] - $mean);
                        }
                        $cov_lag1 /= ($n - 1);
                        $r1 = max(-1.0, min(1.0, $cov_lag1 / ($std_dev ** 2)));
                        if ($r1 > 0.0) {
                            $ic95_upper = $mean + $t_975 * $se_base * sqrt(1.0 + 2.0 * $r1);
                        }
                    }
                    $test_type = 't_student';
                    if ($ic95_upper < 0.0) {
                        $c7b_confirmed = true;
                        $confianza_c7b = 'posible';
                    } else {
                        continue;
                    } // resultado válido: no C7b
                }

                if (!$c7b_confirmed) {
                    continue;
                }

                // ── C7b-025: duraciones irregulares degradan confianza un nivel ──
                if ($cv_dias > 1.0) {
                    $confianza_c7b = ($confianza_c7b === 'alta') ? 'media' : 'posible';
                }

                // ── C7b-005: filtro ruido pesaje ──────────────────────────────
                if ($tipo_art === 'peso' && $abs_mean_raw < $c7b_umbral_ruido_peso) {
                    $incidencias[] = [
                        'idArticulo'           => $id,
                        'tipo'                 => 'Posible error de pesaje',
                        'severidad'            => 'BAJA',
                        'c7_subcaso'           => 'C7b_ruido_peso',
                        'tipo_articulo'        => $tipo_art,
                        'n_recepciones'        => $n_rec,
                        'offset_estimado'      => round($mean_raw, 2),
                        'dias_intervalo_medio' => $dias_intervalo_medio,
                        'fecha_primera'        => $fechas_rec[0],
                        'fecha_ultima'         => $fechas_rec[$n_rec - 1],
                        '_floors_raw'          => $floors_map,
                        'posible_causa'        => sprintf(
                            'El stock aparece %.2f kg en negativo, pero es demasiado pequeño para ser un error real — probablemente es acumulación de decimales de balanza.',
                            $abs_mean_raw
                        ),
                    ];
                    continue;
                }

                // ── C7b-006: cobertura del déficit en todos los floors del periodo ─
                $n_neg   = count(array_filter($floors, fn($f) => $f < 0.0));
                $pct_neg = (int)round($n_neg / $n_floors * 100);

                // ── Cobertura temporal + tendencia reciente (C7b-022) ────────
                if ($test_period === 'base') {
                    $n_analysis_neg      = count(array_filter($floors_analysis, fn($f) => $f < 0.0));
                    $analysis_consistent = $n_analysis >= 2 && $n_analysis_neg === $n_analysis;
                    $cobertura           = $analysis_consistent ? 'A' : 'B';

                    // C7b-022: distinguir déficit activo vs resuelto cuando el test corre sobre la base
                    if ($analysis_consistent) {
                        $tendencia_reciente = 'activo';     // análisis confirma
                    } elseif ($n_analysis === 0) {
                        $tendencia_reciente = 'sin_datos';  // sin recepciones en el período de análisis
                    } else {
                        $mean_analysis = array_sum($floors_analysis) / $n_analysis;
                        if ($mean_analysis > 0.0) {
                            $tendencia_reciente = 'resuelto';   // floors de análisis en positivo
                        } else {
                            $tendencia_reciente = 'mejorando';  // negativos pero no confirman
                        }
                    }
                } else {
                    $analysis_consistent = false;
                    $cobertura           = 'C';
                    $tendencia_reciente  = 'activo'; // el test corre sobre análisis → activo por definición
                }

                // ── Severidad: tabla 2D (confianza × cobertura) + moduladores ─
                // Tabla base:
                //   confianza \ cobertura |  A (base+análisis) | B (base solo) | C (análisis solo)
                //   'alta'  (Wilcoxon)    |  CRITICA           | ALTA          | ALTA
                //   'media' (Bootstrap)   |  ALTA              | ALTA          | MEDIA
                //   'posible' (sign/t/det)|  ALTA              | MEDIA         | MEDIA
                static $sev_tab = [
                    'alta'    => ['A' => 'CRITICA', 'B' => 'ALTA',  'C' => 'ALTA'],
                    'media'   => ['A' => 'ALTA',    'B' => 'ALTA',  'C' => 'MEDIA'],
                    'posible' => ['A' => 'ALTA',    'B' => 'MEDIA', 'C' => 'MEDIA'],
                ];
                static $sev_num = ['CRITICA' => 3, 'ALTA' => 2, 'MEDIA' => 1, 'BAJA' => 0];
                static $sev_name = [3 => 'CRITICA', 2 => 'ALTA', 1 => 'MEDIA', 0 => 'BAJA'];

                $sev_base = $sev_tab[$confianza_c7b][$cobertura];
                $mag_small = ($tipo_art === 'peso')
                    ? ($abs_mean_raw < $c7b_umbral_sev_peso)
                    : ($abs_mean_raw < $c7b_umbral_sev_unidad);

                $sev_nivel = $sev_num[$sev_base];
                if ($mag_small) {
                    $sev_nivel--;
                } // magnitud pequeña
                if ($pct_neg < 50) {
                    $sev_nivel--;
                } // déficit parcial
                $severidad = $sev_name[max(0, $sev_nivel)];

                // ── C7b-022: posible_causa accionable según tendencia ────────
                $unidad_causa = $tipo_art === 'peso' ? 'kg' : 'ud.';
                switch ($tendencia_reciente) {
                    case 'resuelto':
                        $causa_texto = sprintf(
                            'El stock caía ~%d %s en negativo de forma repetida, pero el período reciente no muestra el patrón. Verificar que el albarán pendiente fue registrado o que el error se corrigió.',
                            (int)round($abs_mean_raw),
                            $unidad_causa
                        );
                        break;
                    case 'mejorando':
                        $causa_texto = sprintf(
                            'El stock caía ~%d %s en negativo de forma repetida. El período reciente tiene floors negativos pero sin confirmación estadística — el problema puede estar reduciéndose. Monitorizar en próximo informe.',
                            (int)round($abs_mean_raw),
                            $unidad_causa
                        );
                        break;
                    case 'sin_datos':
                        $causa_texto = sprintf(
                            'El stock caía ~%d %s en negativo de forma repetida. Sin recepciones en el período reciente — no es posible confirmar si el problema sigue activo. Revisar albaranes pendientes.',
                            (int)round($abs_mean_raw),
                            $unidad_causa
                        );
                        break;
                    default: // 'activo'
                        $causa_texto = sprintf(
                            'El stock cae ~%d %s en negativo de forma repetida entre cada recepción. Revisar si hay albaranes pendientes de confirmar o si el stock inicial del artículo está bien introducido.',
                            (int)round($abs_mean_raw),
                            $unidad_causa
                        );
                }

                $incidencias[] = [
                    'idArticulo'               => $id,
                    'tipo'                     => 'Entrada no registrada',
                    'severidad'                => $severidad,
                    'c7_subcaso'               => 'C7b',
                    'confianza'                => $confianza_c7b,
                    'test_period'              => $test_period,
                    'analysis_consistent'      => $analysis_consistent ?? false,
                    'tendencia_reciente'       => $tendencia_reciente,
                    'tipo_articulo'            => $tipo_art,
                    'n_recepciones'            => $n_rec,
                    'offset_estimado'          => round($mean_raw, 1),
                    'offset_norm'              => round($mean, 3),
                    'dispersion'               => round($std_dev, 3),
                    'autocorr_lag1'            => round($r1, 2),
                    'ic95_upper'               => $ic95_upper !== null ? round($ic95_upper, 4) : null,
                    'cv_duraciones'            => round($cv_dias, 3),
                    'test_type'                => $test_type,
                    'test_pvalue'              => $test_pvalue,
                    'test_fallback_reason'     => $test_fallback_reason,
                    'n_intervalos_negativos'   => $n_neg,
                    'pct_intervalos_negativos' => $pct_neg,
                    'dias_intervalo_medio'     => $dias_intervalo_medio,
                    'fecha_primera'            => $fechas_rec[0],
                    'fecha_ultima'             => $fechas_rec[$n_rec - 1],
                    '_floors_raw'              => $floors_map,
                    'posible_causa'            => $causa_texto,
                ];
                continue;
            } elseif ($n_analysis === 2 && isset($subcasos_set['C7b'])) {
                // C7b-010: C7b_posible — solo 2 floors en el periodo de análisis,
                // sin base suficiente para test estadístico.
                // Condición: ambos negativos y CV < 0.15 (muy baja dispersión relativa).
                $f0 = $floors_analysis[0];
                $f1 = $floors_analysis[1];
                $mean_2 = ($f0 + $f1) / 2.0;
                $cv_2   = $mean_2 != 0.0 ? abs($f0 - $f1) / (2.0 * abs($mean_2)) : PHP_FLOAT_MAX;
                if ($f0 < 0 && $f1 < 0 && $cv_2 < 0.15) {
                    $abs_med_2 = abs($mean_2);
                    if (!($tipo_art === 'peso' && $abs_med_2 < 0.5)) {
                        $dias_med_2  = (int)round(array_sum($dias_analysis) / 2);
                        $date_keys_2 = array_slice(array_keys($floors_map), -2);
                        $incidencias[] = [
                            'idArticulo'               => $id,
                            'tipo'                     => 'Entrada no registrada',
                            'severidad'                => 'BAJA',
                            'c7_subcaso'               => 'C7b_posible',   // NO alimenta C7c/C7d/C7e
                            'confianza'                => 'posible',
                            'tipo_articulo'            => $tipo_art,
                            'n_recepciones'            => $n_rec,
                            'offset_estimado'          => round($mean_2, 1),
                            'offset_norm'              => round($mean_2 / max(1, $dias_med_2), 3),
                            'dispersion'               => round(abs($f0 - $f1) / 2.0, 3),
                            'autocorr_lag1'            => 0.0,
                            'ic95_upper'               => null,
                            'n_intervalos_negativos'   => 2,
                            'pct_intervalos_negativos' => 100,
                            'dias_intervalo_medio'     => $dias_med_2,
                            'fecha_primera'            => $date_keys_2[0] ?? $fechas_rec[0],
                            'fecha_ultima'             => $date_keys_2[1] ?? $fechas_rec[$n_rec - 1],
                            '_floors_raw'              => $floors_map,
                            'posible_causa'            => sprintf(
                                'El stock cae ~%d %s en negativo en las 2 recepciones del periodo. Podría faltar un albarán, aunque con solo 2 datos no es posible confirmarlo — conviene revisar manualmente.',
                                (int)round($abs_med_2),
                                $tipo_art === 'peso' ? 'kg' : 'ud.'
                            ),
                        ];
                    }
                }
            }
        }

        // ── C7a-004 + C7a-005: enriquecer C7a con coste estimado de merma y proveedor ──
        if (isset($subcasos_set['C7a'])) {
            $ids_c7a_enr = array_column(
                array_filter($incidencias, fn($inc) => ($inc['c7_subcaso'] ?? '') === 'C7a' || ($inc['c7_subcaso'] ?? '') === 'C7a_posible'),
                'idArticulo'
            );
            if (!empty($ids_c7a_enr)) {
                $fi_stock_esc_c7a = $this->db->real_escape_string($fi_stock);
                $ids_c7a_str      = implode(',', array_map('intval', $ids_c7a_enr));

                $prov_map_c7a   = $this->_queryProveedorArticulos($ids_c7a_str, $fi_stock_esc_c7a, $ff);
                $precio_map_c7a = $this->_queryPrecioMedioCompra($ids_c7a_str, $fi_stock_esc_c7a, $ff);

                foreach ($incidencias as &$inc) {
                    $sub = $inc['c7_subcaso'] ?? '';
                    if ($sub !== 'C7a' && $sub !== 'C7a_posible') continue;
                    $prov = $prov_map_c7a[$inc['idArticulo']] ?? null;
                    $inc['prov_habitual_nombre'] = $prov['prov_habitual_nombre'] ?? null;
                    $inc['prov_habitual_n']      = $prov['prov_habitual_n']      ?? null;
                    $inc['prov_ultimo_nombre']   = $prov['prov_ultimo_nombre']   ?? null;
                    $inc['prov_ultima_fecha']    = $prov['prov_ultima_fecha']    ?? null;
                    $inc['prov_es_mismo']        = $prov['prov_es_mismo']        ?? null;
                    // C7a-004: valor económico estimado de la merma acumulada
                    $precio = $precio_map_c7a[$inc['idArticulo']] ?? null;
                    $inc['precio_medio_compra']  = $precio;
                    $inc['coste_estimado_merma'] = ($precio !== null)
                        ? round((float)$inc['delta_acumulado'] * $precio, 2)
                        : null;
                }
                unset($inc);
            }
        }

        // ── C7b-007 + C7b-011: enriquecer C7b con proveedor y coste estimado ──
        // Aplica a todos los subcasos C7b (C7b, C7b_posible, C7b_ruido_peso).
        if (isset($subcasos_set['C7b'])) {
            $ids_c7b = array_column(
                array_filter($incidencias, fn($inc) => str_starts_with($inc['c7_subcaso'] ?? '', 'C7b')),
                'idArticulo'
            );
            if (!empty($ids_c7b)) {
                $fi_stock_esc = $this->db->real_escape_string($fi_stock);
                $ids_c7b_str  = implode(',', array_map('intval', $ids_c7b));

                $prov_map_c7b   = $this->_queryProveedorArticulos($ids_c7b_str, $fi_stock_esc, $ff);
                $precio_map_c7b = $this->_queryPrecioMedioCompra($ids_c7b_str, $fi_stock_esc, $ff);

                foreach ($incidencias as &$inc) {
                    if (!str_starts_with($inc['c7_subcaso'] ?? '', 'C7b')) continue;
                    $prov = $prov_map_c7b[$inc['idArticulo']] ?? null;
                    $inc['prov_habitual_nombre'] = $prov['prov_habitual_nombre'] ?? null;
                    $inc['prov_habitual_n']      = $prov['prov_habitual_n']      ?? null;
                    $inc['prov_ultimo_nombre']   = $prov['prov_ultimo_nombre']   ?? null;
                    $inc['prov_ultima_fecha']    = $prov['prov_ultima_fecha']    ?? null;
                    $inc['prov_es_mismo']        = $prov['prov_es_mismo']        ?? null;
                    // C7b-011: valor económico estimado del déficit
                    $precio = $precio_map_c7b[$inc['idArticulo']] ?? null;
                    $inc['precio_medio_compra'] = $precio;
                    $inc['coste_estimado']      = ($precio !== null)
                        ? round(abs((float)$inc['offset_estimado']) * $precio, 2)
                        : null;
                }
                unset($inc);
            }
        }

        // ── C7c: detección de cruces entre artículos (C7a + C7b activos) ─────
        // Se ejecuta solo cuando ambos subcasos están activos (sin checkbox propio)
        // Y cuando $skip_cde = false (fase final; con todos los artículos disponibles).
        // En lotes parciales se salta: los cruces solo tienen sentido sobre el conjunto
        // completo de artículos C7a+C7b; detectar dentro de un lote de 40 produce falsos
        // negativos (el artículo cruzado puede estar en otro lote).
        //
        // Hipótesis: un cajero confunde artículo A (C7a, merma aparente) con
        // artículo B (C7b, déficit sistemático) al pesar en la balanza. Los offsets
        // de A (positivos) y B (negativos) se cancelan: μ_A + μ_B ≈ 0.
        //
        // Filtros rígidos:
        //   · Mismo departamento N1 (distinto → imposible físicamente)
        //   · |μ_A + μ_B| / max(|μ_A|, |μ_B|) ≤ 0.45 (los offsets se cancelan, relativo)
        //   · ≥ 4 pares temporalmente solapados (±7 días)
        //   · CV < 0.70 en ambos (patrón regular)
        //   · Pearson ≥ 0.50 si n ≥ 4 (covarianza de los valores absolutos)
        //
        // Score = 0.50 × s_mag + 0.25 × correlación + 0.15 × s_disp + 0.10 × s_nombre
        //   · s_mag  = 1 − |μ_A + μ_B| / max(|μ_A|, |μ_B|)
        //   · s_disp = 1 − |log(σ_A / σ_B)| / 2  (similitud de dispersiones)
        //   · s_nombre: Jaccard sobre nombres
        //
        // Niveles: confirmado ≥ 0.80 | probable ≥ 0.65 | posible ≥ 0.50
        if (!$skip_cde && isset($subcasos_set['C7a']) && isset($subcasos_set['C7b'])) {

            // Indexar C7b con sus floors por fecha
            $c7b_data = [];
            foreach ($incidencias as $inc) {
                if ($inc['c7_subcaso'] !== 'C7b') continue;
                $c7b_data[$inc['idArticulo']] = [
                    'floors_raw' => $inc['_floors_raw'],
                ];
            }

            // Meta (nombre, familias N1) de todos los artículos C7 — una sola query
            $ids_c7   = implode(',', array_unique(array_column($incidencias, 'idArticulo')));
            $meta_c7c = $this->_queryMetaC7c($ids_c7);

            $cruces = [];   // id_c7a => ['id_b' => int, 'score' => float, 'nivel' => string]

            foreach ($incidencias as &$inc) {
                if ($inc['c7_subcaso'] !== 'C7a') continue;

                $mejor_score = 0.0;
                $mejor_id    = null;
                $mejor_nivel = '';

                $n1_a = $meta_c7c[$inc['idArticulo']]['familias_n1'] ?? [];

                foreach ($c7b_data as $id_b => $cb) {

                    // ── 1. Filtro duro N1: mismo departamento ────────────────────────
                    $n1_b = $meta_c7c[$id_b]['familias_n1'] ?? [];
                    if (!empty($n1_a) && !empty($n1_b) && empty(array_intersect($n1_a, $n1_b))) continue;

                    // ── 2. Emparejar suelos por fecha más próxima (±7 días) ──────────
                    // pairs_b guarda el valor negativo original del C7b
                    $pairs_a = [];
                    $pairs_b = [];
                    $ventana  = 7 * 86400;
                    foreach ($inc['_floors_raw'] as $da => $va) {
                        $ts_a      = strtotime($da);
                        $best_diff = $ventana + 1;
                        $best_vb   = null;
                        foreach ($cb['floors_raw'] as $db => $vb) {
                            $diff = abs($ts_a - strtotime($db));
                            if ($diff <= $ventana && $diff < $best_diff) {
                                $best_diff = $diff;
                                $best_vb   = $vb;
                            }
                        }
                        if ($best_vb !== null) {
                            $pairs_a[] = $va;
                            $pairs_b[] = $best_vb;   // valor negativo del C7b
                        }
                    }

                    $n_pairs = count($pairs_a);
                    if ($n_pairs < 4) continue;   // mínimo 4 pares solapados

                    [$mu_a, $sd_a] = $this->_statsFloors($pairs_a);
                    [$mu_b, $sd_b] = $this->_statsFloors($pairs_b);   // mu_b < 0

                    if ($mu_a < 1.5 || abs($mu_b) < 1.5) continue;   // offset pequeño → falsos positivos

                    // ── 3. Filtros rígidos ───────────────────────────────────────────
                    // 3a. |μ_A + μ_B| / max(|μ_A|, |μ_B|) ≤ 0.45: los offsets se cancelan
                    // (filtro relativo para que escale con la magnitud de los artículos)
                    $suma_mu     = abs($mu_a + $mu_b);
                    $suma_mu_rel = $suma_mu / max(abs($mu_a), abs($mu_b));
                    if ($suma_mu_rel > 0.45) continue;

                    // 3b. CV < 0.70 en ambos
                    $cv_a = ($mu_a > 0.0) ? $sd_a / $mu_a : PHP_FLOAT_MAX;
                    $cv_b = (abs($mu_b) > 0.0) ? $sd_b / abs($mu_b) : PHP_FLOAT_MAX;
                    if ($cv_a > 0.70 || $cv_b > 0.70) continue;

                    // ── 4. Correlación de Pearson (en valor absoluto) ────────────────
                    $pairs_b_abs = array_map('abs', $pairs_b);
                    $corr = ($n_pairs >= 4) ? $this->_pearsonCorr($pairs_a, $pairs_b_abs) : 0.5;
                    if ($n_pairs >= 4 && $corr < 0.50) continue;

                    // ── 5. Score compuesto ───────────────────────────────────────────
                    $s_mag  = 1.0 - $suma_mu_rel;   // ya normalizado por max(|μ|)
                    $s_corr = max(0.0, (float)$corr);
                    $s_disp = ($sd_a > 0.0 && $sd_b > 0.0)
                        ? max(0.0, 1.0 - abs(log($sd_a / $sd_b)) / 2.0)
                        : 0.5;
                    $s_nombre = $this->_jaccardNombre(
                        $meta_c7c[$inc['idArticulo']]['nombre'] ?? '',
                        $meta_c7c[$id_b]['nombre'] ?? ''
                    );

                    $score = $s_mag    * 0.50
                        + $s_corr   * 0.25
                        + $s_disp   * 0.15
                        + $s_nombre * 0.10;

                    if ($score < 0.50) continue;
                    $nivel = ($score >= 0.80) ? 'confirmado'
                        : (($score >= 0.65) ? 'probable' : 'posible');

                    if ($score > $mejor_score) {
                        $mejor_score = $score;
                        $mejor_id    = $id_b;
                        $mejor_nivel = $nivel;
                    }
                }

                if ($mejor_id !== null) {
                    $inc['posible_cruce_con'] = $mejor_id;
                    $inc['cruce_score']       = round($mejor_score, 2);
                    $inc['cruce_nivel']       = $mejor_nivel;
                    $cruces[$inc['idArticulo']] = [
                        'id_b'  => $mejor_id,
                        'score' => round($mejor_score, 2),
                        'nivel' => $mejor_nivel,
                    ];
                    $inc['posible_causa'] = sprintf(
                        'Merma con patrón complementario a art. %d (cruce %s, score=%.2f): probable confusión en la balanza de autopesaje entre ambos artículos',
                        $mejor_id,
                        $mejor_nivel,
                        $mejor_score
                    );
                }
            }
            unset($inc);

            // Propagar estado de cruce al lado C7b
            if (!empty($cruces)) {
                $c7b_cruce = [];   // id_c7b => [id_a, score, nivel] del mejor candidato C7a
                foreach ($cruces as $id_a => $data) {
                    $id_b = $data['id_b'];
                    if (!isset($c7b_cruce[$id_b]) || $data['score'] > $c7b_cruce[$id_b]['score']) {
                        $c7b_cruce[$id_b] = ['id_a' => $id_a, 'score' => $data['score'], 'nivel' => $data['nivel']];
                    }
                }
                foreach ($incidencias as &$inc) {
                    if ($inc['c7_subcaso'] !== 'C7b') continue;
                    if (!isset($c7b_cruce[$inc['idArticulo']])) continue;
                    $id_a  = $c7b_cruce[$inc['idArticulo']]['id_a'];
                    $score = $c7b_cruce[$inc['idArticulo']]['score'];
                    $nivel = $c7b_cruce[$inc['idArticulo']]['nivel'];
                    $inc['posible_cruce_con'] = $id_a;
                    $inc['cruce_score']       = $score;
                    $inc['cruce_nivel']       = $nivel;
                    $inc['posible_causa'] = sprintf(
                        'Déficit de ~%.0f ud. con patrón complementario a art. %d (cruce %s, score=%.2f): probable confusión en la balanza o recepción no registrada',
                        abs((float)$inc['offset_estimado']),
                        $id_a,
                        $nivel,
                        $score
                    );
                }
                unset($inc);
            }
        }

        // ── C7e / C7d — solo cuando ambos subcasos están activos ────────────
        // C7b-008: C7e y C7d se invocan aquí, con el mismo guard que C7c, para que
        // el ciclo de vida de _floors_raw quede completamente dentro de este método.
        if (!$skip_cde && isset($subcasos_set['C7a']) && isset($subcasos_set['C7b'])) {
            $this->getIncidenciasC7e($incidencias);
            $this->getIncidenciasC7d($incidencias);
        }

        // Limpiar _floors_raw siempre, independientemente de qué subcasos corrieron.
        // Este es el único punto donde se elimina el campo interno.
        foreach ($incidencias as &$inc) {
            unset($inc['_floors_raw']);
        }
        unset($inc);

        return $incidencias;
    }

    /**
     * C7d — Detección de tríos: dos artículos con déficit (C7b) explican
     * la merma de un tercer artículo (C7a).
     *
     * Hipótesis: μ_A + μ_B ≈ μ_C  (los déficits de A y B cancelan la merma de C).
     *
     * Criterios:
     *   · |μ_C − (μ_A + μ_B)| ≤ 0.5 ud  (suma casi exacta)
     *   · max(σ_A, σ_B, σ_C) / min(...) ≤ 2.0  (variabilidades similares)
     *   · Pearson(suma_AB, merma_C) ≥ 0.7
     *   · ≥ 6 triples solapados (ancla = intervalo de C, ±7 días para A y B)
     *   · Score = s_mag×0.60 + correlación×0.40; probable ≥ 0.75, posible ≥ 0.50
     *
     * Solo sustituye el match de C7c-par si el score del trío es estrictamente mayor.
     * Complejidad: O(N_c7a × N_c7b² × N_floors) — aceptable para N_c7b ≲ 100.
     *
     * @param array &$incidencias  Incidencias C7 (con _floors_raw presentes).
     */
    private function getIncidenciasC7d(array &$incidencias): void
    {
        // Activar solo si hay al menos un C7a y un C7b en esta pasada
        $has_c7a = $has_c7b = false;
        foreach ($incidencias as $inc) {
            if ($inc['c7_subcaso'] === 'C7a') $has_c7a = true;
            if ($inc['c7_subcaso'] === 'C7b') $has_c7b = true;
            if ($has_c7a && $has_c7b) break;
        }

        if ($has_c7a && $has_c7b) {

            // Indexar C7b (reutiliza la misma estructura que C7c)
            $c7b_data = [];
            foreach ($incidencias as $inc) {
                if ($inc['c7_subcaso'] !== 'C7b') continue;
                $c7b_data[$inc['idArticulo']] = [
                    'floors_raw' => $inc['_floors_raw'],
                    'ts_floors'  => $inc['_ts_floors']
                        ?? array_combine(
                            $keys = array_keys($inc['_floors_raw']),
                            array_map('strtotime', $keys)
                        ),
                ];
            }

            $c7b_ids = array_keys($c7b_data);
            $n_c7b   = count($c7b_ids);
            $trios   = [];   // id_c7a => ['id_a', 'id_b', 'score', 'nivel']

            // ── Pre-calcular emparejamientos (C7a, C7b) una sola vez ─────────
            // pair_match[id_c7a][id_c7b] = [floor_date_c7a => mejor_v_c7b]
            // Coste: O(N_a × N_b × F_c × F_b).  El triple loop interior pasa de
            // O(N_a × N_b² × F² ) a O(N_a × N_b² × F) con array_intersect_key.
            $ventana    = 7 * 86400;
            $pair_match = [];
            foreach ($incidencias as $inc_pm) {
                if ($inc_pm['c7_subcaso'] !== 'C7a') continue;
                $id_c     = $inc_pm['idArticulo'];
                $ts_c_map = $inc_pm['_ts_floors'] ?? null;
                $row      = [];
                foreach ($c7b_data as $id_b => $cb) {
                    $ts_b_map       = $cb['ts_floors'];
                    $best_per_floor = [];
                    foreach ($inc_pm['_floors_raw'] as $dc => $vc) {
                        $ts_c      = $ts_c_map ? $ts_c_map[$dc] : strtotime($dc);
                        $best_diff = $ventana + 1;
                        $best_v    = null;
                        foreach ($cb['floors_raw'] as $db => $vb) {
                            $diff = abs($ts_c - $ts_b_map[$db]);
                            if ($diff <= $ventana && $diff < $best_diff) {
                                $best_diff = $diff;
                                $best_v    = $vb;
                            }
                        }
                        if ($best_v !== null) $best_per_floor[$dc] = $best_v;
                    }
                    $row[$id_b] = $best_per_floor;
                }
                $pair_match[$id_c] = $row;
            }

            foreach ($incidencias as &$inc) {
                if ($inc['c7_subcaso'] !== 'C7a') continue;

                $matches_c   = $pair_match[$inc['idArticulo']] ?? [];
                // Superar el score del par (si C7c ya encontró algo)
                $mejor_score = isset($inc['cruce_score']) ? (float)$inc['cruce_score'] : 0.0;
                $mejor_trio  = null;

                for ($i = 0; $i < $n_c7b - 1; $i++) {
                    $id_a    = $c7b_ids[$i];
                    $match_a = $matches_c[$id_a] ?? [];
                    if (empty($match_a)) continue;

                    for ($j = $i + 1; $j < $n_c7b; $j++) {
                        $id_b    = $c7b_ids[$j];
                        $match_b = $matches_c[$id_b] ?? [];

                        // Solo fechas con match en ambos C7b
                        $common = array_intersect_key($match_a, $match_b);
                        if (count($common) < 6) continue;

                        $triples_c  = [];
                        $triples_a  = [];
                        $triples_b  = [];
                        $triples_ab = [];
                        foreach ($common as $dc => $_) {
                            $vc           = $inc['_floors_raw'][$dc];
                            $va           = abs($match_a[$dc]);
                            $vb           = abs($match_b[$dc]);
                            $triples_c[]  = $vc;
                            $triples_a[]  = $va;
                            $triples_b[]  = $vb;
                            $triples_ab[] = $va + $vb;
                        }

                        if (count($triples_c) < 6) continue;

                        [$mu_c,  $sd_c]  = $this->_statsFloors($triples_c);
                        [$mu_ab] = $this->_statsFloors($triples_ab);
                        [,       $sd_a_t] = $this->_statsFloors($triples_a);
                        [,       $sd_b_t] = $this->_statsFloors($triples_b);

                        if ($mu_c < 1.5 || $mu_ab < 1.5) continue;

                        // Criterio 1: |μ_C − (μ_A + μ_B)| ≤ 0.5 ud
                        $suma_neta = abs($mu_c - $mu_ab);
                        if ($suma_neta > 0.5) continue;

                        // Criterio 2: ratio dispersiones individuales ≤ 2.0
                        $sds = array_filter([$sd_a_t, $sd_b_t, $sd_c], fn($s) => $s > 0.0);
                        if (!empty($sds) && max($sds) / min($sds) > 2.0) continue;

                        // Criterio 3: Pearson(suma_AB, merma_C) ≥ 0.7
                        $corr = $this->_pearsonCorr($triples_ab, $triples_c);
                        if ($corr < 0.7) continue;

                        // Score: magnitud (60 %) + correlación (40 %)
                        $s_mag = max(0.0, 1.0 - $suma_neta / max(0.5, $mu_c));
                        $score = $s_mag * 0.60 + max(0.0, $corr) * 0.40;
                        $nivel = ($score >= 0.75) ? 'probable' : 'posible';

                        if ($score > $mejor_score) {
                            $mejor_score = $score;
                            $mejor_trio  = [
                                'id_a'  => $id_a,
                                'id_b'  => $id_b,
                                'score' => round($score, 2),
                                'nivel' => $nivel,
                            ];
                        }
                    }
                }

                if ($mejor_trio !== null) {
                    $inc['posible_cruce_con'] = $mejor_trio['id_a'];
                    $inc['cruce_fuente_b']    = $mejor_trio['id_b'];
                    $inc['cruce_tipo']        = 'trio';
                    $inc['cruce_score']       = $mejor_trio['score'];
                    $inc['cruce_nivel']       = $mejor_trio['nivel'];
                    $trios[$inc['idArticulo']] = $mejor_trio;
                    $inc['posible_causa'] = sprintf(
                        'Merma con patrón trío art. %d + art. %d (%s, score=%.2f): posible confusión sistemática en la balanza de autopesaje',
                        $mejor_trio['id_a'],
                        $mejor_trio['id_b'],
                        $mejor_trio['nivel'],
                        $mejor_trio['score']
                    );
                }
            }
            unset($inc);

            // Propagar a los dos artículos C7b fuente
            foreach ($trios as $id_c => $trio) {
                foreach ($incidencias as &$inc) {
                    if ($inc['c7_subcaso'] !== 'C7b') continue;
                    $id_inc = $inc['idArticulo'];
                    if ($id_inc !== $trio['id_a'] && $id_inc !== $trio['id_b']) continue;
                    $otro = ($id_inc === $trio['id_a']) ? $trio['id_b'] : $trio['id_a'];
                    $inc['posible_cruce_con'] = $id_c;
                    $inc['cruce_fuente_b']    = $otro;
                    $inc['cruce_tipo']        = 'trio';
                    $inc['cruce_score']       = $trio['score'];
                    $inc['cruce_nivel']       = $trio['nivel'];
                    $inc['posible_causa'] = sprintf(
                        'Déficit de ~%.0f ud. incluido en trío junto a art. %d → art. %d (%s, score=%.2f): probable confusión en balanza o recepción no registrada',
                        abs((float)$inc['offset_estimado']),
                        $otro,
                        $id_c,
                        $trio['nivel'],
                        $trio['score']
                    );
                }
                unset($inc);
            }
        }
    }

    /**
     * C7e — Pérdidas fantasma por múltiplos: k unidades de A escaneadas como 1 de B
     * (o 1 de A como k de B), con k ∈ {2, 3, 4, 5}.
     *
     * Extensión de C7c para ratios k ≠ 1. Mientras C7c exige |μ_A + μ_B| ≤ 0.40,
     * C7e busca pares donde |μ_A| ≈ k × |μ_B| (dir A_por_B) o |μ_B| ≈ k × |μ_A|
     * (dir B_por_A), con k entero dentro del 20 % de tolerancia relativa.
     *
     * Casos típicos:
     *   · 2 barras de pan (A) cobradas como 1 paquete (B): k=2, dir=A_por_B
     *   · 1 kg de fruta (A) cobrado como 2 bandejas (B):  k=2, dir=B_por_A
     *
     * Criterios:
     *   · Mismo departamento N1 (filtro duro)
     *   · ≥ 5 pares solapados (±7 días)
     *   · Tolerancia: |k_raw − k| / k ≤ 0.20
     *   · Magnitud escalada: |μ_A + k×μ_B| ≤ 0.4×k  (dir A_por_B)
     *     o              |k×μ_A + μ_B| ≤ 0.4×k  (dir B_por_A)
     *   · Ratio de varianzas escaladas k²×σ²_A/σ²_B ∈ [0.5, 2]
     *   · CV < 0.70 en ambos
     *   · Pearson ≥ 0.50 sobre pares escalados (si n ≥ 4)
     *   · Score = 0.40×s_mag + 0.30×s_corr + 0.20×s_disp + 0.10×s_nombre
     *   · Solo anota si el score supera el match previo (C7c u otro C7e)
     *
     * Debe llamarse ANTES de getIncidenciasC7d (que borra _floors_raw).
     *
     * @param array &$incidencias  Incidencias C7 con _floors_raw presentes.
     */
    private function getIncidenciasC7e(array &$incidencias): void
    {
        $has_c7a = $has_c7b = false;
        foreach ($incidencias as $inc) {
            if ($inc['c7_subcaso'] === 'C7a') $has_c7a = true;
            if ($inc['c7_subcaso'] === 'C7b') $has_c7b = true;
            if ($has_c7a && $has_c7b) break;
        }
        if (!$has_c7a || !$has_c7b) return;

        // Indexar C7b con sus floors por fecha
        $c7b_data = [];
        foreach ($incidencias as $inc) {
            if ($inc['c7_subcaso'] !== 'C7b') continue;
            $c7b_data[$inc['idArticulo']] = [
                'floors_raw' => $inc['_floors_raw'],
                'ts_floors'  => $inc['_ts_floors']
                    ?? array_combine(
                        $keys = array_keys($inc['_floors_raw']),
                        array_map('strtotime', $keys)
                    ),
            ];
        }

        $ids_c7 = implode(',', array_unique(array_column($incidencias, 'idArticulo')));
        $meta   = $this->_queryMetaC7c($ids_c7);

        $cruces_e = [];   // id_c7a => ['id_b', 'score', 'nivel', 'k', 'dir']

        foreach ($incidencias as &$inc) {
            if ($inc['c7_subcaso'] !== 'C7a') continue;

            // Solo anotar si se supera el score previo (C7c u otro C7e)
            $mejor_score = $inc['cruce_score'] ?? 0.0;
            $mejor_id    = null;
            $mejor_nivel = '';
            $mejor_k     = 0;
            $mejor_dir   = '';

            $n1_a = $meta[$inc['idArticulo']]['familias_n1'] ?? [];

            foreach ($c7b_data as $id_b => $cb) {

                // ── Filtro duro N1 ────────────────────────────────────────────
                $n1_b = $meta[$id_b]['familias_n1'] ?? [];
                if (!empty($n1_a) && !empty($n1_b) && empty(array_intersect($n1_a, $n1_b))) continue;

                // ── Emparejamiento temporal ±7 días ───────────────────────────
                $pairs_a  = [];
                $pairs_b  = [];
                $ventana  = 7 * 86400;
                $ts_a_map = $inc['_ts_floors'] ?? null;
                $ts_b_map = $cb['ts_floors'];
                foreach ($inc['_floors_raw'] as $da => $va) {
                    $ts_a      = $ts_a_map ? $ts_a_map[$da] : strtotime($da);
                    $best_diff = $ventana + 1;
                    $best_vb   = null;
                    foreach ($cb['floors_raw'] as $db => $vb) {
                        $diff = abs($ts_a - $ts_b_map[$db]);
                        if ($diff <= $ventana && $diff < $best_diff) {
                            $best_diff = $diff;
                            $best_vb   = $vb;
                        }
                    }
                    if ($best_vb !== null) {
                        $pairs_a[] = $va;
                        $pairs_b[] = $best_vb;
                    }
                }

                $n_pairs = count($pairs_a);
                if ($n_pairs < 5) continue;

                [$mu_a, $sd_a] = $this->_statsFloors($pairs_a);
                [$mu_b, $sd_b] = $this->_statsFloors($pairs_b);   // mu_b < 0

                if ($mu_a < 1.5 || abs($mu_b) < 1.5) continue;

                // ── Determinar k y dirección ──────────────────────────────────
                $k_raw = $mu_a / abs($mu_b);   // > 0

                if ($k_raw >= 1.4) {
                    $k   = (int)round($k_raw);
                    $dir = 'A_por_B';   // k unidades de A por 1 de B
                } elseif ($k_raw <= 0.71) {
                    $k   = (int)round(1.0 / $k_raw);
                    $dir = 'B_por_A';   // 1 unidad de A por k de B
                } else {
                    continue;   // k ≈ 1: cubierto por C7c
                }

                if ($k < 2 || $k > 5) continue;

                // Tolerancia relativa ≤ 20 %
                $k_real  = ($dir === 'A_por_B') ? $k_raw : (1.0 / $k_raw);
                if (abs($k_real - $k) / $k > 0.20) continue;

                // ── Magnitud escalada ─────────────────────────────────────────
                $eps = 0.4 * $k;
                $suma_scaled = ($dir === 'A_por_B')
                    ? abs($mu_a + $k * $mu_b)    // μ_A + k×μ_B ≈ 0 (μ_b < 0)
                    : abs($k * $mu_a + $mu_b);   // k×μ_A + μ_B ≈ 0 (μ_b < 0)
                if ($suma_scaled > $eps) continue;

                // ── CV ────────────────────────────────────────────────────────
                $cv_a = ($mu_a > 0.0) ? $sd_a / $mu_a : PHP_FLOAT_MAX;
                $cv_b = (abs($mu_b) > 0.0) ? $sd_b / abs($mu_b) : PHP_FLOAT_MAX;
                if ($cv_a > 0.70 || $cv_b > 0.70) continue;

                // ── Ratio de varianzas escaladas k²×Var_A/Var_B ∈ [0.5, 2] ──
                $var_ratio = 1.0;
                if ($sd_a > 0.0 && $sd_b > 0.0) {
                    $var_ratio = ($dir === 'A_por_B')
                        ? ($k * $k * $sd_a * $sd_a) / ($sd_b * $sd_b)
                        : ($sd_a * $sd_a) / ($k * $k * $sd_b * $sd_b);
                    if ($var_ratio < 0.5 || $var_ratio > 2.0) continue;
                }

                // ── Pearson sobre pares escalados ─────────────────────────────
                if ($dir === 'A_por_B') {
                    $scaled_b = array_map(fn($v) => $k * abs($v), $pairs_b);
                    $corr = ($n_pairs >= 4) ? $this->_pearsonCorr($pairs_a, $scaled_b) : 0.5;
                } else {
                    $scaled_a = array_map(fn($v) => $k * $v, $pairs_a);
                    $corr = ($n_pairs >= 4) ? $this->_pearsonCorr($scaled_a, array_map('abs', $pairs_b)) : 0.5;
                }
                if ($n_pairs >= 4 && $corr < 0.50) continue;

                // ── Score ─────────────────────────────────────────────────────
                $s_mag    = max(0.0, 1.0 - $suma_scaled / $eps);
                $s_corr   = max(0.0, (float)$corr);
                $s_disp   = max(0.0, 1.0 - abs(log(sqrt($var_ratio))) / 2.0);
                $s_nombre = $this->_jaccardNombre(
                    $meta[$inc['idArticulo']]['nombre'] ?? '',
                    $meta[$id_b]['nombre'] ?? ''
                );
                $score = $s_mag * 0.40 + $s_corr * 0.30 + $s_disp * 0.20 + $s_nombre * 0.10;

                if ($score < 0.50) continue;
                $nivel = ($score >= 0.85) ? 'confirmado' : (($score >= 0.70) ? 'probable' : 'posible');

                if ($score > $mejor_score) {
                    $mejor_score = $score;
                    $mejor_id    = $id_b;
                    $mejor_nivel = $nivel;
                    $mejor_k     = $k;
                    $mejor_dir   = $dir;
                }
            }

            if ($mejor_id !== null) {
                $causa = ($mejor_dir === 'A_por_B')
                    ? sprintf(
                        'Merma con patrón múltiplo k=%d respecto a art. %d (%s, score=%.2f): posible cobro de %d uds. de este artículo como 1 ud. de art. %d en la balanza',
                        $mejor_k,
                        $mejor_id,
                        $mejor_nivel,
                        $mejor_score,
                        $mejor_k,
                        $mejor_id
                    )
                    : sprintf(
                        'Merma con patrón múltiplo k=%d respecto a art. %d (%s, score=%.2f): posible cobro de 1 ud. de este artículo como %d uds. de art. %d en la balanza',
                        $mejor_k,
                        $mejor_id,
                        $mejor_nivel,
                        $mejor_score,
                        $mejor_k,
                        $mejor_id
                    );

                $inc['posible_cruce_con'] = $mejor_id;
                $inc['cruce_score']       = round($mejor_score, 2);
                $inc['cruce_nivel']       = $mejor_nivel;
                $inc['cruce_tipo']        = 'multiplo';
                $inc['cruce_ratio_k']     = $mejor_k;
                $inc['cruce_ratio_dir']   = $mejor_dir;
                $inc['posible_causa']     = $causa;
                $cruces_e[$inc['idArticulo']] = [
                    'id_b'  => $mejor_id,
                    'score' => round($mejor_score, 2),
                    'nivel' => $mejor_nivel,
                    'k'     => $mejor_k,
                    'dir'   => $mejor_dir,
                ];
            }
        }
        unset($inc);

        // Propagar al lado C7b (solo si mejora el match actual)
        if (!empty($cruces_e)) {
            $c7b_cruce = [];
            foreach ($cruces_e as $id_a => $data) {
                $id_b = $data['id_b'];
                if (!isset($c7b_cruce[$id_b]) || $data['score'] > $c7b_cruce[$id_b]['score']) {
                    $c7b_cruce[$id_b] = ['id_a' => $id_a] + $data;
                }
            }
            foreach ($incidencias as &$inc) {
                if ($inc['c7_subcaso'] !== 'C7b') continue;
                if (!isset($c7b_cruce[$inc['idArticulo']])) continue;
                $data = $c7b_cruce[$inc['idArticulo']];
                if (isset($inc['cruce_score']) && $data['score'] <= $inc['cruce_score']) continue;

                $k    = $data['k'];
                $dir  = $data['dir'];
                $id_a = $data['id_a'];

                $causa_b = sprintf(
                    'Déficit de ~%.0f ud. con patrón múltiplo k=%d respecto a art. %d (%s, score=%.2f): probable confusión en balanza o recepción no registrada',
                    abs((float)$inc['offset_estimado']),
                    $k,
                    $id_a,
                    $data['nivel'],
                    $data['score']
                );

                $inc['posible_cruce_con'] = $id_a;
                $inc['cruce_score']       = $data['score'];
                $inc['cruce_nivel']       = $data['nivel'];
                $inc['cruce_tipo']        = 'multiplo';
                $inc['cruce_ratio_k']     = $k;
                $inc['cruce_ratio_dir']   = $dir;
                $inc['posible_causa']     = $causa_b;
            }
            unset($inc);
        }
    }

    /**
     * Devuelve una página de idArticulo con actividad en el rango usando LIMIT/OFFSET.
     *
     * La paginación se hace directamente en SQL: nunca se cargan todos los IDs
     * en memoria PHP, lo que mantiene el coste constante por lote independientemente
     * del tamaño total del periodo.
     *
     * Fin de datos: cuando la query devuelva menos de $pagina filas.
     *
     * @param int $inicial  OFFSET SQL (0, 150, 300 …)
     * @param int $pagina   LIMIT SQL (tamaño del lote)
     */
    public function getArticulosConActividad(
        string $fi_mov,
        string $ff_mov,
        array  $familias_incluir = [],
        array  $familias_excluir = [],
        int    $inicial = 0,
        int    $pagina  = 0,       // 0 = sin límite (devuelve todos, solo para compatibilidad)
        array  $ids_proveedor_filter = []   // article IDs ya resueltos desde proveedores
    ): array {
        $fi = $this->db->real_escape_string($fi_mov);
        $ff = $this->db->real_escape_string($ff_mov);

        $where_fam = '';
        if (!empty($familias_incluir)) {
            $ids = $this->expandirFamilias($familias_incluir);
            if ($ids) $where_fam .= " AND l.idArticulo IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids))";
        }
        if (!empty($familias_excluir)) {
            $ids = $this->expandirFamilias($familias_excluir);
            if ($ids) $where_fam .= " AND l.idArticulo NOT IN (SELECT DISTINCT idArticulo FROM articulosFamilias WHERE idFamilia IN ($ids))";
        }

        $where_prov   = $this->_idsWhere($ids_proveedor_filter);
        $limit_clause = ($pagina > 0) ? "LIMIT $pagina OFFSET $inicial" : '';

        $rows = $this->_queryIdsConActividad($fi, $ff, $where_fam, $limit_clause, $where_prov);
        if (isset($rows['error'])) return [];

        $ids = [];
        foreach ($rows as $r) $ids[] = (int)$r['idArticulo'];
        return $ids;
    }

    /**
     * Procesa las incidencias en lotes de $pagina artículos.
     *
     * Patrón mod_reorganizacion: cada lote devuelve { filas, actual, elementos }.
     * El JS continúa mientras elementos === pagina; para cuando elementos < pagina
     * (último lote, incluido el lote vacío si el total es múltiplo exacto de pagina).
     *
     * La paginación usa LIMIT/OFFSET en SQL — la query costosa de IDs se ejecuta
     * exactamente una vez por lote y solo devuelve $pagina filas, no el total completo.
     *
     * Para caso4: lote único (artículos sin movimiento, no paginables por actividad).
     *
     * @return array  { filas: array, actual: int, elementos: int }
     *                — o array con clave 'error'
     */

    // ══════════════════════════════════════════════════════════════════════
    //  C9 — Merma por backstaging (cálculo)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * C9 — Construye los lotes (intervalos inter-recepción) para un artículo.
     *
     * Lote 0: desde fi_mov hasta la primera recepción -1 día; E_0 = stock_base.
     * Lotes 1..n-1: intervalos completos entre recepciones consecutivas del periodo.
     * Último lote (n-1 en periodo): incluido solo si hay primera recepción post-periodo
     *   que actúe como cierre; su V_t se limita a ff_mov.
     *
     * @param  array  $recs_art      [{fecha, cantidad, es_post_periodo}] para el artículo
     * @param  array  $timeline_art  [{fecha, day_delta}] para el artículo (timeline limpio)
     * @param  float  $stock_base    Saldo acumulado fi_stock..fi_mov-1
     * @param  string $fi_mov        Inicio del periodo analizado
     * @param  string $ff_mov        Fin del periodo analizado
     * @return array  [{idx, fecha_ini, fecha_fin, E_t, V_t, S_t, dias, es_lote0, v_t_parcial}]
     */
    private function _calcularLotesC9(
        array  $recs_art,
        array  $timeline_art,
        string $fi_mov,
        string $ff_mov,
        array  $devs_art = []   // devoluciones ordinarias [{fecha, devolucion}]
    ): array {
        // Separar recepciones en-periodo vs post-periodo
        $rec_periodo  = [];
        $primera_post = null;
        foreach ($recs_art as $r) {
            if ($r['es_post_periodo']) {
                if ($primera_post === null) $primera_post = $r;
            } else {
                $rec_periodo[] = $r;
            }
        }
        if (empty($rec_periodo)) return [];

        // Indexar timeline por fecha para acceso O(1)
        $delta_by_fecha = [];
        foreach ($timeline_art as $t) {
            $delta_by_fecha[$t['fecha']] = (float)$t['day_delta'];
        }
        ksort($delta_by_fecha);

        // Indexar devoluciones ordinarias por fecha
        $dev_by_fecha = [];
        foreach ($devs_art as $d) {
            $dev_by_fecha[$d['fecha']] = ($dev_by_fecha[$d['fecha']] ?? 0.0) + (float)$d['devolucion'];
        }

        // Suma de salidas entre dos fechas inclusive (day_delta siempre positivo)
        $sum_salidas = static function (array $deltas, string $fi, string $ff): float {
            $total = 0.0;
            foreach ($deltas as $fecha => $delta) {
                if ($fecha >= $fi && $fecha <= $ff) {
                    $total += $delta;
                }
            }
            return $total;
        };

        // Suma de devoluciones (ABS) en intervalo inclusive
        $sum_devs = static function (array $devs, string $fi, string $ff): float {
            $total = 0.0;
            foreach ($devs as $fecha => $dev) {
                if ($fecha >= $fi && $fecha <= $ff) {
                    $total += $dev;
                }
            }
            return $total;
        };

        // El análisis empieza en la primera recepción del periodo.
        // Las ventas anteriores a esa fecha son consumo del stock heredado y no
        // pertenecen a ningún lote analizable — se excluyen del modelo.
        // El stock inicial (rebobinado) no entra en los cálculos de merma:
        // cualquier merma de ese stock ya ocurrió en periodos anteriores.

        $lotes = [];
        $n     = count($rec_periodo);

        // ── Lotes 0..n-1 del periodo: intervalos entre recepciones ─────────
        for ($i = 0; $i < $n - 1; $i++) {
            $fi_lot   = $rec_periodo[$i]['fecha'];
            $ff_lot   = date('Y-m-d', strtotime($rec_periodo[$i + 1]['fecha'] . ' -1 day'));
            $e_t_bruto = (float)$rec_periodo[$i]['cantidad'];
            $dev_t    = $sum_devs($dev_by_fecha, $fi_lot, $ff_lot);
            $e_t      = max(0.0, $e_t_bruto - $dev_t);
            $v_t      = $sum_salidas($delta_by_fecha, $fi_lot, $ff_lot);
            // Cruce de ciclo: devolución cubre ≥90% de la recepción y sin ventas significativas
            $cruce_ciclo = ($e_t_bruto > 0.0 && $dev_t / $e_t_bruto >= 0.9 && $v_t < 0.001);
            $lotes[] = [
                'idx'          => $i,
                'fecha_ini'    => $fi_lot,
                'fecha_fin'    => $ff_lot,
                'E_t'          => $e_t,
                'E_t_bruto'    => $e_t_bruto,
                'dev_t'        => round($dev_t, 4),
                'dev_carryback' => 0.0,
                'V_t'          => $v_t,
                'S_t'          => $e_t - $v_t,
                'dias'         => (int)(strtotime($ff_lot) - strtotime($fi_lot)) / 86400 + 1,
                'cruce_ciclo'  => $cruce_ciclo,
                'v_t_parcial'  => false,
            ];
        }

        // ── Último lote del periodo: siempre incluir ────────────────────────
        // Con primera_post: V_t hasta primera_post-1 (ciclo cerrado, v_t_parcial=false).
        // Sin primera_post: V_t hasta ff_mov (ciclo abierto, v_t_parcial=true).
        //   El sobrante positivo del ciclo abierto es carryover al siguiente periodo,
        //   no merma; se excluye de merma_total. Los déficits (S_t<0) sí participan
        //   en backstaging hacia lotes anteriores del mismo periodo.
        {
            $i         = $n - 1;
            $fi_lot    = $rec_periodo[$i]['fecha'];
            $e_t_bruto = (float)$rec_periodo[$i]['cantidad'];

            if ($primera_post !== null) {
                $ff_ciclo         = date('Y-m-d', strtotime($primera_post['fecha'] . ' -1 day'));
                $dev_t            = $sum_devs($dev_by_fecha, $fi_lot, $ff_ciclo);
                $v_t              = $sum_salidas($delta_by_fecha, $fi_lot, $ff_ciclo);
                $v_t_parcial      = false;
                $es_ultimo_abierto = false;
            } else {
                $dev_t            = $sum_devs($dev_by_fecha, $fi_lot, $ff_mov);
                $v_t              = $sum_salidas($delta_by_fecha, $fi_lot, $ff_mov);
                $v_t_parcial      = true;
                $es_ultimo_abierto = true;
            }
            $e_t         = max(0.0, $e_t_bruto - $dev_t);
            $cruce_ciclo = ($e_t_bruto > 0.0 && $dev_t / $e_t_bruto >= 0.9 && $v_t < 0.001);
            $lotes[] = [
                'idx'              => $i,
                'fecha_ini'        => $fi_lot,
                'fecha_fin'        => $ff_mov,
                'E_t'              => $e_t,
                'E_t_bruto'        => $e_t_bruto,
                'dev_t'            => round($dev_t, 4),
                'dev_carryback'    => 0.0,
                'V_t'              => $v_t,
                'S_t'              => $e_t - $v_t,
                'dias'             => (int)(strtotime($ff_mov) - strtotime($fi_lot)) / 86400 + 1,
                'es_ultimo_abierto' => $es_ultimo_abierto,
                'cruce_ciclo'      => $cruce_ciclo,
                'v_t_parcial'      => $v_t_parcial,
            ];
        }

        // ── Devolucion carryback: propagar exceso de devolución al lote anterior ─────
        // Cuando dev_t > E_t_bruto (devolución posterior mayor que la entrada del mismo
        // lote), el exceso de devolución se traslada al lote inmediatamente anterior para
        // reajustar su E_t. Esto cubre el escenario de albaranes erróneos que se registran
        // en un periodo y se devuelven creando un lote nuevo:
        //   ej: entrada 04-26 (+5.2) + entrada 04-30 (+5.5) + devolución 05-02 (−11.2).
        //   Sin carryback: lote 04-30 queda E_t=0, exceso 5.7 kg se pierde → lotes 04-25
        //   y 04-26 mantienen S_t inflados (~9.6 kg) que aparecen como merma falsa.
        //   Con carryback: exceso 5.7 se traslada a lote 04-26 → exceso 0.5 a lote 04-25
        //   → E_t_04-25 se reduce 0.5 → S_t_04-25 baja a 3.945, lote 04-26 queda E_t=0.
        for ($i = count($lotes) - 1; $i > 0; $i--) {
            $exceso = $lotes[$i]['dev_t'] - $lotes[$i]['E_t_bruto'];
            if ($exceso <= 0.001) continue;
            // Acumular carryback en el lote anterior
            $lotes[$i - 1]['dev_t']       += $exceso;
            $lotes[$i - 1]['dev_carryback'] = ($lotes[$i - 1]['dev_carryback'] ?? 0.0) + $exceso;
            // Recalcular E_t y S_t del lote anterior
            $e_nuevo = max(0.0, $lotes[$i - 1]['E_t_bruto'] - $lotes[$i - 1]['dev_t']);
            $lotes[$i - 1]['E_t']  = $e_nuevo;
            $lotes[$i - 1]['S_t']  = $e_nuevo - $lotes[$i - 1]['V_t'];
            // Recalcular cruce_ciclo del lote anterior
            $lotes[$i - 1]['cruce_ciclo'] = (
                $lotes[$i - 1]['E_t_bruto'] > 0.0 &&
                $lotes[$i - 1]['dev_t'] / $lotes[$i - 1]['E_t_bruto'] >= 0.9 &&
                $lotes[$i - 1]['V_t'] < 0.001
            );
        }

        // ── Merge lotes vacíos: E_t=0 + V_t≈0 → extender lote anterior ─────────────
        // Tras el carryback, un lote puede quedar con E_t=0 y sin ventas (V_t≈0):
        // es una recepción errónea completamente anulada por devolución posterior.
        // Si se deja como lote separado, el backstaging lo trata como "sumidero":
        // absorbe parte del déficit del siguiente lote, pero su S_t negativo resultante
        // no se procesa (no estaba en lotes_deficit al inicio) → la merma de lotes
        // anteriores queda sobreestimada. Eliminarlo como frontera de lote y extender
        // el anterior corrige el flujo del backstaging.
        $lotes_merged = [];
        foreach ($lotes as $lot) {
            if (
                !empty($lotes_merged)
                && $lot['E_t'] < 0.001
                && $lot['V_t'] < 0.001
                && !($lot['es_ultimo_abierto'] ?? false)
            ) {
                // Extender el lote anterior en fecha y días; E_t/V_t/S_t no cambian
                $prev = &$lotes_merged[count($lotes_merged) - 1];
                $prev['fecha_fin'] = $lot['fecha_fin'];
                $prev['dias']     += $lot['dias'];
            } else {
                $lotes_merged[] = $lot;
            }
        }
        unset($prev);
        foreach ($lotes_merged as $k => &$l) {
            $l['idx'] = $k;
        }
        unset($l);
        $lotes = $lotes_merged;

        return $lotes;
    }

    /**
     * C9 — LIFO inverso con backstaging exponencial ponderado por distancia temporal.
     *
     * Para cada lote con S_t < 0 (sobreventa), redistribuye el déficit hacia los k
     * lotes anteriores con pesos exponenciales w_i = exp(-beta * delta_t_i), bloqueando
     * lotes cuyo intervalo supera mu + lambda*sigma de los intervalos del periodo.
     *
     * @param  array  $lotes   Salida de _calcularLotesC9 (S_t mutable)
     * @param  int    $k       Profundidad de backstaging (nº de lotes previos)
     * @param  float  $beta    Tasa de decaimiento exponencial
     * @param  float  $lambda  Multiplicador para umbral de continuidad temporal
     * @return array  ['lotes' => array, 'trace' => array, 'n_deficit' => int]
     */
    private function _backstagingExponencial(
        array $lotes,
        int   $k,
        float $beta,
        float $lambda
    ): array {
        $n = count($lotes);
        if ($n === 0) return ['lotes' => [], 'trace' => [], 'n_deficit' => 0];

        // Intervalos entre inicios de lotes consecutivos (días)
        $deltas = [];
        for ($i = 1; $i < $n; $i++) {
            $deltas[$i] = (int)((strtotime($lotes[$i]['fecha_ini']) - strtotime($lotes[$i - 1]['fecha_ini'])) / 86400);
        }

        $trace     = [];
        $n_deficit = 0;

        // Pre-computar qué lotes tienen déficit inicial (antes de cualquier redistribución)
        $lotes_deficit = [];
        for ($i = $n - 1; $i >= 0; $i--) {
            if ($lotes[$i]['S_t'] < 0.0) $lotes_deficit[] = $i;
        }

        // Helper: estadísticas de una ventana de intervalos (mu, sigma)
        $stats_ventana = static function (array $deltas, int $t, int $k): array {
            $win_ini = max(1, $t - $k - 1);
            $win_fin = $t; // deltas[$t] = intervalo entre lote t-1 y t
            $window  = [];
            for ($i = $win_ini; $i <= $win_fin; $i++) {
                if (isset($deltas[$i])) $window[] = $deltas[$i];
            }
            if (empty($window)) return ['mu' => PHP_INT_MAX, 'sigma' => 0.0];
            $mu  = array_sum($window) / count($window);
            $sq  = array_map(fn($d) => ($d - $mu) ** 2, $window);
            $sig = count($window) > 1 ? sqrt(array_sum($sq) / count($window)) : 0.0;
            return ['mu' => $mu, 'sigma' => $sig];
        };

        // LIFO inverso: solo procesar lotes con déficit original (no cascadas)
        foreach ($lotes_deficit as $t) {
            if ($lotes[$t]['S_t'] >= 0.0) continue; // ya neutralizado por redistribución anterior

            $deficit = abs($lotes[$t]['S_t']);
            $n_deficit++;

            // Threshold local: mu/sigma de la ventana de k+1 intervalos antes del lote t
            $st = $stats_ventana($deltas, $t, $k);
            $threshold_local = $st['mu'] + $lambda * $st['sigma'];

            // Calcular pesos para los k lotes anteriores
            $pesos    = [];
            $sum_w    = 0.0;
            for ($j = 1; $j <= $k; $j++) {
                $prev = $t - $j;
                if ($prev < 0) break;
                // Restricción temporal: bloquear si la distancia total al lote candidato
                // supera el umbral estadístico local de continuidad
                $dist         = (int)((strtotime($lotes[$t]['fecha_ini']) - strtotime($lotes[$prev]['fecha_ini'])) / 86400);
                if ($dist > $threshold_local) continue; // bloqueado
                $w            = exp(-$beta * $dist);
                $pesos[$prev] = $w;
                $sum_w       += $w;
            }

            if ($sum_w <= 0.0) {
                // Déficit no redistribuible: merma local confirmada (no hay inventario previo).
                // Se registra en merma_bloqueada para que _clasificarMermaC9 la incluya en merma_total.
                $lotes[$t]['merma_bloqueada'] = round($deficit, 4);
                $lotes[$t]['S_t'] = 0.0;
                $trace[] = ['lote_origen' => $t, 'deficit' => round($deficit, 4), 'bloqueado' => true, 'lotes_destino' => []];
                continue;
            }

            // Redistribuir déficit con pesos normalizados
            $destinos = [];
            foreach ($pesos as $prev => $w) {
                $alpha              = $w / $sum_w;
                $redistrib          = $alpha * $deficit;
                $lotes[$prev]['S_t'] -= $redistrib;
                $destinos[]         = ['idx' => $prev, 'alpha' => round($alpha, 4), 'cantidad' => round($redistrib, 4)];
            }
            $lotes[$t]['S_t'] = 0.0;
            $trace[] = ['lote_origen' => $t, 'deficit' => round($deficit, 4), 'bloqueado' => false, 'lotes_destino' => $destinos];
        }

        return ['lotes' => $lotes, 'trace' => $trace, 'n_deficit' => $n_deficit];
    }

    /**
     * C9 — Clasifica merma por lote, calcula severidad/confianza y valida conservación.
     *
     * @param  array  $lotes        Lotes post-backstaging
     * @param  float  $stock_final  Stock contable al ff_mov (_queryStockRebobinado)
     * @param  string $tipo_fisico  'peso' o 'unidad'
     * @param  float  $epsilon      Tolerancia conservación de masa (kg)
     * @return array  Métricas de merma + detalle por lote
     */
    private function _clasificarMermaC9(
        array  $lotes,
        float  $stock_final,
        float  $stock_at_first_rec,
        string $tipo_fisico,
        float  $epsilon,
        float  $merma_declarada = 0.0   // merma_prov_decl + merma_cli_decl: salidas declaradas
        // que no están en V_t del timeline → ajuste de conservación
    ): array {
        $merma_total       = 0.0;
        $merma_carryover   = 0.0;  // S_t positivo de lotes inciertos al final (horquilla superior)
        $deficit_bloqueado = 0.0;  // Déficits no redistribuibles (sobreventa): NOT merma.
        // Indica albarán faltante, stock sin regularizar o cruce pendiente.
        $total_E           = 0.0;  // E_t solo de lotes cerrados (base del pct_merma)
        $total_E_all       = 0.0;  // E_t de todos los lotes (para pct de la horquilla)
        $n_merma           = 0;
        $n_inciertos       = 0;
        $detalle           = [];

        foreach ($lotes as $lote) {
            // es_lote_incierto: marcado por getIncidenciasC9 según umbral de continuidad vivo.
            // Incluye siempre es_ultimo_abierto. Fallback a es_ultimo_abierto para compatibilidad
            // con tests unitarios que no pasan por getIncidenciasC9.
            $es_incierto        = ($lote['es_lote_incierto'] ?? false)
                || ($lote['es_ultimo_abierto'] ?? false);
            $merma_t           = max($lote['S_t'], 0.0);
            // merma_bloqueada: déficit no redistribuible (sobreventa sin lotes previos).
            // NO es merma física — indica albarán faltante, stock no regularizado del
            // periodo anterior o cruce pendiente. Se acumula en deficit_bloqueado separado.
            $merma_bloqueada_t = $lote['merma_bloqueada'] ?? 0.0;
            $total_E_all      += $lote['E_t'];
            // Acumular déficit bloqueado independientemente de si el lote es incierto o no
            $deficit_bloqueado += $merma_bloqueada_t;
            // Lotes inciertos: carryover al siguiente periodo — sobrante no es merma confirmada.
            if (!$es_incierto) {
                $merma_total += $merma_t;   // ← sin merma_bloqueada_t: déficit ≠ merma
                $total_E     += $lote['E_t'];
                if ($merma_t > 0.0) $n_merma++;
            } else {
                $n_inciertos++;
                $merma_carryover += $merma_t;  // ← sin merma_bloqueada_t
            }
            $detalle[] = [
                'idx'              => $lote['idx'],
                'fecha_ini'        => $lote['fecha_ini'],
                'fecha_fin'        => $lote['fecha_fin'],
                'E_t'              => round($lote['E_t'], 3),
                'V_t'              => round($lote['V_t'], 3),
                'S_t'              => round($lote['S_t'], 3),
                'merma_t'          => round($merma_t, 3),
                'deficit_bloqueado' => round($merma_bloqueada_t, 3),
                'v_t_parcial'      => $lote['v_t_parcial'] ?? false,
                'es_lote_incierto' => $es_incierto,
            ];
        }

        // Conservación de masa ajustada:
        //   sum(S_t_after) + merma_bloqueada_total − merma_declarada ≈ Sf − Si
        //
        // Dos correcciones necesarias respecto al Δcons ingenuo:
        //
        // 1. merma_bloqueada: déficits de lote 0 sin lotes previos que los absorban.
        //    Tras el backstaging su S_t pasa a 0 (se mueve a 'merma_bloqueada'), por lo que
        //    sum(S_t_after) sube artificialmente en ese importe. Sumarlo restaura la masa.
        //
        // 2. merma_declarada (REGULARIZACION, merma especial): salidas físicas de stock
        //    registradas en albaranes especiales que NO aparecen en el timeline V_t.
        //    El modelo las detecta como S_t > 0 en el lote correspondiente (la entrada
        //    no se consumió por ventas regulares, sino por la regularización), por lo que
        //    sum(S_t_before) = Sf − Si + merma_declarada.  Restándola el Δcons cae a ~0
        //    cuando la única "anomalía" son regularizaciones documentadas.
        //
        // stock_final < 0 NO se clampea: el Δcons resultante será grande y delta_critico=true
        // lo captura, degradando la confianza a 'posible'.
        // Corrección 1 — merma_bloqueada:
        //   Cuando un déficit de lote 0 no tiene lotes previos que lo absorban (BUG-H fix),
        //   S_t pasa de −D a 0 y se registra en 'merma_bloqueada'. Eso infla sum(S_t_after)
        //   en D. Restarlo restaura la conservación.
        // Corrección 2 — merma_declarada:
        //   Las regularizaciones (REGULARIZACION FRUTERIA, etc.) son salidas físicas de stock
        //   que NO pasan por el timeline V_t. Aparecen como S_t positivo en el lote correspondiente
        //   (la entrada no se consumió por ventas regulares, sino por la declaración de merma).
        //   Esto infla sum(S_t) en merma_declarada. Restándolo el Δcons cae a ~0 cuando la
        //   única "anomalía" son regularizaciones perfectamente documentadas.
        $merma_bloqueada_total = array_sum(array_column($lotes, 'merma_bloqueada'));
        $sum_s              = array_sum(array_column($lotes, 'S_t'));
        $sum_s_ajustado     = $sum_s - $merma_bloqueada_total - $merma_declarada;
        $conservation_delta = abs($sum_s_ajustado - ($stock_final - $stock_at_first_rec));
        $conservation_ok    = $conservation_delta <= $epsilon;

        $pct = ($total_E > 0.0) ? ($merma_total / $total_E * 100.0) : 0.0;
        $n   = count($lotes);

        // Severidad base: calculada con merma confirmada (lotes cerrados) y su pct.
        // Severidad horquilla: calculada con merma_total + merma_carryover y pct sobre total_E_all.
        // Se usa el mayor nivel entre ambas para no subestimar mermas estacionales cuyo
        // ciclo no cerró todavía (e.g., merma concentrada en los últimos meses del periodo).
        $merma_max  = $merma_total + $merma_carryover;
        $pct_max    = ($total_E_all > 0.0) ? ($merma_max / $total_E_all * 100.0) : 0.0;

        $calc_sev = static function (float $m, float $p, string $tipo): int {
            if ($tipo === 'peso') {
                if ($m >= 15.0 && $p >= 15.0) return 5;
                elseif ($m >=  8.0 && $p >= 10.0) return 4;
                elseif ($m >=  3.0 && $p >=  5.0) return 3;
                elseif ($m >=  1.0 && $p >=  2.0) return 2;
                else                                return 1;
            } else {
                if ($m >= 20.0 && $p >= 15.0) return 5;
                elseif ($m >= 10.0 && $p >= 10.0) return 4;
                elseif ($m >=  5.0 && $p >=  5.0) return 3;
                elseif ($m >=  2.0 && $p >=  2.0) return 2;
                else                                return 1;
            }
        };
        $sev     = $calc_sev($merma_total, $pct, $tipo_fisico);
        $sev_max = $calc_sev($merma_max, $pct_max, $tipo_fisico);
        $sev     = max($sev, $sev_max);

        $sev_labels = [1 => 'BAJA', 2 => 'BAJA', 3 => 'MEDIA', 4 => 'ALTA', 5 => 'CRITICA'];

        // Confianza
        // delta_critico: la confianza cae a 'posible' cuando:
        //   a) Δcons ajustado > 5×epsilon → datos base inconsistentes (rebobinado incorrecto)
        //   b) stock_final < 0 → físicamente imposible, indica rebobinado erróneo
        $delta_critico = $conservation_delta > $epsilon * 5.0 || $stock_final < 0.0;
        if (!$delta_critico && $conservation_ok && $n >= 5) $confianza = 'alta';
        elseif (!$delta_critico && ($conservation_ok || $n >= 3)) $confianza = 'media';
        else                                                        $confianza = 'posible';

        return [
            'merma_total'        => round($merma_total, 3),
            'merma_carryover'    => round($merma_carryover, 3),  // carryover lotes inciertos (horquilla superior)
            'deficit_bloqueado'  => round($deficit_bloqueado, 3), // sobreventa no redistribuible: albarán faltante / stock no regularizado
            'n_lotes_inciertos'  => $n_inciertos,
            'pct_merma'          => round($pct, 2),
            'n_merma'            => $n_merma,
            'detalle_lotes'      => $detalle,
            'conservation_ok'    => $conservation_ok,
            'conservation_delta' => round($conservation_delta, 4),
            'severidad'          => $sev,
            'severidad_label'    => $sev_labels[$sev],
            'confianza'          => $confianza,
            'total_E'            => round($total_E, 3),
        ];
    }

    /**
     * C9 — Merma por backstaging LIFO inverso con ponderación exponencial temporal.
     *
     * Orquesta: Q1 (recepciones sin proveedores especiales) → Q2 (timeline limpio) →
     * Q5a (prov especiales: cruce vs merma declarada) → Q5b (cli especiales: merma
     * declarada) → lotes → backstaging → clasificación.
     *
     * Solo aplica a artículos con tipo_fisico IN ('unidad','peso').
     *
     * @return array  Incidencias C9 o ['error' => ...]
     */
    private function getIncidenciasC9(
        string $fi_mov,
        string $ff_mov,
        string $fi_stock,
        array  $familias_incluir,
        array  $familias_excluir,
        array  $ids_filter        = [],
        array  $stock_base_cache  = [],
        int    $c9_k              = 4,
        float  $c9_beta           = 0.15,
        float  $c9_lambda         = 1.5,
        float  $c9_epsilon        = 1.0,
        int    $c9_min_rec        = 3,
        float  $c9_umbral_unidad  = 2.0,
        float  $c9_umbral_peso    = 1.0,
        int    $c9_dias_post      = 60
    ): array {
        $fi   = $this->db->real_escape_string($fi_mov);
        $ff   = $this->db->real_escape_string($ff_mov);
        $wf   = $this->_familiaWhere($familias_incluir, $familias_excluir);
        $wi   = $this->_idsWhere($ids_filter);
        // ── Paso 1: recepciones (Q1) ────────────────────────────────────────
        $rows_rec = $this->_queryRecepcionesC9($fi, $ff_mov, $wf, $wi, $c9_dias_post);
        if (isset($rows_rec['error'])) return $rows_rec;
        if (empty($rows_rec)) return [];

        // Agrupar por artículo; filtrar por n_recepciones mínimo en-periodo
        $recepciones_map = [];
        foreach ($rows_rec as $r) {
            $aid = (int)$r['idArticulo'];
            $recepciones_map[$aid][] = [
                'fecha'           => $r['fecha'],
                'cantidad'        => (float)$r['cantidad'],
                'es_post_periodo' => (bool)$r['es_post_periodo'],
            ];
        }
        $ids_candidatos = [];
        foreach ($recepciones_map as $aid => $recs) {
            $n_periodo = count(array_filter($recs, fn($r) => !$r['es_post_periodo']));
            if ($n_periodo >= $c9_min_rec) $ids_candidatos[] = $aid;
        }
        if (empty($ids_candidatos)) return [];

        $ids_str = implode(',', array_map('intval', $ids_candidatos));

        // ── Paso 2: filtrar solo artículos físicos ──────────────────────────
        $res_art = $this->db->query(
            "SELECT idArticulo, tipo FROM articulos
              WHERE idArticulo IN ($ids_str)"
        );
        if (!$res_art) return ['error' => 'C9 tipos: ' . $this->db->error];
        $tipos_map = [];
        while ($row = $res_art->fetch_assoc()) $tipos_map[(int)$row['idArticulo']] = $row['tipo'];
        $res_art->free();
        $ids_candidatos = array_values(array_filter($ids_candidatos, fn($id) => isset($tipos_map[$id])));
        if (empty($ids_candidatos)) return [];
        $ids_str = implode(',', array_map('intval', $ids_candidatos));

        // ── Paso 1b: devoluciones ordinarias (nunidades < 0, proveedor no especial) ──
        $ff_post_dev = $this->db->real_escape_string(
            date('Y-m-d', strtotime("$ff_mov +$c9_dias_post days"))
        );
        $rows_dev = $this->_queryDevolucionesProvC9($fi, $ff_post_dev, $ids_str);
        if (isset($rows_dev['error'])) return $rows_dev;
        $devs_map = [];
        foreach ($rows_dev as $r) {
            $devs_map[(int)$r['idArticulo']][] = ['fecha' => $r['fecha'], 'devolucion' => (float)$r['devolucion']];
        }

        // ── Paso 3: timeline limpio (Q2) — extendido hasta ff_post para capturar
        //    ventas post-periodo del último lote (hasta primera_post - 1) ────────
        $ff_post_tl = $this->db->real_escape_string(
            date('Y-m-d', strtotime("$ff_mov +$c9_dias_post days"))
        );
        $rows_tl = $this->_queryTimelineC9($fi, $ff_post_tl, $ids_str);
        if (isset($rows_tl['error'])) return $rows_tl;
        $timeline_map = [];
        foreach ($rows_tl as $r) {
            $timeline_map[(int)$r['idArticulo']][] = ['fecha' => $r['fecha'], 'day_delta' => (float)$r['day_delta']];
        }

        // ── Paso 4: albaranes especiales (Q5a + Q5b) ───────────────────────
        $ff_post = $this->db->real_escape_string(
            date('Y-m-d', strtotime("$ff_mov +$c9_dias_post days"))
        );
        // Los albaranes especiales (merma declarada) se acotan al periodo fi_mov..ff_mov,
        // no a ff_post: las regularizaciones post-periodo pertenecen al siguiente análisis.
        $rows_prov_esp = $this->_queryAlbaranesProvEspecialesC9($fi, $ff, $ids_str);
        if (isset($rows_prov_esp['error'])) return $rows_prov_esp;
        $rows_cli_esp  = $this->_queryAlbaranesCliEspecialesC9($fi, $ff, $ids_str);
        if (isset($rows_cli_esp['error'])) return $rows_cli_esp;

        // Clasificar albaranes proveedor especial: cruce intra-albarán vs merma declarada
        // Paso A: separar intra-albarán cruces (signos mixtos POR ARTÍCULO) de candidatos negativos.
        // Se evalúa por artículo, no por albarán global: un albarán puede tener líneas positivas
        // para otros artículos (p.e. regularizaciones de múltiples artículos) sin que eso invalide
        // las líneas negativas del artículo analizado.
        $alb_prov = [];
        foreach ($rows_prov_esp as $r) $alb_prov[(int)$r['idAlbaran']][] = $r;
        $candidatos_decl = []; // [aid][fecha] => monto absoluto
        foreach ($alb_prov as $lineas) {
            // Detectar signos mixtos por artículo dentro del albarán
            $signos_art = [];
            foreach ($lineas as $l) {
                $aid = (int)$l['idArticulo'];
                $nunidades = (float)$l['nunidades'];
                if ($nunidades > 0) $signos_art[$aid]['pos'] = true;
                if ($nunidades < 0) $signos_art[$aid]['neg'] = true;
            }
            foreach ($lineas as $l) {
                $aid   = (int)$l['idArticulo'];
                $nunidades = (float)$l['nunidades'];
                // Cruce intra-albarán: este artículo tiene signos mixtos en el mismo albarán → ignorar
                if (!empty($signos_art[$aid]['pos']) && !empty($signos_art[$aid]['neg'])) continue;
                $fecha = $l['fecha'];
                if ($nunidades > 0) {
                    // Proveedor especial positivo = entrada normal: añadir a recepciones_map
                    // como si fuera un proveedor ordinario.
                    $ya_existe = false;
                    if (isset($recepciones_map[$aid])) {
                        foreach ($recepciones_map[$aid] as &$_rec) {
                            if ($_rec['fecha'] === $fecha) {
                                $_rec['cantidad'] += $nunidades;
                                $ya_existe = true;
                                break;
                            }
                        }
                        unset($_rec);
                    }
                    if (!$ya_existe) {
                        $recepciones_map[$aid][] = [
                            'fecha'           => $fecha,
                            'cantidad'        => $nunidades,
                            'es_post_periodo' => false,
                        ];
                        // Re-ordenar por fecha para mantener coherencia
                        usort($recepciones_map[$aid], fn($a, $b) => strcmp($a['fecha'], $b['fecha']));
                    }
                } else {
                    // Proveedor especial negativo = merma declarada o cruce (Paso B)
                    $candidatos_decl[$aid][$fecha] = ($candidatos_decl[$aid][$fecha] ?? 0.0)
                        + abs($nunidades);
                }
            }
        }

        // Paso B: detectar cruces cross-albarán.
        // Si en la misma fecha hay una recepción regular para el mismo artículo,
        // el negativo especial es una devolución del ciclo anterior al proveedor
        // que trae stock nuevo: netear E_t de la recepción, NO merma_declarada.
        $merma_prov_decl     = [];
        $merma_prov_decl_mes = []; // [aid][mes(1-12)] => kg acumulado
        foreach ($candidatos_decl as $aid => $fechas) {
            foreach ($fechas as $fecha => $monto) {
                $recs_aid = $recepciones_map[$aid] ?? [];
                $rec_idx  = null;
                foreach ($recs_aid as $idx => $rec) {
                    if ($rec['fecha'] === $fecha) {
                        $rec_idx = $idx;
                        break;
                    }
                }
                if ($rec_idx !== null) {
                    // Cross-albarán cruce: reducir E_t neto de la recepción
                    $recepciones_map[$aid][$rec_idx]['cantidad']   -= $monto;
                    $recepciones_map[$aid][$rec_idx]['cruce_cross'] = true;
                    if ($recepciones_map[$aid][$rec_idx]['cantidad'] <= 0.0) {
                        unset($recepciones_map[$aid][$rec_idx]);
                        $recepciones_map[$aid] = array_values($recepciones_map[$aid]);
                    }
                } else {
                    // Sin recepción regular ese día → merma declarada normal
                    $merma_prov_decl[$aid] = ($merma_prov_decl[$aid] ?? 0.0) + $monto;
                    $_mes_pd = (int)substr($fecha, 5, 2);
                    $merma_prov_decl_mes[$aid][$_mes_pd] = ($merma_prov_decl_mes[$aid][$_mes_pd] ?? 0.0) + $monto;
                }
            }
        }
        // Clasificar albaranes cliente especial: misma lógica Paso A/B que proveedor.
        // En albaranes cliente: nunidades > 0 = salida de stock (merma/venta especial),
        //                       nunidades < 0 = entrada de stock (devolución/regularización).
        $alb_cli = [];
        foreach ($rows_cli_esp as $r) $alb_cli[(int)$r['idAlbaran']][] = $r;
        $merma_cli_decl      = [];
        $merma_cli_decl_mes  = []; // [aid][mes(1-12)] => kg acumulado
        $entradas_cli_esp = []; // [aid][fecha] => monto absoluto (nunidades < 0 sin cruce)
        foreach ($alb_cli as $lineas) {
            // Paso A: detectar signos mixtos por artículo dentro del albarán
            $signos_art = [];
            foreach ($lineas as $l) {
                $aid   = (int)$l['idArticulo'];
                $nunidades = (float)$l['nunidades'];
                if ($nunidades > 0) $signos_art[$aid]['pos'] = true;
                if ($nunidades < 0) $signos_art[$aid]['neg'] = true;
            }
            foreach ($lineas as $l) {
                $aid   = (int)$l['idArticulo'];
                $nunidades = (float)$l['nunidades'];
                if (!empty($signos_art[$aid]['pos']) && !empty($signos_art[$aid]['neg'])) continue;
                $fecha = $l['fecha'];
                if ($nunidades > 0) {
                    // Salida especial → merma declarada
                    $merma_cli_decl[$aid] = ($merma_cli_decl[$aid] ?? 0.0) + $nunidades;
                    $_mes_cd = (int)substr($fecha, 5, 2);
                    $merma_cli_decl_mes[$aid][$_mes_cd] = ($merma_cli_decl_mes[$aid][$_mes_cd] ?? 0.0) + $nunidades;
                } else {
                    // Entrada especial → candidato a neta V_t o entrada directa
                    $entradas_cli_esp[$aid][$fecha] = ($entradas_cli_esp[$aid][$fecha] ?? 0.0)
                        + abs($nunidades);
                }
            }
        }
        // Paso B cliente: si hay ventas regulares el mismo día en timeline, neta V_t;
        // si no, añadir como entrada a recepciones_map.
        foreach ($entradas_cli_esp as $aid => $fechas) {
            foreach ($fechas as $fecha => $monto) {
                $tl_aid = $timeline_map[$aid] ?? [];
                $tl_idx = null;
                foreach ($tl_aid as $idx => $tl) {
                    if ($tl['fecha'] === $fecha) {
                        $tl_idx = $idx;
                        break;
                    }
                }
                if ($tl_idx !== null) {
                    // Cross-albarán: neta V_t del timeline
                    $timeline_map[$aid][$tl_idx]['day_delta'] -= $monto;
                    if ($timeline_map[$aid][$tl_idx]['day_delta'] <= 0.0) {
                        unset($timeline_map[$aid][$tl_idx]);
                        $timeline_map[$aid] = array_values($timeline_map[$aid]);
                    }
                } else {
                    // Sin ventas regulares ese día.
                    //
                    // Los albaranes especiales negativos de clientes son mayoritariamente
                    // conciliaciones de un periodo ya pasado (corrección de stock, devolución
                    // tardía, ajuste de inventario). Si se tratan como nueva recepción crean
                    // un lote ficticio con E_t=monto y V_t≈0, lo que genera merma artificial.
                    //
                    // Estrategia: si ya existe una recepción ese mismo día (normal o especial),
                    // añadir al E_t de esa recepción. Si no existe, buscar la recepción más
                    // reciente anterior a $fecha y añadir ahí (conciliación del lote activo).
                    // Solo si no hay ninguna recepción previa en el periodo se añade como nueva
                    // entrada (inicio de periodo sin historial).
                    $ya_existe    = false;
                    $prev_rec_idx = null;
                    if (isset($recepciones_map[$aid])) {
                        foreach ($recepciones_map[$aid] as $ridx => &$_rec) {
                            if ($_rec['fecha'] === $fecha && !($_rec['es_post_periodo'] ?? false)) {
                                // Recepción en la misma fecha: añadir directamente
                                $_rec['cantidad'] += $monto;
                                $ya_existe = true;
                                break;
                            }
                            if ($_rec['fecha'] <= $fecha && !($_rec['es_post_periodo'] ?? false)) {
                                // Candidato a recepción previa (la más reciente gana)
                                if (
                                    $prev_rec_idx === null
                                    || $_rec['fecha'] >= $recepciones_map[$aid][$prev_rec_idx]['fecha']
                                ) {
                                    $prev_rec_idx = $ridx;
                                }
                            }
                        }
                        unset($_rec);
                    }
                    if (!$ya_existe) {
                        if ($prev_rec_idx !== null) {
                            // Conciliación: añadir al E_t del lote activo en $fecha
                            $recepciones_map[$aid][$prev_rec_idx]['cantidad'] += $monto;
                        } elseif (isset($recepciones_map[$aid])) {
                            // Sin recepción previa en el periodo: añadir como nueva entrada
                            $recepciones_map[$aid][] = [
                                'fecha'           => $fecha,
                                'cantidad'        => $monto,
                                'es_post_periodo' => false,
                            ];
                            usort($recepciones_map[$aid], fn($a, $b) => strcmp($a['fecha'], $b['fecha']));
                        }
                    }
                }
            }
        }

        // ── Paso 5: stock base (compartido con C7 en batch) ────────────────
        $sb = empty($stock_base_cache)
            ? $this->getStockBase($ids_candidatos, $fi_stock, $fi_mov)
            : $stock_base_cache;
        if (isset($sb['error'])) return $sb;

        // ── Paso 5b: stock real al inicio del periodo (rebobinado) ──────────
        // Se usa como E_lote0 en lugar del saldo acumulado desde fi_stock.
        // _queryStockRebobinado incluye TODO el histórico (no solo desde fi_stock),
        // capturando inventario de años anteriores no reflejado en getStockBase.
        $fi_reb = $this->db->real_escape_string(
            date('Y-m-d', strtotime("$fi_mov -1 day"))
        );
        $rows_si = $this->_queryStockRebobinado($ids_str, $fi_reb);
        if (isset($rows_si['error'])) return $rows_si;
        $stock_inicial_map = [];
        foreach ($rows_si as $r) {
            $stock_inicial_map[(int)$r['idArticulo']] = (float)$r['stock_en_periodo'];
        }

        // ── Paso 6: stock rebobinado al ff_mov (ancla conservación) ────────
        $rows_sf = $this->_queryStockRebobinado($ids_str, $ff);
        if (isset($rows_sf['error'])) return $rows_sf;
        $stock_final_map = [];
        foreach ($rows_sf as $r) $stock_final_map[(int)$r['idArticulo']] = (float)$r['stock_en_periodo'];

        // ── Paso 7: loop por artículo ───────────────────────────────────────
        $incidencias = [];

        foreach ($ids_candidatos as $idArticulo) {
            $tipo_fisico  = $tipos_map[$idArticulo];
            // E_lote0: stock real al inicio del periodo (rebobinado historial completo).
            // Fallback al saldo acumulado desde fi_stock si el rebobinado no devuelve fila.
            $stock_base   = $stock_inicial_map[$idArticulo]
                ?? (float)($sb[$idArticulo]['saldo_acumulado'] ?? 0.0);
            $stock_final  = $stock_final_map[$idArticulo] ?? 0.0;
            $timeline_art = $timeline_map[$idArticulo] ?? [];
            $recs_art     = $recepciones_map[$idArticulo] ?? [];

            $devs_art = $devs_map[$idArticulo] ?? [];
            $lotes = $this->_calcularLotesC9($recs_art, $timeline_art, $fi_mov, $ff_mov, $devs_art);
            if (empty($lotes)) continue;

            // Stock en el momento de la primera recepción del periodo.
            // Las ventas entre fi_mov y esa fecha son consumo del stock heredado
            // y no forman parte de ningún lote — se descuentan del ancla inicial
            // para que la conservación de masa sea coherente con el modelo sin lote 0.
            $first_rec_date    = $lotes[0]['fecha_ini'];
            $stock_at_first_rec = $stock_base;
            if ($first_rec_date > $fi_mov) {
                $pre_end = date('Y-m-d', strtotime($first_rec_date . ' -1 day'));
                foreach ($timeline_art as $_tl) {
                    if ($_tl['fecha'] >= $fi_mov && $_tl['fecha'] <= $pre_end) {
                        $stock_at_first_rec -= (float)$_tl['day_delta'];
                    }
                }
            }

            // ── Marcar lotes inciertos al final del periodo ──────────────────
            // Un lote es incierto si su ciclo puede no haber cerrado todavía.
            // Se aplica el umbral de continuidad local (mu + lambda * sigma) en DOS casos:
            //
            //   A) Corte de DB / fin de periodo sin datos posteriores:
            //      existe al menos un lote con es_ultimo_abierto=true.
            //      Justificación: si la DB termina en ff_mov, los lotes cuya fecha_ini
            //      cae dentro del umbral de continuidad ANTES de ff_mov tuvieron su ciclo
            //      natural cortado por el límite del periodo, no por una recepción real.
            //      Se aplica siempre, incluso para análisis históricos.
            //
            //   B) Análisis "en vivo" (hoy dentro de la ventana dias_post):
            //      la primera recepción que cerraría los últimos lotes aún no ha llegado.
            //      Se aplica aunque todos los lotes parezcan cerrados.
            $hoy_str  = date('Y-m-d');
            $ff_post_continuidad = date('Y-m-d', strtotime("$ff_mov +$c9_dias_post days"));
            $es_vivo         = ($hoy_str <= $ff_post_continuidad);
            $hay_abierto     = !empty(array_filter($lotes, fn($l) => !empty($l['es_ultimo_abierto'])));
            $aplicar_umbral  = $es_vivo || $hay_abierto;

            if ($aplicar_umbral && count($lotes) >= 2) {
                $n_lots = count($lotes);
                // Intervalos inter-lote (días entre fechas_ini consecutivas)
                $intervals = [];
                for ($i = 1; $i < $n_lots; $i++) {
                    $intervals[] = (int)round(
                        (strtotime($lotes[$i]['fecha_ini']) - strtotime($lotes[$i - 1]['fecha_ini'])) / 86400
                    );
                }
                // Ventana local: últimos min(k+1, n-1) intervalos
                $ventana = array_slice($intervals, -min($c9_k + 1, count($intervals)));
                $n_v     = count($ventana);
                $mu_v    = $n_v > 0 ? array_sum($ventana) / $n_v : 0.0;
                $var_v   = 0.0;
                foreach ($ventana as $d) $var_v += ($d - $mu_v) ** 2;
                $sigma_v         = $n_v > 1 ? sqrt($var_v / ($n_v - 1)) : 0.0;
                $umbral_cont     = max(1.0, $mu_v + $c9_lambda * $sigma_v);
                $ff_ts           = strtotime($ff_mov);
                // Recorrer desde el último lote hacia atrás
                for ($i = $n_lots - 1; $i >= 0; $i--) {
                    $gap = ($ff_ts - strtotime($lotes[$i]['fecha_ini'])) / 86400;
                    if ($gap <= $umbral_cont) {
                        $lotes[$i]['es_lote_incierto'] = true;
                    } else {
                        break; // ya fuera del umbral de continuidad
                    }
                }
            }
            // Propagar: es_lote_incierto incluye siempre es_ultimo_abierto
            foreach ($lotes as &$_lot) {
                if (!isset($_lot['es_lote_incierto'])) {
                    $_lot['es_lote_incierto'] = !empty($_lot['es_ultimo_abierto']);
                }
            }
            unset($_lot);

            $resultado = $this->_backstagingExponencial($lotes, $c9_k, $c9_beta, $c9_lambda);

            $umbral     = ($tipo_fisico === 'peso') ? $c9_umbral_peso : $c9_umbral_unidad;
            $merma_decl = ($merma_prov_decl[$idArticulo] ?? 0.0) + ($merma_cli_decl[$idArticulo] ?? 0.0);
            // Construir desglose mensual de la merma declarada (proveedor + cliente especiales)
            $_merma_decl_mes = [];
            foreach (($merma_prov_decl_mes[$idArticulo] ?? []) as $_mpm => $_vpd) {
                $_merma_decl_mes[$_mpm] = ($_merma_decl_mes[$_mpm] ?? 0.0) + $_vpd;
            }
            foreach (($merma_cli_decl_mes[$idArticulo] ?? []) as $_mcm => $_vcd) {
                $_merma_decl_mes[$_mcm] = ($_merma_decl_mes[$_mcm] ?? 0.0) + $_vcd;
            }
            ksort($_merma_decl_mes);
            $clasif = $this->_clasificarMermaC9(
                $resultado['lotes'],
                $stock_final,
                $stock_at_first_rec,
                $tipo_fisico,
                $c9_epsilon,
                $merma_decl
            );
            if (($clasif['merma_total'] + $clasif['merma_carryover']) < $umbral) continue;
            $n_en_periodo = count(array_filter($recs_art, fn($r) => !$r['es_post_periodo']));

            $incidencias[] = [
                'caso'                => 'C9',
                'tipo'                => 'Merma backstaging',
                'idArticulo'          => $idArticulo,
                'merma_total_kg'      => $clasif['merma_total'],
                'merma_carryover_kg'  => $clasif['merma_carryover'],   // lotes inciertos: horquilla superior
                'deficit_bloqueado_kg' => $clasif['deficit_bloqueado'], // sobreventa no redistribuible
                'n_lotes_inciertos'   => $clasif['n_lotes_inciertos'],
                'merma_declarada_kg'  => round($merma_decl, 3),
                'merma_decl_por_mes'  => !empty($_merma_decl_mes) ? array_map(fn($v) => round($v, 3), $_merma_decl_mes) : [],
                'pct_merma'           => $clasif['pct_merma'],
                'n_lotes'             => count($resultado['lotes']),
                'n_recepciones'       => $n_en_periodo,
                'n_lotes_deficit'     => $resultado['n_deficit'],
                'n_lotes_merma'       => $clasif['n_merma'],
                'stock_final'         => round($stock_final, 3),
                'ancla_tipo'          => 'contable',
                'conservation_ok'     => $clasif['conservation_ok'],
                'conservation_delta'  => $clasif['conservation_delta'],
                'confianza'           => $clasif['confianza'],
                'severidad'           => $clasif['severidad_label'],
                'severidad_num'       => $clasif['severidad'],
                'merma_por_lote'      => $clasif['detalle_lotes'],
                'backstaging_trace'   => $resultado['trace'],
                'beta_usado'          => $c9_beta,
                'k_usado'             => $c9_k,
                'lambda_usado'        => $c9_lambda,
                'modo'                => ($tipo_fisico === 'peso') ? 'continuo' : 'discreto',
                'total_E'             => $clasif['total_E'],
            ];
        }

        // ── C9: enriquecer con proveedor habitual y coste estimado de la merma ──
        if (!empty($incidencias)) {
            $ids_c9     = array_column($incidencias, 'idArticulo');
            $ids_c9_str = implode(',', array_map('intval', $ids_c9));
            $fi_esc     = $this->db->real_escape_string($fi);
            $ff_esc     = $this->db->real_escape_string($ff);

            $prov_map_c9   = $this->_queryProveedorArticulos($ids_c9_str, $fi_esc, $ff_esc);
            $precio_map_c9 = $this->_queryPrecioMedioCompra($ids_c9_str, $fi_esc, $ff_esc);

            foreach ($incidencias as &$inc) {
                $prov = $prov_map_c9[$inc['idArticulo']] ?? null;
                $inc['prov_habitual_nombre'] = $prov['prov_habitual_nombre'] ?? null;
                $inc['prov_habitual_n']      = $prov['prov_habitual_n']      ?? null;
                $inc['prov_ultimo_nombre']   = $prov['prov_ultimo_nombre']   ?? null;
                $inc['prov_ultima_fecha']    = $prov['prov_ultima_fecha']    ?? null;
                $inc['prov_es_mismo']        = $prov['prov_es_mismo']        ?? null;
                // Coste estimado de la merma: merma_total_kg × precio medio de compra
                $precio = $precio_map_c9[$inc['idArticulo']] ?? null;
                $inc['precio_medio_compra']  = $precio;
                $inc['coste_estimado_merma'] = ($precio !== null && ($inc['merma_total_kg'] ?? 0) > 0)
                    ? round((float)$inc['merma_total_kg'] * $precio, 2)
                    : null;
            }
            unset($inc);
        }

        return $incidencias;
    }

    public function getIncidenciasBatch(array $params, int $inicial, int $pagina): array
    {
        $fi_mov           = $params['fecha_inicio_movimientos'];
        $ff_mov           = $params['fecha_fin_movimientos'];
        $familias_incluir = (array)($params['familias_incluir'] ?? []);
        $familias_excluir = (array)($params['familias_excluir'] ?? []);
        $casos_incluir    = (array)($params['casos_incluir']    ?? []);

        // ── Resolver filtro de proveedores una sola vez para toda la paginación ──
        $proveedores_incluir      = (array)($params['proveedores_incluir']      ?? []);
        $proveedor_todos          = (bool)  ($params['proveedor_todos_productos'] ?? false);
        $ids_proveedor_filter     = [];
        $ids_proveedor_filter_c6b = [];  // C6b: todos los artículos del proveedor, sin filtro de estado
        $ids_str_prov             = '';
        if (!empty($proveedores_incluir)) {
            $ids_str_prov = implode(',', array_map('intval', $proveedores_incluir));
            if (!$proveedor_todos) {
                // Modo normal: solo artículos del proveedor con actividad en el periodo
                $rows_prov = $this->_queryIdsArticulosByProveedores($ids_str_prov);
                if (isset($rows_prov['error'])) return $rows_prov;
                $ids_proveedor_filter = array_column($rows_prov, 'idArticulo');
                if (empty($ids_proveedor_filter)) {
                    return ['filas' => [], 'actual' => $inicial, 'elementos' => 0];
                }
            }
            // C6b evalúa todos los artículos del proveedor independientemente del estado:
            // un artículo inactivo en articulosProveedores puede seguir en stock y vendiendo.
            $rows_prov_c6b = $this->_queryIdsArticulosByProveedoresTodos($ids_str_prov);
            if (!isset($rows_prov_c6b['error'])) {
                $ids_proveedor_filter_c6b = array_column($rows_prov_c6b, 'idArticulo');
            }
        }

        // C4 y C6b no son paginables por actividad en el periodo:
        //   · C4: artículos sin movimiento (nunca aparecerían en la paginación normal)
        //   · C6b: ventana fija anclada en hoy, independiente del periodo analizado
        // Ruta especial cuando son el único caso solicitado: lote único, elementos=0 → JS para
        $casos_no_paginables = ['caso4', 'caso6b'];
        $casos_sin_paginables = array_values(array_diff($casos_incluir, $casos_no_paginables));

        if (empty($casos_sin_paginables)) {
            $filas = $this->getIncidencias($params);
            if (isset($filas['error'])) return $filas;
            return ['filas' => $filas, 'actual' => count($filas), 'elementos' => 0, 'pagina_efectiva' => 0]; // elementos=0 → fin
        }

        // C7a y C7b requieren cálculo estadístico intensivo por artículo (bootstrap 999
        // iteraciones, Mann-Kendall O(n²), Theil-Sen O(n²)). Con lotes grandes el proceso
        // puede tardar varios minutos y provocar que MySQL cierre la conexión por wait_timeout.
        // Se reduce el tamaño del lote para que cada request tarde <30 s.
        // C7c/d/e se salta en estos lotes parciales: requiere el conjunto COMPLETO de
        // artículos C7a+C7b para detectar cruces; ver resolverPOSStockC7cde.php (fase 2).
        $casos_c7_activos = array_intersect($casos_incluir, ['caso7a', 'caso7b']);
        if (!empty($casos_c7_activos)) {
            // C7 usa bootstrap O(n²) por artículo — lotes más grandes que otros casos pero
            // razonables. C7cde ya corre aparte (fase 2), así que el riesgo de timeout
            // por lote es menor. 80 artículos ≈ 10–20 s de procesado estadístico.
            $pagina = min($pagina, 80);
        }

        // C9 es O(n) por artículo (backstaging lineal) pero ejecuta 5 queries extra
        // (recepciones, timeline, prov/cli especiales, stock rebobinado). Lotes de 150
        // son seguros; si se combina con C7 la reducción ya aplica.
        if (in_array('caso9', $casos_incluir, true) && !in_array('caso7a', $casos_incluir, true) && !in_array('caso7b', $casos_incluir, true)) {
            $pagina = min($pagina, 150);
        }

        // En batches mixtos, excluir C4 y C6b de la paginación;
        // C6b se añade al primer lote (inicial === 0) para que aparezca una sola vez.
        $params_batch = $params;
        $params_batch['casos_incluir'] = $casos_sin_paginables;
        // C7c/d/e necesita todos los artículos C7a+C7b: se salta en lotes parciales
        // y corre en una llamada final independiente (resolverPOSStockC7cde).
        if (!empty($casos_c7_activos)) {
            $params_batch['skip_c7_cde'] = true;
        }

        // ── Obtener IDs del lote según modo de proveedor ─────────────────────
        if ($proveedor_todos && $ids_str_prov !== '') {
            // Modo "todos los productos del proveedor": paginar directamente sobre
            // articulosProveedores, sin filtro de actividad en el periodo.
            $ids_batch = $this->_queryArticulosProveedorPaginados($ids_str_prov, $inicial, $pagina);
            if (isset($ids_batch['error'])) return $ids_batch;
            // El filtro de proveedor ya está embebido en ids_filter; no aplicar doble filtro
            $params_batch['ids_proveedor_filter'] = [];
        } else {
            // Modo normal: artículos con actividad en el periodo (intersectado con proveedor si aplica)
            $params_batch['ids_proveedor_filter'] = $ids_proveedor_filter;
            $ids_batch = $this->getArticulosConActividad(
                $fi_mov,
                $ff_mov,
                $familias_incluir,
                $familias_excluir,
                $inicial,
                $pagina,
                $ids_proveedor_filter
            );
        }

        $elementos = count($ids_batch);
        $actual    = $inicial + $elementos;

        if ($elementos === 0) {
            return ['filas' => [], 'actual' => $actual, 'elementos' => 0];
        }

        $params_batch['ids_filter'] = $ids_batch;

        $filas = $this->getIncidencias($params_batch);
        if (isset($filas['error'])) return $filas;

        // Añadir C4/C6b al primer lote únicamente (evita duplicados en lotes sucesivos)
        if ($inicial === 0) {
            $casos_extra = array_values(array_intersect($casos_incluir, $casos_no_paginables));
            if (!empty($casos_extra)) {
                $params_extra = $params;
                $params_extra['casos_incluir'] = $casos_extra;
                // C6b usa su propio conjunto de IDs (todos los artículos del proveedor, sin filtro de estado)
                if (!empty($ids_proveedor_filter_c6b)) {
                    $params_extra['ids_proveedor_filter_c6b'] = $ids_proveedor_filter_c6b;
                }
                $filas_extra = $this->getIncidencias($params_extra);
                if (!isset($filas_extra['error'])) {
                    $filas = array_merge($filas, $filas_extra);
                }
            }
        }

        return [
            'filas'          => $filas,
            'actual'         => $actual,
            'elementos'      => $elementos,
            'pagina_efectiva' => $pagina,   // el JS usa este valor para saber si hay más lotes
        ];
    }

    /**
     * Fase 2 de C7: resuelve los cruces C7c/d/e sobre el conjunto COMPLETO de artículos
     * C7a+C7b ya detectados en los lotes de la fase 1.
     *
     * OPTIMIZACIÓN: NO re-ejecuta la cascada estadística (Mann-Kendall, bootstrap,
     * Theil-Sen, Wilcoxon…). Solo necesita _floors_raw + offset + dispersión, que se
     * obtienen con 2 SQL + un bucle de cálculo de suelos. Para 5–50 artículos es
     * prácticamente instantáneo incluso en periodos trimestrales/anuales.
     *
     * @param  array  $params   Fechas + familias (fi_mov, ff_mov, fi_stock, familias_*).
     * @param  int[]  $ids_c7a  idArticulo de artículos detectados como C7a en fase 1.
     * @param  int[]  $ids_c7b  idArticulo de artículos detectados como C7b en fase 1.
     * @return array  ['cruces' => [idArticulo => [cruce fields]]] o ['error' => ...]
     */
    public function resolverC7cde(array $params, array $ids_c7a, array $ids_c7b): array
    {
        $ids_c7a = array_values(array_unique(array_map('intval', $ids_c7a)));
        $ids_c7b = array_values(array_unique(array_map('intval', $ids_c7b)));
        $ids_all = array_values(array_unique(array_merge($ids_c7a, $ids_c7b)));
        if (empty($ids_all)) return ['cruces' => []];

        $fi_mov  = $params['fecha_inicio_movimientos'];
        $ff_mov  = $params['fecha_fin_movimientos'];
        $fi_stock = $params['fecha_inicio_stock'];
        $familias_incluir = (array)($params['familias_incluir'] ?? []);
        $familias_excluir = (array)($params['familias_excluir'] ?? []);

        $fi_stk = $this->db->real_escape_string($fi_stock);
        $ff     = $this->db->real_escape_string($ff_mov);
        $wf     = $this->_familiaWhere($familias_incluir, $familias_excluir);
        $wi     = $this->_idsWhere($ids_all);

        // ── SQL 1: recepciones en la ventana fi_stock→ff_mov ─────────────────
        $rows_rec = $this->_queryRecepcionesFechasC7($fi_stk, $ff, $wf, $wi);
        if (isset($rows_rec['error'])) return $rows_rec;
        if (empty($rows_rec)) return ['cruces' => []];

        $recepciones_map = [];
        foreach ($rows_rec as $r) {
            $recepciones_map[(int)$r['idArticulo']][] = $r['fecha'];
        }
        foreach ($recepciones_map as $id => $fechas) {
            $u = array_values(array_unique($fechas));
            sort($u);
            $recepciones_map[$id] = $u;
        }

        // ── SQL 2: timeline de movimientos ───────────────────────────────────
        $ids_str = implode(',', $ids_all);
        $rows_tl = $this->_queryTimelineMovimientosC7($fi_stk, $ff, $ids_str);
        if (isset($rows_tl['error'])) return $rows_tl;

        $daily_map = [];
        foreach ($rows_tl as $r) {
            $daily_map[(int)$r['idArticulo']][$r['fecha']] = (float)$r['day_delta'];
        }

        // ── Construir incidencias mínimas para C7c/d/e ───────────────────────
        // Solo se necesita: idArticulo, c7_subcaso, _floors_raw, offset_estimado, dispersion.
        // No se ejecuta ninguna cascada estadística.
        $ids_c7a_set = array_flip($ids_c7a);
        $ids_c7b_set = array_flip($ids_c7b);
        $incidencias_cde = [];

        foreach ($ids_all as $id) {
            if (!isset($recepciones_map[$id])) continue;
            $fechas_rec = $recepciones_map[$id];
            $n_rec      = count($fechas_rec);
            $daily      = $daily_map[$id] ?? [];

            // Stock acumulado por fecha (mismo cálculo que getIncidenciasC7)
            $cum_delta     = 0.0;
            $stock_by_date = [];
            $all_dates     = array_keys($daily);
            sort($all_dates);
            foreach ($all_dates as $d) {
                $cum_delta        += $daily[$d];
                $stock_by_date[$d] = $cum_delta;
            }
            if (empty($stock_by_date)) continue;

            // Suelos inter-recepción
            $floors        = [];
            $fechas_floors = [];
            for ($i = 0; $i < $n_rec; $i++) {
                $fecha_ini = $fechas_rec[$i];
                $fecha_fin = ($i + 1 < $n_rec) ? $fechas_rec[$i + 1] : null;
                $min_floor = null;
                foreach ($stock_by_date as $d => $stock) {
                    if ($d < $fecha_ini) continue;
                    if ($fecha_fin !== null && $d >= $fecha_fin) continue;
                    if ($min_floor === null || $stock < $min_floor) $min_floor = $stock;
                }
                if ($min_floor !== null) {
                    $floors[]        = $min_floor;
                    $fechas_floors[] = $fecha_ini;
                }
            }
            $n_floors = count($floors);
            if ($n_floors < 2) continue;

            $floors_map = [];
            $ts_map     = [];
            for ($i = 0; $i < $n_floors; $i++) {
                $fecha = $fechas_floors[$i];
                $floors_map[$fecha] = $floors[$i];
                $ts_map[$fecha]     = strtotime($fecha);
            }

            $mean_raw = array_sum($floors) / $n_floors;
            $var_raw  = 0.0;
            foreach ($floors as $f) {
                $var_raw += ($f - $mean_raw) ** 2;
            }
            $std_raw  = $n_floors > 1 ? sqrt($var_raw / ($n_floors - 1)) : 0.0;

            // Determinar subcaso desde las listas de la fase 1
            if (isset($ids_c7a_set[$id])) {
                $subcaso = 'C7a';
            } elseif (isset($ids_c7b_set[$id])) {
                $subcaso = 'C7b';
            } else {
                continue; // no debería ocurrir
            }

            $incidencias_cde[] = [
                'idArticulo'      => $id,
                'c7_subcaso'      => $subcaso,
                '_floors_raw'     => $floors_map,
                '_ts_floors'      => $ts_map,
                'offset_estimado' => round($mean_raw, 1),
                'dispersion'      => round($std_raw,  1),
                'posible_causa'   => '',
            ];
        }

        if (empty($incidencias_cde)) return ['cruces' => []];

        // ── Ejecutar C7c / C7e / C7d sobre el conjunto completo ──────────────
        // C7c: cruces de pares C7a ↔ C7b
        $c7b_data   = [];
        foreach ($incidencias_cde as $inc) {
            if ($inc['c7_subcaso'] !== 'C7b') continue;
            $c7b_data[$inc['idArticulo']] = [
                'floors_raw' => $inc['_floors_raw'],
                'ts_floors'  => $inc['_ts_floors'],
            ];
        }
        $ids_str_cde = implode(',', array_unique(array_column($incidencias_cde, 'idArticulo')));
        $meta_c7c    = $this->_queryMetaC7c($ids_str_cde);
        $cruces_map  = [];

        // ── C7c inline (replicado del bloque en getIncidenciasC7) ────────────
        foreach ($incidencias_cde as &$inc) {
            if ($inc['c7_subcaso'] !== 'C7a') continue;
            $mejor_score = 0.0;
            $mejor_id    = null;
            $mejor_nivel = '';
            $n1_a = $meta_c7c[$inc['idArticulo']]['familias_n1'] ?? [];

            foreach ($c7b_data as $id_b => $cb) {
                $n1_b = $meta_c7c[$id_b]['familias_n1'] ?? [];
                if (!empty($n1_a) && !empty($n1_b) && empty(array_intersect($n1_a, $n1_b))) continue;

                // Emparejar suelos por fecha próxima (±7 días)
                $pairs_a  = [];
                $pairs_b = [];
                $ventana  = 7 * 86400;
                $ts_a_map = $inc['_ts_floors'];
                $ts_b_map = $cb['ts_floors'];
                foreach ($inc['_floors_raw'] as $da => $va) {
                    $ts_a    = $ts_a_map[$da];
                    $best_vb = null;
                    $best_diff = $ventana + 1;
                    foreach ($cb['floors_raw'] as $db => $vb) {
                        $diff = abs($ts_a - $ts_b_map[$db]);
                        if ($diff <= $ventana && $diff < $best_diff) {
                            $best_diff = $diff;
                            $best_vb = $vb;
                        }
                    }
                    if ($best_vb !== null) {
                        $pairs_a[] = $va;
                        $pairs_b[] = $best_vb;
                    }
                }
                $n_pairs = count($pairs_a);
                if ($n_pairs < 4) continue;

                // Media y CV de cada lado
                $mu_a = array_sum($pairs_a) / $n_pairs;
                $mu_b = array_sum($pairs_b) / $n_pairs;
                $max_abs = max(abs($mu_a), abs($mu_b));
                if ($max_abs < 1e-9) continue;
                if (abs($mu_a + $mu_b) / $max_abs > 0.45) continue;

                $std_a = 0.0;
                foreach ($pairs_a as $v) {
                    $std_a += ($v - $mu_a) ** 2;
                }
                $std_a = $n_pairs > 1 ? sqrt($std_a / ($n_pairs - 1)) : 0.0;
                $std_b = 0.0;
                foreach ($pairs_b as $v) {
                    $std_b += ($v - $mu_b) ** 2;
                }
                $std_b = $n_pairs > 1 ? sqrt($std_b / ($n_pairs - 1)) : 0.0;
                $cv_a  = $mu_a != 0.0 ? $std_a / abs($mu_a) : PHP_FLOAT_MAX;
                $cv_b  = $mu_b != 0.0 ? $std_b / abs($mu_b) : PHP_FLOAT_MAX;
                if ($cv_a >= 0.70 || $cv_b >= 0.70) continue;

                // Pearson sobre valores absolutos
                $pearson = 0.0;
                if ($n_pairs >= 4 && $std_a > 0.0 && $std_b > 0.0) {
                    $cov = 0.0;
                    for ($k = 0; $k < $n_pairs; $k++) {
                        $cov += (abs($pairs_a[$k]) - abs($mu_a)) * (abs($pairs_b[$k]) - abs($mu_b));
                    }
                    $pearson = $cov / ($n_pairs * $std_a * $std_b);
                }
                if ($pearson < 0.50) continue;

                // Score
                $s_mag   = 1.0 - abs($mu_a + $mu_b) / $max_abs;
                $s_disp  = ($std_a > 0.0 && $std_b > 0.0)
                    ? max(0.0, 1.0 - abs(log($std_a / $std_b)) / 2.0) : 0.0;
                $nom_a   = $meta_c7c[$inc['idArticulo']]['nombre'] ?? '';
                $nom_b   = $meta_c7c[$id_b]['nombre'] ?? '';
                $tok_a   = array_flip(preg_split('/\W+/u', mb_strtolower($nom_a), -1, PREG_SPLIT_NO_EMPTY));
                $tok_b   = array_flip(preg_split('/\W+/u', mb_strtolower($nom_b), -1, PREG_SPLIT_NO_EMPTY));
                $inter   = count(array_intersect_key($tok_a, $tok_b));
                $union   = count(array_merge($tok_a, $tok_b));
                $s_nom   = $union > 0 ? $inter / $union : 0.0;
                $score   = 0.50 * $s_mag + 0.25 * $pearson + 0.15 * $s_disp + 0.10 * $s_nom;

                $nivel = $score >= 0.80 ? 'confirmado' : ($score >= 0.65 ? 'probable' : ($score >= 0.50 ? 'posible' : ''));
                if ($nivel === '' || $score <= $mejor_score) continue;
                $mejor_score = $score;
                $mejor_id = $id_b;
                $mejor_nivel = $nivel;
            }

            if ($mejor_id !== null) {
                $inc['posible_cruce_con'] = $mejor_id;
                $inc['cruce_score']       = round($mejor_score, 2);
                $inc['cruce_nivel']       = $mejor_nivel;
                $cruces_map[$inc['idArticulo']] = ['id_b' => $mejor_id, 'score' => round($mejor_score, 2), 'nivel' => $mejor_nivel];
                $inc['posible_causa'] = sprintf(
                    'Merma con patrón complementario a art. %d (cruce %s, score=%.2f): probable confusión en la balanza de autopesaje entre ambos artículos',
                    $mejor_id,
                    $mejor_nivel,
                    $mejor_score
                );
            }
        }
        unset($inc);

        // C7e y C7d sobre el conjunto completo (después de C7c)
        $this->getIncidenciasC7e($incidencias_cde);
        $this->getIncidenciasC7d($incidencias_cde);

        // Propagar cruce al lado C7b
        if (!empty($cruces_map)) {
            $c7b_cruce = [];
            foreach ($cruces_map as $id_a => $data) {
                $id_b = $data['id_b'];
                if (!isset($c7b_cruce[$id_b]) || $data['score'] > $c7b_cruce[$id_b]['score']) {
                    $c7b_cruce[$id_b] = ['id_a' => $id_a, 'score' => $data['score'], 'nivel' => $data['nivel']];
                }
            }
            foreach ($incidencias_cde as &$inc) {
                if ($inc['c7_subcaso'] !== 'C7b') continue;
                if (!isset($c7b_cruce[$inc['idArticulo']])) continue;
                $d = $c7b_cruce[$inc['idArticulo']];
                $inc['posible_cruce_con'] = $d['id_a'];
                $inc['cruce_score']       = $d['score'];
                $inc['cruce_nivel']       = $d['nivel'];
                $inc['posible_causa'] = sprintf(
                    'Déficit de ~%.0f ud. con patrón complementario a art. %d (cruce %s, score=%.2f): probable confusión en la balanza o recepción no registrada',
                    abs((float)($inc['offset_estimado'] ?? 0)),
                    $d['id_a'],
                    $d['nivel'],
                    $d['score']
                );
            }
            unset($inc);
        }

        // C7e y C7d ya corrieron arriba; eliminar _floors_raw antes de devolver
        foreach ($incidencias_cde as &$inc) {
            unset($inc['_floors_raw']);
        }
        unset($inc);

        // Extraer solo las anotaciones de cruce
        $cruces = [];
        foreach ($incidencias_cde as $f) {
            if (!isset($f['posible_cruce_con'])) continue;
            $cruces[(int)$f['idArticulo']] = [
                'posible_cruce_con' => (int)$f['posible_cruce_con'],
                'cruce_score'       => $f['cruce_score']  ?? null,
                'cruce_nivel'       => $f['cruce_nivel']  ?? null,
                'posible_causa'     => $f['posible_causa'] ?? null,
            ];
        }

        return ['cruces' => $cruces];
    }
}
