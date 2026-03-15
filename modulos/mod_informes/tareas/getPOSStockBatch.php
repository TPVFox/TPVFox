<?php
// Consulta paginada de incidencias POSStock — patrón mod_reorganizacion.
// Devuelve JSON { filas: [...], actual: int, elementos: int }
ini_set('memory_limit', '512M');
set_time_limit(300);

require_once __DIR__ . '/helpers/parsearParamsPosstock.php';

$inicial = max(0, (int)($_POST['inicial'] ?? 0));
$pagina  = max(1, min(500, (int)($_POST['pagina'] ?? 150)));

$params = parsearParamsPosstock($respuesta);
if ($params === null) return;

$posstock  = new ClasePosstock($BDTpv);
$resultado = $posstock->getIncidenciasBatch($params, $inicial, $pagina);

if (isset($resultado['error'])) {
    $respuesta['error'] = $resultado['error'];
    return;
}

$respuesta['filas']     = $resultado['filas'];
$respuesta['actual']    = $resultado['actual'];
$respuesta['elementos'] = $resultado['elementos'];
