<!DOCTYPE html>
<html>
<head>
<?php
//comentario desde casa
include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	include_once ($RutaServidor.$HostNombre.'/controllers/parametros.php');
	$ClasesParametros = new ClaseParametros('parametros.xml');
	
	include '../../clases/cliente.php';
	$Ccliente=new Cliente($BDTpv);
	include 'clases/albaranesVentas.php';
	$Calbcli=new AlbaranesVentas($BDTpv);
	include_once 'clases/pedidosVentas.php';
	$Cped = new PedidosVentas($BDTpv);
	$Controler = new ControladorComun; 
	$Controler->loadDbtpv($BDTpv);
	$Tienda = $_SESSION['tiendaTpv'];
	$Usuario = $_SESSION['usuarioTpv'];// array con los datos de usuario
	$idAlbaranTemporal=0;
	$estado='Abierto';
	$idAlbaran=0;
	$numAlbaran=0;
	$idCliente=0;
	$nombreCliente="";
	$titulo="Albarán De Cliente ";
	$fecha=date('Y-m-d');
	$dedonde="albaran";
	$Datostotales=array();

	$parametros = $ClasesParametros->getRoot();
	foreach($parametros->cajas_input->caja_input as $caja){
			$caja->parametros->parametro[0]="albaran";
	}
	$VarJS = $Controler->ObtenerCajasInputParametros($parametros);
	$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
	$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_ventas',$Usuario['id']);
	$configuracionArchivo=array();
	foreach ($configuracion['incidencias'] as $config){
		if(get_object_vars($config)['dedonde']==$dedonde){
			array_push($configuracionArchivo, $config);
		}
	}
	
	
	if (isset($_GET['id'])){//Cuando recibe un albarán existente cargamos los datos
		$idAlbaran=$_GET['id'];
		$datosAlbaran=$Calbcli->datosAlbaran($idAlbaran);
		$productosAlbaran=$Calbcli->ProductosAlbaran($idAlbaran);
		$ivasAlbaran=$Calbcli->IvasAlbaran($idAlbaran);
		$pedidosAlbaran=$Calbcli->PedidosAlbaranes($idAlbaran);
		$estado=$datosAlbaran['estado'];
		$date=date_create($datosAlbaran['Fecha']);
		$fecha=date_format($date,'Y-m-d');
		$numAlbaran=$datosAlbaran['Numalbcli'];
		$idCliente=$datosAlbaran['idCliente'];
		if ($idCliente){
				// Si se cubrió el campo de idcliente llama a la función dentro de la clase cliente 
				$datosCliente=$Ccliente->DatosClientePorId($idCliente);
				$nombreCliente="'".$datosCliente['Nombre']."'";
		}
		$productosMod=modificarArrayProductos($productosAlbaran);
		$productos=json_decode(json_encode($productosMod));
		$Datostotales = recalculoTotales($productos);
		$productos=json_decode(json_encode($productos), true);
	
		if ($pedidosAlbaran){
			 $modificarPedido=modificarArrayPedidos($pedidosAlbaran, $BDTpv);
			 $pedidos=json_decode(json_encode($modificarPedido), true);
		}
			
		$total=$Datostotales['total'];
		
		
	}else{
		$bandera=1;
			if (isset($_GET['tActual'])){//Recibido un albarán temporal
				$idAlbaranTemporal=$_GET['tActual'];
				$datosAlbaran=$Calbcli->buscarDatosAlabaranTemporal($idAlbaranTemporal);//Recogemos todos los datos del albarán temporal 
				if (isset($datosAlbaran['numalbcli'])){
					$numAlbaran=$datosAlbaran['numalbcli'];
					$id=$Calbcli->datosAlbaranNum($numAlbaran);
					$idAlbaran=$id['id'];
				}
				echo $numAlbaran;
					if ($datosAlbaran['fechaInicio']=="0000-00-00 00:00:00"){
					$fecha=date('Y-m-d');
				}else{
					$fecha1=date_create($datosAlbaran['fechaInicio']);
					$fecha =date_format($fecha1, 'Y-m-d');
				}
			
				$idCliente=$datosAlbaran['idClientes'];
				$cliente=$Ccliente->DatosClientePorId($idCliente);
				if(isset($cliente['Nombre'])){
					$nombreCliente="'".$cliente['Nombre']."'";
				}
				$albaran=$datosAlbaran;
				$productos =  json_decode($datosAlbaran['Productos']) ;
				$pedidos=json_decode($datosAlbaran['Pedidos']);
				
			}
		
	}
		if(isset($albaran['Productos'])){
			// Obtenemos los datos totales ( fin de ticket);
			// convertimos el objeto productos en array
			$Datostotales = recalculoTotales($productos);
			$productos = json_decode(json_encode($productos), true); // Array de arrays	
		}
		if (isset($albaran['Pedidos'])){
			$pedidos=json_decode(json_encode($pedidos), true);
		}
		//Para guardar un albarán creamos un array con todos los datos 
		//Comprobamos que la existencia del albarán real si existe eliminamos todas las tablas que tengan que ver con el albarán real
		//Una vez eliminados creamos los registros con los datos nuevos y por último eliminamos el temporal
		//Si no existe un número de albarán real solo hay que crear un los registros nuevos de los albaranes en las diferentes tablas
		//Y eliminar el temporal
		if (isset($_POST['Guardar'])){
			$guardar=guardarAlbaran($_POST, $_GET, $BDTpv, $Datostotales);
			//~ echo '<pre>';
			//~ print_r($guardar);
			//~ echo '</pre>';
			if (count($guardar)==0){
				
				header('Location: albaranesListado.php');
			}else{
				foreach ($guardar as $error){
					echo '<div class="'.$error['class'].'">'
					. '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br> '.$error['dato']
					. '</div>';
				}
			}
			//~ if (isset($_POST['idTemporal'])){
				//~ $idTemporal=$_POST['idTemporal'];
			//~ }else{
				//~ $idTemporal=$_GET['tActual'];
			//~ }
			//~ $datosAlbaran=$Calbcli->buscarDatosAlabaranTemporal($idAlbaranTemporal);
			//~ if(isset($datosAlbaran['total'])){
				//~ $total=$datosAlbaran['total'];
			//~ }else{
				//~ $total=0;
			//~ }
			//~ $idAlbaran=0;
			//~ $datos=array(
			//~ 'Numtemp_albcli'=>$idTemporal,
			//~ 'Fecha'=>$_POST['fecha'],
			//~ 'idTienda'=>$Tienda['idTienda'],
			//~ 'idUsuario'=>$Usuario['id'],
			//~ 'idCliente'=>$datosAlbaran['idClientes'],
			//~ 'estado'=>"Guardado",
			//~ 'total'=>$total,
			//~ 'DatosTotales'=>$Datostotales,
			//~ 'productos'=>$datosAlbaran['Productos'],
			//~ 'pedidos'=>$datosAlbaran['Pedidos']
			//~ );
			//~ echo '<pre>';
			//~ print_r($datosAlbaran['Productos']);
			//~ echo '</pre>';
			//~ $errores=array();
			//~ if($datosAlbaran['numalbcli']>0){
				//~ $idAlbaran=$datosAlbaran['numalbcli'];
				//~ $eliminarTablasPrincipal=$Calbcli->eliminarAlbaranTablas($idAlbaran);
				//~ if (isset($eliminarTablasPrincipal['error'])){
				//~ $errores[0]=array ( 'tipo'=>'Danger!',
											 //~ 'dato' => $eliminarTablasPrincipal['consulta'],
											 //~ 'class'=>'alert alert-danger',
											 //~ 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
											 //~ );
				//~ }
				
			//~ }
			//~ if(count($errores)==0){
				//~ $addNuevo=$Calbcli->AddAlbaranGuardado($datos, $idAlbaran);
					//~ if(isset($addNuevo['error'])){
					//~ $errores[1]=array ( 'tipo'=>'Danger!',
												 //~ 'dato' => $addNuevo['consulta'],
												 //~ 'class'=>'alert alert-danger',
												 //~ 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
												 //~ );
					//~ }
				//~ $eliminarTemporal=$Calbcli->EliminarRegistroTemporal($idTemporal, $datosAlbaran['numalbcli']);
					//~ if(isset($eliminarTemporal['error'])){
					//~ $errores[2]=array ( 'tipo'=>'Danger!',
												 //~ 'dato' => $eliminarTemporal['consulta'],
												 //~ 'class'=>'alert alert-danger',
												 //~ 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
												 //~ );
					//~ }
				//~ }
			//~ if(count($errores)==0){
				//~ header('Location: albaranesListado.php');
			//~ }else{
					//~ foreach($errores as $error){
						//~ echo '<div class="'.$error['class'].'">'
						//~ . '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br>Sentencia: '.$error['dato']
						//~ . '</div>';
				//~ }
			//~ }
			
		}
		//Cuando cancelamos eliminamos los datos del albrán temporal y si tiene uno real le cambiamos el estado a Guardado
		if (isset($_POST['Cancelar'])){
			if (isset($_POST['idTemporal'])){
				$idTemporal=$_POST['idTemporal'];
			}else{
				if (isset($_GET['tActual'])){
					$idTemporal=$_GET['tActual'];
				}else{
					$idTemporal=0;
				}
				
			}
			echo "entre en cancelar";
			$datosAlbaran=$Calbcli->buscarDatosAlabaranTemporal($idTemporal);
			if (isset($datosAlbaran['Pedidos'])){
				$pedidos=json_decode($datosAlbaran['Pedidos'], true);
				foreach ($pedidos as $pedido){
				$mod=$Cped->ModificarEstadoPedido($pedido['idPedCli'], "Guardado");
				}
			}
			$idAlbaran=0;
			$eliminarTemporal=$Calbcli->EliminarRegistroTemporal($idTemporal, $idAlbaran);
				header('Location: albaranesListado.php');
		}
		if (isset ($pedidos) | isset($_GET['tActual'])| isset($_GET['id'])){
			$style="";
		}else{
			$style="display:none;";
		}
