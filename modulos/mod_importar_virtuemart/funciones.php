<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 TpvOlalla de Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero ,
 * @Descripcion	Funciones para importar datos de Virtuemart a Tpv
 * */
 function prepararTablaTempArticulosComp ($BDVirtuemart,$prefijoBD)
 {
	//@ Objetivo crear las tabla tmp_articulosCompleta temporale de las base datos (Virtuemart)
	// Esta funcion solo se hará al inicializar, es decir al empezar de 0
	// pero se puede aprovechar el código para cuando hagamos al actualizacion.
	
	$resultado = array();
	// En debug es mejor quitar TEMPORARY
	$sqlBDImpor = 'CREATE TEMPORARY TABLE tmp_articulosCompleta as
					select 1 as idTienda,
					CAST( c.virtuemart_product_id as CHAR(18))as crefTienda,
					cr.product_name as articulo_name,
					coalesce((
						select calc_value from '.$prefijoBD.'_virtuemart_calcs as e 
						WHERE e.virtuemart_calc_id = d.product_tax_id),0) as iva,
					c.product_gtin as codbarras,
					0 as beneficio,
					0 as costepromedio,
					case c.published
						when 0 then "NoPublicado"
						when 1 then "Activo"
						end as estado,
					d.product_price as pvpCiva,
					d.product_price as pvpSiva,
					0 as idProveedor,
					c.created_on as fecha_creado,
					c.modified_on as fecha_modificado
					from '.$prefijoBD.'_virtuemart_products as c
					left join '.$prefijoBD.'_virtuemart_products_es_es as cr
						on c.virtuemart_product_id = cr.virtuemart_product_id
					left join '.$prefijoBD.'_virtuemart_product_prices as d
						on c.virtuemart_product_id = d.virtuemart_product_id';
	if ($BDVirtuemart->query($sqlBDImpor) === TRUE) {
		// Se creó con éxito la tabla articulosCompleta en
		$resultado['BDVirtuemart']['tabla-creada'] = TRUE;
	}else {
		// Algo paso  al crear temporal tabla en BDimportar.. no salio bien. Prueba quitando temporal viendo la tabla;
		$resultado['error']['BDVirtuemart']['info_error'] =  $BDVirtuemart->error;
		$resultado['error']['BDVirtuemart']['consulta'] =  $sqlBDImpor;
		//~ $resultado
	}
	// Ahora calculamos el precio con iva, ya que virtuemart no nos lo facilita.
	$sqlUpdate = "UPDATE `tmp_articulosCompleta` SET `pvpCiva`=`pvpSiva`*(100+`iva`)/100";
	if ($BDVirtuemart->query($sqlUpdate) === TRUE) {
		// Se creó con éxito la tabla articulosCompleta en
		$resultado['BDVirtuemart']['RecalculoPrecioConIva'] = TRUE;
	}else {
		// Algo paso  al crear temporal tabla en BDimportar.. no salio bien. Prueba quitando temporal viendo la tabla;
		$resultado['error']['BDVirtuemart']['consulta'] = $sqlUpdate;
		$resultado['error']['BDVirtuemart']['info_error'] =  $BDVirtuemart->error;
		//~ $resultado
	}
	// Ahora añadimos el campo idArticulo auto incremental, recuerda que esto empieza desde 0
	$sqlAlter = "ALTER TABLE `tmp_articulosCompleta` ADD idArticulo INT( 11 ) AUTO_INCREMENT PRIMARY KEY FIRST";
	if ($BDVirtuemart->query($sqlAlter) === TRUE) {
		// Se creó con éxito la tabla articulosCompleta en
		$resultado['BDVirtuemart']['IdArticulo_creado'] = TRUE;
		$resultado['BDVirtuemart']['Num_articulos'] = $BDVirtuemart->affected_rows;

	}else {
		// Algo paso  al crear temporal tabla en BDimportar.. no salio bien. Prueba quitando temporal viendo la tabla;
		$resultado['error']['BDVirtuemart']['consulta'] = $sqlAlter;
		$resultado['error']['BDVirtuemart']['info_error'] =  $BDVirtuemart->error;
		//~ $resultado
	}
	// Ahora hacemos las comprobaciones para evitar errores.
	// [COMPROBAR QUE NO HAY CODBARRAS REPETIDOS]
	$repetidos = ComprobarCodbarras ($BDVirtuemart);
	if ( isset($repetidos['Codbarras_repetidos'])){
		if (count($repetidos['Codbarras_repetidos']) >0 ){
			$resultado['error']['ComprobarCodbarras'] = $repetidos;
		}
	} else {
		// Quiere decir que algo salio mal.
		$resultado['error']['ComprobarCodbarras'] = $repetidos;
	}
    
    
	return $resultado;
}

function  prepararInsertRegistroTpv($BDVirtuemart,$BDTpv,$prefijoBD,$tablas){
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
		$sql = 'SELECT '.$stringcampos.' FROM `tmp_articulosCompleta`';
		$articulos = $BDVirtuemart->query($sql);
		if ( $articulos != true) {
			// Algo salio mal, por lo que devolvemos error y consulta.
			$resultado['BDVirtuemart']['consulta'] = $sql;
			$resultado['BDVirtuemart']['error'] =  $BDVirtuemart->error;
			return $resultado;
		}
		$resultado['tabla'][$tabla]['Select'] = $sql; 
		// Obtenemos lo valores de estaa consulta, pero en grupos de mil
		$agruparValores = ObtenerGruposInsert($articulos,$camposObligatorios);
		$gruposvaloresArticulos = $agruparValores['Aceptados'];
		$resultado['tabla'][$tabla]['Num_Insert_hacer'] = count($gruposvaloresArticulos); 
		//~ if (count( $agruparValores['NDescartes']) === 0){
			//~ $resultado['tabla'][$tabla]['Ndescartado'] = 0;
//~ 
		//~ }
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
	//@ Objetivo es eliminar los productos(articulos) que existen en TPV
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
?>
