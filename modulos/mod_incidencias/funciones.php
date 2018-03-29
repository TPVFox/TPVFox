<?php 
$pulsado = $_GET['pulsado'];
include_once ("./../../configuracion.php");
include_once ("./../mod_conexion/conexionBaseDatos.php");
include_once ("./popup_incidencias.php");
switch ($pulsado) {
	case 'nuevaIncidencia':
		$usuario= $_GET['usuario'];
		$fecha= $_GET['fecha'];
		$datos= $_GET['datos'];
		$dedonde= $_GET['dedonde'];
		$estado= $_GET['estado'];
		$mensaje= $_GET['mensaje'];
		$respuesta['datos']="entre aqui";
		if($mensaje){
			$nuevo=addIncidencia($usuario, $fecha, $dedonde, $datos, $estado, $mensaje, $BDTpv);
			$respuesta=$nuevo['sql'];
		}
	echo json_encode($respuesta);
	
	break;
	default:
	$respuesta="no entra en el switch";
	echo json_encode($respuesta);
	
	break;
	
	
	
}

?>
