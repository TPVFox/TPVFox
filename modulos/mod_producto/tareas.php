<?php
/* Fichero de tareas a realizar.
 * 
 * 
 * Con el switch al final y variable $pulsado
 * 
 *  */
/* ===============  REALIZAMOS CONEXIONES  ===============*/


$pulsado = $_POST['pulsado'];

include_once ("./../../configuracion.php");

// Crealizamos conexion a la BD Datos
include_once ("./../mod_conexion/conexionBaseDatos.php");

// Incluimos funciones
include_once ("./funciones.php");

// Incluimos controlador.
include ("./../../controllers/Controladores.php");
$Controler = new ControladorComun; 
// Añado la conexion a controlador.
$Controler->loadDbtpv($BDTpv);

switch ($pulsado) {

	case 'HtmlLineaCodigoBarras';
	$item=$_POST['fila'];
		$respuesta = array();
		$res 	= HtmlLineaCodigoBarras($item);
		$respuesta['html'] =$res;
		echo json_encode($respuesta);
		break;
		
	case 'Grabar_configuracion':
		// Grabamos configuracion
		$configuracion = $_POST['configuracion'];
		// Ahora obtenemos nombre_modulo y usuario , lo ponermos en variable y quitamos array configuracion.
		$nombre_modulo = $configuracion['nombre_modulo'];
		$idUsuario = $configuracion['idUsuario'];
		unset($configuracion['nombre_modulo'],$configuracion['idUsuario']);
		
		$respuesta = $Controler->GrabarConfiguracionModulo($nombre_modulo,$idUsuario,$configuracion);		
		$respuesta['configuracion'] = $configuracion ; 
		
		echo json_encode($respuesta);
		break;
		
	
	
}


 
?>
