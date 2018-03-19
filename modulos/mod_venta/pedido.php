<!DOCTYPE html>
<html>
<head>
<?php
include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	include 'clases/pedidosVentas.php';
	include '../../clases/cliente.php';
	
	$Cpedido=new PedidosVentas($BDTpv);
	$Ccliente=new Cliente($BDTpv);
	$Controler = new ControladorComun; 
	$Tienda = $_SESSION['tiendaTpv'];
	$Usuario = $_SESSION['usuarioTpv'];// array con los datos de usuario
	$titulo="Pedido De Cliente ";
	$estado='Abierto';
	$bandera=0;
	$fecha=date('Y-m-d');
	$idTemporal = 0;
	$idPedido=0;
	$total=0;
	$idCliente=0;
	
if ($_GET){
	if (isset($_GET['id'])){//Cuanod recibe el id de uno de los pedidos ya creados 
		$idPedido=$_GET['id'];
		$datosPedido=$Cpedido->datosPedidos($idPedido);//Buscar los datos de pedido 
		$estado=$datosPedido['estado'];
		$productosPedido=$Cpedido->ProductosPedidos($idPedido);//Buscamos los productos de ese pedido en su respectiva tabla
		$ivasPedido=$Cpedido->IvasPedidos($idPedido);//Buscamos los datos del iva 
		$fecha=$datosPedido['FechaPedido'];
		$idCliente=$datosPedido['idCliente'];
		if ($idCliente){
				// Si se cubrió el campo de idcliente llama a la función dentro de la clase cliente 
				$datosCliente=$Ccliente->DatosClientePorId($idCliente);
				$nombreCliente=$datosCliente['Nombre'];
		}
		$productosMod=modificarArrayProductos($productosPedido);//MOdificar el array de productos según lo que necesitamos
		$productos=json_decode(json_encode($productosMod));
		$Datostotales = recalculoTotales($productos);
		$productos=json_decode(json_encode($productosMod), true);
		
		
		$total=$Datostotales['total'];
		
		
	}else{
		
			if ($_GET['tActual']){//Si recibe un id de un temporal 
			$idTemporal=$_GET['tActual'];
			$pedidoTemporal= $Cpedido->BuscarIdTemporal($idTemporal);//Buscamos los datos del temporal
			$estado=$pedidoTemporal['estadoPedCli'];
			$idCliente=$pedidoTemporal['idClientes'];
			if ($pedidoTemporal['idPedcli']){
				$idPedido=$pedidoTemporal['idPedcli'];
			}else{
				$idPedido=0;
			}
			$pedido=$pedidoTemporal;
			$productos = json_decode( $pedidoTemporal['Productos']); // Array de objetos
			if ($idCliente){
				// Si se cubrió el campo de idcliente llama a la función dentro de la clase cliente 
				$datosCliente=$Ccliente->DatosClientePorId($idCliente);
				$nombreCliente=$datosCliente['Nombre'];
			}
		}
		
	}
}
$titulo .= ': '.$estado;

		if(isset($pedido['Productos'])){
			// Obtenemos los datos totales ( fin de ticket);
			// convertimos el objeto productos en array
			$Datostotales = recalculoTotales($productos);
			$productos = json_decode(json_encode($productos), true); // Array de arrays	
		}
		//Pasar un pedido temporal a real
		if (isset($_POST['Guardar'])){
			if ($_POST['idTemporal']){
				$idTemporal=$_POST['idTemporal'];
			}else{
				$idTemporal=$_GET['tActual'];
			}
			$pedidoTemporal= $Cpedido->BuscarIdTemporal($idTemporal);
			if($pedidoTemporal['total']){
				$total=$pedidoTemporal['total'];
			}else{
				$total=0;
			}
			$fechaCreacion=date("Y-m-d H:i:s");
			$datosPedido=array(
			'NPedidoTemporal'=>$idTemporal,
			'fecha'=>$_POST['fecha'],
			'idTienda'=>$Tienda['idTienda'],
			'idUsuario'=>$Usuario['id'],
			'idCliente'=>$pedidoTemporal['idClientes'],
			'estado'=>"Guardado",
			'formaPago'=>" ",
			'entregado'=>" ",
			'total'=>$total,
			'fechaCreacion'=>$fechaCreacion,
			'productos'=>$pedidoTemporal['Productos'],
			'DatosTotales'=>$Datostotales
			);
			
			if ($pedidoTemporal['idPedcli']){
				//Elimina los registros reales de pedidos  , añadir nuevos registros y eliminar el temporal , cuando se añaden nuevos registros
				//si tiene número de pedido se mantiene 
				$idPedido=$pedidoTemporal['idPedcli'];
				$datosPedidoReal=$Cpedido->datosPedidos($idPedido);
				$numPedido=$datosPedidoReal['Numpedcli'];
				
				$eliminarTablasPrincipal=$Cpedido->eliminarPedidoTablas($idPedido);
				$addNuevo=$Cpedido->AddPedidoGuardado($datosPedido, $idPedido, $numPedido);
				$eliminarTemporal=$Cpedido->EliminarRegistroTemporal($idTemporal, $idPedido);
			}else{
				//Como no tenemos número de pedido solo añadimos registros nuevos y eliminamos el temporal
				$idPedido=0;
				$numPedido=0;
				$addNuevo=$Cpedido->AddPedidoGuardado($datosPedido, $idPedido, $numPedido);
				$eliminarTemporal=$Cpedido->EliminarRegistroTemporal($idTemporal, $idPedido);
			}
			echo '<pre>';
			print_r($addNuevo);
			echo '</pre>';
			
			//header('Location: pedidosListado.php');
		}
		
		if (isset($datosPedido)){
			if($datosPedido['estado']=="Facturado"){
				$style="display:none;";
				$disabled = 'disabled';
			}else if (isset ($pedido)| $datosPedido['estado']=="Guardado"){
				$style="";
				$disabled = '';
			}else{
				$style="display:none;";
				$disabled = '';
			}
		}else{
			$disabled = '';
			$style="display:none;";
		}
		if (isset ($_GET['tActual'])|| isset ($_GET['id'])){
			$style="";
		}
		$parametros = simplexml_load_file('parametros.xml');
	
