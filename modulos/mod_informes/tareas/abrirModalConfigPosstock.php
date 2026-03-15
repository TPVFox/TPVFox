<?php
// @ Objetivo: generar el HTML del modal de configuración de POSStock
// Lee los parámetros del nodo <posstock> dentro de <configuracion> en parametros.xml

$ClaseParametros = new ClaseParametros('parametros.xml');
$posstock = $ClaseParametros->getNode('configuracion/posstock');

function abrirModalConfigPosstock($posstock)
{
    $html  = '<div class="container-fluid">';
    $html .= '<form id="formConfigPosstock" name="formConfigPosstock" class="form-horizontal">';

    // Usamos title en las etiquetas individuales (no un alert global)

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
    $html .= '        <label class="control-label small" title="Porcentaje que compara el stock previo con la entrada. 100% = misma cantidad. Rango 0–200%.">Umbral sobrestock<br> <small class="text-muted">(Caso 2, porcentaje 0–200)</small></label>';
    $html .= '        <div class="input-group">';
    $html .= '          <input type="number" step="5" min="0" max="200" class="form-control input-sm text-right" name="inputUmbralSobrestock" value="' . (string)$posstock->umbral_sobrestock . '" required title="Porcentaje que compara el stock previo con la entrada. 100% = misma cantidad. Rango 0–200%">';
    $html .= '          <span class="input-group-addon">%</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    // Umbral caducidad
    $html .= '      <div class="col-xs-12 col-sm-4">';
    $html .= '        <label class="control-label small" title="Número de semanas desde la última venta usado para detectar riesgo de caducidad teórica (Caso 3a).">Caducidad teórica<br> <small class="text-muted">(Caso 3a)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="1" min="1" class="form-control text-right" name="inputUmbralCaducidadSemanas" value="' . (string)$posstock->umbral_semanas_desde_ultima_venta . '" required title="Semanas desde la última venta para riesgo de caducidad teórica">';
    $html .= '          <span class="input-group-addon">sem.</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    // Umbral sin rotación
    $html .= '      <div class="col-xs-12 col-sm-4">';
    $html .= '        <label class="control-label small" title="Número de semanas desde la última salida/venta usado para detectar entradas sin rotación previa (Caso 3b).">Semanas sin rotación<br> <small class="text-muted">(Caso 3b)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="1" min="1" class="form-control text-right" name="inputUmbralSinRotacionSemanas" value="' . (string)$posstock->umbral_semanas_sin_rotacion . '" required title="Semanas desde la última salida para detectar entrada sin rotación">';
    $html .= '          <span class="input-group-addon">sem.</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '    </div>';
    $html .= '  </div>';
    $html .= '</div>';

    // --- BLOQUE 3: MODELO ROTURA C5 ---
    $modelo_actual    = (string)$posstock->modelo_rotura_c5 ?: 'binomial';
    $confianza_actual = (string)$posstock->umbral_confianza_poisson ?: '0.05';

    $html .= '<div class="panel panel-default">';
    $html .= '  <div class="panel-heading small text-uppercase fw-bold"><i class="glyphicon glyphicon-stats"></i> Modelo de rotura (Caso 5)</div>';
    $html .= '  <div class="panel-body">';
    $html .= '    <div class="row">';
    $html .= '      <div class="col-xs-12 col-sm-5">';
    $html .= '        <label class="control-label small" title="Algoritmo para detectar roturas físicas (C5).">Modelo estadístico</label>';
    $html .= '        <select class="form-control input-sm" name="inputModeloRoturaC5" id="modalModeloRoturaC5" onchange="modalTogglePoissonConfianza()">';
    $html .= '          <option value="binomial"' . ($modelo_actual === 'binomial' ? ' selected' : '') . '>Binomial (media + 3σ)</option>';
    $html .= '          <option value="poisson"'  . ($modelo_actual === 'poisson'  ? ' selected' : '') . '>Poisson</option>';
    $html .= '        </select>';
    $html .= '      </div>';
    $html .= '      <div class="col-xs-12 col-sm-4" id="modalPoissonConfianzaBloque"'
           . ($modelo_actual !== 'poisson' ? ' style="display:none;"' : '') . '>';
    $html .= '        <label class="control-label small" title="Probabilidad límite: si P(0 ventas) es menor a este valor con stock positivo, se declara rotura.">Nivel de confianza</label>';
    $html .= '        <select class="form-control input-sm" name="inputUmbralConfianzaPoisson">';
    $html .= '          <option value="0.10"' . ($confianza_actual === '0.10' ? ' selected' : '') . '>90 % confianza</option>';
    $html .= '          <option value="0.05"' . ($confianza_actual === '0.05' ? ' selected' : '') . '>95 % confianza</option>';
    $html .= '          <option value="0.01"' . ($confianza_actual === '0.01' ? ' selected' : '') . '>99 % confianza</option>';
    $html .= '        </select>';
    $html .= '      </div>';
    $html .= '    </div>';
    $html .= '  </div>';
    $html .= '</div>';

    // --- BLOQUE 4: OPCIONES ADICIONALES ---
    $html .= '<div class="panel panel-default">';
    $html .= '  <div class="panel-heading small text-uppercase fw-bold"><i class="glyphicon glyphicon-tasks"></i> Casos adicionales</div>';
    $html .= '  <div class="panel-body">';
    $html .= '    <div class="checkbox" style="margin:0;">';
    $html .= '      <label class="small" title="Incluye artículos físicos con stock positivo al cierre del periodo pero sin ningún movimiento en el rango analizado.">';
    $html .= '        <input type="checkbox" name="inputIncluirStockInactivo" value="1"'
        . ((string)$posstock->incluir_stock_inactivo === '1' ? ' checked' : '') . '>';
    $html .= '        Stock inactivo en periodo <small class="text-muted">(stock &gt; 0 y sin movimientos en el rango)</small>';
    $html .= '      </label>';
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
