<?php 
$pulsado = $_POST['pulsado'];

include_once './../../inicial.php';


switch ($pulsado) {
	case 'abririncidencia':
		include_once("./tareas/incidencias_popup.php");
		break;
		
	case 'nuevaIncidencia':
		include_once("./tareas/incidencias_grabar.php");
		breaK;
	
	case 'Grabar_tarifa_producto_cliente':
		include_once("./tareas/grabarArticuloCliente.php");
		$respuesta = $resultado;
		break;

}

echo json_encode($respuesta);

?>
