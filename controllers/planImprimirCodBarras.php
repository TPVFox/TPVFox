<?php 
require_once('../lib/tcpdf/tcpdf.php');
include ('../clases/imprimir.php');
//~ $cabecera='<p>HOLA MUNDO</p>';
//~ $html='<p>HOLA MUNDO HTML</p>';
$pdf = new imprimir(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetMargins(20, 70, 20, true);
//~ $pdf->setHtmlHeader($cabecera);
$pdf->AddPage();
//~ $pdf->writeHTML($html);
$pdf->write1DBarcode('8410014820938', 'EAN13', '', '', '', 18, 0.4, '', 'N');
//~ $pdf->Output($RutaServidor.$rutatmp.'/'.$nombreTmp, 'F');
$pdf->Output('example_027.pdf', 'I');
?>
