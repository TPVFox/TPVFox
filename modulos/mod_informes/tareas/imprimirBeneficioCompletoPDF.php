<?php

include_once __DIR__ . '/../../../inicial.php';
include_once $RutaServidor . $HostNombre . '/clases/claseimprimir.php';
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
$opcionLabels = [1 => 'Solo familias', 2 => 'Familias y subfamilias', 3 => 'Familias, subfamilias y articulos', 4 => 'Seleccion de familias'];

$htmlCabecera = '
<table border="0" cellpadding="2" cellspacing="0" width="100%">
    <tr>
        <td width="75%"><font size="12"><b>Beneficio por familia</b></font></td>
        <td width="25%" align="right"><font size="7" color="#666666">Generado: ' . date('d/m/Y H:i') . '</font></td>
    </tr>
    <tr>
        <td colspan="2" style="border-bottom:1px solid #cccccc; padding-bottom:2px;">
            <font size="7" color="#333333">
                <b>Ambito:</b> ' . htmlspecialchars($nombreAmbito) . '
                &nbsp;|&nbsp;
                <b>Vista:</b> ' . htmlspecialchars($opcionLabels[$opcion] ?? '') . '
                &nbsp;|&nbsp;
                <b>Periodo:</b> ' . htmlspecialchars($fechaInicio) . ' - ' . htmlspecialchars($fechaFinal) . '
            </font>
        </td>
    </tr>
</table>';

$filasHtml = '';
$zebra = ['#ffffff', '#f5f8fb'];
$idx = 0;

