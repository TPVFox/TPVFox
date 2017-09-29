
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

 
 switch ($pulsado) {
     
    case 'import_inicio':
		$nombreTabla = $_POST['Fichero'];
		$fichero = $RutaServidor.$CopiaDBF.'/'.$nombreTabla;
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
		$fichero = $RutaServidor.$CopiaDBF.'/'.$nombreTabla;
		$datosDbf = LeerDbf($fichero,$numFinal,$numInicial,$campos);
		// Necesito el valor nombreTabla sin extension para ejecutar InsertarDatos.
		$nombreTablaSin = substr($nombreTabla,0,-4);  //Tengo enviar en nombretabla que es fichero sin extension (.dbf )
        $respuesta = InsertarDatos($campos,$nombreTablaSin,$datosDbf,$BDImportDbf);
        echo json_encode($respuesta);
        break;
    case 'actualizar_agregar':
		$nombrestablas = $_POST['Ficheros'];
		$respuesta = ActuaAgregarCampos($nombrestablas,$BDImportDbf);

		echo json_encode($respuesta);

		break;
}
 
/* ===============  CERRAMOS CONEXIONES  ===============*/

mysqli_close($BDImportDbf);

 
 
?>
