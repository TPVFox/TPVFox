<!DOCTYPE html>
<html>
<head>
<?php
include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	include '../../clases/Proveedores.php';
	$Cprveedor=new Proveedores($BDTpv);
	include 'clases/albaranesCompras.php';
	$Calbcli=new AlbaranesCompras($BDTpv);
	include_once 'clases/pedidosCompras.php';
	$Cped = new PedidosCompras($BDTpv);
	$Controler = new ControladorComun; 
	$Tienda = $_SESSION['tiendaTpv'];
	$Usuario = $_SESSION['usuarioTpv'];// array con los datos de usuario
	$titulo="Crear Albarán De Proveedor";
	$estado='Abierto';
	if (isset($_GET['id'])){
		
		
	}else{
		
		
	}
		//~ if(isset($albaran['Productos'])){
			//~ // Obtenemos los datos totales ( fin de ticket);
			//~ // convertimos el objeto productos en array
			//~ $Datostotales = recalculoTotalesAl($productos);
			//~ $productos = json_decode(json_encode($productos), true); // Array de arrays	
		//~ }
		//~ if (isset($albaran['Pedidos'])){
			//~ $pedidos=json_decode(json_encode($pedidos), true);
		//~ }
		
		
		if (isset ($pedidos) | $_GET['tActual']| $_GET['id']){
			$style="";
		}else{
			$style="display:none;";
		}
	
		$parametros = simplexml_load_file('parametros.xml');
	
// -------------- Obtenemos de parametros cajas con sus acciones ---------------  //
//Como estamos el albaranes la caja de input num fila cambia el de donde a albaran
		$parametros->cajas_input->caja_input[10]->parametros->parametro[0][0]="albaran";
		
		$VarJS = $Controler->ObtenerCajasInputParametros($parametros);

?>
	<script type="text/javascript">
	// Esta variable global la necesita para montar la lineas.
	// En configuracion podemos definir SI / NO
		
	var CONF_campoPeso="<?php echo $CONF_campoPeso; ?>";
	var cabecera = []; // Donde guardamos idCliente, idUsuario,idTienda,FechaInicio,FechaFinal.
		cabecera['idUsuario'] = <?php echo $Usuario['id'];?>; // Tuve que adelantar la carga, sino funcionaria js.
		cabecera['idTienda'] = <?php echo $Tienda['idTienda'];?>; 
		cabecera['estadoAlbaran'] =<?php echo $estadoCab ;?>; // Si no hay datos GET es 'Nuevo'
		cabecera['idAlbaranTemp'] = <?php echo $idAlbaranTemporal ;?>;
		cabecera['idAlbaran'] = <?php echo $idAlbaran ;?>;
		cabecera['numAlbaran'] = <?php echo $numAlbaran ;?>;
		cabecera['fecha'] = <?php echo $fechaCab ;?>;
		cabecera['idCliente'] = <?php echo $idCliente ;?>;
		cabecera['nombreCliente'] = <?php echo $nombreCliente ;?>;
		
		 // Si no hay datos GET es 'Nuevo';
	var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array
	var pedidos =[];
