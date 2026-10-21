<?php

// @ Objetivo
// Preparar el albarán de la aplicación de una relación: vuelve a calcularla aquí, con lo que se
// eligió —los costes no se reciben de la pantalla—, y deja un albarán temporal de compras con
// sus líneas, que el usuario revisa y guarda en la pantalla de albaranes. Preparar no mueve
// existencias ni costes. Exige el permiso «Preparar» de la pantalla de aplicar relación.

include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesAplicacion.php';
include_once $URLCom . '/modulos/mod_reorganizacion/clases/ClaseComprobacionStockConsulta.php';
// Como el cierre anual: el albarán se deja con la función de compras, que no se modifica.
include_once $URLCom . '/modulos/mod_compras/clases/albaranesCompras.php';

$CAplicacion = new RelacionesAplicacion();
$respuesta = array('ok' => false, 'datos' => array(), 'avisos' => array());

// Desde tareas.php hay que nombrar la vista: sin ella, el permiso se buscaría en tareas.php.
if ($ClasePermisos->getAccion('Preparar', array('modulo' => 'mod_reorganizacion', 'vista' => 'AplicarRelacion.php')) != 1) {
    $respuesta['avisos'][] = $CAplicacion->montarAdvertencia('warning', 'No tienes permiso para preparar el albarán: pídeselo al administrador.');
} else {
    $peticion = array(
        'idProveedor' => '',
        'numRelacion' => '',
        'tipoPeriodo' => '',
        'periodo' => '',
        'version' => 'periodo',
        'idProveedorTraspaso' => '',
        'precios' => array(),
        'ajustePeso' => '1',
        'ajusteValor' => '1',
    );
    foreach (array('idProveedor', 'numRelacion', 'tipoPeriodo', 'periodo', 'version', 'idProveedorTraspaso', 'ajustePeso', 'ajusteValor') as $campo) {
        if (isset($_POST[$campo])) {
            $peticion[$campo] = (string) $_POST[$campo];
        }
    }
    if (isset($_POST['precios'])) {
        $precios = json_decode($_POST['precios'], true);
        if (is_array($precios)) {
            $peticion['precios'] = $precios;
        }
    }

    $respuesta = $CAplicacion->calcular($peticion, new ClaseComprobacionStockConsulta());
    if ($respuesta['ok'] === true) {
        $textos = $CAplicacion->textosAdmitidos($respuesta['datos']['lineas']);
        if (isset($textos['error'])) {
            $respuesta['ok'] = false;
            array_unshift($respuesta['avisos'], $textos['error']);
        } else {
            $fecha = $respuesta['datos']['periodo']['fecha'];
            $suNumero = 'ESCANDALLO';
            $CAlbaranes = new AlbaranesCompras($BDTpv);
            $alta = $CAlbaranes->insertarDatosAlbaranTemporal($Usuario['id'], $Tienda['idTienda'], 'Nuevo', $fecha, $respuesta['datos']['lineas'], (int) $peticion['idProveedor'], array(), $suNumero);
            if (isset($alta['id'])) {
                $respuesta['datos']['idTemporal'] = (int) $alta['id'];
            } else {
                error_log('Aplicar relación: no se pudo crear el albarán temporal: ' . json_encode($alta));
                $respuesta['ok'] = false;
                $respuesta['avisos'] = array($CAplicacion->montarAdvertencia('danger', 'No se pudo preparar el albarán: vuelve a intentarlo y, si se repite, avisa al administrador.'));
            }
        }
    }
}
