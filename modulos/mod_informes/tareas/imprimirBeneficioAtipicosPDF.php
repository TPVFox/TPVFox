<?php

include_once __DIR__ . '/../../../inicial.php';
include_once $RutaServidor . $HostNombre . '/clases/claseimprimir.php';
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
    $nombreAmbito = 'GLOBAL';
} else {
    $familia = buscarFamiliaBeneficio($familiasDatos, $idN1);
    if ($familia === null) {
        http_response_code(404);
        echo 'No se encontro la familia solicitada.';
        exit;
    }
    $atipicos = detectarAtipicosFamilia($familia);
    $nombreAmbito = (string)($familia['nombreN1'] ?? 'Sin familia');
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

$htmlCabecera = '
<table border="0" cellpadding="2" cellspacing="0" width="100%">
    <tr>
        <td width="75%"><font size="12"><b>Beneficio por familia - Productos atipicos</b></font></td>
        <td width="25%" align="right"><font size="7" color="#666666">Generado: ' . date('d/m/Y H:i') . '</font></td>
    </tr>
    <tr>
        <td colspan="2" style="border-bottom:1px solid #cccccc; padding-bottom:2px;">
            <font size="7" color="#333333">
                <b>Ambito:</b> ' . htmlspecialchars($nombreAmbito) . '
                &nbsp;|&nbsp;
                <b>Periodo:</b> ' . htmlspecialchars($fechaInicio) . ' - ' . htmlspecialchars($fechaFinal) . '
                &nbsp;|&nbsp;
                <b>Criterio:</b> margen fuera de media +/- 3sd por subfamilia o margen negativo
                &nbsp;|&nbsp;
                <b>Enlaces:</b> M=Mayor, P=Producto
            </font>
        </td>
    </tr>
</table>';

$filasHtml = '';
$zebra = ['#ffffff', '#f5f8fb'];
$idx = 0;

if ($esGlobal) {
    $grupos = [];
    foreach ($filas as $fila) {
        $familia = (string)($fila['familia'] ?? 'Sin familia');
        $subfamilia = (string)($fila['subfamilia'] ?? 'Sin subfamilia');
        if (!isset($grupos[$familia])) {
            $grupos[$familia] = [];
        }
        if (!isset($grupos[$familia][$subfamilia])) {
            $grupos[$familia][$subfamilia] = [];
        }
        $grupos[$familia][$subfamilia][] = $fila;
    }

    ksort($grupos, SORT_NATURAL | SORT_FLAG_CASE);

    foreach ($grupos as $familia => $subfamilias) {
        ksort($subfamilias, SORT_NATURAL | SORT_FLAG_CASE);

        $filasHtml .= '<tr nobr="true" bgcolor="#d0d8e4">'
            . '<td colspan="11"><font><b>FAMILIA: ' . htmlspecialchars($familia) . '</b></font></td>'
            . '</tr>';

        foreach ($subfamilias as $subfamilia => $articulos) {
            $filasHtml .= '<tr nobr="true" bgcolor="#eef1f5">'
                . '<td colspan="11"><font><b>Subfamilia: ' . htmlspecialchars($subfamilia) . '</b></font></td>'
                . '</tr>';

            foreach ($articulos as $fila) {
                $bg = $zebra[$idx % 2];
                $idx++;
                $colorMargen = ((float)$fila['margen_pct'] < 0) ? '#c0392b' : '#d35400';
                [$urlMayor, $urlProducto] = $buildEnlacesProducto((int)$fila['idArticulo']);

                $filasHtml .= '<tr nobr="true" bgcolor="' . $bg . '">'
                    . '<td width="24%" align="left">' . htmlspecialchars((string)($fila['articulo_name'] ?? '')) . '</td>'
                    . '<td width="6%" align="center">' . (int)$fila['idArticulo'] . '</td>'
                    . '<td width="8%" align="right">' . number_format((float)$fila['totalUnidades'], 3, ',', '.') . '</td>'
                    . '<td width="8%" align="right">' . number_format((float)$fila['pvpSiva'], 4, ',', '.') . '</td>'
                    . '<td width="8%" align="right">' . number_format((float)$fila['costeUsado'], 4, ',', '.') . '</td>'
                    . '<td width="8%" align="right">' . number_format((float)$fila['totalVenta'], 2, ',', '.') . '</td>'
                    . '<td width="8%" align="right">' . number_format((float)$fila['valorMerma'], 2, ',', '.') . '</td>'
                    . '<td width="10%" align="right">' . number_format((float)$fila['beneficio'], 2, ',', '.') . '</td>'
                    . '<td width="6%" align="right"><font color="' . $colorMargen . '"><b>'
                    . number_format((float)$fila['margen_pct'], 2, ',', '.') . '%</b></font></td>'
                    . '<td width="7%" align="center"><a href="' . htmlspecialchars($urlMayor) . '">M</a></td>'
                    . '<td width="7%" align="center"><a href="' . htmlspecialchars($urlProducto) . '">P</a></td>'
                    . '</tr>';
            }
        }
    }
} else {
    foreach ($filas as $fila) {
        $bg = $zebra[$idx % 2];
        $idx++;
        $colorMargen = ((float)$fila['margen_pct'] < 0) ? '#c0392b' : '#d35400';
        [$urlMayor, $urlProducto] = $buildEnlacesProducto((int)$fila['idArticulo']);

        $filasHtml .= '<tr nobr="true" bgcolor="' . $bg . '">'
            . '<td width="24%" align="left">' . htmlspecialchars((string)($fila['articulo_name'] ?? '')) . '</td>'
            . '<td width="6%" align="center">' . (int)$fila['idArticulo'] . '</td>'
            . '<td width="8%" align="right">' . number_format((float)$fila['totalUnidades'], 3, ',', '.') . '</td>'
            . '<td width="8%" align="right">' . number_format((float)$fila['pvpSiva'], 4, ',', '.') . '</td>'
            . '<td width="8%" align="right">' . number_format((float)$fila['costeUsado'], 4, ',', '.') . '</td>'
            . '<td width="8%" align="right">' . number_format((float)$fila['totalVenta'], 2, ',', '.') . '</td>'
            . '<td width="8%" align="right">' . number_format((float)$fila['valorMerma'], 2, ',', '.') . '</td>'
            . '<td width="10%" align="right">' . number_format((float)$fila['beneficio'], 2, ',', '.') . '</td>'
            . '<td width="6%" align="right"><font color="' . $colorMargen . '"><b>'
            . number_format((float)$fila['margen_pct'], 2, ',', '.') . '%</b></font></td>'
            . '<td width="7%" align="center"><a href="' . htmlspecialchars($urlMayor) . '">M</a></td>'
            . '<td width="7%" align="center"><a href="' . htmlspecialchars($urlProducto) . '">P</a></td>'
            . '</tr>';
    }
}

if ($filasHtml === '') {
    $filasHtml = '<tr><td colspan="11" align="center"><font color="#555555">Sin atipicos detectados en este ambito.</font></td></tr>';
}

$filasHtml .= '<tr nobr="true" bgcolor="#2c3e50">'
    . '<td colspan="11" align="right"><font color="#ffffff"><b>Articulos analizados: '
    . (int)$atipicos['total_articulos'] . ' | Atipicos: ' . (int)$atipicos['total_atipicos'] . '</b></font></td>'
    . '</tr>';

$htmlCuerpo = '
<table border="1" cellpadding="4" cellspacing="0" width="100%">
    <thead>
        <tr bgcolor="#2c3e50">
            <th width="24%" align="left"><font color="#ffffff"><b>NOMBRE</b></font></th>
            <th width="6%" align="center"><font color="#ffffff"><b>ID</b></font></th>
            <th width="8%" align="right"><font color="#ffffff"><b>CANTIDAD</b></font></th>
            <th width="8%" align="right"><font color="#ffffff"><b>PVP</b></font></th>
            <th width="8%" align="right"><font color="#ffffff"><b>COSTE</b></font></th>
            <th width="8%" align="right"><font color="#ffffff"><b>VENTA</b></font></th>
            <th width="8%" align="right"><font color="#ffffff"><b>MERMA</b></font></th>
            <th width="10%" align="right"><font color="#ffffff"><b>BENEFICIO REAL</b></font></th>
            <th width="6%" align="right"><font color="#ffffff"><b>MARGEN %</b></font></th>
            <th width="7%" align="center"><font color="#ffffff"><b>M</b></font></th>
            <th width="7%" align="center"><font color="#ffffff"><b>P</b></font></th>
        </tr>
    </thead>
    <tbody>' . $filasHtml . '</tbody>
</table>';

$pdf = new imprimirPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('TPVFox');
$pdf->SetAuthor('mod_informes');
$pdf->SetTitle('Atipicos beneficio - ' . $nombreAmbito);
$pdf->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$pdf->SetMargins(8, 25, 8);
$pdf->SetAutoPageBreak(true, 12);
$pdf->setCabecera($htmlCabecera);
$pdf->AddPage();
$pdf->writeHTML($htmlCuerpo, true, false, true, false, '');

$fichero = 'beneficio_atipicos_' . date('Ymd_His') . '.pdf';
$pdf->Output($fichero, 'D');
exit;
