<?php 
include_once $RutaServidor . $HostNombre . '/modulos/claseModelo.php';
class ClaseUsuarios extends modelo{
	
	public function getConfiguracionModulo($idUsuario){
		//Objetivo:
		//cargar los datos del usuario a buscar
		//return:
		//los datos del usuario determinado o error, en este caso devuelve el error y el sql
		$sql='SELECT * FROM `modulos_configuracion` where idusuario='.$idUsuario;
		return $this->consulta($sql);
	}
	public function eliminarConfiguracionUsuario($idUsuario, $modulo){
		//@Objetivo:Eliminar el registro de un usuario-modulo
		//@Parametros: 
		//idUsuario: id del usuario
		//modulo: nombre del modulo del que vamos a eliminar la configuración de ese usuario
		$sql='DELETE FROM `modulos_configuracion` WHERE idusuario='.$idUsuario.' and `nombre_modulo`="'.$modulo.'"';
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}
	}
    public function todosUsuarios(){
        $sql='SELECT id, username, `group_id`, `estado`, `nombre` FROM usuarios';
        return $this->consulta($sql);
    }
	public function getUsuarioNombrePorId($idUsuario){
		//@Objetivo: Obtener los datos de un usuario por su id
		//@Parametros:
		//idUsuario: id del usuario a buscar
		$sql='SELECT nombre FROM `usuarios` where id='.$idUsuario;
		return $this->consulta($sql);
	}
	public function cambiarContraseñaUsuarios($idsUsuarios, $NuevaContraseña){
		//@Objetivo: Cambiar la contraseña de los usuarios indicados
		//@Parametros:
		//idsUsuarios: array con los ids de los usuarios a cambiar la contraseña
		//NuevaContraseña: nueva contraseña a asignar
		$sql='UPDATE `usuarios` SET `password` = MD5("'.$NuevaContraseña.'") WHERE id IN ('.implode(",",$idsUsuarios).')';
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}
	}
	public function inactivarUsuarios($idsUsuarios){
		//@Objetivo: Inactivar los usuarios que no están en la lista proporcionada
		//@Parametros:
		//idsUsuarios: array con los ids de los usuarios que deben permanecer activos
		$sql='UPDATE `usuarios` SET `estado` = "inactivo" where id NOT IN ('.implode(",",$idsUsuarios).')';
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}
	}

	public function activarUsuarios($idsUsuarios){
		//@Objetivo: Activar los usuarios que están en la lista proporcionada
		//@Parametros:
		//idsUsuarios: array con los ids de los usuarios que deben ser activados
		$sql='UPDATE `usuarios` SET `estado` = "activo" where id IN ('.implode(",",$idsUsuarios).')';
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}
	}


}



?>
