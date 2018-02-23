<?php 
include_once '../clases/imprimir.php';
$html=$_GET['datos'];
$htmlCabecera=$_GET['cabecera'];
$pdf = new imprimir(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetMargins(10, 50,10);
$pdf->setHtmlHeader($htmlCabecera);
$pdf->AddPage();
//$pdf->writeHTML($html, true, false, true, false, '');
$pdf->writeHTML($html);

$pdf->Output('example_006.pdf', 'I');

?>
