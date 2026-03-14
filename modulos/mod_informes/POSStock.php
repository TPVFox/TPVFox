<?php
include_once './../../inicial.php';
include_once $URLCom . '/controllers/parametros.php';
include_once $URLCom . '/modulos/mod_informes/funciones.php';

// ── Sesión y permisos ─────────────────────────────────────────────────
// $thisTpv, $ClasePermisos y $Usuario están disponibles desde inicial.php.
// Redirigir si el usuario no tiene permiso de ejecutar en esta vista.
if ($ClasePermisos->getAccion('ejecutar') == 0) {
    header('Location: ' . $HostNombre . '/index.php');
    exit;
}

// ── Parámetros POSStock ───────────────────────────────────────────────
$ClaseParametros = new ClaseParametros('parametros.xml');
$posstock_cfg    = $ClaseParametros->getNode('configuracion/posstock');
$ventana_dias    = (int)(string)$posstock_cfg->ventana_dias;
?>
<!DOCTYPE html>
<html>
<head>
    <?php include_once $URLCom . '/head.php'; ?>
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
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-3">
            <div class="form-group">
                <label class="control-label small">Periodo</label>
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
                       onchange="posstockActualizarNumero()">
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
                            onclick="window.print()">
                        <i class="glyphicon glyphicon-print"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Filtro de familias ────────────────────────────────────────── -->
    <div class="row" id="posstockFilaFamilias">
        <div class="col-xs-12">
            <button type="button" class="btn btn-default btn-xs"
                    onclick="posstockAbrirFiltroFamilias()">
                <i class="glyphicon glyphicon-filter"></i>
                Filtrar familias:
                <span id="posstockFiltroLabel" class="label label-default">Todas</span>
            </button>
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
    <div id="posstockNavegacion" class="row" style="display:none;">
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
            <p class="text-muted small">Calculando incidencias…</p>
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
    // Ventana de consolidación leída desde PHP para usarla en JS
    var POSSTOCK_VENTANA_DIAS = <?php echo $ventana_dias; ?>;

    // Periodo activo (se rellena al generar o pulsar botón de barra)
    var posstockPeriodoActivo = null;
</script>

<script src="<?php echo $HostNombre; ?>/modulos/mod_informes/funciones.js" type="module"></script>
<?php
echo '<script src="' . $HostNombre . '/plugins/modal/func_modal.js"></script>';
?>

</body>
</html>
