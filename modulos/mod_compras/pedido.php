<!DOCTYPE html>
<html>
<head>
<?php
include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	include 'clases/pedidosCompras.php';
	include '../../clases/Proveedores.php';
	$Cpedido=new PedidosCompras($BDTpv);
	$Cprveedor=new Proveedores($BDTpv);
	$Controler = new ControladorComun; 
	$Tienda = $_SESSION['tiendaTpv'];
	$Usuario = $_SESSION['usuarioTpv'];// array con los datos de usuario
	$titulo="Crear Pedido De Proveedor";
	$estado='Abierto';
	
	if ($_GET){
		if (isset($_GET['id'])){
			$idPedido=$_GET['id'];
			$datosPedido=$Cpedido->datosPedidos($idPedido);
			if ($datosPedido['estado']=='Facturado'){
				$titulo="Pedidos De Proveedor Facturado";
				$estado='Facturado';
				$estadoCab="'".'Facturado'."'";
			}else{
				$titulo="Modificar Pedido De Proveedor";
				$estado='Modificado';
				$estadoCab="'".'Modificado'."'";
			}
			$productosPedido=$Cpedido->ProductosPedidos($idPedido);
			$ivasPedido=$Cpedido->IvasPedidos($idPedido);
			$fecha=$datosPedido['FechaPedido'];
			$idProveedor=$datosPedido['idProveedor'];
			if ($idProveedor){
				// Si se cubrió el campo de idcliente llama a la función dentro de la clase cliente 
				$datosProveedor=$Cprveedor->buscarProveedorId($idProveedor);
				$nombreProveedor=$datosProveedor['nombrecomercial'];
			}
		
			$productosMod=modificarArrayProductos($productosPedido);
			$productos=json_decode(json_encode($productosMod));
			$Datostotales = recalculoTotalesAl($productos);
			$productos=json_decode(json_encode($productosMod), true);
			
		
			$total=$Datostotales['total'];
			$numPedido=$datosPedido['Numpedpro'];
			$numPedidoTemp=0;
			$fechaCab="'".$datosPedido['FechaPedido']."'";
		}else{
			$titulo="Crear Pedido De Proveedor";
			$bandera=1;
			$estado='Abierto';
			$estadoCab="'".'Abierto'."'";
			$fecha=date('Y-m-d');
			if ($_GET['tActual']){
				$numPedidoTemp=$_GET['tActual'];
				$pedidoTemporal=$Cpedido->DatosTemporal($numPedidoTemp);
				$estadoCab="'".$pedidoTemporal['estadoPedPro']."'";
				$estado=$pedidoTemporal['estadoPedPro'];
				$idProveedor=$pedidoTemporal['idProveedor'];
				if ($pedidoTemporal['idPedpro']){
					
					$idPedido=$pedidoTemporal['idPedpro'];
					
					$datos=$Cpedido->DatosPedido($idPedido);
					$numPedido=$datos['Numpedpro'];
				}else{
					$idPedido=0;
					$numPedido=0;
				}
				if ($pedidoTemporal['fechaInicio']){
					$bandera=new DateTime($pedidoTemporal['fechaInicio']);
					$fecha=$bandera->format('Y-m-d');
					$fechaCab="'".$fecha."'";
				}else{
					$fecha=date('Y-m-d');
					$fechaCab="'".$fecha."'";
				}
				$pedido=$pedidoTemporal;
				$productos = json_decode( $pedidoTemporal['Productos']); // Array de objetos
				if ($idProveedor){
					$datosProveedor=$Cprveedor->buscarProveedorId($idProveedor);
					$nombreProveedor=$datosProveedor['nombrecomercial'];
				}
				
			}
		}
		
		
		
		
	}else{
	$fecha=date('Y-m-d');
	$fechaCab="'".$fecha."'";
	$estadoCab="'".'Abierto'."'";
	$pedido_numero = 0;
	$idPedido=0;
	$total=0;
	$idProveedor=0;
	$numPedidoTemp=0;
	$numPedido=0;
	$nombreProveedor="";
}
if(isset($pedido['Productos'])){
			// Obtenemos los datos totales ( fin de ticket);
			// convertimos el objeto productos en array
			$Datostotales = recalculoTotalesAl($productos);
			$productos = json_decode(json_encode($productos), true); // Array de arrays	
		}
