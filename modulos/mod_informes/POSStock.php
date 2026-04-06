<?php
include_once './../../inicial.php';
include_once $URLCom . '/controllers/parametros.php';
include_once $URLCom . '/modulos/mod_informes/funciones.php';
include_once $URLCom . '/modulos/mod_informes/tareas/vistas/vistaOpcionesPeriodo.php';

// ── Sesión y permisos ─────────────────────────────────────────────────
// $thisTpv, $ClasePermisos y $Usuario están disponibles desde inicial.php.
// Redirigir si el usuario no tiene permiso de ejecutar en esta vista.
if ($ClasePermisos->getAccion('ejecutar') == 0) {
    header('Location: ' . $HostNombre . '/index.php');
    exit;
}

// ── Parámetros POSStock ───────────────────────────────────────────────
$ClaseParametros      = new ClaseParametros('parametros.xml');
$posstock_cfg         = $ClaseParametros->getNode('configuracion/posstock');
$ventana_dias         = (int)(string)$posstock_cfg->ventana_dias;
$c3b_dias_post        = (int)(string)$posstock_cfg->c3b_dias_post_periodo ?: 14;
$c3a_multiplicador    = max(2.0, min(6.0, (float)(string)($posstock_cfg->c3a_multiplicador_cadencia ?: '3.0')));
$c6b_dias_historico   = max(30, min(365, (int)(string)($posstock_cfg->c6b_dias_historico ?: '90')));
$mostrar_tecnico      = (string)$posstock_cfg->posstock_mostrar_tecnico === '1';
$incluir_stock_inactivo = ((string)$posstock_cfg->incluir_stock_inactivo === '1');

// ── Checkboxes de casos (se renderizan en PHP) ────────────────────────
$defChecked    = ['caso1' => true];
$checksHtml    = '';
$tiposParaChks = array_filter(posstockTiposActivos($incluir_stock_inactivo), fn($ti) => !$ti['soloAnual']);
foreach ($tiposParaChks as $ti) {
    $checked     = !empty($defChecked[$ti['v']]) ? ' checked' : '';
    $checksHtml .= '<label class="checkbox-inline" style="margin-left:8px; font-weight:normal;">'
        . '<input type="checkbox" id="posstockChk_' . $ti['v'] . '" value="' . $ti['v'] . '"' . $checked
        . ' onchange="posstockRecargarPorCasos()"> '
        . '<span class="label label-default">' . htmlspecialchars($ti['short']) . '</span> '
        . htmlspecialchars($ti['t'])
        . '</label>';
}
?>
<!DOCTYPE html>
<html>

<head>
    <?php include_once $URLCom . '/head.php'; ?>
    <style>
        #posstockTabla .label[title] {
            cursor: help;
        }
    </style>
</head>

