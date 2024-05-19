<?php
    include_once './../../inicial.php';
    //Carga de archivos php necesarios
    include_once $URLCom . '/modulos/mod_compras/funciones.php';
    include_once $URLCom . '/controllers/Controladores.php';
    include_once $URLCom . '/clases/Proveedores.php';

    include_once $URLCom . '/modulos/mod_compras/clases/pedidosCompras.php';
    include_once $URLCom . '/controllers/parametros.php';
    //Carga de clases necesarias
    $ClasesParametros = new ClaseParametros('parametros.xml');
    $Cproveedor=new Proveedores($BDTpv);
    $Cpedido=new PedidosCompras($BDTpv);
    $Controler = new ControladorComun;
    $Controler->loadDbtpv($BDTpv);
    //Inicializar las variables
    $dedonde = "pedido";
    $titulo = "Pedido de Proveedor:";
    // Valores por defecto de estado y accion.
    // [estado] -> Nuevo,Sin Guardar,Guardado,Facturado.
    // [accion] -> editar,ver
    $estado = 'Nuevo';
    // Si existe accion, variable es $accion , sino es "editar"
    $accion = (isset($_GET['accion']))? $_GET['accion'] : 'ver';
    $fecha= date('d-m-Y');
    $idDocumentoTemporal = 0; // idDocumentoTemporal -> idAlbaranTemporal o idPedidoTemporal o idFacturaTemporal
    $idDocumento = 0; // idDocumento -> idAlbaran o idPedido o idFactura
    $idProveedor = "";
    $nombreProveedor = "";
    $Datostotales = array();
    $errores = array();
    $creado_por = array();
    //Cargamos la configuración por defecto y las acciones de las cajas 
    $parametros = $ClasesParametros->getRoot();
    $VarJS = $Controler->ObtenerCajasInputParametros($parametros);
    $conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
    $configuracion = $Controler->obtenerConfiguracion($conf_defecto, 'mod_compras', $Usuario['id']);
    $configuracionArchivo = array();
    foreach ($configuracion['incidencias'] as $config) {
        if (get_object_vars($config)['dedonde'] == $dedonde) {
            array_push($configuracionArchivo, $config);
        }
    }
    // --------------- Controlamos GET -------------------------  //
    // Por GET podemos recibir uno o varios parametros:
    //  [id] cuando editamos o vemos un pedido pulsando en listado.
    //  [tActual] cuando pulsamos en cuadro pedidos temporales.
    //  [accion] cuando indicamos que accion vamos hacer.
    $contador_get = count($_GET);
    if ($contador_get > 0) {
        // Si existe accion, variable es $accion , sino es "ver"
        if (isset($_GET['accion'])) {
            $accion = $_GET['accion'];
            $contador_get = $contador_get - 1;
        }
        if (isset($_GET['tActual'])) {
            $idDocumentoTemporal = $_GET['tActual']; // Id de albaran temporal
            // Si viene temporal, siempre es la accion es editar
            $accion = 'editar';
            $contador_get = $contador_get - 1;
        }
        if (isset($_GET['id'])) {
            $idDocumento = $_GET['id']; // Id real de pedido
            $contador_get = $contador_get - 1;
        }
    }
    if ($contador_get > 0) {
        // Hay un parametro get, que no se controlo
        // deberíamos indicarlo.
        array_push($errores, $Cpedido->montarAdvertencia(
            'warning',
            'Hay parametro get,distinto a tActual,accion,id:' . json_encode($_GET))
        );
    }
    
    // ---------- Posible errores o advertencias mostrar     ------------------- //
    if ($idDocumento > 0) {
        // Comprobamos si existe temporal del idPedido 
        $temporales = $Cpedido->comprobarTemporalIdPedpro($idDocumento);
        // Si existe numero pedido, comprobamos cuantos temporales tiene Pedido y si tiene uno obtenemos el numero.
        if (isset($temporale['idTemporal']) && $temporales['idTemporal'] !== NULL){
            // Existe un temporal de este pedido por lo que cargo ese temporal.
            $idDocumentoTemporal = $temporales['idTemporal'];
            if ($accion !== 'editar' && $accion !== 'ver') {
                $accion = 'ver';
                // Creo alert
                echo '<script>alert("No se permite editar, ya que alguien esta editandolo, ya que hay un temporal");</script>';
            }
        } else {
            if (count($temporales) > 0) {
                array_push($errores,
                    $Cpedido->montarAdvertencia( 'danger',$temporales));
            }
        }
    }
    if ($idDocumento > 0 && count($errores) === 0) {
        // Si existe id y no hay errores estamos modificando directamente un pedido.
        $datosDocumento=$Cpedido->GetPedido($_GET['id']);
        if (isset($datosDocumento['error'])) {
            $errores = $datosDocumento['error'];
        } else {
            if (isset($datosDocumento['estado'])) {
                $estado = $datosDocumento['estado'];
                $idDocumento = $datosDocumento['id'];
                if ($estado=='Facturado'){
                    // Cambiamos accion, ya que solo puede ser ver.
                    $accion = 'ver';
                    // Obtenemos los datos de adjunto su estado es facturado.
                    $Albaran_creado = $Cpedido->NumAlbaranDePedido($idDocumento);
                    if(isset($Albaran_creado['error'])){
                        array_push($errores,$Cpedido->montarAdvertencia(
                                        'danger',
                                        'Error 1.1 en base datos.Consulta:'.json_encode($Albaran_creado['consulta'])
                                )
                        );
                    }
                } 
            }
        }
    }
    if ($idDocumentoTemporal > 0 && count($errores) === 0) {
        // Puede entrar cuando :
        //   -Viene de albaran temporal
        //   -Se recargo mientras editamos.
        //   -Cuando pulsamos guardar.
        $datosDocumento=$Cpedido->buscarPedidoTemporal($idDocumentoTemporal);
        if (isset($datosDocumento['error'])) {
            array_push($errores, $Cpedido->montarAdvertencia(
                'danger',
                'Error 1.1 en base datos.Consulta:' . json_encode($datosDocumento['consulta'])
            )
            );
        } else {
            // Preparamos datos que no viene o que vienen distintos cuando es un temporal.
            $datosDocumento['Productos'] = json_decode($datosDocumento['Productos'], true);
            $idDocumento = $datosDocumento['idPedpro'];
            $estado=$datosDocumento['estadoPedPro'];
        }
    }
    if (count($errores) == 0) {
        // Si no hay errores graves continuamos.
        if ($estado == 'Nuevo' && !isset($datosDocumento)) {
            // SI es NUEVO.
            $datosDocumento = array();
            $datosDocumento['Fecha'] = "0000-00-00 00:00:00";
            $datosDocumento['idProveedor'] = 0;
            $accion = 'editar';
            $creado_por = $Usuario;
        } else {
            // No es NUEVO
            $idProveedor = $datosDocumento['idProveedor'];
            $proveedor = $Cproveedor->buscarProveedorId($idProveedor);
            $nombreProveedor = $proveedor['nombrecomercial'];
            $productos = $datosDocumento['Productos'];
            $fecha = ($datosDocumento['Fecha'] == "0000-00-00 00:00:00")
            ? date('d-m-Y') : date_format(date_create($datosDocumento['Fecha']), 'd-m-Y');
            $hora=date_format(date_create($datosDocumento['Fecha']),'H:i');
            $creado_por = $Cpedido->obtenerDatosUsuario($datosDocumento['idUsuario']);
            if (isset ($datosDocumento['Numpedpro'])){
                $d=$Cpedido->buscarPedidoNumero($datosDocumento['Numpedpro']);
                $idDocumento = $d['id'];
                // Debemos saber si debemos tener incidencias para ese albaran, ya que el boton incidencia es distinto.
                $incidencias = incidenciasAdjuntas($idDocumento, "mod_compras", $BDTpv, $dedonde);
            }
        }
    }
        if (isset($datosDocumento['Productos'])) {
            // Obtenemos los datos totales ;
            // convertimos el objeto productos en array
            $p = (object) $productos;
            $Datostotales = $Cpedido->recalculoTotales($p);
    }
    
    //  ---------  Control y procesos para guardar el documento. ------------------ //
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
    $html_relacionado = '';
    if(isset($Albaran_creado)){
        $html_relacionado = ' <span style="font-size: 0.55em;vertical-align: middle;" class="label label-default">';
        $html_relacionado .= 'albaran:'.$Albaran_creado['numAlbaran'];
        $html_relacionado .= '</span>';
    }
    $titulo .= ' '.$idDocumento.$html_relacionado.' - '.$accion;
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
    if (isset($_GET['id']) || isset($_GET['tActual'])) {
        // Quiere decir que ya inicio , ya tuvo que meter proveedor.
        // no se permite cambiar proveedor.
        $estilos['pro_readonly'] = ' readonly';
        $estilos['pro_styleNo'] = ' style="display:none;"';
        $estilos['styleNo'] = '';
        $estilos['evento_cambio'] = 'onchange ="addTemporal(' . "'" . $dedonde . "'" . ')"'; // Lo utilizo para crear temporal cuando cambia valor.

    }
    if ($accion === 'ver') {
        $estilos['readonly'] = ' readonly';
        $estilos['styleNo'] = ' style="display:none;"';
        $estilos['input_factur'] = ' readonly';
        $estilos['select_factur'] = 'disabled="true"';
    }
    if ($idDocumentoTemporal === 0) {
        // Solo se muestra cuando el idDocumentoTemporal es 0
        $estilos['btn_guardar'] = 'style="display:none;"';
        // Una vez se cree temporal, con javascript se quita style
    }


