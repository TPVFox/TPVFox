<?php 
//~ require_once('../lib/tcpdf/tcpdf.php');
//~ include ('../clases/imprimir.php');

$pdf = new imprimir(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$cabecera="";
$pdf->SetMargins(0, 20,0, true);
$pdf->setHtmlHeader($cabecera);
$pdf->AddPage();
$style = array(
    'position' => '',
    'align' => 'L',
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


$style['cellfitalign'] = 'L';
$i=0;
$pdf->SetFont('helvetica', '', 9);
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
           
           $texto1='Lote: '.$lote.' Fecha Env: '.$lote['fecha_env'];
           $texto2=$producto['nombre'].'\n'.$producto['nombre'];
            //~ $pdf->write1DBarcode($producto['codBarras'], 'EAN13', '', $y-8.5, 105, 18, 0.4, $style, 'M');
            $pdf->write1DBarcode($producto['codBarras'], 'EAN13', '', $y-10, 105, 18, 0.4, $style, 'M');
            $pdf->SetXY($x,$y);
			$pdf->Cell(50, 30, $texto1, 1, 0, 'C', FALSE, '', 0, FALSE, 'C', 'B');
			//~ $pdf->write1DBarcode($producto['codBarras'], 'EAN13', '', $y-10, 105, 18, 0.4, $style, 'M');
			//~ $pdf->SetXY($x,$y);
			$pdf->Multicell(50,20,"This is a multi-line text string\nNew line",1, 0, 'C', '', '', 0, '', 'C', 'B');
			
				
            //~ $pdf->Cell(105, 51, $producto['nombre'], 1, 0, 'C', FALSE, '', 0, FALSE, 'C', 'B');
            
    
		if($i==1){
			$pdf->Ln();
		}
		
		$i++;
		
	}
}
$pdf->Output($RutaServidor.$rutatmp.'/'.$nombreTmp, 'F');
?>
