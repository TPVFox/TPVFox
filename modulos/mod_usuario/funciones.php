<?php 


// Funciones para vista Recambio unico.
function obtenerUsuarios($BDTpv) {
	// Function para obtener usuarios y listarlos

	$usuarios = array();
	$consulta = "Select * from usuarios";
	$ResUsuarios = $BDTpv->query($consulta);
	$usuarios['NItems'] = $ResUsuarios->num_rows;
	$i = 0;
	
	while ($usuario = $ResUsuarios->fetch_assoc()) {
			$usuarios['items'][$i]['id'] = $usuario['id'];
			$usuarios['items'][$i]['username'] = $usuario['username'];
			$usuarios['items'][$i]['nombre'] = $usuario['nombre'];
			$usuarios['items'][$i]['fecha'] = $usuario['fecha'];
			$usuarios['items'][$i]['group_id'] = $usuario['group_id'];
			$usuarios['items'][$i]['block'] = $usuario['estado'];
	$i = $i+1;
	}

	$usuarios ['consulta'] = $consulta;
	return $usuarios;
}
//ver seleccionado en check listado
function verSelec($BDTpv,$idUser,$tabla){
	$consulta = 'SELECT * FROM '. $tabla.' WHERE '.$idUser;
	$unaOpc = $BDTpv->query($consulta);
	if (mysqli_error($BDTpv)) {
		$fila = $unaOpc;
	} else {
		$fila = $unaOpc->fetch_assoc();
	}
	$fila['consulta'] = $consulta;
	return $fila ;
}

function insertarDatos($datos,$BDTpv,$tabla){
	$resultado = array();
	$resultado['Estado'] = '';
	//campos modificables : username, nombreEmpleado, contraseña
	//
	$username = $datos['username'];  //conseguir todos los nombres de la tabla, recorrerlos y comprobar q no existe
	$nombreEmpleado =$datos['nombreEmpleado'];
	$fecha = $datos['fecha'];
	$passwrd = md5($datos['password']); //encripto psw para crear
	
	$idUsuario =$datos['idUsuario'];
	$grupoid=$datos['grupoid'];
	$estado = $datos['estado'];
	
	//$consulta = 'INSERT INTO '.$tabla.'username,password,fecha,nombre  VALUES '.$SqlInsert;
	//~ $resp_insertar = $BDImportDbf->query($consulta);
	//~ if (count($resultado['Errores']) > 0 ){
		//~ $resultado['Estado'] = 'Incorrecto';
	//~ } else {
		//~ //comprobar si el insert es correcto, la resp_insert
		//~ $resultado['Estado'] = 'Correcto';
	//~ }
	
	
	return $resultado;
}

function modificarUsuario($datos,$BDTpv,$tabla){
	//~ echo 'modificar usuario';
	$resultado = array();
	$username = $datos['username'];  //conseguir todos los nombres de la tabla, recorrerlos y comprobar q no existe
	$nombre =$datos['nombreEmpleado'];
	$fecha = $datos['fecha'];		//NO SE MODIFICA es la de alta
	
	$passwrd = md5($datos['password']); //encripto psw para crear
	
	$idUsuario =$datos['idUsuario']; //NO SE MODIFICA autonumerica
	$grupoid=$datos['grupo'];
	$estado = $datos['estado'];
	$id =$datos['idUsuario'];
	
	if ($datos['password'] === 'password'){
		//no actualizar contraseña
		$sql ='UPDATE '.$tabla.' SET username = "'.$username.'", group_id ='
				.$grupoid.' , estado = "'.$estado.'" , nombre ="'.$nombre.'" WHERE id='.$id;
	
	}
	//$resultado['consulta'] =$sql;
	$resultado['consulta'] =$sql;

	return $resultado;
}

?>
