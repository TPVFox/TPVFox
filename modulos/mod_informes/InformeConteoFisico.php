<?php
// @ Objetivo: página de generación de hoja de conteo físico de inventario.
// Permite seleccionar artículos por familia y generar el PDF de conteo en 3 ubicaciones.

include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_informes/funciones.php';

if ($ClasePermisos->getAccion('ejecutar') == 0) {
    header('Location: ' . $HostNombre . '/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include_once $URLCom . '/head.php'; ?>
</head>
<body>
    <?php include_once $URLCom . '/modulos/mod_menu/menu.php'; ?>

    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <h3 class="page-header">
                    <span class="glyphicon glyphicon-list-alt"></span>
                    Hoja de conteo f&iacute;sico
                    <small>Inventario por ubicaciones</small>
                </h3>
            </div>
        </div>

        <div class="row">
            <!-- ── Panel de opciones ─────────────────────────────────────── -->
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>Opciones del informe</strong></div>
                    <div class="panel-body">

                        <div class="form-group">
                            <label>T&iacute;tulo del informe <small class="text-muted">(opcional)</small></label>
                            <input type="text" id="cfTitulo" class="form-control" maxlength="100"
                                   placeholder="Hoja de conteo de inventario">
                        </div>

                        <div class="form-group">
                            <label>Filtrar por familia</label>
                            <div id="cfFamiliasTags" style="min-height:32px; padding:4px; border:1px solid #ddd; border-radius:4px; background:#fff;">
                                <span class="text-muted" id="cfFamiliaPlaceholder">Sin filtro &mdash; todos los art&iacute;culos</span>
                            </div>
                            <input type="hidden" id="cfFamiliasSel" value="">
                            <button type="button" class="btn btn-default btn-xs" style="margin-top:6px;"
                                    onclick="cfAbrirFamilias()">
                                <span class="glyphicon glyphicon-filter"></span> Elegir familia
                            </button>
                            <button type="button" class="btn btn-link btn-xs" onclick="cfLimpiarFamilias()">Limpiar</button>
                        </div>

                        <hr>

                        <div class="form-group">
                            <label>O introducir IDs de art&iacute;culos</label>
                            <textarea id="cfIdsArticulos" class="form-control" rows="3"
                                      placeholder="Ej: 101, 205, 318 (separados por comas)"></textarea>
                            <span class="help-block" style="font-size:11px;">
                                Si se especifican IDs, el filtro de familia se ignora.
                            </span>
                        </div>

                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="cfSoloActivos" checked>
                                Solo art&iacute;culos activos
                            </label>
                        </div>

                        <div class="form-group">
                            <label>Modo del informe</label>
                            <div>
                                <label class="radio-inline">
                                    <input type="radio" name="cfModo" id="cfModoNormal" value="normal" checked>
                                    Normal
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="cfModo" id="cfModoCiega" value="ciega">
                                    Ciega
                                </label>
                            </div>
                            <span class="help-block" id="cfModoDesc" style="font-size:11px;">
                                Stock y conteo juntos en una sola hoja.
                            </span>
                        </div>

                        <hr>

                        <button type="button" class="btn btn-primary btn-block"
                                id="cfBtnGenerar" onclick="cfGenerarPDF()">
                            <span class="glyphicon glyphicon-print"></span>
                            Generar hoja de conteo (PDF)
                        </button>

                        <div id="cfMensaje" style="margin-top:10px;"></div>
                    </div>
                </div>
            </div>

            <!-- ── Panel hoja rápida de conteo ──────────────────────────── -->
            <div class="col-md-4" style="margin-top:0;">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <strong><span class="glyphicon glyphicon-th-list"></span> Hoja r&aacute;pida de conteo</strong>
                        <small class="pull-right" style="padding-top:2px;">Una l&iacute;nea por art&iacute;culo</small>
                    </div>
                    <div class="panel-body">
                        <p style="font-size:12px; margin-top:0;">
                            Usa los mismos filtros del panel izquierdo.<br>
                            Genera una tabla compacta: ID &middot; nombre &middot; stock &middot; casilla de conteo.
                            Ideal para contar junto a un pedido.
                        </p>

                        <div class="checkbox" style="margin-top:0;">
                            <label style="font-size:12px;">
                                <input type="checkbox" id="hrMostrarStock" checked>
                                Mostrar stock sistema
                                <small class="text-muted">(desmarcar = conteo ciego)</small>
                            </label>
                        </div>

                        <button type="button" class="btn btn-info btn-block"
                                id="hrBtnGenerar" onclick="hrGenerarPDF()">
                            <span class="glyphicon glyphicon-print"></span>
                            Generar hoja r&aacute;pida (PDF)
                        </button>

                        <div id="hrMensaje" style="margin-top:8px;"></div>

                        <div id="hrResultadoPDF" style="display:none; margin-top:8px;">
                            <a id="hrEnlacePDF" href="#" target="_blank"
                               class="btn btn-success btn-block btn-sm">
                                <span class="glyphicon glyphicon-download-alt"></span>
                                Abrir hoja r&aacute;pida
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Panel de vista previa / resultado ─────────────────────── -->
            <div class="col-md-8">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>Instrucciones de uso</strong></div>
                    <div class="panel-body">
                        <p>
                            Esta hoja permite registrar el stock f&iacute;sico de los art&iacute;culos seleccionados
                            en hasta <strong>3 ubicaciones</strong>. El n&uacute;mero de la columna
                            <em>Ubicaci&oacute;n</em> es solo un orden; puede asignarle el nombre que necesite
                            (lineal, almac&eacute;n, nevera, camara, etc.).
                        </p>
                        <table class="table table-bordered table-condensed" style="font-size:12px;">
                            <thead>
                                <tr class="active">
                                    <th>Columna</th>
                                    <th>Descripci&oacute;n</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td><strong>ID</strong></td><td>C&oacute;digo interno del art&iacute;culo en el sistema</td></tr>
                                <tr><td><strong>Stock sistema</strong></td><td>Stock registrado en TPVFox en el momento de generar el informe</td></tr>
                                <tr><td><strong>Ubicaci&oacute;n</strong></td><td>N&uacute;mero de zona (1, 2 o 3)</td></tr>
                                <tr><td><strong>Unidades sueltas</strong></td><td>Unidades sin empaquetar encontradas en esa zona</td></tr>
                                <tr><td><strong>Packs</strong></td><td>N&uacute;mero de packs completos</td></tr>
                                <tr><td><strong>Ud / pack</strong></td><td>Unidades por pack (caja, blíster, etc.)</td></tr>
                                <tr><td><strong>Subtotal</strong></td><td>(Packs &times; Ud/pack) + Unidades sueltas</td></tr>
                                <tr><td><strong>TOTAL REAL</strong></td><td>Suma de los subtotales de las 3 ubicaciones</td></tr>
                            </tbody>
                        </table>
                        <div class="alert alert-info" style="font-size:12px;">
                            <strong>Uso para validaci&oacute;n:</strong>
                            Genere un informe con los art&iacute;culos detectados como incidencia en POSStock
                            y compare el <em>TOTAL REAL</em> con el <em>Stock sistema</em> despu&eacute;s del conteo.
                        </div>
                    </div>
                </div>

                <!-- Descripción de modos -->
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>Modos del informe</strong></div>
                    <div class="panel-body" style="font-size:12px;">
                        <dl style="margin-bottom:0;">
                            <dt>Normal</dt>
                            <dd style="margin-bottom:8px;">
                                Una sola hoja: nombre, ID, stock del sistema y tabla de conteo juntos.
                                Útil para inventarios rápidos donde no importa conocer el stock previo.
                            </dd>
                            <dt>Ciega</dt>
                            <dd>
                                El PDF tiene <strong>dos secciones separadas</strong> por un salto de página:<br>
                                <span class="label label-primary">Hoja de referencia</span>
                                lista de artículos <em>con</em> stock del sistema — solo uso interno.<br>
                                <span class="label label-success" style="margin-top:4px; display:inline-block;">
                                    Hoja de conteo
                                </span>
                                misma lista numerada <em>sin</em> stock — se entrega al operario
                                para que no sepa el stock esperado antes de contar.
                            </dd>
                        </dl>
                    </div>
                </div>

                <!-- Resultado del PDF generado -->
                <div id="cfResultadoPDF" style="display:none;">
                    <div class="panel panel-success">
                        <div class="panel-heading"><strong>PDF generado</strong></div>
                        <div class="panel-body">
                            <a id="cfEnlacePDF" href="#" target="_blank"
                               class="btn btn-success btn-lg btn-block">
                                <span class="glyphicon glyphicon-download-alt"></span>
                                Abrir / descargar hoja de conteo
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal selector de familias -->
    <div class="modal fade" id="cfModalFamilias" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Seleccionar familia</h4>
                </div>
                <div class="modal-body" id="cfModalFamiliasBody">
                    <div class="text-center"><span class="glyphicon glyphicon-refresh"></span> Cargando...</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    var cfFamiliasSeleccionadas = [];   // [{id, nombre}]

    // Actualizar descripción al cambiar modo
    $('input[name="cfModo"]').on('change', function() {
        var desc = this.value === 'ciega'
            ? 'Primera sección: lista con stock (referencia interna). Segunda sección: hoja de conteo sin stock para el operario.'
            : 'Stock y conteo juntos en una sola hoja.';
        $('#cfModoDesc').text(desc);
    });

    function cfAbrirFamilias() {
        $('#cfModalFamiliasBody').html('<div class="text-center"><span class="glyphicon glyphicon-refresh"></span> Cargando...</div>');
        $('#cfModalFamilias').modal('show');
        $.post('<?php echo $HostNombre; ?>/modulos/mod_informes/tareas.php', {
            pulsado: 'getCatalogoFamilias'
        }, function(data) {
            if (data.html) {
                $('#cfModalFamiliasBody').html(data.html);
            }
        }, 'json').fail(function() {
            $('#cfModalFamiliasBody').html('<div class="alert alert-danger">Error al cargar familias</div>');
        });
    }

    // Llamada desde las filas del catálogo de familias (reutiliza agregarFamiliaInforme)
    function agregarFamiliaInforme(idFamilia, nombre) {
        // Solo una familia a la vez (simplifica la query)
        cfFamiliasSeleccionadas = [{id: idFamilia, nombre: nombre}];
        cfRefrescarTagsFamilias();
        $('#cfModalFamilias').modal('hide');
    }

    function cfRefrescarTagsFamilias() {
        var $tags = $('#cfFamiliasTags');
        var $ph   = $('#cfFamiliaPlaceholder');
        $tags.find('.cf-tag').remove();
        if (cfFamiliasSeleccionadas.length === 0) {
            $ph.show();
            $('#cfFamiliasSel').val('');
        } else {
            $ph.hide();
            cfFamiliasSeleccionadas.forEach(function(f) {
                $tags.append(
                    '<span class="cf-tag label label-info" style="margin-right:4px; font-size:13px;">'
                    + f.nombre
                    + ' <a href="#" onclick="cfLimpiarFamilias(); return false;" style="color:#fff;">&times;</a>'
                    + '</span>'
                );
            });
            $('#cfFamiliasSel').val(cfFamiliasSeleccionadas.map(function(f){ return f.id; }).join(','));
        }
    }

    function cfLimpiarFamilias() {
        cfFamiliasSeleccionadas = [];
        cfRefrescarTagsFamilias();
    }

    function cfGenerarPDF() {
        var idsArticulos = $('#cfIdsArticulos').val().trim();
        var familiasSel  = $('#cfFamiliasSel').val().trim();
        var soloActivos  = $('#cfSoloActivos').is(':checked') ? '1' : '0';
        var titulo       = $('#cfTitulo').val().trim();

        if (!idsArticulos && !familiasSel) {
            cfMensaje('warning', 'Selecciona una familia o introduce IDs de artículos.');
            return;
        }

        var $btn = $('#cfBtnGenerar');
        $btn.prop('disabled', true).html('<span class="glyphicon glyphicon-refresh"></span> Generando...');
        $('#cfResultadoPDF').hide();
        cfMensaje('', '');

        var modo = $('input[name="cfModo"]:checked').val() || 'normal';

        var data = {
            pulsado:       'imprimirInventarioConteoPDF',
            solo_activos:  soloActivos,
            titulo:        titulo,
            modo:          modo
        };

        if (idsArticulos) {
            data.ids_articulos = idsArticulos;
        } else {
            // cfFamiliasSeleccionadas siempre tiene solo una entrada
            data.id_familia = cfFamiliasSeleccionadas[0].id;
        }

        $.post('<?php echo $HostNombre; ?>/modulos/mod_informes/tareas.php', data, function(resp) {
            if (resp.error) {
                cfMensaje('danger', resp.error);
            } else if (resp.url) {
                $('#cfEnlacePDF').attr('href', resp.url);
                $('#cfResultadoPDF').show();
                cfMensaje('success', resp.total + ' artículo(s) incluidos en la hoja.');
            }
        }, 'json').fail(function() {
            cfMensaje('danger', 'Error de comunicación con el servidor.');
        }).always(function() {
            $btn.prop('disabled', false).html('<span class="glyphicon glyphicon-print"></span> Generar hoja de conteo (PDF)');
        });
    }

    function hrGenerarPDF() {
        var idsArticulos = $('#cfIdsArticulos').val().trim();
        var familiasSel  = $('#cfFamiliasSel').val().trim();
        var soloActivos  = $('#cfSoloActivos').is(':checked') ? '1' : '0';
        var titulo       = $('#cfTitulo').val().trim();
        var mostrarStock = $('#hrMostrarStock').is(':checked') ? '1' : '0';

        if (!idsArticulos && !familiasSel) {
            hrMensaje('warning', 'Selecciona una familia o introduce IDs de artículos en el panel izquierdo.');
            return;
        }

        var $btn = $('#hrBtnGenerar');
        $btn.prop('disabled', true).html('<span class="glyphicon glyphicon-refresh"></span> Generando...');
        $('#hrResultadoPDF').hide();
        hrMensaje('', '');

        var data = {
            pulsado:       'imprimirHojaRapidaConteoPDF',
            solo_activos:  soloActivos,
            titulo:        titulo,
            mostrar_stock: mostrarStock
        };

        if (idsArticulos) {
            data.ids_articulos = idsArticulos;
        } else {
            data.id_familia = cfFamiliasSeleccionadas[0].id;
        }

        $.post('<?php echo $HostNombre; ?>/modulos/mod_informes/tareas.php', data, function(resp) {
            if (resp.error) {
                hrMensaje('danger', resp.error);
            } else if (resp.url) {
                $('#hrEnlacePDF').attr('href', resp.url);
                $('#hrResultadoPDF').show();
                hrMensaje('success', resp.total + ' artículo(s) en la hoja rápida.');
            }
        }, 'json').fail(function() {
            hrMensaje('danger', 'Error de comunicación con el servidor.');
        }).always(function() {
            $btn.prop('disabled', false).html('<span class="glyphicon glyphicon-print"></span> Generar hoja rápida (PDF)');
        });
    }

    function hrMensaje(tipo, texto) {
        var $m = $('#hrMensaje');
        if (!texto) { $m.html(''); return; }
        $m.html('<div class="alert alert-' + tipo + ' alert-dismissible" style="font-size:12px;">'
            + '<button type="button" class="close" data-dismiss="alert">&times;</button>'
            + texto + '</div>');
    }

    function cfMensaje(tipo, texto) {
        var $m = $('#cfMensaje');
        if (!texto) { $m.html(''); return; }
        $m.html('<div class="alert alert-' + tipo + ' alert-dismissible" style="font-size:12px;">'
            + '<button type="button" class="close" data-dismiss="alert">&times;</button>'
            + texto + '</div>');
    }

    // Sobreescribir colapsarTodoCatalogo si el catálogo la necesita
    function colapsarTodoCatalogo() {
        $('#tablaFamiliasJerarquica .FilaFamilia').not('.nivel-1').hide();
    }

    function toggleHijosDirectosCat(el) {
        var $tr     = $(el).closest('tr');
        var ruta    = $tr.data('ruta');
        var nivel   = parseInt($tr.attr('class').match(/nivel-(\d+)/)[1]);
        var $hijos  = $tr.nextAll('tr').filter(function() {
            var r = $(this).data('ruta');
            return r && r.indexOf(ruta + '-') === 0 &&
                   parseInt($(this).attr('class').match(/nivel-(\d+)/)[1]) === nivel + 1;
        });
        if ($hijos.first().is(':visible')) {
            $hijos.hide();
        } else {
            $hijos.show();
        }
    }
    </script>

    <?php
    echo '<script src="' . $HostNombre . '/plugins/modal/func_modal.js"></script>';
    include $URLCom . '/plugins/modal/ventanaModal.php';
    ?>
</body>
</html>
