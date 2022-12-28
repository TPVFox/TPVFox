<?php
    // Vista Factura
    // Anotaciones:
    // 1) No permitimos borrar facturas, por lo que campo Numfactucli no tiene sentido, seria el mismo idFactura.
    //    Si queremos eliminar una factura, deberemos permitir ponerla 0 productos, sino cuando generamos una factura por error
    //    como hacemos para que no genere confusion.
    // 2) Los posibles estados de una factura son :
    //    Nuevo: Es un estado que nunca se guarda como tal, solo sirve para identificar que aun no se creo temporal que seria Sin guardar
    //    Guardado : Estado por defecto cuando se guarda una factura, esto implica que se puede modificar
    //    Sin guardar : Estado por defecto de la factura temporal, tanto cuando nuevo o es uno modificado.
    //    Pagado Parci : No se puede modificar. La factura esta pagado parcial, por el motivo que sea.
    //    Pagado Total : No se puede modificar. La factura esta pagado total
    // 3) El pago de las facturas de cliente, esta pendiente de gestionar. Lo haremos en vista aparte y la tabla utilizar es fac_cobros.
    // 4) Cada factura tendra una fecha vencimiento, esta puede ser modificada, de entrada pone segun nos indique tipo vencimiento que indicamos
    //    en la ficha del cliente.
    // 5) Empleado es el creo la factura o el ultimo que la modifico.
    // 6) Se crea el temporal al meter un albaran o un producto. Mientras tanto lo btn Guardar y Cancelar no deberían aparecer.
    // 9) Por GET puede venir, sino viene nada es NUEVO
    //          [accion] -> editar,ver.
    //          [id] -> Indica que es una factura guardada.
    //          [tActual] -> Es un temporal ( Esta viene sola , ya accion no tiene.. siempre se puede modificar)
    include_once './../../inicial.php';
	include_once $URLCom.'/modulos/mod_venta/funciones.php';
	include_once $URLCom.'/controllers/Controladores.php';
	include_once ($URLCom.'/controllers/parametros.php');
	include_once $URLCom.'/modulos/mod_cliente/clases/ClaseCliente.php';
    include_once $URLCom.'/modulos/mod_venta/clases/albaranesVentas.php';
    include_once $URLCom.'/modulos/mod_venta/clases/facturasVentas.php';
    include_once $URLCom.'/clases/TiposVencimiento.php';
	$ClasesParametros = new ClaseParametros('parametros.xml');
	$Ccliente=new ClaseCliente();
	$Calbcli=new AlbaranesVentas($BDTpv);
	$Cfaccli=new FacturasVentas($BDTpv);
    $CTipoVencimientos = new TiposVencimientos();
	$Controler = new ControladorComun;     
	$Controler->loadDbtpv($BDTpv);
    // -- Valores por defecto -- //
	$idTemporal=0;  
	$idFactura=0;
	$idCliente=0;
	$nombreCliente="";
	$titulo     = "Factura De Cliente ";
    $formaVenci = "";  // Esta fecha se crea cuando añadimos factura, ya que obtiene tipo vencimiento y le sumamos los dias.
    $accion     = '';
	$estado     = 'Nuevo';
	$fecha      = date('d-m-Y');
	$albaranes  = array();
	$dedonde    = "factura";
    $empleado   = $Usuario; // Por defecto ponemos el usuario
    $errores    = array();
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
   
    // Comprobamos:
    // $_GET['tActual] -> No comprobamos nada, solo asignamos valor idTemporal
    // $_GET['id'] -> Si trae accion y si existe temporal. Si exite temporal advertimos y no dejamos editar.
    if (isset($_GET['tActual'])){
        $idTemporal=$_GET['tActual'];
    } else {
        if (isset($_GET['id'])){
            $idFactura = $_GET['id'];
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
                // Comprobamos temporales para ese idFactura y obtenemos idTemporal si tiene.
                $t= $Cfaccli->TodosTemporal($idFactura);
                if (count($t) > 0){
                    // Existe temporal o temporales
                    $accion = 'ver';
                    if (count($t) >1){
                        // Si hay mas de un temporal ejecutamos comprobaciones para informar del error.
                       $errores[] =$Cfaccli->montarAdvertencia('danger',
                                         '<strong>Existen varios temporales de esta factura !! </strong>  <br> '
                                        );
                    } else {
                        $idTemporal = $t['0']['id']; // Si ponemos valor idTemporal podemos comprobar cuando editamos que estado esta guardado
                        $errores[] =$Cfaccli->montarAdvertencia('warning',
                                         '<strong>Existe un temporal</strong> no permitimos editarlo desde aquí, si quieres editarlo haz <a href="factura.php?tActual='. $idTemporal.'">clic en el temporal</a> '
                                        );
                    }
                }
            }
        }
    }
    // --------------- MONTAMOS DATOS DE NUEVO,TEMPORAL,FACTURA CREADA   -------------   //
    // --- NUEVO --- /
    if ($idTemporal==0 && $idFactura == 0){
        $datosCliente = array ('idClientes' => '',
                              'Nombre'     => ''
                       );
        $productos = array();
    }
   
    // --- TEMPORAL --- /   
    if ($idTemporal>0 && $accion==''){
        // Temporal
        $datosFactura = $Cfaccli->buscarDatosFacturasTemporal($idTemporal);
        if (isset($datosFactura['Numfaccli'])){
            $idFactura = $datosFactura['Numfaccli'];
        }
    } 
	 // ---- FACTURA YA CREADA  -------  //
	if ($idFactura >0 ){
		$datosFactura_guardada              = $Cfaccli->datosFactura($idFactura);//Extraemos los datos de la factura
    	$productosFactura                   = $Cfaccli->ProductosFactura($idFactura);//De los productos
		$productosMod                       = modificarArrayProductos($productosFactura);
		$datosFactura_guardada['Productos'] = json_encode($productosMod);
        $albsFactura_creada                 = $Cfaccli->obtenerAlbaranesFactura($idFactura);
        $datosFactura_guardada['Albaranes'] = json_encode($albsFactura_creada['Items']);
		$incidenciasAdjuntas                = incidenciasAdjuntas($idFactura, "mod_ventas", $BDTpv, "factura");
		$inciden                            = count($incidenciasAdjuntas['datos']);
        if ($idTemporal == 0){
            // Si no es temporal, entonces tenemos crear $datosFactura.
            $datosFactura = $datosFactura_guardada;
            $estado=$datosFactura['estado'];
        }
	}
    if ( isset($datosFactura)){
        // Esto es lo comun cuando es temporal o cuando es factura existente.
        $idCliente = $datosFactura['idCliente'];
        $albaranes = json_decode($datosFactura['Albaranes'],true);
        $productos = json_decode($datosFactura['Productos']) ;
        $Datostotales = recalculoTotales($productos); // Necesita un array de objetos.
        $productos = json_decode($datosFactura['Productos'], true); // Convertimos en array de arrays
        $fecha =date_format(date_create($datosFactura['Fecha']), 'd-m-Y');
    }
    //~ echo '<pre>';
    //~ print_r($datosFactura['Albaranes']);;
    //~ echo '</pre>';
    // ---- Compromamos si existe la factura que el estado sea Sin guardar --- //
    if ($idFactura >0){
        $estado = $Cfaccli->getEstado($idFactura);
        if ($idTemporal >0 &&  $estado !='Sin guardar'){
                // Hay un error, hay que informarlo
                $errores[] =$Cfaccli->montarAdvertencia('danger',
                                             'Existe un temporal y su factura, pero el <strong>estado de factura es distinto al sin guardar</strong>. Avisa al administrador del sistema.'
                                            );
        }
    }

    if ($idCliente > 0){
        $datosCliente=$Ccliente->getCliente($idCliente);
        if ( isset($datosCliente['datos'])){
            $datosCliente  = $datosCliente['datos']['0'];
            // Como en tabla en clientes idCliente es idCLientes (algo que está muy mal.. :-) lo tenemos crear.
            $idCliente = $datosCliente['idClientes'];
            // Obtenemos tipo de vencimiento.
            $V = json_decode($datosCliente['formasVenci']);
            $idFormaVencimiento = $V->vencimiento;
        } else {
            $errores[]=array ( 'tipo'=>'Danger!',
                                         'datos' => json_encode($datosCliente),
                                         'class'=>'alert alert-danger',
                                         'mensaje' => 'No se encuentra datos del cliente de la factura con id'.$idCliente
                                         );
        }
    }
    // Montar select tipo vencimiento.
    
    // ---  Pulso Guardar --- //
    if (count($errores) == 0){ 
        if (isset($_POST['Guardar']) && $accion !='ver' && $idTemporal >0){
            // Si entramos aquí es porque existe temporal, porque no esta editando y pulso guardar.
            $estado="Guardado"; // Cambiamos estado a guardado.
          
            if ($idFactura > 0){
                // Tiene numero de factura , entonces cre
                
            }
            // ---- Comprobamos fechas antes guardar --- //
            $comprobaciones = array();
            
            echo '<pre>';
            if ($fecha == true){
                echo 'fecha correcta<br/>';
                echo $fecha;
            }
            echo '</pre>';
            echo '<pre>';
            print_r($datosFactura_guardada);
            echo '</pre>';
            echo '<pre>';
            print_r($datosFactura);
            echo '</pre>';
            
            $fecha=date_format(date_create($_POST['fecha']), 'Y-m-d');
            $datos=array(
                'Numtemp_faccli'=>$idTemporal,
                'Fecha'=>$fecha,
                'idTienda'=>$Tienda['idTienda'],
                'idUsuario'=>$Usuario['id'],
                'idCliente'=>$idCliente,
                'estado'=>$estado,
                'total'=> $Datostotales['total'],
                'DatosTotales'=>$Datostotales,
                'productos'=>$datosFactura['Productos'],
                'albaranes'=>$datosFactura['Albaranes'],
                'fechaCreacion'=>date('Y-m-d'),
                'fechaVencimiento'=>$_POST['fechaVenci'],
                'fechaModificacion'=>date('Y-m-d')
                );
            
            //~ if($datosFactura['Numfaccli']>0){
                //~ $idFactura=$datosFactura['Numfaccli'];
                //~ $eliminarTablasPrincipal=$Cfaccli->eliminarFacturasTablas($idFactura);
                //~ if (isset($eliminarTablasPrincipal['error'])){
                //~ $errores[]=array ( 'tipo'=>'Danger!',
                                             //~ 'dato' => $eliminarTablasPrincipal['consulta'],
                                             //~ 'class'=>'alert alert-danger',
                                             //~ 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
                                             //~ );
                //~ }
            //~ }
            //~ if(count($errores)==0){
                //~ $addNuevo=$Cfaccli->AddFacturaGuardado($datos, $idFactura);
                //~ if (isset($addNuevo['error'])){
                //~ $errores[]=array ( 'tipo'=>'Danger!',
                                             //~ 'dato' => $addNuevo['consulta'],
                                             //~ 'class'=>'alert alert-danger',
                                             //~ 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
                                             //~ );
                //~ }else{
                    //~ $eliminarTemporal=$Cfaccli->EliminarRegistroTemporal($idTemporal, $idFactura);
                    //~ if (isset($eliminarTemporal['error'])){
                    //~ $errores[]=array ( 'tipo'=>'Danger!',
                                             //~ 'dato' => $eliminarTemporal['consulta'],
                                             //~ 'class'=>'alert alert-danger',
                                             //~ 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
                                             //~ );
                     //~ }
                //~ }                    
            //~ }
            //~ if(count($errores)>0){
                //~ foreach($errores as $error){
                    //~ echo '<div class="'.$error['class'].'">'
                    //~ . '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br>Sentencia: '.$error['dato']
                    //~ . '</div>';
                //~ }
            //~ }else{
                //~ //  Redireccionamos a listado facturas una vez guardado correctamente.
                //~ header('Location: facturasListado.php');
            //~ }
        }
    }
    // --- Montamos html_albaranes y convertimos albaranes a adjunto --- //
    $html_adjuntos= '';
    foreach ($albaranes as $k=>$albaran){
        if($idTemporal == 0){
            $albaran = prepararCaberaAdjuntoTemporal($albaran,$dedonde);
            //Tengo añadirle el numero fila
            $albaran['nfila'] =intval($k)+1; // Sumamos uno ya que empieza en 0
        }
        $html_adjuntos .= htmlLineaAdjunto($albaran, "factura");
    }

    // ---  Controlamos cuando poner solo lectura o display none los campos --- //
    $display = '';
    $readonly = '';
    $readonly_cliente = '';
    if ($accion == 'ver'){
        $display = 'style="display:none"';
        $readonly = 'readonly';
        $readonly_cliente = 'readonly';
    }
    // --- html de titulo --- /
    $n = 'Sin Guardar';
    if ($idFactura > 0) {
        $n = $idFactura;
    }
    $html_numero = '<span>'.$n.'</span>';
    if ($idTemporal > 0){
        $a = 'TEMPORAL'.'<span class="glyphicon glyphicon-info-sign" title="Numero temporal:'.$idTemporal.'"></span><input type="text" style="display:none;" name="idFactura" value="'.$idTemporal.'">';
        $readonly_cliente = 'readonly';
    } else {
        $a = $accion;
    }
    $html_accion = '<span>'.$a.'</span>';

