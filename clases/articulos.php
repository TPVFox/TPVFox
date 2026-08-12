<?php

require_once __DIR__ . '/DB.php';

class Articulos
{
	public function __construct($conexion)
	{
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$respuesta = $this->consulta('SELECT count(*) as num_reg FROM articulos');
		$this->num_rows = $respuesta->fetch_object()->num_reg;
	}

	// Consulta parametrizada por la capa DB. Devuelve el mysqli_result (o false
	// en escrituras), o un array ['consulta','error'] si la consulta falla.
	public function consulta($sql, $params = [])
	{
		try {
			return (new DB($this->db))->pquery($sql, $params);
		} catch (\Throwable $e) {
			return array('consulta' => $sql, 'error' => $e->getMessage());
		}
	}

	public function addArticulosProveedores($datos)
	{
		$sql = 'INSERT INTO articulosProveedores (idArticulo, idProveedor, crefProveedor, coste, fechaActualizacion, estado)'
			. ' VALUES (?, ?, ?, ?, ?, ?)';
		$params = array($datos['idArticulo'], $datos['idProveedor'], $datos['refProveedor'], $datos['coste'], $datos['fecha'], $datos['estado']);
		$smt = $this->consulta($sql, $params);
		if (gettype($smt) === 'array') {
			$respuesta['error'] = $smt['error'];
			$respuesta['consulta'] = $smt['consulta'];
			return $respuesta;
		}
	}

	public function buscarReferencia($idArticulo, $idProveedor)
	{
		$smt = $this->consulta('SELECT * FROM articulosProveedores WHERE idArticulo=? and idProveedor=?', array($idArticulo, $idProveedor));
		if (gettype($smt) === 'array') {
			$respuesta['error'] = $smt['error'];
			$respuesta['consulta'] = $smt['consulta'];
			return $respuesta;
		} else {
			if ($result = $smt->fetch_assoc()) {
				$referencia = $result;
				return $referencia;
			}
		}
	}

	public function buscarNombreArticulo($idArticulo)
	{
		$smt = $this->consulta('SELECT articulo_name FROM articulos WHERE idArticulo=?', array($idArticulo));
		$referencia = null;
		if ($result = $smt->fetch_assoc()) {
			$referencia = $result;
		}
		return $referencia;
	}

	public function modificarProveedorArticulo($datos)
	{
		$sql = 'UPDATE articulosProveedores SET crefProveedor=? WHERE idArticulo=? and idProveedor=?';
		$smt = $this->consulta($sql, array($datos['refProveedor'], $datos['idArticulo'], $datos['idProveedor']));
		if (gettype($smt) === 'array') {
			$respuesta['error'] = $smt['error'];
			$respuesta['consulta'] = $smt['consulta'];
			return $respuesta;
		}
	}

	public function modificarCosteProveedorArticulo($datos)
	{
		$sql = 'UPDATE articulosProveedores SET coste=?, fechaActualizacion=? WHERE idArticulo=? and idProveedor=?';
		$smt = $this->consulta($sql, array($datos['coste'], $datos['fecha'], $datos['idArticulo'], $datos['idProveedor']));
		if (gettype($smt) === 'array') {
			$respuesta['error'] = $smt['error'];
			$respuesta['consulta'] = $smt['consulta'];
			return $respuesta;
		}
	}

	public function addHistorico($datos)
	{
		$sql = 'INSERT INTO historico_precios (idArticulo, Antes, Nuevo, Fecha_Creacion, NumDoc, Dedonde, Tipo, estado, idUsuario)'
			. ' VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?)';
		$params = array($datos['idArticulo'], $datos['antes'], $datos['nuevo'], $datos['numDoc'], $datos['dedonde'], $datos['tipo'], $datos['estado'], $datos['idUsuario']);
		$smt = $this->consulta($sql, $params);
		if (gettype($smt) === 'array') {
			$respuesta['error'] = $smt['error'];
			$respuesta['consulta'] = $smt['consulta'];
			return $respuesta;
		}
	}

	public function historicoCompras($numDoc, $Dedonde, $tipo)
	{
		$smt = $this->consulta('SELECT * from historico_precios where NumDoc=? and Dedonde=? and Tipo=?', array($numDoc, $Dedonde, $tipo));
		$historicoPrincipal = array();
		while ($result = $smt->fetch_assoc()) {
			array_push($historicoPrincipal, $result);
		}
		return $historicoPrincipal;
	}

	public function datosPrincipalesArticulo($idArticulo)
	{
		$sql = 'SELECT a.idArticulo, a.iva, a.articulo_name, a.beneficio, b.crefTienda'
			. ' FROM articulos as a left join articulosTiendas as b on a.idArticulo=b.idArticulo where a.idArticulo=?';
		$smt = $this->consulta($sql, array($idArticulo));
		if ($result = $smt->fetch_assoc()) {
			$articulo = $result;
			return $articulo;
		}
	}

