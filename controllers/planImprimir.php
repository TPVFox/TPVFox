<?php 
//include_once '../clases/imprimir.php';
//~ $cabecera='<p>HOLA MUNDO</p>';
//~ $html='<p>HOLA MUNDO HTML</p>';
$pdf = new imprimir(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetMargins(10, 70,10);
$pdf->setHtmlHeader($cabecera);
$pdf->AddPage();
$pdf->writeHTML($html);
$pdf->Output($RutaServidor.$rutatmp.'/'.$nombreTmp, 'F');


$tam = filesize($RutaServidor.$rutatmp.'/'.$nombreTmp);
header("Content-type: application/pdf");
header("Content-Length: $tam");
header("Content-Disposition: inline; filename=".$nombreTmp);
$file=$RutaServidor.$rutatmp.'/'.$nombreTmp;
readfile($file);
?>
