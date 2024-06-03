<?php
/*
 * @version     2.1
 * @copyright   Copyright (C) 2017 TpvFox Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Alberto Lago
 * @Descripcion Conexión con Curl
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
// Primero comprobamos si existe curl en nuestro servidor.

class ConexionCurl
{

    private $ch;

    //Creamos una variable iniciando curl, pasándole la url
    public function __construct($ruta)
    {
        $this->ch = curl_init($ruta);
    }

    public static function existeCurl()
    {
        return function_exists('curl_version');
    }

// if ($existe_curl === FALSE){
//     echo '<pre>';
//     print_r(' No existe curl');
//     echo '</pre>';
//     exit();
// }

    //
    public function setTimeout($timeout = 6)
    {
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);
    }

//especificamos el POST (tambien podemos hacer peticiones enviando datos por GET
    public function setPost($method = 1)
    {
        curl_setopt($ch, CURLOPT_POST, $method);
    }

//le decimos qué paramáetros enviamos (pares nombre/valor, también acepta un array)
    public function setParametros($parametros)
    {
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $parametros);
    }

//le decimos que queremos recoger una respuesta (si no esperas respuesta, ponlo a false)
    public function setRespuesta($queremosRespuesta = true)
    {
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, $queremosRespuesta);
    }

//recogemos la respuesta
    public function execute()
    {
        return curl_exec($this->ch);
    }

    //o el error, por si falla
    public function getError()
    {
        return curl_error($this->ch);
    }

    public function getInfo()
    {
        return curl_getinfo($this->ch);
    }

    //y finalmente cerramos curl
    public function close()
    {
        curl_close($this->ch);
    }
}

// //[ ANALIZAMOS Y MOSTRAMOS POSIBLES ERRORES ]

// $respuesta = execute()   ???
// // [ OBTENEMOS ARRAY DE DATOS DE TMP ARTICULOS COMPLETA ]
// //~ $respuesta = json_decode($respuesta,true);
// if($info['http_code']==200){
//     $respuesta = json_decode($resultado,true);
// } else {
//     $respuesta = array();
//     $respuesta['error_conexion'] = 'Respuesta http:'.$info['http_code'];
//     if (isset($error)){
//         if ($error !==''){
//             $respuesta['error_conexion'] = $error;
//         }
//     }
//     $respuesta['info'] =$info;
// }
//    //~ $respuesta = gettype($info['http_code']);


