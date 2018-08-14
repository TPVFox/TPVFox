<?php 
include_once $URLCom.'/modulos/mod_venta/clases/ClaseVentas.php';
class PedidosVentas extends ClaseVentas{
	
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM pedclit';
		$respuesta = $this->consulta($sql);
		if (gettype($respuesta)==='object'){
			$this->num_rows = $respuesta->fetch_object()->num_reg;
		} else {
			// Es un array porque hubo un fallo
			echo '<pre>';
			print_r($respuesta);
			echo '</pre>';
		}
	}
	
		public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		if ($smt) {
			return $smt;
		} else {
			$repuesta = array();
			$respuesta['consulta'] = $sql;
			$respuesta['error'] = $db->error;
			return $respuesta;
		}
	}
	
	public function addPedidoTemp($idCliente,  $idTienda, $idUsuario, $estado, $idReal, $productos){
		
		$db = $this->db;
		$UnicoCampoProductos=json_encode($productos);
		$PrepProductos = $db->real_escape_string($UnicoCampoProductos);
		$sql='INSERT INTO pedcliltemporales (idClientes, idTienda, idUsuario,
		 estadoPedCli, idPedcli, Productos ) VALUES ('.$idCliente.', '
		 .$idTienda.', '.$idUsuario.', "'.$estado.'", '.$idReal.', "'.$PrepProductos.'")';
		$smt=$this->consulta($sql);
			if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
			}else{
				$id=$db->insert_id;
				$respuesta['id']=$id;
			}
			return $respuesta;
	}
	
	
	public function ModificarPedidoTemp($idCliente, $idTemporal, $idTienda, $idUsuario, $estado, $idReal, $productos){
		
		$db = $this->db;
		$UnicoCampoProductos=json_encode($productos);
		$PrepProductos = $db->real_escape_string($UnicoCampoProductos);
		$sql='UPDATE pedcliltemporales set idClientes ='.$idCliente
		.' , idTienda='.$idTienda.' , idUsuario='.$idUsuario.' ,  estadoPedCli="'
		.$estado.'", idPedcli ='.$idReal.', productos="'.$PrepProductos 
		.'" WHERE id='.$idTemporal;		
		$smt=$this->consulta($sql);
			if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
			}
	}

	public function ModIdReal($idTemporal, $idPedido){
		//@Objetivo: Modificar el pedido temporal para insertar el id del pedido real
		$db = $this->db;
		$sql='UPDATE pedcliltemporales set idPedcli ='.$idPedido
		.' WHERE id='.$idTemporal;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
	}

	public function BuscarIdTemporal($idTemporal){
		//@Objetivo: Buscar todos los campos de un pedido temporal determinado
		
		$tabla='pedcliltemporales';
		$where='id='.$idTemporal;
		$pedido = parent::SelectUnResult($tabla, $where);
		return $pedido;
	
	}
	public function TodosTemporal(){
		//@Objetivo: Muestra los campos principales del temporal
		$db = $this->db;
		$sql='SELECT tem.idPedcli, tem.id , tem.idClientes, tem.total,
		 b.Nombre, c.Numpedcli from pedcliltemporales as tem left JOIN 
		 clientes as b on tem.idClientes=b.idClientes LEFT JOIN pedclit 
		 as c on tem.idPedcli=c.id';
		// Debemos crear un metodo de consulta igual para todos, poder controlar el error y mostrarlo.
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}else{
			$pedidosPrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
				array_push($pedidosPrincipal,$result);
			}
			return $pedidosPrincipal;
		}
		
	}
		public function AddPedidoGuardado($datos, $idPedido){
		//Objetivo:
		//Añade un registro de un pedido ya guardado en pedidos . Si el numero del pedido es mayor  de 0 o sea que hay un registro en pedidos 
		//lo añade a la tabla temporal si no añade un registro normal a la tabla pedido
		$respuesta=array();
		$db = $this->db;
		if ($idPedido>0){
			$sql='INSERT INTO pedclit (id, Numpedcli , Numtemp_pedcli, 
			FechaPedido, idTienda, idUsuario, idCliente, estado, total, fechaCreacion)
			 VALUES ('.$idPedido.' , '.$idPedido.' , '.$datos['NPedidoTemporal'].' , "'
			 .$datos['fecha'].'", '.$datos['idTienda']. ', '.$datos['idUsuario'].', '
			 .$datos['idCliente'].' , "'.$datos['estado'].'", '.$datos['total'].', "'
			 .$datos['fechaCreacion'].'")';
			$smt=$this->consulta($sql);
			if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
			}else{
				$id=$idPedido;
			}
		}else{
			$sql='INSERT INTO pedclit (Numtemp_pedcli, FechaPedido,
			 idTienda, idUsuario, idCliente, estado, total, fechaCreacion) VALUES ('
			 .$datos['NPedidoTemporal'].' , "'.$datos['fecha'].'", '.$datos['idTienda']
			 . ', '.$datos['idUsuario'].', '.$datos['idCliente'].' , "'.$datos['estado']
			 .'", '.$datos['total'].', "'.$datos['fechaCreacion'].'")';
			 $smt=$this->consulta($sql);
			if (gettype($smt)==='array'){
					$respuesta['error']=$smt['error'];
					$respuesta['consulta']=$smt['consulta'];
			}else{
				$id=$db->insert_id;
				$sql='UPDATE pedclit SET Numpedcli  = '.$id.' WHERE id ='.$id;
				$smt=$this->consulta($sql);
				if (gettype($smt)==='array'){
					$respuesta['error']=$smt['error'];
					$respuesta['consulta']=$smt['consulta'];
				}
			}
		}
		$productos = json_decode($datos['productos'], true); 
		$i=1;
		foreach ( $productos as $prod){
			if($prod['estadoLinea']=='Activo'){
				$codBarras="";
				if ($prod['ccodbar']){
					$codBarras=$prod['ccodbar'];
				}
				$sql='INSERT INTO pedclilinea (idpedcli , Numpedcli, idArticulo,
				 cref, ccodbar, cdetalle, ncant, nunidades, precioCiva, iva, nfila, estadoLinea ,pvpSiva) 
				 VALUES ('.$id.', '.$id.' , '.$prod['idArticulo'].', '."'".$prod['cref']."'".', "'
				 .$codBarras.'", "'.$prod['cdetalle'].'", '.$prod['ncant'].' , '.$prod['nunidades']
				 .', '.$prod['precioCiva'].' , '.$prod['iva'].', '.$i.', "'. $prod['estadoLinea'].'", '.$prod['pvpSiva'].' )' ;
				$smt=$this->consulta($sql);
				if (gettype($smt)==='array'){
						$respuesta['error']=$smt['error'];
						$respuesta['consulta']=$smt['consulta'];
						break;
				}
			$i++;
			}
		}
		foreach ($datos['DatosTotales']['desglose'] as  $iva => $basesYivas){
			$sql='INSERT INTO pedcliIva (idpedcli ,  Numpedcli , iva , 
			importeIva, totalbase) VALUES ('.$id.', '.$id.' , '.$iva.', '.$basesYivas['iva']
			.' , '.$basesYivas['base'].')';
			$smt=$this->consulta($sql);
				if (gettype($smt)==='array'){
					$respuesta['error']=$smt['error'];
					$respuesta['consulta']=$smt['consulta'];
					break;
			}
			
		}
		
		 return $respuesta;
	}
	
	public function TodosPedidosFiltro($filtro){
		//@Objetivo: Todos los pedidos guardados pero ultilizando el filtro
		$db=$this->db;
		$sql= 'SELECT a.id , a.Numpedcli, a.FechaPedido, b.Nombre, a.total, a.estado 
		FROM `pedclit` as a LEFT JOIN clientes as b on a.idCliente=b.idClientes '.$filtro;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}else{
			$pedidosPrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
				array_push($pedidosPrincipal,$result);
			}
			$respuesta = array();
			$respuesta['Items'] = $pedidosPrincipal;
			$respuesta['consulta'] = $sql;
			return $respuesta;
		}
	}
	
	public function EliminarRegistroTemporal($idTemporal, $idPedido){
		//@Objetivo:
		// Cuando un pedido pasa de temporal a pedidos se borran los registros temporales
		$db=$this->db;
		if ($idPedido>0){
			$sql='DELETE FROM pedcliltemporales WHERE idPedcli='.$idPedido;
		}else{
			$sql='DELETE FROM pedcliltemporales WHERE id='.$idTemporal;
		}
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
	}
	
	public function datosPedidos($idPedido){
		//@Objetivo:
		//Mostrar todos los datos de un pedido
		$tabla='pedclit';
		$where='id= '.$idPedido;
		$pedido = parent::SelectUnResult($tabla, $where);
		return $pedido;
	}
	
	public function ProductosPedidos($idPedido){
		//@Objetivo:
		//Buscar los articulos de un pedido
		$tabla='pedclilinea';
		$where='idpedcli= '.$idPedido;
		$pedido = parent::SelectVariosResult($tabla, $where);
		return $pedido;
	}

	
	public function IvasPedidos($idPedido){
		//@Objetivo:
		//Buscar de la tabla pedcliIva todos los registros de un pedido
		$tabla='pedcliIva';
		$where='idpedcli= '.$idPedido;
		$pedido = parent::SelectVariosResult($tabla, $where);
		return $pedido;
	}
	
	public function eliminarPedidoTablas($idPedido){
		//@Objetivo:
		//Eliminar los registros de in id de pedido real
		$db=$this->db;
		$respuesta=array();
		$sql[0]='DELETE FROM pedclit where id='.$idPedido ;
		$sql[1]='DELETE FROM pedclilinea where idpedcli='.$idPedido ;
		$sql[2]='DELETE FROM pedcliIva where idpedcli='.$idPedido ;
		foreach ($sql as $consulta){
			$smt=$this->consulta($consulta);
			if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				break;
			}
		}
		return $respuesta;
		
	}
	
	public function sumarIva($numPedido){
		//@Objetivo:
		//Suma importe iva y totoal base de todos los registro de un pedido determinado
		$db=$this->db;
		$smt=$db->query('select sum(importeIva ) as importeIva , sum(totalbase) 
		as  totalbase from pedcliIva where Numpedcli ='.$numPedido);
		if ($result = $smt->fetch_assoc () ){
			$pedido=$result;
		}
		return $pedido;
	}

	public function PedidosClienteGuardado($busqueda, $idCliente){
		//@Objetivo:
		//Buscar algunos datos de un pedido guardado
		$db=$this->db;
		$pedido['busqueda']=$busqueda;
		if ($busqueda>0){
		$sql='select  Numpedcli, id , FechaPedido , total from 
		pedclit where Numpedcli='.$busqueda.' and  idCliente='. $idCliente;
		$smt=$this->consulta($sql);
			if (gettype($smt)==='array'){
				$pedido['error']=$smt['error'];
				$pedido['consulta']=$smt['consulta'];
				
			}else{
				if ($result = $smt->fetch_assoc () ){
					$pedido=$result;
				}
				$pedido['Nitem']=1;
			}
		}else{
			$sql='SELECT  Numpedcli, FechaPedido , total , 
			id from pedclit where idCliente='.$idCliente .' and estado="Guardado"';
			$smt=$this->consulta($sql);
			if (gettype($smt)==='array'){
				$pedido['error']=$smt['error'];
				$pedido['consulta']=$smt['consulta'];
				
			}else{
				$pedidosPrincipal=array();
				while ( $result = $smt->fetch_assoc () ) {
					array_push($pedidosPrincipal,$result);	
				}
				
				$pedido['datos']=$pedidosPrincipal;
			}
		}
		return $pedido;
	}
	
	public function ModificarEstadoPedido($idPedido, $estado){
		//@Objetivo:
		//MOdificar el estado de un pedido real indicado
		$db=$this->db;
		$sql='UPDATE pedclit SET estado="'.$estado.'" 
		WHERE id='.$idPedido;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$resultado['error']=$smt['error'];
				$resultado['consulta']=$smt['consulta'];
				return $resultado;
		}
	}
	
	public function ComprobarPedidos($idCliente, $estado){
		//@Objetivo:
		//Comprobar los pedidos de un cliente determinado con el estado guardado
		$db=$this->db;
		$estado='"'.'Guardado'.'"';
		$sql='SELECT  id from pedclit where idCliente='
		.$idCliente .' and estado='.$estado;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$resultado['error']=$smt['error'];
				$resultado['consulta']=$smt['consulta'];
				return $resultado;
		}else{
			$pedidos=array();
			while ( $result = $smt->fetch_assoc () ) {
				$pedidos['ped']=1;
			}
			return $pedidos;
		}
	}
	public function modTotales($res, $total, $totalivas){
		//@Objetivo:
		//Modificar el total de un albarán temporal
		$db=$this->db;
		$sql='UPDATE pedcliltemporales set total='.$total 
		.' , total_ivas='.$totalivas .' where id='.$res;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$resultado['error']=$smt['error'];
				$resultado['consulta']=$smt['consulta'];
				return $resultado;
		}
	}
	public function modificarFecha($idPedido, $fecha){
		$db=$this->db;
		$sql='UPDATE pedclit SET FechaPedido="'.$fecha.'" where id='.$idPedido;
			$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$resultado=array();
				$resultado['error']=$smt['error'];
				$resultado['consulta']=$smt['consulta'];
				return $resultado;
		}
	}
}

?>