// -------------- Obtenemos de parametros cajas con sus acciones ---------------  //
		$VarJS = $Controler->ObtenerCajasInputParametros($parametros);
?>
	<script type="text/javascript">
	// Esta variable global la necesita para montar la lineas.
	// En configuracion podemos definir SI / NO
		
	var CONF_campoPeso="<?php echo $CONF_campoPeso; ?>";
	var cabecera = []; // Donde guardamos idCliente, idUsuario,idTienda,FechaInicio,FechaFinal.
		cabecera['idUsuario'] = <?php echo $Usuario['id'];?>; // Tuve que adelantar la carga, sino funcionaria js.
		cabecera['idTienda'] = <?php echo $Tienda['idTienda'];?>; 
		cabecera['estado'] ='<?php echo $estado ;?>'; // Si no hay datos GET es 'Nuevo'
		cabecera['idTemporal'] = <?php echo $idTemporal ;?>;
		cabecera['idReal'] = <?php echo $idPedido ;?>;
		cabecera['idCliente']=<?php echo $idCliente ;?>;
		cabecera['fecha']='<?php echo $fecha;?>';
		 // Si no hay datos GET es 'Nuevo';
	var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array

<?php 
	if (isset($pedidoTemporal)| isset($idPedido)){ 
?>
	console.log("entre en el javascript");
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
		if (isset ($product->estado)){
			if ($product->estado !== 'Activo'){
			?>	productos[<?php echo $i;?>].estado=<?php echo'"'.$product['estado'].'"';?>;
			<?php
			}
			$i++;
		}
	}
	
		
	}
	}
	
	
