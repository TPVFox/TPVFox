<?php 
class FormasPago{
	
	private $id;
	private $descripcion;
	
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM formasPago';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		return $smt;
	}
	public function todas(){
		$db = $this->db;
		$smt = $db->query ('SELECT * from formasPago');
		$formasPagoPrincipal=array();
		while ($result = $smt->fetch_assoc () ){
			array_push($formasPagoPrincipal,$result);
		}
		return $formasPagoPrincipal;
	}
	public function formadePagoSinPrincipal($idforma){
		$db = $this->db;
		$smt = $db->query ('SELECT * from formasPago where id<>'.$idforma);
		$formasPagoPrincipal=array();
		while ($result = $smt->fetch_assoc () ){
			array_push($formasPagoPrincipal,$result);
		}
		return $formasPagoPrincipal;
	}
	public function datosPrincipal($idPrincipal){
		$db = $this->db;
		$smt = $db->query ('SELECT * from formasPago where id='.$idPrincipal);
		if ($result = $smt->fetch_assoc () ){
			$resultado=$result;
		}
		return $resultado;
	}
}

?>
