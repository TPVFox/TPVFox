<!DOCTYPE html>
<html>
<head>
<?php
    include_once './../../inicial.php';
    include $URLCom.'/head.php';
	include_once $URLCom.'/modulos/mod_venta/funciones.php';
	include_once $URLCom.'/controllers/Controladores.php';
	include_once ($URLCom.'/controllers/parametros.php');
	include_once $URLCom.'/clases/cliente.php';
    include_once $URLCom.'/modulos/mod_venta/clases/albaranesVentas.php';
    include_once $URLCom.'/modulos/mod_venta/clases/facturasVentas.php';
    include_once $URLCom.'/clases/FormasPago.php';
    
	$ClasesParametros = new ClaseParametros('parametros.xml');
	$Ccliente=new Cliente($BDTpv);
	$Calbcli=new AlbaranesVentas($BDTpv);
	$Cfaccli=new FacturasVentas($BDTpv);
	$CforPago=new FormasPago($BDTpv);
	$Controler = new ControladorComun; 
    
	$Controler->loadDbtpv($BDTpv);
	$Tienda = $_SESSION['tiendaTpv'];
	$Usuario = $_SESSION['usuarioTpv'];// array con los datos de usuario
	$idFacturaTemporal=0;
	$idFactura=0;
	$numFactura=0;
	$idCliente=0;
	$nombreCliente=0;
	$titulo="Factura De Cliente ";
	$estado='Abierto';
	$fecha=date('d-m-Y');
	$Simporte="display:none;";
	$formaPago=0;
	$albaranes=array();
	$importesFactura=array();
	$dedonde="factura";
	$textoNum="";
	$fechaImp=date('Y-m-d');
	$comprobarAlbaran=0;
    $parametros = $ClasesParametros->getRoot();
    $inciden="";
	foreach($parametros->cajas_input->caja_input as $caja){
			$caja->parametros->parametro[0]="factura";
		}
		$VarJS = $Controler->ObtenerCajasInputParametros($parametros);
		$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
		$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_ventas',$Usuario['id']);
		$configuracionArchivo=array();
		foreach ($configuracion['incidencias'] as $config){
			if(get_object_vars($config)['dedonde']==$dedonde){
				array_push($configuracionArchivo, $config);
			}
		}
		
	if (isset($_GET['id'])){//Si rebie un id quiere decir que ya existe la factura
		$idFactura=$_GET['id'];
		$textoNum=$idFactura;
		$datosFactura=$Cfaccli->datosFactura($idFactura);//Extraemos los datos de la factura 
		$productosFactura=$Cfaccli->ProductosFactura($idFactura);//De los productos
		$ivasFactura=$Cfaccli->IvasFactura($idFactura);//De la tabla de ivas
		$albaranFactura=$Cfaccli->AlbaranesFactura($idFactura);//Los albaranes de las facturas añadidos
		$datosImportes=$Cfaccli->importesFactura($idFactura);
      
		$estado=$datosFactura['estado'];
		$fecha =date_format(date_create($datosFactura['Fecha']), 'd-m-Y');
		$numFactura=$datosFactura['Numfaccli'];
		$idCliente=$datosFactura['idCliente'];
		if ($idCliente){
				$datosCliente=$Ccliente->DatosClientePorId($idCliente);
				$nombreCliente="'".$datosCliente['Nombre']."'";
		}
		$comprobarAlbaran=comprobarAlbaran($idCliente, $BDTpv);
		if (isset($datosFactura['formaPago'])){
			if($datosFactura['formaPago']>0){
				$formaPago=$datosFactura['formaPago'];
			}
			
			
		}
		$textoFormaPago=htmlFormasVenci($formaPago, $BDTpv);
		if (isset($datosFactura['fechaVencimiento'])){
			$date=date_create($datosFactura['fechaVencimiento']);
			$fechave=date_format($date,'Y-m-d');
			
		}else{
			$fec=date('Y-m-d');
			
			$fechave=fechaVencimiento($fec, $BDTpv);
		}
		$textoFecha=htmlVencimiento($fechave, $BDTpv);
		
		$productosMod=modificarArrayProductos($productosFactura);
		$productos=json_decode(json_encode($productosMod));
		
		$Datostotales = recalculoTotales($productos);
		$productos=json_decode(json_encode($productos), true);
		if ($albaranFactura){
			 $modificaralbaran=modificarArrayAlbaranes($albaranFactura, $BDTpv);
			 $albaranes=json_decode(json_encode($modificaralbaran), true);
		}
		$total=$Datostotales['total'];
		$importesFactura=modificarArraysImportes($datosImportes, $total);
		$incidenciasAdjuntas=incidenciasAdjuntas($idFactura, "mod_ventas", $BDTpv, "factura");
		$inciden=count($incidenciasAdjuntas['datos']);
		
	}else{// si no recibe un id de una factura ya creada ponemos los datos de la temporal en caso de que tenga 
		//Si no dejamos todo en blanco para poder cubrir
			if (isset($_GET['tActual'])){
				$idFacturaTemporal=$_GET['tActual'];
				$datosFactura=$Cfaccli->buscarDatosFacturasTemporal($idFacturaTemporal);
				if (isset($datosFactura['Numfaccli '])){
					$numFactura=$datosFactura['Numfaccli'];
					$idFactura=$numFactura;
					$textoNum=$idFactura;
				}
				if ($datosFactura['fechaInicio']=="0000-00-00 00:00:00"){
					$fecha=date('d-m-Y');
				}else{
					$fecha =date_format(date_create($datosFactura['fechaInicio']), 'd-m-Y');
				}
				$idCliente=$datosFactura['idClientes'];
				$comprobarAlbaran=comprobarAlbaran($idCliente, $BDTpv);
				$cliente=$Ccliente->DatosClientePorId($idCliente);
				$nombreCliente="'".$cliente['Nombre']."'";
				if (isset ($cliente['formasVenci'])){
					$formasVenci=$cliente['formasVenci'];
				}else{
					$formasVenci=0;
				}
				$factura=$datosFactura;
				$productos =  json_decode($datosFactura['Productos']) ;
				$albaranes=json_decode($datosFactura['Albaranes']);
				echo gettype($datosFactura['FacCobros']);
					echo $datosFactura['FacCobros'];
				$importesFactura=json_decode($datosFactura['FacCobros'], true);
				$textoFormaPago=htmlFormasVenci($formasVenci, $BDTpv);
				$fec=date('Y-m-d');
				$fechave=fechaVencimiento($fec, $BDTpv);
			
				
				$textoFecha=htmlVencimiento($fechave, $BDTpv);
			}
	}
		if(isset($factura['Productos'])){
			// Obtenemos los datos totales ( fin de ticket);
			// convertimos el objeto productos en array
			$Datostotales = recalculoTotales($productos);
			$productos = json_decode(json_encode($productos), true); // Array de arrays	
		}
		
		if (isset($factura['Albaranes'])){
			$albaranes=json_decode(json_encode($albaranes), true);
		}
		//Cuando guardadmos buscamos todos los datos de la factura temporal y hacfemos las comprobaciones pertinentes
		if (isset($_POST['Guardar'])){
			if (isset($_GET['id'])){
				$formaVenci="";
				$fechaVenci="";
				if (isset($_POST['formaVenci'])){
					$formaVenci=$_POST['formaVenci'];
				}
				if(isset($_POST['fechaVenci'])){
					$fecha1=date_create($_POST['fechaVenci']);
					$fechaVenci=date_format($fecha1, 'Y-m-d');;
				}
				$fecha1=date_create($_POST['fecha']);
				
				//~ $fecha =date_format(date_create($_POST['fecha']), 'Y-m-d');
				$fecha=date_format($fecha1, 'Y-m-d');
				$modFecha=$Cfaccli->modificarFechaFactura($_GET['id'], $fecha, $formaVenci, $fechaVenci);
				if(isset($modFecha['error'])){
					echo '<div class="alert alert-danger">'
						. '<strong>Danger! </strong> Error en la base de datos <br>Sentencia: '.$modFecha['consulta']
						. '</div>';
					
				}else{
					 header('Location: facturasListado.php');
				}
			}else{
                $estado="Guardado";
                if (isset($_POST['idTemporal'])){
                    $idTemporal=$_POST['idTemporal'];
                }else if(isset($_GET['tActual'])){
                    $idTemporal=$_GET['tActual'];
                }else{
                    $idTemporal=0;
                }
                $total=0;
                $formaVenci="";
                $entregado=0;
                $idFactura=0;
                $errores=array();
                $datosFactura=$Cfaccli->buscarDatosFacturasTemporal($idFacturaTemporal);
                if($datosFactura['total']){
                    $total=$datosFactura['total'];
                }
                $fechaActual=date('Y-m-d');
                if ($_POST['formaVenci']){
                    $formaVenci=$_POST['formaVenci'];
                }
                if (is_array($importesFactura)){
                    foreach ($importesFactura as $import){
                        $entregado=$entregado+$import['importe'];
                    }
                    if ($total==$entregado){
                        $estado="Pagado total";
                    }else{
                        $estado="Pagado Parci";
                    }
                }
                $fecha=date_format(date_create($_POST['fecha']), 'Y-m-d');
                $datos=array(
                    'Numtemp_faccli'=>$idTemporal,
                    'Fecha'=>$fecha,
                    'idTienda'=>$Tienda['idTienda'],
                    'idUsuario'=>$Usuario['id'],
                    'idCliente'=>$idCliente,
                    'estado'=>$estado,
                    'total'=>$total,
                    'DatosTotales'=>$Datostotales,
                    'productos'=>$datosFactura['Productos'],
                    'albaranes'=>$datosFactura['Albaranes'],
                    'fechaCreacion'=>$fechaActual,
                    'formapago'=>$formaVenci,
                    'fechaVencimiento'=>$_POST['fechaVenci'],
                    'importes'=>$importesFactura,
                    'fechaModificacion'=>$fechaActual
                    );
								if($datosFactura['numfaccli']>0){
									$idFactura=$datosFactura['numfaccli'];
									$eliminarTablasPrincipal=$Cfaccli->eliminarFacturasTablas($idFactura);
									if (isset($eliminarTablasPrincipal['error'])){
									$errores[0]=array ( 'tipo'=>'Danger!',
																 'dato' => $eliminarTablasPrincipal['consulta'],
																 'class'=>'alert alert-danger',
																 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
																 );
									}
								}
								if(count($errores)==0){
									$addNuevo=$Cfaccli->AddFacturaGuardado($datos, $idFactura);
									if (isset($addNuevo['error'])){
									$errores[0]=array ( 'tipo'=>'Danger!',
																 'dato' => $addNuevo['consulta'],
																 'class'=>'alert alert-danger',
																 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
																 );
									}else{
										$eliminarTemporal=$Cfaccli->EliminarRegistroTemporal($idTemporal, $idFactura);
										if (isset($eliminarTemporal['error'])){
										$errores[1]=array ( 'tipo'=>'Danger!',
																 'dato' => $eliminarTemporal['consulta'],
																 'class'=>'alert alert-danger',
																 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
																 );
										 }
									}
									
								}
								if(count($errores)>0){
									foreach($errores as $error){
										echo '<div class="'.$error['class'].'">'
										. '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br>Sentencia: '.$error['dato']
										. '</div>';
									}
								}else{
									header('Location: facturasListado.php');
								}
		}
			
		}
		
