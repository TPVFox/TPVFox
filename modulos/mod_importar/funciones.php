<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 TpvOlalla de Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero ,
 * @Descripcion	Funciones para importar datos Dbf a Mysql
 * */

//Funcion donde se lee Dbf y se obtiene array *
function LeerDbf($fichero,$numFinal,$numInic,$campos) {
	// Parametros:
	// $numFinal y $numInic son enteros.
	// $campos es un array de los campos de la tabla.
	//  [0] 
	// 		[NombreCampo]
	// 		[tipo]
	
	// El objetivo es leer DBF
	// Metodo:
	// A traves exec , obtenemos array.
	// tratamos array $output para obtener los datos y los ponemos a nuestro gusto $resultado;
	$resultado = array();
	$output = array(); 
	$instruccion = "python ./../../lib/py/leerDbf.py 2>&1 -f ".$fichero." -i ".$numInic." -e ".$numFinal;
	
	exec($instruccion, $output,$entero);
	if ($entero === 0) {
		$resultado['Estado'] = 'Correcto';
		// pasamos array asociativo.
		$i=0;
		foreach ($output as $linea) {
			$resultado[$i] = json_decode($linea,true); // Obtenemos array con datos y campos.
			// El problema es cuando el campo es Caracter y tenemos que convertir a codepage CP1252 para español.
			foreach ($campos as $campo) {
				if ($campo['tipo'] === 'C'){
					$nombreCampo = $campo['campo'];
					$convertir = $resultado[$i][$nombreCampo];
					$convertido = htmlentities($convertir, ENT_QUOTES | ENT_IGNORE, "CP1252");
					$resultado[$i][$nombreCampo] = $convertido;
				}
			}
			$i++;
		}
	} else {
		$resultado['Estado'] = 'Errores '. $entero;
		$resultado['Errores'] = $output;
		// Recuerda que esto lo mostramos gracias a que ponemos parametro 2>&1 en exec... 
		// No permitimos continuar.
		
	}
	return $resultado;
}

function LeerEstructuraDbf($fichero) {
	// El objetivo es obtener estructura de DBF
	// Metodo:
	// A traves exec , obtenemos array.
	// tratamos array $output para obtener los datos y los ponemos a nuestro gusto $resultado;
	$resultado = array();
	$output = array(); 
	$instruccion = "python ./../../lib/py/leerEstrucDbf2.py 2>&1 -f ".$fichero;
	exec($instruccion, $output,$entero);
	if ($entero === 0) {
		$resultado['Estado'] = 'Correcto';
		// pasamos array asociativo.
		$i=0;
		foreach ($output as $linea) {
			if ($i === 0) {
				 $resultado['numeroReg'] = $linea;
			 }
			 else{
			//~ $resultado[$i] = $linea;
			$resultado[$i] = json_decode($linea,true);
			}
			$i++;
			
		}
	} else {
		$resultado['Estado'] = 'Errores '. $entero;
		$resultado['Errores'] = $output;
		// Recuerda que esto lo mostramos gracias a que ponemos parametro 2>&1 en exec... 
		// No permitimos continuar.
		
	}
	$resultado['NumCampos'] = $i;
	return $resultado;

	
	
}





?>
