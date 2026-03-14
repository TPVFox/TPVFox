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
              AND c.estado       = 'Guardado'
              AND l.estadoLinea  = 'Activo'
              AND a.tipo         IN (" . self::TIPOS_FISICOS . ")

            UNION ALL

            -- Salidas: tickets de venta (estado Cobrado)
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
              AND c.estado       = 'Cobrado'
              AND l.estadoLinea  = 'Activo'
              AND a.tipo         IN (" . self::TIPOS_FISICOS . ")

            UNION ALL

            -- Salidas: albaranes de cliente (estado Guardado)
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
              AND c.estado       = 'Guardado'
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
}
