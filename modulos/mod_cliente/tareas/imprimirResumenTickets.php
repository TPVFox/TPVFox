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
	$fechaIni=$_POST['fechaInicial'];
	$fechaFin=$_POST['fechaFinal'];
	if($fechaIni<>"" & $fechaFin<>""){
		$fechaInicial =date_format(date_create($fechaIni), 'Y-m-d');
		$fechaFinal =date_format(date_create($fechaFin), 'Y-m-d');
		$textoFechas= 'entre las fechas '.$fechaInicial.' y '.$fechaFinal;
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
		$cabecera='<p></p><font size="20">Super Oliva </font><br>
			<font size="12">'.$Tienda['razonsocial'].'</font><br>'.
			'<font size="12">'.$Tienda['direccion'].'</font><br>'.
			'<font size="9"><b>NIF: </b>'.$Tienda['nif'].'</font><br>'.
			'<font size="9"><b>Teléfono: </b>'.$Tienda['telefono'].'</font><br>'.
			'<font size="17">Factura de Tickets '.$textoFechas.'</font>'.
			'<hr>'.
			'<font size="20">'.$datosCliente['Nombre'].'</font><br>'.
			'<table><tr><td><font size="12">'.$datosCliente['razonsocial'].'</font></td>
			<td><font>Dirección de entrega :</font></td></tr>'.
			'<tr><td><font size="9"><b>NIF: </b>'.$datosCliente['nif'].'</font></td>
			<td><font size="9">'.$datosCliente['direccion'].'</font></td></tr>'.
			'<tr><td><font size="9"><b>Teléfono: </b>'.$datosCliente['telefono'].'</font></td>
			<td><font size="9">Código Postal: </font></td></tr>'.
			'<tr><td><font size="9">email: '.$datosCliente['email'].'</font></td><td></td></tr></table>'.
			'';
						
		$html='<table WIDTH="80%" border="1px"><tr>
			<td WIDTH="50%">Descripción del producto</td>
			<td>Cantidad</td>
			<td>Precio</td>
			<td>Importe</td>
			</tr></table><table  WIDTH="80%" border="1px">';
			foreach($arrayNums['productos'] as $producto){
				$precio=$producto['totalUnidades']*$producto['precioCiva'];
				$html.='<tr>'
				. '<td WIDTH="50%"><font size="8">'.$producto['cdetalle'].'</font></td>'
				.'<td style="text-align:center;"><font size="8">'. number_format ($producto['totalUnidades'],2).'</font></td>'
				.'<td style="text-align:center;"><font size="8">'.number_format ($producto['precioCiva'],2).'</font></td>'
				. '<td style="text-align:center;"><font size="8">'.number_format ($precio,2).'</font></td>'
				.'</tr>';
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
					<td style="text-align:right;">'.$totalLinea.'</td>
					</tr>';
			}
		$html .='<tr>
				<td></td>
				<td></td>
				<td>TOTAL:</td>
				<td><b>'.$totalDesglose.'</b></td>
				</tr>
				</table>	
				<h3>Facturas simplificadas (Tickets)</h3>
				<table  WIDTH="75%" border="1px">'.
				'<tr>
				<td  WIDTH="50%">Fecha</td>
				<td>Ticket</td>
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
					<td><font size="8">'.$bases['sumabase'].'</font></td>
					<td><font size="8">'.$bases['sumarIva'].'</font></td>
					<td><font size="8">'.$totalLinea.'</font></td>
					</tr>';
			}
		$html.='</table>';
		$nombreTmp="Resumen.pdf";
		require_once($URLCom.'/lib/tcpdf/tcpdf.php');
		
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
