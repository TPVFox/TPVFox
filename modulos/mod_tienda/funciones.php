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

function insertarDatos($datos,$BDTpv,$tabla){
	$resultado = array();
	$resultado['Estado'] = '';
	//campos a insertar : NombreComercial, razonsocial, nif, direccion, ano, estado
	$nombrecomercial = $datos['NombreComercial'];
	$razonsocial = $datos['razonsocial'];
	$nif = $datos['nif'];
	$direccion = $datos['direccion'];
	$telefono = $datos['telefono'];
	$ano = $datos['ano'];
	$estado = $datos['estado'];
	
	
	$consulta = 'INSERT INTO '.$tabla.'( NombreComercial, razonsocial, nif, direccion, telefono, ano, estado ) VALUES ("'
				 .$nombrecomercial.'" , "'.$razonsocial.'" , "'.$nif.'" , "'.$direccion.'" , "'.$telefono.'" , '.$ano.' , "'.$estado.'")';
		
	//fin de comprobar existe username
	$result = $BDTpv->query($consulta);
	if (mysqli_error($BDTpv)){
		$resultado['consulta'] = $result;
		$resultado['error'] = $BDTpv->error_list;
		return $resultado;
	} 
	
	$resultado['consulta'] =$result;
	return $resultado;
}


//parametros: 
//datos array de post 
//BDTpv conexion bbdd tpv
//tabla en la que trabajar usuarios
//idSelecc , usuario concreto a modificar , check seleccionado en listaUsuarios
function modificarDatos($datos,$BDTpv,$tabla){
	//~ echo 'modificar usuario';
	$resultado = array();
	
	$nombrecomercial = $datos['nombrecomercial'];
	$razonsocial = $datos['razonsocial'];
	$nif = $datos['nif'];
	$direccion = $datos['direccion'];
	$telefono = $datos['telefono'];
	$ano = $datos['ano'];
	$estado = $datos['estado'];
	$idTienda = $datos['idtienda'];
	
	 
	 $sql ='UPDATE '.$tabla.' SET NombreComercial = "'.$nombrecomercial.'" , razonsocial = "'
				 .$razonsocial.'" , nif = "'.$nif.'" , direccion = "'.$direccion.'" , telefono = "'.$telefono.'" ,  ano = '
				 .$ano.' , estado = "'.$estado.'" WHERE idTienda ='.$idTienda;

	
	$consulta = $BDTpv->query($sql);
	
	//$resultado['consulta'] =$sql;
	$resultado['consulta'] =$consulta;

	return $resultado;
}

?>
