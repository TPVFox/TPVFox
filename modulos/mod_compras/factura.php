<!DOCTYPE html>
<html>
<head>
<?php
	include_once './../../inicial.php';
	//llamadas  a archivos php 
	include_once $URLCom.'/head.php';
	include_once $URLCom.'/modulos/mod_compras/funciones.php';
	include_once $URLCom.'/controllers/Controladores.php';
	include_once ($URLCom.'/controllers/parametros.php');
	include_once $URLCom.'/clases/Proveedores.php';
	include_once $URLCom.'/modulos/mod_compras/clases/albaranesCompras.php';
	include_once $URLCom.'/modulos/mod_compras/clases/facturasCompras.php';
	include_once $URLCom.'/clases/FormasPago.php';
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
	$titulo="Factura De Proveedor";
	$estado='Abierto';
	$formaPago=0;
	$comprobarAlbaran=0;
	$importesFactura=array();
	$albaranes=array();
	$textoNum="";
	$fecha=date('d-m-Y');
	$fechaImporte=date('Y-d-m');
	$numAdjunto=0;
	$suNumero="";
    $idProveedor="";
	$inciden=0;
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
		$datosFactura=$CFac->datosFactura($idFactura);
		$productosFactura=$CFac->ProductosFactura($idFactura);
		$ivasFactura=$CFac->IvasFactura($idFactura);
		$abaranesFactura=$CFac->albaranesFactura($idFactura);
		$textoFormaPago=htmlFormasVenci($formaPago, $BDTpv);
		$datosImportes=$CFac->importesFactura($idFactura);
        $albaranesFactura=addAlbaranesFacturas($productosFactura, $idFactura, $BDTpv);
		$estado=$datosFactura['estado'];
		$date=date_create($datosFactura['Fecha']);
		$fecha=date_format($date,'d-m-Y');
		$idFacturaTemporal=0;
		$numFactura=$datosFactura['Numfacpro'];
		$idProveedor=$datosFactura['idProveedor'];
		if (isset($datosFactura['Su_num_factura'])){
			$suNumero=$datosFactura['Su_num_factura'];
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
		$total=$Datostotales['total'];
		$importesFactura=modificarArraysImportes($datosImportes, $total);
		$comprobarAlbaran=comprobarAlbaran($idProveedor, $BDTpv);
		$incidenciasAdjuntas=incidenciasAdjuntas($idFactura, "mod_compras", $BDTpv, "factura");
		$inciden=count($incidenciasAdjuntas['datos']);
	}else{
		$idFacturaTemporal=0;
		$idFactura=0;
		$numFactura=0;
		$nombreProveedor="";
	//Si recibe los datos de un temporal
		if (isset($_GET['tActual'])){
				$idFacturaTemporal=$_GET['tActual'];
				$datosFactura=$CFac->buscarFacturaTemporal($idFacturaTemporal);
                $numFactura=0;
                $idFactura=0;
                $fecha1=date_create($datosFactura['fechaInicio']);
                $fecha =date_format($fecha1, 'd-m-Y');
                $suNumero="";
				if (isset ($datosFactura['numfacpro'])){
					$numFactura=$datosFactura['numfacpro'];
					$datosReal=$CFac->buscarFacturaNumero($numFactura);
					$idFactura=$datosReal['id'];
					$textoNum=$idFactura;
				}
				if ($datosFactura['fechaInicio']=="0000-00-00 00:00:00"){
					$fecha=date('d-m-Y');
				}
				if (isset($datosFactura['Su_numero'])){
					$suNumero=$datosFactura['Su_numero'];
				}
				$textoFormaPago=htmlFormasVenci($formaPago, $BDTpv);
				$idProveedor=$datosFactura['idProveedor'];
				$proveedor=$Cprveedor->buscarProveedorId($idProveedor);
				$nombreProveedor=$proveedor['nombrecomercial'];
				$importesFactura=json_decode($datosFactura['FacCobros'], true);
				$factura=$datosFactura;
				$productos =  json_decode($datosFactura['Productos']) ;
				$albaranes=json_decode($datosFactura['Albaranes']);
				$comprobarAlbaran=comprobarAlbaran($idProveedor, $BDTpv);
				
				
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
		cabecera['idUsuario'] = <?php echo $Usuario['id'];?>; 
		cabecera['idTienda'] = <?php echo $Tienda['idTienda'];?>; 
		cabecera['estado'] ='<?php echo $estado ;?>'; 
		cabecera['idTemporal'] = <?php echo $idFacturaTemporal ;?>;
		cabecera['idReal'] = <?php echo $idFactura ;?>;
		cabecera['fecha'] ='<?php echo $fecha ;?>';
		cabecera['idProveedor'] = '<?php echo $idProveedor ;?>';
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
</head>
<body>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
    <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_compras/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_compras/js/AccionesDirectas.js"></script>
<?php
     include_once $URLCom.'/modulos/mod_menu/menu.php';
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
			<h2 class="text-center"> <?php echo $titulo;?></h2>
			<form action="" method="post" name="formProducto" onkeypress="return anular(event)">
				<div class="col-md-12">
				<div class="col-md-8" >
                    <a  href="./facturasListado.php">Volver Atrás</a>
					<input class="btn btn-primary" type="submit" value="Guardar" name="Guardar" id="bGuardar">
                    <?php 
                    if($idFactura>0){
                        echo '<input class="btn btn-warning" size="12" 
                        onclick="abrirModalIndicencia('."'".$dedonde."'".' , configuracion, 0,'.$idFactura.');"
                        value="Añadir incidencia " name="addIncidencia" id="addIncidencia">';
                    }
                    if($inciden>0){
                        echo ' <input class="btn btn-info" size="15" 
                        onclick="abrirIncidenciasAdjuntas('.$idFactura.', '."'".'mod_compras'."'".', '."'".'factura'."'".')" 
                        value="Incidencias Adjuntas " name="incidenciasAdj" id="incidenciasAdj">';
                    }
                    ?>
				</div>
				<div class="col-md-4 text-right" >
                    <span class="glyphicon glyphicon-cog" title="Escoje casilla de salto"></span>
					 <select  title="Escoje casilla de salto" id="salto" name="salto">
						<option value="0">Seleccionar</option>
						<option value="1">Id Articulo</option>
						<option value="2">Referencia</option>
						<option value="3">Referencia Proveedor</option>
						<option value="4">Cod Barras</option>
						<option value="5">Descripción</option>
					</select>
                    <input type="submit" class=" btn btn-danger"  value="Cancelar" name="Cancelar" id="bCancelar">
                </div>
					<?php
				if ($idFacturaTemporal>0){
					?>
					<input type="text" style="display:none;" name="idTemporal" value="<?php echo $idFacturaTemporal;?>">
					<?php
				}
					?>
<div class="col-md-12" >
	<div class="col-md-7">
		<div class="col-md-12">
				<div class="col-md-2">
					<strong>Fecha:</strong><br>
					<input type="text" name="fecha" id="fecha" size="10" data-obj= "cajaFecha"  value="<?php echo $fecha;?>" onkeydown="controlEventos(event)" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" placeholder='dd-mm-yyyy' title=" Formato de entrada dd-mm-yyyy">
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
	<div class="col-md-5" >
	<div class="row">
		<div>
			<div style="margin-top:0px;" id="tablaAl" style="<?php echo $style;?>">
			<label  id="numPedidoT">Número del albarán:</label>
			<input  type="text" id="numPedido" name="numPedido" value="" size="5" placeholder='Num' data-obj= "numPedido" onkeydown="controlEventos(event)">
			<a id="buscarPedido" class="glyphicon glyphicon-search buscar" onclick="buscarAdjunto('factura')"></a>
			<table  class="col-md-12" id="tablaPedidos"> 
				<thead>
				<td><b>Número</b></td>
				<td><b>Su Número</b></td>
				<td><b>Fecha</b></td>
				<td><b>TotalCiva</b></td>
				<td><b>TotalSiva</b></td>
				<td></td>
				</thead>
				<?php 
				$i=1;
				if (isset($albaranes)){
					$alb_html=[];
					foreach ($albaranes as $albaran){
						if (!isset ($albaran['nfila'])){
							$albaran['nfila']=$i;
						}
						$html=lineaAdjunto($albaran, "factura");
						echo $html['html'];
 						$alb_html[]=htmlDatosAdjuntoProductos($albaran);

						$i++;
					}
				}
				$alb_html=array_reverse($alb_html);
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
			<td><input id="idArticulo" type="text" name="idArticulo" placeholder="idArticulo" data-obj= "cajaidArticulo" size="4" value=""  onkeydown="controlEventos(event)"></td>
			<td><input id="Referencia" type="text" name="Referencia" placeholder="Referencia" data-obj="cajaReferencia" size="8" value="" onkeydown="controlEventos(event)"></td>
			<td><input id="ReferenciaPro" type="text" name="ReferenciaPro" placeholder="Referencia" data-obj="cajaReferenciaPro" size="10" value="" onkeydown="controlEventos(event)"></td>
			<td><input id="Codbarras" type="text" name="Codbarras" placeholder="Codbarras" data-obj= "cajaCodBarras" size="12" value="" data-objeto="cajaCodBarras" onkeydown="controlEventos(event)"></td>
			<td><input id="Descripcion" type="text" name="Descripcion" placeholder="Descripcion" data-obj="cajaDescripcion" size="17" value="" onkeydown="controlEventos(event)"></td>
		</tr>
		</thead>
		<tbody>
			<?php 
			$i=0;
			if (isset($productos)){
				foreach (array_reverse($productos) as $producto){
					if($producto['numAlbaran']<>$numAdjunto){
						echo $alb_html[$i];
						$numAdjunto=$producto['numAlbaran'];
						$i++;
					}	
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
	if (count($albaranes)==0 & $comprobarAlbaran==0){
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
    if ($_GET['estado']=="ver"){
        ?>
        $("#fila0").hide();	
		$("#bCancelar").hide();
		$("#bGuardar").hide();
        $("#tabla").find('input').attr("disabled", "disabled");
        $("#tabla").find('a').css("display", "none");
        $("#suNumero").prop('disabled', true);
        $("#fecha").prop('disabled', true);
        $("#numPedido").css("display", "none");
        $("#buscarPedido").css("display", "none");
         $(".eliminar").css("display", "none");
        <?php
    }
	?>
</script>
</body>
</html>
