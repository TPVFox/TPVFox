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
	// Parametros:
	// $numFinal y $numInic son enteros.
	// $campos es un array de los campos de la tabla.
	//  [0] 
	//		[NombreCampo]
	//		[tipo]
	//		[longitud]
	//		[decimal]	

	// El objetivo es leer DBF
	// Metodo:
	// A traves exec , obtenemos array.
	// tratamos array $output para obtener los datos y los ponemos a nuestro gusto $resultado;
	$resultado = array();
	$output = array(); 
	$instruccion = "python ./../../lib/py/leerDbf1.py 2>&1 -f ".$fichero." -i ".$numInic." -e ".$numFinal;
	// $instruccion = "python ./../../lib/py/leerDbf1.py 2>/dev/null -f ".$fichero." -i ".$numInic." -e ".$numFinal;

	//enviar al py limI, limF
	//~ $instruccion = "python ./../../lib/py/leerDbf1.py 2>&1 -f ".$fichero;
	exec($instruccion, $output,$entero);
	//~ print('func php leerDbf LIB py '.$instruccion.'<br/>instruccion python num final '.$numFinal.'  num Inicial '.$numInicial);

    //~ echo $output;
	if ($entero === 0) {
		//$resultado['campos'] = $campos;
		$resultado['Estado'] = 'Correcto';
		// pasamos array asociativo.
		$i = 0;
		foreach ($output as $linea) {
			$resultado[$i] = json_decode($linea,true); // Obtenemos array con datos y campos.
			$i++;
		}
	} else {
		$resultado['Estado'] = 'Error-obtener ';
		$resultado['Errores'] = $output;
		// Recuerda que esto lo mostramos gracias a que ponemos parametro 2>&1 en exec... 
		// No permitimos continuar.
		// nos imprime en pantalla (tabla) el error
	}
	return $resultado;
}

