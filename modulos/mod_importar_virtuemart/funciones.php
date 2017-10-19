<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 TpvOlalla de Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero ,
 * @Descripcion	Funciones para importar datos de Virtuemart a Tpv
 * */
  
 function prepararTablasTemporales($BDVirtuemart,$tTemporal)
 {
	//@ Objetivo : 
	// Crear las tablas temporales que indiquemos en array $tablasTemporales en BDVirtuemart
	// RECUERDA que el nombre de los campos tiene que ser el mismo de los campos queremos hacer insert tpv.
	
	$resultado = array();
	$nombre_temporal = $tTemporal['nombre_tabla_temporal'];
	// En debug:
	// Inicialmente haciamos CREATE TEMPORARY TABLE, pero no se cual fue el motivo, pero 
	// en la tabla tmp_productos_img me generaba un error.
	// Por lo que decido hacerlos con CREATE TABLE  permanente.
	// para ello tenemos que hacer:
	$sqlBDImpor = 'DROP TABLE IF EXISTS '.$nombre_temporal;
	$BDVirtuemart->query($sqlBDImpor);
	// Creamos las tablas temporales ( TEMPORARY ) y añadimos campo de id
	//~ foreach($tablasTemporales as $tTemporal) {
		$sqlBDImpor = 'CREATE TABLE '.$nombre_temporal.' as '.$tTemporal['select'];
		
		if ($BDVirtuemart->query($sqlBDImpor) === TRUE) {
			// Se creó con éxito la tabla articulosCompleta en
			$resultado[$nombre_temporal]['tabla-creada'] = TRUE;
			// Obtenemos los registros afectados que serían los registros que hay virtuemart.
			$resultado[$nombre_temporal]['Num_articulos'] = $BDVirtuemart->affected_rows;

		}else {
			// Algo paso  al crear temporal tabla en BDimportar.. no salio bien. Prueba quitando temporal viendo la tabla;
			$resultado['error'][$nombre_temporal]['info_error'] =  $BDVirtuemart->error;
			$resultado['error'][$nombre_temporal]['consulta'] =  $sqlBDImpor;
			//~ $resultado
		}
		// [PENDIENTE DECIDIR]
		// Ahora añadimos el campo id, creo que esto se debería hacer en comprobaciones antes de insertar
		// pero entonces deberíamos hacer el proceso comprobaciones en todas las tablas tmp, cosa que no hago.
		// Recuerda que esto esl que hacer ID auto incremental, recuerda que esto empieza desde 1
		$sqlAlter = "ALTER TABLE ".$nombre_temporal." ADD ".$tTemporal['campo_id']." INT AUTO_INCREMENT PRIMARY KEY FIRST";
		
		if ($BDVirtuemart->query($sqlAlter) === TRUE) {
			// Se creó con éxito la tabla articulosCompleta en
			$creado = $tTemporal['campo_id'].'_creado';
			$resultado[$nombre_temporal][$creado] = TRUE;
			$resultado[$nombre_temporal][$creado.'consula'] = $sqlAlter;


		}else {
			// Algo paso  al crear temporal tabla en BDimportar.. no salio bien. Prueba quitando temporal viendo la tabla;
			$resultado['error'][$nombre_temporal]['consulta'] = $sqlAlter;
			$resultado['error'][$nombre_temporal]['info_error'] =  $BDVirtuemart->error;
			//~ $resultado
		}
	//~ } 
	// Fin de creación tablas temporales
	return $resultado;
}

