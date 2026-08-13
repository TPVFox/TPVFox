<?php

require_once __DIR__ . '/../../../clases/DB.php';

class Modulo_etiquetado
{
	public function consulta($sql, $params = array())
	{
		// Realizamos la consulta.
		// Esta consulta no tiene sentido teniendo la del padre...

		$db = $this->db;
		// pquery() sirve para SELECT (devuelve mysqli_result) y para DML (devuelve
		// false); usamos errno para distinguir exito de error, ya que un DML
		// correcto tambien devuelve false.
		$smt = (new DB($db))->pquery($sql, $params);
		if ($db->errno === 0) {
			return $smt;
		} else {
			$respuesta = array();
			$respuesta['consulta'] = $sql;
			$respuesta['error'] = $db->error;
			return $respuesta;
		}
	}
	public function __construct($conexion)
	{
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM modulo_etiquetado';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}

	public function addTemporal($datos)
	{
		//@Objetivo:
		//Crear un albarán temporal
		//@Retorna:
		//O un error de sql o el id del temporal que se caba de crear
		$respuesta = array();
		$db = $this->db;
		// Controlamos posible error que no tenga productos el temporal
		if (isset($datos['productos'])) {
			$UnicoCampoProductos = json_encode($datos['productos']);
			$PrepProductos = $db->real_escape_string($UnicoCampoProductos);
			$numAlb = 0; // Valor por defecto
			if ($datos['NumAlb'] > 0) {
				$numAlb = $datos['NumAlb'];
			}
			$sql = 'INSERT INTO `modulo_etiquetado_temporal`(`num_lote`, `tipo`,
             `fecha_env`, `fecha_cad`, `idArticulo`, `numAlb`, `estado`,
             `productos`, `idUsuario`) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)';
			$smt = $this->consulta($sql, array($datos['idReal'], $datos['tipo'], $datos['fechaEnv'], $datos['fechaCad'], $datos['idProducto'], $numAlb, $datos['estado'], $UnicoCampoProductos, $datos['idUsuario']));
			if (gettype($smt) === 'array') {
				$respuesta['error'] = $smt['error'];
				$respuesta['consulta'] = $smt['consulta'];
			} else {
				$respuesta['id'] = $db->insert_id;
			}
		} else {
			$respuesta['error'] = 'No hay productos de temporal';
			$respuesta['consulta'] = 'Error en metodo Addtemporal';
		}
		return $respuesta;
	}
	public function modificarTemporal($datos, $idTemporal)
	{
		//@Objetivo:
		//Modificar el albarán temporal
		//@Retorna:
		//error de sql en caso de que lo tenga
		if ($datos['NumAlb'] > 0) {
			$numAlb = $datos['NumAlb'];
		} else {
			$numAlb = 0;
		}
		$respuesta = array();
		$db = $this->db;
		$UnicoCampoProductos = json_encode($datos['productos']);
		$PrepProductos = $db->real_escape_string($UnicoCampoProductos);
		$sql = 'UPDATE `modulo_etiquetado_temporal` SET
		`num_lote`=?,`tipo`=?,`fecha_env`=?
		,`fecha_cad`=?,`idArticulo`=?,`numAlb`=?
		,`estado`=?,`productos`=?
		,`idUsuario`=? WHERE id=?';
		$smt = $this->consulta($sql, array($datos['idReal'], $datos['tipo'], $datos['fechaEnv'], $datos['fechaCad'], $datos['idProducto'], $numAlb, $datos['estado'], $UnicoCampoProductos, $datos['idUsuario'], $idTemporal));
		if (gettype($smt) === 'array') {
			$respuesta['error'] = $smt['error'];
			$respuesta['consulta'] = $smt['consulta'];
		}
		return $respuesta;
	}

	public function todasEtiquetasLimite($limite)
	{
		//@OBjetivo:
		//LIstar todas las etiquetas guardadas
		$db = $this->db;
		// $limite trae el filtro de búsqueda + LIMIT del plugin de paginación
		// (PluginClasePaginacion): búsqueda saneada en ConstructorLike y LIMIT
		// numérico. Parametrizar del todo el buscador exige refactorizar ese
		// plugin compartido (pendiente, fuera de este alcance).
		$sql = 'SELECT a.num_lote, a.id , a.fecha_env, a.fecha_cad, a.estado, b.articulo_name , a.productos from modulo_etiquetado as a
		inner join articulos as b on a.idArticulo=b.idArticulo  ' . $limite;
		$smt = $this->consulta($sql);
		if (gettype($smt) === 'array') {
			$respuesta['error'] = $smt['error'];
			$respuesta['consulta'] = $smt['consulta'];
			return $respuesta;
		} else {
			$etiquetasPrincipal = array();
			while ($result = $smt->fetch_assoc()) {
				array_push($etiquetasPrincipal, $result);
			}
			return $etiquetasPrincipal;
		}
	}
	public function todosTemporal()
	{
		//@Objetivo:
		//Mostrar todos los temporales
		$db = $this->db;
		$sql = 'select a.id, a.num_lote, a.fecha_env, b.articulo_name from
		modulo_etiquetado_temporal as a inner join articulos as b on
		a.idArticulo=b.idArticulo  ';
		$smt = $this->consulta($sql);
		if (gettype($smt) === 'array') {
			$respuesta['error'] = $smt['error'];
			$respuesta['consulta'] = $smt['consulta'];
			return $respuesta;
		} else {
			$etiquetasPrincipal = array();
			while ($result = $smt->fetch_assoc()) {
				array_push($etiquetasPrincipal, $result);
			}
			return $etiquetasPrincipal;
		}
	}

	public function buscarTemporal($idTemporal)
	{
		//Objetivo;
		//BUscar los datos de un temporal
		$db = $this->db;
		$sql = 'select a.*, b.articulo_name FROM modulo_etiquetado_temporal
		 as a inner join articulos as b on a.idArticulo=b.idArticulo
		  where a.id=?';
		$smt = $this->consulta($sql, array($idTemporal));
		if (gettype($smt) === 'array') {
			$respuesta['error'] = $smt['error'];
			$respuesta['consulta'] = $smt['consulta'];
			return $respuesta;
		} else {
			if ($result = $smt->fetch_assoc()) {
				$lote = $result;
				return $lote;
			}
		}
	}
	public function eliminarTemporal($idTemporal)
	{
		//Objetivo:
		//eliminar un temporal determinado
		$db = $this->db;
		$sql = 'DELETE FROM `modulo_etiquetado_temporal` WHERE id=?';
		$smt = $this->consulta($sql, array($idTemporal));
		if (gettype($smt) === 'array') {
			$respuesta['error'] = $smt['error'];
			$respuesta['consulta'] = $smt['consulta'];
			return $respuesta;
		}
	}

	public function addLoteGuardado($datos)
	{
		//OBjetivo:
		//GUardar un lote nuevo o modificarlo si ya existe
		$db = $this->db;
		$UnicoCampoProductos = json_encode($datos['productos']);
		$PrepProductos = $db->real_escape_string($UnicoCampoProductos);
		if ($datos['idReal'] > 0) {
			$sql = 'UPDATE `modulo_etiquetado` SET
			`tipo`=?,`fecha_env`=?,`fecha_cad`=?,
			`idArticulo`=?,`numAlb`=?,`estado`=?,
			`productos`=?,`idUsuario`=? where id=?';
			$params = array($datos['tipo'], $datos['fecha_env'], $datos['fecha_cad'], $datos['idArticulo'], $datos['numAlb'], $datos['estado'], $UnicoCampoProductos, $datos['idUsuario'], $datos['idReal']);
		} else {
			$sql = 'INSERT INTO `modulo_etiquetado`(`tipo`,
			`fecha_env`, `fecha_cad`, `idArticulo`, `numAlb`, `estado`,
			`productos`, `idUsuario`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
			$params = array($datos['tipo'], $datos['fecha_env'], $datos['fecha_cad'], $datos['idArticulo'], $datos['numAlb'], $datos['estado'], $UnicoCampoProductos, $datos['idUsuario']);
		}
		$smt = $this->consulta($sql, $params);
		if (gettype($smt) === 'array') {
			$respuesta['error'] = $smt['error'];
			$respuesta['consulta'] = $smt['consulta'];
			return $respuesta;
		} else {
			$id = $db->insert_id;
			if ($datos['idReal'] == 0) {
				$sql = 'UPDATE modulo_etiquetado SET num_lote=? WHERE id=?';
				$smt = $this->consulta($sql, array($id, $id));
				if (gettype($smt) === 'array') {
					$respuesta['error'] = $smt['error'];
					$respuesta['consulta'] = $smt['consulta'];
					return $respuesta;
				}
			}
		}
	}

	function datosLote($idLote)
	{
		//OBjetivo:
		//MOstrar todos los datos de un lote ya guardado
		$db = $this->db;
		$sql = 'select a.*, b.articulo_name FROM modulo_etiquetado
		 as a inner join articulos as b on a.idArticulo=b.idArticulo
		  where a.id=?';
		$smt = $this->consulta($sql, array($idLote));
		if (gettype($smt) === 'array') {
			$respuesta['error'] = $smt['error'];
			$respuesta['consulta'] = $smt['consulta'];
			return $respuesta;
		} else {
			if ($result = $smt->fetch_assoc()) {
				$lote = $result;
				return $lote;
			}
		}
	}
	function modifEstadoReal($estado, $id)
	{
		//Objetivo:
		//MOdificar el estado de un lote real
		$db = $this->db;
		$sql = 'UPDATE modulo_etiquetado SET estado=? where id=?';
		$smt = $this->consulta($sql, array($estado, $id));
		if (gettype($smt) === 'array') {
			$respuesta['error'] = $smt['error'];
			$respuesta['consulta'] = $smt['consulta'];
			return $respuesta;
		}
	}
}