function LeerEstructuraDbf($fichero) {
	// El objetivo es obtener estructura de DBF
	// Metodo:
	// A traves exec , obtenemos array.
	// tratamos array $output para obtener los datos y los ponemos a nuestro gusto $resultado;

	$instruccion = "python ../../lib/py/leerEstrucDbf2.py 2>&1 -f ".$fichero;

	$resultado = array();
	$output = array(); 

	$resultado['Estado'] = 'Errores ';

	// Recuerda que esto lo mostramos gracias a que ponemos parametro 2>&1 en exec... 
	// No permitimos continuar.
	exec($instruccion, $output, $entero);
	$resultado['Errores'] = $output;

	if ($entero === 0) {
		$resultado['Estado'] = 'Correcto';
		// pasamos array asociativo.
		$i = 0;
		foreach ($output as $linea) {
			if ($i === 0) {
				 $resultado['numeroReg'] = $linea;
			 } else{
				$resultado[$i] = json_decode($linea,true);
			}

			$i++;
		}

		$resultado['NumCampos'] = $i-1;
		unset($resultado['Errores']);
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
	$resultado['Estado'] = 'Incorrecto';
	$resultado['Tabla'] = 'No existe';
	$resultado['accion-borrado'] = '';
	$resultado['accion-creado'] = '';
	$resultado['dropear-tabla'] = false;

	$i = 0;	
	$resp_crear = 'no';

	foreach ($conexion as $tabla){
		if ($nombreTabla === $tabla) {
			$resultado['Tabla'] = 'Existe';
			//consulto estructura y luego comparo
			/** inicio comparacion de campos de la tabla de la bbdd y dbf, 
			 ** 1º recojo estructura de tabla bbdd
			***/
			$sqlShow = 'SHOW COLUMNS FROM '.$nombreTabla;
			$respuesta = $BDImportDbf->query($sqlShow);
			//cogiendo la estructura de la bbdd de tal tabla
			if (! isset ($respuesta)){
				$resultado['dropear-tabla'] = true;
				$resultado['accion-borrado'] = 'Borramos tabla';
				break;
			}


			$arr = array();
			$i = 0;
			while ($fila = $respuesta->fetch_row()) {
				$nombreCampo = $fila[0];
				$tipo = $fila[1];
				$arr[$i] = $nombreCampo.' '.$tipo;
				$i++;
			}
			//monto la estructura de la tabla de la bbdd
			$strEstruct = implode(",",$arr);
			//muestra los campos de la tabla creada en mysql 
			$resultado['debug_campos'] = $strEstruct;

			//muestra los campos del dbf a importar , este es un OBJECTO
			$res_dbf = RecogerCampos($nombreTabla, $campos);
			//$resultado['debug_dbf'] = $res_dbf;
			/** Fin recogida estructura tabla bbdd y dbf para comparar despues **/

			//comparamos que la estructura de la bbdd sea igual que la estructura del dbf que intentamos importar
			//si es igual importamos los datos del dbf
			//si no es igual se borra tabla y se crea de nuevo.
			 if ($strEstruct != $res_dbf){
				$resultado['dropear-tabla'] = true;
				$resultado['accion-borrado'] = 'Borramos tabla';
				break;
			 }

			$resultado['Estado'] = 'Correcto';
		}
	} 
	//si el estado es incorrecto se crea tabla
	//si no hay tablas me crea la ESTRUCTURA de la primera tabla
	//if (count($conexion) === 0){
	
	if ($resultado['Estado'] === 'Incorrecto'){
		$res_dbf = RecogerCampos($nombreTabla, $campos);
		//no existe tablas y la creamos la tabla con la estructura del dbf (res_dbf))
 		$resp_crear = CrearTabla($nombreTabla, $res_dbf,$BDImportDbf, $resultado['dropear-tabla']);
 		$resultado['Estado'] = 'Correcto';
 		$resultado['accion-creado'] = 'Creada estructura tabla';
	}

	// estas son las respuestas que iran al final de la funcion con los result que quiero mostrar..
	//estado,diferencia, nombre tabla.. al inspeccionar pag.
	//por eso creo variable en el resto de funcion para mostrarlas despues aqui..	
	//estado correcto o incorrecto y
	//acciones lo que vamos haciendo.. 
	//$resultado['accion'] = $accion;

	//vaciar tabla comprobar si no da error al vaciar cuando no hay datos y checkear en pantalla
	//necesitamos poner un checkbox en cada tabla para elegir cual queremos vaciar! 
	// si recojo q esta checkado vaciar datos 
	
	//if ($vaciar === 'checked' ){
		$sql = 'TRUNCATE TABLE '.$nombreTabla;
		$resp_del = $BDImportDbf->query($sql);
		$resultado['accion-deleteDatos'] = 'Datos borrados';
	//}
	return $resultado;
}
//funcion para recoge estructura de tabla segun nombreTabla y lo monta en un string formato array separado por comas
function RecogerCampos ($nombreTabla, $campos){
	$strCampos = array();
	$i = 0;
	$resultado = array();
	foreach ($campos as $campo){
		if (isset($campo['campo'])){
			$tipo = '';
			switch ($campo['tipo']){
				case 'C':
					$tipo = 'varchar('.$campo['longitud'].')';
					break;
				case 'N':
					$tipo = 'decimal('.$campo['longitud'].','.$campo['decimal'].')';
					break;
				case 'D':
					$tipo = 'date';
					break;
				case 'L':
					$tipo = 'tinyint(1)';
					break;
			}

			//$strCampos[$i] = $campo['campo'].$campo['tipo'].$campo['longitud'].$campo['decimal'];
			$strCampos[$i] = $campo['campo'].' '.$tipo;
			$i++;
		}
	}

	//implode (",",$v); une los datos separandolos en comas en un array.
	$strSql = implode(",",$strCampos);

	$resultado = $strSql;

	return $resultado;
}
//se le pasan:
//nombre de la tabla
//strSql , estructura de la tabla dbf a importar seria array pero lo ponemos en formato string 
//BDImportarDbf para poder conectarnos
//drop por defecto siempre borrara la tabla, para crearla desde 0. si le mandamos false No la borrara.
function CrearTabla ($nombreTabla,$strSql,$BDImportDbf, $drop=true){
	if ($drop) {
		$sql = 'DROP TABLE IF EXISTS '.$nombreTabla;
		$resp_crear= $BDImportDbf->query($sql);
	}

	$sql = 'CREATE TABLE '.$nombreTabla.' ('.$strSql.')';
	$resp_crear = $BDImportDbf->query($sql);
	//implode y mysql
	$resultado = $resp_crear;
	return $resultado;
}


function InsertarDatos($campos,$nombretabla,$datos,$BDImportDbf){
	// Obtenemos nombres de campos en array estructura 
	// $NombresCampo Ejemplo:
	$resultado = array();
	$resultado['Errores'] = array();
	$resultado['Estado'] = '';
	$NombresCampo = array_column($campos, 'campo');
	// Preparamos array para con datos
	// Quitamos estado... evitamos problemas de nulls en array a la hora de querer insertar
	unset($datos['Estado']);
	$resultado['numCampo'] = count($NombresCampo);
	$i=0;
	foreach ($datos as $dato){
		$ValoresDato = array_values($dato);	//coges datos de la tabla
		if (count($ValoresDato) != count($NombresCampo)){
			array_push($resultado['Errores'], $dato); //array_push inserta elementos al array
			$resultado['numColError'] = count($dato);
			continue; 
		}
		$SqlDato[$i] = '('; 	//montamos el sql para insertar luego
		$contadorCampos = 0;
		foreach ($ValoresDato as $valor){
			 $contadorCampos++;
			 //addslashes -> Escapa un string con barras invertidas. 
			 //Para evitar problema con 1'5kg el \', a la hora de insertar datos en mysql. añadimos \ para leer '
			 $SqlDato[$i] .= "'" . addslashes($valor) . "'"; 
			if ($contadorCampos<count($ValoresDato)){
				 $SqlDato[$i] .= ',';
			}
		}
		$SqlDato[$i] .= ')';
		$i++;  
	}
	
	// preparamos sentencia insert
	$SqlNCampos= implode(',',$NombresCampo);



	
	//~ $resultado['sql'] = $SqlNCampos;
	$SqlInsert = implode(',' ,$SqlDato);
	$consulta1 = 'INSERT INTO '.$nombretabla.' ('.$SqlNCampos.') VALUES '.$SqlInsert;
	$resp_insertar = $BDImportDbf->query($consulta1);
	if (count($resultado['Errores']) > 0 ){
		$resultado['Estado'] = 'Incorrecto';
	} else {
		//comprobar si el insert es correcto, la resp_insert
		$resultado['Estado'] = 'Correcto';
	}
	 $resultado['numErrores'] = count($resultado['Errores']);
	//~ $resultado['datosAinsertar'] = $SqlDato;
	//$resultado['sqlINsertar'] = $SqlInsert;
	//~ $resultado['valores'] = $datos;
	// Ejecutamos sentencia insert
	//$resultado['inserta'] = $consulta1;
	//$resultado = $resp_insertar;
	
	return $resultado;
}


?>