function  prepararInsertArticulosTpv($BDVirtuemart,$BDTpv,$prefijoBD,$tablas){
	// @ Objetivo es preparar un array con los insert que vamos realizar en BDTpv
	// 	a parte eso, tambien devolvemos el numero articulos  y cuanto descartamos.
	$resultado = array();
	// Recorremos array para ejecutar las distintas consultas y insertar los datos .
	$i = 0;					
	foreach ($tablas as $Arraytabla) {
		$tabla = $Arraytabla['nombre'];
		$campos = $Arraytabla['campos'];
		$camposObligatorios = (isset($Arraytabla['obligatorio'])  ? $Arraytabla['obligatorio'] : array());
		$stringcampos = implode(',',$campos);
		$sql = 'SELECT '.$stringcampos.' FROM '.$Arraytabla['origen'];//`tmp_articulosCompleta`';
		$resultado['tabla'][$tabla]['consulta'] = $sql;
		$articulos = $BDVirtuemart->query($sql);

		if ( $articulos != true) {
			// Algo salio mal, por lo que devolvemos error y consulta.
			$resultado['tabla'][$tabla]['consulta'] = $sql;
			$resultado['tabla'][$tabla]['error'] =  $BDVirtuemart->error;
			return $resultado;
		}
		$resultado['tabla'][$tabla]['Select'] = $sql; 
		// Obtenemos lo valores de estaa consulta, pero en grupos de mil
		$agruparValores = ObtenerGruposInsert($articulos,$camposObligatorios);
		$gruposvaloresArticulos = $agruparValores['Aceptados'];
		$resultado['tabla'][$tabla]['Num_Insert_hacer'] = count($gruposvaloresArticulos); 
		$resultado['tabla'][$tabla]['descartado'] = $agruparValores['Descartados'];
		$stringcampos = '('.implode(',',$campos).')';

		$sql = 'INSERT INTO '.$tabla.' '.$stringcampos.' VALUES ';
		$num_reg_insert = 0;
		foreach ($gruposvaloresArticulos as $valoresArticulos){
			$Nuevosql = $sql.implode(',',$valoresArticulos);
			$resultado['tabla'][$tabla]['Insert'][] =$Nuevosql;
		}
		$i++;
	}
	
	
	
	return $resultado;
}
function EliminarArticulosTpv($BDtpv,$tablas,$controlador){
	//@ Objetivo es eliminar las tablas los productos(articulos) que existen en TPV
	// contenido de las tablas.
	$respuesta = array();
	$suma= 0;
	foreach ($tablas  as $tabla){
		$registrosEliminados = $controlador->EliminarTabla($tabla, $BDtpv);
		$respuesta[$tabla] = $registrosEliminados;
		$suma += (int)$registrosEliminados;

	}
	
	$respuesta['TotalRegistroEliminados']= $suma;
	return $respuesta;
}













function ObtenerGruposInsert($registros,$obligatorios = array()){
	// @Objetivo es conseguir un grupos array con los valores para insertar.
	//  esto se hacer para no realizar un insert con mas 1000 registros a insertar.
	// @ Parametros
	// 		$registros 
	// 		$obligatorios -> Es el campo de la tabla que obligatorio que exista.
	$respuesta = array();
	// Ahora obtenemos valores Descartados y Aceptados.
	$stringValores = valoresComprobados($registros,$obligatorios);
    // Ahora tenemos insertar los valores pero lo tenemos hacer de 1000 registros, ya que sino puede generar un error.
    //~ $debug = array();
    $gruposvalores = array(
						'Aceptados' 	=> array(),
						'Descartados' 	=> array()
					);
 	$gruposvalores['Aceptados'] = array_chunk($stringValores['Aceptados'], 1000, true);
 	$gruposvalores['Descartados'] = array_chunk($stringValores['Descartados'], 1000, true);
	$respuesta = $gruposvalores;
	// Formateamos los descartados.
	$descartado = array();
	foreach ($respuesta['Descartados'] as $valoresArticulos){
			$Nuevosql = implode(',',$valoresArticulos);
			$descartado[] =$Nuevosql;
	}
	$respuesta['Descartados']= $descartado;
	return $respuesta ;
	
	
}

