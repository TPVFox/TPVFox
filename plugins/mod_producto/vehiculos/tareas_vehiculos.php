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
		break;
		
		case 'BuscarVersionVehiculo':
			$idModelo = $_POST['idModelo'];
			$respuesta = array();
			
			$datosVersionesUnModelo = $ObjVehiculos->ObtenerVersionesUnModeloWeb($idModelo);
			$respuesta['options']= $datosVersionesUnModelo['Datos']['options_html'];
		break;
		
		case 'BuscarVehiculo':
			$idVersion = $_POST['idVersion'];
			$respuesta = array();
			$datosUnVehiculo = $ObjVehiculos->ObtenerUnVehiculo($idVersion);
            
			$respuesta= $datosUnVehiculo;
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
			// Ahora añado a session el coche seleccionado.
			if (!isset($_SESSION['coches_seleccionados'])){
				$_SESSION['coches_seleccionados'] = array();
			}
			$vehiculo[0]['Recambios'] = $idRecambios;
			$_SESSION['coches_seleccionados'][] = $vehiculo[0] ;
			
		break;
		
		case 'EliminarVehiculoSeleccionado':
			$item_vehiculo = $_POST['item_vehiculo'];
			if ( isset($_SESSION['coches_seleccionados'][$item_vehiculo])){
			    $vehiculo = $_SESSION['coches_seleccionados'][$item_vehiculo];
			    
			    if (count($vehiculo['Recambios'])>0){
					// Eliminamos los recambios de ese vehiculo.
					foreach ($vehiculo['Recambios'] as $valor){
						$index =array_search($valor, $_SESSION['productos_seleccionados']);
						if (gettype($index) !== 'boolean'){
							unset($_SESSION['productos_seleccionados'][$index]);
						}
					}
					
				}
				unset($_SESSION['coches_seleccionados'][$item_vehiculo]);
			$respuesta['eliminado_vehiculo'] = $item_vehiculo;
			}
			
		break;
        case 'modificarDatosWeb':
            $datos = $_POST['datos'];
            
			$respuesta = array();
			$modificarProducto = $ObjVehiculos->modificarProducto($datos);
            $respuesta['datos']=$datos;
			$respuesta['resul']= $modificarProducto;
        break;
		
	}
// Devolvemos.
echo json_encode($respuesta);

 
?>
