<!DOCTYPE html>
<html>

<head>
    <?php
    include './../../head.php';
    include_once './clases/ClaseReorganizar.php';
    include_once $URLCom . '/controllers/Controladores.php';
    include_once $URLCom . '/controllers/parametros.php';
    $CReorganizar = new ClaseReorganizar;
    $ClasesParametros = new ClaseParametros('parametros.xml');
    $Controler = new ControladorComun;
    $Controler->loadDbtpv($BDTpv);

    $Tienda = $_SESSION['tiendaTpv'];
    $idTienda = $Tienda['idTienda'];

    $dedonde = 'reorganizarTPV';
    $total_usuarios = count($CReorganizar->obtenerUsuarios());

    //Cargamos la configuración por defecto y las acciones de las cajas
    $parametros = $ClasesParametros->getRoot();
    foreach ($parametros->cajas_input->caja_input as $caja) {
        // Ahora cambiamos el parametros por defecto que tiene dedonde = pedido y le ponemos albaran
        $caja->parametros->parametro[0] = $dedonde;
    }
    $VarJS = $Controler->ObtenerCajasInputParametros($parametros);
    ?>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_reorganizacion/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_reorganizacion/comprobacion.js"></script>
    <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_reorganizacion/js/AccionesDirectas.js"></script>
</head>

