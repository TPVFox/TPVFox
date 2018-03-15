<!DOCTYPE html>
<html>
<head>
<?php
include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	include '../../clases/cliente.php';
	$Ccliente=new Cliente($BDTpv);
	include 'clases/albaranesVentas.php';
	$Calbcli=new AlbaranesVentas($BDTpv);
	include_once 'clases/pedidosVentas.php';
	$Cped = new PedidosVentas($BDTpv);
	include_once 'clases/facturasVentas.php';
	$Cfaccli=new FacturasVentas($BDTpv);
	include_once '../../clases/FormasPago.php';
	$CforPago=new FormasPago($BDTpv);
	$Controler = new ControladorComun; 
	$Tienda = $_SESSION['tiendaTpv'];
	$Usuario = $_SESSION['usuarioTpv'];// array con los datos de usuario
	if (isset($_GET['id'])){//Si rebie un id quiere decir que ya existe la factura
		$idFactura=$_GET['id'];
		$titulo="Modificar Factura De Cliente";
		$estado='Modificado';
		$estadoCab="'".'Modificado'."'";
		$datosFactura=$Cfaccli->datosFactura($idFactura);//Extraemos los datos de la factura 
		$productosFactura=$Cfaccli->ProductosFactura($idFactura);//De los productos
		$ivasFactura=$Cfaccli->IvasFactura($idFactura);//De la tabla de ivas
		$albaranFactura=$Cfaccli->AlbaranesFactura($idFactura);//Los albaranes de las facturas aÃ±adidos
		$estado=$datosFactura['estado'];
		$estadoCab="'".$datosFactura['estado']."'";
		
		$date=date_create($datosFactura['Fecha']);
		$fecha=date_format($date,'Y-m-d');
		$fechaCab="'".$fecha."'";
		$idFacturaTemporal=0;
		$numFactura=$datosFactura['Numfaccli'];
		$idCliente=$datosFactura['idCliente'];
		if ($idCliente){
				// Si se cubrió el campo de idcliente llama a la función dentro de la clase cliente 
				$datosCliente=$Ccliente->DatosClientePorId($idCliente);
				$nombreCliente="'".$datosCliente['Nombre']."'";
				//$formasVenci=$datosCliente['formasVenci'];
		}
		if ($datosFactura['formaPago']){
			$formaPago=$datosFactura['formaPago'];
		}else{
			$formaPago=0;
		}
		$textoFormaPago=htmlFormasVenci($formaPago, $BDTpv);
		if ($datosFactura['FechaVencimiento']){
			$date=date_create($datosFactura['FechaVencimiento']);
			$fechave=date_format($date,'Y-m-d');
		}else{
			$fec=date('Y-m-d');
			$fechave=fechaVencimiento($fechave, $BDTpv);
		}
		$textoFecha=htmlVencimiento($fechave, $BDTpv);


		$productos=json_decode(json_encode($productosFactura));
		$Datostotales = recalculoTotalesAl($productos);
		
		$productos=json_decode(json_encode($productosFactura), true);
		if ($albaranFactura){
			 $modificaralbaran=modificarArrayAlbaranes($albaranFactura, $BDTpv);
			 $albaranes=json_decode(json_encode($modificaralbaran), true);
		}
		echo '<pre>';
		print_r($albaranes);
		echo '</pre>';
		$total=$Datostotales['total'];
		//Si esta en estado guardado o pagado parcial se puede modificar los importes si no no
		if ($estado="Guardado" || $estado="Pagado parcial"){
			$Simporte="";
			$importes=$datosFactura['importes'];
			$importes=json_decode($importes, true);
			
		}else{
			$Simporte="display:none;";
		}
		
		
		
	}else{// si no recibe un id de una factura ya creada ponemos los datos de la temporal en caso de que tenga 
		//Si no dejamos todo en blanco para poder cubrir
		$titulo="Crear Factura De Cliente";
		$bandera=1;
		$estado='Abierto';
		$estadoCab="'".'Abierto'."'";
		$fecha=date('Y-m-d');
		$fechaCab="'".$fecha."'";
		$Simporte="display:none;";
	
			if (isset($_GET['tActual'])){
				$idFacturaTemporal=$_GET['tActual'];
				$datosFactura=$Cfaccli->buscarDatosFacturasTemporal($idFacturaTemporal);
				if (isset($datosFactura['Numfaccli '])){
					$numFactura=$datosFactura['Numfaccli'];
				}else{
					$numFactura=0;
				}
				
				if ($datosFactura['fechaInicio']=="0000-00-00 00:00:00"){
					$fecha=date('Y-m-d');
				}else{
					$fecha1=date_create($datosFactura['fechaInicio']);
					$fecha =date_format($fecha1, 'Y-m-d');
				}
				$idCliente=$datosFactura['idClientes'];
				
				$cliente=$Ccliente->DatosClientePorId($idCliente);
				$nombreCliente="'".$cliente['Nombre']."'";
				if (isset ($cliente['formasVenci'])){
					$formasVenci=$cliente['formasVenci'];
				}else{
					$formasVenci='';
				}
				$fechaCab="'".$fecha."'";
				$idFactura=0;
				$estadoCab="'".'Abierto'."'";
				$factura=$datosFactura;
				
				$productos =  json_decode($datosFactura['Productos']) ;
				
				$albaranes=json_decode($datosFactura['Albaranes']);
				
				$datoVenci=json_decode($datosFactura['FacCobros'], true);
				
				if ($datoVenci['forma']){
					$formaPago=$datoVenci['forma'];
				}else{
					$formaPago=0;
				}
				$textoFormaPago=htmlFormasVenci($formaPago, $BDTpv);
				if ($datoVenci['fechaVencimiento']){
					$date=date_create($datoVenci['fechaVencimiento']);
					$fechave=date_format($date,'Y-m-d');
				}else{
					$fec=date('Y-m-d');
					$fechave=fechaVencimiento($fechave, $BDTpv);
				}
				
				$textoFecha=htmlVencimiento($fechave, $BDTpv);
			}else{
				$idFacturaTemporal=0;
				$idFactura=0;
				$numFactura=0;
				$idCliente=0;
				$nombreCliente=0;
			}
		
	}
		if(isset($factura['Productos'])){
			// Obtenemos los datos totales ( fin de ticket);
			// convertimos el objeto productos en array
			$Datostotales = recalculoTotalesAl($productos);
			$productos = json_decode(json_encode($productos), true); // Array de arrays	
		}
		if (isset($factura['Albaranes'])){
			$albaranes=json_decode(json_encode($albaranes), true);
		}
		//Cuando guardadmos buscamos todos los datos de la factura temporal y hacfemos las comprobaciones pertinentes
		if (isset($_POST['Guardar'])){
		
			if ($_POST['idTemporal']){
				$idTemporal=$_POST['idTemporal'];
			}else if($_GET['tActual']){
				$idTemporal=$_GET['tActual'];
			}else{
				$idTemporal=0;
			}
			$datosFactura=$Cfaccli->buscarDatosFacturasTemporal($idFacturaTemporal);
			if($datosFactura['total']){
				$total=$datosFactura['total'];
			}else{
				$total=0;
			}
			$fechaActual=date('Y-m-d');
			if ($_POST['formaVenci']){
				$formaVenci=$_POST['formaVenci'];
			}else{
				$formaVenci=0;
			}
			
			if ($datosFactura['importes']){
				$importes=$datosFactura['importes'];
			}else{
				$importes=0;
			}
			if ($datosFactura['entregado']){
				$entregado=$datosFactura['entregado'];
			}else{
				$entregado=0;
			}
			if ($total==$entregado){
				$estado="Pagado total";
			}else{
				if ($datosFactura['estado']){
					$estado=$datosFactura['estado'];
				}else{
					$estado="Guardado";
				}
				
			}
			$datos=array(
			'Numtemp_faccli'=>$idTemporal,
			'Fecha'=>$_POST['fechaFac'],
			'idTienda'=>$Tienda['idTienda'],
			'idUsuario'=>$Usuario['id'],
			'idCliente'=>$idCliente,
			'estado'=>$estado,
			'total'=>$total,
			'DatosTotales'=>$Datostotales,
			'productos'=>$datosFactura['Productos'],
			'albaranes'=>$datosFactura['Albaranes'],
			'fechaCreacion'=>$fechaActual,
			'formapago'=>$formaVenci,
			'fechaVencimiento'=>$_POST['fechaVenci'],
			'importes'=>$importes,
			'entregado'=>$entregado,
			'fechaModificacion'=>$fechaActual
			);
			//Si ya existia una factura real eliminamos todos los datos de la factura real tanto en facturas clientes como productos, ivas y albaranes facturas
			//Una vez que tenemos los datos eliminados agregamos los datos nuevos en las mismas tablas y por Ãºltimo eliminamos la temporal
			if($datosFactura['numfaccli']>0){
				$numFactura=$datosFactura['numfaccli'];
				$buscarId=$Cfaccli->buscarIdFactura($numFactura);
				$idFactura=$buscarId['id'];
				$eliminarTablasPrincipal=$Cfaccli->eliminarFacturasTablas($idFactura);
				$addNuevo=$Cfaccli->AddFacturaGuardado($datos, $idFactura, $numFactura);
				$eliminarTemporal=$Cfaccli->EliminarRegistroTemporal($idTemporal, $idFactura);
				
			 }else{
				 //Si no tenemos una factura real solo realizamos la parte de crear los registros nuevos y eliminar el temporal
				$idFactura=0;
				$numFactura=0;
				$addNuevo=$Cfaccli->AddFacturaGuardado($datos, $idFactura, $numFactura);
				$eliminarTemporal=$Cfaccli->EliminarRegistroTemporal($idTemporal, $idFactura);
			}
			echo '<pre>';
			print_r( $addNuevo);
			echo '</pre>';
//	header('Location: facturasListado.php');
			
		}
		//Cuando cancelamos una factura eliminamos su temporal y ponemos la factura original con estado guardado
		if (isset($_POST['Cancelar'])){
			if ($_POST['idTemporal']){
				$idTemporal=$_POST['idTemporal'];
			}else{
				$idTemporal=$_GET['tActual'];
			}
		
			$datosFactura=$Cfaccli->buscarDatosFacturasTemporal($idTemporal);
			$albaranes=json_decode($datosFactura['Albaranes'], true);
			foreach ($albaranes as $albaran){
				$mod=$Cped->ModificarEstadoAlbaran($albaran['idAlCli'], "Guardado");
			}
			$idFactura=0;
			$eliminarTemporal=$Calbcli->EliminarRegistroTemporal($idTemporal, $idFactura);
				header('Location: albaranesListado.php');
		}
		
		if (isset ($albaranes) | isset($_GET['tActual'])| isset($_GET['id'])){
			$style="";
		}else{
			$style="display:none;";
		}
		if (isset($albaranes)){
			$stylea="";
		}else{
			$stylea="display:none;";
		}
		if (isset($_GET['mensaje'])){
				$mensaje=$_GET['mensaje'];
				$tipomensaje=$_GET['tipo'];
			}
		
		$parametros = simplexml_load_file('parametros.xml');
	
