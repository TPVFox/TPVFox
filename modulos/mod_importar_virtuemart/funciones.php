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

function ObtenerTiendasWeb($BDTpv){
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
		if(fwrite($archivo, date("d m Y H:i:s"). " ". $mensaje. "\n"))
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


function ObtenerTiendaImport($BDTpv,$id){
	// Objetivo obtener datos de la tabla tienda para poder cargar el select de tienda On Line.
	$resultado = array();
	$sql = "SELECT * FROM `tiendas` WHERE `tipoTienda`='web' AND `idTienda` =".$id;
	$resultado['consulta'] = $sql;
	if ($consulta = $BDTpv->query($sql)){
		// Ahora debemos comprobar que cuantos registros obtenemos , si no hay ninguno
		// hay que indicar el error.
		if ($consulta->num_rows === 1) {
				while ($fila = $consulta->fetch_assoc()) {
				$resultado['items'][]= $fila;
				}
			
		} else {
			// Quiere decir que no hay tienda on-line (web) dada de alta o hay mas de una con el mis id..
			$resultado['error'] = 'Error a la hora obtener datos de la tienda Importar';
		}

	} else {
		// Quiere decir que hubo un error en la consulta.
		$resultado['error'] = 'Error en consulta';
		$resultado['numero_error_Mysql']= $BDTpv->errno;
	
	}
	
	return $resultado;
}
function ListadoProductosCompletoTPV($BDTpv){
	$resultado = array();
	$sql = "SELECT a.idArticulo,at.idVirtuemart,a.articulo_name,a.`iva`,at.estado,ap.pvpCiva,ap.pvpSiva,a.`fecha_creado`,a.`fecha_modificado` FROM `articulos` AS a LEFT JOIN articulosTiendas AS at ON at.idArticulo =a.idArticulo and at.idTienda = 4 LEFT JOIN articulosPrecios AS ap ON ap.idArticulo=a.idArticulo and at.idTienda = 4 GROUP BY at.idVirtuemart";
	
	$resultado['consulta'] = $sql;
	if ($consulta = $BDTpv->query($sql)){
		// Ahora debemos comprobar que cuantos registros obtenemos , si no hay ninguno
		// hay que indicar el error.
		if ($consulta->num_rows >0) {
				while ($fila = $consulta->fetch_assoc()) {
				$resultado['items'][]= $fila;
				}
			
		} else {
			// Quiere decir que no hay tienda on-line (web) dada de alta o hay mas de una con el mis id..
			$resultado['error'] = 'Error a la hora obtener datos de la tienda Importar';
		}

	} else {
		// Quiere decir que hubo un error en la consulta.
		$resultado['error'] = 'Error en consulta';
		$resultado['numero_error_Mysql']= $BDTpv->errno;
	
	}
	
	return $resultado;
	
	
}


function ObtenerDiferencias($Productos,$IdVirt){
	// Objetivo obtener array de diferencias.
	$diferencia = array();
	$i = 0;
	foreach ($Productos['Servidor'] as $producto){
		// Busco idVirtuemart de tabla Web en array columna de tpv,$IdVirt['Tpv']-> Array columna de idVirtuemart de tpv)
		$diff = array();
		$array_search = array_search($producto['idVirtuemart'],$IdVirt['Tpv']);
		if (gettype($array_search) === 'boolean'){
			// Quiere decir que no encontro, lo mas probable es que se un producto nuevo
			// ya que en tvp de momento no permitimos eliminar. :-)
				$diferencia[$i]['Diferencia'] = array('idVirtuemart' => $producto['idVirtuemart'],
							  'error' => 'Eliminado en tpv o creado en la web'
						);
				// Añadimos a array Nuevos el articulo nuevo encontrado.
				$diferencia[$i]['Servidor'] = $producto;
				// Ahora añadimos a Javascript datos ProductosNuevosWeb
				$diferencia[$i]['tipo'] = 'Nuevo_web';
				$i++;
				
			} else {
				// Ahora preparamos array para comparar.
				// En tpv quitamos idArticulo
				// Los campos de precios los redondeamos ( ya que no porque, pero hay milesimas diferencias).
				$Producto_tpv = $Productos['Tpv'][$array_search];
				$Producto_web = $producto;
				unset($Producto_tpv['idArticulo']);
				$campos_precios = array('pvpCiva','pvpSiva');
				foreach ($campos_precios as $precio){
					$Producto_tpv[$precio]= number_format($Producto_tpv[$precio],2);
					$Producto_web[$precio] = number_format($Producto_web[$precio],2);
				}
				// Obtenemos solo las claves de las diferencias.
				$diff = array_diff_assoc($Producto_tpv, $Producto_web);
				// [ DESCARTAMOS LO INNECESARIOS]
				if (isset($diff['fecha_modificado']) && count($diff)===1){
					// Lo elimino ya que solo hay diferencia de modificacion.
					unset($diff['fecha_modificado']);
				}
				
				// [ MONTAMOS ARRAY DIFERENCIAS]
				if (count($diff) >0){
				 $diferencia[$i]['Diferencia'] = $diff;
				 $diferencia[$i]['Servidor'] = $producto;
				 $diferencia[$i]['Tpv'] = $Productos['Tpv'][$array_search];
				 $diferencia[$i]['tipo'] = 'Modificado';
				 //~ $diferencia[$i]['NuevoDiferencia'] = array_diff_assoc($Producto_tpv,$Producto_web);

				 $i++;
				}
			
			}
	}
	// Ahora buscamos de momento solo los productos que existan en Web y no existan en TPV 
	// Es decir que se eliminaron en la web, ya que en tpv , no lo permitimos ( de momento).
	foreach ($Productos['Tpv'] as $producto){
		$array_search = array_search($producto['idVirtuemart'],$IdVirt['Web']);
		if (gettype($array_search) === 'boolean'){
			// Quiere decir que no encontro en tpv el idVirtuemart
			$diferencia[$i]['Diferencia']=	 array( 'idArticulo' 	=> $producto['idArticulo'],
													'idVirtuemart' 	=> $producto['idVirtuemart']
													);
			// Ahora identificamos si es nuevo en tpv o es eliminado.
			if ($producto['idVirtuemart']> 0 ){
				// Quiere decir que en algún momento si hubo idVirtuemart, por lo que entonces si se elimino
				$diferencia[$i]['Diferencia']['error']= 'Eliminado en la web';
				$diferencia[$i]['tipo'] = 'Eliminado_web';
			} else {
				// Quiere decir que es nuevo en tpv, ya que no tiene idVirtuemart, se tuvo crear en tpv
				$diferencia[$i]['Diferencia']['error']= 'Nuevo en tvp';
				$diferencia[$i]['tipo'] = 'Nuevo_tpv';
			}
			$diferencia[$i]['Tpv'] = $producto;
			$i++;	
		} 
	}
return $diferencia;
	
}


function InsertUnProductoTpv($BDTpv,$productoNuevo,$tienda_export,$tienda){
	// @Objetivo :
	// Es añadir un producto nuevo en BDTpv, que recojemos en una actualizacion.
	// Se inserta en las tablas:
	//  - articulos
	// 	- articulosPrecios
	//  - articulosTiendas
	// Faltaría por añadir tambien en codbarras, familias, pero de momento lo dejo pendiente.
	// idArticulo: Recuerda que es un autonumerico.
	
	$resultado = array();
	$sqlArticulo = 'INSERT INTO `articulos`(`iva`, `articulo_name`, `estado`, `fecha_creado`, `fecha_modificado`) VALUES ("'.$productoNuevo['iva'].'","'.$productoNuevo['articulo_name'].'","'.$productoNuevo['estado'].'","'.$productoNuevo['fecha_creado'].'","'.$productoNuevo['fecha_modificado'].'")';
	
	$resultado['consulta'][] = $sqlArticulo;
	if ($consulta = $BDTpv->query($sqlArticulo)){
		// Ahora obtener el idArticulo obtenido, para poder montar el resto de insert
		$Num_idArticulo = $BDTpv->insert_id;
		if ($Num_idArticulo > 0 ) {
			// Quiere decir que fue correcto el insert..
	
			$sqlArticuloPrecios = 'INSERT INTO `articulosPrecios`(`idArticulo`, `pvpCiva`, `pvpSiva`, `idTienda`) VALUES ("'.$Num_idArticulo.'","'.$productoNuevo['pvpCiva'].'","'.$productoNuevo['pvpSiva'].'","'.$tienda_export.'")';
			$resultado['consulta'][] = $sqlArticuloPrecios;
			$consultaPrecio = $BDTpv->query($sqlArticuloPrecios);
	
			$sqlArticuloPrecios = 'INSERT INTO `articulosPrecios`(`idArticulo`, `pvpCiva`, `pvpSiva`, `idTienda`) VALUES ("'.$Num_idArticulo.'","'.$productoNuevo['pvpCiva'].'","'.$productoNuevo['pvpSiva'].'","'.$tienda.'")';
			$resultado['consulta'][] = $sqlArticuloPrecios;
			$consultaPrecio = $BDTpv->query($sqlArticuloPrecios);
			// Ahora preparamos el insert para tienda.
			// Recuerda que el idTienda actual de momento no lo obtengo... :-)
			$sqlArticuloTiendas = 'INSERT INTO `articulosTiendas`(`idArticulo`,`idVirtuemart`, `estado`,`idTienda`) VALUES ("'.$Num_idArticulo.'","'.$productoNuevo['idVirtuemart'].'","'.$productoNuevo['estado'].'","'.$tienda_export.'")';
			$resultado['consulta'][] = $sqlArticuloTiendas;
			$consultaTienda = $BDTpv->query($sqlArticuloTiendas);
			// Recordar que el campo CREF depende de la configuracion.. por lo que demomento lo dejo asi, pero depende...no?
			$sqlArticuloTiendas = 'INSERT INTO `articulosTiendas`(`idArticulo`,`crefTienda`, `estado`,`idTienda`) VALUES ("'.$Num_idArticulo.'","'.$productoNuevo['idVirtuemart'].'","'.$productoNuevo['estado'].'","'.$tienda.'")';
			$resultado['consulta'][] = $sqlArticuloTiendas;
			$consultaTienda = $BDTpv->query($sqlArticuloTiendas);
		}
	} else {
		// Quiere decir que hubo un error en la consulta.
		$resultado['error'] = 'Error en consulta';
		$resultado['numero_error_Mysql']= $BDTpv->errno;
	
	}
	
	return $resultado;
	
}
function ComprobarDiferencias($diferencias,$producto_web,$producto_tpv){
	// @ Objetivo: Identificamos cual es la diferencia y comprobamos de los dos array es el mas actualizados.
	// 			   y añadimos array 'datos_coger' , indicando si tpv o servidor.
	//			   También si solo hay diferencia estado, sin fecha , se la añadimos.
	// @ $diferencias ( es un array arrays con las campos diferentes.
	$resultado = array();
	$dedonde = ''; // Indicamos de donde cogemos los datos.
	// Lo primero contamos cuantos 
	if (isset($diferencias['estado'])){
		// Si el cambio es estado, quiere decir que se cambio estado ( publicado o despublicado) sin entrar en producto, 
		// desde la lista productos de la web, ya que no hay diferencias en fecha_modificado
		if (count($diferencias) === 1){
			// Quiere decir que el cambio lo hizo dentro del listado de productos de la web, no entro en detalles producto.
			// Ponemos la fecha del producto modificacion de web, aunque daría igual, son iguales.
			$diferencias['fecha_modificado'] = $producto_web['fecha_modificado'];
		}
	}
	// Ahora comprobamos que datos son los buenos.
	if (isset($producto_tpv) && isset($producto_web)){
		// Ahora comprobamos cual es el mas actual.
		$fechaWeb = date_create($producto_web['fecha_modificado']);
		$fechaTpv = date_create($producto_tpv['fecha_modificado']);
		$interval = date_diff($fechaWeb,$fechaTpv);
		if ($interval->invert === 0 ){
			// Quiere decir que los datos mas actualizados son los tpv o son iguales..
			// de momento esta opción no hacemos nada.
			if ($producto_web['fecha_modificado'] === $producto_tpv['fecha_modificado']){
				// Son iguales ...
				$dedonde = 'web';
			} else {
			    // Son distinta.
			    $dedonde = 'tpv';
			}
		
		} else {
			// Quiere decir que la datos mas actualizados son los de la web.
			// en este caso si actualizamos en la tpv.
			// HAY RECORDAR QUE ESTO DEPENDE DE LA CONFIGURACIÓN POR DEFECTO DE LA TIENDA ( TPV) Y DE LA 
			// CONFIGURACION DE LA ACTUALIZACION QUE SI DEFINIMOS, PERO NO APLICAMOS ..
			$dedonde = 'web';
		}
	}
	// Ahora ponemos los datos de las diferencias que son mas actualizados.
	$nueva_diferencia = $diferencias;
	if ($dedonde === 'tpv'){
		$producto = $producto_tpv;
	}elseif ( $dedonde === 'web'){
		$producto = $producto_web;
	}
	// Recorremos los campos que tenemos diferencias y ponemos el valor mas actualizado
	foreach ($nueva_diferencia as $nombre => $valor){
		$diferencias[$nombre] =$producto[$nombre];
	}
	$diferencias['idArticulo'] = $producto_tpv['idArticulo'];
	$diferencias['idVirtuemart'] = $producto_web['idVirtuemart'];
	$diferencias['dedonde'] = $dedonde;
	// Ahora añadimos tipo diferencias
	$resultado = $diferencias;
	return $resultado;
	
}

function UpdateUnProductoTpv($BDTpv,$DiferenciasComprobadas,$tienda_export,$tienda){
	//@Objetivo es actualizar los datos en BDTpv
	$Sql = array();
	$campos = array();
	
	// Creamos UPDATE para cambiar estado y articulo_name en tabla Articulos
	// Esto habría que tener en cuenta la configuración, ya que a lo no siempre se querra cambiar el estado.
	if (isset($DiferenciasComprobadas['estado']) || isset($DiferenciasComprobadas['articulo_name'])){
		$Sql['0'] = 'UPDATE `articulos` SET ';
		if (isset($DiferenciasComprobadas['estado'])){
			$campo = 'estado = "'.$DiferenciasComprobadas['estado'].'"';
			array_push($campos,$campo);
		}
		if (isset($DiferenciasComprobadas['articulo_name'])){
			$campo = 'articulo_name = "'.$DiferenciasComprobadas['articulo_name'].'"';
			array_push($campos,$campo);
		}
		$Sql['0'] .= implode(',',$campos).' WHERE idArticulo='.$DiferenciasComprobadas['idArticulo'];
	}
	
	// Creamos UPDATE para cambiar estado en tabla articulosTiendas.
	// Esto habría que tener en cuenta la configuración, ya que a lo no siempre se querra cambiar el estado en las dos tiendas.
	// Ademas encuentro un error estructura en la BD ya que tenemos estado en en tabla articulos y articulosTienda que no tiene sentido.
	if (isset($DiferenciasComprobadas['estado'])){
		$Sql['1'] = 'UPDATE `articulosTiendas` SET estado = "'.$DiferenciasComprobadas['estado'].'"'.
					' WHERE idArticulo='.$DiferenciasComprobadas['idArticulo'];
	}
	// Creamos UPDATE para cambiar estado en tabla precios.
	// Lo mismo que los anteriores punto habría que tener en cuenta tema configuraciones... 
	if (isset($DiferenciasComprobadas['pvpCiva']) || isset($DiferenciasComprobadas['pvpSiva'])){
		$Sql['2'] = 'UPDATE `articulosPrecios` SET ';
		$coma = '';
		if (isset($DiferenciasComprobadas['pvpCiva'])){
			// Quiere decir que existe diferencia por lo añadimos 
			$Sql['2'] .= 'pvpCiva = "'.$DiferenciasComprobadas['pvpCiva'].'"';
			$coma =',';
		}
		if (isset($DiferenciasComprobadas['pvpSiva'])){
			$Sql['2'] .= $coma.' pvpSiva="'.$DiferenciasComprobadas['pvpSiva'].'"';
		}
		$Sql['2'] .=  ' WHERE idArticulo='.$DiferenciasComprobadas['idArticulo'];
	}
	// Ahora ejecutamos sentencias Sql
	foreach ($Sql as $consulta){
		$Modificar = $BDTpv->query($consulta);
		
	}
	
	
	
	return $Sql;		
		
}
		
		
		
		
		
	


?>
