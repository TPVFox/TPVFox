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
function ComprobarTabla($nombreTabla,$tablas,$BDImportDbf,$campos,$TControlador) {
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
	//  [Estado] 
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
	foreach ($tablas as $tabla){
		if ($nombreTabla === $tabla) {
			$resultado['Tabla'] = 'Existe';
			// 1º Obtengo estructura  de  la tabla de BDImportar
			$infoTabla= $TControlador->InfoTabla($BDImportDbf,$nombreTabla,'si');
			$campos = $infoTabla['campos'];
			if (isset($infoTabla['error'])){
				// Si NO existe o sale mal la consulta
				$resultado['dropear-tabla'] = true;
				$resultado['accion-borrado'] = 'Borramos tabla';
				break;
			}
			$strEstruct = implode(",",$campos);
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

			$strCampos[$i] = $campo['campo'].' '.$tipo;
			$i++;
		}
	}

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
		if ($BDImportDbf->errno){
			$resultado[$nombretabla]['estado'] ='Error';
			$resultado[$nombretabla]['error'] =$BDImportDbf->errno; 
		} else {
			$resultado[$nombretabla]['estado'] ='Correcto';
		}
	}
	return $resultado ;
	
}

function grabarRegistroImportar($BD,$datos){
	// @ Objetivo :
	// Grabar en tabla de registro de importar los datos .
	// @ Parametro:
	// 		$datos -> array 
	//				 empresas-> array(
	//							idTienda -> (Numero) de id empresa que importamos que es id tienda fisica tb...
	//							tipoTienda-> 'fisica' (String) siempre debería ser
	//							nombre_import -> (String) Es nombre que ponemos en parametros , no tiene que ser el mismo de la 
	//											tienda fisica.
	//							razonsocial-> (String) Es campo tpv de tienda
	// 							ruta -> (String) Es la ruta que ponemos en parametros
	//							)
	//				ficheros-> array (
	//							nombretabla -> Object
	//										Estado = Correcto o error.
	//								)
	
	$respuesta = array();
	$ficheros = json_encode($datos['ficheros']);
	$PrepFicheros = $BD->real_escape_string($ficheros); //  Escapa los caracteres especiales de una cadena para usarla en una sentencia SQL, tomando en cuenta el conjunto de caracteres actual de la conexión
	$ruta = $datos['empresa']['ruta'];
	$consulta = 'INSERT INTO `registro_importacion`(`id`, `empresa`, `ruta`, `ficheros`) VALUES ('.$datos['empresa']['idTienda'].',"'.$datos['empresa']['nombre_import'].'","'.$ruta.'","'.$PrepFicheros.'")';
	$respuesta['consulta'] = $consulta;
	if (!$BD->query($consulta)) {
		// Quiere decir que hubo error en la consulta.
		$respuesta['error'] = $BD->error;
	}
	return $respuesta;
	
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
			if ($array['NItems']>0){
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
	
function VariosRegistros($BD,$consulta,$a_buscar,$campo) {
		/* Objetivo:
		 * Crear una consulta que obtenga todos los campos de la tabla filtrado.
		 * */
		// Funcion para contar registros de una tabla.
		$array = array();
		$array['NItems'] = 0 ; // valor por defecto.	
		$resultadoConsulta = $BD->query($consulta);
		$cont = 0;
		if ($BD->query($consulta)) {
			 //No vale ya podemos descarta alguno.
			if ($resultadoConsulta->num_rows > 0){
				// Hubo resultados
				while ($fila = $resultadoConsulta->fetch_assoc()){
					$respuesta = '';
					// Solo metemos aquellos que cumplan condicion
					$respuesta =DescartarRegistroPalabras($a_buscar,$fila,$campo);
					//~ error_log('fila:'.json_encode($fila).'buscar:'.$a_buscar.' campo:'.$campo.'Respuesta:'.$respuesta);
					if ($respuesta ==='Si'){
						$array['Items'][] = $fila;
						$cont ++;
					}
				}
			}
		$array['NItems'] = $cont;	
		} else {
			// Quiere decir que hubo error en la consulta.
			$array['consulta'] = $consulta;
			$array['error'] = $BD->error;
		}
		 $array['NItems'] = $cont;
		//~ $array['sql']=$consulta;
		return $array;
	}
	
	
	
	
function BuscarIgualSimilar($BDTpv,$campos,$registro){
	// Objetivo
	//  Devolver comprobacion si existe igual o similares ( con lo registros obtenidos).
	$respuesta = array();
	$respuesta['tpv'] = array();
	foreach ($campos as $key=>$datos){
		// Recorremos los campos que tenemos en parametros de cada tabla xml.
		$campo = $key;
		// Ahora recorremos las acciones queremos que haga - si hay claro.
		if (isset($datos['acciones_buscar'])){
			foreach ($datos['acciones_buscar'] as $num_accion=>$accion){
				if ($accion['funcion'] === 'mismo'){
					//Buscamos si es mismo registro.
					if (isset($registro[$campo]) && trim($registro[$campo]) !== '' ){
						$whereC =' WHERE '.$accion['campo_cruce'].'="'.$registro[$campo].'"';
						// Obtenemos tabla... debería comprobar si es o no tabla o si existe.
						$tabla = $accion['tabla_cruce'];
						$consulta = "SELECT * FROM ". $tabla.' '.$whereC;
						$UnRegistro = obtenerUnRegistro($BDTpv,$consulta);
						// Ahora registramos lo que hicimos.
						// Montamos Accion para saber resultado ->CAMPO+Num_Accion+funcion+Descripcion
						$respuesta['comprobacion'][$campo][$num_accion]['accion'] =$accion['funcion'].' -> '.$accion['description'];
						$respuesta['comprobacion'][$campo][$num_accion]['respuesta'] = $UnRegistro;
						// Registramos resultado si hay item
						$respuesta['tpv'] = $UnRegistro;

						if ($UnRegistro['NItems'] > 0){
							$respuesta['comprobacion']['encontrado_tipo'] = 'Mismo';
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
						// Obtenemos where de la tabla, de las palabras indicadas.

						$tabla = $accion['tabla_cruce'];
						$nombre_campo = $accion['campo_cruce'];
						$a_buscar = $registro[$campo];
						// OR ó AND son los posibles operadores.
						$busqueda = ConstructorLike($nombre_campo,$a_buscar,'OR');
						$whereC =' WHERE '.$busqueda;
						$consulta = "SELECT * FROM ". $tabla.' '.$whereC;
						$Registros= VariosRegistros($BDTpv,$consulta,$a_buscar,$nombre_campo);

						if ($Registros['NItems'] >0){
							// Lo ideal sería añadir los registros tpv, pero el problema es que van repetir.
							$respuesta['tpv'] = $Registros;
							$respuesta['comprobacion']['encontrado_tipo'] = 'Similar';
						} 
						// Ahora registramos lo que hicimos
						// Montamos Accion para saber resultado ->CAMPO+Num_Accion+funcion+Descripcion
						$respuesta['comprobacion'][$campo][$num_accion]['accion'] = $accion['funcion'].' -> '.$accion['description'];
						$respuesta['comprobacion'][$campo][$num_accion]['consulta'] = $consulta;
						$respuesta['comprobacion'][$campo][$num_accion]['respuesta'] = $Registros;
						
					}
				}
			}
		}
	}
	// Si no encontro igual o similar
	if (!isset($respuesta['comprobacion']['encontrado_tipo'])){
			// Quiere decir que no encontro ninguno igual o similar
			$respuesta['comprobacion']['encontrado_tipo'] = 'NoEncontrado';
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


function AnhadirRegistroTpv($BDTpv,$BDImportDbf,$CParametros,$datos){
	// @ Objetivo 
	// Añadir registro de las tablas importar a tpv
	// @ Parametros.
	// $tabla : Array con String de nombre tablas.
	// $parametros: Objeto ClaseArrayParametrosTabla
	// $datos: Array (	importar => array( campos unicos y valores de esos campos...)
	//					tpv => array ( campos unicos y valores de esos campos... )
	$respuesta = array();
	// --- Obtenemos las tablas que vamos utilizar tanto tpv, como BDImportarDBF --- //
	$tablas = $CParametros->getTablas();
	// --- Obtenemos el nombre de tabla de importar , que de momento siempre es uno... --- //
	$tabla = $tablas['importar'][0];
	// --- Obtenemos los valores de los campos del registro de la Base de Datos BDimport --- //
	$wheres = array();
	foreach ($datos as $dato){
		foreach ($dato as $campo => $valor){
			$wheres[]= $campo.' = "'.$valor.'"';
		}
	}
	$whereImportar = 'WHERE '.implode(' AND ',$wheres);
	// Obtenemos las consulta->obtener de parametros
	// Ahora interpreto que hay una sola, pero es un array que puede contener varias consultas. 
	$a = $CParametros->getConsultas('Obtener');
	$campos_obtener = $a[0]; // Entiendo que 0 es obtener, que no mas de uno...
	$consulta = "SELECT ".$campos_obtener." FROM ". $tabla.' '.$whereImportar;
	$registro_importar = obtenerUnRegistro($BDImportDbf,$consulta);
	$registro = $registro_importar['Items'][0];
	// Compruebo que haya solo un resultado , para evitar errores
	if ( count($registro_importar['Items']) === 1){
		// Hago bluce tablas de tpv, ya que puede haber mas que uno.
		foreach ($tablas['tpv'] as $t){
			// -------------- Montamos SQL para Insert ------------------- //
			// Obtengo nombre tabla ( Si solo tengo una .. claro... )
			$tabla_tpv = $t;
			$valores = array ();
			$into = array();
			// Antes de nada comprobamos si hay funciones a realizar despues de obtener
			$funcBefore = $CParametros->getBeforeAnhadir();
			if (count($funcBefore) >0){
				// Esta funciones obtenemos campos y valores necesaios para hacer Insert
				$funcion = $funcBefore[$tabla_tpv];
				$r = $funcion();
				if ( count($r) >0 ){
					$into = $r['into'];
					$valores =  $r['valores'];
				}
			}
			// Obtengo campos y valores que nos indica cruces de parametros
			$cruces = $CParametros->ObtenerCrucesTpv($tabla_tpv);
		
			foreach ($cruces as $campo_tpv => $cruce){
				$into[] = $campo_tpv;
				$valores[] = '"'.addslashes(htmlentities($registro[$cruce],ENT_COMPAT)).'"';
			}

			$sql = 	'INSERT INTO '.$tabla_tpv.' ('.implode(',',$into).') VALUES ('.implode(',',$valores).')';
			$BDTpv->query($sql);
			// -- Obtenemos id que se acaba de crear con insert.
			$idTpv = $BDTpv->insert_id;
			//-- Ahora buscamos si tiene funciones a realizar after_insert
		}
		// -- Cambiamos el estado de importar y le ponemos el id.
		$sqlImportar = 'UPDATE '.$tabla.' SET estado = "Nuevo", id = "'.$idTpv.'" '.$whereImportar;
		$BDImportDbf->query($sqlImportar);
		$respuesta['AfectadoImportar'] = $BDImportDbf->affected_rows;
		$respuesta['sqlImportar'] =$sqlImportar;
		$respuesta['sql'] = $sql;
		$respuesta['IdInsertTpv'] = $BDTpv->insert_id;
	}
	$respuesta['consulta_obtener'] = $consulta;
	return $respuesta;
}


function TpvXMLtablaTpv($parametros_importar){
	//@Objetivo
	// Obtener los datos necesarios de parametros de las tablas tpv para modificar,buscar o añadir
	$datos_tablas = array();
	if (isset ($parametros_importar->tpv->tabla)){
		foreach ($parametros_importar->tpv as $tablas_tpv){
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
		$datos_tablas['tablas']['tpv'][]= $nombre_tabla_tpv ;
		}
	}
	return $datos_tablas;
}



function MontarHtmlOpcionesGenerales($parametros_comprobaciones,$resultado_b,$item) {
	$html = '<select id="accion_general_'.$item.'">';
	foreach ($parametros_comprobaciones as $tipo => $parametros){
		if ($tipo === $resultado_b){
		$Options = $parametros[0]->options->option;
			foreach ($Options as $parametro){
				$valor =(string)$parametro['tipo'];
				$texto = (string)$parametro->texto;
				$html .= '<option value="'.$valor.'">'.$texto.'</option>';
			}
		}
	}
	$html .= '</select> ';
	
	return $html;
}
function BeforeProcesosOpcionesGeneralesComprobaciones($Xmlfunciones,$item){
	$respuesta = array();
	foreach ($Xmlfunciones as $Xmlfuncion){
		$funcion = (string) $Xmlfuncion['funcion'];
		$respuesta[] = $funcion($item);
	}
	return $respuesta;
} 
function SeleccionarRegistroFamilias($item){
	$respuesta = '<div>
				<p>Debes añadirle un id en tabla de BDImportar de la tabla Tpv para poder crear el cruce y relacionarlo</p>
				<div class="form-group">
				<input id="anhado_id_'.$item.'" type="number " name="id">
				</div>
				<div class="form-group">
				<button id="AnadirID_'.$item.'" class="btn btn-primary" data-obj="botonID" onclick="controlEventos(event)">Añadir ID</button>
				</div>
				</div>	';
	return $respuesta;
	
}

function FamiliaIdInsert($BDImportDbf,$BDTpv,$datos,$idvalor){
	// Objetivo:
	// Es añadir el cruce de la familia de DBF con TPV y quitarlo del registro.
	$respuesta = array();
	//~ $consultaImportar = 
	
	$respuesta = count($datos);
	
	return $respuesta;
}


function ConstructorLike($campo,$a_buscar,$operador='AND'){
	// @ Objetivo:
	// Construir un where con like de palabras y el campo indicado
	// Si contiene simbolos extranos les ponemos espacios para buscar palabras sin ellos.
	// @ Parametros:
	// 	$operador -> (String) puede ser OR o AND.. no mas...
	$buscar = array(',',';','(',')','-','"');
	$sustituir = array(' , ',' ; ',' ( ',' ) ',' - ',' ');
	$string  = str_replace($buscar, $sustituir, trim($a_buscar));
	$palabras = explode(' ',$string);
	$likes = array();
	// La palabras queremos descartar , la ponemos en mayusculas
	$descartar = array('PARA','COMO','CUAL');
	foreach($palabras as $palabra){
		if (trim($palabra) !== '' && strlen(trim($palabra))>3){
			// Entra si la palabra tiene mas 3 caracteres.
			// Aplicamos filtro de palabras descartadas
			if (!in_array(strtoupper($palabra),$descartar)){
				$likes[] =  $campo.' LIKE "%'.$palabra.'%" ';
			}
		}
	}
	// Montamos busqueda con el operador indicado o el por defecto
	$operador = ' '.$operador.' ';
	$busqueda = implode($operador,$likes);
	return $busqueda;
}
function DescartarRegistroPalabras($a_buscar,$registro,$campo){
	// Objetivo:
	// Comprobar que el resultado contiene mas de la mitad de la palabras.
	$respuesta = 'SinComprobar:';
	$buscar = array('.',',',';','(',')','-','"');
	$sustituir = array(' . ',' , ',' ; ',' ( ',' ) ',' - ',' '); // Doble comilla genera errro la quitamos
	$string  = str_replace($buscar, $sustituir, trim($a_buscar));
	$palabras = explode(' ',$string);
	$likes = array();
	// La palabras queremos descartar , la ponemos en mayusculas
		$cont_palabras= 0;
		$contador_aciertos = 0;
		foreach($palabras as $palabra){
			if (trim($palabra) !== '' && strlen(trim($palabra))>3){
				$respuesta .= $palabra.'--';
				$cont_palabras ++;
				// Entra si la palabra tiene mas 3 caracteres.
				// Aplicamos filtro de palabras descartadas
				$pos =stripos($registro[$campo],$palabra); 
				$respuesta .= $pos.'->';	
				if ($pos !== FALSE){
					$contador_aciertos++;
				}
			}
		}
		// Ahora comprobamos si la division entre palabras y aciertos es mayor 2 quiere decir que es mas 50%
		
		if ($cont_palabras >0 && $contador_aciertos>0){
			if (($cont_palabras/$contador_aciertos)<=2){
				// Indicamos que el registro coincide en mas 50% de las palabras que enviamos.
				$respuesta = 'Si';
			} else {
				$respuesta = 'No'.$cont_palabras.'/'.$contador_aciertos;
			}
		}
		//~ $respuesta .=' contador palabras:'.$cont_palabras.' aciertos :'.$contador_aciertos;
	return $respuesta;
}

function AnhadirEstadoFecha(){
	// @ Objetivo
	// Es actualizar el Estado, Fecha en tpv.
	$respuesta = array();
	$respuesta['into'][] ='estado'; 
	$respuesta['valores'][] = '"'.'importar'.'"';
	$respuesta['into'][] ='fecha_creado';

	$respuesta['valores'][] = '"'.date("Y-m-d H:i:s").'"';
	
	return $respuesta;
	
}

function AnhadirID_DbfImportar($BDImportar,$datos_importar,$datos_tpv,$tabla){
	// Objetivo :
	// Es añadir el id de tpv en BDFimportar con estado 'existe'
	$respuesta = array();
	// Monstamos las consulta.
	$datos=array();
	
	foreach ($datos_tpv as $campos){
		$campo = key($campos);
		$valor = $campos[$campo];
		// Esto es valido para proveedores, pero se si sera para todas las tablas...
		$datos[] = 'id="'.$valor.'",estado="existe"';
	}
	$camposYvalor =implode(',',$datos);  
	
	$datos=array();
	foreach ($datos_importar as $campos){
		$campo = key($campos);
		$valor = $campos[$campo];
		$datos[] = $campo.'="'.$valor.'"';
	}
	
	
	$comparadorYvalor =implode(' AND ',$datos);  
	
	$consulta= 'UPDATE '.$tabla.' SET '.$camposYvalor.' WHERE '.$comparadorYvalor;
	
	$BDImportar->query($consulta);
	
	$respuesta['AfectadoImportar'] = $BDImportDbf->affected_rows;
	$respuesta['consulta'] = $consulta;
	
	return $respuesta;
}

?>