<body>
    <?php include_once $URLCom . '/modulos/mod_menu/menu.php'; ?>

    <div class="container-fluid">

        <!-- ── Cabecera ─────────────────────────────────────────────────── -->
        <div class="row">
            <div class="col-xs-12">
                <h3 class="page-header">
                    POSStock — Revisión de stock
                    <small>
                        <button type="button" class="btn btn-default btn-xs"
                            onclick="abrirModalConfigPosstock()"
                            title="Configuración POSStock">
                            <i class="glyphicon glyphicon-cog"></i> Configuración
                        </button>
                    </small>
                </h3>
            </div>
        </div>

        <!-- ── Advertencia ──────────────────────────────────────────────── -->
        <div class="row">
            <div class="col-xs-12">
                <div class="alert alert-warning alert-sm" role="alert">
                    <i class="glyphicon glyphicon-exclamation-sign"></i>
                    <strong>Solo lectura.</strong>
                    Esta vista no aplica correcciones automáticas.
                    Las incidencias deben corregirse mediante albaranes o ajustes reales.
                </div>
            </div>
        </div>

        <!-- ── Selectores de periodo ────────────────────────────────────── -->
        <div class="row" id="posstockSelectores">
            <div class="col-xs-12 col-sm-3">
                <div class="form-group">
                    <label class="control-label small">Tipo de periodo</label>
                    <select id="posstockTipo" class="form-control input-sm"
                        onchange="posstockActualizarNumero()">
                        <option value="">— seleccionar —</option>
                        <option value="semana">Semanal</option>
                        <option value="quincena">Quincenal</option>
                        <option value="mes">Mensual</option>
                        <option value="trimestre">Trimestral</option>
                        <option value="estacional">Estacional</option>
                        <option value="cuatrimestre">Cuatrimestral</option>
                        <option value="semestre">Semestral</option>
                        <option value="anual">Anual</option>
                    </select>
                </div>
            </div>
            <div class="col-xs-12 col-sm-3">
                <div class="form-group">
                    <label class="control-label small" id="posstockLabelNumero">Periodo</label>
                    <select id="posstockNumero" class="form-control input-sm" disabled>
                        <option value="">— elige tipo primero —</option>
                    </select>
                </div>
            </div>
            <div class="col-xs-12 col-sm-2">
                <div class="form-group">
                    <label class="control-label small">Año</label>
                    <input type="number" id="posstockAnio" class="form-control input-sm"
                        value="<?php echo date('Y'); ?>" min="2020" max="2099"
                        onchange="posstockActualizarNumero(true)">
                </div>
            </div>
            <div class="col-xs-12 col-sm-2">
                <div class="form-group">
                    <label class="control-label small">&nbsp;</label>
                    <button id="posstockBtnGenerar" type="button"
                        class="btn btn-primary btn-sm btn-block"
                        onclick="posstockGenerar()" disabled>
                        <i class="glyphicon glyphicon-search"></i> Generar informe
                    </button>
                </div>
            </div>
            <div class="col-xs-12 col-sm-2" id="posstockBotonesWrap" style="display:none;">
                <div class="form-group">
                    <label class="control-label small">&nbsp;</label>
                    <div class="btn-group btn-block">
                        <button id="posstockBtnExportar" type="button"
                            class="btn btn-default btn-sm" style="display:none;"
                            onclick="exportarPOSStockCSV()">
                            <i class="glyphicon glyphicon-download-alt"></i> CSV
                        </button>
                        <button id="posstockBtnImprimir" type="button"
                            class="btn btn-default btn-sm" style="display:none;"
                            onclick="imprimirPOSStockPDF()">
                            <i class="glyphicon glyphicon-print"></i> Imprimir PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Filtros ───────────────────────────────────────────────────── -->
        <div class="row" id="posstockFilaFiltros">
            <div class="col-xs-12" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <button type="button" class="btn btn-default btn-xs"
                    onclick="posstockAbrirFiltroFamilias()">
                    <i class="glyphicon glyphicon-filter"></i>
                    Filtrar familias:
                    <span id="posstockFiltroLabel" class="label label-default">Todas</span>
                </button>
                <button type="button" class="btn btn-default btn-xs"
                    onclick="posstockAbrirFiltroProveedores()">
                    <i class="glyphicon glyphicon-filter"></i>
                    Filtrar proveedor:
                    <span id="posstockFiltroProveedorLabel" class="label label-default">Todos</span>
                </button>
                <button type="button" class="btn btn-default btn-xs" id="posstockBtnAgruparProv"
                    onclick="posstockToggleAgruparProveedor()" title="Agrupa las filas por proveedor habitual manteniendo el orden interno de cada grupo">
                    <i class="glyphicon glyphicon-th-list"></i>
                    Agrupar por proveedor:
                    <span id="posstockAgruparProvLabel" class="label label-default">No</span>
                </button>
            </div>
        </div>

        <!-- ── Filtro de tipos de incidencia (solo vistas no anuales) ────── -->
        <div class="row" id="posstockFilaCasos" style="display:none; margin-top:12px; margin-bottom:16px;">
            <div class="col-xs-12">
                <span class="text-muted small">
                    <i class="glyphicon glyphicon-filter"></i> Tipos visibles:
                </span>
                <?php echo $checksHtml; ?>
            </div>
        </div>

        <!-- ── Aviso ventana_dias ────────────────────────────────────────── -->
        <div id="posstockAvisoVentana" class="row" style="display:none;">
            <div class="col-xs-12">
                <div class="alert alert-info" role="alert">
                    <i class="glyphicon glyphicon-time"></i>
                    El periodo seleccionado está dentro de la ventana de consolidación
                    (<strong><?php echo $ventana_dias; ?> días</strong>).
                    Selecciona un periodo anterior para evitar descuadres por albaranes pendientes.
                </div>
            </div>
        </div>

        <!-- ── Labels de periodo ─────────────────────────────────────────── -->
        <div id="posstockNavegacion" class="row" style="display:none; margin-top:14px;">
            <div class="col-xs-12">
                <p class="small text-muted">
                    <strong>Periodo movimientos:</strong>
                    <span id="posstockLabelMovimientos">—</span>
                    &nbsp;|&nbsp;
                    <strong>Stock base:</strong>
                    <span id="posstockLabelStock">—</span>
                </p>
            </div>
        </div>

        <!-- ── Barra de botones de navegación entre periodos ────────────── -->
        <div id="posstockBarraBotones" class="row" style="display:none;">
            <div class="col-xs-12">
                <div id="posstockBotonesPeriodo" class="btn-group-sm" style="flex-wrap:wrap; display:flex; gap:3px;"></div>
            </div>
        </div>

        <!-- ── Spinner ───────────────────────────────────────────────────── -->
        <div id="posstockSpinner" class="row text-center" style="display:none; padding:30px 0;">
            <div class="col-xs-12">
                <img src="<?php echo $HostNombre; ?>/css/img/loading.gif" alt="Cargando…">
                <p class="text-muted small" id="posstockProgreso">Calculando incidencias…</p>
            </div>
        </div>

        <!-- ── Tabla de resultados ───────────────────────────────────────── -->
        <div class="row">
            <div class="col-xs-12" id="posstockTablaWrap" style="display:none;"></div>
        </div>

    </div><!-- /container-fluid -->

    <!-- ── Modal (reutilizable del proyecto) ─────────────────────────────── -->
    <?php include $URLCom . '/plugins/modal/ventanaModal.php'; ?>

    <script>
        // Configuración POSStock leída desde PHP
        var POSSTOCK_VENTANA_DIAS = <?php echo (int)$ventana_dias; ?>;
        var POSSTOCK_C3B_DIAS_POST = <?php echo (int)$c3b_dias_post; ?>;
        var POSSTOCK_C3A_MULTIPLICADOR = <?php echo $c3a_multiplicador; ?>;
        var POSSTOCK_C6B_DIAS_HISTORICO = <?php echo (int)$c6b_dias_historico; ?>;
        var POSSTOCK_MOSTRAR_TECNICO = <?php echo $mostrar_tecnico ? 'true' : 'false'; ?>;
        var POSSTOCK_INCLUIR_STOCK_INACTIVO = <?php echo $incluir_stock_inactivo ? 'true' : 'false'; ?>;

        // Estado del selector de periodo activo
        var posstockPeriodoActivo = null;
    </script>

    <script src="<?php echo $HostNombre; ?>/modulos/mod_informes/funciones.js?v=0431-89" type="module"></script>
    <?php
    echo '<script src="' . $HostNombre . '/plugins/modal/func_modal.js"></script>';
    ?>

</body>

</html>
