<?php
    include_once './../../inicial.php';
    //Carga de archivos php necesarios
    include_once $URLCom.'/modulos/mod_compras/funciones.php';
    include_once $URLCom.'/controllers/Controladores.php';
    include_once $URLCom.'/clases/Proveedores.php';

    include_once $URLCom.'/modulos/mod_compras/clases/pedidosCompras.php';
    include_once ($URLCom.'/controllers/parametros.php');
    //Carga de clases necesarias
    $ClasesParametros = new ClaseParametros('parametros.xml');
    $Cproveedor=new Proveedores($BDTpv);
    $Cpedido=new PedidosCompras($BDTpv);
    $Controler = new ControladorComun; 
    $Controler->loadDbtpv($BDTpv);
    //Inicializar las variables
    $dedonde="pedido";
    $titulo="Pedido de Proveedor:";
    // Valores por defecto de estado y accion.
    // [estado] -> Nuevo,Sin Guardar,Guardado,Facturado.
    // [accion] -> editar,ver
    $estado='Nuevo';
    // Si existe accion, variable es $accion , sino es "editar"
    $accion = (isset($_GET['accion']))? $_GET['accion'] : 'editar';
    $fecha=date('d-m-Y');
    $idPedidoTemporal=0;
    $idPedido=0;
    $idProveedor='';
    $nombreProveedor='';
    $Datostotales=array();
    $errores = array();
    $inciden=0;
    //Cargamos la configuración por defecto y las acciones de las cajas 
    $parametros = $ClasesParametros->getRoot();
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
    //  [id] cuando editamos o vemos un pedido pulsando en listado.
    //  [temporal] cuando pulsamos en cuadro pedidos temporales.
    //  [accion] cuando indicamos que accion vamos hacer.
    if (isset($_GET['id'])){
        $idPedido=$_GET['id'];  // Id real de pedido
    }
    if (isset($_GET['temporal'])){
        $idPedidoTemporal=$_GET['temporal']; // Id de pedido temporal
    }
    // ---------- Posible errores o advertencias mostrar     ------------------- //
    if ($idPedido > 0){
        // Comprobamos cuantos temporales tiene idPedido y si tiene uno obtenemos el numero.
        $c = $Cpedido->comprobarTemporalIdPedpro($idPedido);
        if (isset($c['idTemporal']) && $c['idTemporal'] !== NULL){
            // Existe un temporal de este pedido por lo que cargo ese temporal.
            $idPedidoTemporal = $c['idTemporal'];
            $idPedido = 0 ; // Lo pongo en 0 para ejecute la parte temporal
            $_GET['temporal'] = $idPedidoTemporal;
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
    if ( $idPedido > 0 && count($errores) === 0){
        // Si existe id y no hay errores estamos modificando directamente un pedido.
        $datosPedido=$Cpedido->GetPedido($_GET['id']);
        if (isset($datosPedido['error'])){
            $errores=$datosPedido['error'];
        } else {
            if(isset($datosPedido['estado'])){
                $estado=$datosPedido['estado'];
                if ($estado=='Facturado'){
                    $accion = 'ver'; // Con estado facturado la accion es solo ver.
                    // Obtenemos el numero albaran que tiene este pedido.
                    $Albaran_creado = $Cpedido->NumAlbaranDePedido($idPedido);
                    if(isset($Albaran_creado['error'])){
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
    if ( $idPedidoTemporal >0 && count($errores) === 0){
        // Puede entrar cuando :
        //   -Viene de albaran temporal
        //   -Se recargo mientras editamos.
        //   -Cuando pulsamos guardar.
        $datosPedido=$Cpedido->buscarPedidoTemporal($idPedidoTemporal);
        if (isset($datosPedido['error'])){
                array_push($errores,$Cpedido->montarAdvertencia(
                                'danger',
                                'Error 1.1 en base datos.Consulta:'.json_encode($datosPedido['consulta'])
                        )
                );
        } else {
            // Preparamos datos que no viene o que vienen distintos cuando es un temporal.
            $datosPedido['Productos'] = json_decode($datosPedido['Productos'],true);
            $idPedido = $datosPedido['idPedpro'];
            $estado=$datosPedido['estadoPedPro'];
        }
    }
    if (count($errores) == 0){
        // Si no hay errores graves continuamos.
        if (!isset($datosPedido)){
            // SI es NUEVO.
            $datosPedido = array();
            $datosPedido['Fecha']="0000-00-00 00:00:00";
            $datosPedido['idProveedor'] = 0;
            $creado_por = $Usuario;
       } else {
            // No es NUEVO
            $idProveedor=$datosPedido['idProveedor'];
            $proveedor=$Cproveedor->buscarProveedorId($idProveedor);
            $nombreProveedor=$proveedor['nombrecomercial'];
            $productos =$datosPedido['Productos'];
            $fecha = ($datosPedido['Fecha']=="0000-00-00 00:00:00")
                                ? date('d-m-Y'):date_format(date_create($datosPedido['Fecha']),'d-m-Y');
            $hora=date_format(date_create($datosPedido['Fecha']),'H:i');
            $creado_por = $Cpedido->obtenerDatosUsuario($datosPedido['idUsuario']);
            if (isset ($datosPedido['Numpedpro'])){
                $d=$Cpedido->buscarPedidoNumero($datosPedido['Numpedpro']);
                $idPedido=$d['id'];
                // Debemos saber si debemos tener incidencias para ese albaran, ya que el boton incidencia es distinto.
                $incidencias=incidenciasAdjuntas($idPedido, "mod_compras", $BDTpv, $dedonde);
            }
        }
    }
    if (isset ($datosPedido['idProveedor']) && $datosPedido['idProveedor'] > 0){
        //  Obtenemos los datos del proveedor:
        $idProveedor=$datosPedido['idProveedor'];
        $datosProveedor=$Cproveedor->buscarProveedorId($idProveedor);
        $nombreProveedor=$datosProveedor['nombrecomercial'];
    }
    if(isset($datosPedido['Productos'])){
        // Obtenemos los datos totales;
        // convertimos el objeto productos en array
        $Datostotales = $Cpedido->recalculoTotales($productos);
    }
    
    //  ---------  Control y procesos para guardar el pedido. ------------------ //
    if (isset($_POST['Guardar']) && count($errores)===0){
        // Cuando el estado es pedido que recibimos por POST es "Guardado"
        // puede ser que no modificará nada o que exista un temporal, recien creado.
        // lo compruebo.
        $guardar = $Cpedido->guardarPedido();
        if (!isset($guardar['errores']) || count($guardar['errores'])===0){
                // Fue todo correcto.
                // Aunque si hubiera errores o advertencias nunca lo mostraría ya que redirecciono directamente.
                header('Location: pedidosListado.php');
        } else {
            if (isset($guardar['errores']) || is_array($guardar['errores'])){
                $errores = $guardar['errores'];
            }
            if (isset($guardar['id_guardo'])){
                // Hay que indicar que se guardo, aunque hay errores.
                array_push($errores,$Cpedido->montarAdvertencia('warning',
                                    '<strong>Se guardo el id:'.$guardar['id_guardar'].' </strong>  <br>'
                                    .'Ojo que puede generar un duplicado'
                                    )
                        );
            }
            if (isset($guardar['modPedido'])){
                // Se modifico todo o algo, pero hubo un error.
                array_push($errores,$Cpedido->montarAdvertencia('warning',
                                    '<strong>Se modifico algo pero hubo un error.</strong><br/>'
                                    .'Ojo que puede generar un duplicado'
                                    .json_encode($guardar['modPedido'])
                                    )
                        );
            }
        }
    }
    $htmlIvas=htmlTotales($Datostotales);
    // ============          Otros controles posibles errores               ==================== //
    // Controlamos que el estado sea uno de los tres posibles.
    $posibles_estados = array ('Sin Guardar','Guardado','Nuevo','Enviado','Facturado');
    if (!in_array($estado, $posibles_estados)){
        // No existe ese estado.
        array_push($errores,$Cpedido->montarAdvertencia('warning',
                                    '<strong>El estado que tiene no es corrrecto.</strong><br/>'
                                    .'El estado:'.$estado.' no existe en los posibles estados para un pedido.'
                                    )
                        );
    }
    
    // ============                 Montamos el titulo                      ==================== //
    $html_albaran='';
    if(isset($Albaran_creado)){
        $html_albaran = ' <span style="font-size: 0.55em;vertical-align: middle;" class="label label-default">';
        $html_albaran .= 'albaran:'.$Albaran_creado['numAlbaran'];
        $html_albaran .='</span>';
    }
    // Añadimos al titulo el estado
    $titulo .= ' '.$idPedido.$html_albaran.' - '.$accion;
    // ============= Creamos variables de estilos para cada estado y accion =================== //
    $estilos = array ( 'readonly'       => '',
                       'styleNo'        => 'style="display:none;"',
                       'pro_readonly'   => '',
                       'pro_styleNo'    => '',
                       'btn_guardar'    => '',
                       'btn_cancelar'   => '',
                       'input_factur'   => '',
                       'select_factur'  => '',
                       'evento_cambio'  => ''
                    );
    if (isset ($_GET['id']) || isset ($_GET['temporal'])){
        // Quiere decir que ya inicio , ya tuvo que meter proveedor.
        // no se permite cambiar proveedor.
        $estilos['pro_readonly']   = ' readonly';
        $estilos['pro_styleNo']    = ' style="display:none;"';
        $estilos['styleNo']    = '';
        $estilos['evento_cambio'] = 'onchange ="addTemporal('."'".$dedonde."'".')"'; // Lo utilizo para crear temporal cuando cambia valor.

    }
    if ($accion === 'ver'){
        $estilos['readonly']   = ' readonly';
        $estilos['styleNo']     = ' style="display:none;"';
        $estilos['input_factur'] = ' readonly';
        $estilos['select_factur'] = 'disabled="true"';   
    }
    if ($idPedidoTemporal === 0){
        // Solo se muestra cuando el idPedidoTemporal es 0
        $estilos['btn_guardar'] = 'style="display:none;"';
        // Una vez se cree temporal, con javascript se quita style
    }
    
?>
<!DOCTYPE html>
<html>
<head>
    <?php  include_once $URLCom.'/head.php'; ?>

<script type="text/javascript">
    <?php
    // Esta variable global la necesita para montar la lineas.
    // En configuracion podemos definir SI / NO
    echo 'var configuracion='.json_encode($configuracionArchivo).';';
    ?>  
    var cabecera = []; // Donde guardamos idCliente, idUsuario,idTienda,FechaInicio,FechaFinal.
        cabecera['idUsuario'] = <?php echo $Usuario['id'];?>; // Tuve que adelantar la carga, sino funcionaria js.
        cabecera['idTienda'] = <?php echo $Tienda['idTienda'];?>; 
        cabecera['estado'] ='<?php echo $estado ;?>'; 
        cabecera['idTemporal'] = '<?php echo $idPedidoTemporal;?>';
        cabecera['idReal'] = '<?php echo $idPedido ;?>';
        cabecera['idProveedor'] ='<?php echo $idProveedor;?>';
        cabecera['fecha'] = '<?php echo $fecha;?>';
         // Si no hay datos GET es 'Nuevo';
    var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array
    var salto_linea = 'ReferenciaPro'; // Valor por defecto
    <?php 
    $i= 0;
    if (isset($productos)){
        if ($productos){
            foreach($productos as $product){
    ?>
            datos=<?php echo json_encode($product); ?>;
            productos.push(datos);
    <?php //cambiamos estado y cantidad de producto creado si fuera necesario.
                if (isset ($product->estado)){
                    if ($product['estado'] !== 'Activo'){
                    ?>
                        productos[<?php echo $i;?>].estado=<?php echo'"'.$product['estado'].'"';?>;
                    <?php
                    }
                }
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
        mensajeCancelar(<?php echo $idPedidoTemporal;?>, <?php echo "'".$dedonde."'"; ?>);
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
            echo $Cpedido->montarAdvertencia($comprobaciones['tipo'],$comprobaciones['mensaje'],'OK');
            if ($comprobaciones['tipo'] === 'danger'){
                exit; // No continuo.
            }
        }
    }
    ?>
    <form  action="" method="post" name="formProducto" onkeypress="return anular(event)">
    <?php 
    echo '<h3 class="text-center">'.$titulo.'</h3>';
    ?>
    
        <div class="col-md-12">
            <div class="col-md-8" >
                <?php echo $Controler->getHtmlLinkVolver('Volver');
            // Botones de incidencias.
                if($idPedido>0){
                    echo '<input class="btn btn-warning" size="12" 
                    onclick="abrirModalIndicencia('."'".$dedonde."'".' , configuracion, 0, '.$idPedido.');" 
                    value="Añadir incidencia " name="addIncidencia" id="addIncidencia">';
                }
                if($inciden>0){
                   echo '<input class="btn btn-info" size="15" 
                   onclick="abrirIncidenciasAdjuntas('.$idPedido.', '."'".'mod_compras'."'".', '."'".'pedido'."'".')"
                   value="Incidencias Adjuntas " name="incidenciasAdj" id="incidenciasAdj">';
                }
                // Si estado es Facturado o Enviado no se puede ver
                if ($estado =='Facturado' || $estado == 'Enviado'){
                    $accion ='ver';
                }
                if ($accion != "ver"){
                    // El btn guardar solo se crea si el estado es "Nuevo","Sin Guardar","Guardado"
                    echo '<input class="btn btn-primary" '.$estilos['btn_guardar']
                            .' type="submit" value="Guardar" name="Guardar" id="bGuardar" accesskey="G"/>';
                }
                ?>
            </div>
            <div class="col-md-4 text-right" >
            <?php
            if ($estado != "Facturado" || $accion != "ver"){
                // Mostramos input temporal
                echo ' temporal:'.'<input type="text" readonly size ="4" name="idTemporal" value="'.$idPedidoTemporal.'">';
            }
            if ($estado == "Nuevo"){
                echo '<input type="submit" class="btn btn-danger"'
                    .$estilos['btn_cancelar']. 'value="Borrar Temporal" name="Cancelar" id="bCancelar">';
            }
            ?>
            </div>
           
        </div>
    <div class="row" >
    <div class="col-md-7">
                <div class="col-md-12">
            <div class="col-md-3">
                <label>Fecha Pedido:</label>
                 <?php
                    $pattern_numerico = ' pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" ';
                    $title_fecha =' placeholder="dd-mm-yyyy" title=" Formato de entrada dd-mm-yyyy"';
                    echo '<input type="text" name="fecha" id="fecha" size="8" data-obj= "cajaFecha" '
                        . $estilos['input_factur'].' value="'.$fecha.'" '.$estilos['evento_cambio'].' onkeydown="controlEventos(event)" '
                        . $pattern_numerico.$title_fecha.'/>';
                    ?>
            </div>
            <div class="col-md-3">
                <label>Estado:</label>
                <input type="text" id="estado" name="estado" value="<?php echo $estado;?>" readonly>
            </div>
            <div class="col-md-3">
                <label>Creado por:</label>
                <input type="text" id="Usuario" name="Usuario" value="<?php echo $Usuario['nombre'];?>" size="13" readonly>
            </div>
            
        </div>
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
                            .$nombreProveedor.'" '.$estilos['pro_readonly'].' size="60" accesskey="P" />'
                            .'<a id="buscar" '.$estilos['pro_styleNo'].' class="btn glyphicon glyphicon-search buscar"'
                            .' onclick="buscarProveedor('."'".'albaran'."'".',Proveedor.value)"></a>
                         </div>';
                    ?>
            </div>
        
    </div>
    <!-- Tabla de lineas de productos -->
    <div>
            <div>
                <div class="col-md-12 form-inline bg-warning" id="Row0" <?php echo $estilos['styleNo'];?>>  
                    <div class="form-group">
                        <input id="idArticulo" type="text" name="idArticulo" placeholder="idArticulo" data-obj= "cajaidArticulo" size="4" value=""  onkeydown="controlEventos(event)">
                    </div>
                    <div class="form-group">
                        <input id="Referencia" type="text" name="Referencia" placeholder="Referencia" data-obj="cajaReferencia" size="8" value="" onkeydown="controlEventos(event)">
                    </div>
                    <div class="form-group">
                        <input id="ReferenciaPro" type="text" name="ReferenciaPro" placeholder="Ref_proveedor" data-obj="cajaReferenciaPro" size="10" value="" onkeydown="controlEventos(event)">
                    </div>
                    <div class="form-group">
                        <input id="Codbarras" type="text" name="Codbarras" placeholder="Codbarras" data-obj= "cajaCodBarras" size="12" value="" data-objeto="cajaCodBarras" onkeydown="controlEventos(event)">
                    </div>
                    <div class="form-group">
                        <input id="Descripcion" type="text" name="Descripcion" placeholder="Descripcion" data-obj="cajaDescripcion" size="17" value="" onkeydown="controlEventos(event)">
                    </div>
                </div>

            </div>
            <div class="col-lg-9">
        <table id="tabla" class="table table-striped" >
            <thead>
            <tr>
                <th>L</th>
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
                if (isset($productos)){
                    foreach (array_reverse($productos) as $producto){
                        $h=htmlLineaProducto($producto, "pedido",$estilos['readonly']);
                        echo $h['html'];
                    }
                }
            ?>
            </tbody>
      </table>
    </div>
        <div class="col-lg-3 pie-ticket">
            <table id="tabla-pie" class="col-md-12">
            <thead>
            <tr>
                <th>Tipo</th>
                <th>Base</th>
                <th>IVA</th>
            </tr>
            </thead>
            <tbody>
                <?php
                if (isset($Datostotales)) {
                    $htmlIvas = htmlTotales($Datostotales);
                    echo $htmlIvas['html'];
                }
                ?>

            </tbody>
            </table>
        </div>
        </div>
    </div>
    </form>
</div>
    <?php // Incluimos paginas modales
echo '<script src="' . $HostNombre . '/plugins/modal/func_modal.js"></script>';
include $RutaServidor . '/' . $HostNombre . '/plugins/modal/ventanaModal.php';
?>
</body>
</html>
