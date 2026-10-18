<?php

include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_relaciones/clases/Relaciones.php';
include_once $URLCom . '/modulos/mod_relaciones/funciones.php';

// @ Objetivo
// Atender lo que piden las pantallas del módulo. Cada caso lee sus campos por nombre y llama a
// una función de funciones.php, que comprueba su permiso antes de leer o escribir nada.

$pulsado = $_POST['pulsado'];
// La clase del módulo se crea aquí y se pasa a cada función, como en los demás módulos.
$CRelaciones = new Relaciones();
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
}

echo json_encode($respuesta);
return $respuesta;
