<?php

class ClaseVentas{
	
	public $db; //(Objeto) Es la conexion;

	
	public function __construct($conexion){
		$this->db = $conexion;

	}
}

?>
