<?php 
include_once  $URLCom.'/modulos/claseModelo.php';


class ClaseIva extends modelo{
	public function cargarDatos(){
		$sql= 'SELECT * FROM iva ';
		return $this->consulta($sql);
	}
	public function getDatos($id){
		$sql='SELECT * FROM iva WHERE idIva='.$id;
		return $this->consulta($sql);
	}
	public function modificarTabla($datos){
		$sql='UPDATE `iva` SET `descripcionIva`='."'".$datos['descripcion']."'".',`iva`='."'".$datos['iva']."'".',`recargo`='."'".$datos['recargo']."'".' WHERE idIva='.$datos['id'];
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}
	}
	public function insertarRegistro($datos){
		$sql='INSERT INTO `iva`(`descripcionIva`, `iva`, `recargo`) VALUES ('."'".$datos['descripcion']."'".', '."'".$datos['iva']."'".', '."'".$datos['recargo']."'".')';
		$consulta=$this->consultaDML($sql);
		if(isset($consulta['error'])){
			return $consulta;
		}
	}
}



?>
