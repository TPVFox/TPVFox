<?php 


//@Objetivo: Generar el documento de impresión de un resumen de tickets


//~ include_once ($URLCom ."/modulos/mod_cliente/clases/ClaseCliente.php");
$Cliente= new ClaseCliente($BDTpv);
$Tienda = $_SESSION['tiendaTpv'];
$resultado['tienda']=$Tienda;
$idCliente=$_POST['idCliente'];
$fechaInicial=$_POST['fechaInicial'];
$fechaFinal=$_POST['fechaFinal'];
$errores=array();
$textoFechas="";
if(isset($idCliente)){
	$datosCliente=$Cliente->getCliente($idCliente);
	if(isset($datosCliente['error'])){
	 $resultado['error']=array ( 'tipo'=>'DANGER!',
		 'dato' => $datosCliente['consulta'],
		 'class'=>'alert alert-danger',
		 'mensaje' => 'Error en sql'
		 );
	}else{
		$datosCliente=$datosCliente['datos'][0];
	}
}else{
	$resultado['error']=array ( 'tipo'=>'DANGER!',
		'dato' => '',
		'class'=>'alert alert-danger',
		'mensaje' => 'Error no se ha enviado el id del cliente'
		);
		}
if(isset($_POST['fechaInicial']) & isset($_POST['fechaFinal'])){
	$fechaInicial=$_POST['fechaInicial'];
	$fechaFinal=$_POST['fechaFinal'];
	if($fechaInicial<>"" & $fechaFinal<>""){
		$textoFechas= 'Intervalo de fechas: '.date_format(date_create($fechaInicial), 'd-m-Y').' y '.date_format(date_create($fechaFinal), 'd-m-Y');
	}
	$resultado['post']=$fechaInicial;
	$arrayNums=$Cliente->ticketClienteFechas($idCliente, $fechaInicial, $fechaFinal);
	if(isset($arrayNums['error'])){
		$resultado['error']=array ( 'tipo'=>'DANGER!',
		 'dato' => $arrayNums['consulta'],
		 'class'=>'alert alert-danger',
		 'mensaje' => 'Error de sql'
		 );
	}else{
		//~ $resultado['array']=$arrayNums;
		$cabecera='<p></p>'.
            '<table  WIDTH="100%"><tr><td WIDTH="50%"><font size="20">'.$Tienda['NombreComercial'].' </font><br>
			<font size="12">'.$Tienda['razonsocial'].'</font><br>'.
			'<font size="9">'.$Tienda['direccion'].'</font><br>'.
			'<font size="9"><b>NIF: </b>'.$Tienda['nif'].'</font><br>'.
			'<font size="9"><b>Teléfono: </b>'.$Tienda['telefono'].'</font><br>'.
            '</td><td >'.
            '<font size="9"><b>CLIENTE CON NIF: </b>'.$datosCliente['nif'].'</font><br>'.

			'<font size="12">'.$datosCliente['Nombre'].'</font><br>'.
			'<font size="12">'.$datosCliente['razonsocial'].'</font><br>
			<font size="9"><b>Dirección:</b>'.$datosCliente['direccion'].'</font><br>'.
			'<font size="9"><b>Teléfono: </b>'.$datosCliente['telefono'].'</font><br>
			<font size="9">email: '.$datosCliente['email'].'</font></td></tr></table><br>'.$textoFechas;
        $html = '<h3>Facturas simplificadas (Tickets)</h3>
				<table  WIDTH="75%" border="1px">'.
				'<tr>
				<td  WIDTH="50%">Fecha</td>
				<td>Factura</td>
				<td>Base</td>
				<td>Iva</td>
				<td>Total</td>
				</tr>';
		$totalLinea=0;
		$totalbases=0;
			foreach($arrayNums['resumenBases'] as $bases){
				$totalLinea=$bases['sumabase']+$bases['sumarIva'];
				$totalbases=$totalbases+$totalLinea;
				$numTicket=$bases['idTienda'].'-'.$bases['idUsuario'].'-'.$bases['Numticket'];
				$html.= '<tr>
					<td WIDTH="50%"><font size="8">'.$bases['fecha'].'</font></td>
					<td><font size="8">'.$numTicket.'</font></td>
					<td style="text-align:right;"><font size="8">'.$bases['sumabase'].'€</font> </td>
					<td style="text-align:right;"><font size="8">'.$bases['sumarIva'].'€</font> </td>
					<td style="text-align:right;"><font size="8">'.number_format($totalLinea,2).'€ </font></td>
					</tr>';
			}
		$html.='</table>';

        $html.='<table >'
				.'<tr>
				<th></th>
				<th style="text-align:right;">Base</th>
				<th style="text-align:right;">IVA</th>
				<th style="text-align:right;">Total</th>
				</tr>';
		$totalLinea=0;
		$totalDesglose=0;
			foreach($arrayNums['desglose'] as $desglose){
				$totalLinea=$desglose['sumBase']+$desglose['sumiva'];
				$totalDesglose=$totalDesglose+$totalLinea;
				$html.='<tr>
					<td style="text-align:right;">'.$desglose['iva'].'%</td>
					<td style="text-align:right;">'.$desglose['sumBase'].'</td>
					<td style="text-align:right;">'.$desglose['sumiva'].'</td>
					<td style="text-align:right;">'.number_format($totalLinea,2).'</td>
					</tr>';
			}
		$html .='<tr>
				<td></td>
				<td></td>
				<td>TOTAL:</td>
				<td><b>'.$totalDesglose.'€</b></td>
				</tr>
				</table><br>';
        
		$html.='<h3>Desglose de productos de las facturas simplificadas (Tickets)</h3>
            <p>(*) Precios medios de venta, ya que hubo varios precios del mismo producto en ese periodo de tiempo</p>
            <table WIDTH="80%" style="padding: 2px 15px 2px 2px;" border="1px"><tr>
			<td WIDTH="50%">Descripción del producto</td>
			<td>Cantidad</td>
			<td>Precio</td>
			<td>Importe</td>
			</tr>';
        $lineas =  getHmtlTrProductos($arrayNums['productos'],'pdf');
        $html .= $lineas['html'];
		$html.='</table>';
		
		$nombreTmp="Resumen.pdf";
        $margen_top_caja_texto= 70;
		require_once  ($URLCom.'/clases/imprimir.php');
		require_once($URLCom.'/controllers/planImprimir.php');
		$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
		$resultado=$ficheroCompleto;
				
	}
}else{
	$resultado['error']=array ( 'tipo'=>'DANGER!',
		 'dato' => '',
		 'class'=>'alert alert-danger',
		 'mensaje' => 'Error no se han enviado corectamente las fechas'
	 );
}
?>
