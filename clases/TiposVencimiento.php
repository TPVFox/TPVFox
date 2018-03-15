<?php 
class TiposVencimientos{
	private $id;
	private $descripcion;
	private $dias;
	
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM tiposVencimiento';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		return $smt;
	} 
	public function todos(){
		$db = $this->db;
		$smt = $db->query ('SELECT * from tiposVencimiento');
		$tiposPrincipal=array();
		while ($result = $smt->fetch_assoc () ){
			array_push($tiposPrincipal,$result);
		}
		return $tiposPrincipal;
	}	
	public function MenosPrincipal($idPrincipal){
		$db = $this->db;
		$smt = $db->query ('SELECT * from tiposVencimiento WHERE id<>'.$idPrincipal);
		$tiposPrincipal=array();
		while ($result = $smt->fetch_assoc () ){
			array_push($tiposPrincipal,$result);
		}
		return $tiposPrincipal;
	}	
	public function datosPrincipal($idPrincipal){
			$db = $this->db;
		$smt = $db->query ('SELECT * from tiposVencimiento where id='.$idPrincipal);
		if ($result = $smt->fetch_assoc () ){
			$resultado=$result;
		}
		return $resultado;
	}	
}



?>
