<?php

require_once __DIR__ . '/DB.php';
class ClaseTablaTienda
{

	public function __construct($conexion)
	{
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM tiendas';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	public function consulta($sql, $params = [])
	{
		$smt = (new DB($this->db))->pquery($sql, $params);
		return $smt;
	}

	public function DatosTienda($idTienda)
	{
		$db = $this->db;
		$sql = 'SELECT * FROM tiendas where idTienda=' . $idTienda;
		$smt = $this->consulta($sql);
		if ($result = $smt->fetch_assoc()) {
			$tienda = $result;
		}
		return $tienda;
	}
}
