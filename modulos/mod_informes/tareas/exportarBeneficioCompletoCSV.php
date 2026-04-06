<?php

include_once __DIR__ . '/../../../inicial.php';
include_once $URLCom . '/modulos/mod_informes/clases/ClaseInformes.php';

$fechaInicio = isset($_GET['Finicio']) ? trim((string)$_GET['Finicio']) : '';
$fechaFinal = isset($_GET['Ffinal']) ? trim((string)$_GET['Ffinal']) : '';
$opcion = isset($_GET['opcion']) ? (int)$_GET['opcion'] : 3;
$idN1 = $_GET['idN1'] ?? '';
$global = isset($_GET['global']) ? (int)$_GET['global'] : 0;
$familias = isset($_GET['familias']) ? (string)$_GET['familias'] : '';

if ($fechaInicio === '' || $fechaFinal === '' ) {
    http_response_code(400);
    echo 'Faltan parametros obligatorios.';
    exit;
}

$parametros = [
    'id' => 6,
    'Finicio' => $fechaInicio,
    'Ffinal' => $fechaFinal,
    'opcion' => $opcion,
    'familias' => $familias,
];

$calc = new ClaseInformes();
$ret = $calc->BeneficioFamilias($parametros);
$familiasDatos = $ret; // array of familias N1

$esGlobal = ($global === 1 || (string)$idN1 === '__global__');

if (!$esGlobal && $idN1 !== '') {
    // Filter to single family if requested
    $familiasDatos = array_values(array_filter($familiasDatos, fn($f) => (string)$f['idN1'] === (string)$idN1));
}

$nombreAmbito = $esGlobal ? 'GLOBAL' : ($familiasDatos[0]['nombreN1'] ?? 'Seleccionado');

$nombreFichero = 'beneficio_completo_' . ($esGlobal ? 'global' : preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string)$nombreAmbito)) . '_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombreFichero . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, ['Informe', 'Beneficio por familia - Completo'], ';');
fputcsv($out, ['Ambito', $nombreAmbito], ';');
fputcsv($out, ['Periodo', $fechaInicio . ' - ' . $fechaFinal], ';');
fputcsv($out, [] , ';');

foreach ($familiasDatos as $familia) {
    fputcsv($out, ['FAMILIA', $familia['nombreN1'] ?? '', 'ID', $familia['idN1'] ?? ''], ';');
    fputcsv($out, ['Resumen', 'Total linea', 'Num referencias'], ';');
    fputcsv($out, ['', $familia['total_linea'] ?? '', $familia['num_referencias'] ?? ''], ';');
    fputcsv($out, [] , ';');

    $subfamilias = $familia['subfamilias'] ?? [];
    foreach ($subfamilias as $sf) {
        fputcsv($out, ['SUBFAMILIA', $sf['nombreN2'] ?? '', 'ID', $sf['idN2'] ?? ''], ';');
        fputcsv($out, ['Resumen sub', 'Total linea', 'Num referencias'], ';');
        fputcsv($out, ['', $sf['total_linea'] ?? '', $sf['num_referencias'] ?? ''], ';');
        fputcsv($out, [] , ';');

        // Articles header
        fputcsv($out, [
            'NOMBRE', 'ID', 'CANTIDAD', 'PVP', 'COSTE', 'VENTA', 'MERMA', 'BENEFICIO REAL', 'MARGEN %'
        ], ';');

        $articulos = $sf['articulos'] ?? [];
        foreach ($articulos as $art) {
            fputcsv($out, [
                $art['articulo_name'] ?? '',
                $art['idArticulo'] ?? '',
                number_format((float)($art['totalUnidades'] ?? 0), 3, '.', ''),
                number_format((float)($art['pvpSiva'] ?? 0), 4, '.', ''),
                number_format((float)($art['costeUsado'] ?? 0), 4, '.', ''),
                number_format((float)($art['totalVenta'] ?? 0), 2, '.', ''),
                number_format((float)($art['valorMerma'] ?? 0), 2, '.', ''),
                number_format((float)($art['beneficio'] ?? 0), 2, '.', ''),
                number_format((float)($art['margen_pct'] ?? 0), 2, '.', ''),
            ], ';');
        }

        fputcsv($out, [] , ';');
    }

    fputcsv($out, [] , ';');
}

fclose($out);
exit;

?>
