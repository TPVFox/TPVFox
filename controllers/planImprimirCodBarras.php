<?php 
//~ require_once('../lib/tcpdf/tcpdf.php');
//~ include ('../clases/imprimir.php');

$pdf = new imprimir(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$cabecera="";
$pdf->SetMargins(3, 10,3, false);


// margen pie de pagina 0
$pdf->SetAutoPageBreak(false, 0);
$pdf->setHtmlHeader($cabecera);
$pdf->AddPage();
$style = array(
    'position' => '',
    'align' => 'L',
    'stretch' => false,
    'fitwidth' => true,
    'cellfitalign' => '',
    'border' => false,
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
$cont=0;
$pdf->SetFont('helvetica', '', 9);
foreach($lotes as $lote){
	$etiquetas=$CEtiquetado->datosLote($lote);
	$tipo=$etiquetas['tipo'];
	$productos=$etiquetas['productos'];
	$productos=json_decode($productos, true);
	foreach($productos as $producto){
		if($i==2){
			$i=0;
		}
			$x = $pdf->GetX();
            $y = $pdf->GetY();
            $fechaEnv =date_format(date_create($etiquetas['fecha_env']), 'd-m-Y');
			$precioKilo=number_format($producto['precio'], 2);
			$pvp=$producto['peso']*$producto['precio'];
			$pvp=number_format($pvp, 2);
			
			$texto1='Lote: '.$lote.'  Fecha Env: '.$fechaEnv;
			$texto2='<br><br>'.$producto['nombre'].'<br>';
			if($tipo==2){
				$texto2.='Precio kilo: '.$precioKilo.'€<br>Peso: '.$producto['peso'].'kg<br>';
			}
			$texto2.='<font size="15"><b>PVP: '.$pvp.
			'€</b></font><br>Fecha cad: '.date_format(date_create($etiquetas['fecha_cad']), 'd-m-Y').'<br>';
            $pdf->write1DBarcode($producto['codBarras'], 'EAN13', '', $y+2, 105, 18, 0.4, $style, 'M');
            $pdf->SetXY($x,$y);
			$pdf->MultiCell(55, 35, $texto1, 0, 'L', 0, 0, '', '', true, 0, false, true, 45 ,'M');
			$pdf->MultiCell(50, 35, $texto2, 0, 'L', 0, 0, '', '', true, 0, true, true, 45 ,'M');
		if($i==1){
			$pdf->Ln();
		}
		$cont++;
		$i++;
		if($cont==16){
			$pdf->AddPage();
			$cont=0;
		}
		
	}
}
$pdf->Output($RutaServidor.$rutatmp.'/'.$nombreTmp, 'F');
?>
