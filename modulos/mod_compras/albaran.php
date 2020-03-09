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
	//Carga de clases necesarias
	$ClasesParametros = new ClaseParametros('parametros.xml');
	$Cproveedor=new Proveedores($BDTpv);
	$CAlb=new AlbaranesCompras($BDTpv);
	$Cped = new PedidosCompras($BDTpv);
	$Controler = new ControladorComun; 
	$Controler->loadDbtpv($BDTpv);
	//Inicializar las variables
	$dedonde="albaran";
	$titulo="Albarán de proveedor: ";
	// Valores por defecto de estado y accion.
    // [estado] -> Nuevo,Sin Guardar,Guardado,Facturado.
    // [accion] -> editar,ver
    $estado='Nuevo';
    // Si existe accion, variable es $accion , sino es "editar"
    $accion = (isset($_GET['accion']))? $_GET['accion'] : 'editar';
	$fecha=date('d-m-Y');
	$idAlbaranTemporal=0;
    $idAlbaran=0;
    $idProveedor="";
    $nombreProveedor="";
    $Datostotales=array();
	$errores = array();
    $creado_por = array(); 
    $hora="";
    $formaPago= 0;
	$suNumero="";
	$fechaVencimiento="";
    $pedido_html_linea_productos = array();
    $JS_datos_pedidos = '';
    $html_adjuntos = '';
	//Cargamos la configuración por defecto y las acciones de las cajas 
	$parametros = $ClasesParametros->getRoot();	
	foreach($parametros->cajas_input->caja_input as $caja){
        // Ahora cambiamos el parametros por defecto que tiene dedonde = pedido y le ponemos albaran
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
    // Por GET recibimos uno o varios parametros:
    //  [id] cuando editamos o vemos un albaran pulsando en listado.
    //  [tActual] cuando pulsamos en cuadro albaranes temporales.
    //  [accion] cuando indicamos que accion vamos hacer.
    if (isset($_GET['id'])){
        $idAlbaran=$_GET['id'];  // Id real de pedido
    }
    if (isset($_GET['tActual'])){
        $idAlbaranTemporal=$_GET['tActual']; // Id de albaran temporal
    }
    // ---------- Posible errores o advertencias mostrar     ------------------- //

    if ($idAlbaran > 0){
        // Comprobamos cuantos temporales tiene idPedido y si tiene uno obtenemos el numero.
        $c = $CAlb->comprobarTemporalIdAlbpro($idAlbaran);
        if (isset($c['idTemporal']) && $c['idTemporal'] !== NULL){
            // Existe un temporal de este pedido por lo que cargo ese temporal.
            $idAlbaranTemporal = $c['idTemporal'];
            $idAlbaran = 0 ; // Lo pongo en 0 para ejecute la parte temporal
            $_GET['tActual'] = $idAlbaranTemporal;

            if ($accion !== 'temporal' && $accion !=='ver'){
                // Si entro sin accion temporal, NO PERMITO EDITAR.
                // YA PROVABLEMENTE ESTAN EDITANDO.
                $accion = 'ver';
                // Creo alert
                echo '<script>alert("No se permite editar, ya que alguien esta editandolo, hay un temporal");</script>';
            }
        } else {
            if (count($c)>0){
                 $errores= $c;
            }
        }
    }
    if ( $idAlbaran > 0 && count($errores) === 0){
        // Si existe id y no hay errores estamos modificando directamente un albaran.
        $datosAlbaran = $CAlb->GetAlbaran($_GET['id']);
        if (isset($datosAlbaran['error'])){
            $errores=$datosAlbaran['error'];
        } else {
            if(isset($datosAlbaran['estado']) ){
                $estado=$datosAlbaran['estado'];
                $idAlbaran = $datosAlbaran['id'];
                if ($datosAlbaran['estado']=="Facturado"){
                    // Cambiamos accion, ya que solo puede ser ver.
                    $accion = 'ver';
                    // Obtenemos los datos de factura.
                    $numFactura=$CAlb->NumfacturaDeAlbaran($idAlbaran);
                    if(isset($numFactura['error'])){
                        array_push($errores,$CAlb->montarAdvertencia(
                                        'danger',
                                        'Error 1.1 en base datos.Consulta:'.json_encode($numFactura['consulta'])
                                )
                        );
                    }
                }
            }
        }
    }
    if ($idAlbaranTemporal > 0 && count($errores) === 0){
        // Puede entrar cuando :
        //   -Viene de albaran temporal
        //   -Se recargo mientras editamos.
        //   -Cuando pulsamos guardar.
        $datosAlbaran=$CAlb->buscarAlbaranTemporal($idAlbaranTemporal);
        if (isset($datosAlbaran['error'])){
                array_push($errores,$CAlb->montarAdvertencia(
                                'danger',
                                'Error 1.1 en base datos.Consulta:'.json_encode($datosAlbaran['consulta'])
                        )
                );
        } else {
            // Preparamos datos que no viene o que vienen distintos cuando es un temporal.
            $datosAlbaran['Productos'] = json_decode($datosAlbaran['Productos'],true);
            $idAlbaran = $datosAlbaran['Numalbpro'];
            $estado=$datosAlbaran['estadoAlbPro'];
            $datosAlbaran['FechaVencimiento'] ='0000-00-00';

        }
    }
    if (count($errores) == 0){
        // Si no hay errores graves continuamos.
        if (!isset($datosAlbaran)){
            // SI es NUEVO.
            $datosAlbaran = array();
            $datosAlbaran['Fecha']="0000-00-00 00:00:00";
            $datosAlbaran['Su_numero'] = '';
            $datosAlbaran['idProveedor'] = 0;
            $creado_por = $Usuario;
        } else {
            // No es NUEVO
            $idProveedor=$datosAlbaran['idProveedor'];
            $proveedor=$Cproveedor->buscarProveedorId($idProveedor);
            $nombreProveedor=$proveedor['nombrecomercial'];
            $productos =$datosAlbaran['Productos'];
            $fecha = ($datosAlbaran['Fecha']=="0000-00-00 00:00:00")
                                ? date('d-m-Y'):date_format(date_create($datosAlbaran['Fecha']),'d-m-Y');
            $hora=date_format(date_create($datosAlbaran['Fecha']),'H:i');
            $creado_por = $CAlb->obtenerDatosUsuario($datosAlbaran['idUsuario']);
            $formaPago=(isset($datosAlbaran['formaPago']))? $datosAlbaran['formaPago'] : 0;
            $fechaVencimiento=$datosAlbaran['FechaVencimiento'];
            if (isset ($datosAlbaran['Numalbpro'])){
                $d=$CAlb->buscarAlbaranNumero($datosAlbaran['Numalbpro']);
                $idAlbaran=$d['id'];
                // Debemos saber si debemos tener incidencias para ese albaran, ya que el boton incidencia es distinto.
                $incidencias=incidenciasAdjuntas($idAlbaran, "mod_compras", $BDTpv, $dedonde);
            }
            if ($datosAlbaran['Su_numero']!==""){
                $suNumero=$datosAlbaran['Su_numero'];
            }
            if (isset($datosAlbaran['Pedidos'])){
            // Un albaran ya viene con pedidos, si tiene. Puede venir JSON si es temporal
                if ($idAlbaranTemporal >0){
                    // Cuando viene de tActual obtenemos .
                    // Solo convertimos $idAlbaranTemporal >0 , ya que es cuando viene json
                    $datosAlbaran['Pedidos'] = json_decode($datosAlbaran['Pedidos'],true);
                }
                if (count($datosAlbaran['Pedidos'])>0){
                    // Ahora obtengo todos los datos de ese pedido.
                    foreach ($datosAlbaran['Pedidos'] as $key =>$pedido){
                        // Cuando los pedidos adjuntos los cargo con el metodo $CAlb->PedidosAlbaranes 
                        // ========             Ahora obtenemos todos los datos         ======== //
                        if ( isset($pedido['idPedido'])){
                            $idPedido = $pedido['idPedido'];
                        } else {
                            // Entra aquí cuando se añadio a albarantemporal un pedido, pero no se guardo, solo creo temporal.
                            $idPedido = $pedido['idAdjunto'];
                            $datosAlbaran['Pedidos'][$key]['idPedido'] =$idPedido; 
                        }
                        $e = $Cped->datosPedido($idPedido);
                        // El indice 'estado' es el estado del pedido que puede ser "Sin Guardar", "Guardado","Facturado"
                        // Ahora vamos a crear el estado del adjunto, pero teniendo en cuenta
                        // Que si estado_pedido es "Sin Guardar" tenemos que enviar un error.
                        // Si estado_pedido es "Guardado" entonces el estado adjunto es 'Eliminado'.
                        // Si estado_pedido es "Facturado" entonces el estado ajunto es 'activo'.
                        if ($e['estado'] === 'Facturado'){
                            $estado_adjunto = 'activo';
                        } else {
                            $estado_adjunto = 'Eliminado';
                            if ($e['estado'] !== 'Guardado'){
                                // Informo posible error, ya que el estado pedido no es Guardado , ni Facturado..
                                array_push($errores,$CAlb->montarAdvertencia(
                                    'dannger',
                                    'Posible error, el pedido con id:'.$idPedido.' tiene estado '.$e['estado'])
                                );
                            }
                        }
                        $datosAlbaran['Pedidos'][$key]['estado'] = $estado_adjunto;
                        $datosAlbaran['Pedidos'][$key]['fecha'] = $e['Fecha'];
                        $datosAlbaran['Pedidos'][$key]['total_siniva'] = $e['total_siniva'];
                        $datosAlbaran['Pedidos'][$key]['total'] = $e['total'];
                        $datosAlbaran['Pedidos'][$key]['NumAdjunto'] = $e['Numpedpro'];
                        $datosAlbaran['Pedidos'][$key]['idAdjunto'] = $idPedido;
                        $datosAlbaran['Pedidos'][$key]['nfila'] = $key+1;
                        // ========                 JS_datos_pedidos                    ======== //
                        $JS_datos_pedidos .=  'datos='.json_encode($datosAlbaran['Pedidos'][$key]).';'
                                            .'pedidos.push(datos);';
                        // ========               $html_adjuntos                        ======== //
                        $h =lineaAdjunto($datosAlbaran['Pedidos'][$key], "albaran",$accion);
                        $html_adjuntos .= $h['html'];
                        // ========  Array para mostrar en lineas productos de adjuntos ======== //
                        $h =htmlDatosAdjuntoProductos($datosAlbaran['Pedidos'][$key],$dedonde);
                        $pedido_html_linea_producto[$idPedido] = $h;
                    }
                }
            }
            
        }
        // Cargamos forma pago y ponemos seleccina si tiene.
        $textoFormaPago=htmlFormasVenci($formaPago, $BDTpv); // Generamos ya html.
        if(isset($datosAlbaran['Productos'])){
			// Obtenemos los datos totales ;
			// convertimos el objeto productos en array
            $p = (object)$productos;
            $Datostotales = $CAlb->recalculoTotales($p);
        }
        
    }
	if (isset($_POST['Guardar'])){
		//@Objetivo:
        // Guardar los datos que recibimos.
        // todo fue OK , pero sino mostramos el error.
        if ($_POST['fechaVenci'] === ''){
            $_POST['fechaVenci'] = '0000-00-00';
        }
        $guardar=$CAlb->guardarAlbaran($Datostotales);
       
		if (count($guardar)==0){
			header('Location: albaranesListado.php');
		}else{
            // Hubo errores o advertencias.
			foreach ($guardar as $error){
				array_push($errores,$error);
			}
		}
	}
    // ============                 Montamos el titulo                      ==================== //
    $html_facturado='';
    if(isset($numFactura)){
        $html_facturado = ' <span style="font-size: 0.55em;vertical-align: middle;" class="label label-default">';
        $html_facturado .= 'factura:'.$numFactura['idFactura'];
        $html_facturado .='</span>';
    }
    $titulo .= ' '.$idAlbaran.$html_facturado.' - '.$accion;
    // ============= Creamos variables de estilos para cada estado y accion =================== //
    $estilos = array ( 'readonly'       => '',
                       'styleNo'        => 'style="display:none;"',
                       'pro_readonly'   => '',
                       'pro_styleNo'    => '',
                       'btn_guardar'    => '',
                       'btn_cancelar'   => '',
                       'input_factur'   => '',
                       'select_factur'  => ''
                    );
    if (isset ($_GET['id']) || isset ($_GET['tActual'])){
        // Quiere decir que ya inicio , ya tuvo que meter proveedor.
        // no se permite cambiar proveedor.
        $estilos['pro_readonly']   = ' readonly';
        $estilos['pro_styleNo']    = ' style="display:none;"';
        $estilos['styleNo']    = '';
        $evento_cambio = 'onchange ="addTemporal('."'".$dedonde."'".')"'; // Lo utilizo para crear temporal cuando cambia valor.

    }
    if ($accion === 'ver'){
        $estilos['readonly']   = ' readonly';
        $estilos['styleNo']     = ' style="display:none;"';
        $estilos['input_factur'] = ' readonly';
        $estilos['select_factur'] = 'disabled="true"';       
    }
    if ($idAlbaranTemporal === 0){
        // Solo se muestra cuando el idAlbaranTemporal es 0
        $estilos['btn_guardar'] = 'style="display:none;"';
        // Una vez se cree temporal, con javascript se quita style
    }
?>
	<script type="text/javascript">
	// Esta variable global la necesita para montar la lineas.
	// En configuracion podemos definir SI / NO
	<?php echo 'var configuracion='.json_encode($configuracionArchivo).';';?>	
	var cabecera = []; // Donde guardamos idCliente, idUsuario,idTienda,FechaInicio,FechaFinal.
		cabecera['idUsuario'] = <?php echo $creado_por['id'];?>; // Tuve que adelantar la carga, sino funcionaria js.
		cabecera['idTienda'] = <?php echo $Tienda['idTienda'];?>; 
		cabecera['estado'] ='<?php echo $estado ;?>'; // Si no hay datos GET es 'Nuevo'
		cabecera['idTemporal'] = <?php echo $idAlbaranTemporal ;?>;
		cabecera['idReal'] ='<?php echo $idAlbaran ;?>';
		cabecera['idProveedor'] ='<?php echo $idProveedor;?>';
		cabecera['fecha'] = '<?php echo $fecha;?>';
		cabecera['hora'] = '<?php echo $hora;?>';
		cabecera['suNumero']='<?php echo $suNumero; ?>';
		 // Si no hay datos GET es 'Nuevo';
	var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array
	var pedidos =[];
<?php
	if (isset($idAlbaranTemporal)|| isset($idAlbaran)){
		if (isset($productos)){
			foreach($productos as $k =>$product){
?>	
                datos=<?php echo json_encode($product); ?>;
                productos.push(datos);
<?php 
                // cambiamos estado y cantidad de producto creado si fuera necesario.
				if ($product['estado'] !== 'Activo'){
				?>	productos[<?php echo $k;?>].estado=<?php echo'"'.$product['estado'].'"';?>;
				<?php
				}
			}
		}
		if (isset ($datosAlbaran['Pedidos'])){
            if ($JS_datos_pedidos != ''){
                echo $JS_datos_pedidos;
            }
		}
	}	
?>
</script>
</head>
<body>
	<script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
    <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_compras/js/AccionesDirectas.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_compras/funciones.js"></script>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
<?php
    include_once $URLCom.'/modulos/mod_menu/menu.php';
?>
<script type="text/javascript">
	<?php
	if (isset($_POST['Cancelar'])){
	?>
        mensajeCancelar(<?php echo $idAlbaranTemporal;?>, <?php echo "'".$dedonde."'"; ?>);
    <?php
	}
    echo $VarJS;
    ?>
    function anular(e) {
        tecla = (document.all) ? e.keyCode : e.which;
        return (tecla != 13);
    }
</script>
<div class="container">
	<?php
	if (isset($errores)){
        foreach ($errores as $comprobaciones){
            echo $CAlb->montarAdvertencia($comprobaciones['tipo'],$comprobaciones['mensaje'],'OK');
            if ($comprobaciones['tipo'] === 'danger'){
                exit; // No continuo.
            }
        }
    }
    ?>
    <form action="" method="post" name="formProducto" onkeypress="return anular(event)">
    <?php 
    echo '<h3 class="text-center">'.$titulo;
    if ($accion !=='ver'){
        echo ' temporal:'.'<input type="text" readonly size ="4" name="idTemporal" value="'.$idAlbaranTemporal.'">';
    }
    echo '</h3>';
	?>
    
    <div class="col-md-12">
        <div class="col-md-8" >
            <?php echo $Controler->getHtmlLinkVolver('Volver');
            // Botones de incidencias.
            if($idAlbaran>0){
                echo '<input class="btn btn-warning" size="12" onclick="abrirModalIndicencia('."'".$dedonde
                    ."'".' , configuracion, 0 ,'.$idAlbaran
                    .');" value="Añadir incidencia " name="addIncidencia" id="addIncidencia">';
            }
            if( isset($incidencias) && count( $incidencias)> 0){
                echo ' <input class="btn btn-info" size="15" onclick="abrirIncidenciasAdjuntas('
                    .$idAlbaran." ,'mod_compras', 'albaran'"
                    .')" value="Incidencias Adjuntas " name="incidenciasAdj" id="incidenciasAdj">';
            }
            if ($estado != "Facturado" && $accion != "ver"){
                    // El btn guardar solo se crea si el estado es "Nuevo","Sin Guardar","Guardado"
                 echo '<input class="btn btn-primary" '.$estilos['btn_guardar']
                            .' type="submit" value="Guardar" name="Guardar" id="bGuardar">';
            }
            ?>
        </div>
        <div class="col-md-4 text-right" >
            <?php
            if ($estado != "Facturado" || $accion != "ver"){?>
                <span class="glyphicon glyphicon-cog" title="Escoje casilla de salto"></span>
                <?php echo htmlSelectConfiguracionSalto();
                // El btn cancelar solo se crea si el estado es "Nuevo"
                // pero solo se muestra cuando hay un temporal, ya que no tiene sentido mostrarlo si no hay temporal
                if ($estado != "Nuevo"){
                    $estilos['btn_cancelar'] = ' style="display:none;"';
                    // Se cambia con javascript cuando creamos el temporal y el estado es Nuevo.
                }
                echo '<input type="submit" class="btn btn-danger"'
                    .$estilos['btn_cancelar']. ' value="Cancelar" name="Cancelar" id="bCancelar">';
            }
            ?>
        </div>
    </div>
    <div class="row" >
        <div class="col-md-7">
            <div class="col-md-12">
                    <label class="text-center">Proveedor</label>
                    <?php
                    echo '<div class="col-md-2">
                            <input type="text" id="id_proveedor" name="id_proveedor" data-obj= "cajaIdProveedor" value="'
                            .$idProveedor.'" '.$estilos['pro_readonly'].' size="2" onkeydown="controlEventos(event)" placeholder="id">
                        </div>';
                    echo '<div class="col-md-10">
                            <input type="text" id="Proveedor" name="Proveedor" data-obj= "cajaProveedor" '
                            .'placeholder="Nombre de proveedor" onkeydown="controlEventos(event)" value="'
                            .$nombreProveedor.'" '.$estilos['pro_readonly'].' size="60" >'
                            .' <a id="buscar" '.$estilos['pro_styleNo'].' class="btn glyphicon glyphicon-search buscar"'
                            .' onclick="buscarProveedor('."'".'albaran'."'".',Proveedor.value)"></a>
                         </div>';
                    ?>
            </div>
            <div class="col-md-12">
                    <div class="col-md-3">
                        <label>Fecha albarán:</label>
                        <?php
                            $pattern_numerico = ' pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" ';
                            $title_fecha =' placeholder="dd-mm-yyyy" title=" Formato de entrada dd-mm-yyyy"';
                            echo '<input type="text" name="fecha" id="fecha" size="8" data-obj= "cajaFecha" '
                                . $estilos['input_factur'].' value="'.$fecha.'" '.$evento_cambio.' onkeydown="controlEventos(event)" '
                                . $pattern_numerico.$title_fecha.'/>';
                        ?>
                    </div>
                    <div class="col-md-3">
                        <label>Hora de entrega:</label>
                        <?php
                            echo '<input type="time" id="hora" '.$estilos['input_factur'].' value="'.$hora.'" '
                                .' data-obj= "cajaHora" '.$evento_cambio.' onkeydown="controlEventos(event)"  name="hora" size="5"'
                                .' max="24:00" min="00:00" '
                                . $pattern_numerico.' placeholder="HH:MM" title=" Formato de entrada HH:MM">';
                        ?>
                    </div>
                    <div class="col-md-3">
                        <label>Estado:</label>
                        <input type="text" id="estado" name="estado" size="9" value="<?php echo $estado;?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label>Creado por:</label>
                        <input type="text" id="Usuario" name="Usuario" value="<?php echo $creado_por['nombre'];?>" size="8" readonly>
                    </div>
            </div>
            <div class="col-md-12">
                <div class="col-md-4">
                    <label>Su número:</label>
                    <input type="text" id="suNumero" name="suNumero" value="<?php echo $suNumero;?>" size="10" <?php echo $evento_cambio;?> onkeydown="controlEventos(event)" data-obj= "CajaSuNumero" <?php echo $estilos['input_factur'];?>>
                </div>
                <div class="col-md-4">
                        <label>Fecha vencimiento:</label>
                        <?php
                             echo '<input type="date" name="fechaVenci" id="fechaVenci" size="8" '
                                . $estilos['input_factur'].' value="'.$fechaVencimiento.'" onkeydown="controlEventos(event)" '
                                . $pattern_numerico.$title_fecha.'>';
                        ?>
                </div>
                <div class="col-md-4">
                    <label>Forma de pago:</label>
                    <div id="formaspago">
                        <select name='formaVenci' id='formaVenci' <?php echo   $estilos['select_factur'];?>>
                    <?php 
                    if(isset ($textoFormaPago)){
                            echo $textoFormaPago['html'];
                    }
                    ?>
                        </select>
                    </div>
                </div>
                
                
            </div>
            
        </div>
        <div class="col-md-5 bg-warning div_adjunto">
            <?php
            if ($accion !=='ver'){
            ?>
                <label id="numPedidoT">Número del pedido:</label>
                <input type="text" id="numPedido" name="numPedido" value="" size="5" placeholder='Num' data-obj= "numPedido" onkeydown="controlEventos(event)" <?php echo $estilos['input_factur'];?>>
                <a id="buscarPedido" class="glyphicon glyphicon-search buscar" onclick="buscarAdjunto('albaran')"></a>
            <?php
            } ?>
            <table class="table" id="tablaPedidos"> 
                <thead>
                <tr>
                    <td><b>Número</b></td>
                    <td><b>Fecha</b></td>
                    <td><b>Total</b></td>
                </tr>
                </thead>
                <?php 
                if (isset($datosAlbaran['Pedidos'])){
                    if( $html_adjuntos != ''){
                        echo  $html_adjuntos;
                    }
                }
                ?>
            </table>
        </div>
        <!-- Tabla de lineas de productos -->
	<div>
            <div>
                <div class="col-md-12 form-inline bg-success" id="Row0" <?php echo $estilos['styleNo'];?>>  
                    <div class="form-group">
                        <input id="idArticulo" type="text" name="idArticulo" placeholder="idArticulo" data-obj= "cajaidArticulo" size="4" value=""  onkeydown="controlEventos(event)">
                    </div>
                    <div class="form-group">
                        <input id="Referencia" type="text" name="Referencia" placeholder="Referencia" data-obj="cajaReferencia" size="8" value="" onkeydown="controlEventos(event)">
                    </div>
                    <div class="form-group">
                        <input id="ReferenciaPro" type="text" name="ReferenciaPro" placeholder="Ref_proveedor" data-obj="cajaReferenciaPro" size="10" value=""onkeydown="controlEventos(event)">
                    </div>
                    <div class="form-group">
                        <input id="Codbarras" type="text" name="Codbarras" placeholder="Codbarras" data-obj= "cajaCodBarras" size="12" value="" data-objeto="cajaCodBarras" onkeydown="controlEventos(event)">
                    </div>
                    <div class="form-group">
                        <input id="Descripcion" type="text" name="Descripcion" placeholder="Descripcion" data-obj="cajaDescripcion" size="17" value="" onkeydown="controlEventos(event)">
                    </div>
                </div>

            </div>
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
                  
                </thead>
                <tbody>
                    <?php 
                    //Recorremos los productos y vamos escribiendo las lineas.
                    if (isset($productos)){
                        $id_pedido_anterior ='0';
                        foreach (array_reverse($productos) as $producto){
                            // Ahora tengo que controlar si son lineas de adjunto, para añadir linea de adjunto.
                            if (isset($producto['idpedpro']) && $producto['idpedpro'] !==$id_pedido_anterior) {
                                // Si numero pedido es distinto a $id_pedido_anterior,
                                // entonces debemos obtener linea de adjunto para poner en productos.
                                echo $pedido_html_linea_producto[$producto['idpedpro']];
                            }
                            // Si existe index Numpedpro entonces lo pongo como valor, sino dejo 0;
                            $id_pedido_anterior = (isset($producto['idpedpro']))? $producto['idpedpro'] : '0';
                            $html=htmlLineaProducto($producto, "albaran",$estilos['readonly']);
                            echo $html['html'];
                        }
                    }
                    ?>
                </tbody>
              </table>
        </div>
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
    </div>
    </form>
</div>
    <?php // Incluimos paginas modales
    echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
    include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
    ?>
</body>
</html>
