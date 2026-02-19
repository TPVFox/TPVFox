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

        $("#boton-cerrar-stock").on("click", function(event) {
            event.stopPropagation();
            event.preventDefault();

            var idProveedor = prompt("Introduce el ID del proveedor:");
            if (idProveedor == null || idProveedor == "") {
                alert("Operación cancelada. Debes introducir un ID de proveedor.");
                return;
            }

            contarFamiliasProductos(function(respuesta) {
                var obj = JSON.parse(respuesta);
                console.log("Respuesta contar familias:");
                console.log(obj);
                if (obj.length > 0) {
                    var familias = obj;
                    $("#bar-cerrar-stock").show();
                    $("#boton-cerrar-stock").prop("disabled", true);
                    CerrarStockAnoActual(0, 1, familias, '-cerrar-stock', idProveedor);
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
