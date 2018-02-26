<?php
// Configuracion de rutas ( Sin barra final)
$HostNombre = '/ruta/tpvfox';	// ruta instalacion de proyecto
$RutaServidor= '/var/www';		// ruta servidor
$RutaDatos = '/ruta/datos'; // ruta donde guardamos de empresa, como copias,log_tpvFox(guardamos errores)


// Datos de conexion a mysql de local, donde tenemos el tpv
$servidorMysql = 'localhost';
$nombrebdMysql = ' ';
$usuarioMysql='us_tpv';
$passwordMysql='bd_tpv';

// Datos para importacion tienda Online ( Para inicializacion )
// Es necesario tener la instalación en local de la tienda on-line
$nombre_onlineBD = ''; // Nombre Base datos tienda on line -> Local
$Usuario_onlineBD = ''; // Nombre usuario de BDtienda -> Local
$pass_onlineBD = ''; // Password para tienda on line -> local
$prefijoBD = '';  // Prefijo que utiliza joomla para sus tablas
//Carpeta con los archivos temporales que se generan
$tmp='';
// Datos para importacion de DBF ( Para inicializacion )
$nombrebdMysqlImpor = ''; // Base de datos temporal que utilizamos para importar DBF
$usuarioMysqlImpor = ''; 
$passwordMysqlImpor = '';


// Otras configuraciones
$CONF_campoPeso = 'no';  //para ocultar columna peso en el tvp ( tickets) 


?>
