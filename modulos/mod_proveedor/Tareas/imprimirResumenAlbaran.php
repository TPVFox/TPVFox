<?php 
//@Objetivo: Generar el documento de impresión de un resumen de tickets
include_once ($URLCom."/modulos/mod_proveedor/clases/ClaseProveedor.php");
include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
$CTArticulos = new ClaseProductos($BDTpv);
$CProveedor= new ClaseProveedor($BDTpv);
$Tienda = $_SESSION['tiendaTpv'];
$resultado['tienda']=$Tienda;
$idProveedor=$_POST['idProveedor'];
$fechaInicial=$_POST['fechaInicial'];
$fechaFinal=$_POST['fechaFinal'];
$errores=array();
$textoFechas="";
$style = array( 'C'=>'style="text-align:center;"',
				'R'=>'style="text-align:right"',
				'L'=>'style="text-align:left;"'
);

if(isset($idProveedor)){
	$datosProveedor=$CProveedor->getProveedor($idProveedor);
	if(isset($datosProveedor['error'])){
	 $resultado['error']=array ( 'tipo'=>'DANGER!','dato' => $datosProveedor['consulta'],
		 'class'=>'alert alert-danger','mensaje' => 'Error en sql' );
	}else{
		$datosProveedor=$datosProveedor['datos'][0];
	}
}else{
	$resultado['error']=array ( 'tipo'=>'DANGER!','dato' => '','class'=>'alert alert-danger',
		'mensaje' => 'Error no se ha enviado el id del cliente');
		}
if(isset($_POST['fechaInicial']) & isset($_POST['fechaFinal'])){
	$fechaIni=$_POST['fechaInicial'];
	$fechaFin=$_POST['fechaFinal'];
	if($fechaIni<>"" & $fechaFin<>""){
		$fechaInicial =date_format(date_create($fechaIni), 'Y-m-d');
		$fechaFinal =date_format(date_create($fechaFin), 'Y-m-d');
		$textoFechas= 'entre las fechas '.$fechaIni.' y '.$fechaFin;
	}
	$resultado['post']=$fechaInicial;
	$arrayNums=$CProveedor->albaranesProveedoresFechas($idProveedor, $fechaInicial, $fechaFinal);
	$productos = $CProveedor->SumaLineasAlbaranesProveedores($arrayNums['productos'],'KO');
	if(isset($arrayNums['error'])){
		$resultado['error']=array ( 'tipo'=>'DANGER!','dato' => $arrayNums['consulta'],
		 'class'=>'alert alert-danger','mensaje' => 'Error de sql');
	}else{
        $cabecera=<<<EOD
<p></p><font size="20">$Tienda[NombreComercial] </font><br>
<font size="12">$Tienda[razonsocial]</font><br>
<font size="12">$Tienda[direccion]</font><br>
<font size="9"><b>NIF: </b>$Tienda[nif]</font><br>
<font size="9"><b>Teléfono: </b>$Tienda[telefono]</font><br>
<font size="15">Resumen de albaranes $textoFechas</font><hr>
<font size="20">$datosProveedor[nombrecomercial]</font><br>
<table><tr><td><font size="12">$datosProveedor[razonsocial]</font></td>
<td>Dirección de entrega :</td></tr>
<tr><td><font size="9"><b>NIF: </b>$datosProveedor[nif]</font></td>
<td><font size="9">$datosProveedor[direccion]</font></td></tr>
<tr><td><font size="9"><b>Teléfono: </b>$datosProveedor[telefono]</font></td>
<td><font size="9">Código Postal: </font></td></tr>
<tr><td><font size="9">email: $datosProveedor[email]</font></td><td></td></tr></table>
EOD;
$html = '<table WIDTH="80%" border="1px">
<tr>
<th>ID</th>
<th width="3%">P</th>
<th width="50%">PRODUCTO</th>
<th $style[C]>NºC</th>
<th>Uds</th>
<th>Coste</th>
<th width="6%">CM</th>
<th width="20%">IMPORTE</th>
</tr>

';
foreach($productos as $producto){
	$p =$CTArticulos->GetProducto($producto['idArticulo']);
	$cdetalle = $p['articulo_name'];
	//$producto['tipo'] = $p['tipo'];
	$precio=number_format ($producto['totalUnidades']*$producto['costeSiva'],2);
	$unidades=number_format ($producto['totalUnidades'],2);
	$costeiva=number_format ($producto['costeSiva'],2);
	$html.='
		<tr>
		<td><font size="8">'.$producto[idArticulo].'</font></td>
		<td>';
		if ($p['proveedor_principal']['idProveedor'] == $idProveedor ){
		  $html.='*';
		}
		$tcF8 = '<td '.$style['C'].'><font size="8">';
		$html.= '</td>
		<td WIDTH="50%"><font size="8">'. $cdetalle.'</font></td>'
		.$tcF8. $producto['num_compras'].'</font></td>'
		.$tcF8. $unidades.' </font></td>'
		.$tcF8. $costeiva.'</font></td>
		<td>';
		if ($producto['coste_medio'] == 'OK'){
			$html.='*';
		}
		$html.='</td>'
				.$tcF8.$precio.'</font></td></tr>
		';
}
		$html.=<<<EOD
</table><table><tr><th></th><th $style[R]>Base</th><th $style[R]>IVA</th>
<th $style[R]>Total</th></tr>'
EOD;
		$totalLinea=0;
		$totalDesglose=0;
			foreach($arrayNums['desglose'] as $desglose){
				$totalLinea=$desglose['sumBase']+$desglose['sumiva'];
				$totalDesglose=$totalDesglose+$totalLinea;
				$html.=<<<EOD
<tr><td $style[R]>$desglose[iva]%</td><td $style[R]>$desglose[sumBase]</td>
<td $style[R]>$desglose[sumiva]</td><td $style[R]>$totalLinea</td></tr>
EOD;
			}
		$html .=<<<EOD
		<tr><td></td><td></td><td>TOTAL:</td><td><b>$totalDesglose</b></td></tr></table><h3>Listado de Albaranes</h3>
		<table  WIDTH="75%" border="1px"><tr><td  WIDTH="50%">Fecha</td><td>N_albaran</td><td>Base</td><td>Iva</td>
		<td>Total</td></tr>
		EOD;
		$totalLinea=0;
		$totalbases=0;
			foreach($arrayNums['resumenBases'] as $bases){
				$totalLinea=$bases['sumabase']+$bases['sumarIva'];
				$totalbases=$totalbases+$totalLinea;
				
				$html.=<<<EOD
					<tr><td WIDTH="50%"><font size="8">$bases[fecha]</font></td><td><font size="8">$bases[Numalbpro]</font></td>
					<td><font size="8">$bases[sumabase]</font></td><td><font size="8">$bases[sumarIva]</font></td>
					<td><font size="8">$totalLinea</font></td></tr>
					EOD;
			}
		$html.='</table>';
		$nombreTmp="Resumen.pdf";
		//~ require_once($URLCom.'/lib/tcpdf/tcpdf.php');
        $margen_top_caja_texto= 90;
		require_once  ($URLCom.'/clases/imprimir.php');
		require_once($URLCom.'/controllers/planImprimir.php');
		$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
		$resultado=$ficheroCompleto;
	}
}else{
	$resultado['error']=array ( 'tipo'=>'DANGER!','dato' => '',
		 'class'=>'alert alert-danger','mensaje' => 'Error no se han enviado corectamente las fechas');
}
?>
