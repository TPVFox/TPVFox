<!DOCTYPE html>
<html>
<head>
    <?php include_once $URLCom . '/head.php'; ?>
    <script src="<?= $HostNombre ?>/modulos/mod_producto/funciones.js"></script>
    <script src="<?= $HostNombre ?>/modulos/mod_producto/js/AccionesDirectas.js"></script>
    <script src="<?= $HostNombre ?>/lib/js/tpvfoxSinExport.js"></script>
</head>
<body>
    <?php include_once $URLCom . '/modulos/mod_menu/menu.php'; ?>

    <div class="container">
        <div class="row">

            <div class="col-md-12">
                <h2 class="text-center">
                    <?= $modo === 'etiquetas' ? 'Etiquetas: Imprimir etiquetas' : 'Mayor de productos seleccionados' ?>
                </h2>

                <!-- Tabs -->
                <ul class="nav nav-tabs" style="margin-bottom:15px;">
                    <li class="<?= $modo === 'etiquetas' ? 'active' : '' ?>">
                        <a href="?modo=etiquetas">
                            <span class="glyphicon glyphicon-tag"></span> Etiquetas
                        </a>
                    </li>
                    <li class="<?= $modo === 'mayor' ? 'active' : '' ?>">
                        <a href="?modo=mayor">
                            <span class="glyphicon glyphicon-list-alt"></span> Mayor
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Panel de controles (ancho completo, encima de la tabla) -->
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-12" style="margin-bottom:8px;">
                                <?= $Controler->getHtmlLinkVolver('Volver') ?>
                            </div>
                        </div>

                        <?php if ($modo === 'etiquetas' && $ClasePermisos->getAccion('imprimirEtiquetas') == 1): ?>
                        <div class="row">
                            <div class="col-xs-6 col-sm-2">
                                <label>Tamaño:</label>
                                <select id="tamanhos" class="form-control">
                                    <option value="A5">A5</option>
                                    <option value="A7">A7</option>
                                    <option value="A8">A8</option>
                                    <option value="A9">A9</option>
                                </select>
                            </div>
                            <div class="col-xs-6 col-sm-2">
                                <label>Referencia / tecla:</label>
                                <select id="teclaOReferencia" class="form-control">
                                    <option value="1">Sin referencia</option>
                                    <option value="2">Con Tecla</option>
                                    <option value="3">Con Referencia</option>
                                </select>
                            </div>
                            <div class="col-xs-12 col-sm-2" style="padding-top:24px;">
                                <button type="button" class="btn btn-primary"
                                        onclick="imprimirEtiquetas('ListaEtiquetas')">
                                    <span class="glyphicon glyphicon-print"></span> Imprimir etiquetas
                                </button>
                            </div>
                        </div>

                        <?php elseif ($modo === 'mayor' && $ClasePermisos->getAccion('imprimirMayor') == 1): ?>
                        <div class="row">
                            <div class="col-xs-6 col-sm-2">
                                <label>Fecha inicio:</label>
                                <input type="date" id="fecha_inicio" class="form-control"
                                       value="<?= $fecha_inicial ?>">
                            </div>
                            <div class="col-xs-6 col-sm-2">
                                <label>Fecha final:</label>
                                <input type="date" id="fecha_final" class="form-control"
                                       value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-xs-12 col-sm-3" style="padding-top:24px;">
                                <button type="button" class="btn btn-primary"
                                        onclick="verMayorSeleccionados()">
                                    <span class="glyphicon glyphicon-eye-open"></span>
                                    Ver Mayor de seleccionados
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla principal -->
            <div class="col-md-12">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" class="checkSelectTodos"
                                       onclick="CambiarEstadoCheckTodos()">
                            </th>
                            <th>ID</th>
                            <th>Producto</th>
                            <th>P.V.P.</th>
                            <th>Coste</th>
                            <th>Tipo</th>
                            <th>Stock actual</th>
                            <th>
                                Acciones
                                <span class="glyphicon glyphicon-trash" title="Eliminar del listado"></span>
                                <?php if ($modo === 'mayor'): ?>
                                    <span class="glyphicon glyphicon-eye-open" title="Ver mayor por pantalla"></span>
                                <?php endif; ?>
                            </th>
                            <th>
                                <?= $modo === 'etiquetas' ? 'Cant. etiquetas' : 'Stock inicial' ?>
                                <?php if ($modo === 'mayor'): ?>
                                    <span class="glyphicon glyphicon-info-sign"
                                          title="Stock con el que empieza el mayor"></span>
                                <?php endif; ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($Nproductos as $producto): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="checkSelect"
                                           value="<?= $producto['idArticulo'] ?>" checked>
                                </td>
                                <td><?= $producto['idArticulo'] ?></td>
                                <td><?= htmlspecialchars($producto['articulo_name']) ?></td>
                                <td><?= number_format($producto['pvpCiva'], 2) ?>€</td>
                                <td><?= number_format($producto['ultimoCoste'], 2) ?>€</td>
                                <td>
                                    <?php if ($producto['tipo'] === 'peso'): ?>
                                        <span class="glyphicon glyphicon-peso" title="Producto por peso"></span>
                                    <?php else: ?>
                                        <?= htmlspecialchars($producto['tipo']) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $redondeo = $producto['tipo'] === 'peso' ? 3 : 0;
                                    echo number_format(round($producto['stock'], 3), $redondeo);
                                    ?>
                                </td>
                                <td>
                                    <a class="btn btn-xs btn-danger"
                                       onclick="eliminarDeSeleccion(<?= $producto['idArticulo'] ?>, this)"
                                       title="Quitar de la selección">
                                        <span class="glyphicon glyphicon-trash"></span>
                                    </a>
                                    <?php if ($modo === 'mayor'): ?>
                                        <a class="btn btn-xs btn-default"
                                           onclick="redirecionarMayor(<?= $producto['idArticulo'] ?>, 'DetalleMayor')"
                                           title="Ver mayor">
                                            <span class="glyphicon glyphicon-eye-open"></span>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td><?= $producto['input'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <?php echo '<script src="' . $HostNombre . '/plugins/modal/func_modal.js"></script>'; ?>
    <?php include $URLCom . '/plugins/modal/ventanaModal.php'; ?>

    <script>
        var urlTareas      = '<?= $HostNombre ?>/modulos/mod_producto2/tareas.php';
        var urlModProducto = '<?= $HostNombre ?>/modulos/mod_producto';

        // Quitar producto de la seleccion y eliminar la fila
        function eliminarDeSeleccion(idArticulo, btn) {
            $.post(urlTareas, { accion: 'quitar', idArticulo: idArticulo }, function (resp) {
                if (resp.ok) {
                    $(btn).closest('tr').fadeOut(300, function () { $(this).remove(); });
                    if (resp.total === 0) {
                        window.location.href = 'ListaProductos.php';
                    }
                }
            }, 'json');
        }

        // Override: usa ruta absoluta a mod_producto y lee fechas de los inputs locales
        function redirecionarMayor(idArticulo, adonde) {
            var fechaInicio = $('#fecha_inicio').val();
            var fechaFinal  = $('#fecha_final').val();
            var stock       = $('#stkini' + idArticulo).val() || '0';
            if (adonde === 'DetalleMayor') {
                window.open(
                    urlModProducto + '/DetalleMayor.php?idArticulo=' + idArticulo
                    + '&fecha_inicial=' + fechaInicio
                    + '&fecha_final='   + fechaFinal
                    + '&stock='         + stock,
                    '_blank'
                );
            }
        }

        // Abrir DetalleMayor para todos los productos marcados con las fechas del panel
        function verMayorSeleccionados() {
            var fechaInicio = $('#fecha_inicio').val();
            var fechaFinal  = $('#fecha_final').val();
            var ids = [];
            $('.checkSelect:checked').each(function () {
                ids.push($(this).val());
            });
            if (ids.length === 0) {
                alert('No hay productos seleccionados.');
                return;
            }
            // Abre una pestaña por cada producto con un pequeño retardo para evitar bloqueos del navegador
            $.each(ids, function (i, idArticulo) {
                setTimeout(function () {
                    var stock = $('#stkini' + idArticulo).val() || '0';
                    window.open(
                        urlModProducto + '/DetalleMayor.php?idArticulo=' + idArticulo
                        + '&fecha_inicial=' + fechaInicio
                        + '&fecha_final='   + fechaFinal
                        + '&stock='         + stock,
                        '_blank'
                    );
                }, i * 300);
            });
        }

        // Override: imprimirEtiquetas apunta a mod_producto2/tareas.php
        function imprimirEtiquetas(dedonde) {
            var idProductos        = TfObtenerCheck('checkSelect');
            var tamano             = $('#tamanhos option:selected').val();
            var teclaOReferencia   = $('#teclaOReferencia option:selected').val();
            var inputs_cantidades  = TfObtenerObjetos('cantidadEtiquetas');
            var productos          = [];
            inputs_cantidades.each(function () {
                var idArticulo = this.dataset.idarticulo;
                if (idProductos.indexOf(idArticulo) >= 0) {
                    productos.push({ idArticulo: idArticulo, numEtiquetas: this.value });
                }
            });
            $.post(urlTareas, {
                pulsado:            'imprimirEtiquetas',
                dedonde:            dedonde,
                tamano:             tamano,
                teclaOReferencia:   teclaOReferencia,
                productos:          JSON.stringify(productos)
            }, function (response) {
                var resultado = $.parseJSON(response);
                window.open(resultado['fichero']);
            });
        }
    </script>
</body>
</html>
