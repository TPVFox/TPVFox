<?php
        include_once './../../inicial.php';
        include_once $URLCom.'/modulos/mod_producto/funciones.php';
        include './clases/ClaseRegularizaciones.php';
        $alArticulo = new alArticulosRegularizacion();
        $Productos = $alArticulo->leerTodos(['regu.estado=' . K_STOCKREGULARIZACION_ESTADO_ACTIVO], ['regu.*', 'arts.articulo_name']);
        ?>
<!DOCTYPE html>
<html>
    <head>
        <?php  include './../../head.php';?>
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
    </body>
</html>
