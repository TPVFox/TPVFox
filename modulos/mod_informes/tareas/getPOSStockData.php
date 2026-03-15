<?php
// @ Objetivo: calcular incidencias POSStock para el periodo recibido por POST.
// Periodos largos (trimestral, semestral, anual) requieren más recursos.
ini_set('memory_limit', '512M');
set_time_limit(300);
// Recibe las fechas del periodo y devuelve JSON { filas: [...], periodo: {...} }.
//
// POST esperado:
//   fecha_inicio_movimientos, fecha_fin_movimientos,
//   fecha_inicio_stock,       fecha_fin_stock

$fi_mov   = $_POST['fecha_inicio_movimientos'] ?? '';
$ff_mov   = $_POST['fecha_fin_movimientos']    ?? '';
$fi_stock = $_POST['fecha_inicio_stock']       ?? '';
$ff_stock = $_POST['fecha_fin_stock']          ?? '';

// Validar fechas
$fecha_re = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($fecha_re, $fi_mov) || !preg_match($fecha_re, $ff_mov)
 || !preg_match($fecha_re, $fi_stock) || !preg_match($fecha_re, $ff_stock)) {
    $respuesta['error'] = 'Fechas no válidas o incompletas.';
    return;
}

// Leer umbrales desde parametros.xml (cache si existe)
$ClaseParametros = new ClaseParametros('parametros.xml');
$posstock_node   = $ClaseParametros->getNode('configuracion/posstock');

// Filtro de casos: lista de IDs de caso separados por coma
$casos_validos = ['caso1', 'caso2', 'caso3a', 'caso3b', 'caso4', 'caso5'];
$casos_incluir = [];
foreach (explode(',', $_POST['casos_incluir'] ?? '') as $c) {
    $c = trim($c);
    if (in_array($c, $casos_validos, true)) $casos_incluir[] = $c;
}

// Filtro de familias: dos listas independientes de IDs separados por coma
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
    // Convertimos el umbral de porcentaje a valor decimal esperado por la lógica (p.ej. 50 -> 0.5)
    'umbral_sobrestock'           => ((float)(string)$posstock_node->umbral_sobrestock) / 100.0,
    'umbral_caducidad_semanas'    => (int)(string)$posstock_node->umbral_semanas_desde_ultima_venta,
    'umbral_sin_rotacion_semanas' => (int)(string)$posstock_node->umbral_semanas_sin_rotacion,
    'casos_incluir'               => $casos_incluir,
    'min_ventas_c5'               => max(3, (int)($_POST['min_ventas_c5'] ?? 3)),
    'familias_incluir'            => $familias_incluir,
    'familias_excluir'            => $familias_excluir,
];

$posstock = new ClasePosstock($BDTpv);
$filas    = $posstock->getIncidencias($params);

if (isset($filas['error'])) {
    $respuesta['error'] = $filas['error'];
    return;
}

$respuesta['filas']   = $filas;
$respuesta['periodo'] = [
    'fecha_inicio_movimientos' => $fi_mov,
    'fecha_fin_movimientos'    => $ff_mov,
    'fecha_inicio_stock'       => $fi_stock,
    'fecha_fin_stock'          => $ff_stock,
    'total_incidencias'        => count($filas),
];
