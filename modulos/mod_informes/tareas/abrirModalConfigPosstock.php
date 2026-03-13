<?php
// @ Objetivo: generar el HTML del modal de configuración de POSStock
// Lee los parámetros del nodo <posstock> dentro de <configuracion> en parametros.xml

$ClaseParametros = new ClaseParametros('parametros.xml');
$posstock = $ClaseParametros->getNode('configuracion/posstock');

function abrirModalConfigPosstock($posstock)
{
    $html  = '<div class="container-fluid">';
    $html .= '<form id="formConfigPosstock" name="formConfigPosstock" class="form-horizontal">';

    // --- BLOQUE 1: VENTANA DE ANÁLISIS ---
    $html .= '<div class="panel panel-default">';
    $html .= '  <div class="panel-heading small text-uppercase fw-bold"><i class="glyphicon glyphicon-calendar"></i> Ventana de análisis</div>';
    $html .= '  <div class="panel-body">';
    $html .= '    <div class="row">';
    $html .= '      <div class="col-xs-12">';
    $html .= '        <label class="control-label small">Días hacia atrás para consolidar stock (7 o 14)</label>';
    $html .= '        <select class="form-control input-sm" name="inputVentanaDias">';
    $html .= '          <option value="7"'  . ((string)$posstock->ventana_dias === '7'  ? ' selected' : '') . '>7 días</option>';
    $html .= '          <option value="14"' . ((string)$posstock->ventana_dias === '14' ? ' selected' : '') . '>14 días</option>';
    $html .= '        </select>';
    $html .= '      </div>';
    $html .= '    </div>';
    $html .= '  </div>';
    $html .= '</div>';

    // --- BLOQUE 2: UMBRALES ---
    $html .= '<div class="panel panel-default">';
    $html .= '  <div class="panel-heading small text-uppercase fw-bold"><i class="glyphicon glyphicon-adjust"></i> Umbrales de detección</div>';
    $html .= '  <div class="panel-body">';
    $html .= '    <div class="row">';

    // Umbral sobrestock
    $html .= '      <div class="col-xs-12 col-sm-4">';
    $html .= '        <label class="control-label small">Umbral sobrestock <small class="text-muted">(Caso 2, ratio 0–1)</small></label>';
    $html .= '        <input type="number" step="0.05" min="0" max="1" class="form-control input-sm text-right" name="inputUmbralSobrestock" value="' . (string)$posstock->umbral_sobrestock . '" required>';
    $html .= '      </div>';

    // Umbral caducidad
    $html .= '      <div class="col-xs-12 col-sm-4">';
    $html .= '        <label class="control-label small">Semanas sin venta <small class="text-muted">(Caso 3a)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="1" min="1" class="form-control text-right" name="inputUmbralCaducidadSemanas" value="' . (string)$posstock->umbral_caducidad_semanas . '" required>';
    $html .= '          <span class="input-group-addon">sem.</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    // Umbral sin rotación
    $html .= '      <div class="col-xs-12 col-sm-4">';
    $html .= '        <label class="control-label small">Semanas sin rotación <small class="text-muted">(Caso 3b)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="1" min="1" class="form-control text-right" name="inputUmbralSinRotacionSemanas" value="' . (string)$posstock->umbral_sin_rotacion_semanas . '" required>';
    $html .= '          <span class="input-group-addon">sem.</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '    </div>';
    $html .= '  </div>';
    $html .= '</div>';

    $html .= '</form>';
    $html .= '</div>';

    $html .= '<div class="modal-footer" style="border-top:0;">';
    $html .= '  <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>';
    $html .= '  <button type="button" class="btn btn-primary" onclick="guardarConfigPosstock()">';
    $html .= '    <i class="glyphicon glyphicon-floppy-disk"></i> Guardar configuración';
    $html .= '  </button>';
    $html .= '</div>';

    return $html;
}

$respuesta['titulo'] = 'Configuración POSStock';
$respuesta['html']   = abrirModalConfigPosstock($posstock);
