<?php 
include_once '../clases/imprimir.php';
//~ $html=$_POST['html'];
//~ $htmlCabecera=$_POST'cabecera'];
$pdf = new imprimir(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetMargins(10, 70,10);
$pdf->setHtmlHeader($_POST['cabecera']);
$pdf->AddPage();
$pdf->writeHTML($_POST['html']);

$pdf->Output('example_006.pdf', 'I');
?>
