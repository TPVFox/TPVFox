<?php
// @ Objetivo: generar PDF de incidencias POSStock con TCPDF.
// Misma lógica que getPOSStockData pero devuelve la ruta al PDF generado.
// El PDF se guarda en $rutatmp y la URL se devuelve como JSON al cliente.

require_once $RutaServidor . $HostNombre . '/clases/claseimprimir.php';

$fi_mov   = $_POST['fecha_inicio_movimientos'] ?? '';
$ff_mov   = $_POST['fecha_fin_movimientos']    ?? '';
$fi_stock = $_POST['fecha_inicio_stock']       ?? '';
$ff_stock = $_POST['fecha_fin_stock']          ?? '';

$fecha_re = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($fecha_re, $fi_mov) || !preg_match($fecha_re, $ff_mov)
 || !preg_match($fecha_re, $fi_stock) || !preg_match($fecha_re, $ff_stock)) {
    $respuesta['error'] = 'Fechas no válidas o incompletas.';
    return;
}

$ClaseParametros = new ClaseParametros('parametros.xml');
$posstock_node   = $ClaseParametros->getNode('configuracion/posstock');

$familias_incluir = [];
$familias_excluir = [];
foreach (explode(',', $_POST['familias_incluir'] ?? '') as $id) {
    $id = (int)trim($id); if ($id > 0) $familias_incluir[] = $id;
}
foreach (explode(',', $_POST['familias_excluir'] ?? '') as $id) {
    $id = (int)trim($id); if ($id > 0) $familias_excluir[] = $id;
}

$params = [
    'fecha_inicio_movimientos'   => $fi_mov,
    'fecha_fin_movimientos'      => $ff_mov,
    'fecha_inicio_stock'         => $fi_stock,
    'fecha_fin_stock'            => $ff_stock,
    'umbral_sobrestock'          => (float)(string)$posstock_node->umbral_sobrestock,
    'umbral_caducidad_semanas'   => (int)(string)$posstock_node->umbral_caducidad_semanas,
    'umbral_sin_rotacion_semanas'=> (int)(string)$posstock_node->umbral_sin_rotacion_semanas,
    'familias_incluir'           => $familias_incluir,
    'familias_excluir'           => $familias_excluir,
];

$posstock = new ClasePosstock($BDTpv);
$filas    = $posstock->getIncidencias($params);

if (isset($filas['error'])) {
    $respuesta['error'] = $filas['error'];
    return;
}

// ── Cabecera del PDF (impresa en cada página por Header()) ────────────────────
$html_cabecera = '
<table border="0" cellpadding="2" cellspacing="0" width="100%">
    <tr>
        <td width="70%">
            <font size="13"><b>POSStock &mdash; Revisi&oacute;n de stock</b></font>
        </td>
        <td width="30%" align="right">
            <font size="7" color="#666666">Generado: ' . date('d/m/Y H:i') . '</font>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-bottom: 1px solid #cccccc; padding-bottom:2px;">
            <font size="7" color="#333333">
                <b>Periodo movimientos:</b> ' . $fi_mov . ' &ndash; ' . $ff_mov . '
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <b>Stock base:</b> ' . $fi_stock . ' &ndash; ' . $ff_stock . '
            </font>
        </td>
    </tr>
</table>';

// ── Estadísticas por severidad ────────────────────────────────────────────────
$totales = ['CRITICA' => 0, 'MEDIA' => 0, 'BAJA' => 0];
foreach ($filas as $f) {
    if (isset($totales[$f['severidad']])) $totales[$f['severidad']]++;
}

// ── Tabla de incidencias ──────────────────────────────────────────────────────
// Severidad: bgcolor de celda + texto blanco (no solo color de texto)
$sev_cfg = [
    'CRITICA' => ['bg' => '#c0392b', 'label' => 'CRITICA'],
    'MEDIA'   => ['bg' => '#d35400', 'label' => 'MEDIA'],
    'BAJA'    => ['bg' => '#2980b9', 'label' => 'BAJA'],
];

// Colores zebra-striping
$zebra = ['#ffffff', '#f0f4f8'];

