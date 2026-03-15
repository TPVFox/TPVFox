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
    || $mapa['inputUmbralSobrestock'] > 200
) {
    $errores[] = 'Umbral sobrestock debe ser un número entre 0 y 200 (porcentaje).';
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

$modelos_validos   = ['binomial', 'poisson'];
$confianzas_validas = ['0.10', '0.05', '0.01'];

if (!isset($mapa['inputModeloRoturaC5']) || !in_array($mapa['inputModeloRoturaC5'], $modelos_validos, true)) {
    $mapa['inputModeloRoturaC5'] = 'binomial';
}
if (!isset($mapa['inputUmbralConfianzaPoisson']) || !in_array($mapa['inputUmbralConfianzaPoisson'], $confianzas_validas, true)) {
    $mapa['inputUmbralConfianzaPoisson'] = '0.05';
}

if (!empty($errores)) {
    $respuesta['error'] = implode(' | ', $errores);
} else {
    $incluir_stock_inactivo      = (isset($mapa['inputIncluirStockInactivo'])      && $mapa['inputIncluirStockInactivo']      === '1') ? 1 : 0;
    $c5_incluir_stock_negativo   = (isset($mapa['inputC5IncluirStockNegativo'])   && $mapa['inputC5IncluirStockNegativo']   === '1') ? 1 : 0;

    $posstock->ventana_dias                       = $mapa['inputVentanaDias'];
    $posstock->umbral_sobrestock                  = $mapa['inputUmbralSobrestock'];
    $posstock->umbral_semanas_desde_ultima_venta  = intval($mapa['inputUmbralCaducidadSemanas']);
    $posstock->umbral_semanas_sin_rotacion        = intval($mapa['inputUmbralSinRotacionSemanas']);
    $posstock->incluir_stock_inactivo             = $incluir_stock_inactivo;
    $posstock->modelo_rotura_c5                   = $mapa['inputModeloRoturaC5'];
    $posstock->umbral_confianza_poisson           = $mapa['inputUmbralConfianzaPoisson'];
    $posstock->c5_incluir_stock_negativo          = $c5_incluir_stock_negativo;

    if ($ClaseParametros->save()) {
        $respuesta['mensaje']                  = 'Configuración POSStock guardada correctamente.';
        $respuesta['ventana_dias']             = (int)$mapa['inputVentanaDias'];
        $respuesta['modelo_rotura_c5']         = $mapa['inputModeloRoturaC5'];
        $respuesta['umbral_confianza_poisson'] = $mapa['inputUmbralConfianzaPoisson'];
    } else {
        $respuesta['error'] = 'No se pudo guardar el fichero de configuración.';
    }
}
