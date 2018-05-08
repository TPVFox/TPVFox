<?php
/* Este fichero lo utilizamos para cargar lo necesario para el funcionamiento.
 * Normalmente ya lo cargamos en head,  pero muchas veces es necesario cargarlo antes de head.
 * Incluso cuando cargamos en tareas , es necesario , asi no cargamos el head que no es necesario.
 * 
*/



define('FORMATO_FECHA_ES', 'd-m-Y H:m:s');
define('FORMATO_FECHA_MYSQL', 'Y-m-d H:m:s');

	// __DIR__  // Sabemos el directorio donde esta fichero HEAD
	// $_SERVER['DOCUMENT_ROOT']; // Sabemos donde esta el servidor.
	// $RutaServidor = str_replace($_SERVER['DOCUMENT_ROOT'],'', __DIR__);
	$Ruta = __DIR__.'/';
	
	if (file_exists($Ruta.'configuracion.php')){
		include_once ($Ruta.'configuracion.php');
		if (file_exists($RutaServidor . $HostNombre)){
			$URLCom = $RutaServidor . $HostNombre;
		}
   	}
   	if (!isset($URLCom)) {
		echo '<pre>';
			print_r('No se encuentra o esta mal el fichero de configuracion.php');
		echo '</pre>';
		exit();
		
	}
	
	//~ include_once ($URLCom."/modulos/mod_conexion/conexionBaseDatos.php");
	//incluyo ruta del controlador de sesion (funcion php)
    //~ include_once ($URLCom. "/clases/ComprobarSession.php");
    include_once ($URLCom. "/clases/ClaseSession.php");

	// Solo creamos objeto si no existe.
	//~ $thisTpv = new ComprobarSession;
	$thisTpv = new ClaseSession();
	$BDTpv = $thisTpv->getConexion(); // Para la antigua conexion. Eliminar include mod_conexion/conexionBaseDatos
	//~ $TPVsession= $thisTpv->comprobarEstado($BDTpv, $URLCom);
	$thisTpv->comprobarEstado();

