<!DOCTYPE html>
<html>

<head>
    <?php
    include_once './../../inicial.php';
    include_once $URLCom . '/head.php';
    include_once $URLCom . '/modulos/mod_balanza/clases/ClaseBalanza.php';
    include_once $URLCom . '/plugins/paginacion/ClasePaginacion.php';

    $CBalanza = new ClaseBalanza($BDTpv);
    $balanzas = $CBalanza->todasBalanzas();
    $balanzas = $balanzas['datos'] ?? array();

    // --- Paginado de los productos de las balanzas: buscar por producto y filtrar por balanza --- //
    $NPaginado = new PluginClasePaginacion(__FILE__);
    $NPaginado->SetCamposControler(array('articulo_name'));
    $NPaginado->SetCampoFiltro('nombreBalanza');
    $NPaginado->SetOrderConsulta('articulo_name, nombreBalanza');
    $filtro = $NPaginado->GetFiltroWhere('OR');
    $filtroParams = $NPaginado->GetFiltroParams();
    $cantidadProductos = $CBalanza->contarProductosEnBalanzas($filtro, $filtroParams);
    $NPaginado->SetCantidadRegistros($cantidadProductos);
    $htmlPG = $NPaginado->htmlPaginado();
    $htmlBuscar = $NPaginado->htmlBuscar();
    $nombresBalanzas = array_column($balanzas, 'nombreBalanza');
    $htmlFiltrar = $NPaginado->htmlFiltrar($nombresBalanzas, 'Filtrar por balanza');
    $productosEnBalanzas = $CBalanza->productosEnBalanzas($filtro . $NPaginado->GetLimitConsulta(), $filtroParams);
    $productosEnBalanzas = $productosEnBalanzas['datos'];
    ?>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_balanza/funciones.js?v=<?php echo filemtime(__DIR__ . '/funciones.js'); ?>"></script>
    <script src="<?php echo $HostNombre; ?>/lib/js/tpvfoxSinExport.js"></script>

</head>

<body>
    <?php
    include_once $URLCom . '/modulos/mod_menu/menu.php';
    ?>

    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center">
                <h2> Balanzas: Editar y Añadir Balanzas </h2>
            </div>
            <nav class="col-sm-2" id="myScrollspy">
                <h4> Balanzas</h4>
                <h5> Opciones para una selección</h5>
                <ul class="nav nav-pills nav-stacked">
                    <?php if ($ClasePermisos->getAccion("crear") == 1) { ?>
                        <li><a href="#section2" onclick="metodoClick('AgregarBalanza');">Añadir</a></li>
                        <li>
                            <a href="#section2" onclick="metodoClick('EliminarBalanza', 'balanza');">
                                Eliminar <span title="Solo disponible si la balanza no tiene plus asociados">*</span>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
                <table class="table table-striped table-hover">
                    <?php
                    $check = 0;
                    foreach ($balanzas as $balanza) {
                        $check = $check + 1;
                    ?>
                        <tr>
                            <td><input type="checkbox" id="checkBalanza<?php echo $check; ?>" name="checkBalanza<?php echo $check; ?>" value="<?php echo $balanza['idBalanza']; ?>" class="check_balanza"></td>
                            <td><?php echo escapar($balanza['nombreBalanza']); ?></td>
                            <td>
                                <?php if ($ClasePermisos->getAccion("modificar") == 1) { ?>
                                    <a class="glyphicon glyphicon-pencil" title="Editar" href="./balanza.php?id=<?php echo $balanza['idBalanza']; ?>&accion=editar"></a>
                                <?php } ?>
                                <a class="glyphicon glyphicon-eye-open" title="Ver" href="./balanza.php?id=<?php echo $balanza['idBalanza']; ?>&accion=ver"></a>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </table>
            </nav>
            <div class="col-md-10">
                <p>-Productos en balanzas encontrados: <?php echo $cantidadProductos; ?></p>
                <?php echo $htmlPG; ?>
                <?php echo $htmlBuscar; ?>
                <?php echo $htmlFiltrar; ?>
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>PRODUCTO</th>
                            <th>REFERENCIA</th>
                            <th>TIPO</th>
                            <th>PRECIO</th>
                            <th>BALANZA</th>
                            <th>MODELO</th>
                            <th>SECCIONES</th>
                            <th>SOLO PLUS</th>
                            <th>EN LA BALANZA</th>
                            <th>TECLA</th>
                            <th>SECCIÓN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($productosEnBalanzas as $linea) {
                            $conSeccion = 'No';
                            if (strtolower((string) $linea['conSeccion']) === 'si') {
                                $conSeccion = 'Sí';
                            }
                            $soloPlus = 'No';
                            if ((int) $linea['soloPLUS'] === 1) {
                                $soloPlus = 'Sí';
                            }
                            $precio = '';
                            if ($linea['pvpCiva'] !== null) {
                                $precio = number_format((float) $linea['pvpCiva'], 2, '.', '');
                            }
                            // Pulsar una fila abre su balanza para ver
                            echo '<tr style="cursor:pointer" onclick="window.location.href=\'./balanza.php?id=' . $linea['idBalanza'] . '&accion=ver\'">'
                                . '<td>' . $linea['idArticulo'] . '</td>'
                                . '<td>' . escapar($linea['articulo_name']) . '</td>'
                                . '<td>' . escapar($linea['crefTienda']) . '</td>'
                                . '<td>' . escapar($linea['tipo']) . '</td>'
                                . '<td>' . $precio . '</td>'
                                . '<td>' . escapar($linea['nombreBalanza']) . '</td>'
                                . '<td>' . escapar($linea['modelo']) . '</td>'
                                . '<td>' . $conSeccion . '</td>'
                                . '<td>' . $soloPlus . '</td>'
                                . '<td>' . $linea['relacion'] . '</td>'
                                . '<td>' . $linea['plu'] . '</td>'
                                . '<td>' . $linea['seccion'] . '</td>'
                                . '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>
