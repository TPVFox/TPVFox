<?php 


function obtenerProductos($BDTpv,$LimitePagina ,$desde,$filtro) {
	// Function para obtener productos y listarlos
	//tener en cuenta el  paginado con parametros: $LimitePagina ,$desde,$filtro
	$resultado = array();
	//inicio paginacion filtro
	//para evitar repetir codigo
	$Controler = new ControladorComun; 
	$campoBD = 'articulo_name';
	//~ $rangoFiltro = $Controler->paginacionFiltroBuscar($BDTpv,$filtro,$LimitePagina,$desde,$campoBD,$campo2BD='');
	//~ $rango=$rangoFiltro['rango'];
	//~ $filtroFinal=$rangoFiltro['filtro'];
	//fin paginacion y filtro de busqueda 

	$consulta = "SELECT a.*, c.`codBarras`, c.`idArticulo`, p.`idArticulo`, p.`pvpCiva` FROM `articulos` AS a "
				."LEFT JOIN `articulosCodigoBarras` AS c " 
				."ON c.`idArticulo` = a.`idArticulo` " 
				."LEFT JOIN `articulosPrecios` AS p "
				."ON p.`idArticulo` = a.`idArticulo`".$filtro;//$filtroFinal.$rango; 
	
	if ($ResConsulta = $BDTpv->query($consulta)){			
		while ($fila = $ResConsulta->fetch_assoc()) {
			$resultado[] = $fila;
		}
	}
	
	//$resultado['sql'] = $consulta;
	return $resultado;
}

//ver seleccionado en check listado en vista producto
//~ function verSelec($BDTpv,$idUser,$tabla){
	//~ // Obtener datos de un id de usuario.
	//~ $where = 'u.id = '.$idUser. ' and i.idUsuario ='.$idUser;
	//~ $consulta = 'SELECT u.*,i.numticket,i.tempticket FROM '. $tabla.' as u, indices as i WHERE '.$where;
	//~ $unaOpc = $BDTpv->query($consulta);
	//~ if (mysqli_error($BDTpv)) {
		//~ $fila['error'] = 'Error en la consultar'.$BDTpv->errno;
	//~ } else {
		//~ if ($unaOpc->num_rows > 0){
			//~ $fila = $unaOpc->fetch_assoc();
		//~ } else {
			//~ $fila['error']= ' No se a encontrado usuario';
		//~ }
	//~ }
	//~ $fila['Nrow']= $unaOpc->num_rows;
	//~ $fila['consulta'] = $consulta;
	//~ return $fila ;
//~ }

//~ function insertarUsuario($datos,$BDTpv,$idTienda,$tabla){
	//~ $resultado = array();
	//~ //campos modificables : username, nombreEmpleado, contraseña
	//~ //
	//~ $username = $datos['username'];  //conseguir todos los nombres de la tabla, recorrerlos y comprobar q no existe
	//~ $nombreEmpleado =$datos['nombreEmpleado'];
	//~ $fecha = $datos['fecha'];
	//~ $passwrd = md5($datos['password']); //encripto psw para crear
	
	//~ $idUsuario =$datos['idUsuario'];
	//~ $grupoid=$datos['grupo'];
	//~ $estado = $datos['estado'];
	
	//~ //comprobar que username NO EXISTE al crear un nuevo usuario
	//~ $buscarUsuario = 'SELECT * FROM usuarios WHERE username= "'.$username.'"';
	//~ $res = $BDTpv->query($buscarUsuario);
	//~ $numUser = mysqli_num_rows($res); //num usuarios que existen con ese nombre
	//~ if (($numUser === 1) || ($username === '')){
		//~ $resultado['error'] = 'error';
		
	//~ } else {
		//~ $consulta = 'INSERT INTO '.$tabla.'( username, password, fecha, group_id, estado, nombre ) VALUES ("'
			//~ .$username.'" , "'.$passwrd.'" , "'.$fecha.'" , '.$grupoid.' , "'.$estado.'" , "'.$nombreEmpleado.'")';
		//~ $BDTpv->query($consulta);
		//~ $resultado['id'] = $BDTpv->insert_id;
		//~ // Ahora creamos las claves indices de este usuario para esta tienda.
		//~ $InsertSlq= 'INSERT INTO `indices`(`idTienda`, `idUsuario`, `numticket`, `tempticket`) VALUES ('.$idTienda.','.$resultado['id'].',0,0)';
		//~ $BDTpv->query($InsertSlq);
		//~ if (mysqli_error($BDTpv)) {
		//~ $resultado['error'] = 'Error que nunca debería suceder'.$BDTpv->errno;
		//~ } 
	//~ }
	//$resultado['consulta'] =$InsertSlq;
	//~ return $resultado;
//~ }


//parametros: 
//datos array de post 
//BDTpv conexion bbdd tpv
//tabla en la que trabajar usuarios
//idSelecc , usuario concreto a modificar , check seleccionado en listaUsuarios
//~ function modificarUsuario($datos,$BDTpv,$tabla){
	//echo 'modificar usuario';
	//~ $resultado = array();
	//~ $username = $datos['username'];  //conseguir todos los nombres de la tabla, recorrerlos y comprobar q no existe
	//~ $nombre =$datos['nombreEmpleado'];
	//~ $fecha = $datos['fecha'];		//NO SE MODIFICA es la de alta
	
	//~ $passwrd = md5($datos['password']); //encripto psw para crear
	
	//~ $idUsuario =$datos['idUsuario']; //NO SE MODIFICA autonumerica
	//~ $grupoid=$datos['grupo'];
	//~ $estado = $datos['estado'];
	//~ $id =$datos['idUsuario'];
	
	//~ if ($datos['password'] === 'password'){ //username NO se podra MODIFICAR
		//~ //no actualizar contraseña, actualizamos 3 campos : estado, nombre y grupo id. 
		//~ $sql ='UPDATE '.$tabla.' SET group_id ='.$grupoid.' , estado = "'
			//~ .$estado.'" , nombre ="'.$nombre.'" WHERE id='.$idUsuario;
	//~ } else { //actualimos 4 campos, password, username, estado, nombre y grupo id.
		//~ $sql ='UPDATE '.$tabla.' SET group_id ='.$grupoid.' , estado = "'
			//~ .$estado.'" , password ="'.$passwrd.'" , nombre ="'.$nombre.'" WHERE id='.$idUsuario;
	//~ }
	
	//~ $consulta = $BDTpv->query($sql);
	
	//~ //$resultado['consulta'] =$sql;
	//~ $resultado['consulta'] =$consulta;

	//~ return $resultado;
//~ }

?>
