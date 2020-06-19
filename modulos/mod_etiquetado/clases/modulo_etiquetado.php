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
	
	public function addTemporal($datos){
		//@Objetivo:
		//Crear un albarán temporal
		//@Retorna:
		//O un error de sql o el id del temporal que se caba de crear
		$respuesta=array();
		$db = $this->db;
        $UnicoCampoProductos=json_encode($datos['productos']);
		$PrepProductos = $db->real_escape_string($UnicoCampoProductos);
		if($datos['NumAlb']>0){
			$numAlb=$datos['NumAlb'];
		}else{
			$numAlb=0;
		}
		$sql='INSERT INTO `modulo_etiquetado_temporal`(`num_lote`, `tipo`,
		 `fecha_env`, `fecha_cad`, `idArticulo`, `numAlb`, `estado`, 
		 `productos`, `idUsuario`) VALUES('.$datos['idReal'].', '.$datos['tipo'].', "'.$datos['fechaEnv'].'",
		 "'.$datos['fechaCad'].'", '.$datos['idProducto'].', '.$numAlb.', "'.$datos['estado'].'"
		 ,'."'".$PrepProductos."'".', '.$datos['idUsuario'].')';
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
		}else{
			$respuesta['id']=$db->insert_id;
		}
		return $respuesta;
	}
	public function modificarTemporal($datos, $idTemporal){
			//@Objetivo:
			//Modificar el albarán temporal
			//@Retorna:
			//error de sql en caso de que lo tenga
		if($datos['NumAlb']>0){
			$numAlb=$datos['NumAlb'];
		}else{
			$numAlb=0;
		}
		$respuesta=array();
        $db = $this->db;
		$UnicoCampoProductos=json_encode($datos['productos']);
		$PrepProductos = $db->real_escape_string($UnicoCampoProductos);
		$sql='UPDATE `modulo_etiquetado_temporal` SET 
		`num_lote`='.$datos['idReal'].',`tipo`='.$datos['tipo'].',`fecha_env`="'.$datos['fechaEnv'].'"
		,`fecha_cad`="'.$datos['fechaCad'].'",`idArticulo`='.$datos['idProducto'].',`numAlb`='.$numAlb.'
		,`estado`="'.$datos['estado'].'",`productos`='."'".$PrepProductos."'".'
		,`idUsuario`='.$datos['idUsuario'].' WHERE id='.$idTemporal;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
		}
		return $respuesta;
	}
	
	public function todasEtiquetasLimite($limite){
		//@OBjetivo:
		//LIstar todas las etiquetas guardadas 
		$db=$this->db;
		$sql='SELECT a.num_lote, a.id , a.fecha_env, a.fecha_cad, a.estado, b.articulo_name , a.productos from modulo_etiquetado as a
		inner join articulos as b on a.idArticulo=b.idArticulo  '.$limite;
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
	public function todosTemporal(){
		//@Objetivo:
		//Mostrar todos los temporales
		$db=$this->db;
		$sql='select a.id, a.num_lote, a.fecha_env, b.articulo_name from 
		modulo_etiquetado_temporal as a inner join articulos as b on 
		a.idArticulo=b.idArticulo  ';
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
	
	public function buscarTemporal($idTemporal){
		//Objetivo;
		//BUscar los datos de un temporal
		$db=$this->db;
		$sql='select a.*, b.articulo_name FROM modulo_etiquetado_temporal
		 as a inner join articulos as b on a.idArticulo=b.idArticulo
		  where a.id='.$idTemporal;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}else{
			if ($result = $smt->fetch_assoc () ){
					$lote=$result;
					return $lote;
				}
		}
		
	}
	public function eliminarTemporal($idTemporal){
		//Objetivo:
		//eliminar un temporal determinado
		$db=$this->db;
		$sql='DELETE FROM `modulo_etiquetado_temporal` WHERE id='.$idTemporal;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
	}
	
	public function addLoteGuardado($datos){
		//OBjetivo:
		//GUardar un lote nuevo o modificarlo si ya existe
		$db=$this->db;
		$UnicoCampoProductos=json_encode($datos['productos']);
		$PrepProductos = $db->real_escape_string($UnicoCampoProductos);
		if($datos['idReal']>0){
			$sql='UPDATE `modulo_etiquetado` SET 
			`tipo`="'.$datos['tipo'].'",`fecha_env`="'.$datos['fecha_env'].'",`fecha_cad`="'.$datos['fecha_cad'].'",
			`idArticulo`='.$datos['idArticulo'].',`numAlb`='.$datos['numAlb'].',`estado`="'.$datos['estado'].'",
			`productos`='."'".$PrepProductos."'".',`idUsuario`='.$datos['idUsuario'].' where id='.$datos['idReal'];
		}else{
			$sql='INSERT INTO `modulo_etiquetado`(`tipo`, 
			`fecha_env`, `fecha_cad`, `idArticulo`, `numAlb`, `estado`, 
			`productos`, `idUsuario`) VALUES ("'.$datos['tipo'].'", "'.$datos['fecha_env'].'",
			"'.$datos['fecha_cad'].'", '.$datos['idArticulo'].', '.$datos['numAlb'].',
			"'.$datos['estado'].'", '."'".$PrepProductos."'".', '.$datos['idUsuario'].')';
		}
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}else{
			$id=$db->insert_id;
			if($datos['idReal']==0){
				$sql='UPDATE modulo_etiquetado SET num_lote='.$id.' WHERE id='.$id;
				$smt=$this->consulta($sql);
				if (gettype($smt)==='array'){
						$respuesta['error']=$smt['error'];
						$respuesta['consulta']=$smt['consulta'];
						return $respuesta;
				}
			}
		}
	}
	
	function datosLote($idLote){
		//OBjetivo:
		//MOstrar todos los datos de un lote ya guardado
		$db=$this->db;
		$sql='select a.*, b.articulo_name FROM modulo_etiquetado
		 as a inner join articulos as b on a.idArticulo=b.idArticulo
		  where a.id='.$idLote;
		  $smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}else{
			if ($result = $smt->fetch_assoc () ){
					$lote=$result;
					return $lote;
				}
		}
	}
	function modifEstadoReal($estado, $id){
		//Objetivo:
		//MOdificar el estado de un lote real
		$db=$this->db;
		$sql='UPDATE modulo_etiquetado SET estado="'.$estado.'" where id='.$id;
		 $smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
	}
	
}

?>
