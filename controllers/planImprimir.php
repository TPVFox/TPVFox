<?php 
//include_once '../clases/imprimir.php';
//~ $cabecera='<p>HOLA MUNDO</p>';
//~ $html='<p>HOLA MUNDO HTML</p>';
$pdf = new imprimir(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetMargins(10, 70,10);
$pdf->setHtmlHeader($cabecera);
$pdf->AddPage();
$pdf->writeHTML($html);
$pdf->Output($RutaServidor.$rutatmp.'/prueba.pdf', 'F');


?>
