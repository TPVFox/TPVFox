<?php 

$pulsado = $_POST['pulsado'];
include_once 'clases/ClaseIva.php';
include_once 'clases/ClaseFormasPago.php';
include_once 'clases/ClaseVencimiento.php';
include_once 'funciones.php';
include_once ("./../../inicial.php");
switch ($pulsado) {
	case 'abrirModalModificar':
		$html=abrirModal($_POST['id'], $_POST['dedonde'], $BDTpv);
		$respuesta=$html;
	break;
	case 'ModificarTabla':
		$datos=$_POST['datos'];
		
	break;
	
	
	
}

echo json_encode($respuesta);
return $respuesta;
?>
