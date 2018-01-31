<?php
class Cli_FornPago_TipoVen{
	private $id;
	private $idCliente;
	private $idFormaPago;
	private $idTipoVen;
	private $predeterminado;
		public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM Cli_FroPa_TipoVen';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		return $smt;
	} 
	
	
	
	
}


?>
