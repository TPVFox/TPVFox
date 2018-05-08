<?php 
include_once ("./../../configuracion.php");

// Crealizamos conexion a la BD Datos
include_once ("./../../inicial.php");
// Incluimos funciones
include_once ("./funciones.php");

// Obtenemos funcion que nos envia... 

$pulsado = $_POST['pulsado'];

 switch ($pulsado) {

	case 'CopiarDescripcion':
	
	if (isset($_POST['id'])){
		$id = $_POST['id'];
		$DatosRefCruzadas= $_POST['DatosRefCruzadas'];
	}
	
	$respuesta = CopiarDescripcion($id,$DatosRefCruzadas,$prefijoJoomla,$BDWebJoomla);
	header("Content-Type: application/json;charset=utf-8");
	echo json_encode($respuesta);

}

?>
