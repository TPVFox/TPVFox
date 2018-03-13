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
	$CAlb=new AlbaranesCompras($BDTpv);
	include_once 'clases/pedidosCompras.php';
	$Cped = new PedidosCompras($BDTpv);
	$Controler = new ControladorComun; 
	$Tienda = $_SESSION['tiendaTpv'];
	$Usuario = $_SESSION['usuarioTpv'];// array con los datos de usuario
	$titulo="Albarán De Proveedor ";
	$estado='Abierto';
	$fecha=date('Y-m-d');
	$idAlbaranTemporal=0;
	$idAlbaran=0;
	$idProveedor=0;
	$suNumero=0;
	$nombreProveedor="";
	// Si recibe un id es que vamos a modificar un albarán que ya está creado 
	//Para ello tenbemos que buscar los datos del albarán para poder mostrarlos 
	if (isset($_GET['id'])){
		$idAlbaran=$_GET['id'];
		$datosAlbaran=$CAlb->datosAlbaran($idAlbaran);
		$productosAlbaran=$CAlb->ProductosAlbaran($idAlbaran);
		$ivasAlbaran=$CAlb->IvasAlbaran($idAlbaran);
		$pedidosAlbaran=$CAlb->PedidosAlbaranes($idAlbaran);
		$estado=$datosAlbaran['estado'];
		//$date=date_create($datosAlbaran['Fecha']);
		$fecha=date_format(date_create($datosAlbaran['Fecha']),'Y-m-d');
		$idAlbaranTemporal=0;
		//$numAlbaran=$datosAlbaran['Numalbpro'];
		$idProveedor=$datosAlbaran['idProveedor'];
		if ($datosAlbaran['Su_numero']>0){
			$suNumero=$datosAlbaran['Su_numero'];
		}else{
			$suNumero=0;
		}
		if ($idProveedor){
			$proveedor=$Cprveedor->buscarProveedorId($idProveedor);
			$nombreProveedor=$proveedor['nombrecomercial'];
		}
		//Modificamos el array de productos para que sea lo mismo que en facturas y pedidos de esta manera siempre podemos
		//Utilizar siempre las mismas funciones 
		$productosAlbaran=modificarArrayProductos($productosAlbaran);
		$productos=json_decode(json_encode($productosAlbaran));
		//Calciular el total con los productos que estn registrados
		$Datostotales = recalculoTotales($productos);
		$productos=json_decode(json_encode($productosAlbaran), true);
		if ($pedidosAlbaran){
			 $modificarPedido=modificarArrayPedidos($pedidosAlbaran, $BDTpv);
			 $pedidos=json_decode(json_encode($modificarPedido), true);
		}
	//	$total=$Datostotales['total'];
	}else{
	// Cuando recibe tArtual quiere decir que ya hay un albarán temporal registrado, lo que hacemos es que cada vez que seleccionamos uno 
	// o recargamos uno extraemos sus datos de la misma manera que el if de id
		if (isset($_GET['tActual'])){
				$idAlbaranTemporal=$_GET['tActual'];
				$datosAlbaran=$CAlb->buscarAlbaranTemporal($idAlbaranTemporal);
				if (isset ($datosAlbaran['numalbpro'])){
					$numAlbaran=$datosAlbaran['numalbpro'];
					$datosReal=$CAlb->buscarAlbaranNumero($numAlbaran);
					$idAlbaran=$datosReal['id'];
				}else{
					$idAlbaran=0;
				}
				if ($datosAlbaran['fechaInicio']=="0000-00-00 00:00:00"){
					$fecha=date('Y-m-d');
				}else{
					$fecha =date_format(date_create($datosAlbaran['fechaInicio']), 'Y-m-d');
				}
				if ($datosAlbaran['Su_numero']>0){
					$suNumero=$datosAlbaran['Su_numero'];
				}else{
					$suNumero=0;
				}
				$idProveedor=$datosAlbaran['idProveedor'];
				$proveedor=$Cprveedor->buscarProveedorId($idProveedor);
				$nombreProveedor=$proveedor['nombrecomercial'];
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
		//Guardar el albarán para ello buscamos los datos en el albarán temporal, los almacenamos todos en un array
		
	if (isset($_POST['Guardar'])){
		if ($_POST['idTemporal']){
				$idAlbaranTemporal=$_POST['idTemporal'];
			}else{
				$idAlbaranTemporal=$_GET['tActual'];
			}
		$datosAlbaran=$CAlb->buscarAlbaranTemporal($idAlbaranTemporal);
		if(['total']){
				$total=$datosAlbaran['total'];
		}else{
				$total=0;
		}
	
		if ($_POST['suNumero']>0){
				$suNumero=$_POST['suNumero'];
		}else{
			$suNumero=0;
		}
		if (isset ($_POST['fecha'])){
			$fecha=$_POST['fecha'];
		}else{
			$fecha=$datosAlbaran['fechaInicio'];
		}
		$datos=array(
			'Numtemp_albpro'=>$idAlbaranTemporal,
			'fecha'=>$fecha,
			'idTienda'=>$Tienda['idTienda'],
			'idUsuario'=>$Usuario['id'],
			'idProveedor'=>$datosAlbaran['idProveedor'],
			'estado'=>"Guardado",
			'total'=>$total,
			'DatosTotales'=>$Datostotales,
			'productos'=>$datosAlbaran['Productos'],
			'pedidos'=>$datosAlbaran['Pedidos'],
			'suNumero'=>$suNumero
		);
		
		//Si recibe número de albarán quiere decir que ya existe por esta razón tenemos que eliminar todos los datos del albarán
		//original para poder poner los nuevo, una vez que este todo guardado eliminamos el temporal.
		//Si no es así, es un albarán nuevo solo tenemos que crear un albarán definitivo y eliminar el temporal
		if ($datosAlbaran['numalbpro']){
				$numAlbaran=$datosAlbaran['numalbpro'];
				$datosReal=$CAlb->buscarAlbaranNumero($numAlbaran);
				$idAlbaran=$datosReal['id'];
				$eliminarTablasPrincipal=$CAlb->eliminarAlbaranTablas($idAlbaran);
				$addNuevo=$CAlb->AddAlbaranGuardado($datos, $numAlbaran, $idAlbaran);
				$eliminarTemporal=$CAlb->EliminarRegistroTemporal($idAlbaranTemporal, $idAlbaran);
		}else{
				$idAlbaran=0;
				$numAlbaran=0;
				$addNuevo=$CAlb->AddAlbaranGuardado($datos, $numAlbaran, $idAlbaran);
				$eliminarTemporal=$CAlb->EliminarRegistroTemporal($idAlbaranTemporal, $idAlbaran);
				
		}
		
		 header('Location: albaranesListado.php');
	}
	//Cancelar, cuando cancelamos un albarán quiere decir que los cambios que hemos echo no se efectúan para ello eliminamos el temporal que hemos creado
	// y cambiamos el estado del original a guardado
	if (isset ($_POST['Cancelar'])){
		if ($_POST['idTemporal']){
				$idTemporal=$_POST['idTemporal'];
		}else{
				$idTemporal=$_GET['tActual'];
		}
		$datosAlbaran=$CAlb->buscarAlbaranTemporal($idAlbaranTemporal);
		$pedidos=json_decode($pedidos['Pedidos'], true);
		foreach ($pedidos as $pedido){
			$mod=$Cped->modEstadoPedido($pedido['idPedido'], "Guardado");
		}
		$idAlbaran=0;
		$eliminarTemporal=$CAlb->EliminarRegistroTemporal($idTemporal, $idAlbaran);
		 header('Location: albaranesListado.php');
	}
	
	
	
		
		if (isset($albaran['Pedidos'])){
			$pedidos=json_decode(json_encode($pedidos), true);
			$style1="";
		}else{
			$style="display:none;";
		}
		if (isset($idProveedor)){
			$comprobarPedidos=comprobarPedidos($idProveedor, $BDTpv);
			if ($comprobarPedidos==1){
				$style="";
	
			}else{
				$style="display:none;";
			}
			
			echo $comprobarPedidos;
		}
		
		if($_GET['id'] >0 ||$_GET['tActual']>0){
			$estiloTablaProductos="";
		}else{
			$estiloTablaProductos="display:none;";
		}
	
	$titulo .= ': '.$estado;
		$parametros = simplexml_load_file('parametros.xml');
	
// -------------- Obtenemos de parametros cajas con sus acciones ---------------  //
//Como estamos el albaranes la caja de input num fila cambia el de donde a albaran
		
	foreach($parametros->cajas_input->caja_input as $caja){
		$caja->parametros->parametro[0]="albaran";
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
		cabecera['estado'] ='<?php echo $estado ;?>'; // Si no hay datos GET es 'Nuevo'
		cabecera['idTemporal'] = <?php echo $idAlbaranTemporal ;?>;
		cabecera['idReal'] = <?php echo $idAlbaran ;?>;
		cabecera['fecha'] = '<?php echo $fecha;?>';
		cabecera['idProveedor'] = <?php echo $idProveedor ;?>;
		cabecera['suNumero']=<?php echo $suNumero; ?>;
		
		
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
			if ($product['estado'] !== 'Activo'){
			?>	productos[<?php echo $i;?>].estado=<?php echo'"'.$product['estado'].'"';?>;
			<?php
			}
			$i++;
			}
	
		}
		if (is_array($pedidos)){
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
if ($idProveedor==0){
	$idProveedor="";
	
}
if ($suNumero==0){
	$suNumero="";
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
			
				<div class="col-md-2">
					<strong>Fecha albarán:</strong><br>
					<input type="date" name="fecha" id="fecha" size="10" data-obj= "cajaFecha"  value="<?php echo $fecha;?>" onkeydown="controlEventos(event)" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" placeholder='yyyy-mm-dd' title=" Formato de entrada yyyy-mm-dd">
				</div>
				<div class="col-md-2">
					<strong>Estado:</strong><br>
					<span id="EstadoTicket"> <input type="text" id="estado" name="estado" value="<?php echo $estado;?>" size="10" readonly></span><br>
				</div>
			
				<div class="col-md-2">
					<strong>Empleado:</strong><br>
					<input type="text" id="Usuario" name="Usuario" value="<?php echo $Usuario['nombre'];?>" size="10" readonly>
				</div>
				<div class="col-md-3">
					<strong>Su número:</strong><br>
					<input type="text" id="suNumero" name="suNumero" value="<?php echo $suNumero;?>" size="10" onkeydown="controlEventos(event)" data-obj= "CajaSuNumero">
				</div>
			
		</div>
		<div class="form-group">
			<label>Proveedor:</label>
			<input type="text" id="id_proveedor" name="id_proveedor" data-obj= "cajaIdProveedor" value="<?php echo $idProveedor;?>" size="2" onkeydown="controlEventos(event)" placeholder='id'>
			<input type="text" id="Proveedor" name="Proveedor" data-obj= "cajaProveedor" placeholder="Nombre del Proveedor" onkeydown="controlEventos(event)" value="<?php echo $nombreProveedor; ?>" size="60">
			<a id="buscar" class="glyphicon glyphicon-search buscar" onclick="buscarProveedor('albaran')"></a>
		</div>
	</div>
	<div class="col-md-4" >
	<div>
		<div>
			<div style="margin-top:-50px;">
			<label style="<?php echo $style;?>" id="numPedidoT">Número del pedido:</label>
			<input style="<?php echo $style;?>" type="text" id="numPedido" name="numPedido" value="" size="5" placeholder='Num' data-obj= "numPedido" onkeydown="controlEventos(event)">
			<a style="<?php echo $style;?>" id="buscarPedido" class="glyphicon glyphicon-search buscar" onclick="buscarAdjunto('albaran')"></a>
			<table  class="col-md-12" style="<?php echo $style1;?>" id="tablaPedidos"> 
				<thead>
				
				<td><b>Número</b></td>
				<td><b>Fecha</b></td>
				<td><b>Total</b></td>
				
				</thead>
				
				<?php 
				if (is_array($pedidos)){
					foreach ($pedidos as $pedido){
						$html=lineaAdjunto($pedido, "albaran");
					echo $html['html'];
					}
					
				}
				?>
			</table>
			</div>
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
			<th>Referencia Proveedor</th>
			<th>Cod Barras</th>
			<th>Descripcion</th>
			<th>Unid</th>
			<th>Coste</th>
			<th>Iva</th>
			<th>Importe</th>
			<th></th>
		  </tr>
		  <tr id="Row0" style=<?php echo $estiloTablaProductos;?>>  
			<td id="C0_Linea" ></td>
			
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
			//Recorremos los productos y vamos escribiendo las lineas.
			if (isset($productos)){
				foreach (array_reverse($productos) as $producto){
				$html=htmlLineaProducto($producto, "albaran");
				echo $html['html'];
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
// hacemos comprobaciones de estilos 
?>
<script type="text/javascript">
	$('#fecha').focus();
	<?php
	if ($idProveedor>0){
		?>
		$('#Proveedor').prop('disabled', true);
		$('#id_proveedor').prop('disabled', true);
		$("#buscar").css("display", "none");
		<?php
	}
	if (isset($datosAlbaran['estado'])){
		
	
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
		$("#suNumero").prop('disabled', true);
		$("#fecha").prop('disabled', true);
		<?php
	}
}
	?>
	
</script>
	</body>
</html>
