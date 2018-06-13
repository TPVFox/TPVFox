<?php 
include_once $RutaServidor . $HostNombre . '/modulos/claseModelo.php';
class ClaseUsuarios extends modelo{
	
	public function getConfiguracionModulo($idUsuario){
		$sql='SELECT * FROM `modulos_configuracion` where idusuario='.$idUsuario;
		return $this->consulta($sql);
	}
	public function eliminarConfiguracionUsuario($idUsuario, $modulo){
		$sql='DELETE FROM `modulos_configuracion` WHERE idusuario='.$idUsuario.' and `nombre_modulo`="'.$modulo.'"';
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}
	}
	
}



?>
