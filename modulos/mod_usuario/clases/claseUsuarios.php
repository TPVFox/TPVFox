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
        $sql='SELECT id, username FROM usuarios';
        return $this->consulta($sql);
    }
	
}



?>
