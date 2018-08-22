<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 TpvFox Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero
 * @Descripcion	Importar o exportar ficheros de web Joomla
 * */	

 // Gestion de errores
 // error = [String] No permito continuar.
 // error_warning =  Array() - Si permito continuar pero con restrinciones.

 // Datos que se necesita para la conexion:
 // ruta: http://webJoomla/rutaApi
 // key: Clave introduccida en plugin de instalacion de Joomla
 // action: Clave a accion..
 
 // Obtengo informacion de como controlar error, para evitar que rompa... en:
 // https://cybmeta.com/comprobar-en-php-si-existe-un-archivo-o-una-url
 
// Obtenemos los datos de la conexion con servidor remoto.

//Lo primerito, creamos una variable iniciando curl, pasándole la url

$ch = curl_init($ruta);
 
//especificamos el POST (tambien podemos hacer peticiones enviando datos por GET
curl_setopt ($ch, CURLOPT_POST, 1);
 
//le decimos qué paramáetros enviamos (pares nombre/valor, también acepta un array)

curl_setopt ($ch, CURLOPT_POSTFIELDS, $parametros);
 
//le decimos que queremos recoger una respuesta (si no esperas respuesta, ponlo a false)
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);


//recogemos la respuesta
$resultado = curl_exec ($ch);

 
//o el error, por si falla
$error = curl_error($ch);
//~ echo '<pre>';
//~ print_r($respuesta);
//~ echo '</pre>';
$info=curl_getinfo($ch);
//y finalmente cerramos curl
curl_close ($ch);

//[ ANALIZAMOS Y MOSTRAMOS POSIBLES ERRORES ]

// [ OBTENEMOS ARRAY DE DATOS DE TMP ARTICULOS COMPLETA ]
//~ $respuesta = json_decode($respuesta,true);
if($info['http_code']==200){
    $respuesta = json_decode($resultado,true);
} else {
    $respuesta = array();
    $respuesta['error_conexion'] = 'Respuesta http:'.$info['http_code'];
    if (isset($error)){
        if ($error !==''){
            $respuesta['error_conexion'] = $error;
        }
    }
    $respuesta['info'] =$info;
}
   //~ $respuesta = gettype($info['http_code']);
?>