if (isset($_POST['Guardar'])){
	if ($_POST['idTemporal']){
		$numPedidoTemp=$_POST['idTemporal'];
	}else{
		$numPedidoTemp=$_GET['tActual'];
	}
	$pedidoTemporal=$Cpedido->DatosTemporal($numPedidoTemp);
	if($pedidoTemporal['total']){
		$total=$pedidoTemporal['total'];
	}else{
		$total=0;
	}
	//~ if ($pedidoTemporal['fechaInicio']){
		//~ $bandera=new DateTime($pedidoTemporal['fechaInicio']);
		//~ $fecha=$bandera->format('Y-m-d');
	//~ }else{
		//~ $fecha=date('Y-m-d');		
	//~ }
	if (isset($_POST['fecha'])){
		$bandera=new DateTime($_POST['fecha']);
		$fecha=$bandera->format('Y-m-d');
	}else{
		if ($pedidoTemporal['fechaInicio']){
			$bandera=new DateTime($pedidoTemporal['fechaInicio']);
			$fecha=$bandera->format('Y-m-d');
		}else{
			$fecha=date('Y-m-d');		
		}
	}
	if ($pedidoTemporal['idPedpro']){
		$datosPedidoReal=$Cpedido->datosPedidos($pedidoTemporal['idPedpro']);
		$numPedido=$datosPedidoReal['Numpedpro'];
	}else{
		$numPedido=0;
	}
	echo $numPedido;
	$fechaCreacion=date("Y-m-d H:i:s");
	$datosPedido=array(
		'Numtemp_pedpro'=>$numPedidoTemp,
		'FechaPedido'=>$fecha,
		'idTienda'=>$Tienda['idTienda'],
		'idUsuario'=>$Usuario['id'],
		'idProveedor'=>$pedidoTemporal['idProveedor'],
		'estado'=>"Guardado",
		'total'=>$total,
		'numPedido'=>$numPedido,
		'fechaCreacion'=>$fechaCreacion,
		'Productos'=>$pedidoTemporal['Productos'],
		'DatosTotales'=>$Datostotales
	);
	if ($pedidoTemporal['idPedpro']){
		$idPedido=$pedidoTemporal['idPedpro'];
		$eliminarTablasPrincipal=$Cpedido->eliminarPedidoTablas($idPedido);
		$addNuevo=$Cpedido->AddPedidoGuardado($datosPedido, $idPedido, $numPedido);
		$eliminarTemporal=$Cpedido->eliminarTemporal($numPedidoTemp, $idPedido);
	}else{
		$idPedido=0;
		$addNuevo=$Cpedido->AddPedidoGuardado($datosPedido, $idPedido);
		$eliminarTemporal=$Cpedido->eliminarTemporal($numPedidoTemp, $idPedido);
	}
	
	
	header('Location: pedidosListado.php');
	
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
		cabecera['estadoPedido'] =<?php echo $estadoCab ;?>; // Si no hay datos GET es 'Nuevo'
		cabecera['numPedidoTemp'] = <?php echo $numPedidoTemp ;?>;
		cabecera['idPedido'] = <?php echo $idPedido ;?>;
		cabecera['numPedido']=<?php echo $numPedido;?>;
		cabecera['idProveedor']=<?php echo $idProveedor ;?>;
		cabecera['fecha']=<?php echo $fechaCab;?>;
		 // Si no hay datos GET es 'Nuevo';
	var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array

<?php 
	//~ if (isset($pedidoTemporal)| isset($idPedido)){ 
?>
	console.log("entre en el javascript");
	</script>
	<script type="text/javascript">
<?php
	$i= 0;
	if (isset($productos)){
	if ($productos){
		
		foreach($productos as $product){
?>
			datos=<?php echo json_encode($product); ?>;

			productos.push(datos);
	

<?php 

		//cambiamos estado y cantidad de producto creado si fuera necesario.
		if (isset ($product->estado)){
			 if ($product->estado !== 'Activo'){
			 ?>	productos[<?php echo $i;?>].estado=<?php echo'"'.$product['estado'].'"';?>;
			 <?php
			 }
		 }
			 $i++;
		 }
	
	 }	
 
	 }
	
	
?>
</script>
<?php 
 if ($idProveedor===0){
	 $idProveedor="";
}
?>
</head>
<body>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_compras/funciones.js"></script>
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
		
			//~ if (isset($_GET)){
				//~ if(isset($_GET['mensaje']) & isset($_GET['tipo'])){
				//~ $mensaje=$_GET['mensaje'];
				//~ $tipomensaje=$_GET['tipo'];
				//~ if (isset($mensaje) || isset($error)){
			
			   ?> 
<!--
				<div class="alert alert-<?php //echo $tipomensaje; ?>"><?php //echo $mensaje ;?></div>
-->
				<?php 
				//~ if (isset($error)){
				//~ // No permito continuar, ya que hubo error grabe.
				//~ return;
				//~ }
				?>
			<?php
		//~ }
			//~ }
		//~ }
			?>
			<h2 class="text-center"> <?php echo $titulo;?></h2>
			<a  href="pedidosListado.php" onclick="ModificarEstadoPedido(pedido, Pedido);">Volver Atrás</a>
			<form action="" method="post" name="formProducto" onkeypress="return anular(event)">
			<input type="submit" value="Guardar" name="Guardar" id="bGuardar">
					<?php
				if (isset($numPedidoTemp)){
					?>
					<input type="text" style="display:none;" name="idTemporal" value=<?php echo $numPedidoTemp;?>>
					<?php
				}
					?>
<div class="col-md-12" >
	<div class="col-md-8">
		<div class="col-md-12">
			<div class="col-md-7">
				<div class="col-md-6">
					<strong>Fecha Pedido:</strong><br/>
					<input type="date" name="fecha" id="fecha" data-obj= "cajaFecha"  value="<?php echo $fecha;?>" onkeydown="controlEventos(event)" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" placeholder='yyyy-mm-dd' title=" Formato de entrada yyyy-mm-dd">
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
			<label>Proveedor:</label>
			<input type="text" id="id_proveedor" name="id_proveedor" data-obj= "cajaIdProveedor" value="<?php echo $idProveedor;?>" size="2" onkeydown="controlEventos(event)" placeholder='id'>
			<input type="text" id="Proveedor" name="Proveedor" data-obj= "cajaProveedor" placeholder="Nombre de proveedor" onkeydown="controlEventos(event)" value="<?php echo $nombreProveedor; ?>" size="60" >
			<a id="buscar" class="glyphicon glyphicon-search buscar" onclick="buscarProveedor('pedidos')"></a>
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
			<th>Referencia Proveedor</th>
			<th>Cod Barras</th>
			<th>Descripcion</th>
			<th>Unid</th>
			<th>Coste</th>
			<th>Iva</th>
			<th>Importe</th>
			<th></th>
		  </tr>
		  <tr id="Row0">  
			<td id="C0_Linea" ></td>
			<td><input id="idArticulo" type="text" name="idArticulo" placeholder="idArticulo" data-obj= "cajaidArticulo" size="13" value=""  onkeydown="controlEventos(event)"></td>
			<td><input id="Referencia" type="text" name="Referencia" placeholder="Referencia" data-obj="cajaReferencia" size="13" value="" onkeydown="controlEventos(event)"></td>
			<td><input id="ReferenciaPro" type="text" name="ReferenciaPro" placeholder="Referencia" data-obj="cajaReferenciaPro" size="13" value="" onkeydown="controlEventos(event)"></td>
			<td><input id="Codbarras" type="text" name="Codbarras" placeholder="Codbarras" data-obj= "cajaCodBarras" size="13" value="" data-objeto="cajaCodBarras" onkeydown="controlEventos(event)"></td>
			<td><input id="Descripcion" type="text" name="Descripcion" placeholder="Descripcion" data-obj="cajaDescripcion" size="20" value="" onkeydown="controlEventos(event)"></td>
		  </tr>
		</thead>
		<tbody>
			<?php 
			if (isset($productos)){
			foreach (array_reverse($productos) as $producto){
				$html=htmlLineaPedidoAlbaran($producto, "pedidos");
				echo $html['html'];
			}
			}
		?>
		</tbody>
	  </table>
	</div>
	<?php 

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
	 $('#id_proveedor').focus();
	 <?php
	if ($idProveedor>0){
		?>
		$('#id_proveedor').prop('disabled', true);
		$('#Proveedor').prop('disabled', true);
		$("#buscar").css("display", "none");
		$('#idArticulo').focus();
		<?php
	}else{
		?>
		$("#Row0").css("display", "none");
		<?php
	}
	if ($estado=="Facturado"){
		?>
		$("#tabla").find('input').attr("disabled", "disabled");
		$("#tabla").find('a').css("display", "none");
		$("#bGuardar").css("display", "none");
		$("#fecha").prop('disabled', true);
		<?php
	}
	?>
</script>
	</body>
</html>