?>

<!DOCTYPE html>
<html>

<head>
    <?php include_once $URLCom . '/head.php';?>

    <script type="text/javascript">
    // Esta variable global la necesita para montar la lineas.
    // En configuracion podemos definir SI / NO
    <?php echo 'var configuracion=' . json_encode($configuracionArchivo) . ';'; ?>
    var cabecera = []; // Donde guardamos idCliente, idUsuario,idTienda,FechaInicio,FechaFinal.
    cabecera['idUsuario'] = <?php echo $creado_por['id']; ?>; // Tuve que adelantar la carga, sino funcionaria js.
    cabecera['idTienda'] = <?php echo $Tienda['idTienda']; ?>;
    cabecera['estado'] = '<?php echo $estado; ?>'; // Si no hay datos GET es 'Nuevo'
    cabecera['idTemporal'] = '<?php echo $idDocumentoTemporal; ?>';
    cabecera['idReal'] = '<?php echo $idDocumento; ?>';
    cabecera['idProveedor'] = '<?php echo $idProveedor; ?>';
    cabecera['fecha'] = '<?php echo $fecha; ?>';
    var productos = []; // No hace definir tipo variables, excepto cuando intentamos añadir con push, que ya debe ser un array
    var salto_linea = 'ReferenciaPro'; // Valor por defecto
    <?php
    if (isset($idDocumentoTemporal) || isset($idDocumento)) {
        if (isset($productos)) {
            foreach ($productos as $k => $product) {
                ?>
                datos = <?php echo json_encode($product); ?>;
                productos.push(datos);
                <?php
                // --- Creo que esto no hace falta ya , pienso que es un codigo innecesario ---
                // cambiamos estado y cantidad de producto creado si fuera necesario.
                // if ($product['estado'] !== 'Activo') {
                //    echo 'productos['.$k.'].estado = '.'"' . $product['estado'] . '";';
                //}
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
    include_once $URLCom . '/modulos/mod_menu/menu.php';
    ?>
    <script type="text/javascript">
    <?php
    if (isset($_POST['Cancelar'])) {
        ?>
        mensajeCancelar(<?php echo $idDocumentoTemporal; ?>, <?php echo "'" . $dedonde . "'"; ?>);
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
    if (isset($errores)) {
        foreach ($errores as $comprobaciones) {
            echo $Cpedido->montarAdvertencia($comprobaciones['tipo'], $comprobaciones['mensaje'], 'OK');
            if ($comprobaciones['tipo'] === 'danger') {
                exit; // No continuo.
            }
        }
    }
    ?>
        <form action="" method="post" name="formProducto" onkeypress="return anular(event)">
            <?php 
        echo '<h3 class="text-center">'.$titulo.'</h3>';

        ?>

            <div class="col-md-12">
                <div class="col-md-8">
                    <?php echo $Controler->getHtmlLinkVolver('Volver');
            // Botones de incidencias.
            if($idDocumento>0){
                echo '<input class="btn btn-warning" size="12" onclick="abrirModalIndicencia('."'".$dedonde
                    ."'".' , configuracion, 0 ,'.$idDocumento
                    .');" value="Añadir incidencia " name="addIncidencia" id="addIncidencia">';
            }
            if( isset($incidencias) && count( $incidencias)> 0){
                echo ' <input class="btn btn-info" size="15" onclick="abrirIncidenciasAdjuntas('
                    .$idDocumento." ,'mod_compras', '".$dedonde."'"
                    .')" value="Incidencias Adjuntas " name="incidenciasAdj" id="incidenciasAdj">';
            }
            if ($estado != "Facturado" && $accion != "ver"){
                    // El btn guardar solo se crea si el estado es "Nuevo","Sin Guardar","Guardado"
                 echo '<input class="btn btn-primary" '.$estilos['btn_guardar']
                 . ' type="submit" value="Guardar  (Alt+G)" name="Guardar" id="bGuardar" accesskey="G">';
            }
            ?>
                </div>
                <div class="col-md-4 text-right">
                    <?php
            if ($estado != "Facturado" || $accion != "ver"){
                // Mostramos input temporal
                echo ' temporal:'.'<input type="text" readonly size ="4" name="idTemporal" value="'.$idDocumentoTemporal.'">';
            }



            if ($estado === "Nuevo" ){
                // El btn cancelar solo se crea si el estado es "Nuevo"
                // pero solo se muestra cuando hay un temporal, ya que no tiene sentido mostrarlo si no hay temporal
                echo '<input type="submit" class="btn btn-danger" value="Borrar Temporal" '.$estilos['btn_cancelar'].' name="Cancelar" id="bCancelar">';
            }
            ?>
                </div>
            </div>
            <div class="row">
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
                            <input type="text" id="estado" name="estado" size="9" value="<?php echo $estado; ?>"
                                readonly>
                        </div>
                        <div class="col-md-3">
                            <label>Creado por:</label>
                            <input type="text" id="Usuario" name="Usuario" value="<?php echo $creado_por['nombre']; ?>"
                                size="8" readonly>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="text-center">Proveedor</label>
                        <?php
                    echo '<div class="col-md-2">
                                                <input type="text" id="id_proveedor" name="id_proveedor" data-obj= "cajaIdProveedor" value="'
                        . $idProveedor . '" ' . $estilos['pro_readonly'] . ' size="2" onkeydown="controlEventos(event)" placeholder="id">
                                            </div>';
                    echo '<div class="col-md-10">
                                                <input type="text" id="Proveedor" name="Proveedor" data-obj= "cajaProveedor" '
                        . 'placeholder="Nombre de proveedor (Alt+P)" onkeydown="controlEventos(event)" value="'
                        . $nombreProveedor . '" ' . $estilos['pro_readonly'] . ' size="60" accesskey="P" />'
                        . ' <a id="buscar" ' . $estilos['pro_styleNo'] . ' class="btn glyphicon glyphicon-search buscar"'
                        . ' onclick="buscarProveedor(' . "'" . $dedonde . "'" . ',Proveedor.value)"></a>
                                            </div>';
                ?>
                    </div>

                </div>
                <!-- Tabla de lineas de productos -->
                <div class="col-md-12">
                    <div class="col-md-12 form-inline bg-warning" id="Row0" <?php echo $estilos['styleNo']; ?>>
                        <div class="form-group">
                            <input id="idArticulo" type="text" name="idArticulo" placeholder="idArticulo"
                                data-obj="cajaidArticulo" size="4" value="" onkeydown="controlEventos(event)">
                        </div>
                        <div class="form-group">
                            <input id="Referencia" type="text" name="Referencia" placeholder="Referencia"
                                data-obj="cajaReferencia" size="8" value="" onkeydown="controlEventos(event)">
                        </div>
                        <div class="form-group">
                            <input id="ReferenciaPro" type="text" name="ReferenciaPro" placeholder="Ref_proveedor"
                                data-obj="cajaReferenciaPro" size="10" value="" onkeydown="controlEventos(event)"
                                <?php echo (($estado != "Nuevo")?'  autofocus' : '');?>>
                        </div>
                        <div class="form-group">
                            <input id="Codbarras" type="text" name="Codbarras" placeholder="Codbarras"
                                data-obj="cajaCodBarras" size="12" value="" data-objeto="cajaCodBarras"
                                onkeydown="controlEventos(event)">
                        </div>
                        <div class="form-group">
                            <input id="Descripcion" type="text" name="Descripcion" placeholder="Descripcion"
                                data-obj="cajaDescripcion" size="17" value="" onkeydown="controlEventos(event)">
                        </div>
                    </div>
                    <div class="col-lg-9">
                        <table id="tabla" class="table table-striped">
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
                                //Recorremos los productos y vamos escribiendo las lineas.
                                if (isset($productos)){
                                    foreach (array_reverse($productos) as $producto){
                                        
                                            $html = htmlLineaProducto($producto, $dedonde, $estilos['readonly']);
                                            echo $html['html'];
                                        }
                                    }
                                    ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="col-lg-3 pie-ticket">
                        <table id="tabla-pie" class="table">
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
