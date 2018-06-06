<?php 
$pulsado = $_POST['pulsado'];
include_once $RutaServidor . $HostNombre .'/modulos/mod_configuracion/clases/ClaseIva.php';
switch ($pulsado) {
	case 'abrirModalModificar':
		$html=abrirModal($_POST['id'], $_POST['dedonde']);
		$respuesta=$html;
	
	break;
	
	
	
}

echo json_encode($respuesta);
return $respuesta;
?>
