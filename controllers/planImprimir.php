<?php 
//include_once '../clases/imprimir.php';
//~ $cabecera='<p>HOLA MUNDO</p>';
//~ $html='<p>HOLA MUNDO HTML</p>';

$pdf = new imprimir(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
// $pdf->SetMargins(20, XX, 20, false) XX-> Es el margen para alto, para escribir caja texto;
$pdf->SetMargins(20, $margen_top_caja_texto, 20, false);
$pdf->setHtmlHeader($cabecera);
$pdf->AddPage();
$pdf->writeHTML($html);
$pdf->Output($RutaServidor.$rutatmp.'/'.$nombreTmp, 'F');
?>
