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
 // action: Clave a accion.
 
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
$respuesta = curl_exec ($ch);
 
//o el error, por si falla
$error = curl_error($ch);
 
//y finalmente cerramos curl
curl_close ($ch);
// [ OBTENEMOS ARRAY DE DATOS DE TMP ARTICULOS COMPLETA ]
$respuesta = json_decode($respuesta,true);
//[ ANALIZAMOS Y MOSTRAMOS POSIBLES ERRORES ]

if (isset($respuesta['error'])){
	$error = $respuesta;
}
?>