?>
<!DOCTYPE html>
<html>
<head>
<?php include $URLCom.'/head.php'; ?>
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
    // Cabecera - Donde guardamos idCliente, idUsuario,idTienda,FechaInicio,FechaFinal.
	var cabecera = []; 
		cabecera['idUsuario'] = <?php echo $Usuario['id'];?>; 
		cabecera['idTienda'] = <?php echo $Tienda['idTienda'];?>; 
		cabecera['estado'] ='<?php echo $estado ;?>'; 
		cabecera['idTemporal'] = <?php echo $idTemporal ;?>;
		cabecera['idReal'] = <?php echo $idFactura ;?>;
		cabecera['fecha'] = '<?php echo $fecha ;?>';
		cabecera['idCliente'] = <?php echo $idCliente;?>;
	var productos = []; 
	var albaranes =[];
</script>
<?php 
if ($idTemporal > 0 || $idFactura > 0 ){?>
    <script type="text/javascript">
        <?php
        //Introducimos los productos a la cabecera productos
       
		if (isset($productos)){
            
			foreach($productos as $i =>$product){?>	
				datos=<?php echo json_encode($product); ?>;
				productos.push(datos);
            <?php 
            }
		}
		if (isset($albaranes)){
			foreach ($albaranes as $alb){?>
				datos=<?php echo json_encode($alb);?>;
				albaranes.push(datos);
            <?php
			}
		}
    ?>
    </script>
<?php
}	
?>
<script type="text/javascript">
	<?php
	if (isset($_POST['Cancelar'])){
	?>
        cancelarTemporal(<?php echo $idTemporal;?>, <?php echo "'".$dedonde."'"; ?>);
    <?php
	}
	echo $VarJS;?>
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
		}
        // Por si falta maquetar errores.
        echo '<pre>';
        print_r($errores);
        echo '</pre>';
	}

    ?>
    <?php
        // Montamos html de titulo
        echo '<h2 class="text-center">'.$titulo.$html_numero.'-'.$html_accion.'</h2>' ;?>
    
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
                <input type="submit"  class="btn btn-primary" <?php echo $display;?> value="Guardar" id="Guardar" name="Guardar">
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
                
                <input type="submit" class="btn btn-danger" value="Cancelar Temporal" id="Cancelar" name="Cancelar">
            </div>
