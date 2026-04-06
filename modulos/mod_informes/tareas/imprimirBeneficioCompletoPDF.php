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
$familiasDatos = $ret;

$esGlobal = ($global === 1 || (string)$idN1 === '__global__');
if (!$esGlobal && $idN1 !== '') {
    $familiasDatos = array_values(array_filter($familiasDatos, fn($f) => (string)$f['idN1'] === (string)$idN1));
}

$nombreAmbito = $esGlobal ? 'GLOBAL' : ($familiasDatos[0]['nombreN1'] ?? 'Seleccionado');

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

$htmlCabecera = '
<table border="0" cellpadding="2" cellspacing="0" width="100%">
    <tr>
        <td width="75%"><font size="12"><b>Beneficio por familia - Completo</b></font></td>
        <td width="25%" align="right"><font size="7" color="#666666">Generado: ' . date('d/m/Y H:i') . '</font></td>
    </tr>
    <tr>
        <td colspan="2" style="border-bottom:1px solid #cccccc; padding-bottom:2px;">
            <font size="7" color="#333333">
                <b>Ambito:</b> ' . htmlspecialchars($nombreAmbito) . '
                &nbsp;|&nbsp;
                <b>Periodo:</b> ' . htmlspecialchars($fechaInicio) . ' - ' . htmlspecialchars($fechaFinal) . '
            </font>
        </td>
    </tr>
</table>';

$filasHtml = '';
$zebra = ['#ffffff', '#f5f8fb'];
$idx = 0;

foreach ($familiasDatos as $familia) {
    $filasHtml .= '<tr nobr="true" bgcolor="#d0d8e4'><td colspan="9"><font><b>FAMILIA: ' . htmlspecialchars($familia['nombreN1'] ?? '') . '</b></font></td></tr>';

    $subfamilias = $familia['subfamilias'] ?? [];
    foreach ($subfamilias as $sf) {
        $filasHtml .= '<tr nobr="true" bgcolor="#eef1f5'><td colspan="9"><font><b>Subfamilia: ' . htmlspecialchars($sf['nombreN2'] ?? '') . '</b></font></td></tr>';

        foreach ($sf['articulos'] ?? [] as $fila) {
            $bg = $zebra[$idx % 2];
            $idx++;
            $colorMargen = ((float)$fila['margen_pct'] < 0) ? '#c0392b' : '#2c3e50';
            [$urlMayor, $urlProducto] = $buildEnlacesProducto((int)$fila['idArticulo']);

            $filasHtml .= '<tr nobr="true" bgcolor="' . $bg . '">'
                . '<td width="34%" align="left">' . htmlspecialchars((string)($fila['articulo_name'] ?? '')) . '</td>'
                . '<td width="6%" align="center">' . (int)$fila['idArticulo'] . '</td>'
                . '<td width="8%" align="right">' . number_format((float)$fila['totalUnidades'], 3, ',', '.') . '</td>'
                . '<td width="10%" align="right">' . number_format((float)$fila['pvpSiva'], 4, ',', '.') . '</td>'
                . '<td width="10%" align="right">' . number_format((float)$fila['costeUsado'], 4, ',', '.') . '</td>'
                . '<td width="10%" align="right">' . number_format((float)$fila['totalVenta'], 2, ',', '.') . '</td>'
                . '<td width="8%" align="right">' . number_format((float)$fila['valorMerma'], 2, ',', '.') . '</td>'
                . '<td width="8%" align="right"><font color="' . $colorMargen . '"><b>' . number_format((float)$fila['beneficio'], 2, ',', '.') . '</b></font></td>'
                . '<td width="6%" align="right"><font color="' . $colorMargen . '"><b>' . number_format((float)$fila['margen_pct'], 2, ',', '.') . '%</b></font></td>'
                . '</tr>';
        }
    }

    // resumen familia
    $filasHtml .= '<tr nobr="true" bgcolor="#2c3e50"><td colspan="9" align="right"><font color="#ffffff"><b>Total familia: ' . number_format((float)($familia['total_linea'] ?? 0), 2, ',', '.') . '</b></font></td></tr>';
}

if ($filasHtml === '') {
    $filasHtml = '<tr><td colspan="9" align="center"><font color="#555555">Sin datos para el periodo.</font></td></tr>';
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
            <th width="6%" align="right"><font color="#ffffff"><b>MARGEN %</b></font></th>
        </tr>
    </thead>
    <tbody>' . $filasHtml . '</tbody>
</table>';

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

?>
