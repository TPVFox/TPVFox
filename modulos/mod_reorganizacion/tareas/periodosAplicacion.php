<?php

// @ Objetivo
// Los meses o los días en que entró el producto de una relación, para que la pantalla de aplicar
// relación los ofrezca como periodo. No escribe nada. Exige el permiso «ver» de esa pantalla.

include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesAplicacion.php';
include_once $URLCom . '/modulos/mod_reorganizacion/clases/ClaseComprobacionStockConsulta.php';

$CAplicacion = new RelacionesAplicacion();
$respuesta = array('ok' => false, 'datos' => array(), 'avisos' => array());

// Desde tareas.php hay que nombrar la vista: sin ella, el permiso se buscaría en tareas.php.
if ($ClasePermisos->getAccion('ver', array('modulo' => 'mod_reorganizacion', 'vista' => 'AplicarRelacion.php')) != 1) {
    $respuesta['avisos'][] = $CAplicacion->montarAdvertencia('warning', 'No tienes permiso para aplicar relaciones: pídeselo al administrador.');
} else {
    $numRelacion = '';
    $tipoPeriodo = '';
    if (isset($_POST['numRelacion'])) {
        $numRelacion = (string) $_POST['numRelacion'];
    }
    if (isset($_POST['tipoPeriodo'])) {
        $tipoPeriodo = (string) $_POST['tipoPeriodo'];
    }
    $periodos = $CAplicacion->periodosConEntradas($numRelacion, $tipoPeriodo, new ClaseComprobacionStockConsulta());
    if (isset($periodos['error'])) {
        $respuesta['avisos'][] = $periodos['error'];
    } else {
        $respuesta['ok'] = true;
        $respuesta['datos'] = $periodos['datos'];
    }
}
