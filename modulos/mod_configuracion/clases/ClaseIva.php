<?php 
include_once $RutaServidor . $HostNombre . '/modulos/claseModelo.php';


class ClaseIva extends modelo{
	public function cargarDatos(){
		$sql= 'SELECT * FROM iva ';
		return $this->consulta($sql);
	}
	
}



?>
