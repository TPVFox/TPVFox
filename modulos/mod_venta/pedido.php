<!DOCTYPE html>
<html>
<head>
<?php
    include_once './../../inicial.php';
    include $URLCom.'/head.php';
    include_once $URLCom.'/modulos/mod_venta/funciones.php';
	include_once $URLCom.'/controllers/Controladores.php';
	include_once $URLCom.'/modulos/mod_venta/clases/pedidosVentas.php';
	include_once $URLCom.'/clases/cliente.php';
    include_once $URLCom.'/controllers/parametros.php';
	$ClasesParametros = new ClaseParametros('parametros.xml');
	
	$Cpedido=new PedidosVentas($BDTpv);
	$Ccliente=new Cliente($BDTpv);
	$Controler = new ControladorComun; 
	$Controler->loadDbtpv($BDTpv);
	$dedonde="pedidos";
	$Tienda = $_SESSION['tiendaTpv'];
	$Usuario = $_SESSION['usuarioTpv'];// array con los datos de usuario
	$titulo="Pedido De Cliente ";
	$estado='Abierto';
	$bandera=0;
	$fecha=date('d-m-Y');
	$idTemporal = 0;
	$idPedido=0;
	$total=0;
	$idCliente=0;
	$errores=array();
	$textoNum="";
	$parametros = $ClasesParametros->getRoot();
	$VarJS = $Controler->ObtenerCajasInputParametros($parametros);
	$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
	$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_ventas',$Usuario['id']);
	$configuracionArchivo=array();
		foreach ($configuracion['incidencias'] as $config){
		
		if(get_object_vars($config)['dedonde']==$dedonde){
			array_push($configuracionArchivo, $config);
		}
	}
	
	if (isset($_GET['id'])){//Cuanod recibe el id de uno de los pedidos ya creados 
		$idPedido=$_GET['id'];
		$textoNum=$idPedido;
		$datosPedido=$Cpedido->datosPedidos($idPedido);//Buscar los datos de pedido 
		if (isset($datosPedido['error'])){
		$errores[0]=array ( 'tipo'=>'Danger!',
								 'dato' => $datosPedido['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
		}
		$estado=$datosPedido['estado'];
		$productosPedido=$Cpedido->ProductosPedidos($idPedido);//Buscamos los productos de ese pedido en su respectiva tabla
		if (isset($productosPedido['error'])){
		$errores[1]=array ( 'tipo'=>'Danger!',
								 'dato' => $productosPedido['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
		}
		$ivasPedido=$Cpedido->IvasPedidos($idPedido);//Buscamos los datos del iva 
		if (isset($ivasPedido['error'])){
		$errores[2]=array ( 'tipo'=>'Danger!',
								 'dato' => $ivasPedido['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
		}
		$fecha =date_format(date_create($datosPedido['FechaPedido']), 'd-m-Y');
		$idCliente=$datosPedido['idCliente'];
		if ($idCliente){
				// Si se cubrió el campo de idcliente llama a la función dentro de la clase cliente 
				$datosCliente=$Ccliente->DatosClientePorId($idCliente);
				$nombreCliente=$datosCliente['Nombre'];
		}
		$productosMod=modificarArrayProductos($productosPedido);//MOdificar el array de productos según lo que necesitamos
		$productos=json_decode(json_encode($productosMod));
		$Datostotales = recalculoTotales($productos);
		$productos=json_decode(json_encode($productos), true);
		$total=$Datostotales['total'];
		$incidenciasAdjuntas=incidenciasAdjuntas($idPedido, "mod_ventas", $BDTpv, "pedidos");
		$inciden=count($incidenciasAdjuntas['datos']);
	}else{
		
			if (isset($_GET['tActual'])){//Si recibe un id de un temporal 
			$idTemporal=$_GET['tActual'];
			$pedidoTemporal= $Cpedido->BuscarIdTemporal($idTemporal);//Buscamos los datos del temporal
			if (isset($pedidoTemporal['error'])){
			$errores[3]=array ( 'tipo'=>'Danger!',
								 'dato' => $pedidoTemporal['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
			}
			$estado=$pedidoTemporal['estadoPedCli'];
			$idCliente=$pedidoTemporal['idClientes'];
			if (isset($pedidoTemporal['idPedcli'])){
				$idPedido=$pedidoTemporal['idPedcli'];
				$textoNum=$idPedido;
			}else{
				$idPedido=0;
			}
			$pedido=$pedidoTemporal;
			$productos = json_decode( $pedidoTemporal['Productos']); // Array de objetos
			if (isset($idCliente)){
				// Si se cubrió el campo de idcliente llama a la función dentro de la clase cliente 
				$datosCliente=$Ccliente->DatosClientePorId($idCliente);
				$nombreCliente=$datosCliente['Nombre'];
			}
		}
		
	}

$titulo .= ' '.$textoNum.': '.$estado;

		if(isset($pedido['Productos'])){
			// Obtenemos los datos totales ( fin de ticket);
			// convertimos el objeto productos en array
			$Datostotales = recalculoTotales($productos);
			$productos = json_decode(json_encode($productos), true); // Array de arrays	
		}
		//Pasar un pedido temporal a real
		if (isset($_POST['Guardar'])){
			if (isset($_GET['id'])){
				$fecha =date_format(date_create($_POST['fecha']), 'Y-m-d');
				 $modFecha=$Cpedido->modificarFecha($_GET['id'],$fecha);
				if(isset($modFecha['error'])){
					echo '<div class="alert alert-danger">'
						. '<strong>Danger! </strong> Error en la base de datos <br>Sentencia: '.$modFecha['consulta']
						. '</div>';
					
				}else{
					header('Location: pedidosListado.php');
				}
			}else{
				if (isset($_POST['idTemporal'])){
					$idTemporal=$_POST['idTemporal'];
				}else{
					$idTemporal=$_GET['tActual'];
				}
				$pedidoTemporal= $Cpedido->BuscarIdTemporal($idTemporal);
				if (isset($pedidoTemporal['error'])){
				$errores[3]=array ( 'tipo'=>'Danger!',
									 'dato' => $pedidoTemporal['consulta'],
									 'class'=>'alert alert-danger',
									 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
									 );
				}else{
					if(isset($pedidoTemporal['total'])){
						$total=$pedidoTemporal['total'];
					}else{
						$total=0;
					}
					$idPedido=0;
					$fechaCreacion=date("Y-m-d H:i:s");
					$fecha=date_format(date_create($_POST['fecha']), 'Y-m-d');
					$datosPedido=array(
					'NPedidoTemporal'=>$idTemporal,
					'fecha'=>$fecha,
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
					if (isset($pedidoTemporal['idPedcli'])){
						$idPedido=$pedidoTemporal['idPedcli'];
					}
					
					if ($idPedido>0){
						$eliminarTablasPrincipal=$Cpedido->eliminarPedidoTablas($idPedido);
						if (isset($eliminarTablasPrincipal['error'])){
						$errores[3]=array ( 'tipo'=>'Danger!',
										 'dato' => $eliminarTablasPrincipal['consulta'],
										 'class'=>'alert alert-danger',
										 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
										 );
						}
					}
						if(count($errores)==0){
							$addNuevo=$Cpedido->AddPedidoGuardado($datosPedido, $idPedido);
							if(isset($addNuevo['error'])){
								$errores[4]=array ( 'tipo'=>'Danger!',
										 'dato' => $addNuevo['consulta'],
										 'class'=>'alert alert-danger',
										 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
										 );
							}
						
							$eliminarTemporal=$Cpedido->EliminarRegistroTemporal($idTemporal, $idPedido);
							if(isset($eliminarTemporal['error'])){
								$errores[5]=array ( 'tipo'=>'Danger!',
										 'dato' => $eliminarTemporal['consulta'],
										 'class'=>'alert alert-danger',
										 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
										 );
							}
						}
						if(count($errores)==0){
							 header('Location: pedidosListado.php');
						}else{
							foreach ($errores as $error){
								echo '<div class="'.$error['class'].'">'
								. '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br> '.$error['dato']
								. '</div>';
							}
						}
				}
			}
		}
		if (isset($datosPedido)){
			if($estado=="Facturado"){
				$style="display:none;";
				$disabled = 'disabled';
			}else if (isset ($pedido)| $estado=="Guardado"){
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
	//~ include '../../header.php';
     include_once $URLCom.'/modulos/mod_menu/menu.php';
	if (isset($errores)){
		foreach($errores as $error){
				echo '<div class="'.$error['class'].'">'
				. '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br>Sentencia: '.$error['dato']
				. '</div>';
		}
}
?>
<script type="text/javascript">
			<?php
	 if (isset($_POST['Cancelar'])){
		  ?>
		 mensajeCancelar(<?php echo $idTemporal;?>, <?php echo "'".$dedonde."'"; ?>);
		
		 
		  <?php
	  }
	  ?>
<?php echo $VarJS;?>
     function anular(e) {
          tecla = (document.all) ? e.keyCode : e.which;
          return (tecla != 13);
      }
</script>
<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
<script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
<div class="container">
	<?php 
	if($idPedido>0){
		?>
		<input class="btn btn-warning" size="12" onclick="abrirModalIndicencia('<?php echo $dedonde;?>' , configuracion, 0,<?php echo $idPedido ;?>);" value="Añadir incidencia " name="addIncidencia" id="addIncidencia">

		<?php
	}
		if($inciden>0){
		?>
		<input class="btn btn-info" size="15" onclick="abrirIncidenciasAdjuntas(<?php echo $idPedido;?>, 'mod_ventas', 'pedidos')" value="Incidencias Adjuntas " name="incidenciasAdj" id="incidenciasAdj">
		<?php
	}
	?>

			<h2 class="text-center"> <?php echo $titulo;?></h2>
			<form action="" method="post" name="formProducto" onkeypress="return anular(event)">
			<div class="col-md-12" >
			
				<div class="col-md-8" >
			
				<a  href="pedidosListado.php" onclick="ModificarEstadoPedido(pedido, Pedido);">Volver Atrás</a>
					<?php 
						if($estado<>"Facturado"){
					?>
						<input type="submit" value="Guardar" class="btn btn-primary" name="Guardar">
						</div>
				<div class="col-md-4 " >
						<input class="pull-right btn btn-danger" type="submit" value="Cancelar" name="Cancelar" id="Cancelar">
					<?php
					}
					?>
				</div>
			</div>
					<?php
					
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
					<input type="text" name="fecha" id="fecha" data-obj= "cajaFecha"  value="<?php echo $fecha;?>" onkeydown="controlEventos(event)" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" placeholder='dd-mm-yyyy' title=" Formato de entrada dd-mm-yyyy" <?php echo $disabled;?>>
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
		<div class="col-md-12">
		<div class="col-md-4">
					<strong>Escoger casilla de salto:</strong><br>
					<select id="salto" name="salto">
						<option value="0">Seleccionar</option>
						<option value="1">Id Articulo</option>
						<option value="2">Referencia</option>
						<option value="3">Cod Barras</option>
						<option value="4">Descripción</option>
					</select>
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
			<th>S/iva</th>
			<th>Iva</th>
			<th>Importe</th>
			<th></th>
		  </tr>
		  <tr id="Row0" style=<?php echo $style;?>>  
			<td id="C0_Linea" ></td>
			<td><input id="idArticulo" type="text" name="idArticulo" placeholder="idArticulo" data-obj= "cajaidArticulo" size="6" value=""  onkeydown="controlEventos(event)"></td>
			<td><input id="Referencia" type="text" name="Referencia" placeholder="Referencia" data-obj="cajaReferencia" size="13" value="" onkeydown="controlEventos(event)"></td>
			<td><input id="Codbarras" type="text" name="Codbarras" placeholder="Codbarras" data-obj= "cajaCodBarras" size="12" value="" data-objeto="cajaCodBarras" onkeydown="controlEventos(event)"></td>
			<td><input id="Descripcion" type="text" name="Descripcion" placeholder="Descripcion" data-obj="cajaDescripcion" size="17" value="" onkeydown="controlEventos(event)"></td>
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
			<?php 
			if (isset($Datostotales)){
			$htmlIvas=htmlTotales($Datostotales);
			echo $htmlIvas['html'];
			} ?>
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
