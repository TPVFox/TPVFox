
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

// Incluimos y creamos objeto parametros para poder obtener datos.
include_once ('parametros.php');
$Newparametros = new ClaseParametros('parametros.xml');
$parametros = $Newparametros->getRoot();

// DBF71 debería ser una varible que pueda modificar el usuario
$rutaFicheroImportar = $RutaServidor.$RutaDatos.'/'.'DBF71'.'/';
 
 switch ($pulsado) {
     
    case 'import_inicio':
		$nombreTabla = $_POST['Fichero'];
		$fichero = $rutaFicheroImportar.$nombreTabla;
		$respuesta = LeerEstructuraDbf($fichero);
		echo json_encode($respuesta) ;
		break;
	case 'Comprobar-tabla':
		$nombreTabla = $_POST['Fichero'];
		$campos = $_POST['campos'];
		$conexion = $Conexiones[1]['tablas']; // En esta variable obtenemos las tablas que tiene la conexion
		$respuesta = ComprobarTabla($nombreTabla,$conexion,$BDImportDbf,$campos);
		echo json_encode($respuesta);
		break;
    case 'obtenerDbf':
		$numInicial = $_POST['lineaI'];
		$numFinal = $_POST['lineaF'];
		$campos = $_POST['campos']; 
		$nombreTabla = $_POST['Fichero'];	//nombre fichero con extension)
		// Necesito la ruta completa del fichero con extension para ejecutar LeerDbf.
		$fichero = $rutaFicheroImportar.$nombreTabla;
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
		$tabla = $_POST['tabla'];
		$respuesta = DescartarRegistrosImportDbf($BDImportDbf,$tabla,$datos);
		echo json_encode($respuesta);
		break;
	case 'AnhadirRegistroTpv':
		$datos = $_POST['datos'];
		$tabla = $_POST['tabla'];
		// Montamos array consulta con datos necesarios para enviar funcion Anhadir
		$consulta = array();
		$parametros_tabla = TpvXMLtablaImportar($parametros,$tabla);
			$consulta['tabla'] = (string)$parametros_tabla->nombre[0];
		$objConsultas = $Newparametros->setRoot($parametros_tabla);
		$consultas = $Newparametros->Xpath('consultas//consulta[@tipo="obtener"]','Valores');
			$consulta['obtener'] = $consultas;
			$consulta['parametros'] = $parametros_tabla;
			
		$respuesta = AnhadirRegistroTpv($BDTpv,$BDImportDbf,$consulta,$datos);
		echo json_encode($respuesta);
		break;
		break;
}
 
/* ===============  CERRAMOS CONEXIONES  ===============*/

mysqli_close($BDImportDbf);

 
 
?>
