<?php
// @ Objetivo: validar y guardar la configuración POSStock desde el formulario del modal.
// Lee los parámetros del POST, valida y escribe en el nodo <posstock> de parametros.xml.

$ClaseParametros = new ClaseParametros('parametros.xml');
$posstock        = $ClaseParametros->getNode('configuracion/posstock');
$datosFormulario = json_decode($_POST['datosFormulario'], true);

$mapa = [];
foreach ($datosFormulario as $campo) {
    $mapa[$campo['name']] = $campo['value'];
}

$errores = [];

if (!isset($mapa['inputVentanaDias']) || !in_array($mapa['inputVentanaDias'], ['0', '7', '14'])) {
    $errores[] = 'Ventana de días debe ser 0 (sin restricción), 7 o 14.';
}
if (!isset($mapa['inputUmbralSobrestock'])
    || !is_numeric($mapa['inputUmbralSobrestock'])
    || $mapa['inputUmbralSobrestock'] < 0
    || $mapa['inputUmbralSobrestock'] > 100
) {
    $errores[] = 'Umbral sobrestock debe ser un número entre 0 y 100 (porcentaje).';
}
if (!isset($mapa['inputC2UmbralCobertura'])
    || !ctype_digit($mapa['inputC2UmbralCobertura'])
    || intval($mapa['inputC2UmbralCobertura']) < 7
) {
    $errores[] = 'Cobertura mínima C2 debe ser un número entero de al menos 7 días.';
}
if (!isset($mapa['inputUmbralCaducidadSemanas'])
    || !ctype_digit($mapa['inputUmbralCaducidadSemanas'])
    || intval($mapa['inputUmbralCaducidadSemanas']) < 1
) {
    $errores[] = 'Semanas sin venta debe ser un número entero positivo.';
}
if (!isset($mapa['inputUmbralSinRotacionSemanas'])
    || !ctype_digit($mapa['inputUmbralSinRotacionSemanas'])
    || intval($mapa['inputUmbralSinRotacionSemanas']) < 1
) {
    $errores[] = 'Semanas sin rotación debe ser un número entero positivo.';
}

$modelos_validos  = ['automatico', 'binomial', 'poisson_bn', 'gamma'];
$sig_validas      = ['0.90', '0.95', '0.99'];
$sigma_validos    = ['2.0', '2.5', '3.0', '3.5', '4.0'];
$mult_c3a_validos = ['2.0', '2.5', '3.0', '3.5', '4.0', '4.5', '5.0', '5.5', '6.0'];

if (!isset($mapa['inputModeloEstadistico']) || !in_array($mapa['inputModeloEstadistico'], $modelos_validos, true)) {
    $mapa['inputModeloEstadistico'] = 'automatico';
}
if (!isset($mapa['inputModeloSignificancia']) || !in_array($mapa['inputModeloSignificancia'], $sig_validas, true)) {
    $mapa['inputModeloSignificancia'] = '0.95';
}
if (!isset($mapa['inputBinomialSigmaMult']) || !in_array($mapa['inputBinomialSigmaMult'], $sigma_validos, true)) {
    $mapa['inputBinomialSigmaMult'] = '3.0';
}
if (!isset($mapa['inputC6LeadTimeDefecto'])
    || !ctype_digit($mapa['inputC6LeadTimeDefecto'])
    || intval($mapa['inputC6LeadTimeDefecto']) < 1
) {
    $mapa['inputC6LeadTimeDefecto'] = '14';
}
if (!isset($mapa['inputC6bDiasHistorico'])
    || !ctype_digit($mapa['inputC6bDiasHistorico'])
    || intval($mapa['inputC6bDiasHistorico']) < 30
    || intval($mapa['inputC6bDiasHistorico']) > 365
) {
    $mapa['inputC6bDiasHistorico'] = '90';
}
if (!isset($mapa['inputC3bDiasPost'])
    || !ctype_digit($mapa['inputC3bDiasPost'])
    || intval($mapa['inputC3bDiasPost']) < 7
    || intval($mapa['inputC3bDiasPost']) > 30
) {
    $mapa['inputC3bDiasPost'] = '14';
}
if (!isset($mapa['inputC3aMultiplicadorCadencia']) || !in_array($mapa['inputC3aMultiplicadorCadencia'], $mult_c3a_validos, true)) {
    $mapa['inputC3aMultiplicadorCadencia'] = '3.0';
}

if (!empty($errores)) {
    $respuesta['error'] = implode(' | ', $errores);
} else {
    $incluir_stock_inactivo    = (isset($mapa['inputIncluirStockInactivo'])    && $mapa['inputIncluirStockInactivo']    === '1') ? 1 : 0;
    $c5_incluir_stock_negativo = (isset($mapa['inputC5IncluirStockNegativo']) && $mapa['inputC5IncluirStockNegativo'] === '1') ? 1 : 0;
    $incluir_albcli_ventas     = (isset($mapa['inputIncluirAlbcliVentas'])    && $mapa['inputIncluirAlbcliVentas']    === '1') ? 1 : 0;

    $posstock->ventana_dias                      = $mapa['inputVentanaDias'];
    $posstock->umbral_sobrestock                 = $mapa['inputUmbralSobrestock'];
    $posstock->c2_umbral_cobertura_dias          = intval($mapa['inputC2UmbralCobertura']);
    $posstock->umbral_semanas_desde_ultima_venta = intval($mapa['inputUmbralCaducidadSemanas']);
    $posstock->umbral_semanas_sin_rotacion       = intval($mapa['inputUmbralSinRotacionSemanas']);
    $posstock->incluir_stock_inactivo            = $incluir_stock_inactivo;
    $posstock->modelo_estadistico                = $mapa['inputModeloEstadistico'];
    $posstock->modelo_significancia              = $mapa['inputModeloSignificancia'];
    $posstock->binomial_sigma_mult               = $mapa['inputBinomialSigmaMult'];
    $posstock->c5_incluir_stock_negativo         = $c5_incluir_stock_negativo;
    $posstock->incluir_albcli_ventas             = $incluir_albcli_ventas;
    $posstock->c6_lead_time_defecto              = intval($mapa['inputC6LeadTimeDefecto']);
    $posstock->c6b_dias_historico                = intval($mapa['inputC6bDiasHistorico']);
    $posstock->c3b_dias_post_periodo             = intval($mapa['inputC3bDiasPost']);
    $posstock->c3a_multiplicador_cadencia        = $mapa['inputC3aMultiplicadorCadencia'];

    if ($ClaseParametros->save()) {
        $respuesta['mensaje']              = 'Configuración POSStock guardada correctamente.';
        $respuesta['ventana_dias']         = (int)$mapa['inputVentanaDias'];
        $respuesta['modelo_estadistico']   = $mapa['inputModeloEstadistico'];
        $respuesta['modelo_significancia'] = $mapa['inputModeloSignificancia'];
        $respuesta['c3b_dias_post']           = (int)$mapa['inputC3bDiasPost'];
        $respuesta['c3a_multiplicador']       = (float)$mapa['inputC3aMultiplicadorCadencia'];
    } else {
        $respuesta['error'] = 'No se pudo guardar el fichero de configuración.';
    }
}