$titulo .= ': '.$estado;	
		
?>
	<script type="text/javascript">
	// Esta variable global la necesita para montar la lineas.
	// En configuracion podemos definir SI / NO
	<?php echo 'var configuracion='.json_encode($configuracionArchivo).';';?>	
	var CONF_campoPeso="<?php echo $CONF_campoPeso; ?>";
	var cabecera = []; // Donde guardamos idCliente, idUsuario,idTienda,FechaInicio,FechaFinal.
		cabecera['idUsuario'] = <?php echo $Usuario['id'];?>; // Tuve que adelantar la carga, sino funcionaria js.
		cabecera['idTienda'] = <?php echo $Tienda['idTienda'];?>; 
		cabecera['estado'] ='<?php echo $estado ;?>'; // Si no hay datos GET es 'Nuevo'
		cabecera['idTemporal'] = <?php echo $idAlbaranTemporal ;?>;
		cabecera['idReal'] = <?php echo $idAlbaran ;?>;
		cabecera['fecha'] = '<?php echo $fecha ;?>';
		cabecera['idCliente'] = <?php echo $idCliente ;?>;
		
		 // Si no hay datos GET es 'Nuevo';
	var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array
	var pedidos =[];
<?php 
	if (isset($albaranTemporal)| isset($idAlbaran)){ 
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
		if (isset($pedidos)){
			foreach ($pedidos as $pedi){
				?>
				datos=<?php echo json_encode($pedi);?>;
				pedidos.push(datos);
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
<script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
<div class="container">
		<a  onclick="abrirIndicencia('<?php echo $dedonde;?>' , <?php echo $Usuario['id'];?>, configuracion, <?php echo $idAlbaran ;?>);">Añadir Incidencia <span class="glyphicon glyphicon-pencil"></span></a>
			<h2 class="text-center"> <?php echo $titulo;?></h2>
			<form action="" method="post" name="formProducto" onkeypress="return anular(event)">
					<a  href="./albaranesListado.php">Volver Atrás</a>
					<input type="submit" value="Guardar" name="Guardar" id="bGuardar">
					<input type="submit" value="Cancelar" name="Cancelar" id="bCancelar">
					<?php
					if (isset($idAlbaranTemporal)){
				if ($idAlbaranTemporal>0){
					?>
					<input type="text" style="display:none;" name="idTemporal" value="<?php echo $idAlbaranTemporal;?>">
					<?php
				}
			}
					?>
<div class="col-md-12" >
	<div class="col-md-8">
		<div class="col-md-12">
			
				<div class="col-md-4">
					<strong>Fecha albarán:</strong><br>
					<input type="date" name="fecha" id="fecha" size="10" data-obj= "cajaFecha"  value="<?php echo $fecha;?>" onkeydown="controlEventos(event)" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" placeholder='yyyy-mm-dd' title=" Formato de entrada yyyy-mm-dd">
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
			<input type="text" id="id_cliente" name="idCliente" data-obj= "cajaIdCliente" value="<?php echo $idCliente;?>" size="2" onkeydown="controlEventos(event)" placeholder='id'>
			<input type="text" id="Cliente" name="Cliente" data-obj= "cajaCliente" placeholder="Nombre de cliente" onkeydown="controlEventos(event)" value="<?php echo $nombreCliente; ?>" size="60">
			<a id="buscar" class="glyphicon glyphicon-search buscar" onclick="buscarClientes('albaran')"></a>
		</div>
	</div>
	<div class="col-md-4" >
	
		<div>
			<div style="margin-top:-50px;">
			<label style="<?php echo $style;?>" id="numPedidoT">Número del pedido:</label>
			<input style="<?php echo $style;?>" type="text" id="numPedido" name="numPedido" value="" size="5" placeholder='Num' data-obj= "numPedido" onkeydown="controlEventos(event)">
			<a style="<?php echo $style;?>" id="buscarPedido" class="glyphicon glyphicon-search buscar" onclick="buscarPedido('pedidos')"></a>
			<table  class="col-md-12" style="<?php echo $style;?>" id="tablaPedidos"> 
				<thead>
				
				<td><b>Número</b></td>
				<td><b>Fecha</b></td>
				<td><b>Total</b></td>
				<td></td>
				
				</thead>
				
				<?php 
				//Si existen pedidos en el albarán los escribimos
				if (isset($pedidos)){
					foreach ($pedidos as $pedido){
					$html=htmlPedidoAlbaran($pedido, "albaran");
					echo $html['html'];
				}
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
			<th>Num Pedido</th>
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
			<td><input id="idArticulo" type="text" name="idArticulo" placeholder="idArticulo" data-obj= "cajaidArticulo" size="13" value=""  onkeydown="controlEventos(event)"></td>
			<td><input id="Referencia" type="text" name="Referencia" placeholder="Referencia" data-obj="cajaReferencia" size="13" value="" onkeydown="controlEventos(event)"></td>
			<td><input id="Codbarras" type="text" name="Codbarras" placeholder="Codbarras" data-obj= "cajaCodBarras" size="13" value="" data-objeto="cajaCodBarras" onkeydown="controlEventos(event)"></td>
			<td><input id="Descripcion" type="text" name="Descripcion" placeholder="Descripcion" data-obj="cajaDescripcion" size="20" value="" onkeydown="controlEventos(event)"></td>
		  </tr>
		</thead>
		<tbody>
			<?php 
			//Si el albarán ya tiene productos 
			if (isset($productos)){
				$productos=array_reverse($productos);
				foreach ( $productos as $producto){
				$html=htmlLineaPedidoAlbaran($producto, "albaran");
				echo $html;
			}
		
			}
			?>
		</tbody>
	  </table>
	</div>
	<?php 
	if (isset ($Datostotales['total'])){
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
		<?php 
		if(isset($Datostotales)){
			$htmlIvas=htmlTotales($Datostotales);
			echo $htmlIvas['html'];
			}
			 ?>
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
</form>
</div>
<?php // Incluimos paginas modales
echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
?>
<script type="text/javascript">
	$('#fecha').focus();
	<?php
	if ($idCliente>0){
		?>
		$('#Cliente').prop('disabled', true);
		$('#id_cliente').prop('disabled', true);
		$("#buscar").css("display", "none");
		<?php
	}
	if (isset ($datosAlbaran['estado'])){
		if ($datosAlbaran['estado']=="Facturado"){
			?>
			$("#tabla").find('input').attr("disabled", "disabled");
			$("#tabla").find('a').css("display", "none");
			$("#tablaPedidos").css("display", "none");
			$("#numPedidoT").css("display", "none");
			$("#numPedido").css("display", "none");
			$("#buscarPedido").css("display", "none");
			$("#bGuardar").css("display", "none");
			$("#bCancelar").css("display", "none");
			<?php
		}
	}
	?>
</script>
	</body>
</html>
