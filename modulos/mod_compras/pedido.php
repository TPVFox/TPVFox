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
	// Variables que utilizamos en pedidos.
	$titulo="Pedido De Proveedor";
	$fecha=date('Y-m-d');

	$idPedido=0;
	$numPedidoTemp=0;
	//$numPedido=0;

	$estado='Abierto';
	
	$idProveedor=0;
	$nombreProveedor="";

	//$total=0;

		
	if ($_GET){
		if (isset($_GET['id'])){
			$idPedido=$_GET['id'];
			$datosPedido=$Cpedido->DatosPedido($idPedido);
			if ($datosPedido['estado']=='Facturado'){
				$estado=$datosPedido['estado'];
			}else{
				$estado='Modificado';
			}
			$productosPedido=$Cpedido->ProductosPedidos($idPedido);
			$ivasPedido=$Cpedido->IvasPedidos($idPedido);
			$fecha=$datosPedido['FechaPedido'];
			$idProveedor=$datosPedido['idProveedor'];
			$datosProveedor=$Cprveedor->buscarProveedorId($idProveedor);
			$nombreProveedor=$datosProveedor['nombrecomercial'];
			$productosMod=modificarArrayProductos($productosPedido);
			$productos=json_decode(json_encode($productosMod));
			$Datostotales = recalculoTotalesAl($productos);
			$productos=json_decode(json_encode($productosMod), true);
			//$total=$Datostotales['total'];
			//$numPedido=$datosPedido['Numpedpro'];
		}else{
			$bandera=1;
			//$fecha=date('Y-m-d');
			if ($_GET['tActual']){
				$numPedidoTemp=$_GET['tActual'];
				$pedido=$Cpedido->DatosTemporal($numPedidoTemp);
				$estado=$pedido['estadoPedPro'];
				$idProveedor=$pedido['idProveedor'];
				if ($pedido['idPedpro']){				
					$idPedido=$pedido['idPedpro'];					
					//$datos=$Cpedido->DatosPedido($idPedido);
					//$numPedido=$datos['Numpedpro'];
				}
				if ($pedido['fechaInicio']){
					$bandera=new DateTime($pedido['fechaInicio']);
					$fecha=$bandera->format('Y-m-d');
				}
				//$pedido=$pedidoTemporal;
				$productos = json_decode( $pedido['Productos']); // Array de objetos
				if ($idProveedor){
					$datosProveedor=$Cprveedor->buscarProveedorId($idProveedor);
					$nombreProveedor=$datosProveedor['nombrecomercial'];
				}
				
			}
		}
	}
	// Añadimos al titulo el estado
	$titulo .= ': '.$estado;
	
if(isset($pedido['Productos'])){
	// Obtenemos los datos totales;
	// convertimos el objeto productos en array
	$Datostotales = recalculoTotalesAl($productos);
	$productos = json_decode(json_encode($productos), true); // Array de arrays	
}
//  ---------  Control y procesos para guardar el pedido. ------------------ //
if (isset($_POST['Guardar'])){
	// Objetivo :
	// Grabar el pedido.
	// Si el pedido tiene total 0 se permite guarda.
	// No se permite guardar si el pedido no tiene productos. ( lineas Activas ).
	echo '<pre>';
	print_r($_POST);
	echo '</pre>';
	
	
	if ($_POST['idTemporal']){
		$numPedidoTemp=$_POST['idTemporal'];
	}else{
		$numPedidoTemp=$_GET['tActual'];
	}
	$pedidoTemporal=$Cpedido->DatosTemporal($numPedidoTemp);
	if($pedidoTemporal['total']){
		$total=$pedidoTemporal['total'];
	}else{
		// Esto no debería permitir si no hay un total... 
		$total=0;
	}

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
		$datosPedidoReal=$Cpedido->DatosPedido($pedidoTemporal['idPedpro']);
		$numPedido=$datosPedidoReal['Numpedpro'];
	}else{
		$numPedido=0;
	}
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
	
	exit(); // De momento bloqueo redireccion ya que quiero ver que enviamos.
	header('Location: pedidosListado.php');
	
}
// ---------   FIN PROCESO Y CONTROL DE GUARDAR  ------------------  //

