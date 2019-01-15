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
        $ClassProductos = new ClaseProductos($BDTpv);
        $CArticulos = new Articulos($BDTpv);
        $idTienda = $Tienda['idTienda'];
        ?>
        <script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
        <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
        <?php 
        $Nproductos = array();
        foreach ($_SESSION['productos_seleccionados'] as $key =>$producto) {
            $articulo = $ClassProductos->GetProducto($producto);
            //~ $precio = $CArticulos->articulosPrecio($producto);
            $Nproductos[$key]['idArticulo']     = $articulo['idArticulo'];
            $Nproductos[$key]['pvpCiva']        = $articulo['pvpCiva'];
            $Nproductos[$key]['ultimoCoste']        = $articulo['ultimoCoste'];
            $Nproductos[$key]['estado']         = $articulo['estado'];
            $Nproductos[$key]['articulo_name']  = $articulo['articulo_name'];
            $Nproductos[$key]['stock']  = $articulo['stocks']['stockOn'];
            $Nproductos[$key]['tipo']  = $articulo['tipo'];

        }
        $fecha = date('Y-m-d');
        $fecha_inicial = strtotime ( '- 15 days' , strtotime ( $fecha ) ) ;
        $fecha_inicial = date ( 'Y-m-d' , $fecha_inicial );


        ?>
        

        
    </head>

    <body>

        <?php
        include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h2>Mayor del productos seleccionados </h2>
                </div>
                <form action="javascript:MayorProductos();" method="post" name="formProducto" >
                    <div class="col-sm-2">
                        <a class="text-right" href="./ListaProductos.php">Volver Atrás</a>
                        <h4>Fechas por defecto</h4>
                        Fecha desde: 
                        <input type="date" class="calendar" id="fecha_inicio" value="<?php echo $fecha_inicial;?>" />
                        <br><br>
                        Fecha hasta: 
                        <input type="date" class="calendar" id="fecha_final" value="<?php echo date("Y-m-d");?>" />
                    </div>
                </form>
                <div class="col-md-10">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>IdArticulo</th>
                                <th>PRODUCTO</th>
                                <th>PVP<br/>con iva</th>
                                <th>COSTE</th>
                                <th>Tipo</th>
                                <th>STOCK INICIAL<span class="glyphicon glyphicon-info-sign" title="Indicamos con cuanto stock quieres que empiece el mayor"></span></th>
                                <th>STOCK ACTUAL</th>
                                <th>ELIMINAR <span class="glyphicon glyphicon-info-sign" title="Lo borramos de la selección"></span></th>
                                <th>VISUALIZAR</th>
                                <th>IMPRIMIR</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($Nproductos as $producto) {
                                ?>
                                <tr>
                                    <td><?php echo $producto['idArticulo']; ?></td>
                                    <td><?php echo $producto['articulo_name']; ?></td>
                                    <td><?php echo number_format($producto['pvpCiva'], 2); ?>€</td>
                                    <td><?php echo number_format($producto['ultimoCoste'], 2); ?>€</td>
                                    <td><?php echo $producto['tipo']; ?></td>

                                    <td><input type="text" size="6" value="0" style="text-align: right" id="<?php echo 'stkini' . $producto['idArticulo']; ?>"></td>
                                    <td>
                                        <?php
                                        // Si es de peso mostramos decimales , sino entero solo..
                                        $redondeo = 0;
                                        if ($producto['tipo'] === 'peso'){
                                            $redondeo = 3;
                                        }
                                         echo number_format(round($producto['stock'],3),$redondeo);
                                         //~ echo $producto['stock'];
                                         ?>
                                    </td>
                                    <td>
                                        <a onclick="selecionarItemProducto(<?php echo $producto['idArticulo']; ?>, 'ListaMayor')">
                                            <span class="glyphicon glyphicon-trash"></span>
                                        </a>
                                    </td>
                                    <td>
                                        <a onclick="redirecionarMayor(<?php echo $producto['idArticulo']; ?>,'DetalleMayor')">
                                            <span class="glyphicon glyphicon-eye-open"></span>
                                        </a>
                                    </td>
                                    <td>
                                        <a onclick="selecionarItemProducto(<?php echo $producto['idArticulo']; ?>, 'ListaMayor')">
                                            <span class="glyphicon glyphicon-print"></span>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
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
