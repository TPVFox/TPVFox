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
}



?>
