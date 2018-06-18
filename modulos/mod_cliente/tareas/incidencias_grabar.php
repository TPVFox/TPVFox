<?php
// Llegamos aquí de tareas , donde mostramos grabamos incidencia.
include_once ($URLCom."/modulos/mod_incidencias/clases/ClaseIncidencia.php");
include_once ($URLCom."/modulos/mod_cliente/funciones.php");
$CIncidencia=new ClaseIncidencia($BDTpv);
$usuario= $_POST['usuario'];
$fecha= $_POST['fecha'];
$datos= $_POST['datos'];
//~ $dedonde= $_POST['dedonde'];
$dedonde="mod_clientes";
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
?>
