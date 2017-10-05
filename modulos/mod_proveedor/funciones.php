<?php 


function obtenerProveedores($BDTpv,$LimitePagina ,$desde,$filtro) {
	// Function para obtener proveedores y listarlos
	//tener en cuenta el  paginado con parametros: $LimitePagina ,$desde,$filtro

//para evitar repetir codigo
	$Controler = new ControladorComun; 
	$campoBD = 'nombrecomercial';
	$campo2BD = 'razonsocial';
	$rangoFiltro = $Controler->paginacionFiltroBuscar($BDTpv,$filtro,$LimitePagina,$desde,$campoBD,$campo2BD);
	$rango=$rangoFiltro['rango'];
	$filtroFinal=$rangoFiltro['filtro'];
//fin paginacion y filtro de busqueda 


	$proveedores = array();
	$consulta = "Select * from proveedores ".$filtroFinal.$rango; 
	$Resql = $BDTpv->query($consulta);
	$proveedores['NItems'] = $Resql->num_rows;
	$i = 0;
//~ echo '<pre>';
//~ echo $consulta;
//~ echo '</pre>';	
	while ($proveedor = $Resql->fetch_assoc()) {			
			$clientes['items'][$i]['idProveedor'] = $proveedor['idProveedor'];
			$clientes['items'][$i]['nombrecomercial'] = $proveedor['nombrecomercial'];
			$clientes['items'][$i]['razonsocial'] = $proveedor['razonsocial'];
			$clientes['items'][$i]['nif'] = $proveedor['nif'];
			$clientes['items'][$i]['direccion'] = $proveedor['direccion'];
			$clientes['items'][$i]['telefono'] = $proveedor['telefono'];
			$clientes['items'][$i]['movil'] = $proveedor['movil'];
			$clientes['items'][$i]['fax'] = $proveedor['fax'];
			$clientes['items'][$i]['email'] = $proveedor['email'];
			$clientes['items'][$i]['fechaalta'] = $proveedor['fechaalta'];
			$clientes['items'][$i]['estado'] = $proveedor['estado'];
	$i = $i+1;
	}

	$clientes ['consulta'] = $consulta;
	return $clientes;
}

function verSelec($BDTpv,$idSelec,$tabla){
	//ver seleccionado en check listado	
	// Obtener datos de un id de usuario.
	$where = 'idProveedor = '.$idSelec;
	$consulta = 'SELECT * FROM '. $tabla.' WHERE '.$where;
	
	$unaOpc = $BDTpv->query($consulta);
	if (mysqli_error($BDTpv)) {
		$fila['error'] = 'Error en la consulta '.$BDTpv->errno;
	} else {
		if ($unaOpc->num_rows > 0){
			$fila = $unaOpc->fetch_assoc();
		} else {
			$fila['error']= ' No se a encontrado cliente';
		}
	}
	$fila['Nrow']= $unaOpc->num_rows;
	$fila['sql'] = $consulta;
	return $fila ;
}

function insertarProveedor($datos,$BDTpv,$tabla){
	$resultado = array();
	$nombre = $datos['nombrecomercial'];  //conseguir todos los nombres de la tabla, recorrerlos y comprobar q no existe
	$razonsocial =$datos['razonsocial'];
	$nif = $datos['nif'];
	$direccion = $datos['direccion']; 
	
	$telefono=$datos['telefono'];
	$movil = $datos['movil'];
	$fax = $datos['fax'];
	$email =$datos['email'];
	$fechaalta =$datos['fechaalta'];
	$idUsuario =$datos['idUsuario']; //COGER DE LA SESION 
	$estado =$datos['estado'];
	
	//comprobar que razonsocial NO EXISTE al crear un nuevo usuario
	$buscarDatos = 'SELECT * FROM proveedores WHERE razonsocial= "'.$razonsocial.'"';
	$res = $BDTpv->query($buscarDatos);
	$numClientes = mysqli_num_rows($res); //num usuarios que existen con ese nombre
	if ($numClientes === 1){
		// Si entro es porque existe ya el usuario o no mando nombre usuario
		$resultado['error'] = 'error';
		//razon social YA EXISTE	
	} else {
		$consulta = 'INSERT INTO '.$tabla.'( nombrecomercial, razonsocial, nif, direccion, telefono, fax, movil, email, fechaalta, idusuario, estado ) VALUES ("'
			.$nombre.'" , "'.$razonsocial.'" , "'.$nif.'" , "'.$direccion.'" , "'.$telefono.'" , "'.$fax.'" , "'.$movil.'" , "'.$email.'" , "'.$fechaalta.'" , "'.$idUsuario.'" , "'.$estado.'")';
		if ($BDTpv->query($consulta) === true){
			$resultado['id'] = $BDTpv->insert_id;	//crea id en bbddd 
		} else {
			// Quiere decir que hubo error en insertar en clientes
			$resultado['error'] = 'Error en Insert de cliente Numero error:'.$BDTpv->errno;
			$resultado['sql'] = $consulta;

		}
		
	}
	//~ $resultado['consulta'] =$InsertSlq;
	return $resultado;
}


//parametros: 
//datos array de post ,importante NAME de los inputs
//BDTpv conexion bbdd tpv
//tabla en la que trabajar usuarios
//idSelecc , usuario concreto a modificar , check seleccionado en listaUsuarios
function modificarProveedor($datos,$BDTpv,$tabla){
	
	//~ echo 'modificar usuario';
	$resultado = array();	
	$nombre = $datos['nombrecomercial'];  //conseguir todos los nombres de la tabla, recorrerlos y comprobar q no existe
	$razonsocial =$datos['razonsocial'];
	$nif = $datos['nif'];
	$direccion = $datos['direccion']; 
	
	$id =$datos['idProveedor'];
	$telefono=$datos['telefono'];
	$movil = $datos['movil'];
	$fax = $datos['fax'];
	$email =$datos['email'];
	$estado =$datos['estado'];
	//fechaalta e idUsuario no se deberian modificar
	
	$sql ='UPDATE '.$tabla.' SET nombrecomercial ="'.$nombre.'" , razonsocial ="'.$razonsocial.'" , nif ="'
			.$nif.'" , direccion = "'.$direccion.'" , telefono ="'.$telefono.'" , fax ="'.$fax.'" , movil ="'.$movil.'" , email ="'.$email.'" , estado = "'
		.$estado.'" WHERE idProveedor='.$id;
	
	
	$consulta = $BDTpv->query($sql);
	
	//$resultado['consulta'] =$sql;
	$resultado['sql'] =$sql;

	return $resultado;
}

?>
