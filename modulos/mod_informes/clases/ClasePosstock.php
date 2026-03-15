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
     * C5 paso 1 — Fechas de venta únicas (ticket + albcli) por artículo físico.
     *
     * @return array  Filas raw (idArticulo, fecha) o ['error' => ...]
     */
    private function _queryVentasFechasC5(
        string $fi,
        string $ff,
        string $where_fam,
        string $where_ids
    ): array {
        $tipos = self::TIPOS_FISICOS;
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
                  $where_ids
            ) AS ventas
            ORDER BY idArticulo, fecha
        ");
        if (!$smt) return ['error' => $this->db->error];
        $rows = [];
        while ($r = $smt->fetch_assoc()) $rows[] = $r;
        return $rows;
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
            SELECT idArticulo, SUM(day_delta) AS delta_total, MIN(cum_sum) AS min_running
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
        int    $min_u
    ): array {
        $tipos = self::TIPOS_FISICOS;
        $sql = "
            SELECT ent.idArticulo, MAX(sal.fecha) AS ultima_venta
            FROM (
                SELECT DISTINCT l.idArticulo
                FROM albprolinea l
                INNER JOIN albprot   c ON c.id        = l.idalbpro
                INNER JOIN articulos a ON a.idArticulo = l.idArticulo
                WHERE DATE(c.Fecha) BETWEEN '$fi_m' AND '$ff_m'
                  AND c.estado      IN ('Guardado','Facturado')
                  AND l.estadoLinea = 'Activo'
                  AND l.ncant       > 0
                  AND a.tipo        IN ($tipos)
                  $wf $wi
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
            GROUP BY ent.idArticulo
            HAVING MAX(sal.fecha) IS NULL
                OR DATEDIFF('$ff_m', MAX(sal.fecha)) / 7.0 >= $min_u
        ";
        $smt = $this->db->query($sql);
        if (!$smt) return ['error' => $this->db->error];
        $rows = [];
        while ($r = $smt->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    /**
     * Proveedor — idArticulo vinculados a los proveedores indicados.
     * Usa articulosProveedores (estado Activo).
     *
     * @param  string $ids_prov  IN-clause de idProveedor ya preparado
     * @return array  Filas raw (idArticulo) o ['error' => ...]
     */
    private function _queryIdsArticulosByProveedores(string $ids_prov): array
    {
        $smt = $this->db->query(
            "SELECT DISTINCT idArticulo FROM articulosProveedores
             WHERE idProveedor IN ($ids_prov) AND estado = 'Activo'"
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
        $tipos = self::TIPOS_FISICOS;
        $smt = $this->db->query(
            "SELECT DISTINCT ap.idArticulo
             FROM articulosProveedores ap
             INNER JOIN articulos a ON a.idArticulo = ap.idArticulo
             WHERE ap.idProveedor IN ($ids_prov)
               AND ap.estado = 'Activo'
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

        $umbral_sobrestock      = (float)  ($params['umbral_sobrestock']           ?? 0.5);
        $umbral_caducidad       = (int)    ($params['umbral_caducidad_semanas']    ?? 24);
        $umbral_sin_rotacion    = (int)    ($params['umbral_sin_rotacion_semanas'] ?? 12);
        $min_ventas_c5          = (int)    ($params['min_ventas_c5']               ?? 3);
        $modelo_rotura_c5           = (string) ($params['modelo_rotura_c5']              ?? 'binomial');
        $umbral_confianza_c5        = (float)  ($params['umbral_confianza_poisson']      ?? 0.05);
        $c5_incluir_stock_negativo  = (bool)   ($params['c5_incluir_stock_negativo']     ?? false);
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
        $validos_todos = ['caso1', 'caso2', 'caso3a', 'caso3b', 'caso5', 'caso6'];
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
                $ids_filter
            );
            if (isset($c3['error'])) return $c3;
            // Filtrar sub-casos si no se piden ambos
            if (!isset($casos_set['caso3a']) || !isset($casos_set['caso3b'])) {
                $tipos_c3 = [];
                if (isset($casos_set['caso3a'])) $tipos_c3[] = 'Riesgo de caducidad teórica';
                if (isset($casos_set['caso3b'])) $tipos_c3[] = 'Entrada sin rotación previa';
                $c3 = array_values(array_filter($c3, fn($inc) => in_array($inc['tipo'], $tipos_c3, true)));
            }
            $incidencias = array_merge($incidencias, $c3);
        }

        // ── C5 ───────────────────────────────────────────────────────────────
        if (isset($casos_set['caso5'])) {
            $c5 = $this->getIncidenciasCaso5(
                $fi_mov,
                $ff_mov,
                $fi_stock,
                $umbral_sobrestock,
                $familias_incluir,
                $familias_excluir,
                $ids_filter,
                $min_ventas_c5,
                $modelo_rotura_c5,
                $umbral_confianza_c5,
                $c5_incluir_stock_negativo
            );
            if (isset($c5['error'])) return $c5;
            $incidencias = array_merge($incidencias, $c5);
        }

        // ── C6 ───────────────────────────────────────────────────────────────
        if (isset($casos_set['caso6'])) {
            $c6 = $this->getIncidenciasCaso6(
                $fi_mov,
                $ff_mov,
                $fi_stock,
                $familias_incluir,
                $familias_excluir,
                $ids_filter,
                (array)($params['proveedores_incluir'] ?? []),
                (int)  ($params['c6_lead_time_defecto'] ?? 14),
                (float)($params['c6_nivel_servicio']    ?? 0.95),
                $min_ventas_c5,
                $modelo_rotura_c5,
                $umbral_confianza_c5
            );
            if (isset($c6['error'])) return $c6;
            $incidencias = array_merge($incidencias, $c6);
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
            'Agotamiento Estimado'               => 2,  // C6 MEDIA (BN, stock suficiente)
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
            'Agotamiento Estimado'               => '2',
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
            } elseif ($inc['tipo'] === 'Agotamiento Estimado') {
                // C6 CRITICA/ALTA: ordenar por dias_autonomia ascendente (más urgente primero).
                // C6 MEDIA: se ordena por tipo dentro del bloque MEDIA (ya cubierto por orden_tipo_media).
                $dias_pad = str_pad((int)($inc['dias_autonomia'] * 10), 8, '0', STR_PAD_LEFT);
                $sub      = ($inc['severidad'] === 'MEDIA') ? '2' : '0';
                $inc['orden_clave'] = $sev_idx . $sub . $dias_pad . sprintf('%08d', $inc['idArticulo']);
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
        bool  $incluir_stock_negativo = false
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
        $fi_period_ts = $ff_ts - ($periodo_dias - 1) * 86400;
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
        if ($dias_final > $umbral_gap && ($incluir_stock_negativo || $stock_actual > 0)) {
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
            $ko_val = $stock_actual < 0;
            // KO (stock negativo durante rotura en curso) → CRITICA; CR→ALTA; resto→MEDIA
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

    /** Modelo clásico media+3σ para detección de roturas (C5). */
    private function _calcularRoturasC5(int $id, array $fechas_map, float $stock_actual, int $ff_ts, int $min_ventas, bool $incluir_stock_negativo = false): array
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
        $umbral  = $avg_gap + 3 * $sd;
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
        $dias_final   = (int)(($ff_ts - $ts[$n - 1]) / 86400);
        if ($dias_final > $umbral && ($incluir_stock_negativo || $stock_actual > 0)) {
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
            $ko_val = $stock_actual < 0;
            // KO (stock negativo durante rotura en curso) → CRITICA; CR→ALTA; resto→MEDIA
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
     * Calcula el balance mínimo intra-periodo por artículo.
     *
     * Itera los deltas diarios en orden cronológico y registra el mínimo
     * del saldo acumulado TRAS aplicar cada día. Permite detectar momentos
     * en que el stock cayó a negativo aunque el saldo final sea positivo.
     *
     * @param array $movimientos  Resultado de getMovimientosPeriodo()
     * @param array $stock_base   Resultado de getStockBase(), indexado por idArticulo
     *
     * @return array  Indexado por idArticulo → min_balance (float)
     */
    private function calcularMinimosBalance(array $movimientos, array $stock_base): array
    {
        $por_articulo = [];
        foreach ($movimientos as $m) {
            $por_articulo[(int)$m['idArticulo']][] = $m;
        }

        $min_balances = [];
        foreach ($por_articulo as $idArticulo => $movs) {
            $saldo_base = isset($stock_base[$idArticulo])
                ? $stock_base[$idArticulo]['saldo_acumulado']
                : 0.0;

            $delta_por_fecha = [];
            foreach ($movs as $m) {
                $signo = ($m['tipo_movimiento'] === 'entrada_proveedor') ? 1.0 : -1.0;
                $delta_por_fecha[$m['fecha']] = ($delta_por_fecha[$m['fecha']] ?? 0.0)
                    + $signo * (float)$m['ncant'];
            }
            ksort($delta_por_fecha);

            $min  = $saldo_base;
            $acum = $saldo_base;
            foreach ($delta_por_fecha as $delta) {
                $acum += $delta;
                if ($acum < $min) $min = $acum;
            }
            $min_balances[$idArticulo] = $min;
        }

        return $min_balances;
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
        string $fi_stock,            // inicio del rango anual — ventana de análisis histórico
        float  $umbral_sobrestock,   // no usado en C5, recibido por firma uniforme
        array  $familias_incluir,
        array  $familias_excluir,
        array  $ids_filter              = [],
        int    $min_ventas              = 3,
        string $modelo                  = 'binomial',   // 'binomial' | 'poisson'
        float  $umbral_prob             = 0.05,         // solo Poisson: P(0) < umbral → rotura
        bool   $c5_incluir_stock_negativo = false       // si false, excluye stock_actual < 0
    ): array {
        // Usar fi_stock como inicio del análisis para tener suficiente histórico
        // en vistas cortas (semana/quincena). ff_mov sigue siendo el límite.
        $fi   = $this->db->real_escape_string($fi_stock);
        $ff   = $this->db->real_escape_string($ff_mov);

        $where_fam = $this->_familiaWhere($familias_incluir, $familias_excluir);
        $where_ids = $this->_idsWhere($ids_filter);

        // Paso 1: fechas de venta únicas (ticket + albcli) por artículo físico
        $rows_ventas = $this->_queryVentasFechasC5($fi, $ff, $where_fam, $where_ids);
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
                function ($fechas_map, $id) use ($stock_actual) {
                    return ($stock_actual[$id] ?? 0.0) >= 0;
                },
                ARRAY_FILTER_USE_BOTH
            );
        }

        // Paso 3: lógica Caso 5 — detecta TODAS las roturas (binomial o Poisson)
        // periodo_dias se calcula sobre la ventana histórica real (fi_stock→ff_mov)
        // para que la media y σ sean representativos independientemente de la vista activa.
        $ff_ts        = strtotime($ff_mov);
        $periodo_dias = max(1, (int)round(($ff_ts - strtotime($fi_stock)) / 86400) + 1);
        $incidencias  = [];

        foreach ($ventas_fechas as $id => $fechas_map) {
            $roturas = $modelo === 'poisson'
                ? $this->_calcularRoturasC5Poisson(
                    $id,
                    $fechas_map,
                    $stock_actual[$id] ?? 0.0,
                    $ff_ts,
                    $min_ventas,
                    $umbral_prob,
                    $periodo_dias,
                    $c5_incluir_stock_negativo
                )
                : $this->_calcularRoturasC5(
                    $id,
                    $fechas_map,
                    $stock_actual[$id] ?? 0.0,
                    $ff_ts,
                    $min_ventas,
                    $c5_incluir_stock_negativo
                );
            foreach ($roturas as $r) $incidencias[] = $r;
        }

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
     * Proyecta hacia el futuro usando los mismos modelos estadísticos que C5:
     *   · Estima la demanda diaria (d) y su dispersión (Poisson o Binomial Negativa)
     *     a partir de las fechas de venta en el periodo analizado [fi_mov, ff_mov].
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
     * @param int    $min_ventas          Mínimo de días con venta para incluir el artículo
     * @param string $modelo              'binomial' | 'poisson' (selección del modelo C5)
     * @param float  $umbral_prob         Umbral de probabilidad para el modelo Poisson
     *
     * @return array  Filas de incidencia o ['error' => ...]
     */
    private function getIncidenciasCaso6(
        string $fi_mov,
        string $ff_mov,
        string $fi_stock,
        array  $familias_incluir,
        array  $familias_excluir,
        array  $ids_filter,
        array  $proveedores_incluir,
        int    $lead_time_defecto,
        float  $nivel_servicio,
        int    $min_ventas,
        string $modelo,
        float  $umbral_prob
    ): array {
        $fi = $this->db->real_escape_string($fi_mov);
        $ff = $this->db->real_escape_string($ff_mov);

        $where_fam = $this->_familiaWhere($familias_incluir, $familias_excluir);
        $where_ids = $this->_idsWhere($ids_filter);

        // Paso 1 — Fechas de venta únicas por artículo en el periodo (igual que C5)
        $rows_ventas = $this->_queryVentasFechasC5($fi, $ff, $where_fam, $where_ids);
        if (isset($rows_ventas['error'])) return $rows_ventas;
        if (empty($rows_ventas)) return [];

        $ventas_fechas = [];
        foreach ($rows_ventas as $r) {
            $ventas_fechas[(int)$r['idArticulo']][$r['fecha']] = true;
        }

        // Paso 2 — Stock actual en ff_mov por rebobinado (igual que C5)
        $ids_str    = implode(',', array_keys($ventas_fechas));
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

        $ff_ts = strtotime($ff_mov);

        // Periodos no finalizados (trimestral, semestral, anual…): usar solo los días
        // transcurridos hasta hoy. Sin este ajuste, d_diaria = n_ventas / días_totales
        // se diluye artificialmente (ej. anual en marzo → n/365 en lugar de n/74),
        // produciendo ROP y cantidad de pedido muy por debajo de la realidad.
        $ff_efectivo  = min($ff_ts, time());
        $periodo_dias = max(1, (int)round(($ff_efectivo - strtotime($fi_mov)) / 86400) + 1);

        // Granularidad de sub-ventanas (misma lógica que _calcularRoturasC5Poisson)
        if ($periodo_dias < 40)       $chunk_days = 1;
        elseif ($periodo_dias <= 130) $chunk_days = 5;
        else                          $chunk_days = 10;

        $fi_period_ts = $ff_efectivo - ($periodo_dias - 1) * 86400;

        $incidencias = [];

        foreach ($ventas_fechas as $id => $fechas_map) {
            $fechas = array_keys($fechas_map);
            $n      = count($fechas);
            if ($n < $min_ventas) continue;

            $d = $n / $periodo_dias;   // demanda diaria estimada
            if ($d <= 0) continue;

            // ── Parámetros estadísticos (misma lógica que _calcularRoturasC5Poisson) ──
            $n_chunks     = (int)ceil($periodo_dias / $chunk_days);
            $chunk_counts = array_fill(0, $n_chunks, 0);
            foreach (array_map('strtotime', $fechas) as $t) {
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

            // σ_d: desviación estándar de la demanda diaria según modelo
            $modelo_usado = 'Poisson';
            if ($n_chunks >= 4 && $s2_chunk > $mu_chunk + 1e-9 && $mu_chunk > 1e-9) {
                $r_bn         = max(0.001, ($mu_chunk ** 2) / ($s2_chunk - $mu_chunk));
                $sigma_d      = sqrt($d * (1.0 + $d / $r_bn));
                $modelo_usado = 'BN';
            } else {
                $sigma_d = sqrt($d);   // Poisson: varianza = media
            }

            // ── ROP y Stock de Seguridad ──────────────────────────────────────
            $SS  = $z * $sigma_d * sqrt((float)$L);
            $ROP = $d * $L + $SS;

            $stock = $stock_actual[$id] ?? 0.0;
            $dias_autonomia = $d > 0 ? max(0.0, $stock / $d) : PHP_FLOAT_MAX;

            // Solo artículos accionables: bajo ROP, o BN con riesgo latente
            if ($stock >= $ROP && $modelo_usado !== 'BN') continue;

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
                'tipo'            => 'Agotamiento Estimado',
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
                'delta_total' => (float)$r['delta_total'],
                'min_running' => (float)$r['min_running'],
            ];
            $ids[] = (int)$r['idArticulo'];
        }

        $stock_base = empty($stock_base_cache)
            ? $this->getStockBase($ids, $fi_stock, $ff_stock)
            : $stock_base_cache;
        if (isset($stock_base['error'])) return $stock_base;

        $incidencias = [];
        foreach ($delta_map as $id => $data) {
            $saldo_base   = $stock_base[$id]['saldo_acumulado'] ?? 0.0;
            $stock_actual = $saldo_base + $data['delta_total'];
            $min_balance  = $saldo_base + $data['min_running'];

            if ($stock_actual < 0) {
                $incidencias[] = [
                    'idArticulo'    => $id,
                    'tipo'          => 'Stock Negativo',
                    'severidad'     => 'CRITICA',
                    'stock_actual'  => $stock_actual,
                    'min_balance'   => $min_balance,
                    'posible_causa' => 'Inventario negativo al cierre',
                ];
            } elseif ($min_balance < 0) {
                $incidencias[] = [
                    'idArticulo'    => $id,
                    'tipo'          => 'Desajuste Puntual de Stock',
                    'severidad'     => 'ALTA',
                    'stock_actual'  => $stock_actual,
                    'min_balance'   => $min_balance,
                    'posible_causa' => 'Negativo puntual, recuperado al cierre',
                ];
            }
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
    private function getIncidenciasC2(
        string $fi_mov,
        string $ff_mov,
        string $fi_stock,
        string $ff_stock,
        float  $umbral_sobrestock,
        array  $familias_incluir,
        array $familias_excluir,
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

        $incidencias = [];
        foreach ($filas_sql as $e) {
            $id           = (int)$e['idArticulo'];
            $saldo_base   = $stock_base[$id]['saldo_acumulado'] ?? 0.0;
            $stock_previo = $saldo_base + (float)$e['cum_before'];
            $ncant        = (float)$e['ncant'];
            if ($ncant > 0 && $stock_previo >= $ncant * $umbral_sobrestock) {
                $incidencias[] = [
                    'idArticulo'    => $id,
                    'tipo'          => 'Entrada con stock alto',
                    'severidad'     => 'MEDIA',
                    'ncant'         => $ncant,
                    'stock_previo'  => $stock_previo,
                    'fecha'         => $e['fecha'],
                    'posible_causa' => 'Duplicado de albarán o sobrecompra',
                ];
            }
        }
        return $incidencias;
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
        int $umbral_sin_rotacion,
        array  $familias_incluir,
        array $familias_excluir,
        array  $ids_filter = []
    ): array {
        $fi_m  = $this->db->real_escape_string($fi_mov);
        $ff_m  = $this->db->real_escape_string($ff_mov);
        $fi_s  = $this->db->real_escape_string($fi_stock);
        $wf    = $this->_familiaWhere($familias_incluir, $familias_excluir);
        $wi    = $this->_idsWhere($ids_filter);
        $min_u = min($umbral_caducidad, $umbral_sin_rotacion);

        $rows = $this->_queryUltimaVentaC3($fi_m, $ff_m, $fi_s, $wf, $wi, $min_u);
        if (isset($rows['error'])) return $rows;

        $fecha_fin_dt = new DateTime($ff_mov);
        $incidencias  = [];

        foreach ($rows as $r) {
            $id            = (int)$r['idArticulo'];
            $ultima_venta  = $r['ultima_venta'];

            if ($ultima_venta !== null) {
                $semanas = (new DateTime($ultima_venta))->diff($fecha_fin_dt)->days / 7.0;
            } else {
                $semanas = null;
            }

            // C3a: riesgo de caducidad teórica
            if ($ultima_venta !== null && $semanas >= $umbral_caducidad) {
                $incidencias[] = [
                    'idArticulo'                 => $id,
                    'tipo'                       => 'Riesgo de caducidad teórica',
                    'severidad'                  => 'MEDIA',
                    'ultima_venta'               => $ultima_venta,
                    'semanas_desde_ultima_venta' => round($semanas, 1),
                    'posible_causa'              => "Sin ventas >{$umbral_caducidad} sem.",
                ];
            }

            // C3b: entrada sin rotación previa
            if ($ultima_venta === null) {
                $incidencias[] = [
                    'idArticulo'                  => $id,
                    'tipo'                        => 'Entrada sin rotación previa',
                    'severidad'                   => 'BAJA',
                    'ultima_salida'               => null,
                    'semanas_desde_ultima_salida' => null,
                    'posible_causa'               => 'Nunca ha tenido salidas',
                ];
            } elseif ($semanas >= $umbral_sin_rotacion) {
                $incidencias[] = [
                    'idArticulo'                  => $id,
                    'tipo'                        => 'Entrada sin rotación previa',
                    'severidad'                   => 'BAJA',
                    'ultima_salida'               => $ultima_venta,
                    'semanas_desde_ultima_salida' => round($semanas, 1),
                    'posible_causa'               => 'Sin rotación / error de unidad',
                ];
            }
        }
        return $incidencias;
    }

    private function maxFecha(?string $a, ?string $b): ?string
    {
        if ($a === null) return $b;
        if ($b === null) return $a;
        return ($a >= $b) ? $a : $b;
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
                $rows_prov = $this->_queryIdsArticulosByProveedores($ids_str_prov);
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
            $ids_batch = $this->_queryArticulosProveedorPaginados($ids_str_prov, $inicial, $pagina);
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
