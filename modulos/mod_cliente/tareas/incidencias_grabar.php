<?php
// Llegamos aquí de tareas , donde mostramos grabamos incidencia.
include_once ($RutaServidor . $HostNombre."/modulos/mod_incidencias/popup_incidencias.php");
include_once ($RutaServidor . $HostNombre."/modulos/mod_cliente/funciones.php");

$usuario= $_POST['usuario'];
$fecha= $_POST['fecha'];
$datos= $_POST['datos'];
$dedonde= $_POST['dedonde'];
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
	$nuevo=addIncidencia($usuario, $fecha, $dedonde, $datos, $estado, $mensaje, $BDTpv,  $numInicidencia);
	$respuesta=$nuevo['sql'];
}
?>
