<!DOCTYPE html>
<html>
<head>
    <?php include_once $URLCom . '/head.php'; ?>
    <?php if ($script_ObjVirtuemart): ?>
        <?= $script_ObjVirtuemart ?>
    <?php endif; ?>
    <script>var configuracion = <?= json_encode($configuracion) ?>;</script>
    <script src="<?= $HostNombre ?>/jquery/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="<?= $HostNombre ?>/jquery/jquery-ui.min.css">
    <link rel="stylesheet" href="<?= $HostNombre ?>/modulos/mod_productos/css/mod_productos.css">
    <script src="<?= $HostNombre ?>/lib/js/autocomplete.js"></script>
    <script src="<?= $HostNombre ?>/modulos/mod_producto/funciones.js"></script>
    <script src="<?= $HostNombre ?>/modulos/mod_producto/js/AccionesDirectas.js"></script>
    <script src="<?= $HostNombre ?>/controllers/global.js"></script>
    <script src="<?= $HostNombre ?>/lib/js/teclado.js"></script>
    <script>
        var checkID = [];
        <?= $VarJS ?>
    </script>
</head>
<body>
    <?php include_once $URLCom . '/modulos/mod_menu/menu.php'; ?>

    <div class="container">

        <?php foreach ($CTArticulos->GetComprobaciones() as $c): ?>
            <div class="alert alert-<?= $c['tipo'] ?>"><?= $c['mensaje'] ?></div>
            <?php if ($c['tipo'] === 'danger') { exit(); } ?>
        <?php endforeach; ?>

        <div class="row">

            <!-- ===== COLUMNA LATERAL: ACCIONES ===== -->
            <div class="col-sm-2 col-xs-12">

                <h2>Productos</h2>

                <h4>Acciones</h4>
                <ul class="nav nav-pills nav-stacked">
                    <?php if ($ClasePermisos->getAccion('crear') == 1): ?>
                        <li><a onclick="metodoClick('AgregarProducto');">Añadir</a></li>
                    <?php endif; ?>
                    <?php if ($ClasePermisos->getAccion('modificar') == 1): ?>
                        <li><a onclick="metodoClick('VerProducto', 'producto');">Modificar</a></li>
                    <?php endif; ?>
                </ul>

                <!-- Bloque de seleccionados -->
                <div class="productos_seleccionados panel panel-default" <?= $prod_seleccion['display'] ?>>
                    <div class="panel-heading">
                        <h4 style="margin:0 0 8px 0;">Acciones a Seleccionados <span class="label label-default textoCantidad"><?= $prod_seleccion['NItems'] ?></span></h4>
                        <div class="row" style="display:flex; align-items:center;">
                            <div class="col-xs-8">
                                <label class="checkbox-inline" style="font-weight:bold; margin:0;">
                                    <input type="checkbox" id="checkSeleccion" onclick="seleccionProductos()"
                                                   <?= $configuracion['filtro']->valor === 'Si' ? 'checked' : '' ?>>
                                    Filtrar los <span class="textoCantidad"><?= $prod_seleccion['NItems'] ?></span> seleccionados
                                </label>
                            </div>
                            <div class="col-xs-4 text-right">
                                <?php if ($ClasePermisos->getAccion('eliminarSeleccion') == 1): ?>
                                    <button class="btn btn-xs btn-default" onclick="limpiarSeleccion();" title="Borrar selección">
                                        <span class="glyphicon glyphicon-trash"></span> Eliminar <br/>la seleccion
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <ul class="list-group" style="margin-bottom:0;">
                        <?php if ($ClasePermisos->getAccion('imprimirEtiquetas') == 1): ?>
                            <li class="list-group-item">
                                <a href="<?= $HostNombre ?>/modulos/mod_productos/ListaSeleccion.php?modo=etiquetas">
                                    <span class="glyphicon glyphicon-tag"></span> Imprimir Etiquetas
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if ($ClasePermisos->getAccion('imprimirMayor') == 1): ?>
                            <li class="list-group-item">
                                <a href="<?= $HostNombre ?>/modulos/mod_productos/ListaSeleccion.php?modo=mayor">
                                    <span class="glyphicon glyphicon-list-alt"></span> Imprimir Mayor
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if ($ClasePermisos->getAccion('agregarProductosFamilia') == 1): ?>
                            <li class="list-group-item"><a onclick="modalFamiliaProducto('0','ListadoProductos');">Guardar por familia</a></li>
                        <?php endif; ?>
                        <?php if ($ClasePermisos->getAccion('cambiarEstado') == 1): ?>
                            <li class="list-group-item"><a onclick="modalEstadoProductos();">Cambiar estado</a></li>
                        <?php endif; ?>
                        <?php if ($ClasePermisos->getAccion('exportarCsv') == 1): ?>
                            <li class="list-group-item"><a href="<?= $HostNombre ?>/modulos/mod_productos/ExportarCsvProductos.php" target="_blank">
                                <span class="glyphicon glyphicon-download-alt"></span> Exportar a CSV
                            </a></li>
                        <?php endif; ?>
                        <?php if ($ClasePermisos->getAccion('eliminarProductos') == 1): ?>
                            <?php $id_ti = isset($tiendaWeb['idTienda']) ? $tiendaWeb['idTienda'] : 0; ?>
                            <li class="list-group-item"><a class="text-danger" onclick="eliminarProductos(<?= $id_ti ?>);">Eliminar Productos</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Configuracion de columnas -->
                <div class="nav_configuracion">
                    <h4>Configuración</h4>
                    <?= $htmlConfiguracion['htmlCheck'] ?>
                </div>

            </div><!-- /col lateral -->

            <!-- ===== COLUMNA PRINCIPAL: LISTADO ===== -->
            <div class="col-sm-10 col-xs-12">

                <?php
                    $hayFiltroEstado    = $configuracion['estado_filtro'] !== '';
                    $hayFiltroSeleccion = $prod_seleccion['NItems'] > 0 && $configuracion['filtro']->valor === 'Si';
                    $hayBusqueda        = $NPaginado->GetBusqueda() !== '';
                    $hayFiltroFamilia   = $idFamiliaFiltro !== 0;
                    $hayAlgunFiltro     = $hayFiltroEstado || $hayFiltroSeleccion || $hayBusqueda || $hayFiltroFamilia;
                ?>
                <p>
                    Productos encontrados: <strong><?= $CantidadRegistros ?></strong>
                    <?php if ($hayFiltroEstado): ?>
                        <span class="label label-info" title="Filtro activo por estado">
                            <span class="glyphicon glyphicon-filter"></span> Estado: <?= htmlspecialchars($configuracion['estado_filtro']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($hayFiltroSeleccion): ?>
                        <span class="label label-warning" title="Mostrando solo los productos seleccionados">
                            <span class="glyphicon glyphicon-check"></span> Selección: <?= $prod_seleccion['NItems'] ?> productos
                        </span>
                    <?php endif; ?>
                    <?php if ($hayBusqueda): ?>
                        <span class="label label-default" title="Búsqueda activa">
                            <span class="glyphicon glyphicon-search"></span> "<?= htmlspecialchars($NPaginado->GetBusqueda()) ?>"
                        </span>
                    <?php endif; ?>
                    <?php if ($hayFiltroFamilia): ?>
                        <span class="label label-success" title="Filtro activo por familia">
                            <span class="glyphicon glyphicon-folder-open"></span> Familia: <?= htmlspecialchars($filtro_familia_nombre) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($hayAlgunFiltro): ?>
                        <button type="button" class="btn btn-xs btn-danger" onclick="quitarTodosLosFiltros();" title="Eliminar todos los filtros activos">
                            <span class="glyphicon glyphicon-remove"></span> Quitar filtros
                        </button>
                    <?php endif; ?>
                </p>

                <?= $htmlPG ?>

                <!-- Formulario de filtros/búsqueda -->
                <form action="./ListaProductos.php" method="GET" name="formBuscar">
                    <div class="row">

                        <div class="form-group col-md-4">
                            <label>Buscar por:
                                <select onchange="GuardarBusqueda(event);" name="SelectBusqueda">
                                    <?= $htmlConfiguracion['htmlOption'] ?>
                                </select>
                            </label>
                            <input id="buscar" type="text" name="buscar" autofocus size="25"
                                   value="<?= htmlspecialchars($NPaginado->GetBusqueda()) ?>">
                            <input type="submit" value="Buscar" class="btn btn-default btn-sm">
                        </div>

                        <div class="col-md-3">
                            <label>Por Familia:</label>
                            <select id="combobox" class="familiasLista">
                                <option></option>
                                <option value="0">Sin familia</option>
                                <?php foreach (selectFamilias(0, '', [], $BDTpv) as $f): ?>
                                    <option title="<?= $f['title'] ?>" value="<?= $f['id'] ?>"><?= $f['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p id="botonEnviar"></p>
                        </div>

                        <div class="col-md-3">
                            <label>Por Proveedor:</label>
                            <select id="combobox" class="proveedoresLista">
                                <option value="0"></option>
                                <?php foreach ($todosProveedores as $pro): ?>
                                    <option value="<?= $pro['idProveedor'] ?>"><?= htmlspecialchars($pro['nombrecomercial']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p id="botonEnviarPro"></p>
                        </div>

                        <div class="col-md-2">
                            <label>Estado:</label>
                            <select onchange="GuardarFiltroEstado(event);" name="FiltroEstado">
                                <?= $htmlEstadosProducto ?>
                            </select>
                        </div>

                    </div>
                </form>

                <!-- Tabla de productos -->
                <table class="table table-bordered table-hover tablaPrincipal">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="checkUsuTodos" onclick="seleccionarTodo()"></th>
                            <th>ID</th>
                            <th>PRODUCTO</th>
                            <?php if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'], 'codBarras') === 'Si'): ?>
                                <th><span class="glyphicon glyphicon-barcode" title="Código Barras"></span></th>
                            <?php endif; ?>
                            <?php if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'], 't.crefTienda') === 'Si'): ?>
                                <th>REFERENCIA</th>
                            <?php endif; ?>
                            <th>COSTE</th>
                            <th title="Beneficio">%</th>
                            <th>P.S.IVA</th>
                            <th>IVA</th>
                            <th>P.V.P</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $checkUser = 0;
                        foreach ($productos as $prod):
                            $producto  = $CTArticulos->GetProducto($prod['idArticulo']);
                            $checkUser++;
                            $checked   = '';
                            if (isset($prod_seleccion['Items']) && in_array($producto['idArticulo'], $prod_seleccion['Items'])) {
                                $checked = 'checked';
                            }
                            $textoFamilia = '';
                            if (is_array($producto['familias'])) {
                                foreach ($producto['familias'] as $fam) {
                                    $textoFamilia .= ' ' . $fam['familiaNombre'];
                                }
                            }
                            $tdClick = '<td style="cursor:pointer" onclick="UnProductoClick(\'' . $producto['idArticulo'] . '\')">';
                            $decimal = $producto['tipo'] === 'peso' ? 3 : 0;
                        ?>
                        <tr>
                            <td class="rowUsuario">
                                <input type="checkbox"
                                       id="checkUsu<?= $checkUser ?>"
                                       onchange="seleccionarProducto(<?= $producto['idArticulo'] ?>, this)"
                                       value="<?= $producto['idArticulo'] ?>"
                                       <?= $checked ?>>
                            </td>
                            <?= $tdClick . $producto['idArticulo'] ?></td>
                            <?= $tdClick . htmlspecialchars($producto['articulo_name']) ?>
                                <br><sub><?= htmlspecialchars(trim($textoFamilia)) ?></sub>
                            </td>

                            <?php if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'], 'codBarras') === 'Si'): ?>
                                <td>
                                    <?php foreach ($producto['codBarras'] as $cod): ?>
                                        <small><?= $cod ?></small><br>
                                    <?php endforeach; ?>
                                </td>
                            <?php endif; ?>

                            <?php if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'], 't.crefTienda') === 'Si'): ?>
                                <td>
                                    <?php foreach ($producto['ref_tiendas'] as $ref): ?>
                                        <?php if ($ref['idTienda'] == $id_tienda_principal): ?>
                                            <?= $ref['crefTienda'] ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </td>
                            <?php endif; ?>

                            <td><?= number_format($producto['ultimoCoste'], 2) ?></td>
                            <td><?= $producto['beneficio'] ?></td>
                            <td class="text-right"><?= number_format($producto['pvpSiva'], 2) ?> <small>€</small></td>
                            <td><?= $producto['iva'] ?></td>
                            <td class="text-right"><?= number_format($producto['pvpCiva'], 2) ?> <small>€</small></td>
                            <td>
                                <?= number_format($producto['stocks']['stockOn'], $decimal) ?>
                                <?php if ($ClasePermisos->getAccion('regularizar') == 1): ?>
                                    <button class="btn btn-sm boton-regularizar"
                                            data-idarticulo="<?= $producto['idArticulo'] ?>">
                                        <span class="glyphicon glyphicon-pencil"></span>
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td><?= $producto['estado'] ?></td>

                            <td>
                                <?php if (($producto['tipo'] ?? '') === 'peso'): ?>
                                    <span class="glyphicon glyphicon-peso" title="Producto vendido por peso"></span>
                                <?php endif; ?>
                                <a href="<?= $HostNombre ?>/modulos/mod_producto/DetalleMayor.php?idArticulo=<?= $producto['idArticulo'] ?>"
                                   title="Ver mayor de <?= htmlspecialchars($producto['articulo_name']) ?>"
                                   class="btn btn-xs btn-default">
                                    <span class="glyphicon glyphicon-list-alt"></span>
                                </a>
                                <?php if ($tiendaWeb && MostrarColumnaConfiguracion($configuracion['mostrar_lista'], 't.idVirtuemart') === 'Si'): ?>
                                    <?php
                                    if ($CTArticulos->GetReferenciasTiendas()) {
                                        foreach ($CTArticulos->GetReferenciasTiendas() as $ref) {
                                            if ($ref['idVirtuemart'] > 0) {
                                                $class_web = $ref['estado'] === 'Sin Publicar' ? 'icono_web despublicado' : '';
                                                echo '<a id="idProducto_estadoWeb_' . $producto['idArticulo'] . '"'
                                                    . ' target="_blank" class="glyphicon glyphicon-globe ' . $class_web . '"'
                                                    . ' href="' . $ObjVirtuemart->ruta_producto . $ref['idVirtuemart'] . '"'
                                                    . ' title="Ver en web"></a>';
                                            }
                                        }
                                    }
                                    ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (empty($productos)): ?>
                    <p class="text-muted">No hay productos para mostrar. Use el buscador para filtrar.</p>
                <?php endif; ?>

            </div><!-- /col principal -->

        </div><!-- /row -->
    </div><!-- /container -->

    <?php echo '<script src="' . $HostNombre . '/plugins/modal/func_modal.js"></script>'; ?>
    <?php include $URLCom . '/plugins/modal/ventanaModal.php'; ?>

    <div class="loader"></div>

    <script>
        var urlTareas = '<?= $HostNombre ?>/modulos/mod_productos/tareas.php';
        var idsSeleccionados = <?= json_encode($prod_seleccion['Items']) ?>;

        function seleccionProductos() {
            var activo = $('#checkSeleccion').prop('checked');
            configuracion.filtro.valor = activo ? 'Si' : 'No';
            AjaxGuardarConfiguracion();
            setTimeout(function () { window.location.reload(); }, 300);
        }

        $(document).ready(function () {
        });

        function quitarTodosLosFiltros() {
            delete configuracion.estado_filtro;
            configuracion.filtro.valor = 'No';
            // Limpiar busqueda en la URL antes de recargar
            var url = new URL(window.location.href);
            url.searchParams.delete('buscar');
            url.searchParams.delete('SelectBusqueda');
            url.searchParams.delete('familia');
            AjaxGuardarConfiguracion();
            setTimeout(function () { window.location.href = url.toString(); }, 500);
        }

        // Buscar productos de un proveedor y añadirlos a la seleccion
        function buscarProductosProveedor(idProveedor) {
            $.post(urlTareas, { accion: 'buscarProductosProveedor', idProveedor: idProveedor }, function (resp) {
                if (!resp.ok) {
                    alert('Este proveedor no tiene productos.');
                    return;
                }
                idsSeleccionados = resp.ids;
                $('.textoCantidad').text(resp.total);
                $('.productos_seleccionados').show();
                $('#botonEnviarPro').hide();
                refresh();
            }, 'json');
        }

        // Filtrar lista por familia (GET redirect, no añade a seleccion)
        function buscarProductosFamilia(idFamilia) {
            var url = new URL(window.location.href);
            url.searchParams.set('familia', idFamilia);
            window.location.href = url.toString();
        }

        // Marcar o desmarcar un producto — llama a tareas.php y actualiza el contador
        function seleccionarProducto(idArticulo, checkbox) {
            var accion = checkbox.checked ? 'agregar' : 'quitar';
            $.post(urlTareas, { accion: accion, idArticulo: idArticulo }, function (resp) {
                if (resp.ok) {
                    $('.textoCantidad').text(resp.total);
                    if (resp.total > 0) {
                        $('.productos_seleccionados').show();
                    } else {
                        $('.productos_seleccionados').hide();
                    }
                }
            }, 'json');
        }

        // Limpiar toda la seleccion
        function limpiarSeleccion() {
            $.post(urlTareas, { accion: 'limpiar' }, function (resp) {
                if (resp.ok) {
                    $('.rowUsuario input[type=checkbox]').prop('checked', false);
                    $('#checkUsuTodos').prop('checked', false);
                    $('.textoCantidad').text(0);
                    $('.productos_seleccionados').hide();
                }
            }, 'json');
        }

        // Seleccionar / deseleccionar todos los de la pagina
        function seleccionarTodo() {
            var marcado = $('#checkUsuTodos').prop('checked');
            $('.rowUsuario input[type=checkbox]').each(function () {
                if (this.checked !== marcado) {
                    this.checked = marcado;
                    seleccionarProducto($(this).val(), this);
                }
            });
        }

        <?php if ($prod_seleccion['NItems'] > 0): ?>
            $('.productos_seleccionados').show();
        <?php endif; ?>

        <?php if ($tiendaWeb && MostrarColumnaConfiguracion($configuracion['mostrar_lista'], 't.idVirtuemart') === 'Si'
                && $ClasePermisos->getModulo('mod_virtuemart') == 1
                && $CTArticulos->SetPlugin('ClaseVirtuemart') !== false
                && !empty($productos)): ?>
            <?php $ids = array_column($productos, 'idArticulo'); ?>
            var ids_productos = <?= json_encode($ids) ?>;
            var id_tiendaWeb  = <?= $tiendaWeb['idTienda'] ?>;
            $(document).ready(function () {
                obtenerEstadoProductoWeb(ids_productos, id_tiendaWeb);
            });
        <?php endif; ?>
    </script>

    <style>
        .loader {
            position: fixed; left: 0; top: 0; width: 100%; height: 100%;
            z-index: 9999; opacity: .8; display: none;
            background: url('<?= $HostNombre ?>/css/img/loading.gif') 50% 50% no-repeat rgb(249,249,249);
        }
    </style>
</body>
</html>
