<?php 

class AlbaranesCompras{
	public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		return $smt;
	}
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM albprot';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	public function modificarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoPedido, $fecha ,  $idAlbaranTemporal, $productos, $pedidos, $suNumero){
		$db = $this->db;
		$UnicoCampoProductos=json_encode($productos);
		$UnicoCampoPedidos=json_encode($pedidos);
		$smt=$db->query('UPDATE albproltemporales SET idUsuario ='.$idUsuario.' , idTienda='.$idTienda.' , estadoAlbPro="'.$estadoPedido.'" , fechaInicio="'.$fecha.'"  ,Productos='."'".$UnicoCampoProductos."'".', Pedidos='."'".$UnicoCampoPedidos."'".' , Su_numero='.$suNumero.' WHERE id='.$idAlbaranTemporal);
		$respuesta['sql']=$sql;
		$respuesta['idTemporal']=$numPedidoTemp;
		$respuesta['productos']=$UnicoCampoProductos;
		$respuesta['pedidos']=$UnicoCampoPedidos;
		return $respuesta;
	}
	public function insertarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoPedido, $fecha ,  $productos, $idProveedor, $pedidos, $suNumero){
		$db = $this->db;
		$UnicoCampoProductos=json_encode($productos);
		$UnicoCampoPedidos=json_encode($pedidos);
		$smt = $db->query ('INSERT INTO albproltemporales ( idUsuario , idTienda , estadoAlbPro , fechaInicio, idProveedor,  Productos, Pedidos , Su_numero) VALUES ('.$idUsuario.' , '.$idTienda.' , "'.$estadoPedido.'" , "'.$fecha.'", '.$idProveedor.' , '."'".$UnicoCampoProductos."'".' , '."'".$UnicoCampoPedidos."'".', '.$suNumero.')');
		$id=$db->insert_id;
		$respuesta['id']=$id;
		$respuesta['sql']=$sql;
		$respuesta['productos']=$productos;
		
		return $respuesta;
	}
	public function addNumRealTemporal($idTemporal, $idReal){
		$db=$this->db;
		$smt=$db->query('UPDATE albproltemporales set numalbpro ='.$idReal .'  where id='.$idTemporal);
		
		$resultado['sql']=$sql;
		return $resultado;
	}
	public function modEstadoAlbaran($idAlbaran, $estado){
		$db=$this->db;
		$smt=$db->query('UPDATE albprot set estado="'.$estado .'"  where id='.$idAlbaran);
		
		$resultado['sql']=$sql;
		return $resultado;
	}
	public function modTotales($res, $total, $totalivas){
		$db=$this->db;
		$smt=$db->query('UPDATE albproltemporales set total='.$total .' , total_ivas='.$totalivas .' where id='.$res);
		$sql='UPDATE albproltemporales set total='.$total .' , total_ivas='.$totalivas .' where id='.$res;
		$resultado['sql']=$sql;
		return $resultado;
	}
	
	public function buscarAlbaranTemporal($idAlbaranTemporal){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM albproltemporales WHERE id='.$idAlbaranTemporal);
		if ($result = $smt->fetch_assoc () ){
			$albaran=$result;
		}
		return $albaran;
	}
	public function buscarAlbaranNumero($numAlbaran){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM albprot WHERE Numalbpro='.$numAlbaran);
		if ($result = $smt->fetch_assoc () ){
			$albaran=$result;
		}
		return $albaran;
	}
	public function eliminarAlbaranTablas($idAlbaran){
		$db=$this->db;
		$smt=$db->query('DELETE FROM albprot where id='.$idAlbaran );
		$smt=$db->query('DELETE FROM albprolinea where idalbcli ='.$idAlbaran );
		$smt=$db->query('DELETE FROM albproIva where idalbcli ='.$idAlbaran );
		$smt=$db->query('DELETE FROM pedproAlb where idAlbaran ='.$idAlbaran );
		
	}
	
	public function AddAlbaranGuardado($datos, $idAlbaran){
		$db = $this->db;
		if ($idAlbaran>0){
			$smt = $db->query ('INSERT INTO albprot (Numalbpro, Fecha, idTienda , idUsuario , idProveedor , estado , total, Su_numero) VALUES ('.$idAlbaran.', "'.$datos['fecha'].'", '.$datos['idTienda'].', '.$datos['idUsuario'].', '.$datos['idProveedor'].', "'.$datos['estado'].'", '.$datos['total'].', '.$datos['suNumero'].'])');
			$id=$db->insert_id;
		
		}else{
			$smt = $db->query ('INSERT INTO albprot (Numtemp_albpro, Fecha, idTienda , idUsuario , idProveedor , estado , total, Su_numero) VALUES ('.$datos['Numtemp_albpro'].' , "'.$datos['fecha'].'", '.$datos['idTienda']. ', '.$datos['idUsuario'].', '.$datos['idProveedor'].' , "'.$datos['estado'].'", '.$datos['total'].', '.$datos['suNumero'].')');
			$id=$db->insert_id;
			$smt = $db->query('UPDATE albprot SET Numalbpro  = '.$id.' WHERE id ='.$id);
			$sql='INSERT INTO albprot (Numtemp_albpro, Fecha, idTienda , idUsuario , idProveedor , estado , total) VALUES ('.$datos['Numtemp_albpro'].' , "'.$datos['fecha'].'", '.$datos['idTienda']. ', '.$datos['idUsuario'].', '.$datos['idProveedor'].' , "'.$datos['estado'].'", '.$datos['total'].')';
			$resultado['sql']=$sql;
		}
		$productos = json_decode($datos['productos'], true);
		foreach ( $productos as $prod){
			if ($prod['ccodbar']){
				$codBarras=$prod['ccodbar'];
			}else{
				$codBarras=0;
			}
			if ($prod['Numpedpro']){
				$numPed=$prod['Numpedpro'];
			}else{
				$numPed=0;
			}
			if ($prod['crefProveedor']){
				$refProveedor=$prod['crefProveedor'];
			}else{
				$refProveedor=0;
			}
			if ($idAlbaran>0){
			$smt=$db->query('INSERT INTO albprolinea (idalbpro  , Numalbpro  , idArticulo , cref, ccodbar, cdetalle, ncant, nunidades, costeSiva, iva, nfila, estadoLinea, ref_prov , Numpedpro ) VALUES ('.$id.', '.$idAlbaran.' , '.$prod['idArticulo'].', '.$prod['cref'].', '.$codBarras.', "'.$prod['cdetalle'].'", '.$prod['ncant'].' , '.$prod['ncant'].', '.$prod['ultimoCoste'].' , '.$prod['iva'].', '.$prod['nfila'].', "'. $prod['estado'].'" , '.$refProveedor.', '.$numPed.')' );

			}else{
			$smt=$db->query('INSERT INTO albprolinea (idalbpro  , Numalbpro  , idArticulo , cref, ccodbar, cdetalle, ncant, nunidades, costeSiva, iva, nfila, estadoLinea, ref_prov  , Numpedpro ) VALUES ('.$id.', '.$id.' , '.$prod['idArticulo'].', '.$prod['cref'].', '.$codBarras.', "'.$prod['cdetalle'].'", '.$prod['ncant'].' , '.$prod['ncant'].', '.$prod['ultimoCoste'].' , '.$prod['iva'].', '.$prod['nfila'].', "'. $prod['estado'].'" , '.$refProveedor.', '.$numPed.')' );
			}
			
		} 
		foreach ($datos['DatosTotales']['desglose'] as  $iva => $basesYivas){
			if($idAlbaran>0){
			$smt=$db->query('INSERT INTO albproIva (idalbpro  ,  Numalbpro  , iva , importeIva, totalbase) VALUES ('.$id.', '.$idAlbaran.' , '.$iva.', '.$basesYivas['iva'].' , '.$basesYivas['base'].')');

			}else{
			$smt=$db->query('INSERT INTO albproIva (idalbpro  ,  Numalbpro  , iva , importeIva, totalbase) VALUES ('.$id.', '.$id.' , '.$iva.', '.$basesYivas['iva'].' , '.$basesYivas['base'].')');

			}
		}
		$pedidos = json_decode($datos['pedidos'], true); 
		foreach ($pedidos as $pedido){
			if($idAlbaran>0){
				$smt=$db->query('INSERT INTO pedproAlb (idAlbaran  ,  numAlbaran   , idPedido , numPedido) VALUES ('.$id.', '.$idAlbaran.' ,  '.$pedido['idPedido'].' , '.$pedido['Numpedpro'].')');

				}else{
				$smt=$db->query('INSERT INTO pedproAlb (idAlbaran  ,  numAlbaran   , idPedido , numPedido) VALUES ('.$id.', '.$id.' ,  '.$pedido['idPedido'].' , '.$pedido['Numpedpro'].')');
				$resultado='INSERT INTO pedproAlb (idAlbaran  ,  numAlbaran   , idPedido , numPedido) VALUES ('.$id.', '.$id.' ,  '.$pedido['idPedido'].' , '.$pedido['Numpedpro'].')';
				}
		}
		return $resultado;
	}
	public function EliminarRegistroTemporal($idTemporal, $idAlbaran){
		$db=$this->db;
		if ($idAlbaran>0){
			$smt=$db->query('DELETE FROM albproltemporales WHERE numalbpro ='.$idAlbaran);
			$sql='DELETE FROM albproltemporales WHERE numalbpro ='.$idAlbaran;
		}else{
			$smt=$db->query('DELETE FROM albproltemporales WHERE id='.$idTemporal);
		}
		return $sql;
	}
	public function TodosTemporal(){
			$db = $this->db;
			$smt = $db->query ('SELECT * from albproltemporales');
			$albaranPrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
				array_push($albaranPrincipal,$result);
			}
			return $albaranPrincipal;
		
	}
	public function TodosAlbaranes(){
		$db=$this->db;
		$smt=$db->query('SELECT a.id , a.Numalbpro , a.Fecha , b.nombrecomercial, a.total, a.estado FROM `albprot` as a LEFT JOIN proveedores as b on a.idProveedor =b.idProveedor ');
		$albaranesPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($albaranesPrincipal,$result);
		}
		return $albaranesPrincipal;
	}
	public function sumarIva($numAlbaran){
		$db=$this->db;
		$smt=$db->query('select sum(importeIva ) as importeIva , sum(totalbase) as  totalbase from albproIva where  Numalbpro  ='.$numAlbaran);
		if ($result = $smt->fetch_assoc () ){
			$albaran=$result;
		}
		return $albaran;
	}
	public function datosAlbaran($idAlbaran){
		$db=$this->db;
		$smt = $db->query ('SELECT * from albprot where id='.$idAlbaran);
			if ($result = $smt->fetch_assoc () ){
			$albaran=$result;
		}
		return $albaran;
	}
	public function ProductosAlbaran($idAlbaran){
		$db=$this->db;
		$smt=$db->query('SELECT * from  albprolinea where idalbpro='.$idAlbaran);
		$albaranesPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($albaranesPrincipal,$result);
		}
		return $albaranesPrincipal;
	}
	public function IvasAlbaran($idAlbaran){
		$db=$this->db;
		$smt=$db->query('SELECT * from  albproIva where idalbpro='.$idAlbaran);
		$albaranesPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($albaranesPrincipal,$result);
		}
		return $albaranesPrincipal;
	}
	public function PedidosAlbaranes($idAlbaran){
		$db=$this->db;
		$smt=$db->query('SELECT * from  pedproAlb where idAlbaran='.$idAlbaran);
		$albaranesPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($albaranesPrincipal,$result);
		}
		return $albaranesPrincipal;
	}
		
	
	
}


?>
