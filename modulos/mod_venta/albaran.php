<?php

    include_once './../../inicial.php';
	include_once $URLCom.'/modulos/mod_venta/funciones.php';
	include_once $URLCom.'/controllers/Controladores.php';
	include_once ($URLCom.'/controllers/parametros.php');
	include_once $URLCom.'/modulos/mod_cliente/clases/ClaseCliente.php';
    include_once $URLCom.'/modulos/mod_venta/clases/albaranesVentas.php';
    include_once $URLCom.'/modulos/mod_venta/clases/pedidosVentas.php';
     
	$ClasesParametros = new ClaseParametros('parametros.xml');
	$Ccliente=new ClaseCliente();
	$CalbAl=new AlbaranesVentas($BDTpv);
	$Cped = new PedidosVentas($BDTpv);
	$Controler = new ControladorComun; 
	$Controler->loadDbtpv($BDTpv);
    // -- Valores por defecto -- //
	$idTemporal=0;
	$idAlbaran=0;
	$idCliente=0;
	$titulo="Albarán De Cliente ";
    $accion     = '';
	$estado     = 'Nuevo';
	$fecha=date('d-m-Y');
	$dedonde="albaran";
    $empleado   = $Usuario; // Por defecto ponemos el usuario
    $errores    = array();
	$parametros = $ClasesParametros->getRoot();
    $inciden= "";
	foreach($parametros->cajas_input->caja_input as $caja){
			$caja->parametros->parametro[0]="albaran";
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
    // Comprobamos:
    // $_GET['tActual] -> No comprobamos nada, solo asignamos valor idTemporal
    // $_GET['id'] -> Si trae accion y si existe temporal. Si exite temporal advertimos y no dejamos editar.
    if (isset($_GET['tActual'])){
        $idTemporal=$_GET['tActual'];
    } else {
        if (isset($_GET['id'])){
            $idAlbaran = $_GET['id'];
            if (isset($_GET['accion'])){
                if ( $_GET['accion'] == 'editar'){
                    $accion = $_GET['accion'];
                } 
            }
            if ($accion != 'editar'){
                // Cuando trae GET['id'] y no trae accion es 'ver' siempre.
                $accion = 'ver';
            }
            if ($accion != 'ver'){
                // Comprobamos temporales para ese idAlbaran y obtenemos idTemporal si tiene.
            }
        }
    }
    // --------------- MONTAMOS DATOS DE NUEVO,TEMPORAL,FACTURA CREADA   -------------   //
    // --- NUEVO --- /
    if ($idTemporal==0 && $idAlbaran == 0){
        $datosCliente = array ('idClientes' => '',
                              'Nombre'     => '',
                       );
        $productos = array();
    }

    // --- TEMPORAL --- /   
    if ($idTemporal>0 && $accion==''){
        // Temporal
        $datosAlbaran = $datosAlbaran=$CalbAl->buscarDatosAlbaranTemporal($idTemporal);;
        if (isset($datosAlbaran['numalbcli'])){
            $idAlbaran=$datosAlbaran['numalbcli'];
        }
    }

     // ---- ALBARAN YA CREADO  -------  //
     // Entra tanto cuando al id de Albaran , como viene de temporal y esta tiene idAlbaran.
	if ($idAlbaran >0 ){
		$datosAlbaran_guardada              = $CalbAl->datosAlbaran($idAlbaran);
		$productosAlbaran                   = $CalbAl->ProductosAlbaran($idAlbaran);
		$productosMod			    = modificarArrayProductos($productosAlbaran);
		$datosAlbaran_guardada['Productos'] = json_encode($productosMod);
        $pedisAlbaran_creada                = $CalbAl->PedidosAlbaranes($idAlbaran);
        $datosAlbaran_guardada['Pedidos'] = json_encode($pedisAlbaran_creada);
        $incidenciasAdjuntas                = incidenciasAdjuntas($idAlbaran, "mod_ventas", $BDTpv, "albaran");
		$inciden                            = count($incidenciasAdjuntas['datos']);
        if ($idTemporal == 0){
            // Comprobamos si esta albaran tiene temporales.
            // Si no es temporal, entonces tenemos crear $datosAlbaran.
            $datosAlbaran = $datosAlbaran_guardada;
            $estado=$datosAlbaran['estado'];
        }
    }
    if ( isset($datosAlbaran)){
        // Esto es lo comun cuando es temporal o cuando es factura existente.
        $idCliente = $datosAlbaran['idCliente'];
        $pedidos = json_decode($datosAlbaran['Pedidos'],true);
        $productos = json_decode($datosAlbaran['Productos']) ;
        $Datostotales = recalculoTotales($productos); // Necesita un array de objetos.
        $productos = json_decode($datosAlbaran['Productos'], true); // Convertimos en array de arrays
        $fecha =date_format(date_create($datosAlbaran['Fecha']), 'd-m-Y');
    }

    // ---- Compromamos si existe la albaran que el estado sea Sin guardar --- //
    if ($idAlbaran >0){
        // Pendiente hacer.
    
    }

    
    if ($idCliente > 0){
        $datosCliente=$Ccliente->getCliente($idCliente);
        if ( isset($datosCliente['datos'])){
            $datosCliente  = $datosCliente['datos']['0'];
            // Como en tabla en clientes idCliente es idCLientes (algo que está muy mal.. :-) lo tenemos crear.
            $idCliente = $datosCliente['idClientes'];
        } else {
            $errores[]=$CalbAl->montarAdvertencia('danger',
                                             'No se encuentra datos del cliente de la factura con id'.$idCliente.'</br/>'
                                             .json_encode($datosCliente)
                                            );
        }
    }

    // ---  Pulso Guardar --- //
    if (isset($_POST['Guardar'])){
        if ( $accion !='ver' && $idTemporal >0){
            // Si entramos aquí es porque existe temporal y pulso guardar.
            // ---- Comprobamos fechas antes guardar --- //
            $f= explode('-',$_POST['fecha']);
            $validar_fecha = checkdate($f['1'],$f['0'],$f['2']);
            
            if ($validar_fecha ==true){
               $fecha_post = $f['2'].'-'.$f['1'].'-'.$f['0'];
            } else {
                // Si la fecha no es correcta.
                 $errores[] =$CalbAl->montarAdvertencia('warning',
                                             'La fecha que envia POST no es correcta.Fecha:'.$_POST['fecha']
                                            );
            }
            $datos=array(
						'Numtemp_albcli'=>$idTemporal,
						'Fecha'=>$fecha,
						'idTienda'=>$Tienda['idTienda'],
						'idUsuario'=>$Usuario['id'],
						'idCliente'=>$idCliente,
						'estado'=>"Guardado",
						'total'=>$Datostotales['total'],
						'DatosTotales'=>$Datostotales,
						'productos'=>$datosAlbaran['Productos'],
						'pedidos'=>$datosAlbaran['Pedidos']
					);

            if(count($errores)==0){
                if($datosAlbaran['numalbcli']>0){
                    $idAlbaran=$datosAlbaran['numalbcli'];
                    $eliminarTablasPrincipal=$CalbAl->eliminarAlbaranTablas($idAlbaran);
                    if (isset($eliminarTablasPrincipal['error'])){
                        $errores[] =$CalbAl->montarAdvertencia('danger',
                                                 'ERROR EN LA BASE DE DATOS AL BORRAR factura!'.'<br/> Consulta:'.$eliminarTablasPrincipal['consulta']
                                                 .'<br/>Error:'.$eliminarTablasPrincipal['error'].'<br/>'
                                                );
                    }
                }
            }
            if(count($errores)==0){
                $addNuevo=$CalbAl->AddAlbaranGuardado($datos, $idAlbaran);
                if(isset($addNuevo['error'])){
                $errores[]=$CalbAl->montarAdvertencia('Danger!',
                                              'Error al añadir factura y guardarla.<br/>'
                                                 .'Error:'.$addNuevo['error'].'<br/>'
                                                 .'Consulta:'.$addNuevo['consulta'].'<br/>'
                                                 );
                }
                $eliminarTemporal=$CalbAl->EliminarRegistroTemporal($idTemporal, $idAlbaran);
                if(isset($eliminarTemporal['error'])){
                $errores[]=$CFac->montarAdvertencia('danger',
                                                 'Error al eliminar temporal:'.$idTemporal
                                                 .'Error: '.$eliminarTemporal['error'].'<br/>'
                                                 .'Consulta:'.$eliminarTemporal['consulta'].'<br/>'
                                                 );
                }
            }
            if(count($errores) == 0){
                //  Redireccionamos a listado facturas una vez guardado correctamente.
                header('Location: albaranesListado.php');
            } else {
                // Si hay error es una de las 3 consultas que hace para eliminar datos de la factura de varias tablas.
                // devuelve un array con error y consulta. Entonces hay que ver cual elimino o no .

                // --- Ahora pongo valores post, para crear temporal --/
                // Esto debería se una funcion, para poder reutilizar en en debug.
                $_POST['idTemporal']    = 0; // Ya queremos sea un temporal nuevo.
                $_POST['idUsuario']     = $Usuario['id'];
                $_POST['idTienda']      = $Tienda['idTienda'];
                $_POST['idReal']        = $datosAlbaran['numalbcli'];
                $_POST['productos']     = $datosAlbaran_guardada['Productos'];
                $_POST['idCliente']     = $datosAlbaran_guardada['idCliente'];
                // Los albaranes de factura directa hay que prepararlos para ser un adjunto
                $pAdjuntos = prepararAdjuntos(json_decode($datosAlbaran_guardada['Pedidos'],true),$dedonde,$accion);
                $_POST['pedidos']     = $pAdjuntos['adjuntos'];
                // Para añadir el temporal de copia de los datos si hubo error.
                include_once $URLCom.'/modulos/mod_venta/tareas/AddAlbaranTemporal.php';
                $errores[]=$CFac->montarAdvertencia('danger',
                                    'HUBO ERROR AL GRABAR !! <br/>'
                                    .'El error, es el o los anteriores.</br>'
                                    .'Creamos un temporal nuevo con los datos factura guardada para recuperarlos, idTemporal:'.$respuesta['id']
                                    .'<br/> Avisa al administrador de sistema, no cierres pantalla.'
                                    );
            }
        }
    }


    // --- Montamos html_adjuntos y variable adjunto que utilizamos para montar variable JS --- //
    $html_adjuntos= '';
    $adjuntos = array();
    if ($idTemporal>0){
        // No hay accion , por lo que tenemos editar
        $accion = 'editar';
    }
    $pAdjuntos = prepararAdjuntos($pedidos,$dedonde,$accion);
    $adjuntos = $pAdjuntos['adjuntos'];
    $html_adjuntos = $pAdjuntos['html'];
    
    // ---  Controlamos cuando poner solo lectura o display none los campos --- //
    $display = '';
    $readonly = '';
    $readonly_cliente = '';
    if ($accion == 'ver'){
        $display = 'style="display:none"';
        $readonly = 'readonly';
        $readonly_cliente = 'readonly';
    }
    $display_btn_guardar_cancelar = $display;
    if ($idTemporal == 0){
        $display_btn_guardar_cancelar= 'style="display:none"';
    }
    if ($idAlbaran > 0 ){
        $readonly_cliente = 'readonly';
    }
    // --- html de titulo --- /
    $n = 'Sin Guardar';
    if ($idAlbaran > 0) {
        $n = $idAlbaran;
    }
    $html_numero = '<span>'.$n.'</span>';
    if ($idTemporal > 0){
        $a = 'TEMPORAL'.'<span class="glyphicon glyphicon-info-sign" title="Numero temporal:'.$idTemporal.'"></span><input type="text" style="display:none;" name="idAlbaran" value="'.$idTemporal.'">';
        $readonly_cliente = 'readonly';
    } else {
        $a = $accion;
    }
    $html_accion = '<span>'.$a.'</span>';
?>
<!DOCTYPE html>
<html>
<head>
    <?php include $URLCom.'/head.php';?>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_venta/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_venta/js/AccionesDirectas.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script>
    <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
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
		cabecera['idReal'] = <?php echo $idAlbaran ;?>;
		cabecera['fecha'] = '<?php echo $fecha ;?>';
		cabecera['idCliente'] = <?php echo $idCliente ;?>;
		
		 // Si no hay datos GET es 'Nuevo';
	var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array
	var pedidos =[];
<?php 
	if (isset($albaranTemporal)| isset($idAlbaran)){ 
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
    if (isset($_POST['Cancelar'])){
    ?>
        cancelarTemporal(<?php echo $idTemporal;?>, <?php echo "'".$dedonde."'"; ?>);
	<?php
	}
	echo $VarJS;
    ?>
    function anular(e) {
          tecla = (document.all) ? e.keyCode : e.which;
          return (tecla != 13);
    }
</script>

</head>
<body>
<?php
     include_once $URLCom.'/modulos/mod_menu/menu.php';
?>

<div class="container">
	
    <?php
	if (count($errores)>0){
        foreach ($errores as $error){
         		echo '<div class="alert alert-'.$error['tipo'].'">'
				. '<strong>'.$error['tipo'].' </strong> <br/>'.$error['mensaje']. '</div>';
            // Controlamos si hay un danger, no permito cancelar.
            if ( $error['tipo'] =='danger'){
                $display_btn_guardar_cancelar= 'style="display:none"';
            }
        }
	}
    ?>
    <?php
        // Montamos html de titulo
        echo '<h2 class="text-center">'.$titulo.$html_numero.'-'.$html_accion.'</h2>' ;?>
			<form action="" method="post" name="formProducto" onkeypress="return anular(event)">
				<div class="col-md-12">
                    <div class="col-md-8" >
            <?php echo $Controler->getHtmlLinkVolver('Volver');?>
                        <?php 
                            if($idAlbaran>0){
                                ?>
                                <input class="btn btn-warning" size="12" onclick="abrirModalIndicencia('<?php echo $dedonde;?>' , configuracion, 0,<?php echo $idAlbaran ;?>);" value="Añadir incidencia " name="addIncidencia" id="addIncidencia">

                                <?php
                            }
                                if($inciden>0){
                                ?>
                                <input class="btn btn-info" size="15" onclick="abrirIncidenciasAdjuntas(<?php echo $idAlbaran;?>, 'mod_ventas', 'albaran')" value="Incidencias Adjuntas " name="incidenciasAdj" id="incidenciasAdj">
                                <?php
                            }
                            ?>
                <input type="submit"  class="btn btn-primary" <?php echo $display_btn_guardar_cancelar;?> value="Guardar" id="Guardar" name="Guardar">
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
                <input type="submit" class="btn btn-danger" <?php echo $display_btn_guardar_cancelar;?> value="Cancelar Temporal" id="Cancelar" name="Cancelar">
            </div>
<div class="col-md-12" >
	<div class="col-md-8">
		<div class="col-md-12">
            <div class="col-md-2">
                <strong>Fecha Alb:</strong><br>
                <input type="text" name="fecha" id="fecha" size="10" data-obj= "cajaFecha"  value="<?php echo $fecha;?>" onkeydown="controlEventos(event)" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" placeholder='dd-mm-yyyy' <?php echo $readonly;?> title=" Formato de entrada dd-mm-yyyy">
            </div>
            <div class="col-md-2">
                <strong>Estado:</strong><br>
            
                <span id="Estado"> <input type="text" id="estado" name="estado" value="<?php echo $estado;?>" size="10" readonly></span><br>
            </div>
        
				<div class="col-md-2">
                    <strong>Empleado:</strong><br>
                    <input type="text" id="Usuario" name="Usuario" value="<?php echo $empleado['nombre'];?>" size="10" readonly>
                </div>
            
		</div>
		<div class="form-group">
			<label>Cliente:</label>
			<input type="text" id="id_cliente" name="id_cliente" data-obj= "cajaIdCliente" <?php echo $readonly_cliente;?> value="<?php echo $datosCliente['idClientes'];?>" size="2" onkeydown="controlEventos(event)" placeholder='id'>
			<input type="text" id="Cliente" name="Cliente" data-obj= "cajaCliente" placeholder="Nombre de cliente" onkeydown="controlEventos(event)" value="<?php echo $datosCliente['Nombre']; ?>"  <?php echo $readonly_cliente;?> size="60">
            <?php if ($readonly_cliente ==''  ){?>
                <a id="buscar" class="glyphicon glyphicon-search buscar" onclick="buscarClientes('factura')"></a>
            <?php } ?>
		</div>
	</div>
	<div class="col-md-4" >
        <div style="margin-top:0;" id="tablaAl">
			<label id="numPedidoT">Número del pedido:</label>
			<input type="text" id="numPedido" name="numPedido" value="" size="5" placeholder='Num' <?php echo $readonly;?> data-obj= "numPedido" onkeydown="controlEventos(event)">
			<a id="buscarPedido" class="glyphicon glyphicon-search buscar" onclick="buscarAdjunto('albaran')"></a>
			<table  class="col-md-12" id="tablaAlbaran"> 
				<thead>
				
				<td><b>Número</b></td>
				<td><b>Fecha</b></td>
				<td><b>Total</b></td>
				
				</thead>
                <?php echo $html_adjuntos;?>
			</table>
		</div>
	</div>
	</div>
	<!-- Tabla de lineas de productos -->
	<div >
		<table id="tabla" class="table table-striped">
		<thead>
		  <tr>
			<th>L</th>
			<th>Num<span class="glyphicon glyphicon-info-sign" title="Numero de adjunto"></span></th>
			<th>Id Articulo</th>
			<th>Referencia</th>
			<th>Cod Barras</th>
			<th>Descripcion</th>
			<th>Unid</th>
			<th>PVP</th>
			<th>Pv sin iva</th>
			<th>Iva</th>
			<th>Importe</th>
			<th></th>
		  </tr>
		  <tr id="Row0" <?php echo $display;?>>  
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
			//Si el albarán ya tiene productos
			if (isset($productos)){
				foreach (array_reverse($productos) as $producto){
                    $html=htmlLineaProductos($producto, "albaran", $accion);
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
		if(isset($Datostotales) && count($Datostotales)>0){
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
				<?php echo (isset($Datostotales['total']) ? $Datostotales['total'] : '');?>
			</div>
		</div>
	
</form>
</div>
<?php // Incluimos paginas modales
echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
include $RutaServidor.'/'.$HostNombre.'/plugins/modal/ventanaModal.php';
?>
	</body>
</html>