// -------------- Obtenemos de parametros cajas con sus acciones ---------------  //
//Como estamos el albaranes la caja de input num fila cambia el de donde a factura
	foreach($parametros->cajas_input->caja_input as $caja){
			$caja->parametros->parametro[0]="factura";
		}

		$VarJS = $Controler->ObtenerCajasInputParametros($parametros);
		
		

?>
	<script type="text/javascript">
	// Esta variable global la necesita para montar la lineas.
	// En configuracion podemos definir SI / NO
		
	var CONF_campoPeso="<?php echo $CONF_campoPeso; ?>";
	var cabecera = []; // Donde guardamos idCliente, idUsuario,idTienda,FechaInicio,FechaFinal.
		cabecera['idUsuario'] = <?php echo $Usuario['id'];?>; // Tuve que adelantar la carga, sino funcionaria js.
		cabecera['idTienda'] = <?php echo $Tienda['idTienda'];?>; 
		cabecera['estadoFactura'] =<?php echo $estadoCab ;?>; // Si no hay datos GET es 'Nuevo'
		cabecera['idFacturaTemp'] = <?php echo $idFacturaTemporal ;?>;
		cabecera['idFactura'] = <?php echo $idFactura ;?>;
		cabecera['numFactura'] = <?php echo $numFactura ;?>;
		cabecera['fecha'] = <?php echo $fechaCab ;?>;
		cabecera['idCliente'] = <?php echo $idCliente ;?>;
		cabecera['nombreCliente'] = <?php echo $nombreCliente ;?>;
		
		 // Si no hay datos GET es 'Nuevo';
	var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array
	var albaranes =[];
