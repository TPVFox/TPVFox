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
    $html .= '        <label class="control-label small" title="Porcentaje que compara el stock previo con la entrada. 100% = la entrada ya estaba cubierta. Rango 0–100%.">Umbral sobrestock<br><small class="text-muted">(Caso 2, porcentaje 0–100)</small></label>';
    $html .= '        <div class="input-group">';
    $html .= '          <input type="number" step="5" min="0" max="100" class="form-control input-sm text-right" name="inputUmbralSobrestock" value="' . (string)$posstock->umbral_sobrestock . '" required>';
    $html .= '          <span class="input-group-addon">%</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-4">';
    $html .= '        <label class="control-label small" title="Días de ventas que cubre el stock previo a la recepción. Si la cobertura es menor que este umbral la recepción no se considera problemática (producto de alta rotación).">Cobertura mínima C2<br><small class="text-muted">(Caso 2, días)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="1" min="7" max="365" class="form-control text-right" name="inputC2UmbralCobertura" value="' . (string)($posstock->c2_umbral_cobertura_dias ?: '21') . '" required>';
    $html .= '          <span class="input-group-addon">días</span>';
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
    foreach (
        [
            'automatico' => 'Automático',
            'binomial'   => 'Binomial',
            'poisson_bn' => 'Poisson / Binomial Negativa',
            'gamma'      => 'Gamma',
        ] as $val => $label
    ) {
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

    // --- BLOQUE 3a: C3a — MULTIPLICADOR DE CADENCIA ---
    $c3a_mult_validos = ['2.0', '2.5', '3.0', '3.5', '4.0', '4.5', '5.0', '5.5', '6.0'];
    $c3a_mult_actual  = number_format((float)(string)$posstock->c3a_multiplicador_cadencia ?: '3.0', 1);
    if (!in_array($c3a_mult_actual, $c3a_mult_validos, true)) $c3a_mult_actual = '3.0';

    $html .= '<div class="panel panel-default">';
    $html .= '  <div class="panel-heading small text-uppercase fw-bold"><i class="glyphicon glyphicon-repeat"></i> Caída de rotación (Caso 3a)</div>';
    $html .= '  <div class="panel-body">';
    $html .= '    <div class="row">';
    $html .= '      <div class="col-xs-12 col-sm-5">';
    $html .= '        <label class="control-label small" title="Multiplicador sobre la cadencia media histórica del artículo para calcular el umbral de alerta. Umbral = avg_cadencia × multiplicador. Ej: artículo que vende cada 3 días → alerta con 3×3=9 días sin venta. Rango recomendado: 2.0–6.0.">Multiplicador de cadencia<br><small class="text-muted">(Caso 3a — umbral dinámico)</small></label>';
    $html .= '        <select class="form-control input-sm" name="inputC3aMultiplicadorCadencia">';
    $html .= '          <option value="2.0"' . ($c3a_mult_actual === '2.0' ? ' selected' : '') . '>2.0× — más sensible</option>';
    $html .= '          <option value="2.5"' . ($c3a_mult_actual === '2.5' ? ' selected' : '') . '>2.5×</option>';
    $html .= '          <option value="3.0"' . ($c3a_mult_actual === '3.0' ? ' selected' : '') . '>3.0× (estándar)</option>';
    $html .= '          <option value="3.5"' . ($c3a_mult_actual === '3.5' ? ' selected' : '') . '>3.5×</option>';
    $html .= '          <option value="4.0"' . ($c3a_mult_actual === '4.0' ? ' selected' : '') . '>4.0×</option>';
    $html .= '          <option value="4.5"' . ($c3a_mult_actual === '4.5' ? ' selected' : '') . '>4.5×</option>';
    $html .= '          <option value="5.0"' . ($c3a_mult_actual === '5.0' ? ' selected' : '') . '>5.0×</option>';
    $html .= '          <option value="5.5"' . ($c3a_mult_actual === '5.5' ? ' selected' : '') . '>5.5×</option>';
    $html .= '          <option value="6.0"' . ($c3a_mult_actual === '6.0' ? ' selected' : '') . '>6.0× — menos sensible</option>';
    $html .= '        </select>';
    $html .= '      </div>';
    $html .= '    </div>';
    $html .= '    <p class="text-muted small" style="margin:8px 0 0;">';
    $html .= '      Alerta cuando el artículo lleva más de <em>cadencia media × multiplicador</em> días sin vender desde la última venta. Artículos con cadencia corta (&le;7 días) se muestran como riesgo de caducidad.';
    $html .= '    </p>';
    $html .= '  </div>';
    $html .= '</div>';

    // --- BLOQUE 3b: C3b — DÍAS POST-PERIODO ---
    $c3b_dias_post_actual = (string)$posstock->c3b_dias_post_periodo ?: '14';

    $html .= '<div class="panel panel-default">';
    $html .= '  <div class="panel-heading small text-uppercase fw-bold"><i class="glyphicon glyphicon-time"></i> Entrada sin rotación previa (Caso 3b)</div>';
    $html .= '  <div class="panel-body">';
    $html .= '    <div class="row">';
    $html .= '      <div class="col-xs-12 col-sm-5">';
    $html .= '        <label class="control-label small" title="Días posteriores al rango analizado que se comprueban para descartar falsos positivos en C3b: si el artículo vendió en esa ventana, la entrada al final del periodo no se considera incidencia.">Días post-periodo para validar C3b &laquo;nunca vendido&raquo;</label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="1" min="7" max="30" class="form-control text-right" name="inputC3bDiasPost" value="' . htmlspecialchars($c3b_dias_post_actual) . '" required>';
    $html .= '          <span class="input-group-addon">días</span>';
    $html .= '        </div>';
    $html .= '      </div>';
    $html .= '    </div>';
    $html .= '    <p class="text-muted small" style="margin:8px 0 0;">';
    $html .= '      Un artículo que entra el último día del rango y vende en los siguientes X días no se cuenta como "sin ventas registradas". Rango recomendado: 7–14 días.';
    $html .= '    </p>';
    $html .= '  </div>';
    $html .= '</div>';

    // --- BLOQUE 4: CASO 6 — LEAD TIME ---
    $lead_defecto_actual    = (string)$posstock->c6_lead_time_defecto    ?: '14';
    $c6b_dias_hist_actual   = (string)$posstock->c6b_dias_historico      ?: '90';
    $umbral_rec_rop_actual  = (string)$posstock->umbral_reconstituir_rop ?: '10';
    $umbral_stock_neg_actual = (string)$posstock->umbral_stock_negativo  ?: '2';

    $html .= '<div class="panel panel-default">';
    $html .= '  <div class="panel-heading small text-uppercase fw-bold"><i class="glyphicon glyphicon-shopping-cart"></i> Punto de pedido (C6a / C6b)</div>';
    $html .= '  <div class="panel-body">';
    $html .= '    <div class="row">';

    $html .= '      <div class="col-xs-12 col-sm-5">';
    $html .= '        <label class="control-label small" title="Días de lead time usados cuando un artículo no tiene pedidos históricos suficientes para estimar el plazo real.">Lead time por defecto <small class="text-muted">(sin pedidos históricos)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="1" min="1" max="365" class="form-control text-right" name="inputC6LeadTimeDefecto" value="' . htmlspecialchars($lead_defecto_actual) . '" required>';
    $html .= '          <span class="input-group-addon">días</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-5">';
    $html .= '        <label class="control-label small" title="C6b mide la demanda de los últimos N días desde hoy (independiente del periodo analizado). Evita falsas alarmas en productos de temporada fuera de su época. Recomendado: 60–180 días.">C6b — ventana de demanda activa <small class="text-muted">(días hacia atrás desde hoy)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="1" min="30" max="365" class="form-control text-right" name="inputC6bDiasHistorico" value="' . htmlspecialchars($c6b_dias_hist_actual) . '" required>';
    $html .= '          <span class="input-group-addon">días</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '    </div>';
    $html .= '    <div class="row" style="margin-top:10px;">';
    $html .= '      <div class="col-xs-12 col-sm-5">';
    $html .= '        <label class="control-label small" title="Si el stock actual supera N veces el ROP calculado se asume que el dato es incorrecto y se reconstitituye desde la última entrada de compra menos las ventas. Mínimo recomendado: 3. Valor por defecto: 10.">Umbral stock sobredimensionado<br><small class="text-muted">(reconstrucción — múltiplo del ROP)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="0.5" min="3" max="50" class="form-control text-right" name="inputUmbralReconstituirRop" value="' . htmlspecialchars($umbral_rec_rop_actual) . '" required>';
    $html .= '          <span class="input-group-addon">× ROP</span>';
    $html .= '        </div>';
    $html .= '      </div>';
    $html .= '      <div class="col-xs-12 col-sm-5">';
    $html .= '        <label class="control-label small" title="Si el stock es menor que este valor negativo (ej: 2 = reconstruir si stock &lt; -2) se recalcula desde la última entrada menos ventas. Valor por defecto: 2.">Umbral stock negativo<br><small class="text-muted">(reconstrucción — unidades negativas)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <span class="input-group-addon">-</span>';
    $html .= '          <input type="number" step="0.5" min="0" max="100" class="form-control text-right" name="inputUmbralStockNegativo" value="' . htmlspecialchars($umbral_stock_neg_actual) . '" required>';
    $html .= '          <span class="input-group-addon">ud.</span>';
    $html .= '        </div>';
    $html .= '      </div>';
    $html .= '    </div>';
    $html .= '    <p class="text-muted small" style="margin:8px 0 0;">';
    $html .= '      <strong>C6a</strong>: ROP con el periodo analizado (±1 periodo para semana/quincena/mes) — visión estacional. ';
    $html .= '      <strong>C6b</strong>: ROP sobre los últimos N días desde hoy — responde directamente a "¿debo pedir ahora?".';
    $html .= '    </p>';
    $html .= '  </div>';
    $html .= '</div>';

    // --- BLOQUE 4b: CASO 1 — STOCK NEGATIVO ---
    $c1_umbral_frac_actual    = (string)($posstock->c1_umbral_fraccionado  ?: '0.05');
    $c1_umbral_mag_actual     = (string)($posstock->c1_umbral_magnitud     ?: '0.5');
    $c1_umbral_venta_actual   = (string)($posstock->c1_umbral_por_venta    ?: '0.010');
    $c1_timing_actual         = (string)($posstock->c1_timing_ventana_dias ?: '1');

    $html .= '<div class="panel panel-default">';
    $html .= '  <div class="panel-heading small text-uppercase fw-bold"><i class="glyphicon glyphicon-warning-sign"></i> Stock negativo — detección de drift de pesaje (C1)</div>';
    $html .= '  <div class="panel-body">';
    $html .= '    <div class="row">';

    $html .= '      <div class="col-xs-12 col-sm-3">';
    $html .= '        <label class="control-label small" title="Parte fraccionaria mínima del stock para considerarlo con decimales sospechosos. 0.05 = 50g en una balanza de kg. Rango: 0.01–0.49.">Umbral decimal<br><small class="text-muted">(parte fraccionaria, kg/ud)</small></label>';
    $html .= '        <input type="number" step="0.01" min="0.01" max="0.49" class="form-control input-sm text-right" name="inputC1UmbralFraccionado" value="' . htmlspecialchars($c1_umbral_frac_actual) . '" required>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-3">';
    $html .= '        <label class="control-label small" title="Máximo negativo (cierre y mínimo) para que sea candidato a drift de balanza. Si el negativo supera este valor, no puede ser solo un error de pesaje. Rango: 0.1–5.0.">Máximo negativo aceptable<br><small class="text-muted">(magnitud límite, kg/ud)</small></label>';
    $html .= '        <input type="number" step="0.1" min="0.1" max="5.0" class="form-control input-sm text-right" name="inputC1UmbralMagnitud" value="' . htmlspecialchars($c1_umbral_mag_actual) . '" required>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-3">';
    $html .= '        <label class="control-label small" title="Error de pesaje máximo por operación. Se multiplica por el número de ventas para calcular el margen tolerable total. Ej: 0.010 kg/venta × 100 ventas = 1.0 kg tolerable. Rango: 0.001–0.1.">Error por venta<br><small class="text-muted">(kg/ud por operación)</small></label>';
    $html .= '        <input type="number" step="0.001" min="0.001" max="0.1" class="form-control input-sm text-right" name="inputC1UmbralPorVenta" value="' . htmlspecialchars($c1_umbral_venta_actual) . '" required>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-3">';
    $html .= '        <label class="control-label small" title="Días máximos tras el mínimo para que una recepción justifique el negativo puntual (C1b). Con 1 día detecta albaranes introducidos el día siguiente. Ampliar si los albaranes suelen registrarse con más retraso. Rango: 1–7.">Ventana timing C1b<br><small class="text-muted">(días tras el mínimo)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="1" min="1" max="7" class="form-control text-right" name="inputC1TimingVentanaDias" value="' . htmlspecialchars($c1_timing_actual) . '" required>';
    $html .= '          <span class="input-group-addon">días</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '    </div>';
    $html .= '    <p class="text-muted small" style="margin:8px 0 0;">';
    $html .= '      El sistema confirma drift de pesaje en dos pasos: primero el negativo debe ser menor que el <em>máximo negativo</em>; luego se verifica que no supere <em>error por venta × número de ventas</em>. Negocios con charcutería o productos a granel pueden necesitar valores más altos.';
    $html .= '    </p>';
    $html .= '  </div>';
    $html .= '</div>';

    // --- BLOQUE 4c: CASO 7b — RECEPCIÓN NO REGISTRADA ---
    $c7b_min_rec_actual     = (string)($posstock->c7b_min_recepciones         ?: '3');
    $c7b_cv_actual          = (string)($posstock->c7b_umbral_cv               ?: '0.5');
    $c7b_cv_peso_actual     = (string)($posstock->c7b_umbral_cv_peso          ?: '0.75');
    $c7b_iqr_peso_actual    = (string)($posstock->c7b_umbral_iqr_peso         ?: '2.0');
    $c7b_ruido_actual       = (string)($posstock->c7b_umbral_ruido_peso       ?: '0.5');
    $c7b_sev_unidad_actual  = (string)($posstock->c7b_umbral_severidad_unidad ?: '5');
    $c7b_sev_peso_actual    = (string)($posstock->c7b_umbral_severidad_peso   ?: '2.5');
    $c7b_cascada_exh_actual = ((string)($posstock->c7b_cascada_exhaustiva     ?: 'false') === 'true');

    $html .= '<div class="panel panel-default">';
    $html .= '  <div class="panel-heading small text-uppercase fw-bold"><i class="glyphicon glyphicon-inbox"></i> Recepción no registrada — detección de patrón (C7b)</div>';
    $html .= '  <div class="panel-body">';

    // ── Fila 1: Detección — condiciones de aplicabilidad del test estadístico ──
    $html .= '    <p class="small text-muted" style="margin:0 0 6px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Condiciones de detección</p>';
    $html .= '    <div class="row">';

    $html .= '      <div class="col-xs-12 col-sm-3">';
    $html .= '        <label class="control-label small" title="Número mínimo de recepciones históricas (período base) para aplicar la cascada estadística. Con menos recepciones el test no se ejecuta. Rango: 3–10.">Mín. recepciones base<br><small class="text-muted">(para cascada estadística)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="1" min="3" max="10" class="form-control text-right" name="inputC7bMinRecepciones" value="' . htmlspecialchars($c7b_min_rec_actual) . '" required>';
    $html .= '          <span class="input-group-addon">rec.</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-3">';
    $html .= '        <label class="control-label small" title="CV máximo del déficit normalizado para considerar el patrón lo suficientemente estable. Aplica a artículos por unidad. Subir tolera más irregularidad. Rango: 0.3–0.9.">Variabilidad máx. — unidad<br><small class="text-muted">(CV del déficit normalizado)</small></label>';
    $html .= '        <input type="number" step="0.05" min="0.3" max="0.9" class="form-control input-sm text-right" name="inputC7bUmbralCV" value="' . htmlspecialchars($c7b_cv_actual) . '" required>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-3">';
    $html .= '        <label class="control-label small" title="CV máximo para artículos de peso (fruta, carnicería). Mayor que el de unidad porque la cantidad por entrega varía intrínsecamente. Rango: 0.5–1.2.">Variabilidad máx. — peso<br><small class="text-muted">(fruta, carnicería, etc.)</small></label>';
    $html .= '        <input type="number" step="0.05" min="0.5" max="1.2" class="form-control input-sm text-right" name="inputC7bUmbralCVPeso" value="' . htmlspecialchars($c7b_cv_peso_actual) . '" required>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-3">';
    $html .= '        <label class="control-label small" title="Dispersión máxima entre entregas para artículos de peso, expresada como múltiplo del déficit medio (IQR &lt; N×|media|). Un valor mayor tolera entregas con cantidades muy distintas. Rango: 1.5–3.0.">Dispersión máx. — peso<br><small class="text-muted">(tolerancia entre entregas)</small></label>';
    $html .= '        <input type="number" step="0.1" min="1.5" max="3.0" class="form-control input-sm text-right" name="inputC7bUmbralIQRPeso" value="' . htmlspecialchars($c7b_iqr_peso_actual) . '" required>';
    $html .= '      </div>';

    $html .= '    </div>';

    // ── Fila 2: Severidad — magnitud del déficit y filtro de ruido de pesaje ──
    $html .= '    <p class="small text-muted" style="margin:10px 0 6px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Severidad</p>';
    $html .= '    <div class="row">';

    $html .= '      <div class="col-xs-12 col-sm-4">';
    $html .= '        <label class="control-label small" title="Déficit medio (kg) por debajo del cual el sistema lo clasifica como posible error de calibración de balanza en vez de albarán no registrado. Solo afecta a artículos de peso. Rango: 0.1–2.0.">Umbral ruido de pesaje<br><small class="text-muted">(kg — solo artículos de peso)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="0.1" min="0.1" max="2.0" class="form-control text-right" name="inputC7bUmbralRuidoPeso" value="' . htmlspecialchars($c7b_ruido_actual) . '" required>';
    $html .= '          <span class="input-group-addon">kg</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-4">';
    $html .= '        <label class="control-label small" title="Déficit medio mínimo (unidades/día) para que la magnitud no rebaje la severidad. Por debajo de este valor la severidad base se reduce un nivel. Rango: 2–20.">Magnitud mínima — unidad<br><small class="text-muted">(déficit ud./día para no rebajar)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="1" min="2" max="20" class="form-control text-right" name="inputC7bUmbralSevUnidad" value="' . htmlspecialchars($c7b_sev_unidad_actual) . '" required>';
    $html .= '          <span class="input-group-addon">ud./d</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-4">';
    $html .= '        <label class="control-label small" title="Déficit medio mínimo (kg/día) para que la magnitud no rebaje la severidad. Por debajo de este valor la severidad base se reduce un nivel. Rango: 0.5–10.0.">Magnitud mínima — peso<br><small class="text-muted">(déficit kg/día para no rebajar)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="0.5" min="0.5" max="10.0" class="form-control text-right" name="inputC7bUmbralSevPeso" value="' . htmlspecialchars($c7b_sev_peso_actual) . '" required>';
    $html .= '          <span class="input-group-addon">kg/d</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '    </div>';

    // ── Fila 3: Diagnóstico ──
    $html .= '    <div class="panel panel-warning" style="margin:12px 0 0;border-radius:3px;">';
    $html .= '      <div class="panel-body" style="padding:8px 12px;">';
    $html .= '        <div class="checkbox" style="margin:0;">';
    $html .= '          <label class="small" title="Modo diagnóstico: la cascada estadística continúa aunque un test válido no alcance significancia (p≥0.05). El nivel que confirme determinará la confianza y severidad. El motivo de cada fallback queda registrado en test_fallback_reason. Desactivar en producción.">';
    $html .= '            <input type="checkbox" name="inputC7bCascadaExhaustiva" value="1"' . ($c7b_cascada_exh_actual ? ' checked' : '') . '>';
    $html .= '            <strong>Cascada exhaustiva</strong> <small class="text-muted">— continúa la cascada aunque un test no sea significativo (diagnóstico y auditoría, no recomendado en producción)</small>';
    $html .= '          </label>';
    $html .= '        </div>';
    $html .= '      </div>';
    $html .= '    </div>';

    $html .= '  </div>';
    $html .= '</div>';

    // --- BLOQUE 4d: CASO 7a — MERMA ACUMULADA ---
    $c7a_delta_unidad_actual    = (string)($posstock->c7a_umbral_delta_unidad      ?: '2.0');
    $c7a_delta_peso_actual      = (string)($posstock->c7a_umbral_delta_peso        ?: '1.0');
    $c7a_pvalue_actual          = (string)($posstock->c7a_umbral_pvalue            ?: '0.10');
    $c7a_pvalue_alta_actual     = (string)($posstock->c7a_umbral_pvalue_alta       ?: '0.05');
    $c7a_alta_delta_ud_actual   = (string)($posstock->c7a_umbral_alta_delta_unidad ?: '10.0');
    $c7a_alta_delta_kg_actual   = (string)($posstock->c7a_umbral_alta_delta_peso   ?: '5.0');
    $c7a_alta_slope_ud_actual   = (string)($posstock->c7a_umbral_alta_slope_unidad ?: '2.0');
    $c7a_alta_slope_kg_actual   = (string)($posstock->c7a_umbral_alta_slope_peso   ?: '1.0');
    $c7a_cascada_exh_actual     = ((string)($posstock->c7a_cascada_exhaustiva      ?: 'false') === 'true');

    $html .= '<div class="panel panel-default">';
    $html .= '  <div class="panel-heading small text-uppercase fw-bold"><i class="glyphicon glyphicon-warning-sign"></i> Merma acumulada — tendencia ascendente de suelos (C7a)</div>';
    $html .= '  <div class="panel-body">';

    // ── Fila 1: Pre-filtro — variación mínima observable ──
    $html .= '    <p class="small text-muted" style="margin:0 0 6px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Pre-filtro</p>';
    $html .= '    <div class="row">';

    $html .= '      <div class="col-xs-12 col-sm-6">';
    $html .= '        <label class="control-label small" title="Variación mínima acumulada (floor_último − floor_primero) en unidades para que el artículo entre en el análisis de tendencia. Valores demasiado bajos incluyen ruido habitual de inventario. Rango: 0.5–20.">Variación mín. acumulada — unidad<br><small class="text-muted">(floor último − floor primero)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="0.5" min="0.5" max="20" class="form-control text-right" name="inputC7aUmbralDeltaUnidad" value="' . htmlspecialchars($c7a_delta_unidad_actual) . '" required>';
    $html .= '          <span class="input-group-addon">ud.</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-6">';
    $html .= '        <label class="control-label small" title="Variación mínima acumulada en kg para artículos de peso. Menor que el de unidad porque balanzas y cortes acumulan drift intrínseco. Rango: 0.1–10.">Variación mín. acumulada — peso<br><small class="text-muted">(fruta, carnicería, etc.)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="0.1" min="0.1" max="10" class="form-control text-right" name="inputC7aUmbralDeltaPeso" value="' . htmlspecialchars($c7a_delta_peso_actual) . '" required>';
    $html .= '          <span class="input-group-addon">kg</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '    </div>';

    // ── Fila 2: Test estadístico Mann-Kendall ──
    $html .= '    <p class="small text-muted" style="margin:10px 0 6px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Test estadístico (Mann-Kendall — Nivel 1)</p>';
    $html .= '    <p class="small text-muted" style="margin:0 0 6px;">Con n&lt;4 recepciones, el nivel 1 se omite automáticamente (p mínimo alcanzable = 0.33 con n=3) y la cascada empieza en el bootstrap (nivel 2).</p>';
    $html .= '    <div class="row">';

    $html .= '      <div class="col-xs-12 col-sm-6">';
    $html .= '        <label class="control-label small" title="Umbral de p-value para confianza ALTA en Mann-Kendall (p &lt; umbral_alta → C7a confirmado, nivel 1, confianza alta). Debe ser menor que el umbral de descarte. Estándar estadístico: 0.05. Rango: 0.01–0.10.">P-value confianza alta<br><small class="text-muted">(p &lt; umbral → confianza alta, para n ≥ 5)</small></label>';
    $html .= '        <input type="number" step="0.01" min="0.01" max="0.10" class="form-control input-sm text-right" name="inputC7aPvalueAlta" value="' . htmlspecialchars($c7a_pvalue_alta_actual) . '" required>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-6">';
    $html .= '        <label class="control-label small" title="Umbral de p-value para resultado negativo en Mann-Kendall (p ≥ umbral → NO C7a, la cascada se detiene). Por debajo de este umbral y por encima del umbral alta, la cascada continúa al bootstrap. Rango: 0.05–0.30.">P-value umbral negativo<br><small class="text-muted">(p ≥ umbral → NO C7a en nivel 1)</small></label>';
    $html .= '        <input type="number" step="0.01" min="0.05" max="0.30" class="form-control input-sm text-right" name="inputC7aPvalue" value="' . htmlspecialchars($c7a_pvalue_actual) . '" required>';
    $html .= '      </div>';

    $html .= '    </div>';

    // ── Fila 3: Severidad ALTA ──
    $html .= '    <p class="small text-muted" style="margin:10px 0 6px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Severidad ALTA</p>';
    $html .= '    <div class="row">';

    $html .= '      <div class="col-xs-12 col-sm-3">';
    $html .= '        <label class="control-label small" title="Delta acumulado mínimo (ud.) para elevar la severidad a ALTA. Por debajo: MEDIA. Rango: 2–50.">Delta ALTA — unidad<br><small class="text-muted">(pérdida total mínima)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="1" min="2" max="50" class="form-control text-right" name="inputC7aUmbralAltaDeltaUnidad" value="' . htmlspecialchars($c7a_alta_delta_ud_actual) . '" required>';
    $html .= '          <span class="input-group-addon">ud.</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-3">';
    $html .= '        <label class="control-label small" title="Delta acumulado mínimo (kg) para severidad ALTA en artículos de peso. Rango: 1–20.">Delta ALTA — peso<br><small class="text-muted">(pérdida total mínima)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="0.5" min="1" max="20" class="form-control text-right" name="inputC7aUmbralAltaDeltaPeso" value="' . htmlspecialchars($c7a_alta_delta_kg_actual) . '" required>';
    $html .= '          <span class="input-group-addon">kg</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-3">';
    $html .= '        <label class="control-label small" title="Pendiente Theil-Sen mínima (ud./recepción) para severidad ALTA. Representa el ritmo de crecimiento del suelo. Rango: 0.5–10.">Pendiente ALTA — unidad<br><small class="text-muted">(ud./recepción)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="0.5" min="0.5" max="10" class="form-control text-right" name="inputC7aUmbralAltaSlopeUnidad" value="' . htmlspecialchars($c7a_alta_slope_ud_actual) . '" required>';
    $html .= '          <span class="input-group-addon">ud./rec.</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-3">';
    $html .= '        <label class="control-label small" title="Pendiente Theil-Sen mínima (kg/recepción) para severidad ALTA en artículos de peso. Rango: 0.1–5.">Pendiente ALTA — peso<br><small class="text-muted">(kg/recepción)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="0.1" min="0.1" max="5" class="form-control text-right" name="inputC7aUmbralAltaSlopePeso" value="' . htmlspecialchars($c7a_alta_slope_kg_actual) . '" required>';
    $html .= '          <span class="input-group-addon">kg/rec.</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '    </div>';

    // ── Fila 4: Diagnóstico ──
    $html .= '    <div class="panel panel-warning" style="margin:12px 0 0;border-radius:3px;">';
    $html .= '      <div class="panel-body" style="padding:8px 12px;">';
    $html .= '        <div class="checkbox" style="margin:0;">';
    $html .= '          <label class="small" title="Modo diagnóstico: la cascada continúa aunque el nivel 1 (Mann-Kendall) o el nivel 2 (bootstrap) den un resultado negativo válido. El motivo de cada fallback queda registrado en cascade_fallback_reason. Desactivar en producción.">';
    $html .= '            <input type="checkbox" name="inputC7aCascadaExhaustiva" value="1"' . ($c7a_cascada_exh_actual ? ' checked' : '') . '>';
    $html .= '            <strong>Cascada exhaustiva</strong> <small class="text-muted">— continúa aunque un nivel no sea significativo (diagnóstico y auditoría, no recomendado en producción)</small>';
    $html .= '          </label>';
    $html .= '        </div>';
    $html .= '      </div>';
    $html .= '    </div>';

    $html .= '  </div>';
    $html .= '</div>';

    // --- BLOQUE 5: CASO 9 — MERMA POR BACKSTAGING (LIFO INVERSO) ---
    $c9_k_actual              = (string)($posstock->c9_profundidad_k        ?: '4');
    $c9_beta_actual           = (string)($posstock->c9_beta                  ?: '0.15');
    $c9_lambda_actual         = (string)($posstock->c9_lambda                ?: '1.5');
    $c9_epsilon_actual        = (string)($posstock->c9_epsilon               ?: '1.0');
    $c9_min_rec_actual        = (string)($posstock->c9_min_recepciones       ?: '3');
    $c9_umbral_ud_actual      = (string)($posstock->c9_umbral_merma_unidad   ?: '2.0');
    $c9_umbral_kg_actual      = (string)($posstock->c9_umbral_merma_peso     ?: '1.0');
    $c9_dias_post_actual      = (string)($posstock->c9_dias_post             ?: '60');

    $html .= '<div class="panel panel-default">';
    $html .= '  <div class="panel-heading small text-uppercase fw-bold"><i class="glyphicon glyphicon-fire"></i> Merma por backstaging — LIFO inverso (C9)</div>';
    $html .= '  <div class="panel-body">';

    // ── Fila 1: Pre-filtro ──
    $html .= '    <p class="small text-muted" style="margin:0 0 6px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Pre-filtro</p>';
    $html .= '    <div class="row">';

    $html .= '      <div class="col-xs-12 col-sm-4">';
    $html .= '        <label class="control-label small" title="Número mínimo de recepciones en el periodo para analizar el artículo con C9. Con pocas recepciones el modelo LIFO no es fiable. Rango: 2–10.">Recepciones mínimas<br><small class="text-muted">(lotes necesarios para el modelo)</small></label>';
    $html .= '        <input type="number" step="1" min="2" max="10" class="form-control input-sm text-right" name="inputC9MinRecepciones" value="' . htmlspecialchars($c9_min_rec_actual) . '" required>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-4">';
    $html .= '        <label class="control-label small" title="Umbral de merma estimada (unidades) por debajo del cual el artículo no se reporta como C9. Evita ruido en artículos de bajo volumen. Rango: 0.5–10.">Umbral merma mínima — unidad<br><small class="text-muted">(merma_total mín. para reportar)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="0.5" min="0.5" max="10" class="form-control text-right" name="inputC9UmbralMermaUnidad" value="' . htmlspecialchars($c9_umbral_ud_actual) . '" required>';
    $html .= '          <span class="input-group-addon">ud.</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-4">';
    $html .= '        <label class="control-label small" title="Umbral de merma estimada (kg) para artículos de peso. Rango: 0.2–5.">Umbral merma mínima — peso<br><small class="text-muted">(merma_total mín. para reportar)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="0.1" min="0.2" max="5" class="form-control text-right" name="inputC9UmbralMermaPeso" value="' . htmlspecialchars($c9_umbral_kg_actual) . '" required>';
    $html .= '          <span class="input-group-addon">kg</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '    </div>';

    // ── Fila 2: Modelo de redistribución ──
    $html .= '    <p class="small text-muted" style="margin:10px 0 6px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Modelo de redistribución LIFO inverso</p>';
    $html .= '    <div class="row">';

    $html .= '      <div class="col-xs-12 col-sm-3">';
    $html .= '        <label class="control-label small" title="Profundidad de la ventana de retroceso (número de lotes anteriores al déficit que pueden absorber merma). Valores altos capturan redistribuciones más lejanas pero aumentan el ruido. Rango: 2–10.">Profundidad k<br><small class="text-muted">(lotes previos al déficit)</small></label>';
    $html .= '        <input type="number" step="1" min="2" max="10" class="form-control input-sm text-right" name="inputC9ProfundidadK" value="' . htmlspecialchars($c9_k_actual) . '" required>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-3">';
    $html .= '        <label class="control-label small" title="Tasa de decaimiento exponencial β de los pesos de redistribución: w_i = exp(−β·dist). β alto concentra la merma en los lotes más recientes; β bajo la distribuye más uniformemente. Rango: 0.05–1.0.">Beta β (decaimiento)<br><small class="text-muted">(w_i = exp(−β·dist))</small></label>';
    $html .= '        <input type="number" step="0.05" min="0.025" max="1.0" class="form-control input-sm text-right" name="inputC9Beta" value="' . htmlspecialchars($c9_beta_actual) . '" required>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-3">';
    $html .= '        <label class="control-label small" title="Multiplicador λ del umbral estadístico local: threshold_t = μ_ventana + λ·σ_ventana. Los lotes cuya merma_t no supere el threshold se ignoran. Rango: 1.0–3.0.">Lambda λ (umbral estadístico)<br><small class="text-muted">(μ + λ·σ de la ventana local)</small></label>';
    $html .= '        <input type="number" step="0.1" min="1.0" max="4.0" class="form-control input-sm text-right" name="inputC9Lambda" value="' . htmlspecialchars($c9_lambda_actual) . '" required>';
    $html .= '      </div>';

    $html .= '      <div class="col-xs-12 col-sm-3">';
    $html .= '        <label class="control-label small" title="Epsilon ε: diferencia mínima absoluta entre S_t y el threshold para considerar el déficit significativo. Filtra oscilaciones numéricas muy pequeñas. Rango: 0.1–5.0.">Epsilon ε (diferencia mín.)<br><small class="text-muted">(S_t − threshold &gt; ε)</small></label>';
    $html .= '        <input type="number" step="0.1" min="0.1" max="5.0" class="form-control input-sm text-right" name="inputC9Epsilon" value="' . htmlspecialchars($c9_epsilon_actual) . '" required>';
    $html .= '      </div>';

    $html .= '    </div>';

    // ── Fila 3: Ventana de devoluciones ──
    $html .= '    <p class="small text-muted" style="margin:10px 0 6px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Devoluciones post-periodo</p>';
    $html .= '    <div class="row">';

    $html .= '      <div class="col-xs-12 col-sm-4">';
    $html .= '        <label class="control-label small" title="Días adicionales tras el fin del periodo (ff_mov) que se rastrean para capturar devoluciones a proveedor que netan recepciones del periodo. Las devoluciones post-periodo reducen E_t del lote correspondiente. Rango: 30–120.">Días post-periodo para devoluciones<br><small class="text-muted">(ventana de neteo de recepciones)</small></label>';
    $html .= '        <div class="input-group input-group-sm">';
    $html .= '          <input type="number" step="5" min="30" max="120" class="form-control text-right" name="inputC9DiasPost" value="' . htmlspecialchars($c9_dias_post_actual) . '" required>';
    $html .= '          <span class="input-group-addon">días</span>';
    $html .= '        </div>';
    $html .= '      </div>';

    $html .= '    </div>';

    $html .= '  </div>';
    $html .= '</div>';

    // --- BLOQUE 6: OPCIONES ADICIONALES ---
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
