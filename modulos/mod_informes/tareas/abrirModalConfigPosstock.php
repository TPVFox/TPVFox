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
    $html .= '        <label class="control-label small">Días hacia atrás para consolidar stock (Actual, 7 o 14)</label>';
    $html .= '        <select class="form-control input-sm" name="inputVentanaDias">';
    $html .= '          <option value="0"'  . ((string)$posstock->ventana_dias === '0'  ? ' selected' : '') . '>Actual</option>';
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

    $html .= '      <div class="col-xs-12 col-sm-4">';
    $html .= '        <label class="control-label small" title="Porcentaje que compara el stock previo con la entrada. 100% = misma cantidad. Rango 0–200%.">Umbral sobrestock<br><small class="text-muted">(Caso 2, porcentaje 0–200)</small></label>';
    $html .= '        <div class="input-group">';
    $html .= '          <input type="number" step="5" min="0" max="200" class="form-control input-sm text-right" name="inputUmbralSobrestock" value="' . (string)$posstock->umbral_sobrestock . '" required>';
    $html .= '          <span class="input-group-addon">%</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-4">';
    $html .= '        <label class="control-label small" title="Semanas desde la última venta para riesgo de caducidad teórica (Caso 3a).">Caducidad teórica<br><small class="text-muted">(Caso 3a)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="1" min="1" class="form-control text-right" name="inputUmbralCaducidadSemanas" value="' . (string)$posstock->umbral_semanas_desde_ultima_venta . '" required>';
    $html .= '          <span class="input-group-addon">sem.</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-4">';
    $html .= '        <label class="control-label small" title="Semanas desde la última salida para detectar entrada sin rotación (Caso 3b).">Semanas sin rotación<br><small class="text-muted">(Caso 3b)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="1" min="1" class="form-control text-right" name="inputUmbralSinRotacionSemanas" value="' . (string)$posstock->umbral_semanas_sin_rotacion . '" required>';
    $html .= '          <span class="input-group-addon">sem.</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '    </div>';
    $html .= '  </div>';
    $html .= '</div>';

    // --- BLOQUE 3: MODELO ESTADÍSTICO (C5 + C6) ---
    $modelos_validos  = ['automatico', 'binomial', 'poisson_bn', 'gamma'];
    $sig_validas      = ['0.90', '0.95', '0.99'];
    $sigma_validos    = ['2.0', '2.5', '3.0', '3.5', '4.0'];

    $modelo_actual = (string)$posstock->modelo_estadistico;
    if (!in_array($modelo_actual, $modelos_validos, true)) $modelo_actual = 'automatico';

    $sig_actual = number_format((float)(string)$posstock->modelo_significancia, 2);
    if (!in_array($sig_actual, $sig_validas, true)) $sig_actual = '0.95';

    $sigma_actual = number_format((float)(string)$posstock->binomial_sigma_mult, 1);
    if (!in_array($sigma_actual, $sigma_validos, true)) $sigma_actual = '3.0';

    // Descripción de cada modelo
    $desc_modelos = [
        'automatico' => 'Elige automáticamente entre Binomial, Poisson, Binomial Negativa o Gamma según el patrón de ventas de cada artículo. Para artículos de tipo peso o con demanda muy regular aplica Gamma con un umbral más permisivo.',
        'binomial'   => 'Fuerza el modelo Binomial (media + n·σ) para todos los artículos. Útil cuando los datos siguen distribuciones acotadas o el histórico es corto.',
        'poisson_bn' => 'Fuerza Poisson para artículos con dispersión normal y Binomial Negativa para los sobredispersados (ventas en rachas). El test se hace artículo por artículo.',
        'gamma'      => 'Fuerza la distribución Gamma para todos los artículos. Recomendado para productos de flujo continuo (peso, líquidos) o con demanda muy asimétrica.',
    ];

    // Visibilidad inicial de bloques secundarios
    $show_sig   = ($modelo_actual !== 'binomial');
    $show_sigma = ($modelo_actual === 'binomial');

    $html .= '<div class="panel panel-default">';
    $html .= '  <div class="panel-heading small text-uppercase fw-bold"><i class="glyphicon glyphicon-stats"></i> Modelo estadístico (Caso 5 y Caso 6)</div>';
    $html .= '  <div class="panel-body">';

    // Selector de modelo
    $html .= '    <div class="row">';
    $html .= '      <div class="col-xs-12 col-sm-5">';
    $html .= '        <label class="control-label small">Modo de detección</label>';
    $html .= '        <select class="form-control input-sm" name="inputModeloEstadistico" id="modalModeloEstadistico" onchange="modalToggleModeloEstadistico()">';
    foreach ([
        'automatico' => 'Automático',
        'binomial'   => 'Binomial',
        'poisson_bn' => 'Poisson / Binomial Negativa',
        'gamma'      => 'Gamma',
    ] as $val => $label) {
        $sel = $modelo_actual === $val ? ' selected' : '';
        $html .= "          <option value=\"{$val}\"{$sel}>{$label}</option>";
    }
    $html .= '        </select>';
    $html .= '      </div>';

    // Selector de significancia (oculto para 'binomial')
    $html .= '      <div class="col-xs-12 col-sm-4" id="modalSignificanciaBloque"'
        . (!$show_sig ? ' style="display:none;"' : '') . '>';
    $html .= '        <label class="control-label small" title="Nivel de confianza para el umbral de rotura (C5) y para el ROP (C6). Mayor valor = más conservador.">Significancia / nivel de servicio</label>';
    $html .= '        <select class="form-control input-sm" name="inputModeloSignificancia">';
    $html .= '          <option value="0.90"' . ($sig_actual === '0.90' ? ' selected' : '') . '>90 % (menos estricto)</option>';
    $html .= '          <option value="0.95"' . ($sig_actual === '0.95' ? ' selected' : '') . '>95 % (estándar)</option>';
    $html .= '          <option value="0.99"' . ($sig_actual === '0.99' ? ' selected' : '') . '>99 % (más estricto)</option>';
    $html .= '        </select>';
    $html .= '      </div>';

    // Selector de sigma (solo para 'binomial')
    $html .= '      <div class="col-xs-12 col-sm-4" id="modalBinomialSigmaBloque"'
        . (!$show_sigma ? ' style="display:none;"' : '') . '>';
    $html .= '        <label class="control-label small" title="Número de desviaciones típicas para el umbral (C5: umbral = media + nσ; C6: stock de seguridad = nσ · σ_d · √L).">Rango σ (desv. típicas)</label>';
    $html .= '        <select class="form-control input-sm" name="inputBinomialSigmaMult">';
    foreach ($sigma_validos as $sv) {
        $sel = $sigma_actual === $sv ? ' selected' : '';
        $html .= "          <option value=\"{$sv}\"{$sel}>{$sv} σ</option>";
    }
    $html .= '        </select>';
    $html .= '      </div>';

    $html .= '    </div>';

    // Descripción dinámica del modelo seleccionado
    $html .= '    <div id="modalModeloDesc" class="alert alert-info small" style="margin:10px 0 0; padding:7px 10px;">';
    $html .= htmlspecialchars($desc_modelos[$modelo_actual]);
    $html .= '    </div>';

    // Datos de todas las descripciones para JS
    $html .= '    <script>';
    $html .= 'window._modeloDescPosstock = ' . json_encode($desc_modelos) . ';';
    $html .= '    </script>';

    $html .= '  </div>';
    $html .= '</div>';

    // --- BLOQUE 4: CASO 6 — LEAD TIME ---
    $lead_defecto_actual = (string)$posstock->c6_lead_time_defecto ?: '14';

    $html .= '<div class="panel panel-default">';
    $html .= '  <div class="panel-heading small text-uppercase fw-bold"><i class="glyphicon glyphicon-shopping-cart"></i> Punto de pedido (Caso 6)</div>';
    $html .= '  <div class="panel-body">';
    $html .= '    <div class="row">';

    $html .= '      <div class="col-xs-12 col-sm-5">';
    $html .= '        <label class="control-label small" title="Días de lead time usados cuando un artículo no tiene pedidos históricos suficientes para estimar el plazo real.">Lead time por defecto <small class="text-muted">(sin pedidos históricos)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="1" min="1" max="365" class="form-control text-right" name="inputC6LeadTimeDefecto" value="' . htmlspecialchars($lead_defecto_actual) . '" required>';
    $html .= '          <span class="input-group-addon">días</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '    </div>';
    $html .= '    <p class="text-muted small" style="margin:8px 0 0;">';
    $html .= '      Lead time calculado automáticamente desde el intervalo medio entre pedidos al proveedor. ';
    $html .= '      El nivel de servicio para el ROP queda definido por la significancia del modelo estadístico.';
    $html .= '    </p>';
    $html .= '  </div>';
    $html .= '</div>';

    // --- BLOQUE 5: OPCIONES ADICIONALES ---
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
    $html .= '    <div class="checkbox" style="margin:4px 0 0;">';
    $html .= '      <label class="small" title="Cuando está activo, los artículos con stock negativo al cierre también se incluyen en el análisis C5 de detección de venta cero.">';
    $html .= '        <input type="checkbox" name="inputC5IncluirStockNegativo" value="1"'
        . ((string)$posstock->c5_incluir_stock_negativo === '1' ? ' checked' : '') . '>';
    $html .= '        C5 — analizar artículos con stock negativo <small class="text-muted">(incluye detección completa, igual que stock positivo)</small>';
    $html .= '      </label>';
    $html .= '    </div>';
    $html .= '    <div class="checkbox" style="margin:4px 0 0;">';
    $html .= '      <label class="small" title="Por defecto solo se usan tickets de caja como ventas en C5 y C6. Activa esta opción si los albaranes de cliente representan ventas reales recurrentes (no regularizaciones de stock).">';
    $html .= '        <input type="checkbox" name="inputIncluirAlbcliVentas" value="1"'
        . ((string)$posstock->incluir_albcli_ventas === '1' ? ' checked' : '') . '>';
    $html .= '        Incluir albaranes de cliente como ventas (C5 y C6) <small class="text-muted">(desactivado por defecto)</small>';
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
