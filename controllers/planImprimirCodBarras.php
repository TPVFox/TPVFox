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
           
           $texto=$producto['nombre']." Peso:".$producto['peso']." Precio".$producto['precio'];
            //~ $pdf->write1DBarcode($producto['codBarras'], 'EAN13', '', $y-8.5, 105, 18, 0.4, $style, 'M');
            $pdf->write1DBarcode($producto['codBarras'], 'EAN13', '', $y-10, 105, 18, 0.4, $style, 'M');
            $pdf->SetXY($x,$y);
			$pdf->Cell(105, 30, $texto, 1, 0, 'C', FALSE, '', 0, FALSE, 'C', 'B');
			
				
            //~ $pdf->Cell(105, 51, $producto['nombre'], 1, 0, 'C', FALSE, '', 0, FALSE, 'C', 'B');
            
    
		if($i==1){
			$pdf->Ln();
		}
		
		$i++;
		
	}
}
$pdf->Output($RutaServidor.$rutatmp.'/'.$nombreTmp, 'F');
?>