	public function articulosPrecio($idArticulo)
	{
		$smt = $this->consulta('SELECT * FROM articulosPrecios where idArticulo=?', array($idArticulo));
		$articulo = null;
		if ($result = $smt->fetch_assoc()) {
			$articulo = $result;
		}
		return $articulo;
	}

	public function modificarEstadosHistorico($idAlbaran, $dedonde)
	{
		$this->consulta('UPDATE historico_precios set estado="Revisado" where NumDoc=? and Dedonde=? and estado <> "Sin revisar"', array($idAlbaran, $dedonde));
	}

	public function modArticulosPrecio($nuevoCiva, $nuevoSiva, $idArticulo)
	{
		$sql = 'UPDATE articulosPrecios SET pvpCiva=?, pvpSiva=? where idArticulo=?';
		$this->consulta($sql, array($nuevoCiva, $nuevoSiva, $idArticulo));
		return $sql;
	}

	public function modEstadoArticuloHistorico($idArticulo, $idAlbaran, $dedonde, $tipo, $estado)
	{
		$sql = 'UPDATE historico_precios set estado=? where NumDoc=? and Dedonde=? and idArticulo=? and Tipo=?';
		$this->consulta($sql, array($estado, $idAlbaran, $dedonde, $idArticulo, $tipo));
		return $sql;
	}

	public function datosArticulosPrincipal($idArticulo, $idTienda)
	{
		$sql = 'select a.articulo_name, pre.pvpCiva, t.crefTienda, a.idArticulo'
			. ' FROM articulos as a'
			. ' inner join articulosPrecios as pre on a.idArticulo=pre.idArticulo'
			. ' inner join articulosTiendas as t on a.idArticulo=t.idArticulo'
			. ' where a.idArticulo=? and t.idTienda=?';
		$smt = $this->consulta($sql, array($idArticulo, $idTienda));
		if (gettype($smt) === 'array') {
			$respuesta['error'] = $smt['error'];
			$respuesta['consulta'] = $smt['consulta'];
			return $respuesta;
		} else {
			$articulo = array();
			if ($result = $smt->fetch_assoc()) {
				$articulo = $result;
			}
			return $articulo;
		}
	}

	public function buscarPorNombre($valor, $idTienda)
	{
		$respuesta = array();
		$sql = 'select a.articulo_name, pre.pvpCiva, t.crefTienda, a.idArticulo'
			. ' FROM articulos as a'
			. ' inner join articulosPrecios as pre on a.idArticulo=pre.idArticulo'
			. ' inner join articulosTiendas as t on a.idArticulo=t.idArticulo'
			. ' where a.articulo_name like ? and t.idTienda=? group by a.idArticulo LIMIT 0 , 30';
		$smt = $this->consulta($sql, array('%' . $valor . '%', $idTienda));
		if (gettype($smt) === 'array') {
			$respuesta = $smt;
		} else {
			while ($result = $smt->fetch_assoc()) {
				array_push($respuesta, $result);
			}
		}
		return $respuesta;
	}

	public function ComprobarFechasHistorico($idArticulo, $fecha)
	{
		$respuesta = array();
		$sql = 'SELECT * from historico_precios WHERE idArticulo=? and Fecha_Creacion > ?';
		$smt = $this->consulta($sql, array($idArticulo, $fecha));
		if (gettype($smt) === 'array') {
			$respuesta = $smt;
		} else {
			while ($result = $smt->fetch_assoc()) {
				array_push($respuesta, $result);
			}
		}
		return $respuesta;
	}

	public function modificarRegHistorico($idRegistro, $estado)
	{
		$smt = $this->consulta('UPDATE historico_precios SET estado=? where id=?', array($estado, $idRegistro));
		if (gettype($smt) === 'array') {
			$respuesta['error'] = $smt['error'];
			$respuesta['consulta'] = $smt['consulta'];
			return $respuesta;
		}
	}

	// Consulta en la tabla articulos si un producto es de tipo peso.
	public function getTipoArticulo($idArticulo)
	{
		$respuesta = array();
		$smt = $this->consulta('SELECT tipo FROM articulos WHERE idArticulo = ?', array($idArticulo));
		if (gettype($smt) === 'array') {
			$respuesta = $smt;
		} else {
			if ($result = $smt->fetch_assoc()) {
				$respuesta[$idArticulo] = $result['tipo'];
			}
		}
		return $respuesta;
	}

	// Datos de las balanzas asociadas a un articulo.
	public function getBalanzaAsociada($idArticulo): array
	{
		$respuesta = array();
		$smt = $this->consulta('SELECT idBalanza, PLU, Tecla FROM modulo_balanza_plus WHERE idArticulo = ?', array($idArticulo));
		if (gettype($smt) === 'array') {
			$respuesta = $smt;
		} else {
			while ($result = $smt->fetch_assoc()) {
				array_push($respuesta, $result);
			}
		}
		return $respuesta;
	}
}
