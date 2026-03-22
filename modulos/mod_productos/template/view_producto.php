<!DOCTYPE html>
<html>
<head>
    <?php include_once $URLCom . '/head.php'; ?>
    <script src="<?= $HostNombre ?>/jquery/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="<?= $HostNombre ?>/jquery/jquery-ui.min.css">
    <link rel="stylesheet" href="<?= $HostNombre ?>/modulos/mod_productos/css/mod_productos.css">
    <script src="<?= $HostNombre ?>/lib/js/autocomplete.js"></script>
    <script src="<?= $HostNombre ?>/modulos/mod_producto/funciones.js"></script>
    <script src="<?= $HostNombre ?>/modulos/mod_producto/js/AccionesDirectas.js"></script>
    <script src="<?= $HostNombre ?>/controllers/global.js"></script>
    <script src="<?= $HostNombre ?>/lib/js/teclado.js"></script>
    <script>
        <?= $VarJS ?>
        var producto = new Object();
        producto.idArticulo = <?= $id ?>;
        var ivas = <?= json_encode($ivas) ?>;
    </script>
</head>
<body>
    <?php include_once $URLCom . '/modulos/mod_menu/menu.php'; ?>

    <div class="container">

        <?php // --- Comprobaciones del producto --- ?>
        <?php if (isset($Producto['comprobaciones'])): ?>
            <?php foreach ($Producto['comprobaciones'] as $c): ?>
                <div class="alert alert-<?= $c['tipo'] ?>"><?= $c['mensaje'] ?></div>
            <?php endforeach; ?>
            <?php if (isset($Producto['error'])) { return; } ?>
        <?php endif; ?>

        <?php // --- Comprobaciones balanza --- ?>
        <?php foreach ($ComunicacionBalanza['Comprobaciones'] as $c): ?>
            <div class="alert alert-<?= $c['tipo'] ?>"><?= $c['mensaje'] ?></div>
        <?php endforeach; ?>

        <h2 class="text-center"><?= $titulo ?></h2>

        <form method="post" name="formProducto" onkeypress="return anular(event)">
        <div class="col-md-12">

            <div class="col-md-12">
                <?= $Link_volver ?>
                <input type="submit" value="Guardar" class="btn btn-primary">
            </div>

            <!-- ===== COLUMNA IZQUIERDA: datos del producto ===== -->
            <div class="col-md-6 Datos">

                <div class="row">
                    <div class="col-md-2">
                        <label>ID:</label> <?= $id ?>
                        <input type="hidden" id="id" name="id" value="<?= $id ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Estado</label>
                        <select id="idEstado" name="estado"><?= $htmlEstadosProducto ?></select>
                    </div>
                    <div class="col-md-2">
                        <label>Tipo:</label>
                        <?= $htmlTipo ?>
                    </div>
                    <?php if ($id > 0): ?>
                    <div class="col-md-4">
                        <label>Fecha Creación:</label>
                        <input type="date" value="<?= date('Y-m-d', strtotime($Producto['fecha_creado'])) ?>" disabled>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="row">
                    <div class="form-group col-lg-3">
                        <label>Referencia:</label>
                        <input type="text" id="referencia" name="cref_tienda_principal" size="10"
                               data-obj="cajaReferencia" onkeydown="controlEventos(event)"
                               value="<?= htmlspecialchars($Producto['cref_tienda_principal']) ?>">
                    </div>
                    <div class="form-group col-lg-9">
                        <label>Nombre producto:</label>
                        <input type="text" id="nombre" name="articulo_name" size="50" required
                               data-obj="cajaNombre" onkeydown="controlEventos(event)"
                               value="<?= htmlspecialchars($Producto['articulo_name']) ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12"><h4>Costes del Producto</h4></div>
                    <div class="form-group col-md-4">
                        <label>
                            Coste Último:
                            <a onclick="desActivarCoste(event)">
                                <span title="Editar coste para recalcular. No cambia en BD." class="glyphicon glyphicon-cog"></span>
                            </a>
                        </label>
                        <?php $solo_lectura = $id > 0 ? 'readonly' : ''; ?>
                        <input type="text" id="coste" size="8" maxlength="10"
                               pattern="\-?\d*\.?\d+"
                               name="ultimoCoste" data-obj="cajaCoste" onkeydown="controlEventos(event)"
                               value="<?= number_format($Producto['ultimoCoste'], 2, '.', '') ?>"
                               <?= $solo_lectura ?>>
                        <span class="Euro_grande">€</span>
                    </div>
                    <div class="form-group col-md-4">
                        <label>IVA:</label>
                        <select id="idIva" name="idIva" onchange="recalcularPrecioSegunCosteBeneficio();">
                            <?= $htmlIvas ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Coste Promedio:</label>
                        <input type="text" id="costepromedio" size="8" name="costepromedio" readonly
                               value="<?= number_format($Producto['costepromedio'], 2, '.', '') ?>">
                        <span class="Euro_grande">€</span>
                    </div>
                </div>

                <div class="row">
                    <h4>Precios de venta</h4>
                    <div class="col-md-4">
                        <label>Beneficio:</label>
                        <input type="text" id="beneficio" size="5" name="beneficio"
                               data-obj="cajaBeneficio" onkeydown="controlEventos(event)"
                               value="<?= number_format($Producto['beneficio'], 2, '.', '') ?>"> %
                    </div>
                    <div class="col-md-4">
                        <label>Precio sin IVA:</label>
                        <input type="text" id="pvpSiva" size="10" name="pvpSiva"
                               data-obj="cajaPvpSiva" onkeydown="controlEventos(event)" onblur="controlEventos(event)"
                               value="<?= number_format($Producto['pvpSiva'], 2, '.', '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label>
                            Precio con IVA:
                            <a onclick="recalcularPrecioSegunCosteBeneficio()">
                                <span title="Recalcular" class="glyphicon glyphicon-refresh"></span>
                            </a>
                        </label>
                        <input type="text" id="pvpCiva" size="10" name="pvpCiva"
                               data-obj="cajaPvpCiva" onkeydown="controlEventos(event)" onblur="controlEventos(event)"
                               value="<?= number_format($Producto['pvpCiva'], 2, '.', '') ?>">
                    </div>
                </div>

                <div class="row">
                    <h4>Stock</h4>
                    <div class="col-md-4">
                        <label>Mínimo:</label>
                        <input type="text" id="stockmin" size="5" name="stockmin" readonly
                               data-obj="cajaStockMin"
                               value="<?= number_format($Producto['stocks']['stockMin'], 2, '.', '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label>Máximo:</label>
                        <input type="text" id="stockmax" size="5" name="stockmax" readonly
                               data-obj="cajaStockMax"
                               value="<?= number_format($Producto['stocks']['stockMax'], 2, '.', '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label>En almacén:</label>
                        <input type="text" id="stockon" size="5" name="stockon" readonly
                               data-obj="cajaStockOn"
                               value="<?= number_format($Producto['stocks']['stockOn'], 2, '.', '') ?>">
                    </div>
                </div>

            </div><!-- /col-md-6 izquierda -->

            <!-- ===== COLUMNA DERECHA: paneles colapsables ===== -->
            <div class="col-md-6 text-center">
                <div class="panel-group">
                    <?php foreach ($htmltabla as $i => $h): ?>
                        <?= htmlPanelDesplegable($i, $h['titulo'], $h['html']) ?>
                    <?php endforeach; ?>
                </div>
                <a class="glyphicon glyphicon-list"
                   href="<?= $HostNombre ?>/modulos/mod_productos/DetalleMayor.php?idArticulo=<?= $Producto['idArticulo'] ?>">
                   Listado mayor todo el año
                </a>
            </div><!-- /col-md-6 derecha -->

        </div><!-- /col-md-12 -->
        </form>

        <?php if ($ClasePermisos->getAccion('verWebEnProducto') == 1 && isset($datosWebCompletos['htmlproducto']['html'])): ?>
            <?= $datosWebCompletos['htmlproducto']['html'] ?>
            <div class="col-md-6 text-center">
                <div class="panel-group">
                    <?php if (isset($datosWebCompletos['htmlnotificaciones'])): ?>
                        <?= htmlPanelDesplegable(6, 'Notificaciones: <span class="num_notificaciones">' . $datosWebCompletos['num_notificaciones'] . '</span>', $datosWebCompletos['htmlnotificaciones']) ?>
                    <?php endif; ?>
                    <?php if (isset($datosWebCompletos['htmlsLinksVirtuemart']['html_backEnd'])): ?>
                        <?= $datosWebCompletos['htmlsLinksVirtuemart']['html_backEnd'] ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div><!-- /container -->

    <?php echo '<script src="' . $HostNombre . '/plugins/modal/func_modal.js"></script>'; ?>
    <?php include $RutaServidor . '/' . $HostNombre . '/plugins/modal/ventanaModal.php'; ?>

    <script>
        <?php if ($ClasePermisos->getAccion('modificarStock') == 1): ?>
            $('#stockmin').removeAttr('readonly');
            $('#stockmax').removeAttr('readonly');
        <?php endif; ?>
        <?php if ($ClasePermisos->getAccion('verCodBarras') == 0): ?>
            $('#tcodigo a').hide();
            $('#tcodigo input').attr('readonly', 'readonly');
        <?php endif; ?>
        <?php if ($ClasePermisos->getAccion('verProveedores') == 0): ?>
            $('#tproveedor a').hide();
            $('#tproveedor input').attr('readonly', 'readonly');
        <?php endif; ?>
        <?php if ($ClasePermisos->getAccion('verFamilias') == 0): ?>
            $('#tfamilias a').hide();
        <?php endif; ?>
        <?php if ($ClasePermisos->getAccion('verHistoricoPrecios') == 0): ?>
            $('#thitorico a').hide();
        <?php endif; ?>
    </script>

</body>
</html>