<?php 
	if (isset($facturaTemporal)| isset($idFactura)){ 
?>
//	console.log("entre en el javascript");
	</script>
	<script type="text/javascript">
<?php
	$i= 0;
	//Introducimos los productos a la cabecera productos 
		if (isset($productos)){
			foreach($productos as $product){
?>	
				datos=<?php echo json_encode($product); ?>;
				productos.push(datos);
	
<?php 
		// cambiamos estado y cantidad de producto creado si fuera necesario.
			if ($product['estadoLinea'] !== 'Activo'){
			?>	productos[<?php echo $i;?>].estadoLinea=<?php echo'"'.$product['estadoLinea'].'"';?>;
			<?php
			}
			$i++;
			}
	
		}
		if (isset($albaranes)){
			foreach ($albaranes as $alb){
				?>
				datos=<?php echo json_encode($alb);?>;
				albaranes.push(datos);
				<?php
			}
		}
	}	
	
	$es=str_replace("'",'',$estadoCab);  
	
?>
</script>
<?php 
if ($idCliente==0){
	$idCliente="";
	$nombreCliente="";
}
if (isset($_GET['tActual'])){
	$nombreCliente=$cliente['Nombre'];
	
}

?>
</head>
<body>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_venta/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
<?php
	include '../../header.php';
