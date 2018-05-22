<?php 
$pulsado = $_POST['pulsado'];
include_once ("./../../configuracion.php");
include_once ("./../../inicial.php");
include_once ("./funciones.php");
//~ include_once ("../mod_incidencias/popup_incidencias.php");
include_once '../mod_incidencias/clases/ClaseIncidencia.php';
$CIncidencia=new ClaseIncidencia($BDTpv);
$respuesta=array();
switch ($pulsado) {
	case 'abririncidencia':
		$dedonde=$_POST['dedonde'];
		$usuario=$_POST['usuario'];
		$idReal=0;
		if(isset($_POST['idReal'])){
			$idReal=$_POST['idReal'];
		}
		
		$configuracion=$_POST['configuracion'];
		$numInicidencia=0;
		$tipo="mod_proveedor";
		$fecha=date('Y-m-d');
		$datos=array(
		'vista'=>$dedonde,
		'idReal'=>$idReal
		);
		$datos=json_encode($datos);
		$estado="No resuelto";
		$html=$CIncidencia->htmlModalIncidencia($datos, $dedonde, $configuracion, $estado, $numIncidencia);
		//~ $html=modalIncidencia($usuario, $datos, $fecha, $tipo, $estado, $numInicidencia, $configuracion, $BDTpv);
		$respuesta['html']=$html;
		$respuesta['datos']=$datos;
	
		break;
		
		case 'nuevaIncidencia':
		$usuario= $_POST['usuario'];
		$fecha= $_POST['fecha'];
		$datos= $_POST['datos'];
		//~ $dedonde= $_POST['dedonde'];
		$dedonde="mod_proveedores";
		$estado= $_POST['estado'];
		$mensaje= $_POST['mensaje'];
		$usuarioSelect=0;
		if(isset($_POST['usuarioSelec'])){
		$usuarioSelect=$_POST['usuarioSelec'];
		}
		//~ error.log($usuarioSelect);
		if($usuarioSelect>0){
			$datos=json_decode($datos);
			//~ error.log($datos);
			$datos->usuarioSelec=$usuarioSelect;
			$datos=json_encode($datos);
		}
		$numInicidencia=0;
		if($mensaje){
			$nuevo=$CIncidencia->addIncidencia($dedonde, $datos, $mensaje, $estado, $numInicidencia);
			$respuesta=$nuevo;
			//~ $nuevo=addIncidencia($usuario, $fecha, $dedonde, $datos, $estado, $mensaje, $BDTpv,  $numInicidencia);
			//~ $respuesta=$nuevo['sql'];
		}
	
	
	break;
}
echo json_encode($respuesta);
return $respuesta;
?>
