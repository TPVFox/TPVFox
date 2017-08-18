
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
    case 'buscarProducto':
		$campoAbuscar = 'CREF';
		$busqueda='03012'
		//~ $fichero = $RutaServidor.$CopiaDBF.'/'.$nombreTabla;
		$respuesta = BuscarProducto($campoAbuscar,$busqueda,$BDImportDbf);
		 echo json_encode($respuesta) ;
		break;
	
}
 
/* ===============  CERRAMOS CONEXIONES  ===============*/

mysqli_close($BDImportDbf);

 
 
?>
