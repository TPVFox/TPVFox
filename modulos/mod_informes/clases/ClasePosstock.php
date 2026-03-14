<?php
/**
 * ClasePosstock — Lógica de datos para el informe POSStock.
 *
 * Usa $BDTpv (conexión mysqli) siguiendo el patrón del proyecto.
 * Solo lectura: no escribe en ninguna tabla de movimientos ni de stock.
 *
 * Tipos de artículo físico confirmados en BD/Update/install_update_v0.0.40.sql:
 *   tipo IN ('unidad', 'peso')
 * Si en el futuro se añaden tipos físicos nuevos, actualizar la constante TIPOS_FISICOS.
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
    public function getMovimientosPeriodo($fecha_inicio, $fecha_fin)
    {
        $fi = $this->db->real_escape_string($fecha_inicio);
        $ff = $this->db->real_escape_string($fecha_fin);

        $sql = "
            -- Entradas: albaranes de proveedor (estado Guardado)
            SELECT
                'entrada_proveedor'    AS tipo_movimiento,
                l.idArticulo,
                l.ncant,
                DATE(c.Fecha)          AS fecha,
                c.id                   AS idDocumento
            FROM albprolinea l
            INNER JOIN albprot      c ON c.id         = l.idalbpro
            INNER JOIN articulos    a ON a.idArticulo  = l.idArticulo
            WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
              AND c.estado       IN ('Guardado', 'Facturado')
              AND l.estadoLinea  = 'Activo'
              AND a.tipo         IN (" . self::TIPOS_FISICOS . ")

            UNION ALL

            -- Salidas: tickets de venta (estado Cerrado)
            SELECT
                'salida_ticket'        AS tipo_movimiento,
                l.idArticulo,
                l.ncant,
                DATE(c.Fecha)          AS fecha,
                c.id                   AS idDocumento
            FROM ticketslinea l
            INNER JOIN ticketst     c ON c.id         = l.idticketst
            INNER JOIN articulos    a ON a.idArticulo  = l.idArticulo
            WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
              AND c.estado       = 'Cerrado'
              AND l.estadoLinea  = 'Activo'
              AND a.tipo         IN (" . self::TIPOS_FISICOS . ")

            UNION ALL

            -- Salidas: albaranes de cliente (estados Guardado, Procesado)
            SELECT
                'salida_albcli'        AS tipo_movimiento,
                l.idArticulo,
                l.ncant,
                DATE(c.Fecha)          AS fecha,
                c.id                   AS idDocumento
            FROM albclilinea l
            INNER JOIN albclit      c ON c.id         = l.idalbcli
            INNER JOIN articulos    a ON a.idArticulo  = l.idArticulo
            WHERE DATE(c.Fecha) BETWEEN '$fi' AND '$ff'
              AND c.estado       IN ('Guardado', 'Procesado')
              AND l.estadoLinea  = 'Activo'
              AND a.tipo         IN (" . self::TIPOS_FISICOS . ")

            ORDER BY idArticulo, fecha
        ";

        $smt = $this->db->query($sql);
        if (!$smt) {
            return ['error' => $this->db->error, 'consulta' => $sql];
        }

        $resultado = [];
        while ($row = $smt->fetch_assoc()) {
            $resultado[] = $row;
        }
        return $resultado;
    }

    /**
     * T4.2 — Stock base acumulado desde inicio del ejercicio hasta fecha_fin_stock.
     *
     * Solo se calcula para los artículos que tuvieron movimiento en la ventana (T4.1),
     * reduciendo el coste de la consulta.
     *
     * Signos:
     *   entradas proveedor  → ncant positivo  (suma al stock)
     *   salidas ticket      → ncant negativo  (resta al stock)
     *   salidas albcli      → ncant negativo  (resta al stock)
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
                  AND l.idArticulo  IN ($ids)

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
                  AND l.idArticulo  IN ($ids)

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
                  AND l.idArticulo  IN ($ids)

            ) AS movimientos_stock
            GROUP BY idArticulo
        ";

        $smt = $this->db->query($sql);
        if (!$smt) {
            return ['error' => $this->db->error, 'consulta' => $sql];
        }

        $resultado = [];
        while ($row = $smt->fetch_assoc()) {
            $resultado[(int)$row['idArticulo']] = [
                'saldo_acumulado' => (float)$row['saldo_acumulado'],
                'ultima_compra'   => $row['ultima_compra'],
                'ultima_venta'    => $row['ultima_venta'],
            ];
        }
        return $resultado;
    }
}
