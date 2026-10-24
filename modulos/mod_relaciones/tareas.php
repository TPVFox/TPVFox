<?php

include_once './../../inicial.php';
require_once $URLCom . '/clases/exigirSesion.php';
exigirSesion();
include_once $URLCom . '/modulos/mod_relaciones/clases/Relaciones.php';
include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesTemporales.php';
include_once $URLCom . '/modulos/mod_relaciones/funciones.php';

// @ Objetivo
// Atender lo que piden las pantallas del módulo. Cada caso lee sus campos por nombre y llama a
// una función de funciones.php, que comprueba su permiso antes de leer o escribir nada.

$pulsado = $_POST['pulsado'];
// La clase del módulo se crea aquí y se pasa a cada función, como en los demás módulos.
$CRelaciones = new Relaciones();
$CTemporales = new RelacionesTemporales();
$respuesta = array();
switch ($pulsado) {
    case 'cambiarEstadoRelaciones':
        $numRelaciones = array();
        if (isset($_POST['numRelaciones'])) {
            $numRelaciones = $_POST['numRelaciones'];
        }
        $estado = '';
        if (isset($_POST['estado'])) {
            $estado = $_POST['estado'];
        }
        $respuesta = cambiarEstadoRelaciones($numRelaciones, $estado, $CRelaciones, $ClasePermisos, $Usuario);
        break;

    case 'buscarProductos':
        $caja = '';
        if (isset($_POST['caja'])) {
            $caja = $_POST['caja'];
        }
        $texto = '';
        if (isset($_POST['texto'])) {
            $texto = $_POST['texto'];
        }
        $respuesta = buscarProductosRelacion($caja, $texto, $CRelaciones, $ClasePermisos);
        break;

    case 'htmlLineasRelacion':
        $numRelacion = 0;
        if (isset($_POST['numRelacion'])) {
            $numRelacion = $_POST['numRelacion'];
        }
        $idTipo = 0;
        if (isset($_POST['idTipo'])) {
            $idTipo = $_POST['idTipo'];
        }
        $lineas = array();
        if (isset($_POST['lineas'])) {
            $lineas = $_POST['lineas'];
        }
        // Los datos de la relación solo llegan si la pantalla los manda; sin ellos se comparan
        // como si no cambiaran.
        $datos = array();
        foreach (array('nombre', 'descripcion', 'diasViabilidad') as $campo) {
            if (isset($_POST[$campo])) {
                $datos[$campo] = $_POST[$campo];
            }
        }
        $respuesta = htmlLineasRelacion($numRelacion, $idTipo, $lineas, $datos, $CRelaciones, $ClasePermisos);
        break;

    case 'guardarTemporal':
        $temporal = array();
        foreach (array('id', 'numRelacion', 'idTipo', 'nombre', 'descripcion', 'diasViabilidad', 'origenAjuste', 'fechaModificacion') as $campo) {
            $temporal[$campo] = '';
            if (isset($_POST[$campo])) {
                $temporal[$campo] = $_POST[$campo];
            }
        }
        $temporal['lineas'] = array();
        if (isset($_POST['lineas'])) {
            $temporal['lineas'] = $_POST['lineas'];
        }
        $respuesta = guardarTemporalRelacion($temporal, $CTemporales, $ClasePermisos, $Usuario);
        break;

    case 'cancelarTemporal':
        $idTemporal = 0;
        if (isset($_POST['id'])) {
            $idTemporal = $_POST['id'];
        }
        $respuesta = cancelarTemporalRelacion($idTemporal, $CTemporales, $ClasePermisos);
        break;

    case 'activarTemporal':
        $idTemporal = 0;
        if (isset($_POST['id'])) {
            $idTemporal = $_POST['id'];
        }
        $respuesta = activarTemporalRelacion($idTemporal, $CTemporales, $CRelaciones, $ClasePermisos, $Usuario);
        break;
}

echo json_encode($respuesta);
return $respuesta;
