<?php
// Clase base de pedidos

class ClaseCompras
{
	public $db; //(Objeto) Es la conexion;

	public function consulta($sql){
		// Realizamos la consulta.
		$db = $this->db;
		$smt = $db->query($sql);
		if ($smt) {
			return $smt;
		} else {
			$repuesta = array();
			$respuesta['consulta'] = $sql;
			$respuesta['error'] = $db->error;
			return $respuesta;
		}
	} 
	public function __construct($conexion){
		$this->db = $conexion;

	}
	
	public function htmlPendientes(){
		
	}
	
	
	public function sumarIvaBases($from_where){
		//Función para sumar los ivas de un pedido
		$db=$this->db;
		$smt=$db->query('select sum(importeIva ) as importeIva , sum(totalbase) as  totalbase '.$from_where);
		if ($result = $smt->fetch_assoc () ){
			$sumaIvasBases=$result;
		}
		return $sumaIvasBases;
	}
	public function SelectUnResult($tabla, $where){
		$db=$this->db;
		$sql='SELECT * from '.$tabla.' where '.$where;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}else{
		//~ $smt=$db->query('SELECT * from '.$tabla.' where '.$where);
			if ($result = $smt->fetch_assoc () ){
				$resultado=$result;
			}
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
