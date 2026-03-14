<?php
// @ Objetivo: generar y descargar el CSV de incidencias POSStock para el periodo recibido.
// Misma lógica que getPOSStockData pero devuelve un fichero CSV con BOM UTF-8.
// Llama a exit() para evitar que tareas.php emita el json_encode final.

$fi_mov   = $_POST['fecha_inicio_movimientos'] ?? '';
$ff_mov   = $_POST['fecha_fin_movimientos']    ?? '';
$fi_stock = $_POST['fecha_inicio_stock']       ?? '';
$ff_stock = $_POST['fecha_fin_stock']          ?? '';

$fecha_re = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($fecha_re, $fi_mov) || !preg_match($fecha_re, $ff_mov)
 || !preg_match($fecha_re, $fi_stock) || !preg_match($fecha_re, $ff_stock)) {
    http_response_code(400);
    echo 'Fechas no válidas o incompletas.';
    exit;
}

$ClaseParametros = new ClaseParametros('parametros.xml');
$posstock_node   = $ClaseParametros->getNode('configuracion/posstock');

$familias_incluir = [];
$familias_excluir = [];
foreach (explode(',', $_POST['familias_incluir'] ?? '') as $id) {
    $id = (int)trim($id); if ($id > 0) $familias_incluir[] = $id;
}
foreach (explode(',', $_POST['familias_excluir'] ?? '') as $id) {
    $id = (int)trim($id); if ($id > 0) $familias_excluir[] = $id;
}

$params = [
    'fecha_inicio_movimientos'   => $fi_mov,
    'fecha_fin_movimientos'      => $ff_mov,
    'fecha_inicio_stock'         => $fi_stock,
    'fecha_fin_stock'            => $ff_stock,
    'umbral_sobrestock'          => (float)(string)$posstock_node->umbral_sobrestock,
    'umbral_caducidad_semanas'   => (int)(string)$posstock_node->umbral_caducidad_semanas,
    'umbral_sin_rotacion_semanas'=> (int)(string)$posstock_node->umbral_sin_rotacion_semanas,
    'familias_incluir'           => $familias_incluir,
    'familias_excluir'           => $familias_excluir,
];

$posstock = new ClasePosstock($BDTpv);
$filas    = $posstock->getIncidencias($params);

if (isset($filas['error'])) {
    http_response_code(500);
    echo 'Error: ' . $filas['error'];
    exit;
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

// Cabecera de columnas
fputcsv($out, [
    'Articulo',
    'Nombre',
    'Tipo incidencia',
    'Severidad',
    'Detalle',
    'Posible causa',
], ';');

foreach ($filas as $f) {
    $detalle = '';
    if ($f['tipo'] === 'Error critico de stock') {
        $detalle = 'Stock actual: ' . (isset($f['stock_actual'])
            ? number_format((float)$f['stock_actual'], 2, '.', '')
            : '-');
    } elseif ($f['tipo'] === 'Entrada con stock alto') {
        $detalle = 'Stock previo: ' . number_format((float)$f['stock_previo'], 2, '.', '')
                 . ' | Entrada: '   . number_format((float)$f['ncant'], 2, '.', '')
                 . ' | Fecha: '     . ($f['fecha'] ?? '-');
    } elseif ($f['tipo'] === 'Riesgo de caducidad teorica') {
        $detalle = 'Ult. venta: '   . ($f['ultima_venta'] ?? '-')
                 . ' | '            . ($f['semanas_sin_venta'] ?? '-') . ' sem.';
    } elseif ($f['tipo'] === 'Entrada sin rotacion previa') {
        $detalle = $f['ultima_salida']
            ? 'Ult. salida: ' . $f['ultima_salida'] . ' | ' . $f['semanas_sin_rotacion'] . ' sem.'
            : 'Sin salidas en el anno';
    } elseif ($f['tipo'] === 'Stock sin entrada anual') {
        $detalle = 'Stock actual: ' . (isset($f['stock_actual'])
            ? number_format((float)$f['stock_actual'], 2, '.', '')
            : '-');
    }

    fputcsv($out, [
        $f['idArticulo'],
        $f['nombre'] ?? '',
        $f['tipo'],
        $f['severidad'],
        $detalle,
        $f['posible_causa'] ?? '',
    ], ';');
}

fclose($out);
exit;
