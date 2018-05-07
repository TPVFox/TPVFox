<?php 

class Modulo_etiquetado{
	public function consulta($sql){
		// Realizamos la consulta.
		// Esta consulta no tiene sentido teniendo la del padre...
		
		$db = $this->db;
		$smt = $db->query($sql);
		if ($smt) {
			return $smt;
		} else {
			$respuesta = array();
			$respuesta['consulta'] = $sql;
			$respuesta['error'] = $db->error;
			return $respuesta;
		}
	}
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM modulo_etiquetado';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	public function addTemporal($datos, $productos){
		$respuesta=array();
		$sql='INSERT INTO `modulo_etiquetado_temporal`(`num_lote`, `tipo`,
		 `fecha_env`, `fecha_cad`, `idArticulo`, `numAlb`, `estado`, 
		 `productos`, `idUsuario`) VALUES('.$datos['idReal'].', '.$datos['tipo'].', "'.$datos['fechaEnv'].'",
		 "'.$datos['fechaCad'].'", '.$datos['idProducto'].', '.$datos['NumAlb'].', "'.$datos['estado'].'"
		 ,"'.$productos.'", '.$datos['idUsuario'].')';
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
		}else{
			$respuesta['id']=$smt->insert_id;
		}
		return $respuesta;
	}
	public function modificarTemporal($datos, $productos, $idTemporal){
		$respuesta=array();
		$sql='UPDATE `modulo_etiquetado_temporal` SET 
		`num_lote`='.$datos['idReal'].',`tipo`='.$datos['tipo'].',`fecha_env`="'.$datos['fechaEnv'].'"
		,`fecha_cad`="'.$datos['fechaCad'].'",`idArticulo`='.$datos['idProducto'].',`numAlb`='.$datos['NumAlb'].'
		,`estado`="'.$datos['estado'].'",`productos`="'.$productos.'"
		,`idUsuario`='.$datos['idUsuario'].' WHERE id='.$idTemporal;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
		}
		return $respuesta;
	}
	
	public function todasEtiquetasLimite($limite){
		$db=$this->db;
		$sql='a.num_lote, a.fecha_env, a.fecha_cad, a.estado, b.articulo_name from modulo_etiquetado as a
		inner join articulos as b on a.idArticulo=b.idArticulo where '.$limite;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}else{
			$etiquetasPrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
				array_push($etiquetasPrincipal,$result);
			}
			return $etiquetasPrincipal;
		}
	}
	
}

?>
