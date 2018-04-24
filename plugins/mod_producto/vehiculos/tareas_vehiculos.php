<?php
/* Fichero de tareas a realizar.
 * 
 * 
 * Con el switch al final y variable $pulsado
 * 
 *  */
/* ===============  REALIZAMOS CONEXIONES  ===============*/


$pulsado = $_POST['pulsado'];

include_once ("./../../../configuracion.php");

// Crealizamos conexion a la BD Datos
//~ include_once ("./../mod_conexion/conexionBaseDatos.php");
include_once ($RutaServidor.$HostNombre. "/clases/ClaseSession.php");

	// Solo creamos objeto si no existe.
	$thisTpv = new ClaseSession();
	$BDTpv = $thisTpv->getConexion();
include ($RutaServidor.$HostNombre."/plugins/mod_producto/vehiculos/ClaseVehiculos.php");
$ObjVehiculos = new PluginClaseVehiculos();

switch ($pulsado) {

	case 'BuscarModelos':
		$idMarca = $_POST['idMarca'];
		$respuesta = array();
		
		$datosModelosUnaMarca = $ObjVehiculos->ObtenerModelosUnaMarcaWeb($idMarca);
		$respuesta['options']= $datosModelosUnaMarca['Datos']['options_html'];
		echo json_encode($respuesta);
	break;
	
	case 'BuscarVersionVehiculo':
		$idModelo = $_POST['idModelo'];
		$respuesta = array();
		
		$datosVersionesUnModelo = $ObjVehiculos->ObtenerVersionesUnModeloWeb($idModelo);
		$respuesta['options']= $datosVersionesUnModelo['Datos']['options_html'];
		echo json_encode($respuesta);
	break;
	
	case 'BuscarVehiculo':
		$idVersion = $_POST['idVersion'];
		$respuesta = array();
		
		$datosUnVehiculo = $ObjVehiculos->ObtenerUnVehiculo($idVersion);
		$respuesta= $datosUnVehiculo;
		echo json_encode($respuesta);
	break;

	case 'GuardarVehiculoSeleccionado':
		$vehiculo = $_POST['datosVehiculo'];
		$idRecambios = $_POST['idRecambios'];
		$respuesta = array();
		if (!isset($_SESSION['productos_seleccionados'])){
			$_SESSION['productos_seleccionados'] = array();
		}
		foreach ($idRecambios as $id){
			$_SESSION['productos_seleccionados'][] =  $id;
		};
		$htmlVehiculo = $ObjVehiculos->HtmlVehiculo($vehiculo[0],count($idRecambios));
		// Ahora añado a session el coche seleccionado.
		if (!isset($_SESSION['coches_seleccionados'])){
			$_SESSION['coches_seleccionados'] = array();
		}
		$_SESSION['coches_seleccionados'][] = $htmlVehiculo;
		$respuesta['html']= $htmlVehiculo;
		echo json_encode($respuesta);
	break;
	
}


 
?>
