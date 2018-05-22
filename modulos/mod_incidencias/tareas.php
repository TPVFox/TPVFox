<?php 
$pulsado = $_POST['pulsado'];

//~ include_once ("./../../configuracion.php");
include_once ("./../../inicial.php");
include_once ("./clases/ClaseIncidencia.php");
$Cincidencias = new ClaseIncidencia($BDTpv);
//~ include_once ("./popup_incidencias.php");

switch ($pulsado) {
	case 'abririncidencia':
		$numIncidencia	= $_POST['numIncidencia']; // Siempre lo debemos enviar, si es nuevo enviamos 0
		$configuracion	= $_POST['configuracion'];
		$dedonde		= 'mod_incidencia';
		$datos=array(
		'vista'=>$_POST['dedonde']
		);
		$datos			= json_encode($datos);
		$estado="No resuelto";
		$html 	=	$Cincidencias->htmlModalIncidencia($datos, $dedonde, $configuracion, $estado, $numIncidencia);
		$respuesta['html']=$html;
		$respuesta['datos']=$datos;
		
		break;
		
	case 'nuevaIncidencia':
		$respuesta = array();
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
			$respuesta=$Cincidencias->addIncidencia( $dedonde, $datos, $mensaje, $estado, $numIncidencia);
		}
	
	
	break;
	
}
echo json_encode($respuesta);
return $respuesta;
?>
