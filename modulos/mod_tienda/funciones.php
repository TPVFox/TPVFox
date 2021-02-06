<?php 


// Funciones para vista Tienda unica.
function obtenerTiendas($BDTpv) {
	// Function para obtener tiendas y listarlos

	$tiendas = array();
	$consulta = "Select * from tiendas";
	$res = $BDTpv->query($consulta);
	$tiendas['NItems'] = $res->num_rows;
	$i = 0;
	
	while ($tienda = $res->fetch_assoc()) {
			$tiendas['items'][$i]['idTienda'] = $tienda['idTienda'];
			$tiendas['items'][$i]['NombreComercial'] = $tienda['NombreComercial'];
            $tiendas['items'][$i]['tipoTienda'] = $tienda['tipoTienda'];
			$tiendas['items'][$i]['razonsocial'] = $tienda['razonsocial'];
			$tiendas['items'][$i]['nif'] = $tienda['nif'];
			$tiendas['items'][$i]['direccion'] = $tienda['direccion'];			
			$tiendas['items'][$i]['telefono'] = $tienda['telefono'];
			$tiendas['items'][$i]['ano'] = $tienda['ano'];
			$tiendas['items'][$i]['estado'] = $tienda['estado'];
	$i = $i+1;
	}

	$tiendas ['consulta'] = $consulta;
	return $tiendas;
}
//ver seleccionado en check listado //comparador => $id = $idCheck
function verSelec($BDTpv,$comparador,$tabla){
	$consulta = 'SELECT * FROM '. $tabla.' WHERE '.$comparador;
	$unaOpc = $BDTpv->query($consulta);
	if (mysqli_error($BDTpv)) {
		$fila = $unaOpc;
	} else {
		$fila = $unaOpc->fetch_assoc();
	}
	//~ $fila['sql'] = $unaOpc;
	$fila['consulta'] = $consulta;
	return $fila ;
}

function insertarDatos($datos,$BDTpv,$tabla,$campos){
	$resultado = array();
	// Obtenemos valores comunes: razonsocial, nif, telefono, estado
	$valores = '"'.$datos['razonsocial'].'" , "'.$datos['nif'].'" , "'.$datos['telefono']
				.'" , "'.$datos['tipoTienda'].'","'.$datos['estado'].'"';
	// Recuerda que los campos es una array indexado [id].
	if ($datos['tipoTienda'] === 'web'){
		$valores .= ',"'.$datos['dominio'].'","'.$datos['key_api'].'"';
		
	} else {
		$valores .=	',"'.$datos['nombrecomercial'].'","'.$datos['direccion'].'","'.$datos['ano'].'"';	
	}
	
	// Ahora montamos consulta.
	
	$consulta = 'INSERT INTO '.$tabla.'('.implode(',',$campos).') VALUES ('.$valores.')';
		
	$result = $BDTpv->query($consulta);
	if (mysqli_error($BDTpv)){
		$resultado['error'] = $BDTpv->error_list;
	} 
	
	$resultado['consulta'] =$consulta;
	return $resultado;
}


//parametros: 
//datos array de post 
//BDTpv conexion bbdd tpv
//tabla en la que trabajar usuarios
//idSelecc , usuario concreto a modificar , check seleccionado en listaUsuarios
function modificarDatos($datos,$BDTpv,$tabla,$idTienda){
	// @ Parametros:
	//    $datos= Array con datos de tabla que vamos a guarda.
	//    $tabla= nombre de la tabla.
	$resultado = array();
	
	
	// [PENDIENTE VER COMO HACER UPDATE AUTOMATICO, SEGUN TIPO DE TIENDA...

	$resultado['Rdatos']= $datos;
	$updateSet = array();
	foreach ($datos as $key => $dato){
		$updateSet[]= $key.'="'.$dato.'"';
	}
	$envioUpdate = implode(',',$updateSet);
	
	
	 
	$sql ='UPDATE '.$tabla.' SET '.$envioUpdate.' WHERE idTienda ='.$idTienda;

	$resultado['consulta']= $sql;
	if ($consulta = $BDTpv-> query($sql)){
		// Ya modificamos.
		// Comprobamos que solo modifique un registro, si son mas hubo error grave.
		$resultado['Num_registros'] = $BDTpv->affected_rows;
		if ($resultado['Num_registros'] > 1){
			// Quiere decir que el resultado esta mal, ya que cambio dos registros.
			$resultado['error'] = 'Error, modifico dos registros';
		}
	} else {
		// Quiere decir que hubo un error en la consulta.
		$resultado['error'] = 'Error en consulta';
		$resultado['numero_error_Mysql']= $BDTpv->errno;
	
	}
	

	return $resultado;
}

?>
