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
$idProveedor = '';
$nombreProveedor = '';
$estilos = [
    'pro_readonly' => '',
    'pro_styleNo' => ''
];
$dedonde = 'modalCerrarStock';
$titulo = "Cerrar Stock Anual ";
$html = '
<div class="panel panel-default shadow-sm" style="max-width: 600px; margin: 20px auto;">
    <div class="panel-heading clearfix" style="background-color: #fff; border-bottom: 0;">
        <h4 class="panel-title pull-left" style="padding-top: 7px;">
            <i class="glyphicon glyphicon-transfer text-primary"></i> Cierre de Stock Anual
        </h4>
        <button type="button" class="btn btn-default btn-sm pull-right" onclick="abrirConfiguracionXML()">
            <i class="glyphicon glyphicon-cog"></i> Configuración Base
        </button>
    </div>

    <div class="panel-body">
        <div class="alert alert-warning" style="font-size: 0.9em; margin-bottom: 20px;">
            <i class="glyphicon glyphicon-exclamation-sign"></i>
            Este proceso ajustará el stock a <strong>0</strong>. Se recomienda usar un proveedor dedicado.
        </div>

        <form id="formCierreStock">
            <div class="well well-sm shadow-sm" style="background-color: #fcfcfc; border: 1px solid #eee; cursor: pointer;" onclick="document.getElementById(\'modoBase\').click()">
                <div class="radio" style="margin: 10px;">
                    <label>
                        <input type="radio" name="modoCierre" id="modoBase" value="0" checked onchange="togglePanelManual()">
                        <strong style="font-size: 1.1em;">Ejecución Base (XML)</strong>
                        <p class="text-muted small" style="margin-top: 5px;">Cierre masivo respetando las exclusiones del sistema.</p>
                        <div style="margin-top: 10px;">
                            <span class="label label-primary">Proveedor: 105</span>
                            <span class="label label-default">Familias excluidas: 4</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="well well-sm shadow-sm" style="background-color: #fcfcfc; border: 1px solid #eee; cursor: pointer;" onclick="document.getElementById(\'modoManual\').click()">
                <div class="radio" style="margin: 10px;">
                    <label>
                        <input type="radio" name="modoCierre" id="modoManual" value="1" onchange="togglePanelManual()">
                        <strong style="font-size: 1.1em;">Cierre Específico / Manual</strong>
                        <p class="text-muted small" style="margin-top: 5px;">Ajuste puntual para una sola familia.</p>
                    </label>
                </div>

                <div id="panelManual" style="display:none; margin-top: 15px; padding: 15px; border-top: 1px solid #ddd;">
                    <div class="row">
                        <div class="col-xs-12 form-group">
                            <label class="control-label small text-uppercase">Seleccionar Familia</label>
                            <select class="form-control input-sm">
                                <option value="1">Cítricos</option>
                                <option value="2">Fruta de hueso</option>
                            </select>
                        </div>
                        <div class="col-xs-12 form-group" style="margin-bottom: 0;">
                            <label class="control-label small text-uppercase">ID Proveedor de Ajuste</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-addon">ID</span>
                                <input type="number" class="form-control" value="105">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="panel-footer clearfix" style="background-color: #fff; border-top: 0; padding-top: 0;">
        <hr style="margin-top: 0;">
        <button type="button" class="btn btn-link pull-left text-muted" onclick="cerrarModal()">Cancelar</button>
        <button type="button" class="btn btn-primary pull-right px-4" onclick="ejecutarProceso()">
            <i class="glyphicon glyphicon-play"></i> Iniciar Cierre
        </button>
    </div>
</div>';

$respuesta['html'] = $html;
