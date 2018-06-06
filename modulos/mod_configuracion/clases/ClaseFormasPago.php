<?php 
include_once $RutaServidor . $HostNombre . '/modulos/claseModelo.php';


class ClaseFormasPago extends modelo{
	public function cargarDatos(){
		$sql= 'SELECT * FROM formasPago ';
		return $this->consulta($sql);
	}
	
}



?>
