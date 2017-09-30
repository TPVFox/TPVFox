<?php
// Configuracion de rutas
$HostNombre = '/superoliva/tpvolalla';	// ruta instalacion de proyecto
$RutaServidor= '/home/olalla/www';		// ruta servidor

// Datos de conexion a mysql de local, donde tenemos el tpv
$servidorMysql = 'localhost';
$nombrebdMysql = ' ';
$usuarioMysql='us_tpv';
$passwordMysql='bd_tpv';

// Datos para importacion tienda Online ( Para inicializacion )
// Es necesario tener la instalación en local de la tienda on-line
$nombre_onlineBD = '' // Nombre Base datos tienda on line -> Local
$Usuario_onlineBD = '' // Nombre usuario de BDtienda -> Local
$pass_onlineBD = '' // Password para tienda on line -> local
$prefijoBD = ''  // Prefijo que utiliza joomla para sus tablas

// Datos para importacion de DBF ( Para inicializacion )
$CopiaDBF = '/superoliva/datos/DBF71';	// Ruta donde tenemos los DBF, dentro ruta public de apache y con permiso para funcione
$nombrebdMysqlImpor = ''; // Base de datos temporal que utilizamos para importar DBF
$usuarioMysqlImpor = ''; 
$passwordMysqlImpor = '';


// Otras configuraciones
$CONF_campoPeso = 'no';  //para ocultar columna peso en el tvp ( tickets) 


?>
