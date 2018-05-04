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
		$sql='INSERT INTO `modulo_etiquetado`(`num_lote`, `tipo`,
		 `fecha_env`, `fecha_cad`, `idArticulo`, `numAlb`, `estado`, 
		 `productos`, `idUsuario`) VALUES('.$datos['idReal'].', '.$datos['tipo'].', "'.$datos['fechaEnv'].'",
		 "'.$datos['fechaCad'].'", '.$datos['idProducto'].', '.$datos['NumAlb'].', "'.$datos['estado'].'"
		 ,"'.$productos.'", '.$datos['idUsuario'].')';
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
		}else{
			$respuesta['id']=$db->insert_id;
		}
		return $respuesta;
	}
	
}

?>
