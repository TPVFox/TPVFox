<?php

class ClaseVentas{
	
	public $db; //(Objeto) Es la conexion;

	public function consulta($sql){
		// Realizamos la consulta.
		$db = $this->db;
		$smt = $db->query($sql);
		if ($smt) {
			return $smt;
		} else {
			$respuesta = array();
			$respuesta['consulta'] = $sql;
			$respuesta['error'] = $db->error;
			return $respuesta;
		}
	}  
	
	public function __construct($conexion){
		$this->db = $conexion;

	}
	public function SelectUnResult($tabla, $where){
		$db=$this->db;
		$sql='SELECT * from '.$tabla.' where '.$where;
		//~ $smt=$db->query('SELECT * from '.$tabla.' where '.$where);
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}else{
			if ($result = $smt->fetch_assoc () ){
				$resultado=$result;
				return $resultado;
			}
		}
		
	}
	public function SelectVariosResult($tabla, $where){
		$db=$this->db;
		$sql='SELECT * from '.$tabla.' where '.$where;
		$smt = $db->query($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}else{
			$resultadoPrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
				array_push($resultadoPrincipal,$result);
			}
			return $resultadoPrincipal;
		}
	}
}

?>
