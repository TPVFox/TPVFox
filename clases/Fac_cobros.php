<?php

require_once __DIR__ . '/DB.php';
class Fac_cobro
{
	private  $id;
	private $idFactura;
	private $idFormaPago;
	private $fechaPago;
	private $importe;
	public function __construct($conexion)
	{
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM fac_cobros';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	public function consulta($sql, $params = [])
	{
		$smt = (new DB($this->db))->pquery($sql, $params);
		return $smt;
	}
}