$filas_html = '';
$i = 0;
foreach ($filas as $f) {
    $bg = $zebra[$i % 2];
    $i++;

    // Detalle en una sola línea: se usa · como separador para evitar <br> y filas partidas
    $detalle = '';
    if ($f['tipo'] === 'Error crítico de stock') {
        $detalle = 'Stock: '
            . (isset($f['stock_actual']) ? number_format((float)$f['stock_actual'], 2, ',', '') : '—');
    } elseif ($f['tipo'] === 'Entrada con stock alto') {
        $detalle = 'Previo: ' . number_format((float)$f['stock_previo'], 2, ',', '')
                 . ' · Entra: ' . number_format((float)$f['ncant'], 2, ',', '')
                 . ' · ' . ($f['fecha'] ?? '—');
    } elseif ($f['tipo'] === 'Riesgo de caducidad teórica') {
        $detalle = 'Ult.venta: ' . ($f['ultima_venta'] ?? '—')
                 . ' · ' . ($f['semanas_sin_venta'] ?? '—') . ' sem.';
    } elseif ($f['tipo'] === 'Entrada sin rotación previa') {
        $detalle = $f['ultima_salida']
            ? 'Ult.salida: ' . $f['ultima_salida'] . ' · ' . $f['semanas_sin_rotacion'] . ' sem.'
            : 'Sin salidas en el año';
    } elseif ($f['tipo'] === 'Stock sin entrada anual') {
        $detalle = 'Stock: '
            . (isset($f['stock_actual']) ? number_format((float)$f['stock_actual'], 2, ',', '') : '—');
    }

    $sev       = $f['severidad'] ?? 'BAJA';
    $sev_bg    = $sev_cfg[$sev]['bg']    ?? '#888888';
    $sev_label = $sev_cfg[$sev]['label'] ?? $sev;

    // nobr="true" evita que TCPDF parta la fila entre páginas
    $filas_html .= '<tr nobr="true" bgcolor="' . $bg . '">'
        . '<td align="center" width="10%">'   . htmlspecialchars($f['idArticulo'])       . '</td>'
        . '<td width="26%">'                  . htmlspecialchars($f['nombre'] ?? '')      . '</td>'
        . '<td width="20%">'                  . htmlspecialchars($f['tipo'])              . '</td>'
        . '<td align="center" width="9%" bgcolor="' . $sev_bg . '">'
            . '<font color="#ffffff"><b>' . $sev_label . '</b></font>'
        . '</td>'
        . '<td width="20%">'                  . htmlspecialchars($detalle)                . '</td>'
        . '<td width="15%">'
            . '<font color="#555555">'        . htmlspecialchars($f['posible_causa'] ?? '') . '</font>'
        . '</td>'
        . '</tr>';
}

if ($filas_html === '') {
    $filas_html = '<tr><td colspan="6" align="center"><font color="#555555">'
        . 'Sin incidencias detectadas para este periodo.'
        . '</font></td></tr>';
}

// Fila de totales al final
$filas_html .= '
<tr nobr="true" bgcolor="#2c3e50">
    <td colspan="2" align="right"><font color="#ffffff"><b>Total incidencias: ' . count($filas) . '</b></font></td>
    <td colspan="4">
        <font color="#e74c3c"><b>' . $totales['CRITICA'] . ' CR&Iacute;TICA</b></font>
        &nbsp;&nbsp;
        <font color="#f39c12"><b>' . $totales['MEDIA']   . ' MEDIA</b></font>
        &nbsp;&nbsp;
        <font color="#74b9ff"><b>' . $totales['BAJA']    . ' BAJA</b></font>
    </td>
</tr>';

$html_cuerpo = '
<table border="1" cellpadding="4" cellspacing="0" width="100%">
    <thead>
        <tr bgcolor="#2c3e50">
            <th width="10%" align="center"><font color="#ffffff"><b>Art&#237;culo</b></font></th>
            <th width="26%"><font color="#ffffff"><b>Nombre</b></font></th>
            <th width="20%"><font color="#ffffff"><b>Tipo incidencia</b></font></th>
            <th width="9%" align="center"><font color="#ffffff"><b>Sev.</b></font></th>
            <th width="20%"><font color="#ffffff"><b>Detalle</b></font></th>
            <th width="15%"><font color="#ffffff"><b>Posible causa</b></font></th>
        </tr>
    </thead>
    <tbody>' . $filas_html . '</tbody>
</table>';

// ── Generar PDF ───────────────────────────────────────────────────────────────
// Orientación horizontal (L) para 6 columnas en A4 (297 x 210 mm).
$pdf = new imprimirPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('TPVFox');
$pdf->SetAuthor('POSStock');
$pdf->SetTitle('POSStock ' . $fi_mov . ' - ' . $ff_mov);
$pdf->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$pdf->SetMargins(8, 25, 8);
$pdf->SetAutoPageBreak(true, 12);
$pdf->setCabecera($html_cabecera);
$pdf->AddPage();
$pdf->writeHTML($html_cuerpo, true, false, true, false, '');

$fichero  = 'posstock_' . date('Ymd_His') . '.pdf';
$filename = $RutaServidor . $rutatmp . '/' . $fichero;
$pdf->Output($filename, 'F');

$respuesta['url'] = $rutatmp . '/' . $fichero;
