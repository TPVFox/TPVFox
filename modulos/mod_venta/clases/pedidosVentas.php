<?php 
include_once ('./clases/ClaseVentas.php');
class PedidosVentas extends ClaseVentas{
	
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM pedclit';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	
		public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		return $smt;
	}
	
	//~ public function AddClienteTemp($idCliente, $idTienda, $idUsuario, $estado){
		//~ //@Objetivo: Añadir un nuevo registro a la tabla de pedidos temporales
		//~ //@parametros:
		//~ //idCliente: id del cliente seleccionado 
		//~ //idTienda: id de la tienda 
		//~ //idUsuario: id del usuario 
		//~ //estado: estado del pedido
		
		//~ $db = $this->db;
		//~ $smt = $db->query ('INSERT INTO pedcliltemporales (idClientes, idTienda, idUsuario, estadoPedCli) VALUES ('.$idCliente.', '.$idTienda.', '.$idUsuario.', "'.$estado.'")');
		//~ $id=$db->insert_id;
		//~ $respuesta['id']=$id;
		//~ return $respuesta;
		
	//~ }
	
	//~ public function AddClienteTempPedidoGuardado($idCliente, $idTienda, $idUsuario, $estado, $idPedido){
		//~ //Objetivo:
		//~ //añadir un nuevo registro temporal cuando ya tenemos el id del pedido real
		//~ $db = $this->db;
		//~ $smt = $db->query ('INSERT INTO pedcliltemporales (idClientes, idTienda, idUsuario, estadoPedCli, idPedcli) VALUES ('.$idCliente.', '.$idTienda.', '.$idUsuario.', "'.$estado.'", '.$idPedido.')');
		//~ $id=$db->insert_id;
		//~ $respuesta['id']=$id;
		//~ return $respuesta;
	//~ }
	public function addPedidoTemp($idCliente,  $idTienda, $idUsuario, $estado, $idReal, $productos){
		$UnicoCampoProductos=json_encode($productos);
		$db = $this->db;
		$smt = $db->query ('INSERT INTO pedcliltemporales (idClientes, idTienda, idUsuario, estadoPedCli, idPedcli, Productos ) VALUES ('.$idCliente.', '.$idTienda.', '.$idUsuario.', "'.$estado.'", '.$idReal.', '."'".$UnicoCampoProductos."'".')');
		$id=$db->insert_id;
		$respuesta['sql']='INSERT INTO pedcliltemporales (idClientes, idTienda, idUsuario, estadoPedCli, idPedcli, Productos ) VALUES ('.$idCliente.', '.$idTienda.', '.$idUsuario.', "'.$estado.'", '.$idReal.', '."'".$UnicoCampoProductos."'".')';
		$respuesta['id']=$id;
		return $respuesta;
	}
	
	//~ public function ModClienteTemp($idCLiente, $idTemporal, $idTienda, $idUsuario, $estado){
		//~ //@Objetivo: Modificar los datos de la tabla temporal
		//~ $db = $this->db;
		//~ $smt = $db->query ('UPDATE pedcliltemporales set idClientes ='.$idCLiente.' , idTienda='.$idTienda.' , idUsuario='.$idUsuario.' ,  estadoPedCli="'.$estado.'" WHERE id='.$idTemporal);		
		
	//~ }
	public function ModificarPedidoTemp($idCliente, $idTemporal, $idTienda, $idUsuario, $estado, $idReal, $productos){
		$UnicoCampoProductos=json_encode($productos);
		$db = $this->db;
		$smt = $db->query ('UPDATE pedcliltemporales set idClientes ='.$idCliente.' , idTienda='.$idTienda.' , idUsuario='.$idUsuario.' ,  estadoPedCli="'.$estado.'", idPedcli ='.$idReal.', productos='."'".$UnicoCampoProductos ."'".' WHERE id='.$idTemporal);		
		
	}

	public function ModIdReal($idTemporal, $idPedido){
		//@Objetivo: Modificar el pedido temporal para insertar el id del pedido real
		$db = $this->db;
		$smt = $db->query ('UPDATE pedcliltemporales set idPedcli ='.$idPedido.' WHERE id='.$idTemporal);
	}

	public function BuscarIdTemporal($idTemporal){
		//@Objetivo: Buscar todos los campos de un pedido temporal determinado
		
		$tabla='pedcliltemporales';
		$where='id='.$idTemporal;
		$pedido = parent::SelectUnResult($tabla, $where);
		return $pedido;
	
	}
	
	//~ public function AddProducto($idTemporal, $productos, $total){
		//~ //@Objetivo: Añade al atabla temporal el listado de productos
		//~ $total=round($total, 2);
		//~ $UnicoCampoProductos=json_encode($productos);
		//~ $db = $this->db;
		//~ $PrepProductos=$db->real_escape_string($UnicoCampoProductos);
		//~ $smt = $db->query ('UPDATE pedcliltemporales set total='.$total.' ,  Productos ='."'".$PrepProductos ."'".' WHERE id='.$idTemporal);
		//~ $resultado="Correcto Add Id";
		//~ return $resultado;
	//~ }
	
	public function TodosTemporal(){
		//@Objetivo: Muestra los campos principales del temporal
			$db = $this->db;
			$smt=$db->query('SELECT tem.idPedcli, tem.id , tem.idClientes, tem.total, b.Nombre, c.Numpedcli from pedcliltemporales as tem left JOIN clientes as b on tem.idClientes=b.idClientes LEFT JOIN pedclit as c on tem.idPedcli=c.id');
			$pedidosPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($pedidosPrincipal,$result);
		}
		return $pedidosPrincipal;
		
	}
		public function AddPedidoGuardado($datos, $idPedido, $numPedido){
		//Objetivo:
		//Añade un registro de un pedido ya guardado en pedidos . Si el numero del pedido es mayor  de 0 o sea que hay un registro en pedidos 
		//lo añade a la tabla temporal si no añade un registro normal a la tabla pedido

		$db = $this->db;
		if ($idPedido>0){
		$smt = $db->query ('INSERT INTO pedclit (id, Numpedcli , Numtemp_pedcli, FechaPedido, idTienda, idUsuario, idCliente, estado, total, fechaCreacion) VALUES ('.$idPedido.' , '.$numPedido.' , '.$datos['NPedidoTemporal'].' , "'.$datos['fecha'].'", '.$datos['idTienda']. ', '.$datos['idUsuario'].', '.$datos['idCliente'].' , "'.$datos['estado'].'", '.$datos['total'].', "'.$datos['fechaCreacion'].'")');
		$id=$idPedido;
		}else{
		$smt = $db->query ('INSERT INTO pedclit (Numtemp_pedcli, FechaPedido, idTienda, idUsuario, idCliente, estado, total, fechaCreacion) VALUES ('.$datos['NPedidoTemporal'].' , "'.$datos['fecha'].'", '.$datos['idTienda']. ', '.$datos['idUsuario'].', '.$datos['idCliente'].' , "'.$datos['estado'].'", '.$datos['total'].', "'.$datos['fechaCreacion'].'")');
		$id=$db->insert_id;
		$smt = $db->query('UPDATE pedclit SET Numpedcli  = '.$id.' WHERE id ='.$id);
		}
		$productos = json_decode($datos['productos'], true); 
		foreach ( $productos as $prod){
			if($prod['estado']=='Activo'){
			if ($prod['codBarras']){
				$codBarras=$prod['codBarras'];
			}else{
				$codBarras=0;
			}
			if ($idPedido>0){
			$smt=$db->query('INSERT INTO pedclilinea (idpedcli , Numpedcli, idArticulo, cref, ccodbar, cdetalle, ncant, nunidades, precioCiva, iva, nfila, estadoLinea ) VALUES ('.$id.', '.$idPedido.' , '.$prod['idArticulo'].', '."'".$prod['crefTienda']."'".', '.$codBarras.', "'.$prod['articulo_name'].'", '.$prod['cant'].' , '.$prod['cant'].', '.$prod['pvpCiva'].' , '.$prod['iva'].', '.$prod['nfila'].', "'. $prod['estado'].'" )' );

			}else{
			$smt=$db->query('INSERT INTO pedclilinea (idpedcli , Numpedcli, idArticulo, cref, ccodbar, cdetalle, ncant, nunidades, precioCiva, iva, nfila, estadoLinea ) VALUES ('.$id.', '.$id.' , '.$prod['idArticulo'].', '."'".$prod['crefTienda']."'".', '.$codBarras.', "'.$prod['articulo_name'].'", '.$prod['cant'].' , '.$prod['cant'].', '.$prod['pvpCiva'].' , '.$prod['iva'].', '.$prod['nfila'].', "'. $prod['estado'].'" )' );
		}
		}
	}
		foreach ($datos['DatosTotales']['desglose'] as  $iva => $basesYivas){
			if($idPedido>0){
			$smt=$db->query('INSERT INTO pedcliIva (idpedcli ,  Numpedcli , iva , importeIva, totalbase) VALUES ('.$id.', '.$idPedido.' , '.$iva.', '.$basesYivas['iva'].' , '.$basesYivas['base'].')');

			}else{
			$smt=$db->query('INSERT INTO pedcliIva (idpedcli ,  Numpedcli , iva , importeIva, totalbase) VALUES ('.$id.', '.$id.' , '.$iva.', '.$basesYivas['iva'].' , '.$basesYivas['base'].')');

			}
		}
		
		return $resultado;
	}
	
	public function TodosPedidosFiltro($filtro){
		//@Objetivo: Todos los pedidos guardados pero ultilizando el filtro
		$db=$this->db;
		$smt=$db->query('SELECT a.id , a.Numpedcli, a.FechaPedido, b.Nombre, a.total, a.estado FROM `pedclit` as a LEFT JOIN clientes as b on a.idCliente=b.idClientes '.$filtro);
		$pedidosPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($pedidosPrincipal,$result);
		}
		return $pedidosPrincipal;
	}
	
	public function EliminarRegistroTemporal($idTemporal, $idPedido){
		//@Objetivo:
		// Cuando un pedido pasa de temporal a pedidos se borran los registros temporales
		$db=$this->db;
		if ($idPedido>0){
			$smt=$db->query('DELETE FROM pedcliltemporales WHERE idPedcli='.$idPedido);
		}else{
			$smt=$db->query('DELETE FROM pedcliltemporales WHERE id='.$idTemporal);
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
		$smt=$db->query('DELETE FROM pedclit where id='.$idPedido );
		$smt=$db->query('DELETE FROM pedclilinea where idpedcli='.$idPedido );
		$smt=$db->query('DELETE FROM pedcliIva where idpedcli='.$idPedido );
		
	}
	
	public function contarPedidosTemporal($idPedido){
		//@Objetivo:
		//Contar los registros temporales que tiene un id real
		$db=$this->db;
		$smt=$db->query('Select count(id) as numPedTemp FROM pedcliltemporales where idPedcli='.$idPedido );
		if ($result = $smt->fetch_assoc () ){
			$pedido=$result;
		}
		return $pedido;
		}
		
	public function sumarIva($numPedido){
		//@Objetivo:
		//Suma importe iva y totoal base de todos los registro de un pedido determinado
		$db=$this->db;
		$smt=$db->query('select sum(importeIva ) as importeIva , sum(totalbase) as  totalbase from pedcliIva where Numpedcli ='.$numPedido);
		if ($result = $smt->fetch_assoc () ){
			$pedido=$result;
		}
		return $pedido;
	}
	
	//~ public function buscarNumPedido($idTemporal){
		//~ //@Objetivo:
		//~ //Busca el número de pedido de un pedido temporal
		//~ $db=$this->db;
		//~ $smt=$db->query('select  Numpedcli from pedclit where id='.$idTemporal);
		//~ if ($result = $smt->fetch_assoc () ){
			//~ $pedido=$result;
		//~ }
		//~ return $pedido;
	//~ }
	
	public function buscarNumPedidoId($idTemporal){
		//@Objetivo:
		//buscar el id de un número de pedido determinado
		$db=$this->db;
		$smt=$db->query('select  Numpedcli, id from pedclit where Numpedcli='.$idTemporal);
		if ($result = $smt->fetch_assoc () ){
			$pedido=$result;
		}
		$pedido['Nitems']= $smt->num_rows;
		return $pedido;
	}
	public function PedidosClienteGuardado($busqueda, $idCliente){
		//@Objetivo:
		//Buscar algunos datos de un pedido guardado
		$db=$this->db;
		$pedido['busqueda']=$busqueda;
		if ($busqueda>0){
		$smt=$db->query('select  Numpedcli, id , FechaPedido , total from pedclit where Numpedcli='.$busqueda.' and  idCliente='. $idCliente);
		if ($smt){
			if ($result = $smt->fetch_assoc () ){
				$pedido=$result;
			}
			$pedido['Nitem']=1;
		}
		}else{
			$smt=$db->query('SELECT  Numpedcli, FechaPedido , total , id from pedclit where idCliente='.$idCliente .' and estado="Guardado"');
			$pedidosPrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
				array_push($pedidosPrincipal,$result);	
			}
			
			$pedido['datos']=$pedidosPrincipal;
			
		}
		return $pedido;
	}
	
	public function ModificarEstadoPedido($idPedido, $estado){
		//@Objetivo:
		//MOdificar el estado de un pedido real indicado
		$db=$this->db;
		$smt=$db->query('UPDATE pedclit SET estado="'.$estado.'" WHERE id='.$idPedido);
		return $resultado;
	}
	
	public function ComprobarPedidos($idCliente, $estado){
		//@Objetivo:
		//Comprobar los pedidos de un cliente determinado con el estado guardado
		$db=$this->db;
		$estado='"'.'Guardado'.'"';
		$smt=$db->query('SELECT  id from pedclit where idCliente='.$idCliente .' and estado='.$estado);
		while ( $result = $smt->fetch_assoc () ) {
			$pedidos['ped']=1;
		}
		return $pedidos;
	}
	public function modTotales($res, $total, $totalivas){
		//@Objetivo:
		//Modificar el total de un albarán temporal
		$db=$this->db;
		$smt=$db->query('UPDATE pedcliltemporales set total='.$total .' , total_ivas='.$totalivas .' where id='.$res);
	}
}

?>