<div class="col-md-12" >
	<div class="col-md-8">
		<div class="col-md-12">
            <div class="col-md-2">
                <strong>Fecha Fact:</strong><br>
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
            
            <div class="col-md-3" id="tiposVencimientos">
                <label class="text-center">Tipo vencimiento:</label>
                <select name='formaTipoVencimiento' id='formaTipoVencimiento' onChange='selectFormas()'>
                    <?php echo $htmlFormasPago;?>
                </select>
            </div>
            <div class="col-md-3" id="fechaVencimiento">
                <label class="text-center">Fecha vencimiento:</label>
                <input type="date" name="fechaVencimiento" id="fechaVencimiento" size="10" data-obj= "cajaFecha"  value="<?php echo $fechaVencimiento;?>" onkeydown="controlEventos(event)" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" placeholder='dd-mm-yyyy' <?php echo $readonly;?> title=" Formato de entrada dd-mm-yyyy">
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
            <label  id="numAlbaranT">Número del albaran:</label>
            <input  type="text" id="numAlbaran" name="numAlbaran" value="" size="5" placeholder='Num' <?php echo $readonly;?> data-obj= "numAlbaran" onkeydown="controlEventos(event)">
            <a  id="buscarAlbaran" class="glyphicon glyphicon-search buscar" onclick="buscarAdjunto('factura')"></a>
            <table  class="col-md-12"  id="tablaAlbaran"> 
                <thead>
                    <td><b>Número</b></td>
                    <td><b>Fecha</b></td>
                    <td><b>Total</b></td>
                </thead>
                <?php echo $html_adjuntos;?>
            </table>
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
</div>
</form>
</div>
<?php // Incluimos paginas modales
echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
include $RutaServidor.'/'.$HostNombre.'/plugins/modal/ventanaModal.php';
?>

</body>
</html>
