
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
	
	if ($_GET['id']){
		
		
	}else{
		$titulo="Crear Pedido De Cliente";
		$bandera=1;
		$estado='Pendiente';
		$estadoCab="'".'Pendiente'."'";
		$fecha=date('Y-m-d ');
		//~ $pedido_numero = 0;
		
			
		
	}
	if ($_GET['tActual']){
		
		// Si recibe el número de pedido temporal cubre los campos 
			$pedido_numero=$_GET['tActual'];
			$pedidoTemporal= $Cpedido->BuscarIdTemporal($pedido_numero);
			$estadoCab="'".$pedidoTemporal['estadoPedCli']."'";
			$estado=$pedidoTemporal['estadoPedCli'];
			$idCliente=$pedidoTemporal['idClientes'];
	
		$productos = json_decode( $pedidoTemporal['Productos'] , true );
		//~ print_r($pedidoTemporal['Productos']);
		//~ print_r($productos);
		
			if ($idCliente){
				// Si se cubrió el campo de idcliente llama a la función dentro de la clase cliente 
				$datosCliente=$Ccliente->DatosClientePorId($idCliente);
				$nombreCliente=$datosCliente['Nombre'];
				
				
			}
		}else{
			$pedido_numero = 0;
		}
	echo $pedido_numero;	
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
		cabecera['estadoPedido'] =<?php echo $estadoCab ;?>; // Si no hay datos GET es 'Nuevo'
		cabecera['numPedido'] = <?php echo $pedido_numero ;?>;
		 // Si no hay datos GET es 'Nuevo';
	var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array

<?php 
	if (isset($pedidoTemporal)){ 
		?>
		console.log("entre en el javascript");
		<?php
	$i= 0;
	
	foreach($productos as $product){
	?>
	datos=<?php echo json_encode($product); ?>;
	//~ console.log (datos);
	productos.push(datos);
	//~ console.log(productos);
		<?php 
		// cambiamos estado y cantidad de producto creado si fuera necesario.
		if ($product->estado !== 'Activo'){
		?>	productos[<?php echo $i;?>].estado=<?php echo'"'.$product['estado'].'"';?>;
		<?php
		}
		$i++;
	}
	
}
		?>
</script>
<?php 
//~ echo '<pre>';
//~ print_r(array_reverse($productos));
//~ echo '</pre>';
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
</script>
<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
<div class="container">
			<?php 
			if (isset($_GET)){
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
			<a  href="./pedidosListado.php">Volver Atrás</a>
			<form action="" method="post" name="formProducto">
<!--


				<?php //if ($_GET['id']){


					?>
					<input type="submit" value="Guardar">
					<?php
				//~ }else{?>
					<input type="submit" value="Nuevo">
					<?php 
				//~ }
					?>
-->
					
<div class="col-md-12" >
	<div class="col-md-8">
		<div class="col-md-12">
			<div class="col-md-7">
				<div class="col-md-6">
					<strong>Fecha Pedido:</strong><br/>
					<input type="date" name="fecha" id="fecha" data-obj= "cajaFecha"  value=<?php echo $fecha;?> onkeydown="controlEventos(event)" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" placeholder='yyyy-mm-dd' title=" Formato de entrada yyyy-mm-dd">
				</div>
				<div class="col-md-6">
					<strong>Estado:</strong>
					<span id="EstadoTicket"> <input type="text" id="estado" name="estado" value="<?php echo $estado;?>" readonly></span><br/>
					<?php if ($bandera<>1){?>
					<strong>NºT_temp:</strong>
					<span id="NTicket"><?php echo $ticket_numero ;?></span><br/>
					<?php 
					}
					?>
				</div>
			</div>
			<div class="col-md-3">
				<label>Empleado:</label>
				<input type="text" id="Usuario" name="Usuario" value="<?php echo $Usuario['nombre'];?>" size="25" readonly>
			</div>
		</div>
		<div class="form-group">
			<label>Cliente:</label>
			<input type="text" id="id_cliente" name="idCliente" data-obj= "cajaIdCliente" value="<?php echo $idCliente;?>" size="2" onkeydown="controlEventos(event)" placeholder='id'>
			<input type="text" id="Cliente" name="Cliente" data-obj= "cajaCliente" placeholder="Nombre de cliente" onkeydown="controlEventos(event)" value="<?php echo $nombreCliente; ?>" size="60">
			<a id="buscar" class="glyphicon glyphicon-search buscar" onclick="buscarClientes('pedidos')"></a>
		</div>
	</div>
	<!-- Tabla de lineas de productos -->
	<div>
		<table id="tabla" class="table table-striped">
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
		<tr id="Row0">  
			<td id="C0_Linea" ></td>
			<td><input id="idArticulo" type="text" name="idArticulo" placeholder="idArticulo" data-obj= "cajaidArticulo" size="13" value=""  onkeydown="controlEventos(event)"></td>
			<td><input id="Referencia" type="text" name="Referencia" placeholder="Referencia" data-obj="cajaReferencia" size="13" value="" onkeydown="controlEventos(event)"></td>
			<td><input id="Codbarras" type="text" name="Codbarras" placeholder="Codbarras" data-obj= "cajaCodBarras" size="13" value="" data-objeto="cajaCodBarras" onkeydown="controlEventos(event)"></td>
			<td><input id="Descripcion" type="text" name="Descripcion" placeholder="Descripcion" data-obj="cajaDescripcion" size="20" value="" onkeydown="controlEventos(event)"></td>
		</tr>
		
		</thead>
		<tbody>
			<?php 
			foreach (array_reverse($productos) as $producto){
			$html=htmlLineaPedido($producto, $producto['nfila'], $CONF_campoPeso);
			echo $html;

			}
		?>
		</tbody>
	  </table>
	</div>
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
					
				</td>
				<td id="base4">
					
				</td>
				<td id="iva4">
					
				</td>
				
			</tr>
			<tr id="line10">
				<td id="tipo10">
					
				</td>
				<td id="base10">
					
				</td>
				<td id="iva10">
					
				</td>
				
			</tr>
			<tr id="line21">
				<td id="tipo21">
					
				</td>
				<td id="base21">
					
				</td>
				<td id="iva21">
					
				</td>
				
			</tr>
		</tbody>
		</table>
		<div class="col-md-6">
			<div class="col-md-4">
			<h3>TOTAL</h3>
			</div>
			<div class="col-md-8 text-rigth totalImporte" style="font-size: 3em;">
			</div>
		</div>
	</div>
</form>
</div>

<?php // Incluimos paginas modales
include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
?>
<script type="text/javascript">

$('#Codbarras').focus();
</script>
	</body>
</html>
