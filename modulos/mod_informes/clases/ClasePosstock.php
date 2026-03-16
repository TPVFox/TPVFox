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
 * ─── TIPOS DE ARTÍCULO FÍSICO ────────────────────────────────────────────────
 *
 *   tipo IN ('unidad', 'peso')  — confirmado en BD/Update/install_update_v0.0.40.sql
 *   Si se añaden nuevos tipos físicos, actualizar la constante TIPOS_FISICOS.
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

    // Tipos de artículo considerados físicos (confirmar en BD si se añaden nuevos tipos)
    const TIPOS_FISICOS = "'unidad', 'peso'";

    // Antigüedad máxima de la vinculación artículo-proveedor (fechaActualizacion).
    // Relaciones no actualizadas en más de este número de años se consideran obsoletas
    // y se excluyen del filtro de proveedor en POSStock.
    const PROV_MAX_ANTIGUEDAD_ANOS = 2;

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
        $tipos = self::TIPOS_FISICOS;
        $sql = "
            SELECT tipo_movimiento, idArticulo, SUM(ncant) AS ncant, fecha, NULL AS idDocumento
            FROM (
                SELECT
                    'entrada_proveedor'    AS tipo_movimiento,
                    l.idArticulo,
                    l.ncant,
                    DATE(c.Fecha)          AS fecha
                FROM albprolinea l
                INNER JOIN albprot      c ON c.id         = l.idalbpro
                INNER JOIN articulos    a ON a.idArticulo  = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado       IN ('Guardado', 'Facturado')
                  AND l.estadoLinea  = 'Activo'
                  AND a.tipo         IN ($tipos)
                  $where_familia
                  $where_ids

                UNION ALL

                SELECT
                    'salida_ticket'        AS tipo_movimiento,
                    l.idArticulo,
                    l.ncant,
                    DATE(c.Fecha)          AS fecha
                FROM ticketslinea l
                INNER JOIN ticketst     c ON c.id         = l.idticketst
                INNER JOIN articulos    a ON a.idArticulo  = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado       = 'Cerrado'
                  AND l.estadoLinea  = 'Activo'
                  AND a.tipo         IN ($tipos)
                  $where_familia
                  $where_ids

                UNION ALL

                SELECT
                    'salida_albcli'        AS tipo_movimiento,
                    l.idArticulo,
                    l.ncant,
                    DATE(c.Fecha)          AS fecha
                FROM albclilinea l
                INNER JOIN albclit      c ON c.id         = l.idalbcli
                INNER JOIN articulos    a ON a.idArticulo  = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado       IN ('Guardado', 'Procesado')
                  AND l.estadoLinea  = 'Activo'
                  AND a.tipo         IN ($tipos)
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
     * @return array  Filas raw (idArticulo, ncant_signo, tipo_mov, fecha) o ['error' => ...]
     */
    private function _queryStockBase(string $fi, string $ff, string $ids_str): array
    {
        $sql = "
            SELECT
                idArticulo,
                SUM(ncant_signo)                        AS saldo_acumulado,
                MAX(CASE WHEN tipo_mov = 'entrada'
                         THEN fecha END)                AS ultima_compra,
                MAX(CASE WHEN tipo_mov = 'salida'
                         THEN fecha END)                AS ultima_venta
            FROM (

                -- Entradas proveedor (positivo)
                SELECT
                    l.idArticulo,
                     l.ncant                            AS ncant_signo,
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
                    -l.ncant                            AS ncant_signo,
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
                    -l.ncant                            AS ncant_signo,
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
        $tipos = self::TIPOS_FISICOS;
        $smt = $this->db->query(
            "SELECT idArticulo FROM articulos a WHERE a.tipo IN ($tipos) $where_familia"
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
                SELECT idArticulo, SUM(ncant_signo) AS net_posterior
                FROM (
                    SELECT l.idArticulo,  l.ncant AS ncant_signo
                    FROM albprolinea l INNER JOIN albprot c ON c.id = l.idalbpro
                    WHERE DATE(c.Fecha) > '$ff_esc'
                      AND c.estado IN ('Guardado','Facturado','Exportado','Importado')
                      AND l.estadoLinea = 'Activo'
                      AND l.idArticulo IN ($ids_str)
                    UNION ALL
                    SELECT l.idArticulo, -l.ncant AS ncant_signo
                    FROM ticketslinea l INNER JOIN ticketst c ON c.id = l.idticketst
                    WHERE DATE(c.Fecha) > '$ff_esc'
                      AND c.estado = 'Cerrado'
                      AND l.estadoLinea = 'Activo'
                      AND l.idArticulo IN ($ids_str)
                    UNION ALL
                    SELECT l.idArticulo, -l.ncant AS ncant_signo
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
        $tipos    = self::TIPOS_FISICOS;
        $union_albcli = $incluir_albcli ? "
                UNION
                SELECT DISTINCT l.idArticulo, DATE(c.Fecha) AS fecha
                FROM albclilinea l
                INNER JOIN albclit   c ON c.id = l.idalbcli
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado IN ('Guardado','Procesado')
                  AND l.estadoLinea = 'Activo'
                  AND a.tipo IN ($tipos)
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
                  AND a.tipo IN ($tipos)
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
     * @return array  Filas raw (idArticulo, fecha, ncant_dia) o ['error' => ...]
     */
    private function _queryVentasCantidadesC6(
        string $fi,
        string $ff,
        string $where_fam,
        string $where_ids,
        bool   $incluir_albcli = false
    ): array {
        $tipos = self::TIPOS_FISICOS;
        if ($incluir_albcli) {
            $smt = $this->db->query("
                SELECT idArticulo, fecha, SUM(ncant) AS ncant_dia
                FROM (
                    SELECT l.idArticulo, DATE(c.Fecha) AS fecha, l.ncant
                    FROM ticketslinea l
                    INNER JOIN ticketst  c ON c.id = l.idticketst
                    INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                    WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                      AND c.estado = 'Cerrado'
                      AND l.estadoLinea = 'Activo'
                      AND a.tipo IN ($tipos)
                      $where_fam
                      $where_ids
                    UNION ALL
                    SELECT l.idArticulo, DATE(c.Fecha) AS fecha, l.ncant
                    FROM albclilinea l
                    INNER JOIN albclit   c ON c.id = l.idalbcli
                    INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                    WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                      AND c.estado IN ('Guardado','Procesado')
                      AND l.estadoLinea = 'Activo'
                      AND a.tipo IN ($tipos)
                      $where_fam
                      $where_ids
                ) AS ventas
                GROUP BY idArticulo, fecha
                ORDER BY idArticulo, fecha
            ");
        } else {
            $smt = $this->db->query("
                SELECT l.idArticulo, DATE(c.Fecha) AS fecha, SUM(l.ncant) AS ncant_dia
                FROM ticketslinea l
                INNER JOIN ticketst  c ON c.id = l.idticketst
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado = 'Cerrado'
                  AND l.estadoLinea = 'Activo'
                  AND a.tipo IN ($tipos)
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
        $tipos = self::TIPOS_FISICOS;
        $sql = "
            SELECT idArticulo, SUM(day_delta) AS delta_total, MIN(cum_sum) AS min_running,
                   MIN(CASE WHEN rn_min = 1 THEN fecha END) AS fecha_minimo
            FROM (
                SELECT idArticulo, fecha, day_delta, cum_sum,
                       ROW_NUMBER() OVER (PARTITION BY idArticulo
                                          ORDER BY cum_sum ASC, fecha ASC) AS rn_min
                FROM (
                    SELECT idArticulo, fecha, day_delta,
                           SUM(day_delta) OVER (PARTITION BY idArticulo ORDER BY fecha
                                                ROWS UNBOUNDED PRECEDING) AS cum_sum
                    FROM (
                        SELECT idArticulo, fecha, SUM(delta) AS day_delta
                        FROM (
                            SELECT l.idArticulo, DATE(c.Fecha) AS fecha, l.ncant AS delta
                            FROM albprolinea l
                            INNER JOIN albprot    c ON c.id        = l.idalbpro
                            INNER JOIN articulos  a ON a.idArticulo = l.idArticulo
                            WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                              AND c.estado      IN ('Guardado','Facturado')
                              AND l.estadoLinea = 'Activo'
                              AND a.tipo        IN ($tipos)
                              $wf $wi
                            UNION ALL
                            SELECT l.idArticulo, DATE(c.Fecha) AS fecha, -l.ncant AS delta
                            FROM ticketslinea l
                            INNER JOIN ticketst  c ON c.id        = l.idticketst
                            INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                            WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                              AND c.estado      = 'Cerrado'
                              AND l.estadoLinea = 'Activo'
                              AND a.tipo        IN ($tipos)
                              $wf $wi
                            UNION ALL
                            SELECT l.idArticulo, DATE(c.Fecha) AS fecha, -l.ncant AS delta
                            FROM albclilinea l
                            INNER JOIN albclit   c ON c.id        = l.idalbcli
                            INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                            WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                              AND c.estado      IN ('Guardado','Procesado')
                              AND l.estadoLinea = 'Activo'
                              AND a.tipo        IN ($tipos)
                              $wf $wi
                        ) AS all_movs
                        GROUP BY idArticulo, fecha
                    ) AS daily
                ) AS windowed_inner
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
     * @return array  Filas raw (idArticulo, fecha, ncant, cum_before) o ['error' => ...]
     */
    private function _queryEntradasC2(
        string $fi,
        string $ff,
        string $wf,
        string $wi
    ): array {
        $tipos = self::TIPOS_FISICOS;
        $sql = "
            SELECT e.idArticulo, e.fecha, e.ncant,
                   COALESCE(r.cum_before, 0) AS cum_before
            FROM (
                -- Entradas positivas de proveedor agrupadas por artículo y fecha
                SELECT l.idArticulo, DATE(c.Fecha) AS fecha, SUM(l.ncant) AS ncant
                FROM albprolinea l
                INNER JOIN albprot   c ON c.id        = l.idalbpro
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado      IN ('Guardado','Facturado')
                  AND l.estadoLinea = 'Activo'
                  AND l.ncant       > 0
                  AND a.tipo        IN ($tipos)
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
                        SELECT l.idArticulo, DATE(c.Fecha) AS fecha, l.ncant AS delta
                        FROM albprolinea l
                        INNER JOIN albprot    c ON c.id        = l.idalbpro
                        INNER JOIN articulos  a ON a.idArticulo = l.idArticulo
                        WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                          AND c.estado      IN ('Guardado','Facturado')
                          AND l.estadoLinea = 'Activo'
                          AND a.tipo        IN ($tipos)
                          $wf $wi
                        UNION ALL
                        SELECT l.idArticulo, DATE(c.Fecha) AS fecha, -l.ncant AS delta
                        FROM ticketslinea l
                        INNER JOIN ticketst  c ON c.id        = l.idticketst
                        INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                        WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                          AND c.estado      = 'Cerrado'
                          AND l.estadoLinea = 'Activo'
                          AND a.tipo        IN ($tipos)
                          $wf $wi
                        UNION ALL
                        SELECT l.idArticulo, DATE(c.Fecha) AS fecha, -l.ncant AS delta
                        FROM albclilinea l
                        INNER JOIN albclit   c ON c.id        = l.idalbcli
                        INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                        WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                          AND c.estado      IN ('Guardado','Procesado')
                          AND l.estadoLinea = 'Activo'
                          AND a.tipo        IN ($tipos)
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
        $tipos          = self::TIPOS_FISICOS;
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
                       COUNT(DISTINCT CASE WHEN l.ncant > 0 THEN c.id END)        AS n_entradas,
                       SUM(CASE WHEN l.ncant > 0 THEN l.ncant ELSE 0 END)         AS cantidad_recibida,
                       MIN(CASE WHEN l.ncant > 0 THEN DATE(c.Fecha) END)          AS fecha_primera_entrada,
                       COUNT(DISTINCT CASE WHEN l.ncant < 0 THEN c.id END)        AS n_devoluciones,
                       SUM(CASE WHEN l.ncant < 0 THEN ABS(l.ncant) ELSE 0 END)   AS cantidad_devuelta
                FROM albprolinea l
                INNER JOIN albprot   c ON c.id        = l.idalbpro
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi_m' AND '$ff_m'
                  AND c.estado      IN ('Guardado','Facturado')
                  AND l.estadoLinea = 'Activo'
                  AND a.tipo        IN ($tipos)
                  $wf $wi
                GROUP BY l.idArticulo
                HAVING COUNT(DISTINCT CASE WHEN l.ncant > 0 THEN c.id END) > 0
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

    /**
     * Proveedor — idArticulo vinculados a los proveedores indicados.
     * Usa articulosProveedores (estado Activo, fechaActualizacion dentro de PROV_MAX_ANTIGUEDAD_ANOS
     * años antes de $ff_mov para que el corte sea coherente con el periodo de análisis).
     *
     * @param  string $ids_prov  IN-clause de idProveedor ya preparado
     * @param  string $ff_mov    Fecha fin del periodo de análisis ('YYYY-MM-DD')
     * @return array  Filas raw (idArticulo) o ['error' => ...]
     */
    private function _queryIdsArticulosByProveedores(string $ids_prov, string $ff_mov): array
    {
        $ff   = $this->db->real_escape_string($ff_mov);
        $anos = self::PROV_MAX_ANTIGUEDAD_ANOS;
        $smt = $this->db->query(
            "SELECT DISTINCT idArticulo FROM articulosProveedores
             WHERE idProveedor IN ($ids_prov)
               AND estado = 'Activo'
               AND fechaActualizacion >= DATE_SUB('$ff', INTERVAL $anos YEAR)"
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
     * Aplica el mismo corte de antigüedad (PROV_MAX_ANTIGUEDAD_ANOS relativo a $ff_mov)
     * que _queryIdsArticulosByProveedores.
     *
     * @return int[]  Array de idArticulo, o array con clave 'error'.
     */
    private function _queryArticulosProveedorPaginados(string $ids_prov, string $ff_mov, int $offset, int $limit): array
    {
        $ff   = $this->db->real_escape_string($ff_mov);
        $anos = self::PROV_MAX_ANTIGUEDAD_ANOS;
        $tipos = self::TIPOS_FISICOS;
        $smt = $this->db->query(
            "SELECT DISTINCT ap.idArticulo
             FROM articulosProveedores ap
             INNER JOIN articulos a ON a.idArticulo = ap.idArticulo
             WHERE ap.idProveedor IN ($ids_prov)
               AND ap.estado = 'Activo'
               AND ap.fechaActualizacion >= DATE_SUB('$ff', INTERVAL $anos YEAR)
               AND a.tipo IN ($tipos)
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
        $tipos = self::TIPOS_FISICOS;
        $smt = $this->db->query("
            SELECT DISTINCT idArticulo FROM (
                SELECT l.idArticulo FROM albprolinea l
                INNER JOIN albprot c ON c.id = l.idalbpro
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado IN ('Guardado','Facturado','Exportado','Importado')
                  AND l.estadoLinea = 'Activo'
                  AND a.tipo IN ($tipos)
                  $where_fam $where_prov
                UNION
                SELECT l.idArticulo FROM ticketslinea l
                INNER JOIN ticketst c ON c.id = l.idticketst
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado = 'Cerrado'
                  AND l.estadoLinea = 'Activo'
                  AND a.tipo IN ($tipos)
                  $where_fam $where_prov
                UNION
                SELECT l.idArticulo FROM albclilinea l
                INNER JOIN albclit c ON c.id = l.idalbcli
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
                  AND c.estado IN ('Guardado','Procesado')
                  AND l.estadoLinea = 'Activo'
                  AND a.tipo IN ($tipos)
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
     *   idArticulo, ncant, fecha (DATE), idDocumento
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
     *   idArticulo, idDocumento, fecha, ncant,
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
                    + $signo * (float)$m['ncant'];
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
                    'ncant'                    => (float)$entrada['ncant'],
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
        $min_ventas_c5          = (int)    ($params['min_ventas_c5']               ?? 3);
        $modelo_rotura_c5           = (string) ($params['modelo_rotura_c5']              ?? 'automatico');
        $umbral_confianza_c5        = (float)  ($params['umbral_confianza_poisson']      ?? 0.05);
        $c5_incluir_stock_negativo  = (bool)   ($params['c5_incluir_stock_negativo']     ?? false);
        $binomial_sigma_mult        = (float)  ($params['binomial_sigma_mult']           ?? 3.0);
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
                $rows_prov = $this->_queryIdsArticulosByProveedores($ids_str_prov, $ff_mov);
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
        $validos_todos = ['caso1', 'caso2', 'caso3a', 'caso3b', 'caso5', 'caso6a', 'caso6b'];
        $casos_raw     = (array)($params['casos_incluir'] ?? []);
        $casos_set     = array_flip(
            empty($casos_raw)
                ? $validos_todos
                : array_intersect($casos_raw, [...$validos_todos, 'caso4'])
        );

        $incidencias = [];

        // ── Precalcular stock_base compartido para C1 y C2 ───────────────────
        $sb_shared = [];
        if (!empty($ids_filter) && (isset($casos_set['caso1']) || isset($casos_set['caso2']))) {
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
                $sb_shared
            );
            if (isset($c1['error'])) return $c1;
            $incidencias = array_merge($incidencias, $c1);
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
                $fi_mov, $ff_mov, $fi_stats, $umbral_sobrestock,
                $familias_incluir, $familias_excluir, $ids_filter,
                $min_ventas_c5, $modelo_rotura_c5, $umbral_confianza_c5,
                $c5_incluir_stock_negativo, $binomial_sigma_mult, $incluir_albcli,
                $c3b_dias_post, $ff_stats
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
                $fi_mov, $ff_mov, $fi_stock,
                $familias_incluir, $familias_excluir, $ids_filter,
                $c6_proveedores, $c6_lead_time, $c6_nivel_serv,
                $min_ventas_c5, $modelo_rotura_c5, $umbral_confianza_c5,
                $binomial_sigma_mult, $incluir_albcli,
                $fi_stats, $ff_stats,
                'Agotamiento Estimado'
            );
            if (isset($c6a['error'])) return $c6a;
            $incidencias = array_merge($incidencias, $c6a);
        }

        // ── C6b — ROP operacional (ventana histórica fija anclada en hoy) ───
        // A diferencia de C6a (anclada en ff_mov), C6b usa siempre la fecha actual
        // como referencia: fi = hoy-N días, ff = hoy, stock = hoy.
        // Así el resultado es estable e independiente del periodo analizado.
        if (isset($casos_set['caso6b'])) {
            $c6b_dias    = (int)($params['c6b_dias_historico'] ?? 90);
            $ff_hoy      = date('Y-m-d');
            $anio_hoy    = (int)substr($ff_hoy, 0, 4);
            $fi_stats_6b = max(
                date('Y-m-d', strtotime("$ff_hoy -{$c6b_dias} days")),
                "{$anio_hoy}-01-01"   // límite BD anualizada; resoluble con mod_api
            );
            $c6b = $this->getIncidenciasCaso6(
                $ff_hoy, $ff_hoy, $fi_stock,   // fi_mov = ff_mov = hoy → stock actual
                $familias_incluir, $familias_excluir, $ids_filter,
                $c6_proveedores, $c6_lead_time, $c6_nivel_serv,
                $min_ventas_c5, $modelo_rotura_c5, $umbral_confianza_c5,
                $binomial_sigma_mult, $incluir_albcli,
                $fi_stats_6b, $ff_hoy,
                'Punto de Pedido'
            );
            if (isset($c6b['error'])) return $c6b;
            $incidencias = array_merge($incidencias, $c6b);
        }

        // ── C4 — solo si solicitado explícitamente (no paginable por actividad) ─
        if (isset($casos_set['caso4'])) {
            $articulos_sin_mov = $this->getArticulosSinMovimiento(
                $fi_mov,
                $ff_mov,
                $familias_incluir,
                $familias_excluir
            );
            if (isset($articulos_sin_mov['error'])) return $articulos_sin_mov;
            foreach ($this->_formatearCaso4($articulos_sin_mov) as $inc) {
                $incidencias[] = $inc;
            }
        }

        // ── Ordenar: CRITICA → ALTA → MEDIA (C2→C5→C3a) → BAJA (C3b sin-rot→C3b nunca→C4) ─
        $orden_sev = ['CRITICA' => 0, 'ALTA' => 1, 'MEDIA' => 2, 'BAJA' => 3];

        $orden_tipo_media = [
            'Entrada con stock alto'             => 0,  // C2
            'Venta Cero (Posible Rotura Física)' => 1,  // C5
            'Agotamiento Estimado'               => 2,  // C6a MEDIA (BN, stock suficiente)
            'Punto de Pedido'                    => 2,  // C6b MEDIA (mismo nivel que C6a)
            'Riesgo de caducidad teórica'        => 3,  // C3a
        ];

        // C3b con ultima_salida (Sin rotación) antes que sin ultima_salida (Nunca salidas)
        $subtipo_baja = static function (array $inc): int {
            if ($inc['tipo'] === 'Entrada sin rotación previa') {
                return (isset($inc['ultima_salida']) && $inc['ultima_salida'] !== null) ? 0 : 1;
            }
            return 2; // 'Stock Inactivo en Periodo' (C4)
        };

        usort($incidencias, static function ($a, $b) use ($orden_sev, $orden_tipo_media, $subtipo_baja) {
            $cmp = $orden_sev[$a['severidad']] <=> $orden_sev[$b['severidad']];
            if ($cmp !== 0) return $cmp;
            if ($a['severidad'] === 'MEDIA') {
                return ($orden_tipo_media[$a['tipo']] ?? 99) <=> ($orden_tipo_media[$b['tipo']] ?? 99);
            }
            if ($a['severidad'] === 'BAJA') {
                return $subtipo_baja($a) <=> $subtipo_baja($b);
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
        $a = [-3.969683028665376e+01,  2.209460984245205e+02,
              -2.759285104469687e+02,  1.383577518672690e+02,
              -3.066479806614716e+01,  2.506628277459239e+00];
        $b = [-5.447609879822406e+01,  1.615858368580409e+02,
              -1.556989798598866e+02,  6.680131188771972e+01,
              -1.328068155288572e+01];
        $c = [-7.784894002430293e-03, -3.223964580411365e-01,
              -2.400758277161838e+00, -2.549732539343734e+00,
               4.374664141464968e+00,  2.938163982698783e+00];
        $d = [7.784695709041462e-03,  3.224671290700398e-01,
              2.445134137142996e+00,  3.754408661907416e+00];

        $p_low = 0.02425;
        if ($p < $p_low) {
            $q = sqrt(-2.0 * log($p));
            return (((((($c[0]*$q+$c[1])*$q+$c[2])*$q+$c[3])*$q+$c[4])*$q+$c[5]) /
                    ((((($d[0]*$q+$d[1])*$q+$d[2])*$q+$d[3])*$q+1.0)));
        } elseif ($p <= 1.0 - $p_low) {
            $q = $p - 0.5;
            $r = $q * $q;
            return (((((($a[0]*$r+$a[1])*$r+$a[2])*$r+$a[3])*$r+$a[4])*$r+$a[5])*$q /
                    (((((($b[0]*$r+$b[1])*$r+$b[2])*$r+$b[3])*$r+$b[4])*$r+1.0)));
        } else {
            $q = sqrt(-2.0 * log(1.0 - $p));
            return -(((((($c[0]*$q+$c[1])*$q+$c[2])*$q+$c[3])*$q+$c[4])*$q+$c[5]) /
                     ((((($d[0]*$q+$d[1])*$q+$d[2])*$q+$d[3])*$q+1.0)));
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
                    $id, $fechas_map, $stock_actual, $ff_ts, $min_ventas, $umbral_prob, $incluir_stock_negativo, 'GammaReg'
                );
            }
            // Alta rotación, gaps más variables → Poisson (inter-arrivals ~ Exponencial)
            return $this->_calcularRoturasC5Poisson(
                $id, $fechas_map, $stock_actual, $ff_ts, $min_ventas, $umbral_prob, $periodo_dias, $incluir_stock_negativo, $ff_stats_ts
            );
        }

        // RAMA 2: Rotación media/baja (≤80 %)
        // 2a. Demanda en rachas → Binomial Negativa (activa internamente en _calcularRoturasC5Poisson)
        if ($overdispersed) {
            return $this->_calcularRoturasC5Poisson(
                $id, $fechas_map, $stock_actual, $ff_ts, $min_ventas, $umbral_prob, $periodo_dias, $incluir_stock_negativo, $ff_stats_ts
            );
        }

        // 2b. Muy esporádico (no tipo peso) → Poisson (proceso de eventos raros)
        if (!$is_peso && $rotation < 0.15) {
            return $this->_calcularRoturasC5Poisson(
                $id, $fechas_map, $stock_actual, $ff_ts, $min_ventas, $umbral_prob, $periodo_dias, $incluir_stock_negativo, $ff_stats_ts
            );
        }

        // 2c. General / tipo peso → Gamma (ajuste a distribución de gaps, cuantil de rotura)
        return $this->_calcularRoturasC5Gamma(
            $id, $fechas_map, $stock_actual, $ff_ts, $min_ventas, $umbral_prob, $incluir_stock_negativo
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
        array $familias_excluir = []
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
        $rows_stock = $this->_queryStockRebobinado($ids_str, $ff, true);
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
                    $id, $fechas_map, $sa,
                    $ff_ts, $min_ventas, $umbral_prob, $periodo_dias,
                    $c5_incluir_stock_negativo, $ff_stats_ts
                ),
                'gamma' => $this->_calcularRoturasC5Gamma(
                    $id, $fechas_map, $sa,
                    $ff_ts, $min_ventas, $umbral_prob,
                    $c5_incluir_stock_negativo
                ),
                'poisson_bn', 'poisson' => $this->_calcularRoturasC5Poisson(
                    $id, $fechas_map, $sa,
                    $ff_ts, $min_ventas, $umbral_prob, $periodo_dias,
                    $c5_incluir_stock_negativo, $ff_stats_ts
                ),
                default => $this->_calcularRoturasC5(  // 'binomial'
                    $id, $fechas_map, $sa,
                    $ff_ts, $min_ventas, $c5_incluir_stock_negativo, $binomial_sigma_mult
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
                    $ids_str_curso, $fi_post_esc, $ff_post_esc, $incluir_albcli
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
        string $tipo_override       = ''      // tipo de incidencia ('Agotamiento Estimado' | 'Punto de Pedido')
    ): array {
        $fi_stats = $fi_stats ?: $fi_mov;
        $ff_stats = $ff_stats ?: $ff_mov;
        $fi = $this->db->real_escape_string($fi_stats);
        $ff = $this->db->real_escape_string($ff_mov);
        $ff_stats_esc = $this->db->real_escape_string($ff_stats);

        $where_fam = $this->_familiaWhere($familias_incluir, $familias_excluir);
        $where_ids = $this->_idsWhere($ids_filter);

        // Paso 1 — Cantidades vendidas por día y artículo en el periodo
        // (no fechas únicas: necesitamos unidades para d = unidades/día)
        // Ventana estadística fi_stats→ff_stats para mayor muestra en vistas cortas
        $rows_ventas = $this->_queryVentasCantidadesC6($fi, $ff_stats_esc, $where_fam, $where_ids, $incluir_albcli);
        if (isset($rows_ventas['error'])) return $rows_ventas;
        if (empty($rows_ventas)) return [];

        // $ventas_cant[idArticulo][fecha] = ncant_dia  (float)
        $ventas_cant = [];
        foreach ($rows_ventas as $r) {
            $ventas_cant[(int)$r['idArticulo']][$r['fecha']] = (float)$r['ncant_dia'];
        }

        // Paso 2 — Stock actual en ff_mov por rebobinado (igual que C5)
        $ids_str    = implode(',', array_keys($ventas_cant));
        $rows_stock = $this->_queryStockRebobinado($ids_str, $ff, false);
        if (isset($rows_stock['error'])) return $rows_stock;

        $stock_actual = [];
        foreach ($rows_stock as $r) {
            $stock_actual[(int)$r['idArticulo']] = (float)$r['stock_en_periodo'];
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

        $incidencias = [];

        foreach ($ventas_cant as $id => $fechas_map) {
            // $n  = días únicos con venta (base estadística del modelo)
            // $total_units = unidades totales vendidas (base de la demanda media)
            $n           = count($fechas_map);
            $total_units = array_sum($fechas_map);
            if ($n < $min_ventas) continue;

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
                'd_diaria'        => round($d, 4),
                'stock_seguridad' => round($SS, 2),
                'rop'             => round($ROP, 2),
                'modelo_usado'    => $modelo_usado,
                'posible_causa'   => $posible_causa,
            ];
        }

        // Añadir nombres
        if (!empty($incidencias)) {
            $ids_inc = implode(',', array_unique(array_column($incidencias, 'idArticulo')));
            $smt     = $this->db->query(
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
        array $familias_excluir,
        array  $ids_filter = [],
        array  $stock_base_cache = []   // pre-calculado por el caller para evitar doble consulta
    ): array {
        $fi    = $this->db->real_escape_string($fi_mov);
        $ff    = $this->db->real_escape_string($ff_mov);
        $wf    = $this->_familiaWhere($familias_incluir, $familias_excluir);
        $wi    = $this->_idsWhere($ids_filter);

        $rows = $this->_queryDeltasC1($fi, $ff, $wf, $wi);
        if (isset($rows['error'])) return $rows;
        if (empty($rows)) return [];

        $delta_map = [];
        $ids       = [];
        foreach ($rows as $r) {
            $delta_map[(int)$r['idArticulo']] = [
                'delta_total'  => (float)$r['delta_total'],
                'min_running'  => (float)$r['min_running'],
                'fecha_minimo' => $r['fecha_minimo'],
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
                $ya_negativo_inicio = $saldo_base < 0;
                $frac               = abs($stock_actual - round($stock_actual));
                $es_fraccionado     = $frac > 0.05;

                $incidencias[] = [
                    'idArticulo'         => $id,
                    'tipo'               => 'Inventario en negativo',
                    'severidad'          => 'CRITICA',
                    'stock_actual'       => $stock_actual,
                    'min_balance'        => $min_balance,
                    'ya_negativo_inicio' => $ya_negativo_inicio,
                    'es_fraccionado'     => $es_fraccionado,
                    'posible_causa'      => '',   // se sobreescribe en el bloque de enriquecimiento
                ];
                $ids_c1a[] = $id;
            } elseif ($min_balance < 0) {
                $frac_c1b       = abs($min_balance - round($min_balance));
                $es_fraccionado = $frac_c1b > 0.05;

                $incidencias[] = [
                    'idArticulo'     => $id,
                    'tipo'           => 'Desajuste Puntual de Stock',
                    'severidad'      => 'ALTA',
                    'stock_actual'   => $stock_actual,
                    'min_balance'    => $min_balance,
                    'es_fraccionado' => $es_fraccionado,
                    'fecha_minimo'   => $data['fecha_minimo'],
                    'posible_causa'  => 'Negativo puntual, recuperado al cierre',
                ];
                $ids_c1b[] = $id;
            }
        }

        // Enriquecer C1a con actividad del periodo (recepciones y ventas)
        if (!empty($ids_c1a)) {
            $ids_str = implode(',', $ids_c1a);
            $detalle = $this->_queryDetalleC1($ids_str, $fi, $ff);
            foreach ($incidencias as &$inc) {
                if ($inc['tipo'] !== 'Inventario en negativo') continue;
                $d     = $detalle[$inc['idArticulo']] ?? null;
                $n_ent = $d['n_entradas'] ?? 0;
                $inc['n_entradas']     = $n_ent;
                $inc['ultima_entrada'] = $d['ultima_entrada'] ?? null;
                $inc['n_ventas']       = $d['n_ventas']       ?? 0;

                if ($inc['ya_negativo_inicio']) {
                    $inc['posible_causa'] = 'Stock ya negativo al inicio del periodo: el problema viene de antes, revisar inventario anterior';
                } elseif ($n_ent === 0 && $inc['n_ventas'] > 0) {
                    $inc['posible_causa'] = 'Ventas registradas sin ninguna recepción en el periodo: comprobar si falta dar entrada de mercancía';
                } elseif ($n_ent > 0) {
                    $inc['posible_causa'] = 'Entradas registradas pero el stock sigue negativo: revisar si falta alguna recepción o si hay ventas duplicadas';
                } elseif ($inc['es_fraccionado']) {
                    $inc['posible_causa'] = 'Stock con decimales: posible acumulación de imprecisiones en ventas por peso o fraccionado';
                } else {
                    $inc['posible_causa'] = 'Sin movimientos que justifiquen el negativo: verificar si hay ajustes o movimientos no registrados';
                }
            }
            unset($inc);
        }

        // Enriquecer C1b con actividad del periodo (recepciones y ventas) + causa dinámica
        if (!empty($ids_c1b)) {
            $ids_str = implode(',', $ids_c1b);
            $detalle = $this->_queryDetalleC1($ids_str, $fi, $ff);

            // Confirmar timing: entrada dentro de 3 días tras la fecha del mínimo
            $id_fecha_map = [];
            foreach ($incidencias as $inc) {
                if ($inc['tipo'] === 'Desajuste Puntual de Stock' && !empty($inc['fecha_minimo'])) {
                    $id_fecha_map[$inc['idArticulo']] = $inc['fecha_minimo'];
                }
            }
            $timing_set = $this->_queryTimingC1b($id_fecha_map);

            foreach ($incidencias as &$inc) {
                if ($inc['tipo'] !== 'Desajuste Puntual de Stock') continue;
                $d     = $detalle[$inc['idArticulo']] ?? null;
                $n_ent = $d['n_entradas'] ?? 0;
                $inc['n_entradas']     = $n_ent;
                $inc['ultima_entrada'] = $d['ultima_entrada'] ?? null;
                $inc['n_ventas']       = $d['n_ventas']       ?? 0;
                $inc['timing_proximo'] = isset($timing_set[$inc['idArticulo']]);

                if ($inc['timing_proximo']) {
                    $inc['posible_causa'] = 'Probable venta registrada antes que la recepción (timing de entrada)';
                } elseif ($n_ent > 0) {
                    $inc['posible_causa'] = 'Entradas en el periodo pero no coinciden con el momento del negativo: revisar si hay un desajuste de inventario puntual';
                } elseif ($inc['es_fraccionado']) {
                    $inc['posible_causa'] = 'Stock con decimales: posible acumulación de imprecisiones en ventas por peso o fraccionado';
                } else {
                    $inc['posible_causa'] = 'Sin recepciones en el periodo: revisar movimientos duplicados o ajustes manuales';
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
     * C2 MEDIA — stock_previo >= ncant * umbral_sobrestock
     */
    /**
     * Ventas por ticket en el periodo para los artículos candidatos de C2.
     * Usado para calcular cobertura_dias = stock_previo / ventas_diarias.
     */
    private function _queryVentasC2(string $ids, string $fi, string $ff): array
    {
        if (empty($ids)) return [];
        $smt = $this->db->query("
            SELECT l.idArticulo, SUM(l.ncant) AS ventas_total
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
     *   - ncant_anterior       : cantidad de la recepción anterior (null si no hay)
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
            SELECT l.idArticulo, DATE(c.Fecha) AS fecha, SUM(l.ncant) AS ncant
            FROM albprolinea l
            INNER JOIN albprot c ON c.id = l.idalbpro
            WHERE l.idArticulo IN ($ids_str)
              AND DATE(c.Fecha) BETWEEN '$fi_ext' AND '$ff'
              AND c.estado      IN ('Guardado','Facturado')
              AND l.estadoLinea = 'Activo'
              AND l.ncant       > 0
            GROUP BY l.idArticulo, DATE(c.Fecha)
            ORDER BY l.idArticulo, DATE(c.Fecha)
        ");
        $rec_por_art = [];
        if ($smt) {
            while ($r = $smt->fetch_assoc()) {
                $rec_por_art[(int)$r['idArticulo']][] = ['fecha' => $r['fecha'], 'ncant' => (float)$r['ncant']];
            }
        }

        // ── Ventas por ticket, por día, del periodo extendido ─────────────────
        $smt2 = $this->db->query("
            SELECT l.idArticulo, DATE(c.Fecha) AS fecha, SUM(l.ncant) AS qty
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
                $inc['ncant_anterior']      = $anterior['ncant'];

                $ventas_entre = 0.0;
                foreach ($vtas_por_art[$id] ?? [] as $v) {
                    if ($v['fecha'] > $anterior['fecha'] && $v['fecha'] < $fecha_actual) {
                        $ventas_entre += $v['qty'];
                    }
                }
                $inc['ventas_entre_recepciones'] = $ventas_entre;
            } else {
                $inc['dias_desde_anterior']      = null;
                $inc['ncant_anterior']            = null;
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
            $ncant        = (float)$e['ncant'];
            if ($ncant <= 0 || $stock_previo < $ncant * $umbral_sobrestock) continue;

            $ratio = $stock_previo / $ncant;
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
                'ncant'         => $ncant,
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
            $ncant_a = $inc['ncant_anterior'];
            $vtr     = $inc['ventas_entre_recepciones'];
            $cob     = $inc['cobertura_dias'];

            // Duplicado probable: mismo día o día anterior + cantidad similar (diferencia < 15%)
            $es_duplicado_probable = $dias !== null && $dias <= 1 && $ncant_a !== null
                && (abs($inc['ncant'] - $ncant_a) / max($inc['ncant'], $ncant_a)) < 0.15;

            // Posible duplicado: hasta 3 días + cantidad similar (señal más débil)
            $es_duplicado_posible = !$es_duplicado_probable && $dias !== null && $dias <= 3 && $ncant_a !== null
                && (abs($inc['ncant'] - $ncant_a) / max($inc['ncant'], $ncant_a)) < 0.15;

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
                        'ncant'                    => $ultimo['ncant'],
                        'stock_previo'             => $stock_max,
                        'ratio'                    => $ultimo['ratio'],
                        'c2_categoria'             => 'acumulacion',
                        'fecha'                    => $ultimo['fecha'],
                        'fecha_inicio'             => $primero['fecha'],
                        'n_eventos'                => $n_total,
                        'gap_medio'                => $gap_medio,
                        'cobertura_dias'           => $ultimo['cobertura_dias'],
                        'dias_desde_anterior'      => $ultimo['dias_desde_anterior'],
                        'ncant_anterior'           => $ultimo['ncant_anterior'],
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
                            'ncant'                    => $ultimo['ncant'],
                            'stock_previo'             => $stock_max,
                            'ratio'                    => $ultimo['ratio'],
                            'c2_categoria'             => 'tendencia',
                            'fecha'                    => $ultimo['fecha'],
                            'fecha_inicio'             => $primero['fecha'],
                            'n_eventos'                => $n,
                            'cobertura_inicio'         => $cob_ini,
                            'cobertura_dias'           => $cob_fin,
                            'dias_desde_anterior'      => $ultimo['dias_desde_anterior'],
                            'ncant_anterior'           => $ultimo['ncant_anterior'],
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
        $ids_str_prov             = '';
        if (!empty($proveedores_incluir)) {
            $ids_str_prov = implode(',', array_map('intval', $proveedores_incluir));
            if (!$proveedor_todos) {
                // Modo normal: solo artículos del proveedor con actividad en el periodo
                $rows_prov = $this->_queryIdsArticulosByProveedores($ids_str_prov, $ff_mov);
                if (isset($rows_prov['error'])) return $rows_prov;
                $ids_proveedor_filter = array_column($rows_prov, 'idArticulo');
                if (empty($ids_proveedor_filter)) {
                    return ['filas' => [], 'actual' => $inicial, 'elementos' => 0];
                }
            }
        }

        // C4 no es paginable por actividad — ruta especial si es el único caso solicitado
        if ($casos_incluir === ['caso4']) {
            $filas = $this->getIncidencias($params);
            if (isset($filas['error'])) return $filas;
            return ['filas' => $filas, 'actual' => count($filas), 'elementos' => 0]; // elementos=0 → fin
        }

        // En batches mixtos, excluir C4 (no paginable por actividad)
        $params_batch = $params;
        $params_batch['casos_incluir'] = array_values(array_filter($casos_incluir, fn($c) => $c !== 'caso4'));

        // ── Obtener IDs del lote según modo de proveedor ─────────────────────
        if ($proveedor_todos && $ids_str_prov !== '') {
            // Modo "todos los productos del proveedor": paginar directamente sobre
            // articulosProveedores, sin filtro de actividad en el periodo.
            $ids_batch = $this->_queryArticulosProveedorPaginados($ids_str_prov, $ff_mov, $inicial, $pagina);
            if (isset($ids_batch['error'])) return $ids_batch;
            // El filtro de proveedor ya está embebido en ids_filter; no aplicar doble filtro
            $params_batch['ids_proveedor_filter'] = [];
        } else {
            // Modo normal: artículos con actividad en el periodo (intersectado con proveedor si aplica)
            $params_batch['ids_proveedor_filter'] = $ids_proveedor_filter;
            $ids_batch = $this->getArticulosConActividad(
                $fi_mov, $ff_mov, $familias_incluir, $familias_excluir,
                $inicial, $pagina, $ids_proveedor_filter
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

        return [
            'filas'     => $filas,
            'actual'    => $actual,
            'elementos' => $elementos,
        ];
    }
}