<?php 
	if (isset($albaranTemporal)| isset($idAlbaran)){ 
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
//~ if ($idCliente==0){
	//~ $idCliente="";
	//~ $nombreCliente="";
//~ }
//~ if (isset($_GET['tActual'])){
	//~ $nombreCliente=$cliente['Nombre'];
//~ }
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
			<a  href="./albaranesListado.php">Volver Atrás</a>
			<form action="" method="post" name="formProducto" onkeypress="return anular(event)">
					<input type="submit" value="Guardar" name="Guardar" id="bGuardar">
					<input type="submit" value="Cancelar" name="Cancelar" id="bCancelar">
					<?php
				if ($idAlbaranTemporal>0){
					?>
					<input type="text" style="display:none;" name="idTemporal" value="<?php echo $idAlbaranTemporal;?>">
					<?php
				}
					?>
<div class="col-md-12" >
	<div class="col-md-8">
		<div class="col-md-12">
			
				<div class="col-md-4">
					<strong>Fecha albarán:</strong><br>
					<input type="date" name="fechaAl" id="fechaAl" size="10" data-obj= "fechaAl"  value="<?php echo $fecha;?>" onkeydown="controlEventos(event)" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" placeholder='yyyy-mm-dd' title=" Formato de entrada yyyy-mm-dd">
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
			<input type="text" id="id_clienteAl" name="id_clienteAl" data-obj= "cajaIdClienteAl" value="<?php echo $idCliente;?>" size="2" onkeydown="controlEventos(event)" placeholder='id'>
			<input type="text" id="ClienteAl" name="ClienteAl" data-obj= "cajaClienteAl" placeholder="Nombre de cliente" onkeydown="controlEventos(event)" value="<?php echo $nombreCliente; ?>" size="60">
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
				
				</thead>
				
				<?php 
				//~ if (isset($pedidos)){
					//~ $html=htmlPedidoAlbaran($pedidos, "albaran");
					//~ echo $html['html'];
				//~ }
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
			<td><input id="idArticuloAl" type="text" name="idArticuloAl" placeholder="idArticulo" data-obj= "cajaidArticuloAl" size="13" value=""  onkeydown="controlEventos(event)"></td>
			<td><input id="ReferenciaAl" type="text" name="ReferenciaAl" placeholder="Referencia" data-obj="cajaReferenciaAl" size="13" value="" onkeydown="controlEventos(event)"></td>
			<td><input id="CodbarrasAl" type="text" name="CodbarrasAl" placeholder="Codbarras" data-obj= "cajaCodBarrasAl" size="13" value="" data-objeto="cajaCodBarras" onkeydown="controlEventos(event)"></td>
			<td><input id="DescripcionAl" type="text" name="DescripcionAl" placeholder="Descripcion" data-obj="cajaDescripcionAl" size="20" value="" onkeydown="controlEventos(event)"></td>
		  </tr>
		</thead>
		<tbody>
			<?php 
			//~ echo '<pre>';
			//~ print_r($productos);
			//~ echo '</pre>';
			//~ if (isset($productos)){
				//~ foreach (array_reverse($productos) as $producto){
				//~ $html=htmlLineaPedidoAlbaran($producto);
				//~ echo $html['html'];
			//~ }
		
			//~ }
			?>
		</tbody>
	  </table>
	</div>
	<?php 
			// Ahora montamos base y ivas
			//~ foreach ($Datostotales['desglose'] as  $iva => $basesYivas){
				//~ switch ($iva){
					//~ case 4 :
						//~ $base4 = $basesYivas['base'];
						//~ $iva4 = $basesYivas['iva'];
					//~ break;
					//~ case 10 :
						//~ $base10 = $basesYivas['base'];
						//~ $iva10 = $basesYivas['iva'];
					//~ break;
					//~ case 21 :
						//~ $base21 = $basesYivas['base'];
						//~ $iva21 = $basesYivas['iva'];
					//~ break;
				//~ }
			//~ }
	
	?>

		<script type="text/javascript">
			//total = <?php echo $Datostotales['total'];?>;
			</script>

			<?php

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
					<?php //echo (isset($base4) ? " 4%" : '');?>
				</td>
				<td id="base4">
					<?php //echo (isset($base4) ? $base4 : '');?>
				</td>
				<td id="iva4">

					<?php //echo (isset($iva4) ? $iva4 : '');?>

				</td>
				
			</tr>
			<tr id="line10">
				<td id="tipo10">
					<?php //echo (isset($base10) ? "10%" : '');?>
				</td>
				<td id="base10">
					<?php //echo (isset($base10) ? $base10 : '');?>
				</td>
				<td id="iva10">
					<?php //echo (isset($iva10) ? $iva10 : '');?>
				</td>
				
			</tr>
			<tr id="line21">
				<td id="tipo21">
					<?php// echo (isset($base21) ? "21%" : '');?>
				</td>
				<td id="base21">
					<?php //echo (isset($base21) ? $base21 : '');?>
				</td>
				<td id="iva21">
					<?php //echo (isset($iva21) ? $iva21 : '');?>
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
	//~ $('#id_cliente').focus();
	//~ <?php
	//~ if ($idCliente>0){
		//~ ?>
		//~ $('#ClienteAl').prop('disabled', true);
		//~ $('#id_clienteAl').prop('disabled', true);
		//~ $("#buscar").css("display", "none");
		//~ <?php
	//~ }
	//~ if ($datosAlbaran['estado']=="Facturado"){
		//~ ?>
		//~ $("#tabla").find('input').attr("disabled", "disabled");
		//~ $("#tabla").find('a').css("display", "none");
		//~ $("#tablaPedidos").css("display", "none");
		//~ $("#numPedidoT").css("display", "none");
		//~ $("#numPedido").css("display", "none");
		//~ $("#buscarPedido").css("display", "none");
		//~ $("#bGuardar").css("display", "none");
		//~ $("#bCancelar").css("display", "none");
		//~ <?php
	//~ }
	//~ ?>
</script>
	</body>
</html>
