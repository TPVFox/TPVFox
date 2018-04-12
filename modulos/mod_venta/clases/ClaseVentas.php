<?php

class ClaseVentas{
	
	public $db; //(Objeto) Es la conexion;

	
	public function __construct($conexion){
		$this->db = $conexion;

	}
	public function SelectUnResult($tabla, $where){
		$db=$this->db;
		$smt=$db->query('SELECT * from '.$tabla.' where '.$where);
		if ($result = $smt->fetch_assoc () ){
			$resultado=$result;
			return $resultado;
		}
		
	}
	public function SelectVariosResult($tabla, $where){
		$db=$this->db;
		$smt=$db->query('SELECT * from '.$tabla.' where '.$where);
		$resultadoPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($resultadoPrincipal,$result);
		}
		return $resultadoPrincipal;
	}
}

?>
