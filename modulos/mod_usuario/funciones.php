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
	// Obtener datos de un id de usuario.
	$where = 'u.id = '.$idUser. ' and i.idUsuario ='.$idUser;
	$consulta = 'SELECT u.*,i.numticket,i.tempticket FROM '. $tabla.' as u, indices as i WHERE '.$where;
	$unaOpc = $BDTpv->query($consulta);
	if (mysqli_error($BDTpv)) {
		$fila['error'] = 'Error en la consultar'.$BDTpv->errno;
	} else {
		if ($unaOpc->num_rows > 0){
			$fila = $unaOpc->fetch_assoc();
		} else {
			$fila['error']= ' No se a encontrado usuario';
		}
	}
	$fila['Nrow']= $unaOpc->num_rows;
	$fila['consulta'] = $consulta;
	return $fila ;
}

function insertarUsuario($datos,$BDTpv,$idTienda,$tabla){
	$resultado = array();
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
	$buscarUsuario = 'SELECT * FROM usuarios WHERE username= "'.$username.'"';
	$res = $BDTpv->query($buscarUsuario);
	$numUser = mysqli_num_rows($res); //num usuarios que existen con ese nombre
	if (($numUser === 1) || ($username === '')){
		// Si entro es porque existe ya el usuario o no mando nombre usuario
		$resultado['error'] = 'error';
		$resultado['sql'] = $buscarUsuario;		
	} else {
		$consulta = 'INSERT INTO '.$tabla.'( username, password, fecha, group_id, estado, nombre ) VALUES ("'
			.$username.'" , "'.$passwrd.'" , "'.$fecha.'" , '.$grupoid.' , "'.$estado.'" , "'.$nombreEmpleado.'")';
		if ($BDTpv->query($consulta) === true){
			$resultado['id'] = $BDTpv->insert_id;
			// Entonces inserto en indice.
			// Ahora creamos las claves indices de este usuario para esta tienda.
			$InsertSlq= 'INSERT INTO `indices`(`idTienda`, `idUsuario`, `numticket`, `tempticket`) VALUES ('.$idTienda.','.$resultado['id'].',0,0)';
			if ($BDTpv->query($InsertSlq) !== true) {
				// Quiere decir que hubo error en insertar en indice
				$resultado['error'] = 'Error en Insert en indice -1 Numero error'.$BDTpv->errno;
				$resultado['consulta'] = $InsertSlq;
			} 
		
		} else {
			// Quiere decir que hubo error en insertar en usuarios
			$resultado['error'] = 'Error en Insert de usuario-2 Numero error:'.$BDTpv->errno;
			$resultado['consulta'] = $consulta;

		}
		
	}
	//~ $resultado['consulta'] =$InsertSlq;
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
function htmlTablaGeneral($datos, $HostNombre, $dedonde){
	if($datos<>0){
	$html='	<table class="table table-striped">
		<thead>
			<tr>
				
				<td>Nombre Modulo</td>
				<td>Fecha</td>
				<td>Eliminar</td>
			</tr>
		</thead>
		<tbody>';
	$i=0;
		foreach($datos as $dato){
			$html.='<tr>'.
				'<td>'.$dato['nombre_modulo'].'</td>'.
				'<td>'.$dato['fecha'].'</td>'.
				'<td><a class="glyphicon glyphicon-trash" onclick="eliminarConfiguracionModulo('.$dato['idusuario'].', '."'".$dato['nombre_modulo']."'".')"></a></td>'.
			'</tr>';
			$i++;
			if($i==10){
				break;
			}
		}
		$html.='</tbody></table>';
	}else{
		$html='<div class="alert alert-info">Este Usuario no tiene configuración de modulos</div>';
	}
	
	return $html;
}
function htmlTablaIncidencias($incidenciasUsuario){
    if(count($incidenciasUsuario)>0){
        $html='	<table class="table table-striped">
		<thead>
			<tr>
				
				<td>Número</td>
				<td>Fecha</td>
				<td>Dedonde</td>
                <td>Mensaje</td>
			</tr>
		</thead>
		<tbody>';
	$i=0;
        foreach($incidenciasUsuario as $dato){
            $html.='<tr>
                <td>'.$dato['num_incidencia'].'</td>
                <td>'.$dato['fecha_creacion'].'</td>
                <td>'.$dato['dedonde'].'</td>
                <td>'.$dato['mensaje'].'</td>
            </tr>';
            $i++;
                if($i==10){
                    break;
                }
        }
        $html.='</tbody></table>';
    }else{
        $html='<div class="alert alert-info">Este Usuario no tiene incidencias sin Revisar</div>';
    }
    return $html;
    
}
function htmlPermisosUsuario($permisosUsuario, $admin, $ClasePermisos, $Usuarios){
    //OBjetivo: Mostrar los inputs con los permisos anidados
    $permisos=array();
    $modulo="";
    $vista="";
    $accion="";
    $html="";
    $permiso=0;
    $checked="";
    $i=0;
    $bloquear="";
    if($admin == 0){ //Si no es administrador tiene los inputs bloqueados
        $bloquear='readonly="readonly" disabled';
    }else{
         $html.='Copiar permisos de : <select id="usuario">';
         foreach ($Usuarios['datos'] as $usuario){
             $html.='<option value="'.$usuario['id'].'">'.$usuario['username'].'</option>';
         }
         
         $html.='</select> <a class="btn btn-primary" onclick="copiarPermisosUsuario()" >Copiar Permisos</a><br>';
    }
   
   if(count($permisosUsuario)>0){
        foreach ($permisosUsuario as $permiso){ //Recorremos todos los permisos
            if($permiso['permiso']==1){ //Si el permiso es 1 es input está marcado
                $checked="checked";
            }else{
                 $checked="";
            }
            
            if($modulo<>$permiso['modulo']){
                $modulo=$permiso['modulo'];
                //De todos vamos obteniendo la descripción del acces
                $descripcion=$ClasePermisos->ObtenerDescripcion($permiso['modulo'], $permiso);
                $html.='<input type="checkbox" id="modulo_'.$i.'" value=1 class="permiso_'.$i.'" name="permiso_'.$i.'" '.$checked.' '.$bloquear.'><b>'.$descripcion.'</b><br>';
            }else{
                if($vista<>$permiso['vista']){
                   
                $vista=$permiso['vista'];
                $descripcion=$ClasePermisos->ObtenerDescripcion($permiso['vista'], $permiso);
                $html.='&nbsp;&nbsp;&nbsp;<input type="checkbox" value=1 class="permiso_'.$i.'" id="vista_'.$i.'" name="permiso_'.$i.'" '.$checked.' '.$bloquear.'>'.$descripcion.'<br>';
                }else{
                    $accion=$permiso['accion'];
                    $descripcion=$ClasePermisos->ObtenerDescripcion($permiso['accion'], $permiso);
                    $html.='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" class="permiso_'.$i.'" value=1 id="accion_'.$i.'" name="permiso_'.$i.'" '.$checked.' '.$bloquear.'>'.$descripcion.'<br>';
                }
               
            }
             
             $i++;
        
        }
    }else{
         $html='<div class="alert alert-info">Este Usuario no tiene permisos</div>';
    }
    
    
    return $html;
}
?>
