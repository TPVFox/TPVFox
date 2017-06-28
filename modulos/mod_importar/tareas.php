
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
		$nombreTabla = $_POST['Fichero'];
		$fichero = $RutaServidor.$CopiaDBF.'/'.$nombreTabla;
        $respuesta = LeerEstructuraDbf($fichero);
	    echo json_encode($respuesta) ;
        break;
    case 'obtenerDbf':
		$numInicial = $_POST['lineaI'];
		$numFinal = $_POST['lineaF'];
		$campos = $_POST['campos'];
		$nombreTabla = $_POST['Fichero'];
		$fichero = $RutaServidor.$CopiaDBF.'/'.$nombreTabla;
        $respuesta = LeerDbf($fichero,$numFinal,$numInicial,$campos);
        //ejecutar func 

        echo json_encode($respuesta) ;
        break;
}
 
/* ===============  CERRAMOS CONEXIONES  ===============*/

mysqli_close($BDImportRecambios);
mysqli_close($BDRecambios);
 
 
?>
