<?php 

include_once "../../clases/FormasPago.php";
$CFormasPago=new FormasPago($BDTpv);

function obtenerClientes($BDTpv,$filtro) {
	// Function para obtener clientes y listarlos

	$clientes = array();
	$consulta = "Select * from clientes ".$filtro;//.$filtroFinal.$rango; 
	//$clientes['NItems'] = $Resql->num_rows;
	$i = 0;
	if ($Resql = $BDTpv->query($consulta)){			
		while ($fila = $Resql->fetch_assoc()) {
			$clientes[] = $fila;
		}
	}

	//$clientes ['consulta'] = $consulta;
	return $clientes;
}

function verSelec($BDTpv,$idSelec,$tabla){
	//ver seleccionado en check listado	
	// Obtener datos de un id de usuario.
	$where = 'idClientes = '.$idSelec;
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

function insertarCliente($datos,$BDTpv,$tabla){
	$resultado = array();
	$nombre = $datos['nombre'];  //conseguir todos los nombres de la tabla, recorrerlos y comprobar q no existe
	$razonsocial =$datos['razonsocial'];
	$nif = $datos['nif'];
	$direccion = $datos['direccion']; 
	
	$telefono=$datos['telefono'];
	$movil = $datos['movil'];
	$fax = $datos['fax'];
	$email =$datos['email'];
	$estado =$datos['estado'];
	
	//comprobar que razonsocial NO EXISTE al crear un nuevo usuario
	$buscarCliente = 'SELECT * FROM clientes WHERE razonsocial= "'.$razonsocial.'"';
	$res = $BDTpv->query($buscarCliente);
	$numClientes = mysqli_num_rows($res); //num usuarios que existen con ese nombre
	if ($numClientes === 1){
		// Si entro es porque existe ya el usuario o no mando nombre usuario
		$resultado['error'] = 'error';
		//razon social YA EXISTE	
	} else {
		$consulta = 'INSERT INTO '.$tabla.'( Nombre, razonsocial, nif, direccion, telefono, movil, fax, email, estado ) VALUES ("'
			.$nombre.'" , "'.$razonsocial.'" , "'.$nif.'" , "'.$direccion.'" , "'.$telefono.'" , "'.$movil.'" , "'.$fax.'" , "'.$email.'" , "'.$estado.'")';
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
function modificarCliente($datos,$BDTpv,$tabla){
	
	//~ echo 'modificar usuario';
	$resultado = array();	
	$nombre = $datos['nombre'];  //conseguir todos los nombres de la tabla, recorrerlos y comprobar q no existe
	$razonsocial =$datos['razonsocial'];
	$nif = $datos['nif'];
	$direccion = $datos['direccion']; 
	
	$idCliente =$datos['idCliente'];
	$telefono=$datos['telefono'];
	$movil = $datos['movil'];
	$fax = $datos['fax'];
	$email =$datos['email'];
	$estado =$datos['estado'];
	
	$sql ='UPDATE '.$tabla.' SET Nombre ="'.$nombre.'" , razonsocial ="'.$razonsocial.'" , nif ="'
			.$nif.'" , direccion = "'.$direccion.'" , telefono ="'.$telefono.'" , movil ="'.$fax.'" , email ="'.$email.'" , estado = "'
		.$estado.'" WHERE idClientes='.$idCliente;
	
	
	$consulta = $BDTpv->query($sql);
	
	//$resultado['consulta'] =$sql;
	$resultado['sql'] =$sql;

	return $resultado;
}


?>
