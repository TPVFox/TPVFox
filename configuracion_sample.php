<?php
// Configuracion de rutas ( Sin barra final)
$HostNombre = '/ruta/tpvfox';	// Ruta relativa desde servidor deinstalacion de proyecto
$RutaServidor= '/var/www';		// ruta servidor
$RutaDatos = '/ruta/datos'; // Ruta relativa desde servidor donde guardamos de empresa, como copias,log_tpvFox(guardamos errores)


// Datos de conexion a mysql de local, donde tenemos el tpv
$servidorMysql = 'localhost';
$nombrebdMysql = ' ';
$usuarioMysql='us_tpv';
$passwordMysql='bd_tpv';


//Carpeta con los archivos temporales que se generan
$rutatmp=''; // Ruta relativa desde el servidor.
$ruta_upload=''; // Ruta absoluta donde guardar los ficheros subidos validos.
$ruta_segura = ''; // Ruta absoluta donde queremos tener documentacion segura, por eso tiene que se debajo public.
// Datos para importacion de DBF ( Para inicializacion )


// Otras configuraciones
$CONF_campoPeso = 'no';  //para ocultar columna peso en el tvp ( tickets) 

$email_direccion_origen = 'sinrespuesta@tpvfox.com';
$email_usuario_origen = 'correo automático TPVFox';

$PHPMAILER_CONF = [
    'host' => 'smtp.mailtrap.io',
    'SMTPAuth' => true,
    'Port' => 2525,
    'Username' => '2f117f32afaa38',
    'Password' => 'cfeadb433cb791',
];
?>
