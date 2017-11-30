<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 TpvOlalla de Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero ,
 * @Descripcion	Funciones para importar datos de Virtuemart a Tpv
 * 
 *  // Utilizamos sufijos en los nombres de las funciones:
 * 	// Crear_  -> Proceso crear tablas temporales.( Realmente este no es un sufijo).. ya que solo utilizamos en una funcion.
 *  // Comprobar_  -> Proceso que hacermos despues de crear las tablas temporales.
 *  // preparar
 * */
  
 function CrearTablasTemporales($BDVirtuemart,$tTemporal)
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
		$sqlBDImpor = 'CREATE TABLE '.$nombre_temporal.' as '.$tTemporal['select'];
		
		if ($BDVirtuemart->query($sqlBDImpor) === TRUE) {
			// Se creó con éxito la tabla articulosCompleta en
			$resultado[$nombre_temporal]['tabla-creada'] = TRUE;
			// Obtenemos los registros afectados que serían los registros que hay virtuemart.
			$resultado[$nombre_temporal]['Num_articulos'] = $BDVirtuemart->affected_rows;

		}else {
			// Algo paso  al crear temporal tabla en BDimportar.. no salio bien. Prueba quitando temporal viendo la tabla;
			$resultado['error'][$nombre_temporal]['crearTmp']['info_error'] =  $BDVirtuemart->error;
			$resultado['error'][$nombre_temporal]['crearTmp']['consulta'] =  $sqlBDImpor;
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
			$resultado['error'][$nombre_temporal]['alter']['consulta'] = $sqlAlter;
			$resultado['error'][$nombre_temporal]['alter']['info_error'] =  $BDVirtuemart->error;
			//~ $resultado
		}
	// Fin de creación tabla temporal
	return $resultado;
}

function  prepararInsertTablasBDTpv($BDVirtuemart,$tablas){
	// @ Objetivo es preparar un array con los insert que vamos realizar en varias tablas de BDTpv
	// 	a parte eso, tambien devolvemos el numero inserts y cuanto descartamos.
	$resultado = array();
	// Recorremos array para ejecutar las distintas consultas y insertar los datos .
	foreach ($tablas as  $key => $tabla) {
		$tabla_destino = $tabla['nombre'];
		$tabla_origen = $tabla['origen'];
		// Ahora recorremos array $tablas['tipos_inserts'] por si ha mas de un proceso.
		$i = 0;					
		foreach ($tabla['tipos_inserts'] as $Arraytabla){
			$campos_origen = implode(',',$Arraytabla['campos_origen']);
			$campos_destino = '('.implode(',',$Arraytabla['campos_destino']).')';
			$camposObligatorios = (isset($Arraytabla['obligatorio'])  ? $Arraytabla['obligatorio'] : array());
			$insertUnaTabla = prepararInsertUnaTabla($BDVirtuemart,$campos_destino,$campos_origen,$tabla_origen,$tabla_destino,$camposObligatorios);
			$resultado[$key][$tabla_destino][$i]= $insertUnaTabla;
			$i++;

		}
	}
	return $resultado;
}