$parametros = simplexml_load_file('parametros.xml');
// -------------- Obtenemos de parametros cajas con sus acciones ---------------  //
$VarJS = $Controler->ObtenerCajasInputParametros($parametros);
?>
<script type="text/javascript">
// Objetos cajas de tpv
<?php echo $VarJS;?>
     function anular(e) {
          tecla = (document.all) ? e.keyCode : e.which;
          return (tecla != 13);
      }
	// Esta variable global la necesita para montar la lineas.
	// En configuracion podemos definir SI / NO
		
	var CONF_campoPeso="<?php echo $CONF_campoPeso; ?>";
	var cabecera = []; // Donde guardamos idCliente, idUsuario,idTienda,FechaInicio,FechaFinal.
		cabecera['idUsuario'] = <?php echo $Usuario['id'];?>; // Tuve que adelantar la carga, sino funcionaria js.
		cabecera['idTienda'] = <?php echo $Tienda['idTienda'];?>; 
		cabecera['estadoPedido'] ='<?php echo $estado ;?>'; // Si no hay datos GET es 'Abierto'
		cabecera['numPedidoTemp'] = <?php echo $numPedidoTemp ;?>;
		cabecera['idPedido'] = <?php echo $idPedido ;?>;
	//	cabecera['numPedido']=<?php echo $numPedido;?>;
		cabecera['idProveedor']=<?php echo $idProveedor ;?>;
		cabecera['fecha']='<?php echo $fecha;?>';
		 // Si no hay datos GET es 'Nuevo';
	var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array
	<?php 
	$i= 0;
	if (isset($productos)){
		if ($productos){
			foreach($productos as $product){
	?>
			datos=<?php echo json_encode($product); ?>;
			productos.push(datos);
	<?php //cambiamos estado y cantidad de producto creado si fuera necesario.
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
<script src="<?php echo $HostNombre; ?>/modulos/mod_compras/funciones.js"></script>
<script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>

</head>
<body>
	
<?php
	include '../../header.php';
?>

<div class="container">
	<h2 class="text-center"> <?php echo $titulo;?></h2>
	<form class="form-group" action="" method="post" name="formProducto" onkeypress="return anular(event)">
		<div class="col-md-12 btn-toolbar">
			<a  href="pedidosListado.php" onclick="ModificarEstadoPedido(pedido, Pedido);">Volver Atrás</a>
			<input type="submit" value="Guardar" name="Guardar" id="bGuardar">
			<?php
			if (isset($numPedidoTemp)){
			?>
				<input type="text" style="display:none;" name="idTemporal" value=<?php echo $numPedidoTemp;?>>
			<?php
			}
			?>
		</div>
	<div class="col-md-8">
			<div class="col-md-3">
				<label>Estado:</label>
				<input type="text" id="estado" name="estado" value="<?php echo $estado;?>" readonly>
			</div>
			<div class="col-md-4">
				<label>Usuario:</label>
				<input type="text" id="Usuario" name="Usuario" value="<?php echo $Usuario['nombre'];?>" size="13" readonly>
			</div>
			<div class="col-md-5">
				<label>Fecha Pedido:</label>
				<input type="date" name="fecha" id="fecha" data-obj= "cajaFecha"  value="<?php echo $fecha;?>" onkeydown="controlEventos(event)" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" placeholder='yyyy-mm-dd' title=" Formato de entrada yyyy-mm-dd">
			</div>
			
			
		<div class="col-md-12">
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
			<td><input id="idArticulo" type="text" name="idArticulo" placeholder="idArticulo" data-obj= "cajaidArticulo" size="6" value=""  onkeydown="controlEventos(event)"></td>
			<td><input id="Referencia" type="text" name="Referencia" placeholder="Referencia" data-obj="cajaReferencia" size="13" value="" onkeydown="controlEventos(event)"></td>
			<td><input id="ReferenciaPro" type="text" name="ReferenciaPro" placeholder="Referencia" data-obj="cajaReferenciaPro" size="13" value="" onkeydown="controlEventos(event)"></td>
			<td><input id="Codbarras" type="text" name="Codbarras" placeholder="Codbarras" data-obj= "cajaCodBarras" size="13" value="" data-objeto="cajaCodBarras" onkeydown="controlEventos(event)"></td>
			<td><input id="Descripcion" type="text" name="Descripcion" placeholder="Descripcion" data-obj="cajaDescripcion" size="25" value="" onkeydown="controlEventos(event)"></td>
		  </tr>
		</thead>
		<tbody>
			<?php 
			if (isset($productos)){
				foreach (array_reverse($productos) as $producto){
					$h=htmlLineaPedidoAlbaran($producto, "pedidos");
					echo $h['html'];
				}
			}
		?>
		</tbody>
	  </table>
	</div>
	<?php 

	// Ahora montamos base y ivas, esto debería ser una funcion, ya que lo utilizamos en imprimir tb.
	if (isset($Datostotales)){
		// Montamos ivas y bases
		$htmlIvas = '';
		foreach ($Datostotales['desglose'] as  $key => $basesYivas){
			$key = intval($key);
			$htmlIvas.='<tr id="line'.$key.'">';
			$htmlIvas.='<td id="tipo'.$key.'"> '.$key.'%</td>';
			$htmlIvas.='<td id="base'.$key.'"> '.$basesYivas['base'].'</td>';
			$htmlIvas.='<td id="iva'.$key.'">'.$basesYivas['iva'].'</td>';
			$htmlIvas.='</tr>';
		}
	}
	if (isset($DatosTotales)){
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
			<?php echo $htmlIvas; ?>
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