function ComprobarCodbarras ($BDVirtuemart){
	// No permitimos que dos productos distintos tenga el mismo código de barras, si es así antes de hacer la iniciación debemos 
	// informar, y ademas la tabla articulosCodbarras no lo permite.
	$respuesta= array();
	$sql = "SELECT `codbarras` , COUNT( * ) Total FROM tmp_articulosCompleta GROUP BY codbarras HAVING COUNT( * ) >1";
	$registros = $BDVirtuemart->query($sql); 
	if ($registros == TRUE){
		// Si se ejecuta correctamente.
		// Ahora montamos array de codbarrarRepetidos sin los vacios.
		$codbarrasRepetidos = array();
		while  ($fila = $registros->fetch_assoc()){
			if ($fila['codbarras']!=''){
				$codbarrasRepetidos[] = $fila['codbarras'];
			}
		}
		$respuesta['Codbarras_repetidos'] = $codbarrasRepetidos;
	} else {
		// Algo salio mal..
		$respuesta['error'] = $BDVirtuemart->error;
		$respuesta['consulta']= $sql;
	}
	return $respuesta;
	
}





function valoresComprobados($registros,$obligatorios = array()){
	// @ Objetivos es limpiar los valores que no contienen datos en los campos obligatorios
	// @ Parametros:
	// 		//registros = Consulta ya realizada en $BD
	// @ Devolvemos :
	// 	array con Aceptados y descartados.
	
	$respuesta = array(
			'Aceptados' 	=> array(),
			'Descartados' 	=> array()
			);
	$i= 0;
	while ($registro = $registros->fetch_assoc()) {
		// Montamos array para devolver array de arrays
		$error = '';
        $valores = array();
		foreach ($registro as $valor){
			// El insert funciona con "1" aunque el campo sea int o decimal,,por eso lo pongo y por los vacios
			$valores[]= '"'. $valor.'"';
		}
		if (count($obligatorios) > 0){
			//Quiere decir que hay campos que son obligatorios 
			foreach ($obligatorios as $obligatorio){
				// Recorremos y comprobamos los campos obligatorios
				if (strlen($registro[$obligatorio]) === 0){
				// Quiere decir que no tiene valor el campo, por lo que no continuamos
				$error = 'Campo '.$obligatorio. ' no tiene dato';
				break; // Salgo foreach obligatorio.
				}
			}
		}
		if ($error === ''){
			$respuesta['Aceptados'][]= '('.implode(',',$valores).')';
		} else{
			$respuesta['Descartados'][] = '('.implode(',',$valores).')';
		}
    }
    return $respuesta;
    
	
}

function RealizarInsert($Inserts,$BDTpv){
	//@ Objetivo ejecutar inserts de las tablas.
	//@ Parametro:
	//   $Inserts-> Es un array de inserts.
	//	 $BDTpv ->  Conexion a base de datos.
	$respuesta = array(
					'Num_affecta' => array()
				);
	foreach ($Inserts as $insert){
		$BDTpv->query($insert);
		if ($BDTpv->errno){
			$respuesta['error'] = 'Error en consulta:'.$BDTpv->errno;
			$respuesta['consulta'] = $insert;
		} else {
			$respuesta['Num_affecta'][] =$BDTpv->affected_rows;
		}
	}
	return $respuesta;
	
}

