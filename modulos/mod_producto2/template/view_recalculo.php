<!DOCTYPE html>
<html>
<head>
    <?php include_once $URLCom . '/head.php'; ?>
    <script src="<?= $HostNombre ?>/modulos/mod_producto/funciones.js"></script>
    <script src="<?= $HostNombre ?>/modulos/mod_producto/js/AccionesDirectas.js"></script>
    <script src="<?= $HostNombre ?>/lib/js/teclado.js"></script>
    <script>
        <?= $VarJS ?>
    </script>
</head>
<body>
    <?php include_once $URLCom . '/modulos/mod_menu/menu.php'; ?>

    <div class="container">
        <h2 class="text-center"><?= htmlspecialchars($titulo) ?></h2>

        <?php if (!empty($guardadoOk)): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <span class="glyphicon glyphicon-ok"></span> Precios guardados correctamente.
        </div>
        <?php endif; ?>

        <form action="" method="post" name="formProducto" onkeypress="return event.keyCode !== 13">
            <div class="row">

                <!-- Columna de acciones -->
                <div class="col-md-2">
                    <div class="btn-group-vertical" style="width:100%;">
                        <a href="<?= $ruta_volver ?>" class="btn btn-default btn-sm" style="margin-bottom:6px;">
                            <span class="glyphicon glyphicon-arrow-left"></span> Volver
                        </a>
                        <button type="submit" name="Guardar" class="btn btn-primary btn-sm" style="margin-bottom:6px;"
                                <?= !empty($cacheExists) ? 'disabled title="Ya guardado"' : '' ?>>
                            <span class="glyphicon glyphicon-floppy-disk"></span> Guardar
                        </button>
                        <?php if (!empty($cacheExists)): ?>
                        <button type="button" class="btn btn-default btn-sm" style="margin-bottom:6px;"
                                onclick="imprimirRecalculo(<?= $id ?>)">
                            <span class="glyphicon glyphicon-print"></span> Imprimir
                        </button>
                        <div class="btn-group" style="width:100%; margin-bottom:6px;">
                            <button type="button" id="btnSeleccionados" class="btn btn-warning btn-sm dropdown-toggle"
                                    data-toggle="dropdown" disabled
                                    title="Selecciona productos para habilitar">
                                <span class="glyphicon glyphicon-tag"></span> Seleccionados <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu" style="width:100%;">
                                <li><a href="#" onclick="procesarSeleccion('etiquetas'); return false;">
                                    <span class="glyphicon glyphicon-barcode"></span> Imprimir etiquetas</a></li>
                                <li><a href="#" onclick="procesarSeleccion('mayor'); return false;">
                                    <span class="glyphicon glyphicon-list-alt"></span> Imprimir mayor</a></li>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Columna de datos -->
                <div class="col-md-10">

                    <!-- Datos del albarán -->
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-xs-6 col-sm-2">
                                    <label>Fecha albarán:</label>
                                    <input type="date" name="fecha" value="<?= $fecha ?>" class="form-control" readonly>
                                </div>
                                <div class="col-xs-6 col-sm-2">
                                    <label>ID Proveedor:</label>
                                    <input type="text" name="idProveedor" value="<?= htmlspecialchars($datosAlbaran['idProveedor']) ?>" class="form-control" readonly>
                                </div>
                                <div class="col-xs-12 col-sm-5">
                                    <label>Nombre comercial:</label>
                                    <input type="text" name="nombreProveedor" value="<?= htmlspecialchars($datosProveedor['nombrecomercial']) ?>" class="form-control" readonly>
                                </div>
                                <div class="col-xs-12 col-sm-3">
                                    <label>Estado albarán:</label>
                                    <input type="text" name="estado" value="<?= htmlspecialchars($datosAlbaran['estado']) ?>" class="form-control" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de productos -->
                    <div>
                <?php if (!empty($cacheExists)): ?>
                <!-- Vista guardada: datos desde XML, solo lectura con selección -->
                <table class="table table-bordered table-hover table-condensed">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selTodos" title="Seleccionar todos"></th>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Referencia</th>
                            <th>Coste anterior</th>
                            <th>Coste nuevo</th>
                            <th>PVP anterior</th>
                            <th>PVP nuevo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productosXml as $prod): ?>
                        <tr>
                            <td><input type="checkbox" name="selArticulo[]" value="<?= $prod['idArticulo'] ?>" class="chk-articulo"></td>
                            <td><?= $prod['idArticulo'] ?></td>
                            <td><?= htmlspecialchars($prod['nombre']) ?></td>
                            <td><?= htmlspecialchars($prod['referencia']) ?></td>
                            <td><?= $prod['costeAnterior'] ?></td>
                            <td><?= $prod['costeNuevo'] ?></td>
                            <td><?= $prod['pvpAnterior'] ?></td>
                            <td><strong><?= $prod['pvpNuevo'] ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <!-- Vista edición: datos desde historial BD -->
                <table class="table table-bordered table-hover table-condensed">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Referencia</th>
                            <th>Coste último</th>
                            <th>Coste anterior</th>
                            <th>Beneficio %</th>
                            <th>IVA %</th>
                            <th>PVP actual</th>
                            <th>PVP recomendado</th>
                            <th>Eliminar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($productosHistoricos as $producto):
                            if ($producto['estado'] === 'Revisado') {
                                continue;
                            }
                            $datosArticulo          = $CArticulo->datosPrincipalesArticulo($producto['idArticulo']);
                            $datosPrecios           = $CArticulo->articulosPrecio($producto['idArticulo']);
                            $datosArticuloProveedor = $CArticulo->buscarReferencia($producto['idArticulo'], $datosAlbaran['idProveedor']);
                            $ivaPrecio              = $datosArticulo['iva'] / 100;
                            $precioProducto         = $producto['Nuevo'] * (1 + $ivaPrecio);
                            $pvpRecomendado         = $precioProducto * (1 + $datosArticulo['beneficio'] / 100);
                            $classFila              = in_array($producto['estado'], ['Pendiente', 'Sin revisar'], true) ? '' : 'tachado';
                        ?>
                        <tr id="Row<?= $i ?>" class="<?= $classFila ?>">
                            <td><?= $producto['idArticulo'] ?></td>
                            <td><?= htmlspecialchars($datosArticulo['articulo_name']) ?></td>
                            <td><?= htmlspecialchars($datosArticuloProveedor['crefProveedor'] ?? '') ?></td>
                            <td><?= $producto['Nuevo'] ?></td>
                            <td><?= $producto['Antes'] ?></td>
                            <td><?= $datosArticulo['beneficio'] ?></td>
                            <td><?= $datosArticulo['iva'] ?></td>
                            <td><?= number_format($datosPrecios['pvpCiva'], 4) ?></td>
                            <td>
                                <?php if ($producto['estado'] === 'Sin revisar'): ?>
                                    <input type="text" id="pvpRecomendado_<?= $i ?>" name="pvpRecomendado_<?= $i ?>"
                                           class="form-control input-sm" style="width:80px;"
                                           onkeydown="controlEventos(event)" data-obj="pvpRecomendado"
                                           value="<?= number_format($pvpRecomendado, 2) ?>" disabled>
                                    <span class="glyphicon glyphicon-ban-circle text-danger"
                                          title="Este producto tiene recalculos de precio posteriores"></span>
                                <?php elseif ($producto['estado'] === 'Sin Cambios'): ?>
                                    <input type="text" id="pvpRecomendado_<?= $i ?>" name="pvpRecomendado_<?= $i ?>"
                                           class="form-control input-sm" style="width:80px;"
                                           onkeydown="controlEventos(event)" data-obj="pvpRecomendado"
                                           value="<?= number_format($pvpRecomendado, 2) ?>" disabled>
                                <?php else: ?>
                                    <input type="text" id="pvpRecomendado_<?= $i ?>" name="pvpRecomendado_<?= $i ?>"
                                           class="form-control input-sm" style="width:80px;"
                                           onkeydown="controlEventos(event)" data-obj="pvpRecomendado"
                                           value="<?= number_format($pvpRecomendado, 2) ?>">
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($producto['estado'] === 'Sin revisar'): ?>
                                    <!-- sin accion -->
                                <?php elseif ($producto['estado'] === 'Pendiente'): ?>
                                    <a onclick="cambiarEstadoRecalculo(<?= $producto['idArticulo'] ?>, 'albaran', <?= $id ?>, 'compras', <?= $i ?>, 'eliminar')"
                                       class="btn btn-xs btn-danger" title="Quitar del recalculo">
                                        <span class="glyphicon glyphicon-trash"></span>
                                    </a>
                                <?php else: ?>
                                    <a onclick="cambiarEstadoRecalculo(<?= $producto['idArticulo'] ?>, 'albaran', <?= $id ?>, 'compras', <?= $i ?>, 'retorno')"
                                       class="btn btn-xs btn-default" title="Recuperar">
                                        <span class="glyphicon glyphicon-export"></span>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php
                            if (in_array($producto['estado'], ['Pendiente', 'Sin revisar', 'Sin Cambios'], true)) {
                                $i++;
                            }
                        endforeach;
                        ?>
                    </tbody>
                </table>
                <?php endif; ?>
                    </div><!-- fin tabla -->
                </div><!-- fin col-md-10 -->

            </div><!-- fin row -->
        </form>
    </div>

    <!-- Modal conflicto selección -->
    <div class="modal fade" id="modalSelConflicto" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Selección existente</h4>
                </div>
                <div class="modal-body">
                    <p></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning btn-sm" onclick="ejecutarSeleccion('agregar')">
                        <span class="glyphicon glyphicon-plus"></span> Agregar a existente
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="ejecutarSeleccion('reemplazar')">
                        <span class="glyphicon glyphicon-refresh"></span> Reemplazar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php echo '<script src="' . $HostNombre . '/plugins/modal/func_modal.js"></script>'; ?>
    <?php include $URLCom . '/plugins/modal/ventanaModal.php'; ?>

    <script>
        var urlTareasRecalculo = '<?= $HostNombre ?>/modulos/mod_producto2/tareas.php';

        // Override: evita que 'accion' colisione con el enrutador de tareas.php
        function cambiarEstadoRecalculo(idArticulo, dedonde, id, tipo, fila, accion) {
            $.ajax({
                data: {
                    pulsado:    'cambiarEstadoRecalculo',
                    idArticulo: idArticulo,
                    dedonde:    dedonde,
                    id:         id,
                    tipo:       tipo,
                    operacion:  accion
                },
                url:  urlTareasRecalculo,
                type: 'post',
                success: function (response) {
                    var resultado = $.parseJSON(response);
                    var nuevaAccion, icono;
                    if (resultado.accion === 'eliminar') {
                        nuevaAccion = 'retorno';
                        icono = 'glyphicon-export';
                        $('#Row' + fila).addClass('tachado');
                    } else {
                        nuevaAccion = 'eliminar';
                        icono = 'glyphicon-trash';
                        $('#Row' + fila).removeClass('tachado');
                    }
                    $('#Row' + fila + ' > .eliminar').html(
                        '<a onclick="cambiarEstadoRecalculo(' + idArticulo + ', \'' + dedonde + '\', ' + id + ', \'' + tipo + '\', ' + fila + ', \'' + nuevaAccion + '\')" class="btn btn-xs ' + (nuevaAccion === 'retorno' ? 'btn-danger' : 'btn-default') + '">' +
                        '<span class="glyphicon ' + icono + '"></span></a>'
                    );
                }
            });
        }

        function imprimirRecalculo(id) {
            $.post(urlTareasRecalculo, { pulsado: 'imprimirRecalculo', id: id }, function (response) {
                var resultado = $.parseJSON(response);
                if (resultado.error) {
                    alert(resultado.error);
                } else {
                    window.open(resultado.fichero);
                }
            });
        }

        $('#selTodos').on('change', function () {
            $('.chk-articulo').prop('checked', this.checked);
            actualizarBtnSeleccionados();
        });

        $(document).on('change', '.chk-articulo', function () {
            var total = $('.chk-articulo').length;
            var marcados = $('.chk-articulo:checked').length;
            $('#selTodos').prop('indeterminate', marcados > 0 && marcados < total);
            $('#selTodos').prop('checked', marcados === total);
            actualizarBtnSeleccionados();
        });

        function actualizarBtnSeleccionados() {
            var haySeleccion = $('.chk-articulo:checked').length > 0;
            $('#btnSeleccionados').prop('disabled', !haySeleccion);
        }

        var _destino, _ids;

        function procesarSeleccion(destino) {
            _ids = [];
            $('.chk-articulo:checked').each(function () { _ids.push($(this).val()); });
            if (!_ids.length) return;
            _destino = destino;

            $.post(urlTareasRecalculo, { accion: 'obtener' }, function (resp) {
                var resultado = (typeof resp === 'string') ? $.parseJSON(resp) : resp;
                if (resultado.total > 0) {
                    $('#modalSelConflicto .modal-body p').text(
                        'Tienes ' + resultado.total + ' producto(s) seleccionado(s) anteriormente. ¿Qué deseas hacer con los ' + _ids.length + ' producto(s) nuevos?'
                    );
                    $('#modalSelConflicto').modal('show');
                } else {
                    ejecutarSeleccion('agregar');
                }
            });
        }

        function ejecutarSeleccion(modo) {
            $('#modalSelConflicto').modal('hide');
            var accion = (modo === 'reemplazar') ? 'establecerSeleccion' : 'agregarASeleccion';
            $.post(urlTareasRecalculo, { accion: accion, ids: _ids }, function () {
                window.location.href = '<?= $HostNombre ?>/modulos/mod_producto2/ListaSeleccion.php?desde=' + _destino;
            });
        }
    </script>
</body>
</html>
