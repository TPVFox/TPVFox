<?php
// @ Objetivo: generar y descargar el CSV de incidencias POSStock para el periodo recibido.
// Misma lógica que getPOSStockData pero devuelve un fichero CSV con BOM UTF-8.
// Llama a exit() para evitar que tareas.php emita el json_encode final.

require_once __DIR__ . '/helpers/parsearParamsPosstock.php';
require_once __DIR__ . '/helpers/exportarPosstock.php';

$params = parsearParamsPosstock($respuesta);
if ($params === null) {
    http_response_code(400);
    echo $respuesta['error'] ?? 'Error de parámetros.';
    exit;
}

$fi_mov   = $params['fecha_inicio_movimientos'];
$ff_mov   = $params['fecha_fin_movimientos'];
$fi_stock = $params['fecha_inicio_stock'];
$ff_stock = $params['fecha_fin_stock'];

$posstock = new ClasePosstock($BDTpv);
$filas    = $posstock->getIncidencias($params);

if (isset($filas['error'])) {
    http_response_code(500);
    echo 'Error: ' . $filas['error'];
    exit;
}

// Filtrar por artículos visibles si el frontend envió una lista filtrada
if (!empty($params['articulos_filtrados'])) {
    $ids_set = array_flip($params['articulos_filtrados']);
    $filas   = array_values(array_filter($filas, fn($f) => isset($ids_set[(int)$f['idArticulo']])));
}

// Los grupos de columnas marcados en la página; si no llegan, todos
$gruposColumnas = null;
if (isset($_POST['columnas_csv'])) {
    $gruposColumnas = array_filter(explode(',', (string) $_POST['columnas_csv']));
}

// ── Cabeceras HTTP ────────────────────────────────────────────────────────────
$nombre_fichero = 'posstock_' . date('Ymd') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombre_fichero . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// BOM UTF-8 para que Excel en Windows lo abra con la codificación correcta
fwrite($out, "\xEF\xBB\xBF");

// Filas de cabecera informativa (periodo consultado)
fputcsv($out, ['Periodo movimientos', $fi_mov . ' - ' . $ff_mov], ';');
fputcsv($out, ['Stock base',          $fi_stock . ' - ' . $ff_stock], ';');
fputcsv($out, ['Generado',            date('Y-m-d H:i:s')], ';');
fputcsv($out, [], ';'); // línea vacía separadora

// Cabecera de columnas: las seis de siempre y detrás una por cada dato de los grupos elegidos
fputcsv($out, columnasCsvPosstock($gruposColumnas), ';');

foreach ($filas as $f) {
    fputcsv($out, filaCsvPosstock($f, $gruposColumnas), ';');
}

fclose($out);
exit;
