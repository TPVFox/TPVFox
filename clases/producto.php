<?php
class Producto{
	private $idArticulo;
	private $iva;
	private $idProveedor;
	private $articulo_name;
	private $beneficio;
	private $costepromedio;
	private $estado;
	private $fecha_creado;
	private $fecha_modificado;
	private $codBarras;
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM articulos';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		return $smt;
	}
	public function arrayDatosProductos($datos){
		$this->idArticulo=$datos['idArticulos'];
		$this->iva=$datos['iva'];
		$this->idProveedor=$datos['idProveedor'];
		$this->articulo_name=$datos['articulo_name'];
		$this->beneficio=$datos['beneficio'];
		$this->costepromedio=$datos['costepromedio'];
		$this->estado=$datos['estado'];
		$this->fecha_creado=$datos['fecha_creado'];
		$this->fecha_modificado=$datos['fecha_modificado'];
	}
	public function arrayCodBarras($datos){
		$this->idArticulo=$datos['idArticulo'];
		$this->codBarras=$datos['codBarras'];
	}
	
	
	
}

?>
