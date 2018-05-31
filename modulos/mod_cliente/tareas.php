<?php 
$pulsado = $_POST['pulsado'];

include_once './../../inicial.php';
include_once './funciones.php';

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
	
	case 'Borrar_producto_tarifa_cliente':
		include_once("./tareas/borrarArticuloCliente.php");
		$respuesta = $resultado;
		break;
	
	case 'leerArticulo':
		include_once("./tareas/leerArticulo.php");
		$respuesta = $resultado;
		break;
	case 'imprimirResumenTickets':
		include_once ("./tareas/imprimirResumenTickets.php");
		$respuesta=$resultado;

}
echo json_encode($respuesta);
return $respuesta;
?>
