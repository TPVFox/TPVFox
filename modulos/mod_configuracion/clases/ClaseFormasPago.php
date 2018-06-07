<?php 
include_once '../../modulos/claseModelo.php';


class ClaseFormasPago extends modelo{
	public function cargarDatos(){
		$sql= 'SELECT * FROM formasPago ';
		return $this->consulta($sql);
	}
	public function getDatos($id){
		$sql='SELECT * FROM formasPago WHERE id='.$id;
		return $this->consulta($sql);
	}
	public function modificarTabla($datos){
		$sql='UPDATE `formasPago` SET `descripcion`='."'".$datos['descripcion']."'".' WHERE id='.$datos['id'];
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}
	}
	public function insertarRegistro($datos){
		$sql='INSERT INTO `formasPago`(`descripcion`) VALUES ('."'".$datos['descripcion']."'".')';
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}
	}
	
}



?>
