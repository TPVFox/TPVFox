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
    case 'Inicio':
		$fichero = $RutaServidor.$CopiaDBF.'/albprol.dbf';
        $respuesta = LeerEstructuraDbf($fichero);
        echo json_encode($respuesta) ;
        break;
    case 'obtenerDbf':
		$numInicial = $_POST['lineaI'];
		$numFinal = $_POST['lineaF'];
		//~ $pulsado = $_POST['campos'];

		
		
		$fichero = $RutaServidor.$CopiaDBF.'/albprol.dbf';
        $respuesta = LeerDbf($fichero,$numFinal,$numInicial,$campos);
        echo json_encode($respuesta) ;
        break;
}
 
/* ===============  CERRAMOS CONEXIONES  ===============*/

mysqli_close($BDImportRecambios);
mysqli_close($BDRecambios);
 
 
?>