function ComprobarTablaTempArticulosCompleta ($BDVirtuemart){
	// @ Objetivo:
	//   Comprobar en tabla tempora tmp_articulosCompleta.
	//			subproceso: RecalculoPrecioConIvas
	//			subproceso: CodbarrasRepetidos.
	$resultado = array();
	// [SUBPROCESO:RecalculoPrecioConIvas] Calculamos el precio con iva,  ya que virtuemart no nos lo facilita.
	$sqlUpdate = "UPDATE `tmp_articulosCompleta` SET `pvpCiva`=`pvpSiva`*(100+`iva`)/100";
	if ($BDVirtuemart->query($sqlUpdate) === TRUE) {
		// Se creó con éxito la tabla articulosCompleta en
		$resultado['RecalculoPrecioConIva']['estado'] = TRUE;
	}else {
		// Algo paso  al crear temporal tabla en BDimportar.. no salio bien. Prueba quitando temporal viendo la tabla;
		$resultado['RecalculoPrecioConIva']['error']['consulta'] = $sqlUpdate;
		$resultado['RecalculoPrecioConIva']['error']['info_error'] =  $BDVirtuemart->error;
		$resultado['RecalculoPrecioConIva']['estado'] = false;
	}
	// Ahora hacemos las comprobaciones:
	// [SUBPROCESO : CodbarrasRepetidos] Comprobamos codbarras repetidos.]
	// Por defecto pongo 
	$resultado['CodbarrasRepetidos']['estado'] = TRUE;
	// Si hay un error devolvemos YA LOS CAMBIAMOS Y MANDAMOS error 
	$repetidos = ComprobarCodbarras ($BDVirtuemart);
	if ( isset($repetidos['Codbarras_repetidos'])){
		if (count($repetidos['Codbarras_repetidos']) >0 ){
			// Quiere decir que hay repetidos.
			$resultado['CodbarrasRepetidos']['error']['items'] = $repetidos;
			$resultado['CodbarrasRepetidos']['estado'] = false;
		} 
	} else {
		// Quiere decir que algo salio mal.
		$resultado['ComprobarCodbarras']['error'] = $repetidos;
		$resultado['CodbarrasRepetidos']['estado'] = false;
	}	
	return $resultado;
}


function ComprobarTablaTempClientes ($BDVirtuemart){
	// @ Objetivo 
	// Comprobar la tabla temporal de Clientes 
	// 		subproceso: AnhadirIdClientes1
	//	[SUBPROCESO : AnhadirIdCliente1] -> Donde añadimos el registro con id 1 que es cliente Sin identificar
	//  La complejidad de esto es que ya tenemos la tabla cubierta,
	//  con el campo idClientes autoincremental creado por lo que :
	//  Quitamos auto incremental : 
	$sqlInsert ="ALTER TABLE `tmp_clientes` CHANGE `idClientes` `idClientes` INT(11) NOT NULL";
	$BDVirtuemart->query($sqlInsert);
	//  Eliminamos indice: 
	$sqlInsert = " ALTER TABLE `tmp_clientes` DROP PRIMARY KEY";
	$BDVirtuemart->query($sqlInsert);
	// 	Ahora deberíamos cambiar idClientes y le incrementamos uno .
	$sqlInsert = " UPDATE `tmp_clientes` SET `idClientes`= idClientes+1"; 
	$BDVirtuemart->query($sqlInsert);
	//  Ahora añadimos el primer regi;stro.
    $sqlInsert = " INSERT INTO `tmp_clientes` (`idClientes`, `Nombre`, `razonsocial`) VALUES (1,'Sin identificar','Sin identificar') ";
	$BDVirtuemart->query($sqlInsert);
	//  Volvemos a crear el indice
	$sqlInsert = " ALTER TABLE `tmp_clientes` ADD PRIMARY KEY(`idClientes`) ";
	$BDVirtuemart->query($sqlInsert);
	// 	Cambiamos denuevo el campo idCliente a autoincremental.
	$sqlInsert = " ALTER TABLE `tmp_clientes` CHANGE `idClientes` `idClientes` INT(11) NOT NULL AUTO_INCREMENT" ;
	if ($BDVirtuemart->query($sqlInsert) === TRUE) {
		// Se creó con éxito la tabla articulosCompleta en
		$resultado['AnhadirIdCliente1']['estado'] = TRUE;
	}else {
		// Algo paso  al crear temporal tabla en BDimportar.. no salio bien. Prueba quitando temporal viendo la tabla;
		$resultado['AnhadirIdCliente1']['error']['consulta'] = $sqlInsert;
		$resultado['AnhadirIdCliente1']['error']['info_error'] =  $BDVirtuemart->error;
		$resultado['AnhadirIdCliente1']['estado'] = false;
	}
	//[NOTA] :
	// Intente ejecutar todo con una mismo $BDVirtuemart->query($sqlInsert);
	// pero me generaba un error por eso hago todas las consultas... :-)
	return $resultado;
	
}







?>
