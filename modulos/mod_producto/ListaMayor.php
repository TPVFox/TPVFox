<!DOCTYPE html>
<html>
    <head>
        <?php
        include './../../head.php';
        include './funciones.php';
        include ("./../../plugins/paginacion/paginacion.php");
        include ("./../../controllers/Controladores.php");
        include ("./clases/ClaseProductos.php");
        include ('../../clases/articulos.php');
        
        $CArticulos = new Articulos($BDTpv);
        $Tienda = $_SESSION['tiendaTpv'];
        $idTienda = $Tienda['idTienda'];
        $dedonde = "ListaEtiquetas";
        ?>
        <script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
        <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
        <script src="<?php echo $HostNombre; ?>/modulos/mod_producto/mayor.js"></script> 
    </head>

    <body>

        <?php
        include './../../header.php';
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
                    <h2> Etiquetas: Imprimir Mayor del producto </h2>
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
                        <input type="submit" name="Imprimir" value="Imprimir">
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
                                        <a onclick="selecionarItemProducto(<?php echo $producto; ?>, 'ListaEtiquetas')">
                                            <span class="glyphicon glyphicon-trash"></span>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
                            echo '<input type="hidden" id="idsproducto" value="'.implode(', ', $productos),'" />';
                            ?>
                        </tbody>
                    </table>
                </div>
                <div id="tablamayor" class="row" style="display: none">
                    
                </div>
            </div>
        </div>

    </body>
</html>
