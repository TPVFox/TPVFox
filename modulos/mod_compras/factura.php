<!DOCTYPE html>
<html>
<head>
<?php
	//llamadas  a archivos php 
	include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	include_once ($RutaServidor.$HostNombre.'/controllers/parametros.php');
	include '../../clases/Proveedores.php';
	include 'clases/albaranesCompras.php';
	include_once 'clases/facturasCompras.php';
	include_once '../../clases/FormasPago.php';
	//Carga de clases necesarias
	$ClasesParametros = new ClaseParametros('parametros.xml');
	$Cprveedor=new Proveedores($BDTpv);
	$CAlb=new AlbaranesCompras($BDTpv);
	$CFac = new FacturasCompras($BDTpv);
	$Controler = new ControladorComun; 
	$Controler->loadDbtpv($BDTpv);
	$CforPago=new FormasPago($BDTpv);
	//iniciación de las variables
	$dedonde="factura";
	$Tienda = $_SESSION['tiendaTpv'];
	$Usuario = $_SESSION['usuarioTpv'];// array con los datos de usuario
	$titulo="Factura De Proveedor";
	$estado='Abierto';
	$estadoCab="'".'Abierto'."'";
	$formaPago=0;
	$comprobarAlbaran=0;
	$importesFactura=array();
	$albaranes=array();
	$textoNum="";
	$fecha=date('d-m-Y');
	$fechaImporte=date('Y-d-m');
	//Carga de los parametros de configuración y las acciones de las cajas
	$parametros = $ClasesParametros->getRoot();		
	foreach($parametros->cajas_input->caja_input as $caja){
		$caja->parametros->parametro[0]="factura";
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
	
	
	
	//Si recibe un id de una factura que ya está creada cargamos sus datos para posibles modificaciones 
	if (isset($_GET['id'])){
		$idFactura=$_GET['id'];
		$textoNum=$idFactura;
		//~ $titulo="Modificar factura De Proveedor";
		$datosFactura=$CFac->datosFactura($idFactura);
		$productosFactura=$CFac->ProductosFactura($idFactura);
		$ivasFactura=$CFac->IvasFactura($idFactura);
		$abaranesFactura=$CFac->albaranesFactura($idFactura);
		$textoFormaPago=htmlFormasVenci($formaPago, $BDTpv);
		$datosImportes=$CFac->importesFactura($idFactura);
		$estado=$datosFactura['estado'];
		$estadoCab="'".$datosFactura['estado']."'";
		$date=date_create($datosFactura['Fecha']);
		//~ $fecha=date_format($date,'Y-m-d');
		$fecha=date_format($date,'d-m-Y');
		$fechaCab="'".$fecha."'";
		$idFacturaTemporal=0;
		$numFactura=$datosFactura['Numfacpro'];
		$idProveedor=$datosFactura['idProveedor'];
		if (isset($datosFactura['Su_num_factura'])){
			$suNumero=$datosFactura['Su_num_factura'];
		}else{
			$suNumero="";
		}
		if ($idProveedor){
			$proveedor=$Cprveedor->buscarProveedorId($idProveedor);
			$nombreProveedor=$proveedor['nombrecomercial'];
		}
		$productosFactura=modificarArrayProductos($productosFactura);
		$productos=json_decode(json_encode($productosFactura));
		$Datostotales = recalculoTotales($productos);
		$productos=json_decode(json_encode($productosFactura), true);
			
		if ($abaranesFactura){
			 $modificarAlbaran=modificarArrayAdjunto($abaranesFactura, $BDTpv, "factura");
			 $albaranes=json_decode(json_encode($modificarAlbaran), true);
		}
			//~ echo '<pre>';
		//~ print_r($datosImportes);
		//~ echo '</pre>';
		$total=$Datostotales['total'];
		$importesFactura=modificarArraysImportes($datosImportes, $total);
	//~ echo $total;
		$comprobarAlbaran=comprobarAlbaran($idProveedor, $BDTpv);
	}else{
		$fecha=date('d-m-Y');
		$fechaCab="'".$fecha."'";
		$idFacturaTemporal=0;
		$idFactura=0;
		$numFactura=0;
		$idProveedor=0;
		$suNumero="";
		$nombreProveedor="";
	//Si recibe los datos de un temporal
		if (isset($_GET['tActual'])){
				$idFacturaTemporal=$_GET['tActual'];
				$datosFactura=$CFac->buscarFacturaTemporal($idFacturaTemporal);
				if (isset ($datosFactura['numfacpro'])){
					$numFactura=$datosFactura['numfacpro'];
					$datosReal=$CFac->buscarFacturaNumero($numFactura);
					$idFactura=$datosReal['id'];
					$textoNum=$idFactura;
				}else{
					$numFactura=0;
					$idFactura=0;
				}
				if ($datosFactura['fechaInicio']=="0000-00-00 00:00:00"){
					//~ $fecha=date('Y-m-d');
					$fecha=date('d-m-Y');
				}else{
					$fecha1=date_create($datosFactura['fechaInicio']);
					//~ $fecha =date_format($fecha1, 'Y-m-d');
					$fecha =date_format($fecha1, 'd-m-Y');
				}
				if (isset($datosFactura['Su_numero'])){
					$suNumero=$datosFactura['Su_numero'];
				}else{
					$suNumero="";
				}
				$textoFormaPago=htmlFormasVenci($formaPago, $BDTpv);
				$idProveedor=$datosFactura['idProveedor'];
				$proveedor=$Cprveedor->buscarProveedorId($idProveedor);
				$nombreProveedor=$proveedor['nombrecomercial'];
				$fechaCab="'".$fecha."'";
				$importesFactura=json_decode($datosFactura['FacCobros'], true);
				
				$estadoCab="'".'Abierto'."'";
				$factura=$datosFactura;
				$productos =  json_decode($datosFactura['Productos']) ;
				
			
				$albaranes=json_decode($datosFactura['Albaranes']);
				
		}
		
	}
	if(isset($factura['Productos'])){
			// Obtenemos los datos totales ( fin de ticket);
			// convertimos el objeto productos en array
			$Datostotales = recalculoTotales($productos);
			$productos = json_decode(json_encode($productos), true); // Array de arrays
		}
		
	if (isset($_POST['Guardar'])){
			$guardar=guardarFactura($_POST, $_GET, $BDTpv, $Datostotales, $importesFactura);
			if (count($guardar)==0){
				header('Location: facturasListado.php');
			}else{
				foreach ($guardar as $error){
					echo '<div class="'.$error['class'].'">'
					. '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br> '.$error['dato']
					. '</div>';
				}
			}
	}
		if (isset($factura['Albaranes'])){
			$albaranes=json_decode(json_encode($albaranes), true);
		}
		if (isset($albaranes) || $comprobarAlbaran==1){
			$style="";
		}else{
			$style="display:none;";
		}
	
		if(isset($_GET['id']) || isset($_GET['tActual'])){
			$estiloTablaProductos="";
		}else{
			$estiloTablaProductos="display:none;";
		}
		$titulo .= ' '.$textoNum.': '.$estado;
?>
	<script type="text/javascript">
	// Esta variable global la necesita para montar la lineas.
	// En configuracion podemos definir SI / NO
	<?php echo 'var configuracion='.json_encode($configuracionArchivo).';';?>	
	var cabecera = []; // Donde guardamos idCliente, idUsuario,idTienda,FechaInicio,FechaFinal.
		cabecera['idUsuario'] = <?php echo $Usuario['id'];?>; // Tuve que adelantar la carga, sino funcionaria js.
		cabecera['idTienda'] = <?php echo $Tienda['idTienda'];?>; 
		cabecera['estado'] =<?php echo $estadoCab ;?>; // Si no hay datos GET es 'Nuevo'
		cabecera['idTemporal'] = <?php echo $idFacturaTemporal ;?>;
		cabecera['idReal'] = <?php echo $idFactura ;?>;
		cabecera['fecha'] = <?php echo $fechaCab ;?>;
		cabecera['idProveedor'] = <?php echo $idProveedor ;?>;
		cabecera['suNumero']='<?php echo $suNumero; ?>';
		
		
		 // Si no hay datos GET es 'Nuevo';
	var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array
	var albaranes =[];
<?php 
	if (isset($facturaTemporal)| isset($idFactura)){ 
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
		$i= 0;
		if (isset($albaranes)){
			foreach ($albaranes as $alb){
				?>
				datos=<?php echo json_encode($alb);?>;
				albaranes.push(datos);
				albaranes[<?php echo $i;?>],estado="activo";
				<?php
				$i++;
			}
		}
	}	
	
	
?>
</script>
<?php 
if ($idProveedor==0){
	$idProveedor="";
	
}
?>
</head>
<body>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_compras/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
    <script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
<?php
	include '../../header.php';
?>
<script type="text/javascript">
		<?php
	 if (isset($_POST['Cancelar'])){
		  ?>
		 mensajeCancelar(<?php echo $idFacturaTemporal;?>, <?php echo "'".$dedonde."'"; ?>); 
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
			 <a  onclick="abrirIndicencia('<?php echo $dedonde;?>' , <?php echo $Usuario['id'];?>, configuracion, <?php echo $idFactura ;?>);">Añadir Incidencia <span class="glyphicon glyphicon-pencil"></span></a>
			<h2 class="text-center"> <?php echo $titulo;?></h2>
			<form action="" method="post" name="formProducto" onkeypress="return anular(event)">
				<a  href="./facturasListado.php">Volver Atrás</a>
					<input type="submit" value="Guardar" name="Guardar" id="bGuardar">
					<input type="submit" value="Cancelar" name="Cancelar" id="bCancelar">
					<?php
				if ($idFacturaTemporal>0){
					?>
					<input type="text" style="display:none;" name="idTemporal" value="<?php echo $idFacturaTemporal;?>">
					<?php
				}
					?>
<div class="col-md-12" >
	<div class="col-md-8">
		<div class="col-md-12">
				<div class="col-md-2">
					<strong>Fecha albarán:</strong><br>
					<input type="date" name="fecha" id="fecha" size="10" data-obj= "cajaFecha"  value="<?php echo $fecha;?>" onkeydown="controlEventos(event)" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" placeholder='dd-mm-yyyy' title=" Formato de entrada dd-mm-yyyy">
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
			<a id="buscar" class="glyphicon glyphicon-search buscar" onclick="buscarProveedor('factura')"></a>
		</div>
	</div>
	<div class="col-md-4" >
	<div>
		<div>
			<div style="margin-top:-50px;" id="tablaAl" style="<?php echo $style;?>">
			<label  id="numPedidoT">Número del albarán:</label>
			<input  type="text" id="numPedido" name="numPedido" value="" size="5" placeholder='Num' data-obj= "numPedido" onkeydown="controlEventos(event)">
			<a id="buscarPedido" class="glyphicon glyphicon-search buscar" onclick="buscarAdjunto('factura')"></a>
			<table  class="col-md-12" id="tablaPedidos"> 
				<thead>
				<td><b>Número</b></td>
				<td><b>Fecha</b></td>
				<td><b>Total</b></td>
				<td></td>
				</thead>
				<?php 
				$i=1;
				if (isset($albaranes)){
					foreach ($albaranes as $albaran){
						if (!isset ($albaran['nfila'])){
							$albaran['nfila']=$i;
						}
						$html=lineaAdjunto($albaran, "factura");
						echo $html['html'];
						$i++;
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
			<th>Num Albaran</th>
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
			if (isset($productos)){
				foreach (array_reverse($productos) as $producto){
					$html=htmlLineaProducto($producto, "factura");
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
			if (isset($Datostotales)){
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
	<div class ="col-md-6" id="divImportes">
			<h3>Entregas</h3>
			<table  id="tablaImporte" class="table table-striped">
				<thead>
					<tr>
						<td>Importe</td>
						<td>Fecha</td>
						<td>Forma de Pago</td>
						<td>Referencia</td>
						<td>Pendiente</td>
					</tr>
				</thead>
				<tbody>
					 <tr id="fila0">  
						<td><input id="Eimporte" name="Eimporte" type="text" placeholder="importe" data-obj= "cajaEimporte" size="13" value=""  onkeydown="controlEventos(event)"></td>
						<td><input id="Efecha" name="Efecha" type="date" placeholder="fecha"    value="<?php echo $fechaImporte;?>"  pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" placeholder='yyyy-mm-dd' title=" Formato de entrada yyyy-mm-dd"></td>
						<td>
						<select name='Eformas' id='Eformas'>
						<?php 
						if(isset($textoFormaPago['html'])){
							echo $textoFormaPago['html'];
						}
						?>
						</select>
						</td>
						<td><input id="Ereferencia" name="Ereferencia" type="text" placeholder="referencia" data-obj= "Ereferencia"  onkeydown="controlEventos(event)" value="" onkeydown="controlEventos(event)"></td>
						<td><a onclick="addTemporal('factura')" class="glyphicon glyphicon-ok"></a></td>
					</tr>
				<?php //Si esa factura ya tiene importes los mostramos 
				if (isset($importesFactura)){
					foreach (array_reverse($importesFactura) as $importe){
						$htmlImporte=htmlImporteFactura($importe, $BDTpv);	
						echo $htmlImporte['html'];
					}
				}			
				?>
				</tbody>
			</table>
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
	if ($idProveedor>0){
		?>
		$('#Proveedor').prop('disabled', true);
		$('#id_proveedor').prop('disabled', true);
		$("#buscar").css("display", "none");
		<?php
	}
	if (count($albaranes)>0){
		?>
		 $('#Row0').css('display', 'none');
		 $('.unidad').attr("readonly","readonly");
		<?php
	}
	if ($estado=="Guardado"){
		?>
		$('#divImportes').show();
		<?php
	}
	if (count($albaranes)==0){
		?>
		$('#tablaAl').hide();
		<?php
	}
	if (count($importesFactura)>0){
		?>
		$("#tabla").find('input').attr("disabled", "disabled");
		$("#tabla").find('a').css("display", "none");
		$("#tablaImporte").show();
		$("#fila0").show();
		<?php
	}
	if ($estado=="Pagado total"){
		?>
		$("#fila0").hide();	
		$("#Cancelar").hide();
		$("#Guardar").hide();
		<?php
	}
	?>
</script>
</body>
</html>
