<?php

/* Este fichero lo utilizamos para cargar lo necesario para el funcionamiento.
 * Normalmente ya lo cargamos en head,  pero muchas veces es necesario cargarlo antes de head.
 * Incluso cuando cargamos en tareas , es necesario , asi no cargamos el head que no es necesario.
 * 
*/



// define('FORMATO_FECHA_ES', 'd-m-Y H:m:s');
// define('FORMATO_FECHA_MYSQL', 'Y-m-d H:m:s');

$Ruta = __DIR__.'/';

$error_conf = '';
	if (file_exists($Ruta.'configuracion.php')){		
		include_once ($Ruta.'configuracion.php');
		if (file_exists($RutaServidor . $HostNombre)){
			$URLCom = $RutaServidor . $HostNombre;
		} else {
            $error_conf = 'Error en ruta completa URLCom : '.$RutaServidor . $HostNombre;
        }
   	} else {
   	    $error_conf = 'No existe fichero de configuracion:'.$Ruta.'configuracion.php';
	}
    if ($error_conf !== ''){
        // hubo un error
        error_log($error_conf);
		exit();
    }	
    // include_once ($URLCom. "/clases/ClaseSession.php");
	include_once ($URLCom. "/app/helpers.php");

	// Solo creamos objeto si no existe.
	
	// $thisTpv = new ClaseSession();
	// $BDTpv = $thisTpv->getConexion(); // Para la antigua conexion. 
   
	// // [PENDIENTE ] Eliminar mod_conexion/conexionBaseDatos , falta arreglar mod_importar
	// //~ $thisTpv->comprobarEstado();

	// $Usuario= (isset($_SESSION['usuarioTpv']) ? $_SESSION['usuarioTpv'] : array('id'=>0, 'group_id'=>0,'login' =>'invitado'));
    // $ClasePermisos=$thisTpv->permisos;
	// $Tienda = (isset($_SESSION['tiendaTpv']) ? $_SESSION['tiendaTpv']: array('razonsocial'=>''));