$titulo .= ' '.$textoNum.': '.$estado;
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
		cabecera['idTemporal'] = <?php echo $idFacturaTemporal ;?>;
		cabecera['idReal'] = <?php echo $idFactura ;?>;
		cabecera['fecha'] = '<?php echo $fecha ;?>';
		cabecera['idCliente'] = <?php echo $idCliente ;?>;
		
		 // Si no hay datos GET es 'Nuevo';
	var productos = []; // No hace definir tipo variables, excepto cuando intentamos a�adir con push, que ya debe ser un array
	var albaranes =[];
<?php 
	if (isset($facturaTemporal)| isset($idFactura)){ 
?>
	</script>
	<script type="text/javascript">
<?php
	$i= 0;
	//Introducimos los productos a la cabecera productos 
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
		if (isset($albaranes)){
			foreach ($albaranes as $alb){
				?>
				datos=<?php echo json_encode($alb);?>;
				albaranes.push(datos);
				<?php
			}
		}
	}	
?>
</script>
<?php 
if ($idCliente==0){
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
<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
<script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
<div class="container">
	
			<h2 class="text-center"> <?php echo $titulo;?></h2>
			<form action="" method="post" name="formProducto" onkeypress="return anular(event)">
				<div class="col-md-12">
				<div class="col-md-8" >
                    <a  href="./facturasListado.php">Volver Atrás</a>
                    <?php 
                        if($idFactura>0){
                            ?>
                            <input class="btn btn-warning" size="12" onclick="abrirModalIndicencia('<?php echo $dedonde;?>' , configuracion, 0,<?php echo $idFactura ;?>);" value="Añadir incidencia " name="addIncidencia" id="addIncidencia">

                            <?php
                        }
                            if($inciden>0){
                            ?>
                            <input class="btn btn-info" size="15" onclick="abrirIncidenciasAdjuntas(<?php echo $idFactura;?>, 'mod_ventas', 'factura')" value="Incidencias Adjuntas " name="incidenciasAdj" id="incidenciasAdj">
                            <?php
                        }
                        ?>
					<input type="submit"  class="btn btn-primary" value="Guardar" id="Guardar" name="Guardar">
					</div>
				<div class="col-md-4 text-right" >
                     <span class="glyphicon glyphicon-cog" title="Escoje casilla de salto"></span>
                     <select  title="Escoje casilla de salto" id="salto" name="salto">
                        <option value="0">Seleccionar</option>
						<option value="1">Id Articulo</option>
						<option value="2">Referencia</option>
						<option value="3">Cod Barras</option>
						<option value="4">Descripción</option>
					</select>
					<input type="submit" class="btn btn-danger" value="Cancelar" id="Cancelar" name="Cancelar">
					</div>
					<?php
				if ($idFacturaTemporal>0){
					?>
					<input type="text" style="display:none;" name="idFactura" value="<?php echo $idFacturaTemporal;?>">
					<?php
				}
					?>
<div class="col-md-12" >
	<div class="col-md-8">
		<div class="col-md-12">
			
				<div class="col-md-2">
					<strong>Fecha Fact:</strong><br>
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
					<strong>Forma de pago:</strong><br>
					<p id="formaspago">
						<select name='formaVenci' id='formaVenci' onChange='selectFormas()'>
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
					<p id="fechaVencimiento">
						<?php
						if (isset ($textoFecha)){
							echo $textoFecha['html'];
						}
					?>
					
						</p>
			</div>
			
		</div>
		<div class="form-group">
			<label>Cliente:</label>
			<input type="text" id="id_cliente" name="id_cliente" data-obj= "cajaIdCliente" value="<?php echo $idCliente;?>" size="2" onkeydown="controlEventos(event)" placeholder='id'>
			<input type="text" id="Cliente" name="Cliente" data-obj= "cajaCliente" placeholder="Nombre de cliente" onkeydown="controlEventos(event)" value="<?php echo $nombreCliente; ?>" size="60">
			<a id="buscar" class="glyphicon glyphicon-search buscar" onclick="buscarClientes('factura')"></a>
		</div>
	</div>
	<div class="col-md-4" >
	
		<div>
			<div style="margin-top:0;" id="tablaAl">
			<label  id="numAlbaranT">Número del albaran:</label>
			<input  type="text" id="numAlbaran" name="numAlbaran" value="" size="5" placeholder='Num' data-obj= "numAlbaran" onkeydown="controlEventos(event)">
			<a  id="buscarAlbaran" class="glyphicon glyphicon-search buscar" onclick="buscarAlbaran('albaran')"></a>
			<table  class="col-md-12"  id="tablaAlbaran"> 
				<thead>
				
				<td><b>Número</b></td>
				<td><b>Fecha</b></td>
				<td><b>Total</b></td>
				
				</thead>
				
				<?php 
				
				if (isset($albaranes)){
					$html=htmlAlbaranFactura($albaranes, "factura");
					echo $html['html'];
				}
				?>
			</table>
			</div>
		</div>
	</div>
	<!-- Tabla de lineas de productos -->
	<div>
		<table id="tabla" class="table table-striped" >
		<thead>
		  <tr>
			<th>L</th>
			<th>Num Albaran</th>
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
		  <tr id="Row0">  
			<td id="C0_Linea" ></td>
			<td></td>
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
				$html=htmlLineaPedidoAlbaran($producto, "factura");
				echo $html;
			}
		
			}
			?>
		</tbody>
	  </table>
	</div>
	<?php 
	if(isset($Datostotales)){
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
			if(isset($Datostotales)){
			$htmlIvas=htmlTotales($Datostotales);
			echo $htmlIvas['html']; 
			}?>
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
		<div class ="col-md-6" >
			<h3 style="<?php echo $Simporte;?>">Entregas</h3>
			<table  id="tablaImporte" class="table table-striped" style="<?php echo $Simporte;?>">
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
				<td><input id="Efecha" name="Efecha" type="date" placeholder="fecha" data-obj= "cajaEfecha"  onkeydown="controlEventos(event)" value="<?php echo $fechaImp;?>" onkeydown="controlEventos(event)" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" placeholder='yyyy-mm-dd' title=" Formato de entrada yyyy-mm-dd"></td>
				<td>
					<select name='Eformas' id='Eformas'>
				<?php 
				if(isset ($textoFormaPago['html'])){
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
	if ($idCliente>0){
		?>
		$('#Cliente').prop('disabled', true);
		$('#id_cliente').prop('disabled', true);
		$("#buscar").css("display", "none");
		<?php
	}else{
		?>
		$("#Row0").css("display", "none");
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

if (count($albaranes)>0){
		?>
		 $('#Row0').css('display', 'none');
		 $('.unidad').attr("readonly","readonly");
		<?php
}
if($estado=="Guardado"){
	?>
	$("#tablaImporte").show();
	$("#fila0").show();
	<?php
}
if (isset($productos) & $albaranes==null & $comprobarAlbaran==0){
	?>
	$("#tablaAl").hide();
	<?php
}
if($_GET['estado']=="ver"){
    ?>
    $("#fila0").hide();
	$("#Cancelar").hide();
	$("#Guardar").hide();
    $("#tabla").find('input').attr("disabled", "disabled");
    $("#tabla").find('a').css("display", "none");
     $("#fecha").attr("disabled", "disabled");
    $("#fechaVenci").attr("disabled", "disabled");
    $("#numAlbaran").css("display", "none");
    $("#buscarAlbaran").css("display", "none");
        $(".eliminar").css("display", "none");
    <?php 
}
	?>
</script>
	</body>
</html>
