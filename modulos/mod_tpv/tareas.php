
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

 //recojo datos del ajax buscarProducto con parametros definidos
 //llamo a funcion php donde uso esos datos y me devuelve un array
 switch ($pulsado) {
    case 'buscarProducto':
		$busqueda = $_POST['valorCampo'];
		$campoAbuscar = $_POST['campo'];
		//cuando busco dsde el popup el estado me es indiferente
		$deDonde = $_POST['dedonde'];
		
		
		$respuesta = BuscarProducto($campoAbuscar,$busqueda,$BDImportDbf);
		// Si respuesta es incorrecta, entonces devuelvo html de respuesta
		//si dedonde == 'popup' 
		if ($respuesta['Estado'] !='Correcto' ){
			$respuesta['listado']= htmlProductos($respuesta['datos'],$campoAbuscar,$busqueda);
		}
		
		if ($respuesta['Estado'] === 'Correcto' && $deDonde === 'popup'){
			alert(' estado popup y correcto');
			$respuesta['listado']= htmlProductos($respuesta['datos'],$campoAbuscar,$busqueda);
		}
		echo json_encode($respuesta);  
		
		//en funcion utilizo assoc_fetch
		//en assoc Cuando es TRUE, los object devueltos serán convertidos a array asociativos.
		break;
	
}
 
/* ===============  CERRAMOS CONEXIONES  ===============*/

mysqli_close($BDImportDbf);

 
 
?>
