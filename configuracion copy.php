<?php
// Configuracion de rutas ( Sin barra final)
$HostNombre = '';	// ruta instalacion de proyecto
$RutaServidor= '/var/www/tpvfox';		// ruta servidor
$RutaDatos = '/datostpvfox'; // ruta donde guardamos de empresa, como copias,log_tpvFox(guardamos errores)


// Datos de conexion a mysql de local, donde tenemos el tpv
$servidorMysql = 'localhost';
$nombrebdMysql = 'tpvfox';
$usuarioMysql='tpvfox';
$passwordMysql='tpvfoxpass';


//Carpeta con los archivos temporales que se generan
$rutatmp='/../datostpvfox/tmp';
// Datos para importacion de DBF ( Para inicializacion )


// Otras configuraciones
$CONF_campoPeso = 'no';  //para ocultar columna peso en el tvp ( tickets) 

$PHPMAILER_CONF = [
    'host' => 'smtp.mailtrap.io',
    'SMTPAuth' => true,
    'Port' => 2525,
    'Username' => '2f117f32afaa38',
    'Password' => 'cfeadb433cb791',
];

?>
