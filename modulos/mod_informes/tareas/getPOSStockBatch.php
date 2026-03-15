<?php
// Consulta paginada de incidencias POSStock — patrón mod_reorganizacion.
// Devuelve JSON { filas: [...], actual: int, total: int, elementos: int }
//
// POST esperado:
//   fecha_inicio_movimientos, fecha_fin_movimientos,
//   fecha_inicio_stock,       fecha_fin_stock,
//   inicial  (int, offset del lote — 0 para el primero),
//   pagina   (int, tamaño del lote — por defecto 150),
//   tipo_incidencia, familias_incluir, familias_excluir  (igual que getPOSStockData)
ini_set('memory_limit', '512M');
set_time_limit(300);

$fi_mov   = $_POST['fecha_inicio_movimientos'] ?? '';
$ff_mov   = $_POST['fecha_fin_movimientos']    ?? '';
$fi_stock = $_POST['fecha_inicio_stock']       ?? '';
$ff_stock = $_POST['fecha_fin_stock']          ?? '';

$fecha_re = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($fecha_re, $fi_mov) || !preg_match($fecha_re, $ff_mov)
 || !preg_match($fecha_re, $fi_stock) || !preg_match($fecha_re, $ff_stock)) {
    $respuesta['error'] = 'Fechas no válidas o incompletas.';
    return;
}

$inicial = max(0, (int)($_POST['inicial'] ?? 0));
$pagina  = max(1, min(500, (int)($_POST['pagina'] ?? 150)));

$ClaseParametros = new ClaseParametros('parametros.xml');
$posstock_node   = $ClaseParametros->getNode('configuracion/posstock');

$casos_validos = ['caso1', 'caso2', 'caso3a', 'caso3b', 'caso4', 'caso5'];
$casos_incluir = [];
foreach (explode(',', $_POST['casos_incluir'] ?? '') as $c) {
    $c = trim($c);
    if (in_array($c, $casos_validos, true)) $casos_incluir[] = $c;
}

$familias_incluir = [];
$familias_excluir = [];
foreach (explode(',', $_POST['familias_incluir'] ?? '') as $id) {
    $id = (int)trim($id);
    if ($id > 0) $familias_incluir[] = $id;
}
foreach (explode(',', $_POST['familias_excluir'] ?? '') as $id) {
    $id = (int)trim($id);
    if ($id > 0) $familias_excluir[] = $id;
}

$params = [
    'fecha_inicio_movimientos'    => $fi_mov,
    'fecha_fin_movimientos'       => $ff_mov,
    'fecha_inicio_stock'          => $fi_stock,
    'fecha_fin_stock'             => $ff_stock,
    'umbral_sobrestock'           => ((float)(string)$posstock_node->umbral_sobrestock) / 100.0,
    'umbral_caducidad_semanas'    => (int)(string)$posstock_node->umbral_semanas_desde_ultima_venta,
    'umbral_sin_rotacion_semanas' => (int)(string)$posstock_node->umbral_semanas_sin_rotacion,
    'casos_incluir'               => $casos_incluir,
    'familias_incluir'            => $familias_incluir,
    'familias_excluir'            => $familias_excluir,
];

$posstock  = new ClasePosstock($BDTpv);
$resultado = $posstock->getIncidenciasBatch($params, $inicial, $pagina);

if (isset($resultado['error'])) {
    $respuesta['error'] = $resultado['error'];
    return;
}

$respuesta['filas']     = $resultado['filas'];
$respuesta['actual']    = $resultado['actual'];
$respuesta['elementos'] = $resultado['elementos'];
