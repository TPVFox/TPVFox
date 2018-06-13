<?php 
include_once $RutaServidor . $HostNombre . '/modulos/claseModelo.php';
class ClaseUsuarios extends modelo{
	
	public function getConfiguracionModulo($idUsuario){
		$sql='SELECT * FROM `modulos_configuracion` where idusuario='.$idUsuario;
		return $this->consulta($sql);
	}
	
}



?>
