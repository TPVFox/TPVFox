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
	if (isset($_GET['id'])){
		$idFactura=$_GET['id'];
		$titulo="Modificar Factura De Cliente";
		$estado='Modificado';
		$estadoCab="'".'Modificado'."'";
		$datosFactura=$Cfaccli->datosFactura($idFactura);
		$productosFactura=$Cfaccli->ProductosFactura($idFactura);
		$ivasFactura=$Cfaccli->IvasFactura($idAlbaran);
		$albaranFactura=$Cfaccli->AlbaranesFactura($idAlbaran);
		
		$date=date_create($datosAlbaran['Fecha']);
		$fecha=date_format($date,'Y-m-d');
		$fechaCab="'".$fecha."'";
		$idFacturaTemporal=0;
		$numFactura=$datosFactura['Numfaccli'];
		$idCliente=$datosFactura['idCliente'];
		if ($idCliente){
				// Si se cubrió el campo de idcliente llama a la función dentro de la clase cliente 
				$datosCliente=$Ccliente->DatosClientePorId($idCliente);
				$nombreCliente="'".$datosCliente['Nombre']."'";
		}
		$productos=json_decode(json_encode($productosFactura));
		$Datostotales = recalculoTotalesAl($productos);
		//$productos=json_decode(json_encode($productosFactura), true);
		//if ($albaranFactura){
		//	 $modificarPedido=modificarArrayPedidos($albaranFactura, $BDTpv);
		//	 $pedidos=json_decode(json_encode($modificarPedido), true);
		//}
		$total=$Datostotales['total'];
		
		
	}else{
		$titulo="Crear Factura De Cliente";
		$bandera=1;
		$estado='Abierto';
		$estadoCab="'".'Abierto'."'";
		$fecha=date('Y-m-d');
		$fechaCab="'".$fecha."'";
			if (isset($_GET['tActual'])){
				$idFacturaTemporal=$_GET['tActual'];
				$datosFactura=$Cfaccli->buscarDatosFacturasTemporal($idFacturaTemporal);
				if (isset($datosFactura['Numfaccli '])){
					$numFactura=$datosFactura['Numfaccli'];
				}else{
					$numFactura=0;
				}
				$fecha1=date_create($datosFactura['fechaInicio']);
				$fecha =date_format($fecha1, 'Y-m-d');
				$idCliente=$datosFactura['idClientes'];
				$cliente=$Ccliente->DatosClientePorId($idCliente);
				$nombreCliente="'".$cliente['Nombre']."'";
				$fechaCab="'".$fecha."'";
				$idFactura=0;
				$estadoCab="'".'Abierto'."'";
				$factura=$datosFactura;
				
				$productos =  json_decode($datosFactura['Productos']) ;
				//~ echo '<pre>';
				//~ print_r($productos);
				//~ echo '</pre>';
				$albaranes=json_decode($datosFactura['Albaranes']);
				
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
		
		if (isset($_POST['Guardar'])){
			if ($_POST['idTemporal']){
				$idTemporal=$_POST['idTemporal'];
			}else{
				$idTemporal=$_GET['tActual'];
			}
			$datosFactura=$Cfaccli->buscarDatosFacturasTemporal($idFacturaTemporal);
			if($datosFactura['total']){
				$total=$datosFactura['total'];
			}else{
				$total=0;
			}
			
			$datos=array(
			'Numtemp_faccli'=>$idTemporal,
			'Fecha'=>$_POST['fechaFac'],
			'idTienda'=>$Tienda['idTienda'],
			'idUsuario'=>$Usuario['id'],
			'idCliente'=>$datosFactura['idClientes'],
			'estado'=>"Guardado",
			'total'=>$total,
			'DatosTotales'=>$Datostotales,
			'productos'=>$datosFactura['Productos'],
			'albaranes'=>$datosFactura['Albaranes']
			);
		
			if($datosFactura['numfaccli']>0){
				$idFactura=$datosFactura['numfaccli'];
			
				//$eliminarTablasPrincipal=$Calbcli->eliminarAlbaranTablas($idFactura);
				// $addNuevo=$Calbcli->AddAlbaranGuardado($datos, $idFactura);
				// $eliminarTemporal=$Calbcli->EliminarRegistroTemporal($idTemporal, $idFactura);
			 }else{
				//$idFactura=0;
				//$addNuevo=$Calbcli->AddAlbaranGuardado($datos, $idFactura);
				//$eliminarTemporal=$Calbcli->EliminarRegistroTemporal($idTemporal, $idFactura);
			}
		//header('Location: albaranesListado.php');
			
		}
		if (isset($_POST['Cancelar'])){
			if ($_POST['idTemporal']){
				$idTemporal=$_POST['idTemporal'];
			}else{
				$idTemporal=$_GET['tActual'];
			}
			//echo "entre en cancelar";
			$datosFactura=$Cfaccli->buscarDatosFacturasTemporal($idAlbaranTemporal);
			$albaranes=json_decode($datosFactura['Albaranes'], true);
			foreach ($albaranes as $albaran){
				$mod=$Cped->ModificarEstadoAlbaran($albaran['idAlCli'], "Guardado");
			}
			$idFactura=0;
			$eliminarTemporal=$Calbcli->EliminarRegistroTemporal($idTemporal, $idFactura);
				header('Location: albaranesListado.php');
		}
		
		if (isset ($albaranes) | $_GET['tActual']| $_GET['id']){
			$style="";
		}else{
			$style="display:none;";
		}
	
		$parametros = simplexml_load_file('parametros.xml');
	
// -------------- Obtenemos de parametros cajas con sus acciones ---------------  //
//Como estamos el albaranes la caja de input num fila cambia el de donde a albaran
		$parametros->cajas_input->caja_input[10]->parametros->parametro[0][0]="factura";
		
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
			if (isset($_GET['mensaje'])){
				$mensaje=$_GET['mensaje'];
				$tipomensaje=$_GET['tipo'];
			}
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
			<a  href="./facturasListado.php">Volver Atrás</a>
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
					<span id="EstadoTicket"> <input type="text" id="estado" name="estado" value="<?php echo $estado;?>" size="10" readonly></span><br>
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
			<label style="<?php echo $style;?>" id="numAlbaranT">Número del albaran:</label>
			<input style="<?php echo $style;?>" type="text" id="numAlbaran" name="numAlbaran" value="" size="5" placeholder='Num' data-obj= "numAlbaran" onkeydown="controlEventos(event)">
			<a style="<?php echo $style;?>" id="buscarAlbaran" class="glyphicon glyphicon-search buscar" onclick="buscarAlbaran('albaran')"></a>
			<table  class="col-md-12" style="<?php echo $style;?>" id="tablaAlbaran"> 
				<thead>
				
				<td><b>Número</b></td>
				<td><b>Fecha</b></td>
				<td><b>Total</b></td>
				
				</thead>
				
				<?php 
				
				if (isset($albaranes)){
					$html=htmlAlbaranFactura($albaranes);
					echo $html['html'];
				}
				?>
			</table>
			</div>
		</div>
	</div>
	<!-- Tabla de lineas de productos -->
	<div>
		<table id="tabla" class="table table-striped">
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
			//~ echo '<pre>';
			//~ print_r($productos);
			//~ echo '</pre>';
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
	if ($factura['Productos']){
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
					
					</p>
			</div>
			<div class="col-md-4">
					<strong>Fecha vencimiento:</strong><br>
					<p id="fechaVencimiento">
						
						</p>
			</div>
		</div>
	</div>
</form>
</div>
<?php // Incluimos paginas modales
include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
?>
<script type="text/javascript">
	$('#id_cliente').focus();
	<?php
	if ($idCliente>0){
		?>
		$('#ClienteFac').prop('disabled', true);
		$('#id_clienteFac').prop('disabled', true);
		$("#buscar").css("display", "none");
		<?php
	}
	?>
</script>
	</body>
</html>
