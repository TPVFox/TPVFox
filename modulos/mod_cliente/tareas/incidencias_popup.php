<?php
// Llegamos aquí de tareas , donde necesitamos creamos incidencia.
include_once ($URLCom."/modulos/mod_incidencias/clases/ClaseIncidencia.php");
include_once ($URLCom."/modulos/mod_cliente/funciones.php");
$CIncidencia=new ClaseIncidencia($BDTpv);
$dedonde=$_POST['dedonde'];
$usuario=$_POST['usuario'];
$idReal=0;
if(isset($_POST['idReal'])){
	$idReal=$_POST['idReal'];
}

$configuracion=$_POST['configuracion'];
$numInicidencia=0;
$tipo="mod_cliente";
$fecha=date('Y-m-d');
$datos=array(
'vista'=>$dedonde,
'idReal'=>$idReal
);
$datos=json_encode($datos);
$estado="No resuelto";
$numIncidencia=0;
$html=$CIncidencia->htmlModalIncidencia($datos, $dedonde, $configuracion, $estado, $numIncidencia);
//~ $html=modalIncidencia($usuario, $datos, $fecha, $tipo, $estado, $numInicidencia, $configuracion, $BDTpv);
$respuesta['html']=$html;
$respuesta['datos']=$datos;

