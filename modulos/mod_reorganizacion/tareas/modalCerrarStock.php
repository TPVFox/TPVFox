<?php
/*
modalCerrarStock.php -
Se trata de un modal para configurar el cierre de stock anual, este modal debe hacer de forma secuencial las siguientes tareas:
    1) Definir el proveedor que generara el albaran de cierre
    2) Definir las familias que se incluiran en el albaran de cierre
    3) Definir las familias que se omitiran en el albaran de cierre
    4) Boton para ejecutar el cierre de stock anual (cierra el modal y permite ejecutar el proceso mostrando la barra de progreso en el listado de reorganización)
Idea: El modal tiene 4 secciones display none. Cuando una se completa se presenta como display Block y se mantiene la enterior
*/

$familias = [];

foreach ($xml->familias_excluidas->familia as $familia) {
    $familias[] = [
        'id' => (string)$familia['id'],      // atributo id
        'nombre' => (string)$familia         // valor del nodo
    ];
}

$idProveedor = $xml->ajustes_globales->id_proveedor_defecto;

$html = '<div class="panel panel-default shadow-sm" style="max-width: 600px; margin: 20px auto;">';
$html .=   '<div class="panel-heading clearfix" style="background-color: #fff; border-bottom: 0;">';
$html .=        '<h4 class="panel-title pull-left" style="padding-top: 7px;"><i class="glyphicon glyphicon-transfer text-primary"></i> Cierre de Stock Anual</h4>';
$html .=        '<button type="button" class="btn btn-default btn-sm pull-right" onclick="abrirConfiguracionXML(\'cierre_stock_anual\')"><i class="glyphicon glyphicon-cog"></i> Configuración Base</button>';
$html .=    '</div>';
$html .=    '<div class="panel-body">';
$html .=        '<div class="alert alert-warning" style="font-size: 0.9em; margin-bottom: 20px;"><i class="glyphicon glyphicon-exclamation-sign"></i>Este proceso ajustará el stock a <strong>0</strong>. Se recomienda usar un proveedor dedicado.</div>';

$html .=        '<form id="formCierreStock">';
$html .=            '<div class="well well-sm shadow-sm" style="background-color: #fcfcfc; border: 1px solid #eee; cursor: pointer;" onclick="document.getElementById(\'modoBase\').click()">';
$html .=                '<div class="radio" style="margin: 10px;">';
$html .=                    '<label>';
$html .=                        '<input type="radio" name="modoCierre" id="modoBase" value="0" checked onchange="togglePanelManual()">';
$html .=                        '<strong style="font-size: 1.1em;">Ejecución Base (XML)</strong>';
$html .=                        '<p class="text-muted small" style="margin-top: 5px;">Cierre masivo respetando las exclusiones del sistema.</p>';
$html .=                        '<div style="margin-top: 10px;">';
$html .=                            '<span class="label label-primary">Proveedor: ' . $idProveedor . '</span>';
$html .=                            '<span class="label label-default" title="Familias excluidas: ' . implode(', ', array_map(function ($familia) {
    return (string)$familia['nombre'];
}, $familias)) . '">Familias excluidas: ' . count($familias) . '</span>';
$html .=                        '</div>';
$html .=                    '</label>';
$html .=                '</div>';
$html .=            '</div>';

$html .=            '<div class="well well-sm shadow-sm" style="background-color: #fcfcfc; border: 1px solid #eee; cursor: pointer;" onclick="document.getElementById(\'modoManual\').click()">';
$html .=                '<div class="radio" style="margin: 10px;">';
$html .=                    '<label>';
$html .=                        '<input type="radio" name="modoCierre" id="modoManual" value="1" onchange="togglePanelManual()">';
$html .=                        '<strong style="font-size: 1.1em;">Cierre Específico / Manual</strong>';
$html .=                        '<p class="text-muted small" style="margin-top: 5px;">Ajuste puntual para una sola familia.</p>';
$html .=                    '</label>';
$html .=                '</div>';

$html .=                '<div id="panelManual" style="display:none; margin-top: 15px; padding: 15px; border-top: 1px solid #ddd;">';
$html .=                    '<div class="row">';
$html .=                        '<div class="col-xs-12 form-group">';
$html .=                            '<label class="control-label small text-uppercase">Seleccionar Familia</label>';
$html .=                            '<select class="form-control input-sm" name="familiaSeleccionada">';
$html .=                                '<option value="">-- Seleccionar --</option>';
foreach ($familias as $familia) {
    $html .=                                '<option value="' . $familia['id'] . '">' . $familia['nombre'] . '</option>';
}
$html .=                            '</select>';
$html .=                        '</div>';
$html .=                        '<div class="col-xs-12 form-group" style="margin-bottom: 0;">';
$html .=                            '<label class="control-label small text-uppercase">ID Proveedor de Ajuste</label>';
$html .=                            '<div class="input-group input-group-sm">';
$html .=                                '<span class="input-group-addon">ID</span>';
$html .=                                '<input type="number" class="form-control" name="idProveedorAjuste" value="105">';
$html .=                            '</div>';
$html .=                        '</div>';
$html .=                    '</div>';
$html .=                '</div>';
$html .=            '</div>';
$html .=        '</form>';
$html .=    '</div>';

$html .=    '<div class="panel-footer clearfix" style="background-color: #fff; border-top: 0; padding-top: 0;">';
$html .=        '<hr style="margin-top: 0;">';
$html .=        '<button type="button" class="btn btn-link pull-left text-muted" onclick="cerrarModal()">Cancelar</button>';
$html .=        '<button type="button" class="btn btn-primary pull-right px-4" onclick="ejecutarProceso()">';
$html .=            '<i class="glyphicon glyphicon-play"></i> Iniciar Cierre';
$html .=        '</button>';
$html .=    '</div>';
$html .= '</div>';

$respuesta['html'] = $html;
