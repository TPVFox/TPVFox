<!DOCTYPE html>
<html>
    <head>
        <?php
        include_once './../../inicial.php';
        include_once $URLCom.'/head.php';
        include_once $URLCom.'/modulos/mod_producto/funciones.php';
        include_once $URLCom.'/plugins/paginacion/paginacion.php';
        include_once $URLCom.'/controllers/Controladores.php';
      	include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
        include_once $URLCom.'/clases/articulos.php';

        $CArticulos = new Articulos($BDTpv);
        //~ $Tienda = $_SESSION['tiendaTpv']; // Ya no hace falta ya que la creamos inicial.
        $idTienda = $Tienda['idTienda'];
        $dedonde = "ListaEtiquetas";
        ?>
        <script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
        <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
        <script src="<?php echo $HostNombre; ?>/modulos/mod_producto/mayor.js"></script> 
    </head>

    <body>

        <?php
        //~ include_once $URLCom.'/header.php';
        include_once $URLCom.'/modulos/mod_menu/menu.php';
//~ echo $_POST['tamanhos'];
//~ echo '<pre>';
//~ print_r($_SESSION['productos_seleccionados']);
//~ echo '</pre>';
        ?>
        <script type="text/javascript">
<?php
if (isset($_POST['Imprimir'])) {
    echo 'imprimirEtiquetas(' . "'" . json_encode($_SESSION['productos_seleccionados']) . "'" . ',"' . $dedonde . '","'
    . $idTienda . '","' . $_POST['tamanhos'] . '");';
}
?>
        </script>
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h2> Productos: Imprimir Mayor del producto </h2>
                </div>
                <form action="javascript:MayorProductos();" method="post" name="formProducto" >
                    <div class="col-sm-2">
                        <a class="text-right" href="./ListaProductos.php">Volver Atrás</a>
                        <br><br>
                        Fecha desde: 
                        <input type="text" class="calendar" id="inputFechadesde" />
                        <br><br>
                        Fecha hasta: 
                        <input type="text" class="calendar" id="inputFechahasta" />
                        <br><br>
                        <input type="submit" name="generar" value="Generar">
                    </div>
                </form>
                <div class="col-md-10">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>PRODUCTO</th>
                                <th>P.V.P</th>
                                <th>STOCK INICIAL</th>
                                <th>ELIMINAR</th>
                                <th>VISUALIZAR</th>
                                <th>IMPRIMIR</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $productos = [];
                            foreach ($_SESSION['productos_seleccionados'] as $producto) {
                                $articulo = $CArticulos->buscarNombreArticulo($producto);
                                $precio = $CArticulos->articulosPrecio($producto);
                                $productos[] = $producto;
                                ?>
                                <tr>
                                    <td><?php echo $producto; ?></td>
                                    <td><?php echo $articulo['articulo_name']; ?></td>
                                    <td><?php echo number_format($precio['pvpCiva'], 2); ?>€</td>
                                    <td><input type="text" value="0" style="text-align: right" id="<?php echo 'stkini' . $producto; ?>"</td>
                                    <td>
                                        <a onclick="selecionarItemProducto(<?php echo $producto; ?>, 'ListaMayor')">
                                            <span class="glyphicon glyphicon-trash"></span>
                                        </a>
                                    </td>
                                    <td ><div id="visualizar<?php echo $producto; ?>" style="display:none">
                                        <button class="btn btn-link boton-visualizar" 
                                                type="button" 
                                                data-toggle="collapse" 
                                                data-productoid="<?php echo $producto; ?>"
                                                data-target="#multiCollapseExample<?php echo $producto; ?>" 
                                                aria-expanded="false" 
                                                aria-controls="multiCollapseExample<?php echo $producto; ?>">
                                            <span class="glyphicon glyphicon-eye-open"></span>
                                        </button>
                                            </div>
                                    </td>
                                    <td id="imprimir<?php echo $producto; ?>"> </td>
                                </tr>
                                <tr id="linea_<?php echo $producto; ?>" style="display: none">
                                    <td colspan="7">
                                        <div class="collapse multi-collapse" id="multiCollapseExample<?php echo $producto; ?>">
                                            Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 
                                            Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident.
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                            echo '<input type="hidden" id="idsproducto" value="' . implode(', ', $productos), '" />';
                            ?>
                        </tbody>
                    </table>
                </div>
                <div id="tablamayor" class="col-10" style="display: none">

                </div>
            </div>
        </div>

    </body>
</html>
