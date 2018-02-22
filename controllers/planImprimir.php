<?php 
include_once '../clases/imprimir.php';
$html=$_GET['datos'];

$pdf = new imprimir(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->AddPage();
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('example_006.pdf', 'I');

?>
