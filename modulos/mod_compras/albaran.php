<!DOCTYPE html>
<html>
<head>
<?php
	include_once './../../inicial.php';
	//Carga de archivos php necesarios
	include_once $URLCom.'/head.php';
	include_once $URLCom.'/modulos/mod_compras/funciones.php';
	include_once $URLCom.'/controllers/Controladores.php';
	include_once $URLCom.'/clases/Proveedores.php';
	include_once $URLCom.'/modulos/mod_compras/clases/albaranesCompras.php';
	include_once $URLCom.'/modulos/mod_compras/clases/pedidosCompras.php';
	include_once ($URLCom.'/controllers/parametros.php');
	//cargar las clases necesarias
	$ClasesParametros = new ClaseParametros('parametros.xml');
	$Cprveedor=new Proveedores($BDTpv);
	$CAlb=new AlbaranesCompras($BDTpv);
	$Cped = new PedidosCompras($BDTpv);
	$Controler = new ControladorComun; 
	$Controler->loadDbtpv($BDTpv);
	//Inicializar las variables
	$Tienda = $_SESSION['tiendaTpv'];
	$Usuario = $_SESSION['usuarioTpv'];
	$dedonde="albaran";
	$titulo="Albarán De Proveedor ";
	$estado='Abierto';
	
	$fecha=date('d-m-Y');
	$hora="";
	$idAlbaranTemporal=0;
	$idAlbaran=0;
	$idProveedor=0;
	$suNumero="";
	$nombreProveedor="";
	$formaPago=0;
	$fechaVencimiento="";
	$style1="";
	$Datostotales=array();
	$textoNum="";
	$inciden=0;
	
	//Cargamos la configuración por defecto y las acciones de las cajas 
	$parametros = $ClasesParametros->getRoot();	
	foreach($parametros->cajas_input->caja_input as $caja){
		$caja->parametros->parametro[0]="albaran";
	}
	$VarJS = $Controler->ObtenerCajasInputParametros($parametros);
	
	$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
	$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_compras',$Usuario['id']);
	$configuracionArchivo=array();
	foreach ($configuracion['incidencias'] as $config){
		if(get_object_vars($config)['dedonde']==$dedonde){
			array_push($configuracionArchivo, $config);
		}
	}
	
	
	// Si recibe un id es que vamos a modificar un albarán que ya está creado 
	//Para ello tenbemos que buscar los datos del albarán para poder mostrarlos 
	if (isset($_GET['id'])){
		$datosAlbaran=DatosIdAlbaran($_GET['id'], $CAlb, $Cprveedor, $BDTpv );
		if (isset($datosAlbaran['error'])){
			$errores=$datosAlbaran['error'];
		}else{
			$idAlbaran=$datosAlbaran['idAlbaran'];
			$estado=$datosAlbaran['estado'];
			$fecha =date_format(date_create($datosAlbaran['fecha']), 'd-m-Y');
			$formaPago=$datosAlbaran['formaPago'];
			$textoFormaPago=htmlFormasVenci($formaPago, $BDTpv); // Generamos ya html.
			$fechaVencimiento=$datosAlbaran['fechaVencimiento'];
			$idProveedor=$datosAlbaran['idProveedor'];
			$suNumero=$datosAlbaran['suNumero'];
			$nombreProveedor=$datosAlbaran['nombreProveedor'];
			$productos=$datosAlbaran['productos'];
			$Datostotales=$datosAlbaran['DatosTotales'];
			$pedidos=$datosAlbaran['pedidos'];
			$textoNum=$idAlbaran;
			$hora=$datosAlbaran['hora'];
			
			if($estado=="Facturado"){
				$numFactura=$CAlb->NumfacturaDeAlbaran($idAlbaran);
				if(isset($numFactura['error'])){
					$errores[0]=array ( 'tipo'=>'Danger!',
								 'dato' => $numFactura['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
				}
			
			}
		}
		$incidenciasAdjuntas=incidenciasAdjuntas($idAlbaran, "mod_compras", $BDTpv, "albaran");
		$inciden=count($incidenciasAdjuntas['datos']);
		
	}else{
	// Cuando recibe tArtual quiere decir que ya hay un albarán temporal registrado, lo que hacemos es que cada vez que seleccionamos uno 
	// o recargamos uno extraemos sus datos de la misma manera que el if de id
		if (isset($_GET['tActual'])){
				$idAlbaranTemporal=$_GET['tActual'];
				$datosAlbaran=$CAlb->buscarAlbaranTemporal($idAlbaranTemporal);
				if (isset($datosAlbaran['error'])){
						$errores[0]=array ( 'tipo'=>'Danger!',
								 'dato' => $datosAlbaran['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
				}else{
				if (isset ($datosAlbaran['numalbpro'])){
					$numAlbaran=$datosAlbaran['numalbpro'];
					$datosReal=$CAlb->buscarAlbaranNumero($numAlbaran);
					$idAlbaran=$datosReal['id'];
					$textoNum=$idAlbaran;
				}else{
					$idAlbaran=0;
				}
				if ($datosAlbaran['fechaInicio']=="0000-00-00 00:00:00"){
					$fecha=date('d-m-Y');
				}else{
					$fecha =date_format(date_create($datosAlbaran['fechaInicio']), 'd-m-Y');
					$hora=date_format(date_create($datosAlbaran['fechaInicio']),'H:i');
				}
				if ($datosAlbaran['Su_numero']!==""){
					$suNumero=$datosAlbaran['Su_numero'];
				}
				$idProveedor=$datosAlbaran['idProveedor'];
				$proveedor=$Cprveedor->buscarProveedorId($idProveedor);
				$nombreProveedor=$proveedor['nombrecomercial'];
				$albaran=$datosAlbaran;
				$productos =json_decode($datosAlbaran['Productos']);
				$pedidos=json_decode($datosAlbaran['Pedidos']);
			}
		}
		
	}
	
	if(isset($albaran['Productos'])){
			// Obtenemos los datos totales ;
			// convertimos el objeto productos en array
			$Datostotales = recalculoTotales($productos);
			$productos = json_decode(json_encode($productos), true); // Array de arrays	
	}
		//Guardar el albarán para ello buscamos los datos en el albarán temporal, los almacenamos todos en un array
		
	if (isset($_POST['Guardar'])){
		//@Objetivo: enviar los datos principales a la funcion guardarAlabaran
		//si el resultado es  quiere decir que no hay errores y fue todo correcto
		//si no es así muestra mensaje de error
		 $guardar=guardarAlbaran($_POST, $_GET, $BDTpv, $Datostotales);
		if (count($guardar)==0){
			header('Location: albaranesListado.php');
		}else{
			foreach ($guardar as $error){
				echo '<div class="'.$error['class'].'">'
				. '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br> '.$error['dato']
				. '</div>';
			}
		}
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
		}
		if (isset ($_GET['id']) || isset ($_GET['tActual'])){
			$estiloTablaProductos="";
		}else{
			$estiloTablaProductos="display:none;";
		}
	
		
        if(isset($numFactura)){
				$titulo .= $textoNum.' : '.$estado." ".$idAlbaran;
        }else{
            $titulo .= $textoNum.' : '.$estado;
        }
		
?>
	<script type="text/javascript">
	// Esta variable global la necesita para montar la lineas.
	// En configuracion podemos definir SI / NO
	<?php echo 'var configuracion='.json_encode($configuracionArchivo).';';?>	
	var cabecera = []; // Donde guardamos idCliente, idUsuario,idTienda,FechaInicio,FechaFinal.
		cabecera['idUsuario'] = <?php echo $Usuario['id'];?>; // Tuve que adelantar la carga, sino funcionaria js.
		cabecera['idTienda'] = <?php echo $Tienda['idTienda'];?>; 
		cabecera['estado'] ='<?php echo $estado ;?>'; // Si no hay datos GET es 'Nuevo'
		cabecera['idTemporal'] = <?php echo $idAlbaranTemporal ;?>;
		cabecera['idReal'] = <?php echo $idAlbaran ;?>;
		cabecera['fecha'] = '<?php echo $fecha;?>';
		cabecera['hora'] = '<?php echo $hora;?>';
		cabecera['idProveedor'] = <?php echo $idProveedor ;?>;
		cabecera['suNumero']='<?php echo $suNumero; ?>';
		 // Si no hay datos GET es 'Nuevo';
	var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array
	var pedidos =[];
<?php 
	if (isset($albaranTemporal)|| isset($idAlbaran)){ 
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
		if (isset ($pedidos)){
			if (is_array($pedidos)){
				foreach ($pedidos as $pedi){
					?>
					datos=<?php echo json_encode($pedi);?>;
					pedidos.push(datos);
					<?php
				}
			}
		}
	}	
	
?>
</script>
</head>
<body>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_compras/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
    <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
<?php
	  //~ include $URLCom.'/header.php';
       include_once $URLCom.'/modulos/mod_menu/menu.php';
?>
<script type="text/javascript">
	<?php
	 if (isset($_POST['Cancelar'])){
		  ?>
		 mensajeCancelar(<?php echo $idAlbaranTemporal;?>, <?php echo "'".$dedonde."'"; ?>);
		
		 
		  <?php
	  }
	  ?>
<?php echo $VarJS;?>
     function anular(e) {
          tecla = (document.all) ? e.keyCode : e.which;
          return (tecla != 13);
      }
</script>

<div class="container">
	<?php
	
	if (isset($errores)){
		foreach($errores as $error){
				echo '<div class="'.$error['class'].'">'
				. '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br>Sentencia: '.$error['dato']
				. '</div>';
		}
	}
	if($idProveedor==0){
			$idProveedor="";
	}
	if($idAlbaran>0){
		?>
	<input class="btn btn-warning" size="12" onclick="abrirModalIndicencia('<?php echo $dedonde;?>' , configuracion, 0,<?php echo $idAlbaran ;?>);" value="Añadir incidencia " name="addIncidencia" id="addIncidencia">
		<?php
	}
	if($inciden>0){
		?>
		<input class="btn btn-info" size="15" onclick="abrirIncidenciasAdjuntas(<?php echo $idAlbaran;?>, 'mod_compras', 'albaran')" value="Incidencias Adjuntas " name="incidenciasAdj" id="incidenciasAdj">
		<?php
	}
	?>
	<?php 
			
			?>
			<h2 class="text-center"> <?php echo $titulo;?></h2>
			
			<form action="" method="post" name="formProducto" onkeypress="return anular(event)">
			<div class="col-md-12">
				<div class="col-md-8" >
						<a  href="./albaranesListado.php">Volver Atrás</a>
						<input class="btn btn-primary" type="submit" value="Guardar" name="Guardar" id="bGuardar">
				</div>
				<div class="col-md-4 " >
						<input type="submit" class="pull-right btn btn-danger" value="Cancelar" name="Cancelar" id="bCancelar">
						<?php
					if ($idAlbaranTemporal>0){
						?>
						<input type="text" style="display:none;" name="idTemporal" value="<?php echo $idAlbaranTemporal;?>">
						<?php
					}
						?>
					</div>
				</div>
<div class="row" >
	<div class="col-md-8">
		<div class="col-md-12">
				<div class="col-md-3">
					<strong>Fecha albarán:</strong><br>
					<input type="text" name="fecha" id="fecha" size="10" data-obj= "cajaFecha"  value="<?php echo $fecha;?>" onkeydown="controlEventos(event)" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" placeholder='dd-mm-yyyy' title=" Formato de entrada dd-mm-yyyy">
					
				</div>
				<div class="col-md-3">
					<strong>Hora de entrega:</strong><br>
					<input type="time" id="hora" value="<?php echo $hora;?>"  data-obj= "cajaHora" onkeydown="controlEventos(event)"  name="hora" size="5" max="24:00" min="00:00" pattern="[0-2]{1}[0-9]{1}:[0-5]{1}[0-9]{1}" placeholder='HH:MM' title=" Formato de entrada HH:MM">
					
				</div>
				<div class="col-md-3">
					<strong>Estado:</strong><br>
					<span id="EstadoTicket"> <input type="text" id="estado" name="estado" value="<?php echo $estado;?>" size="10" readonly></span><br>
				</div>
			
				<div class="col-md-3">
					<strong>Empleado:</strong><br>
					<input type="text" id="Usuario" name="Usuario" value="<?php echo $Usuario['nombre'];?>" size="10" readonly>
				</div>
		</div>
		<div class="col-md-12">
			<div class="col-md-3">
				<strong>Su número:</strong><br>
				<input type="text" id="suNumero" name="suNumero" value="<?php echo $suNumero;?>" size="10" onkeydown="controlEventos(event)" data-obj= "CajaSuNumero">
			</div>
			<div class="col-md-3">
				<strong>Forma de pago:</strong><br>
				<p id="formaspago">
					<select name='formaVenci' id='formaVenci'>
				<?php 
				
				if(isset ($textoFormaPago)){
						echo $textoFormaPago['html'];
				}
				?>
				</select>
				</p>
			</div>
			<div class="col-md-3">
					<strong>Fecha vencimiento:</strong><br>
					<input type="date" name="fechaVenci" id="fechaVenci" size="10"  pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="<?php echo $fechaVencimiento;?>"placeholder='yyyy-mm-dd' title=" Formato de entrada yyyy-mm-dd">
			</div>
			<div class="col-md-3">
					<strong>Escoger casilla de salto:</strong><br>
					<select id="salto" name="salto">
						<option value="0">Seleccionar</option>
						<option value="1">Id Articulo</option>
						<option value="2">Referencia</option>
						<option value="3">Referencia Proveedor</option>
						<option value="4">Cod Barras</option>
						<option value="5">Descripción</option>
					</select>
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
			<div style="margin-top:-20x;">
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
				if (isset($pedidos)){
					if (is_array($pedidos)){
						foreach ($pedidos as $pedido){
							$html=lineaAdjunto($pedido, "albaran");
							echo $html['html'];
						}
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
			<td><input id="idArticulo" type="text" name="idArticulo" placeholder="idArticulo" data-obj= "cajaidArticulo" size="4" value=""  onkeydown="controlEventos(event)"></td>
			<td><input id="Referencia" type="text" name="Referencia" placeholder="Referencia" data-obj="cajaReferencia" size="8" value="" onkeydown="controlEventos(event)"></td>
			<td><input id="ReferenciaPro" type="text" name="ReferenciaPro" placeholder="Referencia" data-obj="cajaReferenciaPro" size="10" value="" onkeydown="controlEventos(event)"></td>
			<td><input id="Codbarras" type="text" name="Codbarras" placeholder="Codbarras" data-obj= "cajaCodBarras" size="12" value="" data-objeto="cajaCodBarras" onkeydown="controlEventos(event)"></td>
			<td><input id="Descripcion" type="text" name="Descripcion" placeholder="Descripcion" data-obj="cajaDescripcion" size="17" value="" onkeydown="controlEventos(event)"></td>
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
			if (isset ($Datostotales)){
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

				<?php echo (isset($Datostotales['total']) ? number_format ($Datostotales['total'],2, '.', '') : '');?>

			</div>
		</div>
	</div>
</form>
</div>
<?php // Incluimos paginas modales
echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
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
