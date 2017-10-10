
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
include ("./../../controllers/Controladores.php");
// Cargamos el controlador.
$TControlador = new ControladorComun; 
 
 switch ($pulsado) {
     
    case 'Preparar insert':
    $Arraytablas = $_POST['tablasImpor'];
    $respuesta= prepararInsertArticulosTpv($BDVirtuemart,$BDTpv,$prefijoBD,$Arraytablas);
	header('Content-Type: application/json');
	echo json_encode($respuesta);
	break;
	
    case 'Vaciar tablas';
	$tablas = $_POST['tablas'];
	// Llamamos fuciones de vaciar.
	$respuesta = EliminarArticulosTpv($BDTpv,$tablas,$TControlador);
	echo json_encode($respuesta,true);
	break;	
	
    case 'Realizar insert';
	$inserts = $_POST['InsertRealizar'];
	// Llamamos fuciones de vaciar.
	$respuesta= RealizarInsert($inserts,$BDTpv);
	echo json_encode($respuesta,true);
	break;	
	
	case 'Crear Tabla Temporal';
	$tablaTemporal = $_POST['TablaTemporal'];
	// Llamamos fuciones de vaciar.
	$CrearTablaTemporal = prepararTablaTempArticulosComp($BDVirtuemart,$tablaTemporal);
	echo json_encode($CrearTablaTemporal,true);
	break;	
	
}
 
/* ===============  CERRAMOS CONEXIONES  ===============*/

mysqli_close($BDImportDbf);

 
 
?>
