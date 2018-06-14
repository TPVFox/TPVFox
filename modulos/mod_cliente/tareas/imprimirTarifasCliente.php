<?php 
include_once ($RutaServidor . $HostNombre."/modulos/mod_cliente/clases/ClaseCliente.php");
require_once ($RutaServidor . $HostNombre.'/modulos/mod_cliente/clases/claseTarifaCliente.php');
$Cliente= new ClaseCliente($BDTpv);
$idCliente=$_POST['idCliente'];
if(isset($idCliente)){
	$datosCliente=$Cliente->getCliente($idCliente);
	if(isset($datosCliente['error'])){
		$resultado['error']=$datosCliente['consulta'];
	}else{
		$datosCliente=$datosCliente['datos'][0];
	}
}else{
	$resultado['error']='Error no se ha enviado el id del cliente';
}
$tarifaCliente = (new TarifaCliente($BDTpv))->leer($idCliente);
if(isset($tarifaCliente['error'])){
	$resultado['error']=$tarifaCliente['consulta'];
}else{
	 $datos = $tarifaCliente['datos'];
	 $cabecera='<p></p><font size="20">Super Oliva </font><br>
			<font size="12">'.$Tienda['razonsocial'].'</font><br>'.
			'<font size="12">'.$Tienda['direccion'].'</font><br>'.
			'<font size="9"><b>NIF: </b>'.$Tienda['nif'].'</font><br>'.
			'<font size="9"><b>Teléfono: </b>'.$Tienda['telefono'].'</font><br>'.
			'<font size="17">Tarifas de cliente</font>'.
			'<hr>'.
			'<font size="20">'.$datosCliente['Nombre'].'</font><br>'.
			'<table><tr><td><font size="12">'.$datosCliente['razonsocial'].'</font></td>
			<td><font>Dirección de entrega :</font></td></tr>'.
			'<tr><td><font size="9"><b>NIF: </b>'.$datosCliente['nif'].'</font></td>
			<td><font size="9">'.$datosCliente['direccion'].'</font></td></tr>'.
			'<tr><td><font size="9"><b>Teléfono: </b>'.$datosCliente['telefono'].'</font></td>
			<td><font size="9">Código Postal: </font></td></tr>'.
			'<tr><td><font size="9">email: '.$datosCliente['email'].'</font></td><td></td></tr></table>'.
			'<table WIDTH="80%" border="1px">'.
				'<tr>'.
					'<td>IdArticulo</td>'.
					'<td WIDTH="40%">Descripción</td>'.
					'<td>Precio S/IVA</td>'.
					'<td>IVA</td>'.
					'<td>Precio C/IVA</td>'.
				'</tr>'.
			'</table>';
		
		$html='<table  WIDTH="80%">';
		foreach($datos as $tarifaCliente){
			$html.='<tr>
			<td>'. $tarifaCliente['idArticulo'] .'</td>
			<td WIDTH="40%">' . $tarifaCliente['descripcion'] . '</td>
			<td>' . number_format($tarifaCliente['pvpSiva'],2, '.', '') . '</td>
			<td>' . number_format($tarifaCliente['ivaArticulo'],2, '.', '') . '</td>
			<td>' . number_format($tarifaCliente['pvpCiva'],2, '.', '') . '</td>
			</tr>';
		}
		
		$html.='</table>';
		$nombreTmp="TarifaCliente.pdf";
		require_once('../../lib/tcpdf/tcpdf.php');
		require_once ('../../clases/imprimir.php');
		include_once('../../controllers/planImprimir.php');
		$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
		 $resultado=$ficheroCompleto;
		//~ $resultado=$cabecera;
}



?>
