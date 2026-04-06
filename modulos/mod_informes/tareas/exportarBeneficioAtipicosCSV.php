<?php

include_once __DIR__ . '/../../../inicial.php';
include_once $URLCom . '/modulos/mod_informes/clases/ClaseInformes.php';
include_once __DIR__ . '/helpers/beneficioAtipicos.php';

$fechaInicio = isset($_GET['Finicio']) ? trim((string)$_GET['Finicio']) : '';
$fechaFinal = isset($_GET['Ffinal']) ? trim((string)$_GET['Ffinal']) : '';
$opcion = isset($_GET['opcion']) ? (int)$_GET['opcion'] : 3;
$idN1 = $_GET['idN1'] ?? '';
$global = isset($_GET['global']) ? (int)$_GET['global'] : 0;
$familias = isset($_GET['familias']) ? (string)$_GET['familias'] : '';

if ($fechaInicio === '' || $fechaFinal === '' || ($idN1 === '' && $global !== 1)) {
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
$familiasDatos = $ret['familias'] ?? [];
$esGlobal = ($global === 1 || (string)$idN1 === '__global__');

if ($esGlobal) {
    $atipicos = detectarAtipicosGlobal($familiasDatos);
    $tituloInforme = 'Beneficio por familia - Atipicos global';
    $nombreAmbito = 'GLOBAL';
    $slugAmbito = 'global';
} else {
    $familia = buscarFamiliaBeneficio($familiasDatos, $idN1);
    if ($familia === null) {
        http_response_code(404);
        echo 'No se encontro la familia solicitada.';
        exit;
    }
    $atipicos = detectarAtipicosFamilia($familia);
    $tituloInforme = 'Beneficio por familia - Atipicos';
    $nombreAmbito = $familia['nombreN1'] ?? 'Sin familia';
    $slugAmbito = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string)$nombreAmbito);
}

$filas = $atipicos['filas'];

$esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$esHttps = $esHttps || (isset($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443');
$esquema = $esHttps ? 'https' : 'http';
$hostHttp = $_SERVER['HTTP_HOST'] ?? '';
$baseAbsoluta = $hostHttp !== '' ? ($esquema . '://' . $hostHttp) : '';
$fechaMayorInicio = date('Y-01-01');
$fechaMayorFinal = date('Y-m-d');

$buildEnlacesProducto = static function (int $idArticulo) use ($HostNombre, $baseAbsoluta, $fechaMayorInicio, $fechaMayorFinal): array {
    $queryMayor = http_build_query([
        'idArticulo' => $idArticulo,
        'fecha_inicial' => $fechaMayorInicio,
        'fecha_final' => $fechaMayorFinal,
        'stock' => 0,
    ]);
    $urlMayor = $baseAbsoluta . $HostNombre . '/modulos/mod_producto/DetalleMayor.php?' . $queryMayor;
    $urlProducto = $baseAbsoluta . $HostNombre . '/modulos/mod_producto/producto.php?id=' . $idArticulo;

    return [$urlMayor, $urlProducto];
};

$nombreFichero = 'beneficio_atipicos_' . $slugAmbito . '_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombreFichero . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, ['Informe', $tituloInforme], ';');
fputcsv($out, ['Ambito', $nombreAmbito], ';');
fputcsv($out, ['Periodo', $fechaInicio . ' - ' . $fechaFinal], ';');
fputcsv($out, ['Criterio', 'margen fuera de media +/- 3sd por subfamilia o margen negativo'], ';');
fputcsv($out, ['Articulos analizados', $atipicos['total_articulos']], ';');
fputcsv($out, ['Articulos atipicos', $atipicos['total_atipicos']], ';');
fputcsv($out, [], ';');

fputcsv($out, [
    'NOMBRE',
    'ID',
    'CANTIDAD',
    'PVP',
    'COSTE',
    'VENTA',
    'MERMA',
    'BENEFICIO REAL',
    'MARGEN %',
    'ENLACE MAYOR',
    'ENLACE PRODUCTO',
], ';');

foreach ($filas as $fila) {
    [$urlMayor, $urlProducto] = $buildEnlacesProducto((int)$fila['idArticulo']);

    fputcsv($out, [
        $fila['articulo_name'] ?? '',
        $fila['idArticulo'],
        number_format((float)$fila['totalUnidades'], 3, '.', ''),
        number_format((float)$fila['pvpSiva'], 4, '.', ''),
        number_format((float)$fila['costeUsado'], 4, '.', ''),
        number_format((float)$fila['totalVenta'], 2, '.', ''),
        number_format((float)$fila['valorMerma'], 2, '.', ''),
        number_format((float)$fila['beneficio'], 2, '.', ''),
        number_format((float)$fila['margen_pct'], 2, '.', ''),
        $urlMayor,
        $urlProducto,
    ], ';');
}

fclose($out);
exit;
