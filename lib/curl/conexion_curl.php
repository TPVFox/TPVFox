<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 TpvFox Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero
 * @Descripcion Importar o exportar ficheros de web Joomla
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
        $existe_curl =function_exists('curl_version');
        if ($existe_curl === FALSE){
            echo '<pre>';
            print_r(' No exite curl');
            echo '</pre>';
            exit();
        }

// Inicializamos variables
$ch = null;
$resultado = false;
$error = '';
$info = array();
$respuesta = array();

try {
    // Creamos una variable iniciando curl, pasándole la url
    $ch = curl_init($ruta);
    if ($ch === false) {
        throw new Exception('No se pudo inicializar cURL');
    }

    // Opciones
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);

    // especificamos el POST (tambien podemos hacer peticiones enviando datos por GET)
    curl_setopt($ch, CURLOPT_POST, 1);
     
    // le decimos qué paramáetros enviamos (pares nombre/valor, también acepta un array)
    curl_setopt($ch, CURLOPT_POSTFIELDS, $parametros);
     
    // le decimos que queremos recoger una respuesta (si no esperas respuesta, ponlo a false)
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // recogemos la respuesta
    $resultado = curl_exec($ch);

    // o el error, por si falla
    $error = curl_error($ch);
    $info = curl_getinfo($ch);

    if ($resultado === false) {
        // Error en la ejecución de cURL
        $mensaje = $error !== '' ? $error : 'Error desconocido en curl_exec';
        throw new Exception($mensaje);
    }

    // Si llegamos aquí, analizamos el código HTTP
    if (isset($info['http_code']) && $info['http_code'] == 200) {
        $respuesta = json_decode($resultado, true);
        if ($respuesta === null && json_last_error() !== JSON_ERROR_NONE) {
            // Si el JSON no es válido, devolver contenido bruto en un campo
            $respuesta = array('raw_response' => $resultado, 'json_error' => json_last_error_msg());
        }
    } else {
        // Respuesta HTTP distinta de 200
        $respuesta = array();
        $http_code = isset($info['http_code']) ? $info['http_code'] : 'desconocido';
        $respuesta['error_conexion'] = 'Respuesta http:'.$http_code;
        if ($error !== ''){
            $respuesta['error_conexion'] = $error;
        }
        $respuesta['info'] = $info;
    }

} catch (Exception $e) {
    // Capturamos excepciones y normalizamos la salida en $respuesta
    $respuesta = array();
    $respuesta['error_conexion'] = $e->getMessage();
    if (!empty($info)) {
        $respuesta['info'] = $info;
    }
    if (!empty($error)) {
        $respuesta['curl_error'] = $error;
    }
} finally {
    // y finalmente cerramos curl si se creo
    if ($ch !== null) {
        // En algunas versiones $ch puede ser un recurso u objeto; cerramos si es válido
        try {
            curl_close($ch);
        } catch (Throwable $t) {
            // No hacemos nada: cerramos de forma segura
        }
    }
}
   //~ $respuesta = gettype($info['http_code']);
?>