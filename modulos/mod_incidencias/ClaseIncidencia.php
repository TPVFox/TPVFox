<?php 

class incidencia{
	
	public function __construct($conexion){
		$this->db = $conexion;
	
		$sql = 'SELECT count(*) as num_reg FROM `modulo_incidencia';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		
	}
	public function consulta($sql){
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
	
	
	public function todasIncidenciasLimite($limite){
		$db=$this->db;
		$sql='SELECT a.id as id , a.`num_incidencia` as num_incidencia ,
		 a.fecha_creacion as fecha , a.dedonde as dedonde, 
		 a.estado as estado, b.nombre as nombre , a.mensaje as mensaje 
		 from modulo_incidencia as a INNER JOIN usuarios as b 
		 on a.id_usuario=b.id  where a.id in  (select max(id) from modulo_incidencia GROUP by num_incidencia) '.$limite;
		 $smt=$this->consulta($sql);
		 if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}else{
			$incidenciasPrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
				array_push($incidenciasPrincipal,$result);
			}
			return $incidenciasPrincipal;
		}
	}
	public function datosIncidencia($idIncidencia){
		$db=$this->db;
		$sql='select * from modulo_incidencia where id='.$idIncidencia;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}else{
			if ($result = $smt->fetch_assoc () ){
				$incidencia=$result;
			}
			return $incidencia;	
		}
	}
	public function incidenciasNumero($numeroIncidencia){
		$db=$this->db;
		$sql='select a.* ,  b.username from modulo_incidencia as a inner JOIN   usuarios as b on a.id_usuario=b.id where num_incidencia='.$numeroIncidencia;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}else{
			$incidenciaPrincipal=array();
				while ( $result = $smt->fetch_assoc () ) {
					array_push($incidenciaPrincipal,$result);
				}
				return $incidenciaPrincipal;
		}
	}
	
	
	
	
}

?>
