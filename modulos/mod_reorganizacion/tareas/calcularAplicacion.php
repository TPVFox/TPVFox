<?php

// @ Objetivo
// Calcular la aplicación de una relación a un periodo para enseñarla en la pantalla: la versión
// que regía, lo disponible, el coste de partida, el aviso de proceso duplicado, la cantidad que
// entra en la receta y el escandallo. No escribe nada. Exige el permiso «ver» de la pantalla de aplicar relación.

include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesAplicacion.php';
include_once $URLCom . '/modulos/mod_reorganizacion/clases/ClaseComprobacionStockConsulta.php';

$CAplicacion = new RelacionesAplicacion();
$respuesta = array('ok' => false, 'datos' => array(), 'avisos' => array());

// Desde tareas.php hay que nombrar la vista: sin ella, el permiso se buscaría en tareas.php.
if ($ClasePermisos->getAccion('ver', array('modulo' => 'mod_reorganizacion', 'vista' => 'AplicarRelacion.php')) != 1) {
    $respuesta['avisos'][] = $CAplicacion->montarAdvertencia('warning', 'No tienes permiso para aplicar relaciones: pídeselo al administrador.');
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
        'cantidad' => '',
    );
    foreach (array('idProveedor', 'numRelacion', 'tipoPeriodo', 'periodo', 'version', 'idProveedorTraspaso', 'ajustePeso', 'ajusteValor', 'cantidad') as $campo) {
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
}