?>
<script type="text/javascript">
// Objetos cajas de tpv
<?php echo $VarJS;?>
     function anular(e) {
          tecla = (document.all) ? e.keyCode : e.which;
          return (tecla != 13);
      }
</script>
<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
<div class="container">
			<?php 
			
			if (isset($mensaje) || isset($error)){   ?> 
				<div class="alert alert-<?php echo $tipomensaje; ?>"><?php echo $mensaje ;?></div>
				<?php 
				if (isset($error)){
				// No permito continuar, ya que hubo error grabe.
				return;
				}
				?>
			<?php
			}
			?>
			<h2 class="text-center"> <?php echo $titulo;?></h2>
			<a  href="./facturasListado.php">Volver AtrÃ¡s</a>
			<form action="" method="post" name="formProducto" onkeypress="return anular(event)">
					<input type="submit" value="Guardar" name="Guardar">
					<input type="submit" value="Cancelar" name="Cancelar">
					<?php
				if ($idFacturaTemporal>0){
					?>
					<input type="text" style="display:none;" name="idFactura" value="<?php echo $idFacturaTemporal;?>">
					<?php
				}
					?>
<div class="col-md-12" >
	<div class="col-md-8">
		<div class="col-md-12">
			
				<div class="col-md-4">
					<strong>Fecha Factura:</strong><br>
					<input type="date" name="fechaFac" id="fechaFac" size="10" data-obj= "fechaFac"  value="<?php echo $fecha;?>" onkeydown="controlEventos(event)" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" placeholder='yyyy-mm-dd' title=" Formato de entrada yyyy-mm-dd">
				</div>
				<div class="col-md-3">
					<strong>Estado:</strong><br>
				
					<span id="EstadoTicket"> <input type="text" id="estado" name="estado" value="<?php echo $es;?>" size="10" readonly></span><br>
				</div>
			
				<div class="col-md-4">
					<strong>Empleado:</strong><br>
					<input type="text" id="Usuario" name="Usuario" value="<?php echo $Usuario['nombre'];?>" size="10" readonly>
				</div>
			
		</div>
		<div class="form-group">
			<label>Cliente:</label>
			<input type="text" id="id_clienteFac" name="id_clienteFac" data-obj= "cajaIdClienteFac" value="<?php echo $idCliente;?>" size="2" onkeydown="controlEventos(event)" placeholder='id'>
			<input type="text" id="ClienteFac" name="ClienteFac" data-obj= "cajaClienteFac" placeholder="Nombre de cliente" onkeydown="controlEventos(event)" value="<?php echo $nombreCliente; ?>" size="60">
			<a id="buscar" class="glyphicon glyphicon-search buscar" onclick="buscarClientes('factura')"></a>
		</div>
	</div>
	<div class="col-md-4" >
	
		<div>
			<div style="margin-top:-50px;">
			<label style="<?php echo $stylea;?>" id="numAlbaranT">NÃºmero del albaran:</label>
			<input style="<?php echo $stylea;?>" type="text" id="numAlbaran" name="numAlbaran" value="" size="5" placeholder='Num' data-obj= "numAlbaran" onkeydown="controlEventos(event)">
			<a style="<?php echo $stylea;?>" id="buscarAlbaran" class="glyphicon glyphicon-search buscar" onclick="buscarAlbaran('albaran')"></a>
			<table  class="col-md-12" style="<?php echo $stylea;?>" id="tablaAlbaran"> 
				<thead>
				
				<td><b>NÃºmero</b></td>
				<td><b>Fecha</b></td>
				<td><b>Total</b></td>
				
				</thead>
				
				<?php 
				
				if (isset($albaranes)){
					$html=htmlAlbaranFactura($albaranes, "factura");
					echo $html['html'];
				}
				?>
			</table>
			</div>
		</div>
	</div>
	<!-- Tabla de lineas de productos -->
	<div>
		<table id="tabla" class="table table-striped" >
		<thead>
		  <tr>
			<th>L</th>
			<th>Num Albaran</th>
			<th>Id Articulo</th>
			<th>Referencia</th>
			<th>Cod Barras</th>
			<th>Descripcion</th>
			<th>Unid</th>
			<th>PVP</th>
			<th>Iva</th>
			<th>Importe</th>
			<th></th>
		  </tr>
		  <tr id="Row0" style=<?php echo $style;?>>  
			<td id="C0_Linea" ></td>
			<td></td>
			<td><input id="idArticuloFac" type="text" name="idArticuloFac" placeholder="idArticulo" data-obj= "cajaidArticuloFac" size="13" value=""  onkeydown="controlEventos(event)"></td>
			<td><input id="ReferenciaFac" type="text" name="ReferenciaFac" placeholder="Referencia" data-obj="cajaReferenciaFac" size="13" value="" onkeydown="controlEventos(event)"></td>
			<td><input id="CodbarrasFac" type="text" name="CodbarrasFac" placeholder="Codbarras" data-obj= "cajaCodBarrasFac" size="13" value="" data-objeto="cajaCodBarras" onkeydown="controlEventos(event)"></td>
			<td><input id="DescripcionFac" type="text" name="DescripcionFac" placeholder="Descripcion" data-obj="cajaDescripcionFac" size="20" value="" onkeydown="controlEventos(event)"></td>
		  </tr>
		</thead>
		<tbody>
			<?php 
			if (isset($productos)){
				foreach (array_reverse($productos) as $producto){
				$html=htmlLineaPedidoAlbaran($producto, "factura");
				echo $html['html'];
			}
		
			}
			?>
		</tbody>
	  </table>
	</div>
	<?php 
	if(isset($Datostotales)){
			// Ahora montamos base y ivas
			foreach ($Datostotales['desglose'] as  $iva => $basesYivas){
				switch ($iva){
					case 4 :
						$base4 = $basesYivas['base'];
						$iva4 = $basesYivas['iva'];
					break;
					case 10 :
						$base10 = $basesYivas['base'];
					$iva10 = $basesYivas['iva'];
				break;
				case 21 :
						$base21 = $basesYivas['base'];
						$iva21 = $basesYivas['iva'];
					break;
				}
			}
	
	?>

		<script type="text/javascript">
			total = <?php echo $Datostotales['total'];?>;
			</script>

			<?php
	}
	?>
	<div class="col-md-10 col-md-offset-2 pie-ticket">
		<table id="tabla-pie" class="col-md-6">
		<thead>
			<tr>
				<th>Tipo</th>
				<th>Base</th>
				<th>IVA</th>
			</tr>
		</thead>
		<tbody>
			<tr id="line4">
				<td id="tipo4">
					<?php echo (isset($base4) ? " 4%" : '');?>
				</td>
				<td id="base4">
					<?php echo (isset($base4) ? $base4 : '');?>
				</td>
				<td id="iva4">
					<?php echo (isset($iva4) ? $iva4 : '');?>
				</td>
				
			</tr>
			<tr id="line10">
				<td id="tipo10">
					<?php echo (isset($base10) ? "10%" : '');?>
				</td>
				<td id="base10">
					<?php echo (isset($base10) ? $base10 : '');?>
				</td>
				<td id="iva10">
					<?php echo (isset($iva10) ? $iva10 : '');?>
				</td>
				
			</tr>
			<tr id="line21">
				<td id="tipo21">
					<?php echo (isset($base21) ? "21%" : '');?>
				</td>
				<td id="base21">
					<?php echo (isset($base21) ? $base21 : '');?>
				</td>
				<td id="iva21">
					<?php echo (isset($iva21) ? $iva21 : '');?>
				</td>
				
			</tr>
		</tbody>
		</table>
		<div class="col-md-6">
			<div class="col-md-4">
			<h3>TOTAL</h3>
			</div>
			<div class="col-md-8 text-rigth totalImporte" style="font-size: 3em;">
				<?php echo (isset($Datostotales['total']) ? $Datostotales['total'] : '');?>
			</div>
		</div>
	</div>
	
	
	<div class="col-md-6">
		<h3>Formas de pago</h3>
			
			<div class="col-md-4">
					<strong>Forma de pago:</strong><br>
					<p id="formaspago">
					<?php 
					if(isset ($textoFormaPago)){
							echo $textoFormaPago['html'];
					}
				
					?>
					</p>
			</div>
			<div class="col-md-4">
					<strong>Fecha vencimiento:</strong><br>
					<p id="fechaVencimiento">
						<?php
						if (isset ($textoFecha)){
							echo $textoFecha['html'];
						}
					?>
					
						</p>
			</div>
		</div>
		<div class ="col-md-6">
			<h3 style="<?php echo $Simporte;?>">Entregas</h3>
			<table  id="tablaImporte" class="table table-striped" style="<?php echo $Simporte;?>">
			<thead>
			<tr>
			<td>Importe</td>
			<td>Fecha</td>
			<td>Pendiente</td>
			</tr>
			</thead>
			<tbody>
			 <tr id="fila0" style="<?php echo $Simporte;?>">  
				<td><input id="Eimporte" name="Eimporte" type="text" placeholder="importe" data-obj= "cajaEimporte" size="13" value=""  onkeydown="controlEventos(event)"></td>
				<td><input id="Efecha" name="Efecha" type="date" placeholder="fecha" data-obj= "cajaEfecha"  onkeydown="controlEventos(event)" value="<?php echo $fecha;?>" onkeydown="controlEventos(event)" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" placeholder='yyyy-mm-dd' title=" Formato de entrada yyyy-mm-dd"></td>
				<td></td>
			</tr>
			<?php //Si esa factura ya tiene importes los mostramos 
			if (isset ($importes)){
			
				foreach ($importes as $importe){
					$html=htmlImporteFactura($importe['importe'], $importe['fecha'], $importe['pendiente']);
					echo $html['html'];
				}
				
			}
			?>
			
			</tbody>
			
			</table>
		</div>
	</div>
</form>
</div>
<?php // Incluimos paginas modales
include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
?>
<script type="text/javascript">
	$('#fechaFac').focus();
	<?php
	if ($idCliente>0){
		?>
		$('#ClienteFac').prop('disabled', true);
		$('#id_clienteFac').prop('disabled', true);
		$("#buscar").css("display", "none");
		<?php
	}
	if (isset ($datosFactura['importes'])){
	if ($datosFactura['importes']){
		?>
		$("#tabla").find('input').attr("disabled", "disabled");
		$("#tabla").find('a').css("display", "none");
		<?php
	}
}
	?>
</script>
	</body>
</html>