?>
</script>
<?php 
if ($idCliente===0){
	$idCliente="";
	$nombreCliente="";
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
<?php echo $VarJS;?>
     function anular(e) {
          tecla = (document.all) ? e.keyCode : e.which;
          return (tecla != 13);
      }
</script>
<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
<div class="container">
			<?php 
		
			if (isset($_GET)){
				if(isset($_GET['mensaje']) & isset($_GET['tipo'])){
				$mensaje=$_GET['mensaje'];
				$tipomensaje=$_GET['tipo'];
				if (isset($mensaje) || isset($error)){
			
			   ?> 
				<div class="alert alert-<?php echo $tipomensaje; ?>"><?php echo $mensaje ;?></div>
				<?php 
				if (isset($error)){
				// No permito continuar, ya que hubo error grabe.
				return;
				}
				?>
			<?php
		}
			}
		}
			?>
			<h2 class="text-center"> <?php echo $titulo;?></h2>
			<a  href="pedidosListado.php" onclick="ModificarEstadoPedido(pedido, Pedido);">Volver Atrás</a>
			<form action="" method="post" name="formProducto" onkeypress="return anular(event)">
				<?php 
				
					if($datosPedido['estado']<>"Facturado"){
				?>
					<input type="submit" value="Guardar" name="Guardar">
					<?php
					}
				
			
				if (isset($_GET['tActual'])){
					?>
					<input type="text" style="display:none;" name="idTemporal" value=<?php echo $_GET['tActual'];?>>
					<?php
				}
					?>
<div class="col-md-12" >
	<div class="col-md-8">
		<div class="col-md-12">
			<div class="col-md-7">
				<div class="col-md-6">
					<strong>Fecha Pedido:</strong><br/>
					<input type="date" name="fecha" id="fecha" data-obj= "cajaFecha"  value="<?php echo $fecha;?>" onkeydown="controlEventos(event)" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" placeholder='yyyy-mm-dd' title=" Formato de entrada yyyy-mm-dd" <?php echo $disabled;?>>
				</div>
				<div class="col-md-6">
					<strong>Estado:</strong>
					<span id="EstadoTicket"> <input type="text" id="estado" name="estado" value="<?php echo $estado;?>" readonly></span><br/>
				</div>
			</div>
			<div class="col-md-3">
				<label>Empleado:</label>
				<input type="text" id="Usuario" name="Usuario" value="<?php echo $Usuario['nombre'];?>" size="25" readonly>
			</div>
		</div>
		<div class="form-group">
			<label>Cliente:</label>
			<input type="text" id="id_cliente" name="idCliente" data-obj= "cajaIdCliente" value="<?php echo $idCliente;?>" size="2" onkeydown="controlEventos(event)" placeholder='id' <?php echo $disabled;?>>
			<input type="text" id="Cliente" name="Cliente" data-obj= "cajaCliente" placeholder="Nombre de cliente" onkeydown="controlEventos(event)" value="<?php echo $nombreCliente; ?>" size="60" <?php echo $disabled;?>>
			<a id="buscar" class="glyphicon glyphicon-search buscar" onclick="buscarClientes('pedidos')" style="<?php echo $style;?>"></a>
		</div>
	</div>
	<!-- Tabla de lineas de productos -->
	<div>
		<table id="tabla" class="table table-striped" >
		<thead>
		  <tr>
			<th>L</th>
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
			<td><input id="idArticulo" type="text" name="idArticulo" placeholder="idArticulo" data-obj= "cajaidArticulo" size="13" value=""  onkeydown="controlEventos(event)"></td>
			<td><input id="Referencia" type="text" name="Referencia" placeholder="Referencia" data-obj="cajaReferencia" size="13" value="" onkeydown="controlEventos(event)"></td>
			<td><input id="Codbarras" type="text" name="Codbarras" placeholder="Codbarras" data-obj= "cajaCodBarras" size="13" value="" data-objeto="cajaCodBarras" onkeydown="controlEventos(event)"></td>
			<td><input id="Descripcion" type="text" name="Descripcion" placeholder="Descripcion" data-obj="cajaDescripcion" size="20" value="" onkeydown="controlEventos(event)"></td>
		  </tr>
		</thead>
		<tbody>
			<?php 
		
			if (isset($productos)){
			foreach (array_reverse($productos) as $producto){
					
				$html=htmlLineaPedidoAlbaran($producto,"pedidos");
			
				echo $html;
			}
			}
		?>
		</tbody>
	  </table>
	</div>
	<?php 
	if (isset($pedido['Productos']) | isset ($idPedido)){
			// Ahora montamos base y ivas
			if (isset($Datostotales)){
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
			}
	if (isset($DatosTotales)){
	?>
		<script type="text/javascript">
			total = <?php echo $Datostotales['total'];?>;
			</script>
			<?php
	}
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
		$('#Cliente').prop('disabled', true);
		$('#id_cliente').prop('disabled', true);
		$("#buscar").css("display", "none");
		<?php
	}
	if($estado=="Facturado"){
		?>
		$("#Row0").css("display", "none");
		<?php
	}
	?>
</script>
	</body>
</html>