function prepararInsertUnaTabla($BDVirtuemart,$campos_destino,$campos_origen,$tabla_origen,$tabla_destino,$camposObligatorios){
	// @ Objetivo es preparar un array con los insert que vamos realizar en UNA TABLA de BDTpv
	// 	a parte eso, tambien devolvemos el numero inserts y cuanto descartamos.
	$resultado = array();
	$sql = 'SELECT '.$campos_origen.' FROM '.$tabla_origen;
		$articulos = $BDVirtuemart->query($sql);
		if ( $articulos != true) {
			// Algo salio mal, por lo que devolvemos error y consulta.
			$resultado['error'] =  $BDVirtuemart->error;
		} else {
			// Obtenemos lo valores de estas consulta, pero en grupos de mil
			$agruparValores = ObtenerGruposInsert($articulos,$camposObligatorios);
			//~ $gruposvaloresArticulos = $agruparValores['Aceptados'];

			$sql = 'INSERT INTO '.$tabla_destino.' '.$campos_destino.' VALUES ';
			foreach ($agruparValores['Aceptados'] as $valoresArticulos){
				$resultado['Insert'][] = $sql.implode(',',$valoresArticulos);
			}
		}	
		$resultado['Num_Insert_hacer'] = count($agruparValores['Aceptados']); 
		$resultado['descartado'] = $agruparValores['Descartados'];
		$resultado['consulta'] = $sql;

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
	// Comprobamos que si dos productos tienen mismo código de barras,
	// deberíamos guardar estos datos en un informe de importacion.
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

function BeforeTabla_tmp_familias($BDVirtuemart){
	// @Objetivo es modificar el id_padre por id correcto, ya que el id que ponemos es virtuemart.
	// Esta funcion es llamada desde tareas, $tablaTemporal['nombre_tabla_temporal']
	$resultado = array();
	$Updates = array();
	$Sql = "SELECT * FROM tmp_familias";
	
	
	if ($ItemFamilias = $BDVirtuemart->query($Sql)) {
		foreach ($ItemFamilias as $item){
		// Obtenemos el id familia de la tienda: ref_familia_tienda
		// Montamos el update que vamos realizar.
			if ($item['familiaPadre']>0){
				// Buscamos el idFamilia del padre.
				$SqlPadre = 'SELECT idFamilia FROM tmp_familias WHERE ref_familia_tienda="'.$item['familiaPadre'].'"';
				$Consulta = $BDVirtuemart->query($SqlPadre);
				$idNuevoFamilia = $Consulta->fetch_row();
				$Updates[]= 'UPDATE tmp_familias SET familiaPadre = '.$idNuevoFamilia[0].' WHERE idFamilia='.$item['idFamilia'];
			}
		}
		
	}else {
		// Algo paso  al crear temporal tabla en BDimportar.. no salio bien. Prueba quitando temporal viendo la tabla;
		$resultado['error']['consulta'] = $Sql;
		$resultado['error']['info_error'] =  $BDVirtuemart->error;
	}
	// Si no hubo error entonces ejecutamos los update que preparamos.
	if ( count($resultado) === 0  &&  count( $Updates) >0) {
		// Esto lo hago separado del otro foreach, ya que hay se que hay una forma de montar un update unico
		foreach ( $Updates as $Update) {
			$BDVirtuemart->query($Update);
		}
		array_push($resultado,array('Update' => $Updates));

	}
	
	
	
	return $resultado;
}


function ObtenerNumRegistrosVariasTablas($Controler,$BDTpv,$tablas){
	// @Objetivo: Es contrar los registros de un array de varias tablas
	foreach ( $tablas as $key => $tabla ) {
		$nombreTabla = $tabla['nombre'];
		$Registros = $Controler->contarRegistro($BDTpv,$nombreTabla);
		$tablas[$key]['NumRegistros'] = (int)$Registros ;
	}
	return $tablas;
}

function SumarNumRegistrosVariasTablas($tablas){
	// @Objetivo: Es sumar todos los registros que tenga array de tablas
	$sum_registros = 0;
	foreach ( $tablas as $key => $tabla ) {
		$sum_registros = $sum_registros + $tablas[$key]['NumRegistros'];
	}
	return $sum_registros;

}



function htmlBDTpvTR($tablas){
	// @Objetivo es obtener tbody de tabla de Base Datos Tpv
	$html = '';
	foreach ( $tablas as $key=>$tabla ) {
		$n_tabla = $tabla['nombre'];
		$html.='<tr id="'.$key.'">';
		$html.='<th>'.$tabla['nombre'].'</th>';
		$html.= '<td>'.$tabla['NumRegistros'] .'</td>';
		$html.= '<td>';
		if ($tabla['NumRegistros'] === 0){
		$html.= '<span class="glyphicon glyphicon-ok"></span>';
		}
		$html.='</td>';
		$html.= '<td class="inserts">'.'</td>';
		$html.='</tr>';
	}
	return $html;


}

function ObtenerTiendaWeb($BDTpv){
	// Objetivo obtener datos de la tabla tienda para poder cargar el select de tienda On Line.
	$resultado = array();
	$sql = "SELECT * FROM `tiendas` WHERE `tipoTienda`='web'";
	$resultado['consulta'] = $sql;
	if ($consulta = $BDTpv->query($sql)){
		// Ahora debemos comprobar que cuantos registros obtenemos , si no hay ninguno
		// hay que indicar el error.
		if ($consulta->num_rows > 0) {
				while ($fila = $consulta->fetch_assoc()) {
				$resultado['items'][]= $fila;
				}
			
		} else {
			// Quiere decir que no hay tienda on-line (web) dada de alta.
			$resultado['error'] = 'No hay tienda on-line';
		}

	} else {
		// Quiere decir que hubo un error en la consulta.
		$resultado['error'] = 'Error en consulta';
		$resultado['numero_error_Mysql']= $BDTpv->errno;
	
	}
	
	return $resultado;
	
	
	
	
}
function ComprobarExisteLogTpv($ruta,$mensaje_log){
	// @Objetivo: 
	// Es comprobar existe la ruta donde vamos guardar log mod_impor_virtuemart ( uno por dia)
	// @ Parametro: ruta directorio de datos ( sin el ultima barra)
	$respuesta = array();
	// Comprobamos si existe el directorio log de tpvfox
	if (!is_dir($ruta)){
		// Quiere decir que no existe la ruta log. 
		// la creo , aunque no se si debería.
		mkdir($ruta);
		$respuesta['error'][] = ' No existia ruta datos/log_tpvFox, se creo';
	}
	$nombreDir = $ruta.'/mod_impor_virtuemart/';
	// Comprobamos si existe el directorio del modulo dentro log
	if (!is_dir($nombreDir)){
		// Si no exite lo crea
		mkdir($nombreDir);
		$respuesta['error'][] = ' No existia ruta datos/log_tpvFox/mod_impor_virtuemart, se creo';
	}
	// Ahora creamos el fichero log_mod_impor_virtuemart_fecha.log
	$nombre_fic_log = $nombreDir.date("Ymd").'.log';
	$mensaje = '';
	// Comprobamo si existe el fichero, para poner mensaje inicio
	if (!file_exists($nombre_fic_log)){
		$mensaje .=  '======== Se crea fichero log para Importacion o Actualizacion de virtuemart. =========='."\n";
	}
	// Comprobamos si hubo errores en los directorios lo indicamos.
	if (isset($respuesta['error'])){
		$mensaje .= implode("\n",$respuesta['error'])."\n";
	}
	$mensaje .= $mensaje_log;
	// Escribimos mensaje.
	if($archivo = fopen($nombre_fic_log, "a")) {
		if(fwrite($archivo, date("d m Y H:m:s"). " ". $mensaje. "\n"))
			{
				$respuesta['correcto'] = "Grabado";
			}
			else
			{
				$respuesta['error'][] = 'No se podido crear el archivo'.$nombre_fic_log;
			}
	 
			fclose($archivo);
	}
	$respuesta['fichero'] = $nombre_fic_log;
	return $respuesta;
	
}
function GrabarRegistro ($BDTpv,$configuracion,$tipo){
	// @ Objetivo es grabar en la BDTpv de mod_importar_virt_reg
	// @ Paramentro: 
	//    configuracion: Json de configuracion.
	// 	  tipo : String no mas 10 caracteres... ( importar/actualizar )
	// Convertimos $configuracion a JSon preparado para grabar en msyql
	$resultado = array();
	$conf_escapa_sql = $BDTpv->real_escape_string($configuracion); 
	$Sql = 'INSERT INTO `mod_importar_virtuemart_reg`(`fecha`, `tipo`, `configuracion`) VALUES ("'.date("Y-m-d H:m:s").'","'.$tipo.'","'.$conf_escapa_sql.'")';
	$BDTpv->query($Sql);
	$resultado['consulta'] = $Sql;
		if (mysqli_error($BDTpv)){
			$resultado['consulta'] = $Sql;
			$resultado['error'] = $BDTpv->error_list;
		} 
	return $resultado;
}
function ObtenerUltimoRegistroMod($BDTpv){
	// @Objetivo : Obtener el ultimo registro de la tabla mod_importar_virtuemart_reg.
	// Recuerda que debe obtenerlo ante hacer el nuevo registros, ya que no tendría sentido hacerlo 
	// despues de guarda la actualizacion que estamos realizando, la fecha te daría la actual.
	$resultado = array();
	$Sql = "SELECT *  FROM mod_importar_virtuemart_reg ORDER BY id DESC LIMIT 1";
	$res = $BDTpv->query($Sql);
	if (mysqli_error($BDTpv) || $res->num_rows != 1){
		$resultado['consulta'] = $Sql;
		$resultado['error'] = $BDTpv->error_list;
		return $resultado; // Devolvemos array
	} 
	// No hubo error en consulta.
	$resultado = $res->fetch_assoc();

	return $resultado['fecha']; // Devolvemos String
}



?>
