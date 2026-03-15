<?php
include_once './../../inicial.php';

$pulsado = $_POST['pulsado'];
include_once $URLCom . '/configuracion.php';
include_once $URLCom . '/controllers/parametros.php';
include_once $URLCom . '/modulos/mod_informes/funciones.php';
include_once $URLCom . '/modulos/mod_informes/clases/ClasePosstock.php';



$respuesta = array();
switch ($pulsado) {
    case 'obtenerLoading':
        $html = '<img src="' . $HostNombre . '/css/img/loading.gif" alt="Esperando">';
        $respuesta['html'] = $html;
        break;
    case 'abrirModalConfigPosstock':
        include_once $URLCom . '/modulos/mod_informes/tareas/abrirModalConfigPosstock.php';
        break;
    case 'guardarConfigPosstock':
        $ClaseParametros = new ClaseParametros('parametros.xml');
        $posstock = $ClaseParametros->getNode('configuracion/posstock');
        $datosFormulario = json_decode($_POST['datosFormulario'], true);
        $mapa = array();
        foreach ($datosFormulario as $campo) {
            $mapa[$campo['name']] = $campo['value'];
        }
        $errores = array();
        if (!isset($mapa['inputVentanaDias']) || !in_array($mapa['inputVentanaDias'], ['0', '7', '14'])) {
            $errores[] = 'Ventana de días debe ser 0 (sin restricción), 7 o 14.';
        }
        if (!isset($mapa['inputUmbralSobrestock']) || !is_numeric($mapa['inputUmbralSobrestock']) || $mapa['inputUmbralSobrestock'] < 0 || $mapa['inputUmbralSobrestock'] > 200) {
            $errores[] = 'Umbral sobrestock debe ser un número entre 0 y 200 (porcentaje).';
        }
        if (!isset($mapa['inputUmbralCaducidadSemanas']) || !ctype_digit($mapa['inputUmbralCaducidadSemanas']) || intval($mapa['inputUmbralCaducidadSemanas']) < 1) {
            $errores[] = 'Semanas sin venta debe ser un número entero positivo.';
        }
        if (!isset($mapa['inputUmbralSinRotacionSemanas']) || !ctype_digit($mapa['inputUmbralSinRotacionSemanas']) || intval($mapa['inputUmbralSinRotacionSemanas']) < 1) {
            $errores[] = 'Semanas sin rotación debe ser un número entero positivo.';
        }
        $modelos_validos = ['binomial', 'poisson'];
        if (!isset($mapa['inputModeloRoturaC5']) || !in_array($mapa['inputModeloRoturaC5'], $modelos_validos, true)) {
            $mapa['inputModeloRoturaC5'] = 'binomial';
        }
        $confianzas_validas = ['0.10', '0.05', '0.01'];
        if (!isset($mapa['inputUmbralConfianzaPoisson']) || !in_array($mapa['inputUmbralConfianzaPoisson'], $confianzas_validas, true)) {
            $mapa['inputUmbralConfianzaPoisson'] = '0.05';
        }
        if (!empty($errores)) {
            $respuesta['error'] = implode(' | ', $errores);
        } else {
            $incluir_stock_inactivo = (isset($mapa['inputIncluirStockInactivo']) && $mapa['inputIncluirStockInactivo'] === '1') ? 1 : 0;
            $posstock->ventana_dias                       = $mapa['inputVentanaDias'];
            $posstock->umbral_sobrestock                  = $mapa['inputUmbralSobrestock'];
            $posstock->umbral_semanas_desde_ultima_venta  = intval($mapa['inputUmbralCaducidadSemanas']);
            $posstock->umbral_semanas_sin_rotacion        = intval($mapa['inputUmbralSinRotacionSemanas']);
            $posstock->incluir_stock_inactivo             = $incluir_stock_inactivo;
            $posstock->modelo_rotura_c5                   = $mapa['inputModeloRoturaC5'];
            $posstock->umbral_confianza_poisson           = $mapa['inputUmbralConfianzaPoisson'];
            if ($ClaseParametros->save()) {
                $respuesta['mensaje']                   = 'Configuración POSStock guardada correctamente.';
                $respuesta['ventana_dias']              = (int)$mapa['inputVentanaDias'];
                $respuesta['modelo_rotura_c5']          = $mapa['inputModeloRoturaC5'];
                $respuesta['umbral_confianza_poisson']  = $mapa['inputUmbralConfianzaPoisson'];
            } else {
                $respuesta['error'] = 'No se pudo guardar el fichero de configuración.';
            }
        }
        break;
    case 'calcularPeriodoPosstock':
        $tipo   = $_POST['tipo']   ?? '';
        $numero = $_POST['numero'] ?? 0;
        $anio   = $_POST['anio']   ?? date('Y');
        $periodo = calcularPeriodoPosstock($tipo, (int)$numero, (int)$anio);
        if ($periodo === null) {
            $respuesta['error'] = 'Tipo de periodo no válido: ' . $tipo;
        } else {
            $respuesta = $periodo;
        }
        break;
    case 'getPOSStockData':
        include_once $URLCom . '/modulos/mod_informes/tareas/getPOSStockData.php';
        break;
    case 'getPOSStockBatch':
        include_once $URLCom . '/modulos/mod_informes/tareas/getPOSStockBatch.php';
        break;
    case 'getFamiliasPosstock':
        include_once $URLCom . '/modulos/mod_informes/tareas/getFamiliasPosstock.php';
        break;
    case 'exportarPOSStockCSV':
        // El fichero include emite cabeceras HTTP + CSV y llama a exit(),
        // por lo que el json_encode del final de este script no se ejecuta.
        include_once $URLCom . '/modulos/mod_informes/tareas/exportarPOSStockCSV.php';
        break;
    case 'imprimirPOSStockPDF':
        include_once $URLCom . '/modulos/mod_informes/tareas/imprimirPOSStockPDF.php';
        break;
    default:
        error_log('Tarea mod_informes case no encontrado: ' . $pulsado);
}
echo json_encode($respuesta);
return $respuesta;
