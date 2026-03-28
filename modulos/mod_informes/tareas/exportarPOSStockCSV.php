<?php
// @ Objetivo: generar y descargar el CSV de incidencias POSStock para el periodo recibido.
// Misma lógica que getPOSStockData pero devuelve un fichero CSV con BOM UTF-8.
// Llama a exit() para evitar que tareas.php emita el json_encode final.

require_once __DIR__ . '/helpers/parsearParamsPosstock.php';

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
    if ($f['tipo'] === 'Stock Negativo') {
        $detalle = 'Stock actual: ' . (isset($f['stock_actual'])
            ? number_format((float)$f['stock_actual'], 2, '.', '')
            : '-');
    } elseif ($f['tipo'] === 'Desajuste Puntual de Stock') {
        $detalle = 'Stock final: ' . (isset($f['stock_actual'])
            ? number_format((float)$f['stock_actual'], 2, '.', '')
            : '-')
            . ' | Min. intra-periodo: ' . (isset($f['min_balance'])
            ? number_format((float)$f['min_balance'], 2, '.', '')
            : '-');
    } elseif ($f['tipo'] === 'Entrada con stock alto') {
        $detalle = 'Stock previo: ' . number_format((float)$f['stock_previo'], 2, '.', '')
            . ' | Entrada: '   . number_format((float)$f['ncant'], 2, '.', '')
            . ' | Fecha: '     . ($f['fecha'] ?? '-');
    } elseif ($f['tipo'] === 'Riesgo de caducidad teórica') {
        $detalle = 'Ult. venta: '   . ($f['ultima_venta'] ?? '-')
            . ' | '            . ($f['semanas_desde_ultima_venta'] ?? '-') . ' sem.';
    } elseif ($f['tipo'] === 'Venta Cero (Posible Rotura Física)') {
        $estado  = $f['fecha_fin_rotura'] ? 'Recuperada ' . $f['fecha_fin_rotura'] : 'En curso';
        $detalle = 'Ult. venta: ' . ($f['ultima_venta'] ?? '-')
            . ' | Rotura desde: ' . ($f['fecha_inicio_rotura'] ?? '-')
            . ' | ' . $estado
            . ' | ' . ($f['dias_rotura'] ?? '-') . ' d';
    } elseif ($f['tipo'] === 'Entrada sin rotación previa') {
        $detalle = $f['ultima_salida']
            ? 'Ult. salida: ' . $f['ultima_salida'] . ' | ' . ($f['semanas_desde_ultima_salida'] ?? '-') . ' sem.'
            : 'Sin salidas registradas';
    } elseif ($f['tipo'] === 'Stock Inactivo en Periodo') {
        $detalle = 'Stock en periodo: ' . (isset($f['stock_actual'])
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
