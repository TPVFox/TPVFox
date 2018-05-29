<?php 


function obtenerProveedores($BDTpv,$filtro) {
	// Function para obtener proveedores y listarlos
	//tener en cuenta el  paginado con parametros:  ,$filtro

	$proveedores = array();
	$consulta = "Select * from proveedores ".$filtro; 
	$Resql = $BDTpv->query($consulta);
	$proveedores['NItems'] = $Resql->num_rows;
	$i = 0;
//~ echo '<pre>';
//~ echo $consulta;
//~ echo '</pre>';	
	while ($proveedor = $Resql->fetch_assoc()) {			
		$clientes['items'][$i] = $proveedor;
		$i++;
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


function htmlTablaGeneral($datos, $HostNombre, $dedonde){
	if(count($datos)>0){
	switch($dedonde){
			case 'factura':
				$url=$HostNombre.'/modulos/mod_compras/factura.php?id=';
			break;
			case 'albaran':
				$url=$HostNombre.'/modulos/mod_compras/albaran.php?id=';
			break;
			case 'pedido':
				$url=$HostNombre.'/modulos/mod_compras/pedido.php?id=';
			break;
	}
	$html='<table class="table table-striped">
		<thead>
			<tr>
				<td>Fecha</td>
				<td>Número</td>
				<td>Total</td>
			</tr>
		</thead>
		<tbody>';
	
		foreach($datos as $dato){
			$html.='<tr>'.
				'<td>'.$dato['fecha'].'</td>'.
				'<td><a href="'.$url.$dato['id'].'">'.$dato['num'].'</a></td>'.
				'<td>'.$dato['total'].'</td>'.
			'</tr>';
		}
		$html.='</tbody></table>';
	}else{
		$html='<div class="alert alert-info">Este proveedor no tiene '.$dedonde.'</div>';
	}
	
	return $html;
}
function htmlPanelDesplegable($num_desplegable,$titulo,$body){
	// @ Objetivo:
	// Montar html de desplegable.
	// @ Parametros:
	// 		$num_desplegable -> (int) que indica el numero deplegable para un correcto funcionamiento.
	// 		$titulo-> (string) El titulo que se muestra en desplegable
	// 		$body-> (String) lo que contiene el desplegable.
	// Ejemplo tomado de:
	// https://www.w3schools.com/bootstrap/tryit.asp?filename=trybs_collapsible_panel&stacked=h 
	
	$collapse = 'collapse'.$num_desplegable;
	$html ='<div class="panel panel-default">'
			.		'<div class="panel-heading">'
			.			'<h2 class="panel-title">'
			.			'<a data-toggle="collapse" href="#'.$collapse.'">'
			.			$titulo.'</a>'
			.			'</h2>'
			.		'</div>'
			.		'<div id="'.$collapse.'" class="panel-collapse collapse">'
			.			'<div class="panel-body">'
			.				$body
			.			'</div>'
			.		'</div>'
			.'</div>';
	return $html;
	 
}

function guardarProveedor($datosPost, $BDTpv){
	$Proveedor= new ClaseProveedor($BDTpv);
	$direccion="";
	$telefono="";
	$fax="";
	$movil="";
	$email="";
	$estado="";
	if(isset($datosPost['direccion'])){
		$direccion=$datosPost['direccion'];
	}
	if(isset($datosPost['telefono'])){
		$telefono=$datosPost['telefono'];
	}
	if(isset($datosPost['fax'])){
		$fax=$datosPost['fax'];
	}
	if(isset($datosPost['movil'])){
		$movil=$datosPost['movil'];
	}
	if(isset($datosPost['email'])){
		$email=$datosPost['email'];
	}
	if(isset($datosPost['estado'])){
		$estado=$datosPost['estado'];
	}
	$datos=array(
		'nombrecomercial'=>$datosPost['nombrecomercial'],
		'razonsocial'=>$datosPost['razonsocial'],
		'nif'=>$datosPost['nif'],
		'direccion'=>$direccion,
		'telefono'=>$telefono,
		'fax'=>$fax,
		'movil'=>$movil,
		'email'=>$email,
		'estado'=>$estado
	);
	if($datosPost['idProveedor']>0){
		$mod=$Proveedor->modificarDatosProveedor($datos);
	}else{
		
	}
	return $resultado;
}
?>
