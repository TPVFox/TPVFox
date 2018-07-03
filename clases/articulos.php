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
		if ($smt) {
			return $smt;
		} else {
			$respuesta = array();
			$respuesta['consulta'] = $sql;
			$respuesta['error'] = $db->error;
			return $respuesta;
		}
	}
	
	public function addArticulosProveedores($datos){
		$db=$this->db;
		$sql='INSERT INTO articulosProveedores (idArticulo, idProveedor, crefProveedor, 
		coste, fechaActualizacion, estado) VALUE ('.$datos['idArticulo'].', '.$datos['idProveedor'].', '
		."'".$datos['refProveedor']."'".', '.$datos['coste'].', "'.$datos['fecha'].'", "'
		.$datos['estado'].'")';
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}
	}
	public function buscarReferencia($idArticulo, $idProveedor){
		$db=$this->db;
		$sql='SELECT * FROM articulosProveedores WHERE idArticulo='.$idArticulo.' and idProveedor='.$idProveedor;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}else{
			if ($result = $smt->fetch_assoc () ){
				$referencia=$result;
				return $referencia;
			}
		}
	}
	
	public function buscarNombreArticulo($idArticulo){
		$db=$this->db;
		$smt=$db->query('SELECT articulo_name FROM articulos WHERE idArticulo='.$idArticulo);
		if ($result = $smt->fetch_assoc () ){
			$referencia=$result;
		}
		return $referencia;
	}
	public function modificarProveedorArticulo($datos){
		$db=$this->db;
		$sql='UPDATE articulosProveedores SET crefProveedor='."'".$datos['refProveedor']
		."'".' WHERE idArticulo='.$datos['idArticulo'].' and idProveedor='
		.$datos['idProveedor'];
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}
	}
	public function modificarCosteProveedorArticulo($datos){
		$db=$this->db;
		$sql='UPDATE articulosProveedores SET coste='.$datos['coste']
		.',  fechaActualizacion="'.$datos['fecha'].'" WHERE idArticulo='
		.$datos['idArticulo'].' and idProveedor='.$datos['idProveedor'];
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}
		
	}
	
	public function addHistorico($datos){
		$db=$this->db;
		$sql='INSERT INTO historico_precios (idArticulo, Antes, Nuevo, Fecha_Creacion , NumDoc, 
		Dedonde, Tipo, estado, idUsuario) VALUES ('.$datos['idArticulo'].' , '.$datos['antes'].' , '.$datos['nuevo']
		.', NOW() , '.$datos['numDoc'].', '."'".$datos['dedonde']."'".', '
		."'".$datos['tipo']."'".' , '."'".$datos['estado']."'".', '.$datos['idUsuario'].')';
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}
	}
	public function historicoCompras($numDoc, $Dedonde, $tipo){
		$db=$this->db;
		$smt=$db->query('SELECT * from historico_precios where NumDoc='.$numDoc.' and  Dedonde ='."'".$Dedonde ."'".' and Tipo ='."'".$tipo."'");
		$historicoPrincipal=array();
		while ($result = $smt->fetch_assoc () ){
			array_push($historicoPrincipal,$result);
		}
		return $historicoPrincipal;
		
	}
	public function datosPrincipalesArticulo($idArticulo){
		$db=$this->db;
		$smt=$db->query('SELECT a.idArticulo, a.iva , a.articulo_name, a.beneficio , b.crefTienda
        FROM articulos as a left join articulosTiendas as b on a.idArticulo=b.idArticulo where a.idArticulo='.$idArticulo);
		if ($result = $smt->fetch_assoc () ){
			$articulo=$result;
            return $articulo;
		}
		
		}
		
		public function articulosPrecio($idArticulo){
			$db=$this->db;
			$smt=$db->query('SELECT * FROM 	articulosPrecios where idArticulo='.$idArticulo);
			if ($result = $smt->fetch_assoc () ){
			$articulo=$result;
		}
		return $articulo;
			}
			
		public function modificarEstadosHistorico($idAlbaran, $dedonde){
				$db=$this->db;
				$smt=$db->query('UPDATE  historico_precios set estado="Revisado"  where NumDoc='.$idAlbaran.' and Dedonde="'.$dedonde.'" and estado <> "Sin revisar"');
		}
				
		public function modArticulosPrecio($nuevoCiva, $nuevoSiva, $idArticulo){
					$db=$this->db;
                    $sql='UPDATE articulosPrecios SET pvpCiva='.$nuevoCiva.' , pvpSiva='.$nuevoSiva.' where idArticulo='.$idArticulo;
					$smt=$db->query('UPDATE articulosPrecios SET pvpCiva='.$nuevoCiva.' , pvpSiva='.$nuevoSiva.' where idArticulo='.$idArticulo);
                    //~ error_log($sql);
                    return $sql;
                    
		}
					
		public function modEstadoArticuloHistorico($idArticulo, $idAlbaran, $dedonde, $tipo, $estado){
				$db=$this->db;
				$smt=$db->query('UPDATE historico_precios set estado='."'".$estado."'".' where NumDoc='.$idAlbaran.' and Dedonde="'.$dedonde.'" and idArticulo='.$idArticulo.' and Tipo="'.$tipo.'"');
	$sql='UPDATE historico_precios set estado='."'".$estado."'".' where NumDoc='.$idAlbaran.' and Dedonde="'.$dedonde.'" and idArticulo='.$idArticulo.' and Tipo="'.$tipo.'"';
	return $sql;
		}
		public function datosArticulosPrincipal($idArticulo, $idTienda){
		$db=$this->db;
		$sql='select a.articulo_name , pre.pvpCiva , t.crefTienda, a.idArticulo
				FROM articulos as a 
				inner join articulosPrecios as pre on a.idArticulo=pre.idArticulo 
				
				inner join articulosTiendas as t on a.idArticulo=t.idArticulo
				where a.idArticulo='.$idArticulo.' and t.idTienda='.$idTienda;
		$smt=$this->consulta($sql);
		$articulo['sql']=$sql;
			if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
			}else{
				if ($result = $smt->fetch_assoc () ){
				$articulo=$result;
				
				}
				return $articulo;
			}
		
		}
		
	public function buscarPorNombre($valor, $idTienda){
		$db=$this->db;
		$sql='select a.articulo_name,  pre.pvpCiva , t.crefTienda, a.idArticulo
				FROM articulos as a 
				inner join articulosPrecios as pre on a.idArticulo=pre.idArticulo 
				
				inner join articulosTiendas as t on a.idArticulo=t.idArticulo
				where a.articulo_name like "%'.$valor.'%" and t.idTienda='.$idTienda.' group by  a.idArticulo LIMIT 0 , 30';
		
		
		$smt=$this->consulta($sql);
			if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
			}else{
				$articulosPrincipal=array();
				while ($result = $smt->fetch_assoc () ){
					array_push($articulosPrincipal,$result);
				}
				return $articulosPrincipal;
			}
	}
    public function ComprobarFechasHistorico($idArticulo, $fecha){
        //@ Objetivo :
        // Obtener registros historico de un producto que sean superiores a una fecha.
        $db=$this->db;
        $sql='SELECT * from historico_precios WHERE idArticulo='.$idArticulo.' and Fecha_Creacion > "'.$fecha.'"';
        $smt=$this->consulta($sql);
        if (gettype($smt)==='array'){
            $respuesta['error']=$smt['error'];
            $respuesta['consulta']=$smt['consulta'];
            return $respuesta;
        }else{
            $articulosPrincipal=array();
            while ($result = $smt->fetch_assoc () ){
                array_push($articulosPrincipal,$result);
            }
            return $articulosPrincipal;
        }
    }

	public function modificarRegHistorico($idRegistro, $estado){
         $db=$this->db;
         $sql='UPDATE historico_precios SET estado="'.$estado.'" where id='.$idRegistro;
         $smt=$this->consulta($sql);
         if (gettype($smt)==='array'){
            $respuesta['error']=$smt['error'];
            $respuesta['consulta']=$smt['consulta'];
            return $respuesta;
        }
    }
	
	
}


?>
