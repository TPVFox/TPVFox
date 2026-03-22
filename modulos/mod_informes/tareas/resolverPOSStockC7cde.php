<?php
// Fase 2 de C7: resuelve cruces C7c/d/e sobre el conjunto completo de artículos
// C7a+C7b ya detectados en los lotes de la fase 1.
//
// Recibe los IDs de artículos C7a y C7b (POST ids_c7a / ids_c7b como listas
// de enteros separados por coma), más los parámetros de periodo y configuración
// estándar (mismos que getPOSStockBatch).
//
// Devuelve JSON { cruces: { idArticulo: { posible_cruce_con, cruce_score,
//                                          cruce_nivel, posible_causa } } }
ini_set('memory_limit', '256M');
set_time_limit(120);

require_once __DIR__ . '/helpers/parsearParamsPosstock.php';

// Parsear IDs C7a y C7b enviados por el JS
$ids_c7a = [];
$ids_c7b = [];
foreach (explode(',', $_POST['ids_c7a'] ?? '') as $id) {
    $id = (int)trim($id);
    if ($id > 0) $ids_c7a[] = $id;
}
foreach (explode(',', $_POST['ids_c7b'] ?? '') as $id) {
    $id = (int)trim($id);
    if ($id > 0) $ids_c7b[] = $id;
}

if (empty($ids_c7a) && empty($ids_c7b)) {
    $respuesta['cruces'] = [];
    return;
}

$params = parsearParamsPosstock($respuesta);
if ($params === null) return;

$posstock  = new ClasePosstock($BDTpv);
$resultado = $posstock->resolverC7cde($params, $ids_c7a, $ids_c7b);

if (isset($resultado['error'])) {
    $respuesta['error'] = $resultado['error'];
    return;
}

$respuesta['cruces'] = $resultado['cruces'];
