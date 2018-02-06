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
}



?>
