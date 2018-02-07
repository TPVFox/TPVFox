<?php 
class PedidosCompras{
	public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		return $smt;
	}
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM pedprot';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	
	
	public function modificarDatosPedidoTemporal($idUsuario, $idTienda, $estadoPedido, $fecha ,  $numPedidoTemp, $productos){
		$db = $this->db;
		$UnicoCampoProductos=json_encode($productos);
		$smt=$db->query('UPDATE pedprotemporales SET idUsuario='.$idUsuario.' , idTienda='.$idTienda.' , estadoPedPro="'.$estadoPedido.'" , fechaInicio='.$fecha.'  ,Productos='."'".$UnicoCampoProductos."'".'  WHERE id='.$numPedidoTemp);
		$sql='UPDATE pedprotemporales SET idUsuario='.$idUsuario.' , idTienda='.$idTienda.' , estadoPedPro='.$estadoPedido.' , fechaInicio='.$fecha.'  ,Productos='."'".$UnicoCampoProductos."'".'  WHERE id='.$numPedidoTemp;
		$respuesta['sql']=$sql;
		$respuesta['idTemporal']=$numPedidoTemp;
		$respuesta['productos']=$UnicoCampoProductos;
	
		return $respuesta;
	}
	public function insertarDatosPedidoTemporal($idUsuario, $idTienda, $estadoPedido, $fecha ,  $productos, $idProveedor){
		$db = $this->db;
		$UnicoCampoProductos=json_encode($productos);
		$smt = $db->query ('INSERT INTO pedprotemporales ( idUsuario , idTienda , estadoPedPro , fechaInicio, idProveedor,  Productos ) VALUES ('.$idUsuario.' , '.$idTienda.' , "'.$estadoPedido.'" , "'.$fecha.'", '.$idProveedor.' , '."'".$UnicoCampoProductos."'".')');
		$sql='INSERT INTO pedprotemporales ( idUsuario , idTienda , estadoPedPro , fechaInicio, idProveedor, Productos) VALUES ('.$idUsuario.' , '.$idTienda.' , "'.$estadoPedido.'" , "'.$fecha.'", '.$idProveedor.' , '."'".$UnicoCampoProductos."'".' )';

		$id=$db->insert_id;
		$respuesta['id']=$id;
		$respuesta['sql']=$sql;
		$respuesta['productos']=$productos;
		
		return $respuesta;
	}
	public function modTotales($res, $total, $totalivas){
		$db=$this->db;
		$smt=$db->query('UPDATE pedprotemporales set total='.$total .' , total_ivas='.$totalivas .' where id='.$res);
		$sql='UPDATE pedprotemporales set total='.$total .' , total_ivas='.$totalivas .' where id='.$res;
		$resultado['sql']=$sql;
		return $resultado;
	}
	public function DatosTemporal($idTemporal){
		$db=$this->db;
		$smt=$db->query('SELECT * from pedprotemporales where id='.$idTemporal);
		if ($result = $smt->fetch_assoc () ){
			$pedido=$result;
		}
		return $pedido;
	}
	public function DatosPedido($idPedido){
		$db=$this->db;
		$smt=$db->query('SELECT * from pedprot where id='.$idTemporal);
		if ($result = $smt->fetch_assoc () ){
			$pedido=$result;
		}
		return $pedido;
	}
	
	public function eliminarPedidoTablas($idPedido){
		$db=$this->db;
		$smt=$db->query('DELETE FROM pedprot where id='.$idPedido );
		$smt=$db->query('DELETE FROM pedprolinea where idpedpro ='.$idPedido );
		$smt=$db->query('DELETE FROM pedproIva where idpedpro ='.$idPedido );
	}
	public function AddPedidoGuardado($datos, $idPedido){
		$db = $this->db;
		if ($idPedido>0){
			$smt = $db->query('INSERT INTO pedprot (Numpedpro, Numtemp_pedpro, FechaPedido, idTienda, idUsuario, idProveedor, estado, total, fechaCreacion) VALUES ('.$idPedido.', '.$datos['Numtemp_pedpro'].', "'.$datos['FechaPedido'].'", '.$datos['idTienda'].' , '.$datos['idUsuario'].', '.$datos['idProveedor'].', "'.$datos['estado'].'", '.$datos['total'].', "'.$datos['fechaCreacion'].'")');
			$id=$db->insert_id;
		}else{
			$smt=$db->query('INSERT INTO pedprot ( Numtemp_pedpro, FechaPedido, idTienda, idUsuario, idProveedor, estado, total, fechaCreacion) VALUES ('.$datos['Numtemp_pedpro'].', "'.$datos['FechaPedido'].'", '.$datos['idTienda'].' , '.$datos['idUsuario'].', '.$datos['idProveedor'].', "'.$datos['estado'].'", '.$datos['total'].', "'.$datos['fechaCreacion'].'")');
			$id=$db->insert_id;
			$smt=$db->query('UPDATE pedprot set Numpedpro='.$id.' WHERE id='.$id);
		}
		$productos = json_decode($datos['Productos'], true); 
		foreach ( $productos as $prod){
			if ($prod['ccodbar']){
				$codBarras=$prod['ccodbar'];
			}else{
				$codBarras=0;
			}
			if ($idPedido>0){
				$smt=$db->query('INSERT INTO pedprolinea (idpedpro, Numpedpro, idArticulo, cref, ref_prov , ccodbar, cdetalle, ncant, nunidades, costeSiva, iva, nfila, estadoLinea) values ('.$id.', '.$idPedido.', '.$prod['idArticulo'].', '.$prod['cref'].', '.$prod['cref'].', '.$codBarras.', "'.$prod['cdetalle'].'", '.$prod['ncant'].', '.$prod['nunidades'].', '.$prod['precioCiva'].', '.$prod['iva'].', '.$prod['nfila'].', "'.$prod['estado'].'")');
			}else{
				$smt=$db->query('INSERT INTO pedprolinea (idpedpro, Numpedpro, idArticulo, cref, ref_prov , ccodbar, cdetalle, ncant, nunidades, costeSiva, iva, nfila, estadoLinea) values ('.$id.', '.$id.', '.$prod['idArticulo'].', '.$prod['cref'].', '.$prod['cref'].', '.$codBarras.', "'.$prod['cdetalle'].'", '.$prod['ncant'].', '.$prod['nunidades'].', '.$prod['precioCiva'].', '.$prod['iva'].', '.$prod['nfila'].', "'.$prod['estado'].'")');
			}
		}
		foreach ($datos['DatosTotales']['desglose'] as  $iva => $basesYivas){
			if($idPedido>0){
				$smt=$db->query('INSERT INTO pedproIva (idpedpro, Numpedpro, iva, importeIva, totalbase) values ('.$id.', '.$idPedido.', '.$iva.', '.$basesYivas['iva'].', '.$basesYivas['base'].')');
			}else{
				$smt=$db->query('INSERT INTO pedproIva (idpedpro, Numpedpro, iva, importeIva, totalbase) values ('.$id.', '.$id.', '.$iva.', '.$basesYivas['iva'].', '.$basesYivas['base'].')');
				$sql='INSERT INTO pedproIva (idpedpro, Numpedpro, iva, importeIva, totalbase) values ('.$id.', '.$id.', '.$iva.', '.$basesYivas['iva'].', '.$basesYivas['base'].')';
				
			}
		}
		return $sql;
	}
	
	public function eliminarTemporal($idTemporal, $idPedido){
		$db=$this->db;
		if ($idPedido>0){
			$smt=$db->query('DELETE FROM pedprotemporales WHERE idPedpro='.$idPedido);
		}else{
			$smt=$db->query('DELETE FROM pedprotemporales WHERE id='.$idTemporal);
		}
	}
	public function TodosTemporal(){
			$db = $this->db;
			$smt = $db->query ('SELECT * from pedprotemporales');
			$pedidosPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($pedidosPrincipal,$result);
		}
		return $pedidosPrincipal;
		
	}
	public function TodosPedidos(){
		$db=$this->db;
		$smt=$db->query('SELECT a.id , a.Numpedpro , a.FechaPedido, b.nombrecomercial, a.total, a.estado FROM `pedprot` as a LEFT JOIN proveedores as b on a.idProveedor=b.idProveedor ');
		$pedidosPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($pedidosPrincipal,$result);
		}
		return $pedidosPrincipal;
	}
	public function datosPedidos($idPedido){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM pedprot WHERE id= '.$idPedido );
		if ($result = $smt->fetch_assoc () ){
			$pedido=$result;
		}
		return $pedido;
	}
	public function sumarIva($numPedido){
		$db=$this->db;
		$smt=$db->query('select sum(importeIva ) as importeIva , sum(totalbase) as  totalbase from pedproIva where Numpedpro ='.$numPedido);
		if ($result = $smt->fetch_assoc () ){
			$pedido=$result;
		}
		return $pedido;
	}
	
}



?>
