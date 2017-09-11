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
	$grupoid=$datos['grupo'];
	$estado = $datos['estado'];
	
	//comprobar que username NO EXISTE al crear un nuevo usuario
	$buscarUsuario = 'SELECT * FROM '.$tabla.' WHERE username= "'.$username.'"';
	$res = $BDTpv->query($buscarUsuario);
	$numUser = mysqli_num_rows($res); //num usuarios que existen con ese nombre
	if (($numUser === 1) || ($username === '')){
		$resultado['error'] = 'error';
		
	} else {
			$consulta = 'INSERT INTO '.$tabla.'( username, password, fecha, group_id, estado, nombre ) VALUES ("'
				.$username.'" , "'.$passwrd.'" , "'.$fecha.'" , '.$grupoid.' , "'.$estado.'" , "'.$nombreEmpleado.'")';
		
	}//fin de comprobar existe username
	$result = $BDTpv->query($consulta);
	
	$resultado['consulta'] =$result;
	return $resultado;
}


//parametros: 
//datos array de post 
//BDTpv conexion bbdd tpv
//tabla en la que trabajar usuarios
//idSelecc , usuario concreto a modificar , check seleccionado en listaUsuarios
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
	
	
	
		if ($datos['password'] === 'password'){ //username NO se podra MODIFICAR
			//no actualizar contraseña, actualizamos 3 campos : estado, nombre y grupo id. 
			$sql ='UPDATE '.$tabla.' SET group_id ='.$grupoid.' , estado = "'
				.$estado.'" , nombre ="'.$nombre.'" WHERE id='.$idUsuario;
		
		} else { //actualimos 4 campos, password, username, estado, nombre y grupo id.
			$sql ='UPDATE '.$tabla.' SET group_id ='.$grupoid.' , estado = "'
					.$estado.'" , password ="'.$passwrd.'" , nombre ="'.$nombre.'" WHERE id='.$idUsuario;
		}
	
	$consulta = $BDTpv->query($sql);
	
	//$resultado['consulta'] =$sql;
	$resultado['consulta'] =$consulta;

	return $resultado;
}

?>
