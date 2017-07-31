<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 TpvOlalla de Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero ,
 * @Descripcion	Funciones para importar datos Dbf a Mysql
 * */

//Funcion donde se lee Dbf y se obtiene array *
//~ ,$numFinal,$numInic,$campos 

function LeerDbf($fichero,$numFinal,$numInic,$campos) {
//~ function LeerDbf($fichero) {

	// Parametros:
	// $numFinal y $numInic son enteros.
	// $campos es un array de los campos de la tabla.
	//  [0] 
	// 		[NombreCampo]
	// 		[tipo]
	//		[longitud]
	//		[decimal]	
		
	// El objetivo es leer DBF
	// Metodo:
	// A traves exec , obtenemos array.
	// tratamos array $output para obtener los datos y los ponemos a nuestro gusto $resultado;
	$resultado = array();
	$output = array(); 
	$instruccion = "python ./../../lib/py/leerDbf1.py 2>&1 -f ".$fichero." -i ".$numInic." -e ".$numFinal;
	
	//enviar al py limI, limF
	//~ $instruccion = "python ./../../lib/py/leerDbf1.py 2>&1 -f ".$fichero;
	exec($instruccion, $output,$entero);
	//~ print('func php leerDbf LIB py '.$instruccion.'<br/>instruccion python num final '.$numFinal.'  num Inicial '.$numInicial);

	
	if ($entero === 0) {
		//~ $resultado['campos'] = $campos;
		$resultado['Estado'] = 'Correcto';
		// pasamos array asociativo.
		$i=0;
		foreach ($output as $linea) {
			$resultado[$i] = json_decode($linea,true); // Obtenemos array con datos y campos.
			// El problema es cuando el campo es Caracter y tenemos que convertir a codepage CP1252 para español.
			//~ foreach ($campos as $campo) {
				//~ if ($campo['tipo'] === 'C'){
					//~ $nombreCampo = $campo['campo'];
					//~ $convertir = $resultado[$i][$nombreCampo];
					//~ $convertido = htmlentities($convertir, ENT_QUOTES | ENT_IGNORE, "CP1252");
					//~ $resultado[$i][$nombreCampo] = $convertido;
				//~ }
			//~ }
			$i++;
		}
	} else {
		$resultado['Estado'] = 'Error-obtener ';
		$resultado['Errores'] = $output;
		// Recuerda que esto lo mostramos gracias a que ponemos parametro 2>&1 en exec... 
		// No permitimos continuar.
		//nos imprime en pantalla (tabla) el error		
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
		$resultado['NumCampos'] = $i-1;
	} else {
		$resultado['Estado'] = 'Errores ';
		$resultado['Errores'] = $output;
		// Recuerda que esto lo mostramos gracias a que ponemos parametro 2>&1 en exec... 
		// No permitimos continuar.
		
	}
	return $resultado;
}
//
function ComprobarTabla($nombreTabla,$conexion,$BDImportDbf,$campos) {
	// Lo que hacemos es comprobar que los $nombrestablas 
	// $campos es un array de los campos de la tabla.
	//  [0] 
	// 		[campo]
	// 		[tipo]
	//		[longitud]
	//		[decimal]	
	$resultado = array();
	$resultado['Estado']='Incorrecto';
	$resultado['Tabla']='No existe';
	$accion = '';
	$i=0;	
	$resp_crear='no';
		
	foreach ($conexion as $tabla){
		if ($nombreTabla === $tabla) {
			$resultado['Estado']='Correcto';
			$resultado['Tabla']='Existe';
			//consulto estructura y luego comparo
			
			$sqlShow = 'SHOW COLUMNS FROM '.$nombreTabla;
			$respuesta= $BDImportDbf->query($sqlShow);
			//cogiendo la estructura de la bbdd de tal tabla
			if (isset ($respuesta)){
				$arr = array();
				$i=0;
				while ($fila = $respuesta->fetch_row()) {
					$nombreCampo = $fila[0];
					$tipo = $fila[1];
					$arr[$i]= $nombreCampo.' '.$tipo;
					$i++;
				}
				//monto la estructura de la tabla de la bbdd
				$strEstruct= implode(",",$arr);	
				
				//muestra los campos de la tabla creada en mysql 
				$resultado['debug_campos']=$strEstruct;
				
				//muestra los campos del dbf a importar , este es un OBJECTO
				$res_dbf=RecogerCampos($nombreTabla, $campos);
				$resultado['debug_dbf']=$res_dbf;
				
				//comparamos que la estructura de la bbdd sea igual que la estructura del dbf que intentamos importar
				//si es igual importamos los datos del dbf
				//si no es igual se borra tabla y se crea de nuevo.
				 if ($strEstruct != $res_dbf){
					$resultado['Estado'] = 'Incorrecto';
					$sql = 'DROP TABLE '.$nombreTabla;
					$respuesta= $BDImportDbf->query($sql);
					$accion = 'Borramos tabla';
				 }
			}
			
			break;
		}	
	} 
	//si el estado es incorrecto se crea tabla
	//si no hay tablas me crea la ESTRUCTURA de la primera tabla
	//if (count($conexion) === 0){
	
	if ($resultado['Estado'] === 'Incorrecto'){
		$res_dbf=RecogerCampos($nombreTabla, $campos);
		//no existe tablas y la creamos la tabla con la estructura del dbf (res_dbf))
 		$resp_crear=CrearTabla($nombreTabla, $res_dbf,$BDImportDbf);
 		$accion = $accion+'Creada estructura tabla.';
	}	
	
	
	// estas son las respuestas que iran al final de la funcion con los result que quiero mostrar..
	//estado,diferencia, nombre tabla.. al inspeccionar pag.
	//por eso creo variable en el resto de funcion para mostrarlas despues aqui..	
	//estado correcto o incorrecto y
	//acciones lo que vamos haciendo.. 

	
	$resultado['accion'] = $accion;
	
	
	return $resultado;
}
//funcion para crear estructura de tabla segun nombreTabla
function RecogerCampos ($nombreTabla, $campos){
		
		$strCampos = array();
		$i=0;
		$resultado = array();
		foreach ($campos as $campo){
			if (isset($campo['campo'])){
				$tipo = '';
				switch ($campo['tipo']){
					  case 'C':
						$tipo = 'varchar('.$campo[longitud].')';
					  break;
					  case 'N':
						$tipo = 'decimal('.$campo[longitud].','.$campo[decimal].')';
					  break;
					  case 'D':
						$tipo = 'date';
					  break;
					  case 'L':
						$tipo = 'tinyint(1)';
					  break;
					}
				
			//$strCampos[$i]= $campo['campo'].$campo['tipo'].$campo['longitud'].$campo['decimal'];
			$strCampos[$i]= $campo['campo'].' '.$tipo;
			$i++;
			}
		}
		
		//implode (",",$v); une los datos separandolos en comas en un array.
		$strSql=implode(",",$strCampos);
		

		
		$resultado = $strSql;
		
		return $resultado;
	
}

function CrearTabla ($nombreTabla,$strSql,$BDImportDbf){
		
		$sql = 'CREATE TABLE '.$nombreTabla.' ('.$strSql.')';
		$resp_crear= $BDImportDbf->query($sql);
		//implode y mysql
		$resultado = $resp_crear;
		return $resultado;
}

?>
