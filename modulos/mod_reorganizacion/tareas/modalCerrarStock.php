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
$proveedor = (string)$xml->ajustes_globales->proveedor;
$idProveedor = $xml->ajustes_globales->proveedor['id'];

$html = '<div class="panel panel-default shadow-sm" style="max-width: 600px; margin: 20px auto;">';
$html .=   '<div class="panel-heading clearfix" style="background-color: #fff; border-bottom: 0;">';
$html .=        '<h4 class="panel-title pull-left" style="padding-top: 7px;"><i class="glyphicon glyphicon-transfer text-primary"></i> Cierre de Stock Anual</h4>';
$html .=        '<button type="button" class="btn btn-default btn-sm pull-right" onclick="abrirConfiguracionXML(\'cierre_stock_anual\', \'' . $titulo . '\')"><i class="glyphicon glyphicon-cog"></i> Configuración Base</button>';
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
$html .=                            '<input type="hidden" id="proveedorPredefinido" value="' . $idProveedor . '">';
$html .=                            '<span class="label label-primary" title="Proveedor: ' . $proveedor . '">Proveedor: ' . $idProveedor . '</span>';
$html .=                            '<span class="label label-default" title="Familias excluidas: ' . implode(', ', array_map(function ($familia) {
    return (string)$familia['nombre'];
}, $familias)) . '">Familias excluidas: ' . count($familias) . '</span>';
$html .=                        '</div>';
$html .=                    '</label>';
// Si al ejecutar se detecta que el id del proveedor no existe o no coincide con el nombre y lo mismo para las familias, mostrar un mensaje de alerta indicando que se han detectado inconsistencias en la configuración XML y que se recomienda revisar la configuración antes de ejecutar el proceso.
$html .=                    '<div id="alertaModoBase" class="alert alert-danger" style="margin-top: 15px; display:none;">';
$html .=                        '<p><i class="glyphicon glyphicon-warning-sign"></i> Se han detectado inconsistencias en la configuración XML. Por favor, revise el proveedor y las familias excluidas antes de ejecutar el proceso.</p>';
$html .=                    '</div>';
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
// Agregar Nueva Familia
$html .= '          <label class="small">Familia de cierre:</label>';
$html .=                '<div class="row" style="margin-top:10px; display: flex; align-items: center;">';

// 1. Bloque de Identificación y Búsqueda (9 columnas)
$html .=                    '<div class="col-xs-12">';
$html .=                    '    <div class="row">';
// ID
$html .=                    '        <div class="col-xs-3" style="padding-right:5px;">';
$html .=                    '            <input type="text" id="id_familia" name="id_familia" data-obj="cajaIdFamilia" class="form-control input-sm" placeholder="ID" onkeydown="controlEventos(event)">';
$html .=                    '        </div>';

// Nombre + Botones de búsqueda (agrupados para que queden pegados)
$html .=                    '        <div class="col-xs-9">';
$html .=                    '            <div class="input-group">';
$html .=                    '                <input type="text" id="Familia" name="Familia" data-obj="cajaFamilia" class="form-control input-sm" placeholder="Nombre Familia..." onkeydown="controlEventos(event)">';
$html .=                    '                <span class="input-group-btn">';
$html .=                    '                    <button type="button" class="btn btn-sm btn-default" title="Buscar" onclick="buscarFamilia(\'' . $dedonde . '\',\'Familia.value\')">';
$html .=                    '                        <i class="glyphicon glyphicon-search"></i>';
$html .=                    '                    </button>';
$html .=                    '                    <button type="button" class="btn btn-sm btn-default" title="Catálogo" onclick="catalogoFamilias()">';
$html .=                    '                        <i class="glyphicon glyphicon-book"></i>';
$html .=                    '                    </button>';
$html .=                    '                </span>';
$html .=                    '            </div>';
$html .=                    '        </div>';
$html .=                    '    </div>';
$html .=                    '</div>';
$html .=                '</div>';
$html .= '        <div class="form-group" style="margin:0 0 15px 0;">';
$html .= '          <label class="small">Proveedor por Defecto (Albarán):</label>';
$html .= '          <div class="row">'; // Usamos una fila interna para dividir ID y Nombre
$html .= '            <div class="col-xs-8" style="padding-right:5px;">';
$html .= '              <div class="input-group input-group-sm">';
$html .= '                <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>';
$html .= '                <input type="text" class="form-control" name="Proveedor" id="Proveedor" data-obj= "cajaIdProveedor" value="' . $xml->ajustes_globales->proveedor . '" placeholder="Nombre..." onkeydown="controlEventos(event)">';
$html .= '              </div>';
$html .= '            </div>';
$html .= '            <div class="col-xs-4" style="padding-left:0;">';
$html .= '              <div class="input-group input-group-sm">';
$html .= '                <input type="text" class="form-control" name="id_proveedor" id="id_proveedor" data-obj= "cajaProveedor" value="' . $xml->ajustes_globales->proveedor['id'] . '" placeholder="ID" onkeydown="controlEventos(event)">';
$html .= '                <span class="input-group-btn">';
$html .= '                  <button type="button" class="btn btn-default" onclick="buscarProveedor(\'' . $dedonde . '\',\'Proveedor.value\')">';
$html .= '                    <i class="glyphicon glyphicon-search"></i>';
$html .= '                  </button>';
$html .= '                </span>';
$html .= '              </div>';
$html .= '            </div>';
$html .= '          </div>'; // Cierre Row interno
$html .= '        </div>';
$html .=                    '<div id="alertaModoManual" class="alert alert-danger" style="margin-top: 15px; display:none;">';
$html .=                        '<p><i class="glyphicon glyphicon-warning-sign"></i> Se han detectado inconsistencias en la configuración XML. Por favor, revise el proveedor y las familias excluidas antes de ejecutar el proceso.</p>';
$html .=                    '</div>';
$html .=                    '</div>';
$html .=                '</div>';
$html .=            '</div>';
$html .=        '</form>';
$html .=    '</div>';

$html .=    '<div class="panel-footer clearfix" style="background-color: #fff; border-top: 0; padding-top: 0;">';
$html .=        '<hr style="margin-top: 0;">';
$html .=        '<button type="button" class="btn btn-link pull-left text-muted" onclick="cerrarPopUpConTitulo(\'' . $titulo . '\')">Cancelar</button>';
$html .=        '<button type="button" id="btnValidarCierre" class="btn btn-primary pull-right px-4" onclick="validarProceso(\'' . $titulo . '\')">';
$html .=            '<i class="glyphicon glyphicon-check"></i> Validar Cierre';
$html .=        '</button>';
$html .=        '<button id="btnIniciarCierre" class="btn btn-success pull-right px-4" style="display:none;">';
$html .=            '<i class="glyphicon glyphicon-play"></i> Iniciar Cierre';
$html .=        '</button>';
$html .=    '</div>';
$html .= '</div>';

$respuesta['html'] = $html;
