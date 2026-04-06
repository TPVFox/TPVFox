<?php

include_once $URLCom . '/modulos/mod_informes/clases/CosteFluctuacionCalculator.php';

$params = [
    'fecha_inicio' => trim($_POST['fecha_inicio'] ?? date('Y-01-01')),
    'fecha_final' => trim($_POST['fecha_final'] ?? date('Y-m-d')),
    'min_recepciones' => (int)($_POST['min_recepciones'] ?? 3),
    'min_meses' => (int)($_POST['min_meses'] ?? 3),
    'incluir_proveedor_especial' => (int)($_POST['incluir_proveedor_especial'] ?? 0),
    'familias' => trim($_POST['familias'] ?? ''),
    'agrupacion' => trim($_POST['agrupacion'] ?? 'articulo'),
];

$calc = new CosteFluctuacionCalculator($BDTpv);
$data = $calc->calcular($params);

if (isset($data['error'])) {
    $respuesta['error'] = $data['error'];
    return;
}

$respuesta['coste_fluctuacion'] = $data;
