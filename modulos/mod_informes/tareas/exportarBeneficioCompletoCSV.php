<?php

include_once __DIR__ . '/../../../inicial.php';
include_once $URLCom . '/modulos/mod_informes/clases/ClaseInformes.php';

$fechaInicio = isset($_GET['Finicio']) ? trim((string)$_GET['Finicio']) : '';
$fechaFinal = isset($_GET['Ffinal']) ? trim((string)$_GET['Ffinal']) : '';
$opcion = isset($_GET['opcion']) ? (int)$_GET['opcion'] : 3;
$idN1 = $_GET['idN1'] ?? '';
$global = isset($_GET['global']) ? (int)$_GET['global'] : 0;
$familias = isset($_GET['familias']) ? (string)$_GET['familias'] : '';

if ($fechaInicio === '' || $fechaFinal === '') {
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
if (!$esGlobal && $idN1 !== '') {
    $familiasDatos = array_values(array_filter($familiasDatos, fn($f) => (string)$f['idN1'] === (string)$idN1));
}

$nombreAmbito = $esGlobal ? 'GLOBAL' : ($familiasDatos[0]['nombreN1'] ?? 'Seleccionado');
$opcionLabels = [1 => 'Solo familias', 2 => 'Familias y subfamilias', 3 => 'Familias subfamilias y articulos', 4 => 'Seleccion de familias'];

$nombreFichero = 'beneficio_completo_' . ($esGlobal ? 'global' : preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string)$nombreAmbito)) . '_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombreFichero . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, ['Informe', 'Beneficio por familia'], ';');
fputcsv($out, ['Ambito', $nombreAmbito], ';');
fputcsv($out, ['Vista', $opcionLabels[$opcion] ?? ''], ';');
fputcsv($out, ['Periodo', $fechaInicio . ' - ' . $fechaFinal], ';');
fputcsv($out, [], ';');

// ── Opcion 1: Solo familias ───────────────────────────────────────────────────
if ($opcion === 1) {
    fputcsv($out, ['FAMILIA', 'REF.', 'VENTA', 'COSTE', 'MERMA', 'BENEFICIO', 'MARGEN %'], ';');
    foreach ($familiasDatos as $familia) {
        fputcsv($out, [
            $familia['nombreN1'] ?? '',
            $familia['num_referencias'] ?? '',
            number_format((float)($familia['totalVenta'] ?? 0), 2, '.', ''),
            number_format((float)($familia['totalCoste'] ?? 0), 2, '.', ''),
            number_format((float)($familia['totalMerma'] ?? 0), 2, '.', ''),
            number_format((float)($familia['beneficio'] ?? 0), 2, '.', ''),
            number_format((float)($familia['margen_pct'] ?? 0), 2, '.', ''),
        ], ';');
    }

// ── Opcion 2: Familias y subfamilias ──────────────────────────────────────────
} elseif ($opcion === 2) {
    fputcsv($out, ['NIVEL', 'NOMBRE', 'REF.', 'VENTA', 'COSTE', 'MERMA', 'BENEFICIO', 'MARGEN %'], ';');
    foreach ($familiasDatos as $familia) {
        fputcsv($out, [
            'FAMILIA',
            $familia['nombreN1'] ?? '',
            $familia['num_referencias'] ?? '',
            number_format((float)($familia['totalVenta'] ?? 0), 2, '.', ''),
            number_format((float)($familia['totalCoste'] ?? 0), 2, '.', ''),
            number_format((float)($familia['totalMerma'] ?? 0), 2, '.', ''),
            number_format((float)($familia['beneficio'] ?? 0), 2, '.', ''),
            number_format((float)($familia['margen_pct'] ?? 0), 2, '.', ''),
        ], ';');
        foreach ($familia['subfamilias'] ?? [] as $sf) {
            fputcsv($out, [
                'subfamilia',
                '  ' . ($sf['nombreN2'] ?? ''),
                $sf['num_referencias'] ?? '',
                number_format((float)($sf['totalVenta'] ?? 0), 2, '.', ''),
                number_format((float)($sf['totalCoste'] ?? 0), 2, '.', ''),
                number_format((float)($sf['totalMerma'] ?? 0), 2, '.', ''),
                number_format((float)($sf['beneficio'] ?? 0), 2, '.', ''),
                number_format((float)($sf['margen_pct'] ?? 0), 2, '.', ''),
            ], ';');
        }
        fputcsv($out, [], ';');
    }

// ── Opcion 3 / 4: Familias, subfamilias y articulos ───────────────────────────
} else {
    fputcsv($out, ['NIVEL', 'NOMBRE', 'ID', 'CANTIDAD', 'PVP', 'COSTE', 'VENTA', 'MERMA', 'BENEFICIO', 'MARGEN %'], ';');
    foreach ($familiasDatos as $familia) {
        fputcsv($out, [
            'FAMILIA',
            $familia['nombreN1'] ?? '',
            '',
            '',
            '',
            '',
            number_format((float)($familia['totalVenta'] ?? 0), 2, '.', ''),
            number_format((float)($familia['totalMerma'] ?? 0), 2, '.', ''),
            number_format((float)($familia['beneficio'] ?? 0), 2, '.', ''),
            number_format((float)($familia['margen_pct'] ?? 0), 2, '.', ''),
        ], ';');
        foreach ($familia['subfamilias'] ?? [] as $sf) {
            fputcsv($out, [
                'subfamilia',
                '  ' . ($sf['nombreN2'] ?? ''),
                '',
                '',
                '',
                '',
                number_format((float)($sf['totalVenta'] ?? 0), 2, '.', ''),
                number_format((float)($sf['totalMerma'] ?? 0), 2, '.', ''),
                number_format((float)($sf['beneficio'] ?? 0), 2, '.', ''),
                number_format((float)($sf['margen_pct'] ?? 0), 2, '.', ''),
            ], ';');
            foreach ($sf['articulos'] ?? [] as $art) {
                fputcsv($out, [
                    'articulo',
                    '    ' . ($art['articulo_name'] ?? ''),
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
        }
        fputcsv($out, [], ';');
    }
}

fclose($out);
exit;