// ── Opcion 1: Solo familias ───────────────────────────────────────────────────
if ($opcion === 1) {
    foreach ($familiasDatos as $familia) {
        $cls = (float)($familia['margen_pct'] ?? 0) < 0 ? '#c0392b' : '#2c3e50';
        $filasHtml .= '<tr nobr="true" bgcolor="' . $zebra[$idx % 2] . '">'
            . '<td width="40%" align="left"><b>' . htmlspecialchars($familia['nombreN1'] ?? '') . '</b></td>'
            . '<td width="8%" align="center">' . (int)($familia['num_referencias'] ?? 0) . '</td>'
            . '<td width="13%" align="right">' . number_format((float)($familia['totalVenta'] ?? 0), 2, ',', '.') . '</td>'
            . '<td width="13%" align="right">' . number_format((float)($familia['totalCoste'] ?? 0), 2, ',', '.') . '</td>'
            . '<td width="13%" align="right"><font color="#c0392b">' . number_format((float)($familia['totalMerma'] ?? 0), 2, ',', '.') . '</font></td>'
            . '<td width="7%" align="right"><font color="' . $cls . '"><b>' . number_format((float)($familia['beneficio'] ?? 0), 2, ',', '.') . '</b></font></td>'
            . '<td width="6%" align="right"><font color="' . $cls . '"><b>' . number_format((float)($familia['margen_pct'] ?? 0), 1, ',', '.') . '%</b></font></td>'
            . '</tr>';
        $idx++;
    }
    $htmlCuerpo = '
    <table border="1" cellpadding="4" cellspacing="0" width="100%">
        <thead>
            <tr bgcolor="#2c3e50">
                <th width="40%" align="left"><font color="#ffffff"><b>FAMILIA</b></font></th>
                <th width="8%" align="center"><font color="#ffffff"><b>REF.</b></font></th>
                <th width="13%" align="right"><font color="#ffffff"><b>VENTA</b></font></th>
                <th width="13%" align="right"><font color="#ffffff"><b>COSTE</b></font></th>
                <th width="13%" align="right"><font color="#ffffff"><b>MERMA</b></font></th>
                <th width="7%" align="right"><font color="#ffffff"><b>BENEFICIO</b></font></th>
                <th width="6%" align="right"><font color="#ffffff"><b>MARGEN%</b></font></th>
            </tr>
        </thead>
        <tbody>' . ($filasHtml ?: '<tr><td colspan="7" align="center">Sin datos.</td></tr>') . '</tbody>
    </table>';

// ── Opcion 2: Familias y subfamilias ──────────────────────────────────────────
} elseif ($opcion === 2) {
    foreach ($familiasDatos as $familia) {
        $filasHtml .= '<tr nobr="true" bgcolor="#d0d8e4">'
            . '<td colspan="7"><b>FAMILIA: ' . htmlspecialchars($familia['nombreN1'] ?? '') . '</b>'
            . '  <font size="7">'
            . number_format((float)($familia['totalVenta'] ?? 0), 2, ',', '.') . ' €'
            . '  Benef: ' . number_format((float)($familia['beneficio'] ?? 0), 2, ',', '.') . ' €'
            . '  ' . number_format((float)($familia['margen_pct'] ?? 0), 1, ',', '.') . '%'
            . '</font></td></tr>';
        foreach ($familia['subfamilias'] ?? [] as $sf) {
            $cls = (float)($sf['margen_pct'] ?? 0) < 0 ? '#c0392b' : '#2c3e50';
            $filasHtml .= '<tr nobr="true" bgcolor="' . $zebra[$idx % 2] . '">'
                . '<td width="40%" align="left" style="padding-left:16px;">' . htmlspecialchars($sf['nombreN2'] ?? '') . '</td>'
                . '<td width="8%" align="center">' . (int)($sf['num_referencias'] ?? 0) . '</td>'
                . '<td width="13%" align="right">' . number_format((float)($sf['totalVenta'] ?? 0), 2, ',', '.') . '</td>'
                . '<td width="13%" align="right">' . number_format((float)($sf['totalCoste'] ?? 0), 2, ',', '.') . '</td>'
                . '<td width="13%" align="right"><font color="#c0392b">' . number_format((float)($sf['totalMerma'] ?? 0), 2, ',', '.') . '</font></td>'
                . '<td width="7%" align="right"><font color="' . $cls . '"><b>' . number_format((float)($sf['beneficio'] ?? 0), 2, ',', '.') . '</b></font></td>'
                . '<td width="6%" align="right"><font color="' . $cls . '"><b>' . number_format((float)($sf['margen_pct'] ?? 0), 1, ',', '.') . '%</b></font></td>'
                . '</tr>';
            $idx++;
        }
    }
    $htmlCuerpo = '
    <table border="1" cellpadding="4" cellspacing="0" width="100%">
        <thead>
            <tr bgcolor="#2c3e50">
                <th width="40%" align="left"><font color="#ffffff"><b>FAMILIA / SUBFAMILIA</b></font></th>
                <th width="8%" align="center"><font color="#ffffff"><b>REF.</b></font></th>
                <th width="13%" align="right"><font color="#ffffff"><b>VENTA</b></font></th>
                <th width="13%" align="right"><font color="#ffffff"><b>COSTE</b></font></th>
                <th width="13%" align="right"><font color="#ffffff"><b>MERMA</b></font></th>
                <th width="7%" align="right"><font color="#ffffff"><b>BENEFICIO</b></font></th>
                <th width="6%" align="right"><font color="#ffffff"><b>MARGEN%</b></font></th>
            </tr>
        </thead>
        <tbody>' . ($filasHtml ?: '<tr><td colspan="7" align="center">Sin datos.</td></tr>') . '</tbody>
    </table>';

// ── Opcion 3 / 4: Familias, subfamilias y articulos ───────────────────────────
} else {
    foreach ($familiasDatos as $familia) {
        $filasHtml .= '<tr nobr="true" bgcolor="#d0d8e4">'
            . '<td colspan="9"><b>FAMILIA: ' . htmlspecialchars($familia['nombreN1'] ?? '') . '</b>'
            . '  <font size="7">'
            . number_format((float)($familia['totalVenta'] ?? 0), 2, ',', '.') . ' €'
            . '  Benef: ' . number_format((float)($familia['beneficio'] ?? 0), 2, ',', '.') . ' €'
            . '  ' . number_format((float)($familia['margen_pct'] ?? 0), 1, ',', '.') . '%'
            . '</font></td></tr>';
        foreach ($familia['subfamilias'] ?? [] as $sf) {
            $filasHtml .= '<tr nobr="true" bgcolor="#eef1f5">'
                . '<td colspan="9" style="padding-left:12px;"><b>Subfamilia: ' . htmlspecialchars($sf['nombreN2'] ?? '') . '</b>'
                . '  <font size="7">'
                . number_format((float)($sf['totalVenta'] ?? 0), 2, ',', '.') . ' €'
                . '  Benef: ' . number_format((float)($sf['beneficio'] ?? 0), 2, ',', '.') . ' €'
                . '  ' . number_format((float)($sf['margen_pct'] ?? 0), 1, ',', '.') . '%'
                . '</font></td></tr>';
            foreach ($sf['articulos'] ?? [] as $fila) {
                $bg = $zebra[$idx % 2];
                $idx++;
                $colorMargen = ((float)($fila['margen_pct'] ?? 0) < 0) ? '#c0392b' : '#2c3e50';
                $filasHtml .= '<tr nobr="true" bgcolor="' . $bg . '">'
                    . '<td width="34%" align="left" style="padding-left:24px;">' . htmlspecialchars((string)($fila['articulo_name'] ?? '')) . '</td>'
                    . '<td width="6%" align="center">' . (int)$fila['idArticulo'] . '</td>'
                    . '<td width="8%" align="right">' . number_format((float)($fila['totalUnidades'] ?? 0), 3, ',', '.') . '</td>'
                    . '<td width="10%" align="right">' . number_format((float)($fila['pvpSiva'] ?? 0), 4, ',', '.') . '</td>'
                    . '<td width="10%" align="right">' . number_format((float)($fila['costeUsado'] ?? 0), 4, ',', '.') . '</td>'
                    . '<td width="10%" align="right">' . number_format((float)($fila['totalVenta'] ?? 0), 2, ',', '.') . '</td>'
                    . '<td width="8%" align="right"><font color="#c0392b">' . number_format((float)($fila['valorMerma'] ?? 0), 2, ',', '.') . '</font></td>'
                    . '<td width="8%" align="right"><font color="' . $colorMargen . '"><b>' . number_format((float)($fila['beneficio'] ?? 0), 2, ',', '.') . '</b></font></td>'
                    . '<td width="6%" align="right"><font color="' . $colorMargen . '"><b>' . number_format((float)($fila['margen_pct'] ?? 0), 1, ',', '.') . '%</b></font></td>'
                    . '</tr>';
            }
        }
    }
    $htmlCuerpo = '
    <table border="1" cellpadding="4" cellspacing="0" width="100%">
        <thead>
            <tr bgcolor="#2c3e50">
                <th width="34%" align="left"><font color="#ffffff"><b>NOMBRE</b></font></th>
                <th width="6%" align="center"><font color="#ffffff"><b>ID</b></font></th>
                <th width="8%" align="right"><font color="#ffffff"><b>CANT.</b></font></th>
                <th width="10%" align="right"><font color="#ffffff"><b>PVP</b></font></th>
                <th width="10%" align="right"><font color="#ffffff"><b>COSTE</b></font></th>
                <th width="10%" align="right"><font color="#ffffff"><b>VENTA</b></font></th>
                <th width="8%" align="right"><font color="#ffffff"><b>MERMA</b></font></th>
                <th width="8%" align="right"><font color="#ffffff"><b>BENEFICIO</b></font></th>
                <th width="6%" align="right"><font color="#ffffff"><b>MARGEN%</b></font></th>
            </tr>
        </thead>
        <tbody>' . ($filasHtml ?: '<tr><td colspan="9" align="center">Sin datos.</td></tr>') . '</tbody>
    </table>';
}

$pdf = new imprimirPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('TPVFox');
$pdf->SetAuthor('mod_informes');
$pdf->SetTitle('Beneficio completo - ' . $nombreAmbito);
$pdf->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$pdf->SetMargins(8, 25, 8);
$pdf->SetAutoPageBreak(true, 12);
$pdf->setCabecera($htmlCabecera);
$pdf->AddPage();
$pdf->writeHTML($htmlCuerpo, true, false, true, false, '');

$fichero = 'beneficio_completo_' . date('Ymd_His') . '.pdf';
$pdf->Output($fichero, 'D');
exit;
