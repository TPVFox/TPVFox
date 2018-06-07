<?php 
include_once '../../modulos/claseModelo.php';


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
}



?>
