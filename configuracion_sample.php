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


//Carpeta con los archivos temporales que se generan
$rutatmp='';
// Datos para importacion de DBF ( Para inicializacion )


// Otras configuraciones
$CONF_campoPeso = 'no';  //para ocultar columna peso en el tvp ( tickets) 


?>
