<?php
/**
 * Tarea: renderizarTablaPosstock
 *
 * Recibe el array de incidencias acumulado (JSON) y el contexto de periodo
 * ya calculado por el cliente, devuelve el HTML listo para inyectar en
 * #posstockTablaWrap. De este modo toda la lógica de presentación vive en PHP.
 *
 * POST esperado:
 *   filas_json               string  JSON del array de incidencias acumuladas
 *   fecha_fin_movimientos    string  YYYY-MM-DD
 *   fecha_inicio_stock       string  YYYY-MM-DD
 *   anio                     int
 *   c3b_dias_post            int
 *   c6b_dias_historico       int
 */

include_once $URLCom . '/modulos/mod_informes/tareas/vistas/vistaTablaPosstock.php';

$filasJson = $_POST['filas_json'] ?? '[]';
$filas     = json_decode($filasJson, true);

if (!is_array($filas)) {
    $respuesta['error'] = 'JSON de incidencias no válido.';
    return;
}

$cfg = [
    'fecha_fin_movimientos' => trim($_POST['fecha_fin_movimientos'] ?? ''),
    'fecha_inicio_stock'    => trim($_POST['fecha_inicio_stock']    ?? ''),
    'anio'                  => (int)($_POST['anio']               ?? date('Y')),
    'c3b_dias_post'         => (int)($_POST['c3b_dias_post']       ?? 14),
    'c6b_dias_historico'    => (int)($_POST['c6b_dias_historico']  ?? 90),
];

$respuesta['html'] = renderTablaPosstock($filas, $cfg);
