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
	$ClasesParametros = new ClaseParametros('parametros.xml');
	$Ccliente=new ClaseCliente();
	$Calbcli=new AlbaranesVentas($BDTpv);
	$CFac=new FacturasVentas($BDTpv);
	$Controler = new ControladorComun;     
	$Controler->loadDbtpv($BDTpv);
    // -- Valores por defecto -- //
	$idTemporal=0;  
	$idFactura=0;
	$idCliente=0;
	$titulo     = "Factura De Cliente ";
    $accion     = '';
	$estado     = 'Nuevo';
	$fecha      = date('d-m-Y');
	$albaranes  = array();
	$dedonde    = "factura";
    $empleado   = $Usuario; // Por defecto ponemos el usuario
    $formasVenci= $Ccliente->getVencimientos();
    $errores    = array();
    $parametros = $ClasesParametros->getRoot();
    $inciden= "";
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
                $t= $CFac->TodosTemporal($idFactura);
                if (count($t) > 0){
                    // Existe temporal o temporales convertimos accion en ver
                    $accion = 'ver';
                    if (count($t) >1){
                        // Si hay mas de un temporal ejecutamos comprobaciones para informar del error.
                       $errores[] =$CFac->montarAdvertencia('danger',
                                         '<strong>Existen varios temporales de esta factura !! </strong>  <br> '
                                        );
                    } else {
                        $errores[] =$CFac->montarAdvertencia('warning',
                                         '<strong>Existe un temporal</strong> no permitimos editarlo desde aquí, si quieres editarlo haz <a href="factura.php?tActual='. $t['0']['id'].'">clic en el temporal</a> '
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
                              'Nombre'     => '',
                              'formasVenci'=> '{"vencimiento":"0"}'
                       );
        $productos = array();
    }
   
    // --- TEMPORAL --- /   
    if ($idTemporal>0 && $accion==''){
        // Temporal
        $datosFactura = $CFac->buscarDatosTemporal($idTemporal);
        if (isset($datosFactura['Numfaccli'])){
            $idFactura = $datosFactura['Numfaccli'];
        }
    }
    
	// ---- FACTURA YA CREADA  -------  //
    // Entra tanto cuando al id de Factura , como viene de temporal y esta tiene idFactura.
	if ($idFactura >0 ){
		$datosFactura_guardada              = $CFac->datosFactura($idFactura);//Extraemos los datos de la factura
    	$productosFactura                   = $CFac->ProductosFactura($idFactura);//De los productos
		$productosMod                       = modificarArrayProductos($productosFactura);
		$datosFactura_guardada['Productos'] = json_encode($productosMod);
        $albsFactura_creada                 = $CFac->obtenerAlbaranesFactura($idFactura);
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

    // ---- Compromamos si existe la factura que el estado sea Sin guardar --- //
    
    if ($idFactura >0){
        $estado = $CFac->getEstado($idFactura);
        if ($idTemporal >0 &&  $estado !='Sin guardar'){
                // Hay un error, hay que informarlo
                $errores[] =$CFac->montarAdvertencia('danger',
                                             'Existe un temporal y su factura, pero el <strong>estado de factura es distinto al sin guardar</strong>. Avisa al administrador del sistema.'
                                            );
                //[DEBUG]
                // Si se produce este error, era bueno que al administrador se le permita Guardar.
                // Permitir guardar aunque haya este error y ya no muestra el btn.
                // Si quieres guardarlo, comenta este if y te deja guardarlo con en mismo numero.
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
            $errores[]= $CFac->montarAdvertencia('danger',
                                             'No se encuentra datos del cliente de la factura con id'.$idCliente.'</br/>'
                                             .json_encode($datosCliente)
                                            );
        }
    }
    
    // ---  Pulso Guardar --- //
    if (isset($_POST['Guardar'])) {
        if (count($errores) == 0){ 
            if ( $accion !='ver' && $idTemporal >0){
                // Si entramos aquí es porque existe temporal y pulso guardar.
                // ---- Comprobamos fechas antes guardar --- //
                $f= explode('-',$_POST['fecha']);
                $validar_fecha = checkdate($f['1'],$f['0'],$f['2']);
                
                if ($validar_fecha ==true){
                   $fecha_post = $f['2'].'-'.$f['1'].'-'.$f['0'];
                } else {
                    // Si la fecha no es correcta.
                     $errores[] =$CFac->montarAdvertencia('warning',
                                                 'La fecha que envia POST no es correcta.Fecha:'.$_POST['fecha']
                                                );
                }
                $datos=array(
                    'Numtemp_faccli'=>$idTemporal,
                    'Fecha'=>$fecha_post,
                    'idTienda'=>$Tienda['idTienda'],
                    'idUsuario'=>$Usuario['id'],
                    'idCliente'=>$idCliente,
                    'estado'=>"Guardado",
                    'total'=> $Datostotales['total'],
                    'DatosTotales'=>$Datostotales,
                    'productos'=>$datosFactura['Productos'],
                    'albaranes'=>$datosFactura['Albaranes'],
                    'fechaCreacion'=>date('Y-m-d'),
                    'fechaVencimiento'=>$_POST['fechaVencimiento'],
                    'fechaModificacion'=>date('Y-m-d')
                    );
               
                if(count($errores)==0){
                    if($datosFactura['Numfaccli']>0){
                        $idFactura=$datosFactura['Numfaccli'];
                        $eliminarTablasPrincipal=$CFac->eliminarFacturasTablas($idFactura);
                        if (isset($eliminarTablasPrincipal['error'])){
                            $errores[] =$CFac->montarAdvertencia('danger',
                                                 'ERROR EN LA BASE DE DATOS AL BORRAR factura!'.'<br/> Consulta:'.$eliminarTablasPrincipal['consulta']
                                                 .'<br/>Error:'.$eliminarTablasPrincipal['error'].'<br/>'
                                                );
                        }
                    }
                }
                if(count($errores)==0){
                    $addNuevo=$CFac->AddFacturaGuardado($datos, $idFactura);
                    if (isset($addNuevo['errores'])){
                        foreach ($addNuevo['errores'] as $error){
                            $errores[]=$CFac->montarAdvertencia('Danger!',
                                                          'Error al añadir factura y guardarla.<br/>'
                                                             .'Error:'.$error['error'].'<br/>'
                                                             .'Consulta:'.$error['consulta'].'<br/>'
                                                             );
                        }
                    } else {
                        $eliminarTemporal=$CFac->EliminarRegistroTemporal($idTemporal, $idFactura);
                        if (isset($eliminarTemporal['error'])){
                        $errores[]=$CFac->montarAdvertencia('danger',
                                                 'Error al eliminar temporal:'.$idTemporal
                                                 .'Error: '.$eliminarTemporal['error'].'<br/>'
                                                 .'Consulta:'.$eliminarTemporal['consulta'].'<br/>'
                                                 );
                         }
                    }
                }
                if(count($errores) == 0){
                    //  Redireccionamos a listado facturas una vez guardado correctamente.
                    header('Location: facturasListado.php');
                } else {
                     // Si hay error es una de las 3 consultas que hace para eliminar datos de la factura de varias tablas.
                            // devuelve un array con error y consulta. Entonces hay que ver cual elimino o no .

                            // --- Ahora pongo valores post, para crear temporal --/
                            // Esto debería se una funcion, para poder reutilizar en en debug.
                            $_POST['idTemporal']    = 0; // Ya queremos sea un temporal nuevo.
                            $_POST['idUsuario']     = $Usuario['id'];
                            $_POST['idTienda']      = $Tienda['idTienda'];
                            $_POST['idReal']        = $datosFactura['Numfaccli'];
                            $_POST['productos']     = $datosFactura_guardada['Productos'];
                            $_POST['idCliente']     = $datosFactura_guardada['idCliente'];
                $_POST['dedonde']       = $dedonde;
                            // Los albaranes de factura directa hay que prepararlos para ser un adjunto
                            $pAdjuntos = prepararAdjuntos(json_decode($datosFactura_guardada['Albaranes'],true),$dedonde,$accion);
                            $_POST['albaranes']     = $pAdjuntos['adjuntos'];
                            // Para añadir el temporal de copia de los datos si hubo error.
                            include_once $URLCom.'/modulos/mod_venta/tareas/AddFacturaTemporal.php';
                            $errores[]=$CFac->montarAdvertencia('danger',
                                                'HUBO ERROR AL GRABAR !! <br/>'
                                                .'El error, es el o los anteriores.</br>'
                                                .'Creamos un temporal nuevo con los datos factura guardada para recuperarlos, idTemporal:'.$respuesta['id']
                                                .'<br/> Avisa al administrador de sistema, no cierres pantalla.'
                                                );

                }
            }
        } else {
            // Pulso guardar, pero hay errores anteriores.
            $errores[]=$CFac->montarAdvertencia('warning',
                                                'Pulsaste GUARDAR, pero hay errores anteriores que hace que no ejecutemos.'
                                                 );
        }
    }
    // --- Montamos html_adjuntos y variable adjunto que utilizamos para montar variable JS --- //
    $html_adjuntos= '';
    $adjuntos = array();
    if ($idTemporal>0){
        // No hay accion , por lo que tenemos editar
        $accion = 'editar';
    }
    $pAdjuntos = prepararAdjuntos($albaranes,$dedonde,$accion);
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
    if ($idFactura > 0 ){
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
    // -- Obtenemos options de vencimiento y vencimiento por defecto.
    $array_venci = json_decode($datosCliente['formasVenci']);
    $html_options_vencimiento =  htmlOptions($formasVenci,$array_venci->vencimiento);
    if ($datosCliente['idClientes'] > 0){
        if ($datosFactura['fechaVencimiento'] ==null || $datosFactura['fechaVencimiento']< $datosFactura['Fecha'] ) {
            // Si no tiene fecha vencimiento , pues la generamos.
            $dias_vencimiento = $Ccliente->getVencimiento($array_venci->vencimiento);
            $fechaVencimiento = fechaVencimiento($datosFactura['Fecha'], $dias_vencimiento);
        } else  {
            $fechaVencimiento = fechaVencimiento($datosFactura['fechaVencimiento'],0); // Asi la convierto al formato.
        }
    } else {
        $fechaVencimiento = date('Y-m-d');
    }
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
        cabecera['accion'] ='<?php echo $accion ;?>';
		cabecera['idTemporal'] = <?php echo $idTemporal ;?>;
		cabecera['idReal'] = <?php echo $idFactura ;?>;
		cabecera['fecha'] = '<?php echo $fecha ;?>';
		cabecera['idCliente'] = <?php echo $idCliente;?>;
	var productos = []; 
	var adjuntos =[];
<?php 
if ($idTemporal > 0 || $idFactura > 0 ){?>
        <?php
        //Introducimos los productos a la cabecera productos
       
		if (isset($productos)){
			foreach($productos as $i =>$product){?>	
				datos=<?php echo json_encode($product); ?>;
				productos.push(datos);
            <?php 
            }
		}
		if (isset($adjuntos)){
			foreach ($adjuntos as $adjunto){?>
				datos=<?php echo json_encode($adjunto);?>;
				adjuntos.push(datos);
            <?php
			}
		}
}
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
                    <?php echo $html_options_vencimiento;?>
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
			if (isset($productos)){
				foreach (array_reverse($productos) as $producto){
                    $html=htmlLineaProductos($producto, "factura", $accion);
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
