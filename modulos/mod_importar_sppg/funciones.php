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
	exec($instruccion, $output,$entero);
	// Recuerda que $output es un array de todas las lineas obtenidad en .py
	// tambien recuerad que si el $entero es distinto de 0 , es que hubo un error en la respuesta de  .py
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
	// Lo que hacemos es comprobar que las tablas ( $nombrestablas ) existene DBFImportar y 
	// ademas si la estructura es la correcta.
	// Estructura de campos. 
	//$campos es un array de los campos de la tabla.
	//  [0] 
	// 		[campo]
	// 		[tipo]
	//		[longitud]
	//		[decimal]	
	// Devolvemos resultado : 
	//  [Estaod] 
	//  [accion-xxxxx] ->  borrado(vaciar),creado, eliminar tabla.

	$resultado = array();
	$resultado['Estado'] = 'Incorrecto';
	$resultado['Tabla'] = 'No existe';
	$resultado['accion-borrado'] = '';
	$resultado['accion-creado'] = '';
	$resultado['dropear-tabla'] = false;
	//Obtengo los muestra los campos de la tabla en dbf a importar , este es un OBJECTO
	$Estructura_dbf = RecogerCampos($nombreTabla, $campos);

	$i = 0;	
	$resp_crear = 'no';
	// Inicio comparacion de campos de la tabla de la bbdd y dbf, 
	foreach ($conexion as $tabla){
		if ($nombreTabla === $tabla) {
			$resultado['Tabla'] = 'Existe';
			// 1º Obtengo estructura  de  la tabla de BDImportar
			$arr = ObtenerEstructuraTablaMysq($BDImportDbf,$nombreTabla);
			if (isset($arr['dropear-tabla'])){
				// Si NO existe o sale mal la consulta
				$resultado['dropear-tabla'] = true;
				$resultado['accion-borrado'] = 'Borramos tabla';
				break;
			}
			$strEstruct = implode(",",$arr);
			// Despues de montar la estructura en un array tambien lo muestro para debug.
			$resultado['debug_campos'] = $strEstruct;
			//comparamos que la estructura de la bbdd sea igual que la estructura del dbf que intentamos importar
			//si es igual importamos los datos del dbf
			//si no es igual se borra tabla y se crea de nuevo.
			 if ($strEstruct != $Estructura_dbf){
				$resultado['dropear-tabla'] = true;
				$resultado['accion-borrado'] = 'Borramos tabla';
				break;
			 }

			$resultado['Estado'] = 'Correcto';
		}
	} 
	//Aqui ya tenemos el Estado como correcto o incorrecto
	//Si el estado es incorrecto me crea la ESTRUCTURA de tabla de dbf
	if ($resultado['Estado'] === 'Incorrecto'){
		//no existe tablas y la creamos la tabla con la estructura del dbf (res_dbf))
 		$resp_crear = CrearTabla($nombreTabla, $Estructura_dbf,$BDImportDbf, $resultado['dropear-tabla']);
 		$resultado['Estado'] = 'Correcto';
 		$resultado['accion-creado'] = 'Creada estructura tabla';
	} 
	return $resultado;
}
//funcion para recoge estructura de tabla segun nombreTabla y lo monta en un string formato array separado por comas
function RecogerCampos ($nombreTabla, $campos){
	// Esta funcion la utilizamos en:
	// ComprobarTabla(); 
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

function CrearTabla ($nombreTabla,$strSql,$BDImportDbf, $drop=true){
	// @parametros
	// $nombretabla -> nombre de la tabla queremos crear.
	// $strSql -> Estructura de la tabla en string
	// $BDImportarDbf -> Conexion 
	// $drop -> por defectro es true, si queremos no eliminarla entonces enviamos false
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
function ObtenerEstructuraTablaMysq($BDImportDbf,$nombreTabla,$string ='si'){
	// @Objetivo : Obtener array con los campos de la tabla.
	// Obtenemos array con los campos de la tabla.
	$resultado = array();
	$sqlShow = 'SHOW COLUMNS FROM '.$nombreTabla;
	if ($res=$BDImportDbf->query($sqlShow)) {
		$respuesta =  $res->fetch_row() ;
		if (! isset ($respuesta)){
			// Si NO existe o no sale mal la consulta borramos tabla
			$resultado['dropear-tabla'] = true;
		} else {
			$i = 0;
			// Recorro respuesta y monto array de campos .
			while ($fila = $res->fetch_row()) {
				if ($string ==='si'){
					$nombreCampo = $fila[0];
					$tipo = $fila[1];
					$resultado[$i] = $nombreCampo.' '.$tipo;
				} else {
					$resultado[$i] = $fila[0];
				}
				$i++;
			}
		}
	}
	return $resultado;
	
}



function ActualizarAgregarCampoEstado($nombrestablas,$BDImportDbf){
	// Objetivo:
	// Agregar el campo estado a al BDImportar, para saber que productos añadimos, modificamos o descartamos.
	// Parametro:
	//  $nombretablas ( Array con los nombres de la tabla)
	$resultado = array();
		// Ahora deberíamos preparar DBImport para actualizar.
	foreach ( $nombrestablas as $nombretabla){
		$sql = 'ALTER TABLE '.$nombretabla.' ADD `estado` VARCHAR(11)';
		$BDImportDbf->query($sql);
		// Ahora ponemos la fila de id de primera.
		$sql ='ALTER TABLE '.$nombretabla.' ADD `id` INT';
		$BDImportDbf->query($sql);
		if ($mysqli->errno){
			$resultado[$nombretabla]['estado'] ='Error';
			$resultado[$nombretabla]['error'] =$mysqli->errno; 
		} else {
			$resultado[$nombretabla]['estado'] ='Correcto';
		}
	}
	return $resultado ;
	
}


function obtenerUnRegistro($BD,$consulta) {
		/* Objetivo:
		 * Crear una consulta que obtenga todos los campos de la tabla filtrado.
		 * */
		// Funcion para contar registros de una tabla.
		$array = array();
		$resultadoConsulta = $BD->query($consulta);
		if ($BD->query($consulta)) {
			$array['NItems'] = $resultadoConsulta->num_rows;
			if ($array['NItems'] === 1){
				// Hubo resultados
				while ($fila = $resultadoConsulta->fetch_assoc()){
					$array['Items'][] = $fila;
				}
			} 
		} else {
			// Quiere decir que hubo error en la consulta.
			$array['consulta'] = $consulta;
			$array['error'] = $BD->error;
		}
		
		//~ $array['sql']=$consulta;
		return $array;
	}
	
function VariosRegistros($BD,$consulta) {
		/* Objetivo:
		 * Crear una consulta que obtenga todos los campos de la tabla filtrado.
		 * */
		// Funcion para contar registros de una tabla.
		$array = array();
		$array['NItems'] = 0 ; // valor por defecto.	
		$resultadoConsulta = $BD->query($consulta);
		if ($BD->query($consulta)) {
			$array['NItems'] = $resultadoConsulta->num_rows;
			if ($array['NItems'] > 0){
				// Hubo resultados
				while ($fila = $resultadoConsulta->fetch_assoc()){
					$array['Items'][] = $fila;
				}
			}

		} else {
			// Quiere decir que hubo error en la consulta.
			$array['consulta'] = $consulta;
			$array['error'] = $BD->error;
		}
		
		//~ $array['sql']=$consulta;
		return $array;
	}
	
	
	
	
function BuscarIgualSimilar($BDTpv,$tabla,$campos,$registro){
	// Objetivo devolver comprobacion si existe igual o similares.
	$respuesta = array();
	foreach ($campos as $key=>$datos){
			$campo = $key;
			// Ahora recorremos las acciones queremos que haga - si hay claro.
		if (isset($datos['acciones_buscar'])){
			foreach ($datos['acciones_buscar'] as $num_accion=>$accion){
				if ($accion['funcion'] === 'mismo'){
					//Buscamos si es mismo registro.
					if (isset($registro[$campo]) && trim($registro[$campo]) !== '' ){
						$whereC =' WHERE '.$accion['campo_cruce'].'="'.$registro[$campo].'"';
						$tabla = $accion['tabla_cruce'];
						$consulta = "SELECT * FROM ". $tabla.' '.$whereC;
						$UnRegistro = obtenerUnRegistro($BDTpv,$consulta);
						// Ahora registramos lo que hicimos.
						// Montamos Accion para saber resultado ->CAMPO+Num_Accion+funcion+Descripcion
						$respuesta['comprobacion'][$campo][$num_accion]['accion'] =$accion['funcion'].' -> '.$accion['description'];
						$respuesta['comprobacion'][$campo][$num_accion]['respuesta'] = $UnRegistro;
						// Registramos resultado si hay item
						if ($UnRegistro['NItems'] === 1){
							$respuesta['comprobacion']['encontrado_tipo'] = 'Mismo';
							$respuesta['tpv'] = $UnRegistro;
							// Salimos bucles de campo_acciones y campos, no tiene sentido seguir realizando acciones, ya encontro
							// uno  igual.
							break 2;
						} 
						
						
					}
				} 
				//Si encontro alguno ya no llega aquí
				if ($accion['funcion']=== 'comparar'){
					// Ejecutamos funcion de comparar
					if(isset($registro[$campo])){
						$tabla = $accion['tabla_cruce'];
						$palabras = explode(' ',trim($registro[$campo]));
						$likes = array();
						foreach($palabras as $palabra){
							if (trim($palabra) !== '' && strlen(trim($palabra))>3){
								$likes[] =  $accion['campo_cruce'].' LIKE "%'.$palabra.'%" ';
							}
						}
						$busqueda = implode(' and ',$likes);
						$whereC =' WHERE '.$busqueda;
						//~ echo '<br/>'.$item.'----> '.$whereC.'<br/>';
						$consulta = "SELECT * FROM ". $tabla.' '.$whereC;
						$Registros= VariosRegistros($BDTpv,$consulta);
						if ($Registros['NItems'] >0){
							$respuesta['tpv'] = $Registros;
							$respuesta['comprobacion']['encontrado_tipo'] = 'Similar';

						} 
						// Ahora registramos lo que hicimos
						// Montamos Accion para saber resultado ->CAMPO+Num_Accion+funcion+Descripcion
						$respuesta['comprobacion'][$campo][$num_accion]['accion'] = $accion['funcion'].' -> '.$accion['description'];
						$respuesta['comprobacion'][$campo][$num_accion]['control'] = 'Entro';
						$respuesta['comprobacion'][$campo][$num_accion]['respuesta'] = $Registros;
						
					}
				}
			}
		}
	}
	// Si no encontro igual o similar
	if (!isset($respuesta['comprobacion']['encontrado_tipo'])){
			// Quiere decir que no encontro ninguno igual o similar
			$respuesta['comprobacion']['encontrado_tipo'] = 'No existe mismo-Ni similar';
	}
	return $respuesta;
}


function DescartarRegistrosImportDbf($BDImportDbf,$tabla,$datos){
	// @ Objetivo:
	// Cambiar el estado de los registros que nos indica datos a DESCARTADOS.
	$respuesta = array();
	$wheres = array ();
	foreach ($datos as $dato){
		foreach ($dato as $nombre_campo => $valor){
			if (strlen($valor) >0 ){
				$wheres[] = '('.$nombre_campo.'="'.$valor.'")';
			}
		}
	}
	$Sql ='UPDATE '.$tabla.' SET estado="Descartado" WHERE '.implode(' AND ',$wheres);
	if (($BDImportDbf->query($Sql)) === true){
		$respuesta['estado'] = 'Correcto';
	} else {
		$respuesta['estado'] = 'Incorrecto';
		$respuesta['Sql'] = $Sql;
	}
	return $respuesta;
}


function AnhadirRegistroTpv($BDTpv,$BDImportDbf,$parametros_tabla,$datos){
	// @ Objetivo 
	// Añadir registro de las tablas importar a tpv
	// Segun la tabla que recibimos serán funciones independientes
	// @ Parametros.
	// $tabla : String con nombre tabla.
	// $datos: Array (	importar => array( campos unicos...)
	//					tpv => array ( campos unicos... )
	$respuesta = array();
	$tabla = $parametros_tabla['tabla'];
	// --- Obtenemos los datos del registro de la Base de Datos BDimport --- //
	$wheres = array();
	foreach ($datos as $dato){
		foreach ($dato as $campo => $valor){
			$wheres[]= $campo.' = "'.$valor.'"';
		}
	}
	$whereImportar = 'WHERE '.implode(' AND ',$wheres);
	// Obtenemos las consulta->obtener de parametros
	// Ahora interpreto que hay una sola, pero es un array que puede contener varias consultas. 
	$consulta = "SELECT ".$parametros_tabla['obtener'][0]." FROM ". $tabla.' '.$whereImportar;
	//~ $consulta = "SELECT * FROM ". $tabla.' '.$whereImportar;
	$registro_importar = obtenerUnRegistro($BDImportDbf,$consulta);
	$registro = $registro_importar['Items'][0];
	// ----- Obtenemos parametros tpv para poder añadir ------- //
	$parametros_tpv = TpvXMLtablaTpv($parametros_tabla['parametros']);
	// -------------- Montamos SQL para Insert ------------------- //
	// Obtengo nombre tabla
	$tabla_tpv = $parametros_tpv['tablas']['tpv'];
	// Obtengo campos y valores que nos indica cruces de parametros
	$cruces = $parametros_tpv['tpv']['cruce'];
	$valores = array ();
	$into = array();
	foreach ($cruces as $campo_tpv => $array){
		$into[] = $campo_tpv;
		$campo =$array[0];
		$valores[] = '"'.addslashes(htmlentities($registro[$campo],ENT_COMPAT)).'"';
	}
	// Faltaria añadir estado y fecha alta;
	//~ $into [] = 'estado';
	//~ $into [] = 'fechaalta';
	//~ $valores[] = '"importar"';
	//~ $valores[] = 'NOW()';
	
	$sql = 	'INSERT INTO '.$tabla_tpv.' ('.implode(',',$into).') VALUES ('.implode(',',$valores).')';
	$BDTpv->query($sql);
	// -- Obtenemos id que se acaba de crear con insert.
	$idTpv = $BDTpv->insert_id;
	// -- Cambiamos el estado de importar y le ponemos el id.
	$sqlImportar = 'UPDATE '.$tabla.' SET estado = "Nuevo", id = "'.$idTpv.'" '.$whereImportar;
	$BDImportDbf->query($sqlImportar);
	$respuesta['AfectadoImportar'] = $BDImportDbf->affected_rows;
	$respuesta['sqlImportar'] =$sqlImportar;
	$respuesta['sql'] = $sql;
	$respuesta['IdInsertTpv'] = $BDTpv->insert_id;
	
	return $respuesta;
}

function TpvXMLtablaImportar($parametros,$tabla){
	// @Objetivo.
	// Obtener objeto SimpleXML dentro de parametros
	$respuesta = array();
	foreach ($parametros->tablas as $tabla_importar){
		if (htmlentities((string)$tabla_importar->tabla->nombre) === $tabla){
			$respuesta = $tabla_importar->tabla;
		}
	}
	
	return $respuesta;
}

function TpvXMLtablaTpv($tabla_importar){
	//@Objetivo
	// Obtener los datos necesarios de parametros de las tablas para modificar tpv
	$datos_tablas = array();
	if (isset ($tabla_importar->tpv->tabla)){
		foreach ($tabla_importar->tpv as $tablas_tpv){
			foreach ($tablas_tpv as $tabla_tpv){
				$nombre_tabla_tpv = (string) $tabla_tpv->nombre;
				foreach ($tabla_tpv->campo as $campo){
					$nombre_campo =(string) $campo['nombre'];
					if (isset($campo->cruce)){
						$cruce = $campo->cruce;
						$datos_tablas['tpv']['cruce'][$nombre_campo][] = (string)$campo->cruce;
						if (isset($cruce['tipo'])){
							$datos_tablas['tpv']['cruce'][$nombre_campo]['tipo'] = (string)$cruce['tipo'];
						}
					}
					if (isset($campo->tipo)){
						if ((string) $campo->tipo === 'Unico'){
							$datos_tablas['tpv']['campos'][]=$nombre_campo;
						}
					}
				}
			}
		}
	}
	$datos_tablas['tablas']['tpv']= $nombre_tabla_tpv ;
	return $datos_tablas;
}

?>
