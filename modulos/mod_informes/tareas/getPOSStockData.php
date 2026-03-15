<?php
// @ Objetivo: calcular incidencias POSStock para el periodo recibido por POST.
// Periodos largos (trimestral, semestral, anual) requieren más recursos.
ini_set('memory_limit', '512M');
set_time_limit(300);

require_once __DIR__ . '/helpers/parsearParamsPosstock.php';

$params = parsearParamsPosstock($respuesta);
if ($params === null) return;

$posstock = new ClasePosstock($BDTpv);
$filas    = $posstock->getIncidencias($params);

if (isset($filas['error'])) {
    $respuesta['error'] = $filas['error'];
    return;
}

$respuesta['filas']   = $filas;
$respuesta['periodo'] = [
    'fecha_inicio_movimientos' => $params['fecha_inicio_movimientos'],
    'fecha_fin_movimientos'    => $params['fecha_fin_movimientos'],
    'fecha_inicio_stock'       => $params['fecha_inicio_stock'],
    'fecha_fin_stock'          => $params['fecha_fin_stock'],
    'total_incidencias'        => count($filas),
];