<body>
    <?php
    include_once $URLCom . '/modulos/mod_menu/menu.php';
    ?>

    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center">
                <h2> Reorganización y Limpieza </h2>
            </div>
            <div class="col-md-10" id="tablareorg">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Acción</th>
                            <th>Item a reorganizar</th>
                            <th>Progreso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($ClasePermisos) && $ClasePermisos->getAccion("regenerar_stock") == 1) { ?>
                            <!-- Regenerar stock -->
                            <tr>
                                <td><button id="boton-stock" class="btn">
                                        <span class="glyphicon glyphicon-save"> </span>Regenerar Stock</button></td>
                                <td>Regenerar Stock según ventas y entradas tpv</td>
                                <td>
                                    <div class="progress" style="margin:0 100px">
                                        <div id="bar0" class="progress-bar progress-bar-info"
                                            role="progressbar" aria-valuenow="0"
                                            aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                                            0 % completado
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (isset($ClasePermisos) && $ClasePermisos->getAccion("cerrar_stock_anual") == 1) { ?>
                            <!-- Cerrar stock (finalizar año) -->
                            <tr>
                                <!-- id="boton-cerrar-stock" se debe pasar al boton dentro del modal el modal sirve para
                                 1) Definir el proveedor que generara el albaran de cierre
                                 2) Definir las familias que se incluiran en el albaran de cierre
                                 3) Definir las familias que se omitiran en el albaran de cierre -->
                                <td><button class="btn" onclick="modalCerrarStock()">
                                        <span class="glyphicon glyphicon-save"> </span>Cerrar Stock</button></td>
                                <td>Cerrar stock del año actual y cerrar ejercicio creando albaranes de cierre</td>
                                <td>
                                    <div class="progress" style="margin:0 100px">
                                        <div id="bar-cerrar-stock" class="progress-bar progress-bar-info"
                                            role="progressbar" aria-valuenow="0"
                                            aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                                            0 % completado
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php
                        $puedeComprobar = isset($ClasePermisos) && $ClasePermisos->getAccion('ComprobarExistencias') == 1;
                        $puedeAdmitir = isset($ClasePermisos) && $ClasePermisos->getAccion('AdmitirComprobacion') == 1;
                        $puedeExportarComprobacion = isset($ClasePermisos) && $ClasePermisos->getAccion('ExportarComprobacion') == 1;
                        if ($puedeComprobar || $puedeAdmitir) { ?>
                            <!-- Comprobación de existencias en el cambio de año: el modal elige el ejercicio -->
                            <tr>
                                <td><button id="boton-comprobacion-stock" class="btn" onclick="abrirModalComprobacionStock()">
                                        <span class="glyphicon glyphicon-search"> </span>Comprobar existencias</button></td>
                                <td>Comprobar los productos con existencia negativa en el ejercicio vigente, o admitir en el anterior el fichero del vigente</td>
                                <td>
                                    <div class="progress" style="margin:0 100px">
                                        <div id="bar-comprobacion-stock" class="progress-bar progress-bar-info"
                                            role="progressbar" aria-valuenow="0"
                                            aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                                            0 % completado
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (isset($ClasePermisos) && $ClasePermisos->getAccion('ver', array('modulo' => 'mod_reorganizacion', 'vista' => 'AplicarRelacion.php')) == 1) { ?>
                            <!-- Aplicar una relación a un periodo -->
                            <tr>
                                <td><a class="btn" href="AplicarRelacion.php">
                                        <span class="glyphicon glyphicon-scissors"> </span>Aplicar relación</a></td>
                                <td>Aplicar una relación a lo que entró en un periodo y preparar su albarán de compras</td>
                                <td></td>
                            </tr>
                        <?php } ?>

                        <?php if (count($CReorganizar->SetPlugin('ClaseVirtuemart')->TiendaWeb) > 0) {;
                            // Solo mostramos si hay web conectada a tienda principal.
                        ?>
                            <?php if (isset($ClasePermisos) && $ClasePermisos->getAccion("subir_stock_web") == 1) { ?>
                                <!-- Subir stock y precios a web -->
                                <tr>
                                    <td><button id="boton_subir_stock" class="btn">
                                            <span class="glyphicon glyphicon-save"> </span>Subir Stock y Precios</button></td>
                                    <td>Subir Stock y precios a web</td>
                                    <td>
                                        <div class="progress" style="margin:0 100px">
                                            <div id="bar1" class="progress-bar progress-bar-info"
                                                role="progressbar" aria-valuenow="0"
                                                aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                                                0 % completado
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>

                        </tr>
                        <?php if (isset($ClasePermisos) && $ClasePermisos->getAccion("reorganizar") == 1) { ?>
                            <!-- Reorganizar permisos de usuarios -->
                            <tr>
                                <td><button onclick=reorganizarPermisosModulos(0,<?php echo $total_usuarios; ?>) class="btn">
                                        <span class="glyphicon glyphicon-save"> </span>Reorganizar permisos</button></td>
                                <td>Limpiar y crea permisos de modulos inexistentes</td>
                                <td>
                                    <div class="progress" style="margin:0 100px">
                                        <div id="bar2" class="progress-bar progress-bar-info"
                                            role="progressbar" aria-valuenow="0"
                                            aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                                            0 % completado
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php if ($puedeComprobar || $puedeAdmitir) { ?>
                <!-- Resultado de la comprobación de existencias: un panel propio, apartado de las operaciones -->
                <div class="col-md-12" style="margin-top:30px">
                    <div id="panelComprobacionStock" class="panel panel-info" style="display:none">
                        <div class="panel-heading">
                            <button type="button" class="close" onclick="cerrarPanelComprobacionStock()" title="Cerrar el resultado">&times;</button>
                            <h3 class="panel-title">Comprobación de existencias — <span id="tituloComprobacionStock"></span></h3>
                        </div>
                        <div class="panel-body">
                            <?php if ($puedeExportarComprobacion) { ?>
                                <div style="margin-bottom:10px">
                                    <button id="btnComprobacionStockExportar" type="button" class="btn btn-default" disabled onclick="exportarComprobacionStockVigenteXML()" style="display:none">
                                        <span class="glyphicon glyphicon-download-alt"></span> Descargar fichero de intercambio</button>
                                    <button id="btnComprobacionStockAnteriorExportar" type="button" class="btn btn-default" disabled onclick="exportarInformeComprobacionStockAnterior()" style="display:none">
                                        <span class="glyphicon glyphicon-download-alt"></span> Descargar informe final</button>
                                </div>
                            <?php } ?>
                            <div id="areaComprobacionStock"></div>
                        </div>
                    </div>
                </div>
                <!-- Contenido del modal: el ejercicio que se comprueba -->
                <script type="text/template" id="plantillaModalComprobacionStock">
                    <?php if ($puedeComprobar) { ?>
                        <div class="radio"><label><input type="radio" name="ejercicioComprobacion" id="radioComprobacionVigente" value="vigente" checked>
                            Ejercicio vigente: calcular los productos con existencia negativa</label></div>
                        <div class="checkbox" style="margin-left:20px"><label><input type="checkbox" id="chkComprobacionStockModoEstricto">
                            Modo estricto (trunca a cero el saldo de apertura de cada producto)</label></div>
                        <!-- El filtro del vigente: unas familias, con sus subfamilias, o unos proveedores. Las
                             mismas cajas y búsquedas que el cierre de stock anual; cada una se añade a la lista. -->
                        <div style="margin-left:20px">
                            <label class="small">Solo las familias:</label>
                            <div class="row" style="margin-bottom:10px">
                                <div class="col-xs-3" style="padding-right:5px;">
                                    <input type="text" id="id_familia" name="id_familia" data-obj="cajaIdFamilia" class="form-control input-sm" placeholder="ID" onkeydown="controlEventos(event)">
                                </div>
                                <div class="col-xs-9">
                                    <div class="input-group">
                                        <input type="text" id="Familia" name="Familia" data-obj="cajaFamilia" class="form-control input-sm" placeholder="Nombre Familia..." onkeydown="controlEventos(event)">
                                        <span class="input-group-btn">
                                            <button type="button" class="btn btn-sm btn-default" title="Buscar" onclick="buscarFamilia('<?php echo $dedonde; ?>','Familia.value')"><i class="glyphicon glyphicon-search"></i></button>
                                            <button type="button" class="btn btn-sm btn-default" title="Catálogo" id="btnCatalogoFamiliasComprobacionStock" onclick="catalogoFamilias()"><i class="glyphicon glyphicon-book"></i></button>
                                            <button type="button" class="btn btn-sm btn-default" title="Añadir" id="btnAgregarFamiliaComprobacionStock" onclick="agregarFiltroComprobacionStock('familia')"><i class="glyphicon glyphicon-plus"></i></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <label class="small">o solo los proveedores:</label>
                            <div class="row" style="margin-bottom:10px">
                                <div class="col-xs-8" style="padding-right:5px;">
                                    <input type="text" class="form-control input-sm" name="Proveedor" id="Proveedor" data-obj="cajaProveedor" placeholder="Nombre..." onkeydown="controlEventos(event)">
                                </div>
                                <div class="col-xs-4" style="padding-left:0;">
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" name="id_proveedor" id="id_proveedor" data-obj="cajaIdProveedor" placeholder="ID" onkeydown="controlEventos(event)">
                                        <span class="input-group-btn">
                                            <button type="button" class="btn btn-default" onclick="buscarProveedor('<?php echo $dedonde; ?>','Proveedor.value')"><i class="glyphicon glyphicon-search"></i></button>
                                            <button type="button" class="btn btn-default" title="Añadir" id="btnAgregarProveedorComprobacionStock" onclick="agregarFiltroComprobacionStock('proveedor')"><i class="glyphicon glyphicon-plus"></i></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <ul class="list-inline" id="listaFiltroComprobacionStock"></ul>
                            <div class="text-danger small" id="avisoFiltroComprobacionStock"></div>
                        </div>
                    <?php } ?>
                    <?php if ($puedeAdmitir) { ?>
                        <div class="radio"><label><input type="radio" name="ejercicioComprobacion" id="radioComprobacionAnterior" value="anterior" <?php echo $puedeComprobar ? '' : 'checked'; ?>>
                            Ejercicio anterior: admitir el fichero de intercambio del vigente</label></div>
                        <form id="formAdmitirComprobacionStock" enctype="multipart/form-data" style="margin-left:20px">
                            <input class="form-control" type="file" id="ficheroComprobacionStock" name="ficheroComprobacionStock" accept=".xml">
                        </form>
                    <?php } ?>
                    <button type="button" class="btn btn-primary" id="btnComprobacionStockIniciar" onclick="iniciarComprobacionStockDesdeModal()">Comprobar</button>
                </script>
            <?php } ?>
        </div>
</body>

</html>

<?php // Incluimos paginas modales
echo '<script src="' . $HostNombre . '/plugins/modal/func_modal.js"></script>';
include $RutaServidor . '/' . $HostNombre . '/plugins/modal/ventanaModal.php';
?>


<script type="text/javascript">
    $(function() {

        $("#boton-stock").on("click", function(event) {
            event.stopPropagation();
            event.preventDefault();

            contarProductosEstoqueables(function(respuesta) {
                var obj = JSON.parse(respuesta);
                if (obj.totalProductos > 0) {
                    var totalProductos = obj.totalProductos;
                    $("#bar0").show();
                    $("#boton-stock").prop("disabled", true);
                    RegenerarStock(0, 100, totalProductos, '0');
                }

            });
        });

        $(document).on("click", "#btnIniciarCierre", function(event) {
            event.stopPropagation();
            event.preventDefault();

            var configuracion = {}
            if ($('#modoManual').is(':checked')) {
                configuracion.modo = 'manual';
                configuracion.familiaSeleccionada = $('#id_familia').val();
                idProveedor = $('#id_proveedor').val();
            } else {
                idProveedor = $('#proveedorPredefinido').val();
                configuracion.modo = 'automatico';
            }
            // Si es modo manual obtenemos la unica familia a cerrar, si la dejamos vacia se cerraran todas las familias.
            var idFamiliaCierreStock = '';
            if (configuracion.modo === 'manual') {
                idFamiliaCierreStock = configuracion.familiaSeleccionada;
            }

            cerrarPopUpConTitulo("Cierre Stock Anual");
            contarFamiliasProductos(idFamiliaCierreStock, function(respuesta) {
                var obj = JSON.parse(respuesta);
                console.log("Respuesta contar familias:");
                console.log(obj);
                if (obj.length > 0) {
                    var familias = obj;
                    $("#bar-cerrar-stock").show();
                    $("#btnIniciarCierre").prop("disabled", true);
                    CerrarStockAnoActual(0, 1, familias, '-cerrar-stock', idProveedor, configuracion);
                }

            });
        });

        $("#boton_subir_stock").on("click", function(event) {
            event.stopPropagation();
            event.preventDefault();
            // La idea es subir fichero con todos los productos y su stock.
            // una vez subido en la web ejecutar en segundo plano.

            contarProductosWeb(function(respuesta) {
                var obj = JSON.parse(respuesta);
                if (obj.totalProductos > 0) {
                    var totalProductos = obj.totalProductos;
                    $("#bar1").show();
                    $("#boton_subir_stock").prop("disabled", true);
                    SubirStockWeb(0, 100, totalProductos, '1');
                }

            });
        });

    });
</script>

<script>
    // Creamos una variable global que el archivo .js externo pueda leer
    var V_JS = {
        dedonde: "<?php echo $dedonde; ?>"
    };
    <?php echo $VarJS; ?>
    var cabecera = [];
</script>

<script>
    function togglePanelManual() {
        const isManual = document.getElementById('modoManual').checked;
        document.getElementById('panelManual').style.display = isManual ? 'block' : 'none';
    }
</script>
