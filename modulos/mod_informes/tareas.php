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
        include_once $URLCom . '/modulos/mod_informes/tareas/guardarConfigPosstock.php';
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
    case 'getInfoPeriodos':
        include_once $URLCom . '/modulos/mod_informes/tareas/getInfoPeriodos.php';
        break;
    case 'getProveedoresList':
        include_once $URLCom . '/modulos/mod_informes/tareas/getProveedoresList.php';
        break;
    default:
        error_log('Tarea mod_informes case no encontrado: ' . $pulsado);
}
echo json_encode($respuesta);
return $respuesta;
