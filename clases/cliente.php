<?php

require_once __DIR__ . '/DB.php';

class Cliente
{
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
	/** @var DB Capa de acceso a datos segura (parametrizada). */
	private $dbl;

	public function __construct($conexion)
	{
		$this->db = $conexion;
		$this->dbl = new DB($conexion);
		// Obtenemos el numero registros.
		$filas = $this->dbl->select('SELECT count(*) as num_reg FROM clientes');
		$this->num_rows = $filas[0]['num_reg'] ?? 0;
	}
	public function arrrayDatos($datos)
	{
		$this->id = $datos['idClientes'];
		$this->nombre = $datos['Nombre'];
		$this->razonSocial = $datos['razonsocial'];
		$this->nif = $datos['nif'];
		$this->direccion = $datos['direccion'];
		$this->codPostal = $datos['codPostal'];
		$this->telefono = $datos['telefono'];
		$this->movil = $datos['movil'];
		$this->fax = $datos['fax'];
		$this->email = $datos['email'];
		$this->estado = $datos['estado'];
	}
	public function DatosClientePorId($idCliente)
	{
		// Capa DB: el id va como parámetro, no concatenado.
		$filas = $this->dbl->selectWhere('clientes', ['idClientes' => $idCliente]);
		return $filas[0] ?? null;
	}

	public function BuscarClientePorNombre($nombre)
	{
		// Buscar por Nombre Comercial o razon social, por palabras.
		$palabras = array_values(array_filter(explode(' ', trim($nombre)), function ($p) {
			return $p !== '';
		}));
		if (count($palabras) === 0) {
			return ['sql' => '', 'datos' => array()];
		}

		// Cada palabra va como parámetro LIKE (nunca concatenada).
		$condNombre = array();
		$condRazon = array();
		foreach ($palabras as $palabra) {
			$condNombre[] = 'Nombre LIKE ?';
			$condRazon[] = 'razonsocial LIKE ?';
		}
		$sql = 'SELECT * FROM clientes WHERE (' . implode(' AND ', $condNombre) . ')'
			. ' OR (' . implode(' AND ', $condRazon) . ')';

		$like = array_map(function ($p) {
			return '%' . $p . '%';
		}, $palabras);
		$params = array_merge($like, $like); // primero para Nombre, luego para razonsocial

		$datos = $this->dbl->select($sql, $params);
		return ['sql' => $sql, 'datos' => $datos];
	}
}
