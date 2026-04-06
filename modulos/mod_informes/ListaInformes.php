<?php
include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_informes/funciones.php';
include_once $URLCom . '/modulos/mod_informes/clases/ClaseInformes.php';
$CInformes = new ClaseInformes();
?>

<!DOCTYPE html>
<html>

<head>
    <?php include_once $URLCom . '/head.php'; ?>
</head>

<body>
    <?php
    include_once $URLCom . '/modulos/mod_menu/menu.php';
    ?>

    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center">
                <h2> Listados de informes </h2>
            </div>

            <nav class="col-sm-2" id="myScrollspy">
                <div data-offset-top="505">
                    <h4> Informes</h4>
                    <h5> Opciones para una selección</h5>
                    <ul class="nav nav-pills nav-stacked">
                        <?php
                        if ($ClasePermisos->getAccion("ejecutar") == 1) {
                        ?>
                            <li><a href="#section1" onclick="metodoClick('Ejecutar');">Ejecutar</a></li>
                        <?php
                        }
                        ?>
                        <li><a>Exportar CSV</a></li>
                    </ul>
                </div>
            </nav>
            <div class="col-md-10">
                <div class="col-md-4 form-group">
                    <label>Fecha Inicio</label>
                    <input type="date" id="idFechaInicio" name="fechaInicio">
                </div>
                <div class="col-md-4 form-group">
                    <label>Fecha Final</label>
                    <input type="date" id="idFechaFinal" name="fechaFinal">
                </div>
                <div>
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th></th>
                                <th>NOMBRE INFORME</th>
                                <th>DESCRIPCION INFORME</th>
                                <th>Opciones</th>
                                <th>Guardado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input class="rowCheck" type="checkbox" name="informe" value="1"></td>
                                <td><strong>Suma compras por proveedores</strong></td>
                                <td>Suma los albaranes de proveedor en el rango de fechas, agrupados por artículo</td>
                                <td>
                                    <select id="opcion1">
                                        <option value="1" selected>Todos</option>
                                        <option value="2">Facturados</option>
                                        <option value="3">Sin facturar</option>
                                        <option value="4">Proveedores activos</option>
                                    </select>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><input class="rowCheck" type="checkbox" name="informe" value="2"></td>
                                <td><strong>Suma compras por familias</strong></td>
                                <td>Suma los albaranes de proveedor agrupando por familia, subfamilia y/o producto</td>
                                <td>
                                    <select id="opcion2" onchange="_onOpcionChange(2)">
                                        <option value="1" selected>Solo familias</option>
                                        <option value="2">Familias y subfamilias</option>
                                        <option value="3">Familias, subfamilias y productos</option>
                                        <option value="4">Selección de familias</option>
                                    </select>
                                    <div id="panelFamilias2" style="display:none; margin-top:6px;">
                                        <button type="button" class="btn btn-default btn-xs" onclick="abrirCatalogoFamilias()">
                                            <span class="glyphicon glyphicon-list"></span> Elegir familias
                                        </button>
                                        <div id="tagsFamilias2" style="margin-top:4px;">
                                            <span class="text-muted">Ninguna familia seleccionada</span>
                                        </div>
                                        <input type="hidden" id="familiasSel2" value="">
                                    </div>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><input class="rowCheck" type="checkbox" name="informe" value="4"></td>
                                <td><strong>Suma de ventas por familia</strong></td>
                                <td>Suma albaranes de cliente y tickets cerrados agrupados por familia, subfamilia y/o producto</td>
                                <td>
                                    <select id="opcion4" onchange="_onOpcionChange(4)">
                                        <option value="1" selected>Solo familias</option>
                                        <option value="2">Familias y subfamilias</option>
                                        <option value="3">Familias, subfamilias y productos</option>
                                        <option value="4">Selección de familias</option>
                                    </select>
                                    <div id="panelFamilias4" style="display:none; margin-top:6px;">
                                        <button type="button" class="btn btn-default btn-xs" onclick="abrirCatalogoFamilias()">
                                            <span class="glyphicon glyphicon-list"></span> Elegir familias
                                        </button>
                                        <div id="tagsFamilias4" style="margin-top:4px;">
                                            <span class="text-muted">Ninguna familia seleccionada</span>
                                        </div>
                                        <input type="hidden" id="familiasSel4" value="">
                                    </div>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><input class="rowCheck" type="checkbox" name="informe" value="6"></td>
                                <td><strong>Beneficio por familia</strong></td>
                                <td>Margen bruto (venta &minus; coste actual) agrupado por familia, subfamilia y/o producto</td>
                                <td>
                                    <select id="opcion6" onchange="_onOpcionChange(6)">
                                        <option value="1" selected>Solo familias</option>
                                        <option value="2">Familias y subfamilias</option>
                                        <option value="3">Familias, subfamilias y productos</option>
                                        <option value="4">Selección de familias</option>
                                    </select>
                                    <div id="panelFamilias6" style="display:none; margin-top:6px;">
                                        <button type="button" class="btn btn-default btn-xs" onclick="abrirCatalogoFamilias()">
                                            <span class="glyphicon glyphicon-list"></span> Elegir familias
                                        </button>
                                        <div id="tagsFamilias6" style="margin-top:4px;">
                                            <span class="text-muted">Ninguna familia seleccionada</span>
                                        </div>
                                        <input type="hidden" id="familiasSel6" value="">
                                    </div>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><input class="rowCheck" type="checkbox" name="informe" value="7"></td>
                                <td><strong>Fluctuacion de coste mensual</strong></td>
                                <td>Tendencia de coste promedio por mes, con agregacion por articulo/familia/subfamilia</td>
                                <td>
                                    <select id="opcion7" onchange="_onOpcionChange(7)" class="form-control input-sm" style="margin-bottom:6px;">
                                        <option value="1">Solo familias</option>
                                        <option value="2">Familias y subfamilias</option>
                                        <option value="3" selected>Familias, subfamilias y articulos</option>
                                        <option value="4">Seleccion de familias</option>
                                    </select>
                                    <div id="panelFamilias7" style="display:none; margin-bottom:6px;">
                                        <button type="button" class="btn btn-default btn-xs" onclick="abrirCatalogoFamiliasCosteFluctuacion()">
                                            <span class="glyphicon glyphicon-list"></span> Elegir familias
                                        </button>
                                        <button type="button" class="btn btn-link btn-xs" onclick="limpiarFamiliasCosteFluctuacion()">Limpiar</button>
                                        <div id="cfTagsFamilias" style="margin-top:4px;">
                                            <span class="text-muted">Sin filtro de familias</span>
                                        </div>
                                        <input type="hidden" id="familiasSel7" value="">
                                    </div>
                                    <div class="row" style="margin:0;">
                                        <div class="col-sm-4" style="padding-left:0;">
                                            <label style="font-size:11px; margin-bottom:2px;">Min rec/mes</label>
                                            <input type="number" min="1" step="1" id="cfMinRecepciones" class="form-control input-sm" value="3">
                                        </div>
                                        <div class="col-sm-4">
                                            <label style="font-size:11px; margin-bottom:2px;">Min meses</label>
                                            <input type="number" min="1" step="1" id="cfMinMeses" class="form-control input-sm" value="3">
                                        </div>
                                        <div class="col-sm-4" style="padding-right:0; padding-top:18px;">
                                            <label style="font-weight:normal; font-size:12px;">
                                                <input type="checkbox" id="cfIncluirEspecial" value="1"> Prov. Especial
                                            </label>
                                        </div>
                                    </div>
                                </td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Declaramos variables globales
        var checkID = [];
    </script>
    <!-- Cargamos funciones de modulo. -->
    <script src="<?php echo $HostNombre; ?>/modulos/mod_informes/funciones.js" type="module"></script>
    <?php
    echo '<script src="' . $HostNombre . '/plugins/modal/func_modal.js"></script>';
    include $URLCom . '/plugins/modal/ventanaModal.php';
    ?>
</body>

</html>
