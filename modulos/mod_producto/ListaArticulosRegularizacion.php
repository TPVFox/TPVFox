<!DOCTYPE html>
<html>
    <head>
        <?php
        include './../../inicial.php';
        include './../../head.php';
        include './funciones.php';
        include './clases/ClaseRegularizaciones.php';

        $alArticulo = new alArticulosRegularizacion();
        $Productos = $alArticulo->leerTodos(['regu.estado=' . K_STOCKREGULARIZACION_ESTADO_ACTIVO], ['regu.*', 'arts.articulo_name']);
        ?>

        <script>
            // Declaramos variables globales
            var checkID = [];
        </script> 
        <!-- Cargamos fuciones de modulo. -->
        <script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
        <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
        <script src="<?php echo $HostNombre; ?>/plugins/modal/func_modal_reutilizables.js"></script>
    </head>

    <body>
        
        <?php
        //~ include './../../header.php';
        include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>

        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h2> Productos: Pendientes de regularizar stock </h2>
                </div>
                <div class="col-sm-2">
                    <div class="nav">
                        <h4> Productos</h4>
                        <h5> Opciones para una selección</h5>
                    </div>

                </div>

                <div class="col-md-10">
                    <!-- TABLA DE PRODUCTOS -->
                    <div>
                        <form action="ordenar">
<!--
                            <table class="table table-bordered table-hover">
                                <tr>
                                    <td><button class="btn btn-sm boton-ordenar" data-idarticulo="<?php //echo $producto['idArticulo']; ?>">Ordenar</button></td>
                                </tr>
                            </table>
-->
                        </form>
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>ID</th>
                                    <th>PRODUCTO</th>
                                    <th>Fecha reg</th>
                                    <th>Stock inicial</th>
                                    <th>Modificacion</th>
                                    <th>stock final</th>
                                    <th>idUsuario</th>
                                    <th>idAlbaran</th>
                                    <th>Estado</th>
                                    <th>botón alguna acción</th>

                                </tr>
                            </thead>

                            <?php
                            if (isset($Productos)) {
                                foreach ($Productos as $producto) {
                                    ?>

                                    <tr>
                                        <td></td>
                                        <td><?php echo $producto['id']; ?></td>
                                        <td><?php echo $producto['articulo_name']; ?></td>
                                        <td><?php echo $producto['fechaRegularizacion']; ?></td>
                                        <td ><?php echo number_format($producto['stockActual'], 2); ?></td>
                                        <td ><?php echo number_format($producto['stockModif'], 2); ?></td>
                                        <td ><?php echo number_format($producto['stockFinal'], 2); ?></td>
                                        <td><?php echo $producto['idUsuario']; ?></td>
                                        <td><?php echo $producto['idAlbaran']; ?></td>
                                        <td><?php echo $producto['estado']; ?></td>
                                        <td><button class="btn btn-sm boton-albaran" data-idarticulo="<?php echo $producto['idArticulo']; ?>">Albarán</button></td>
                                    </tr>

                                    <?php
                                }
                            }
                            ?>

                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal -->
        <div id="regularizaStockModal" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header btn-primary">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h3 class="modal-title text-center">Titulo configurable</h3>
                    </div>
                    <form id="fregulariza" action="javascript:grabarRegularizacion();">
                        <div class="modal-body">
                            <table>
                                <tr>
                                    <td colspan="3">
                                        <h5>Articulo a regularizar:<p id="nombre" > </p> </h5></td>                                    
                                </tr>
                                <tr>
                                    <td>Stock Actual</td>
                                    <td>Stock a colocar</td>
                                    <td>SUMAR al stock</td>
                                </tr>
                                <tr>
                                    <td><input type="text" id="stockactual" value="000" readonly="readonly"/></td>
                                    <td><input type="text" id="stockcolocar" value="000" /></td>
                                    <td><input type="text" id="stocksumar" value="000" /></td>
                                </tr>
                            </table>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-default" >Guardar</button>
                            <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                        </div>
                        <input type="hidden" id="articuloid" value="000" />
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>
