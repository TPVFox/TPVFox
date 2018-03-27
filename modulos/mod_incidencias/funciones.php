<?php 

$pulsado = $_POST['pulsado'];

include_once ("./../../configuracion.php");
// Crealizamos conexion a la BD Datos
include_once ("./../mod_conexion/conexionBaseDatos.php");
include_once ("/popup_incidencias.php");
switch ($pulsado) {
	case 'nuevaIncidencia':
		$usuario=$_POST['usuario'];
		$fecha=$_POST['fecha'];
		$datos=$_POST['datos'];
		$dedonde=$_POST['dedonde'];
		$estado=$_POST['estado'];
		$mensaje=$_POST['mensaje'];
		if($mensaje){
			$nuevo=addIncidencia($usuario, $fecha, $dedonde, $datos, $estado, $mensaje, $BDTpv);
		}
	
	break;
	
	
}

?>
