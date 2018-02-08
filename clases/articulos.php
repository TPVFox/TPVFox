<?php
class Articulos{
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
	
	public function addArticulosProveedores($datos){
		$db=$this->db;
		$smt=$db->query('INSERT INTO articulosProveedores (	idArticulo, idProveedor, crefProveedor, coste, fechaActualizacion, estado) VALUE ('.$datos['idArticulo'].', '.$datos['idProveedor'].', '.$datos['refProveedor'].', '.$datos['coste'].', "'.$datos['fecha'].'", "'.$datos['estado'].'")');
		$sql='INSERT INTO articulosProveedores (	idArticulo, idProveedor, crefProveedor, coste, fechaActualizacion, estado) VALUE ('.$datos['idArticulo'].', '.$datos['idProveedor'].', '.$datos['refProveedor'].', '.$datos['coste'].', "'.$datos['fecha'].'", "'.$datos['estado'].'")';
		return $sql;
	}
	
	
}


?>
