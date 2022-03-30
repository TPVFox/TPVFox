<?php 
include_once './../../inicial.php';

$pulsado = $_POST['pulsado'];
include_once $URLCom.'/configuracion.php';



$respuesta=array();
switch ($pulsado) {
	
}
echo json_encode($respuesta);
return $respuesta;
?>
