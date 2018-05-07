<?php 
$pulsado = $_POST['pulsado'];

include_once ("./../../configuracion.php");
include_once ("./../mod_conexion/conexionBaseDatos.php");
include_once ("./popup_incidencias.php");

switch ($pulsado) {
	case 'abririncidencia':
		$dedonde=$_POST['dedonde'];
		$usuario=$_POST['usuario'];
		$numIncidencia=0;
		if(isset($_POST['numIncidencia'])){
			$numIncidencia=$_POST['numIncidencia'];
			
		}
		$configuracion=$_POST['configuracion'];
		$tipo="mod_incidencias";
		$fecha=date('Y-m-d');
		$datos=array(
		'dedonde'=>$dedonde
		);
		
		$datos=json_encode($datos);
		$estado="No resuelto";
		$html=modalIncidencia($usuario, $datos, $fecha, $tipo, $estado, $numIncidencia, $configuracion, $BDTpv);
		$respuesta['html']=$html;
		$respuesta['datos']=$datos;
		
		break;
		
	case 'nuevaIncidencia':
		$usuario= $_POST['usuario'];
		$fecha= $_POST['fecha'];
		$datos= $_POST['datos'];
		$dedonde= $_POST['dedonde'];
		$estado= $_POST['estado'];
		$mensaje= $_POST['mensaje'];
		if(isset($_POST['usuarioSelec'])){
		$usuarioSelect=$_POST['usuarioSelec'];
		}
		
		if($usuarioSelect>0){
			$datos=json_decode($datos);
			//~ error.log($datos);
			$datos->usuarioSelec=$usuarioSelect;
			$datos=json_encode($datos);
		}
		$numIncidencia=0;
		if(isset($_POST['numIncidencia'])){
			$numIncidencia=$_POST['numIncidencia'];
			
		}
		
		if($mensaje){
			$nuevo=addIncidencia($usuario, $fecha, $dedonde, $datos, $estado, $mensaje, $BDTpv, $numIncidencia);
			$respuesta=$nuevo['sql'];
		}
	
	
	break;
	
}
echo json_encode($respuesta);
return $respuesta;
?>
