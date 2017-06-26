<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 TpvOlalla de Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero ,
 * @Descripcion	Funciones para importar datos Dbf a Mysql
 * */

//Funcion donde se lee Dbf y se obtine array *
function LeerDbf($fichero,$numFinal,$numInic) {
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
			$resultado[$i] = json_decode($linea,true);
			// Para solucionar tema codificacion pagina de descripcion, lo hacermos 
			$convertir = $resultado[$i]['detalles'];
			$convertido = htmlentities($convertir, ENT_QUOTES | ENT_IGNORE, "CP1252");
			$resultado[$i]['detalles'] = $convertido;
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
	return $resultado;

	
	
}





?>
