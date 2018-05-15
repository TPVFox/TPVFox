<?php 
//~ require_once('../lib/tcpdf/tcpdf.php');
//~ include ('../clases/imprimir.php');

$pdf = new imprimir(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$cabecera="";
$pdf->SetMargins(0, 0, 0, true);
$pdf->setHtmlHeader($cabecera);
$pdf->AddPage();
$tbl = "<table border='1px'><tr>";
		
	
$pdf->writeHTML($tbl);
$i=0;
foreach($lotes as $lote){
	$etiquetas=$CEtiquetado->datosLote($lote);
	$productos=$etiquetas['productos'];
	$productos=json_decode($productos, true);
	foreach($productos as $producto){
		if($i==2){
			$i=0;
			$tbl = "<tr>";
			$pdf->writeHTML($tbl);
		}
		$tbl='<td>Prueba de imprimir</td>';
		$pdf->writeHTML($tbl);
		
		if($i==1){
			$tbl = "</tr>";
			$pdf->writeHTML($tbl);
		}
		$i++;
		
	}
}
if($i<=1){
		$rep=2-$i;
		$tbl= str_repeat("<td></td>", $rep);
		$pdf->writeHTML($tbl);
		$tbl='</tr>';
		$pdf->writeHTML($tbl);
	}

$tbl = "</table>";
	
	$pdf->writeHTML($tbl);


//~ $pdf->writeHTML($html);
//~ $pdf->write1DBarcode('8410014820938', 'EAN13', '', '', '', 18, 0.4, '', 'N');
//~ $pdf->Output($RutaServidor.$rutatmp.'/'.$nombreTmp, 'F');
//~ $pdf->Output('etiquetas.pdf', 'I');
$pdf->Output($RutaServidor.$rutatmp.'/'.$nombreTmp, 'F');
?>
