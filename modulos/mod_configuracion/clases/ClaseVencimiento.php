<?php 
include_once '../../modulos/claseModelo.php';

class ClaseVencimiento extends modelo{
	public function cargarDatos(){
		$sql= 'SELECT * FROM tiposVencimiento ';
		return $this->consulta($sql);
	}
	public function getDatos($id){
		$sql='SELECT * FROM tiposVencimiento WHERE id='.$id;
		return $this->consulta($sql);
	}
	public function modificarTabla($datos){
		$sql='UPDATE `tiposVencimiento` SET `descripcion`='."'".$datos['descripcion']."'".', dias='."'".$datos['dias']."'".' WHERE id='.$datos['id'];
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}
	}
	public function insertarRegistro($datos){
		$sql='INSERT INTO `tiposVencimiento`(`descripcion`, dias) VALUES ('."'".$datos['descripcion']."'".', '."'".$datos['dias']."'".')';
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}
	}
}



?>
