<?php

// @ Objetivo
// Las recetas que se pueden aplicar a la vez que una relación —sus hermanas, del mismo tipo y con el
// mismo producto de partida, y las del mismo tipo que parten de sus derivados—, para que la pantalla
// de aplicar relación las ofrezca en su lista, y la elegida con los productos que salen de ella, con
// la que empieza el árbol. No escribe nada. Exige el permiso «ver» de esa pantalla.

include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesAplicacion.php';

$CAplicacion = new RelacionesAplicacion();
$respuesta = array('ok' => false, 'datos' => array(), 'avisos' => array());

// Desde tareas.php hay que nombrar la vista: sin ella, el permiso se buscaría en tareas.php.
if ($ClasePermisos->getAccion('ver', array('modulo' => 'mod_reorganizacion', 'vista' => 'AplicarRelacion.php')) != 1) {
    $respuesta['avisos'][] = $CAplicacion->montarAdvertencia('warning', 'No tienes permiso para aplicar relaciones: pídeselo al administrador.');
} else {
    $numRelacion = '';
    if (isset($_POST['numRelacion'])) {
        $numRelacion = (string) $_POST['numRelacion'];
    }
    $hermanas = array('error' => $CAplicacion->montarAdvertencia('warning', 'Falta elegir la relación: elígela en la lista.'));
    if (ctype_digit($numRelacion)) {
        $hermanas = $CAplicacion->hermanas((int) $numRelacion);
    }
    if (isset($hermanas['error'])) {
        $respuesta['avisos'][] = $hermanas['error'];
    } else {
        $respuesta['ok'] = true;
        $respuesta['datos'] = $hermanas['datos'];
        $respuesta['elegida'] = $hermanas['elegida'];
    }
}
