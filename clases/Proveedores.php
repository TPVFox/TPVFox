<?php 

class Proveedores{
	private $idProveedor;
	private $nombreComercial;
	private $razonSocial;
	private $nif;
	private $direccion;
	private $telefono;
	private $fax;
	private $movil;
	private $email;
	private $fecha_creado;
	private $estado;
	
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM proveedores';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		return $smt;
	}
	
	public function buscarProveedorId($idProveedor){
		$db = $this->db;
		$smt=$db->query('SELECT * from proveedores where idProveedor='.$idProveedor);
		if ($result = $smt->fetch_assoc () ){
			$proveedor=$result;
		}
		return $proveedor;
	}
	public function buscarProveedorNombre($nombre){
		$db = $this->db;
		$smt=$db->query('SELECT * from proveedores where nombrecomercial like "%'.$nombre.'%"');
		$sql='SELECT * from proveedores where nombrecomercial="%'.$nombre.'%"';
		$proveedorPrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
				array_push($proveedorPrincipal, $result);
			}
			$respuesta['datos']=$proveedorPrincipal;
			$respuesta['sql']=$sql;
			return $respuesta;
	}
	
}

?>
