<?php 

class Cliente{
	private $idCliente;
	private $nombre;
	private $razonSocial;
	private $nif;
	private $direccion;
	private $codPostal;
	private $telefono;
	private $movil;
	private $fax;
	private $email;
	private $estado;
	
		public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM clientes';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		return $smt;
	}
	public function arrrayDatos($datos){
		$this->id= $datos['idClientes'];
		$this->nombre=$datos['Nombre'];
		$this->razonSocial=$datos['razonsocial'];
		$this->nif=$datos['nif'];
		$this->direccion=$datos['direccion'];
		$this->codPostal=$datos['codPostal'];
		$this->telefono=$datos['telefono'];
		$this->movil=$datos['movil'];
		$this->fax=$datos['fax'];
		$this->email=$datos['email'];
		$this->estado=$datos['estado'];
	}
	public function DatosClientePorId($idCliente){
		$db = $this->db;
		$smt = $db->query ('SELECT * from clientes WHERE idClientes='.$idCliente);
		if ($result = $smt->fetch_assoc () ){
			$cliente=$result;
		}
		return $cliente;
	}
	
	
	
	
}
?>
