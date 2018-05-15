<?php 
//~ require_once('../lib/tcpdf/tcpdf.php');
//~ include ('../clases/imprimir.php');

$pdf = new imprimir(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$cabecera="";
$pdf->SetMargins(0, 10,0, true);
$pdf->setHtmlHeader($cabecera);
$pdf->AddPage();
$style = array(
    'position' => '',
    'align' => 'C',
    'stretch' => false,
    'fitwidth' => true,
    'cellfitalign' => '',
    'border' => true,
    'hpadding' => 'auto',
    'vpadding' => 'auto',
    'fgcolor' => array(0,0,0),
    'bgcolor' => false, //array(255,255,255),
    'text' => true,
    'font' => 'helvetica',
    'fontsize' => 8,
    'stretchtext' => 4
);


$style['cellfitalign'] = 'C';
$i=0;
foreach($lotes as $lote){
	$etiquetas=$CEtiquetado->datosLote($lote);
	$productos=$etiquetas['productos'];
	$productos=json_decode($productos, true);
	foreach($productos as $producto){
		if($i==2){
			$i=0;
		}
		
		
	
			$x = $pdf->GetX();
            $y = $pdf->GetY();
            //~ $pdf->write1DBarcode($producto['codBarras'], 'EAN13', '', $y-8.5, 105, 18, 0.4, $style, 'M');
            $pdf->write1DBarcode($producto['codBarras'], 'EAN13', '', $y, 105, 18, 0.4, $style, 'M');
            //Reset X,Y so wrapping cell wraps around the barcode's cell.
            $pdf->SetXY($x,$y);
            //~ $pdf->Cell(105, 51, $producto['nombre'], 1, 0, 'C', FALSE, '', 0, FALSE, 'C', 'B');
            $pdf->Cell(105, 51, $producto['nombre'], 1, 0, 'C', FALSE, '', 0, FALSE, 'C', 'B');
    
		if($i==1){
			$pdf->Ln();
		}
		
		$i++;
		
	}
}
//~ $pdf->writeHTML($html);
//~ $pdf->write1DBarcode('8410014820938', 'EAN13', '', '', '', 18, 0.4, '', 'N');
//~ $pdf->Output($RutaServidor.$rutatmp.'/'.$nombreTmp, 'F');
//~ $pdf->Output('etiquetas.pdf', 'I');
$pdf->Output($RutaServidor.$rutatmp.'/'.$nombreTmp, 'F');
?>
