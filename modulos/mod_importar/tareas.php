
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
//~ include_once ("./../mod_conexion/conexionBaseDatos.php");

// Incluimos funciones
include_once ("./funciones.php");

// Incluimos el controlador comun
include ("./../../controllers/Controladores.php");
$TControlador = new ControladorComun; 
// El post tabla solo debemos utilizarlo si existe el fichero en parametros sino genera un error.
if (isset($_POST['tabla'])){
	// Incluimos y creamos objeto parametros poder tratar los ficheros Mysql.
	$tabla = $_POST['tabla'];
	include_once('parametrostablas.php');
	$NewParametrosTabla = new ClaseArrayParametrosTabla($tabla,'parametros.xml');
	$parametros = $NewParametrosTabla->getParametros();
}
 switch ($pulsado) {
     
    case 'import_inicio':
		$fichero = $_POST['Fichero'];
		$respuesta = LeerEstructuraDbf($fichero);
		echo json_encode($respuesta) ;
		break;
	case 'Comprobar-tabla':
		$nombreTabla = $_POST['Fichero'];
		$campos = $_POST['campos'];
		$tablas = $Conexiones[1]['tablas']; // En esta variable obtenemos las tablas que tiene la conexion
		$respuesta = ComprobarTabla($nombreTabla,$tablas,$BDImportDbf,$campos,$TControlador);
		echo json_encode($respuesta);
		break;
    case 'obtenerDbf':
		$numInicial = $_POST['lineaI'];
		$numFinal = $_POST['lineaF'];
		$campos = $_POST['campos']; 
		$nombreTabla = $_POST['Fichero'];	//nombre fichero con extension)
		$ruta = $_POST['ruta'];
		// Necesito la ruta completa del fichero con extension para ejecutar LeerDbf.
		$fichero = $ruta.'/'.$nombreTabla;
		$datosDbf = LeerDbf($fichero,$numFinal,$numInicial,$campos);
		// Necesito el valor nombreTabla sin extension para ejecutar InsertarDatos.
		$nombreTablaSin = substr($nombreTabla,0,-4);  //Tengo enviar en nombretabla que es fichero sin extension (.dbf )
        $respuesta = InsertarDatos($campos,$nombreTablaSin,$datosDbf,$BDImportDbf);
        echo json_encode($respuesta);
        break;
    case 'actualizar_agregar':
		$nombrestablas = $_POST['Ficheros'];
		$respuesta = ActualizarAgregarCampoEstado($nombrestablas,$BDImportDbf);
		echo json_encode($respuesta);
		break;
		
	case 'DescartarRegistro' :
		$datos = $_POST['datos'];
		// $tabla ya cargo al inicio tarea.
		$respuesta = DescartarRegistrosImportDbf($BDImportDbf,$tabla,$datos);
		echo json_encode($respuesta);
		break;
	case 'AnhadirRegistroTpv':
		$datos = $_POST['datos'];
		// -- Montamos los parametros que necesitamos para realizar -- //
		//~ $p = array();
		//~ $p['tablas'] = $NewParametrosTabla->getParametrosTabla(); // Nombres de tablas tanto importar como tpv
		
		$respuesta = AnhadirRegistroTpv($BDTpv,$BDImportDbf,$NewParametrosTabla,$datos);
		//~ $parametros =$parametros->tpv;
		//[PENDIENTE]
		// Añadir metodos en clase parametrostabla para obtener de mejor :
		// campos, cruces , funcione antess ... 
	
		echo json_encode($respuesta);
		break;
		
	case 'FamiliaAnhadirIdRegistro':
		$respuesta 	= array();
		$datos 		= $_POST['datos'];
		$idvalor 	= $_POST['idValor'];
		$respuesta = FamiliaIdInsert($BDImportDbf,$BDTpv,$datos,$idvalor);
		echo json_encode($respuesta);
		break;
		
	
	case 'grabarRegistroImportar':
		$respuesta 	= array();
		$datos 		= $_POST['datos'];
		$respuesta = grabarRegistroImportar($BDImportDbf,$datos);
		echo json_encode($respuesta);
		break;
	
	case 'AnhadirID_DbfImportar':
		$respuesta = array();
		$datos_importar = $_POST['datos_importar'];
		$datos_tpv = $_POST['datos_tpv'];
		$tabla = $_POST['tabla'];
		$respuesta = AnhadirID_DbfImportar($BDImportDbf,$datos_importar,$datos_tpv,$tabla);
		$respuesta['algo'] = 'Entro';
		echo json_encode($respuesta);

	
}
 
/* ===============  CERRAMOS CONEXIONES  ===============*/

mysqli_close($BDImportDbf);

 
 
?>
