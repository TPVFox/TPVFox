<?php 
class PedidosVentas{
	private $db;
	private $num_rows;
	private $idPedido;
	private $numeroPedido;
	private $numTempPedido;
	private $fechaPedido;
	private $idTienda;
	private $idUsuario;
	private $idCliente;
	private $estadoPedido;
	private $formaPago;
	private $entregado;
	private $totalPedido;
	private $fechaCreacion;
	private $fechaModificacion;
	private $idPediLinea;
	private $idArticulo;
	private $cref;
	private $ccodbar;
	private $cdetalle;
	private $ncant;
	private $nunidades;
	private $precioCiva;
	private $ivaProducto;
	private $nFila;
	private $estadoLinea;
	private $idPedIva;
	private $idIva;
	private $importeIva;
	private $totalBase;
	private $idTemporal;
	private $fechaInicioTemporal;
	private $fechaFinTemporal;
	private $totalIvasTemp;
	private $productosTemp;
	
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM pedclit';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	
	public function datosPedClit($datos){
		$this->idPedido=$datos['id'];
		$this->numeroPedido=$datos['Numpedcli'];
		$this->numTempPedido=$datos['Numtemp_pedcli'];
		$this->fechaPedido=$datos['FechaPedido'];
		$this->idTienda=$datos['idTienda'];
		$this->idUsuario=$datos['idUsuario'];
		$this->idCliente=$datos['idCliente'];
		$this->estadoPedido=$datos['estado'];
		$this->formaPago=$datos['formaPago'];
		$this->entregado=$datos['entregado'];
		$this->totalPedido=$datos['total'];
		$this->fechaCreacion=$datos['fechaCreacion'];
		$this->fechaModificacion=$datos['fechaModificacion'];
	}
	public function datosPedClilinea($datos){
		$this->idPediLinea=$datos['idLinea'];
		$this->idPedido=$datos['idpedcli'];
		$this->numeroPedido=$datos['Numpedcli'];
		$this->idArticulo=$datos['idArticulo'];
		$this->cref=$datos['cref'];
		$this->ccodbar=$datos['ccodbar'];
		$this->cdetalle=$datos['cdetalle'];
		$this->ncant=$datos['ncant'];
		$this->nunidades=$datos['nunidades'];
		$this->precioCiva=$datos['precioCiva'];
		$this->ivaProducto=$datos['ivaProducto'];
		$this->nfila=$datos['nfila'];
		$this->estadoLinea=$datos['estadoLinea'];
	}
	public function datosPedCliIva($datos){
		$this->idPedIva=$datos['idPedIva'];
		$this->idPedido=$datos['idpedcli'];
		$this->numeroPedido=$datos['Numpedcli'];
		$this->idIva=$datos['iva'];
		$this->importeIva=$datos['importeIva'];
		$this->totalBase=$datos['totalBase'];
	}
	
	public function datosPedidoTemporal($datos){
		$this->idTemporal=$datos['idTemporal'];
		$this->estadoPedido=$datos['estadopedcli'];
		$this->idTienda=$datos['idTienda'];
		$this->idUsuario=$datos['idUsuario'];
		$this->fechaInicioTemporal=$datos['fechaInicio'];
		$this->fechaFinTemporal=$datos['fechaFinal'];
		$this->idCliente=$datos['idClientes'];
		$this->totalPedido=$datos['total'];
		$this->totalIvasTemp=$datos['total_ivas'];
		$this->productosTemp=$datos['Productos'];
		$this->idPedido=$datos['idPedcli'];
	}
	
		public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		return $smt;
	}
	//Añade un nuevo registro a la tabla temporal
	public function AddClienteTemp($idCliente, $idTienda, $idUsuario, $estadoPedido){
		$db = $this->db;
		$smt = $db->query ('INSERT INTO pedcliltemporales (idClientes, idTienda, idUsuario, estadoPedCli) VALUES ('.$idCliente.', '.$idTienda.', '.$idUsuario.', "'.$estadoPedido.'")');
		$sql='INSERT INTO pedcliltemporales (idClientes, idTienda, idUsuario, estadoPedCli) VALUES ('.$idCliente.', '.$idTienda.', '.$idUsuario.', "'.$estadoPedido.'")';
		$id=$db->insert_id;
		$respuesta['id']=$id;
		$respuesta['sql']=$sql;
		return $respuesta;
		
	}
	//Añade un nuevo regustro a la tabla temporal de un pedido que ya esta guardado
	public function AddClienteTempPedidoGuardado($idCliente, $idTienda, $idUsuario, $estadoPedido, $idPedido){
		$db = $this->db;
		$smt = $db->query ('INSERT INTO pedcliltemporales (idClientes, idTienda, idUsuario, estadoPedCli, idPedcli) VALUES ('.$idCliente.', '.$idTienda.', '.$idUsuario.', "'.$estadoPedido.'", '.$idPedido.')');
		$sql='INSERT INTO pedcliltemporales (idClientes, idTienda, idUsuario, estadoPedCli, idPedcli) VALUES ('.$idCliente.', '.$idTienda.', '.$idUsuario.', "'.$estadoPedido.'", '.$idPedido.')';
		$id=$db->insert_id;
		$respuesta['id']=$id;
		$respuesta['sql']=$sql;
		return $respuesta;
	}
	//Modifica los datos bases de un registro en la tabla temporal
	public function ModClienteTemp($idCLiente, $numPedidoTemp, $idTienda, $idUsuario, $estadoPedido){
		$db = $this->db;
		$smt = $db->query ('UPDATE pedcliltemporales set idClientes ='.$idCLiente.' , idTienda='.$idTienda.' , idUsuario='.$idUsuario.' ,  estadoPedCli="'.$estadoPedido.'" WHERE id='.$numPedidoTemp);		
		return $sql;
	}
	//Modifica los el numero de pedido guardado en la tabla temporal
	public function ModNumPedidoTtemporal($idTemporal, $idPedido){
		$db = $this->db;
		$smt = $db->query ('UPDATE pedcliltemporales set idPedcli ='.$idPedido.' WHERE id='.$idTemporal);
		$sql='UPDATE pedcliltemporales set idPedcli ='.$idPedido.' WHERE id='.$idTemporal;
		return $sql;
	}
	//Busca todos los campos de un registro en la tabla temporal
	public function BuscarIdTemporal($idTemporal){
		$db = $this->db;
		$smt = $db->query ('SELECT * from pedcliltemporales WHERE id='.$idTemporal);
		if ($result = $smt->fetch_assoc () ){
			$pedido=$result;
		}
		return $pedido;
	
	}
	//Añade a la tabla temporal los productos en json
	public function AddProducto($idTemporal, $productos, $total){
		$total=round($total, 2);
		$UnicoCampoProductos=json_encode($productos);
		$db = $this->db;
		$PrepProductos=$db->real_escape_string($UnicoCampoProductos);
		$smt = $db->query ('UPDATE pedcliltemporales set total='.$total.' ,  Productos ='."'".$PrepProductos ."'".' WHERE id='.$idTemporal);
		$resultado="Correcto Add Id";
		return $resultado;
	}
	//Muestra todos los temporales
	public function TodosTemporal(){
			$db = $this->db;
			$smt = $db->query ('SELECT * from pedcliltemporales');
			$pedidosPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($pedidosPrincipal,$result);
		}
		return $pedidosPrincipal;
		
	}
	//Añade un registro de un pedido ya guardado en pedidos . Si el numero del pedido es mayor  de 0 o sea que hay un registro en pedidos 
	//lo añade a la tabla temporal si no añade un registro normal a la tabla pedido
	public function AddPedidoGuardado($datos, $idPedido){
		$db = $this->db;
		if ($idPedido>0){
		$smt = $db->query ('INSERT INTO pedclit (Numpedcli , Numtemp_pedcli, FechaPedido, idTienda, idUsuario, idCliente, estado, total, fechaCreacion) VALUES ('.$idPedido.' , '.$datos['NPedidoTemporal'].' , "'.$datos['fecha'].'", '.$datos['idTienda']. ', '.$datos['idUsuario'].', '.$datos['idCliente'].' , "'.$datos['estado'].'", '.$datos['total'].', "'.$datos['fechaCreacion'].'")');
		$id=$db->insert_id;
		}else{
		$smt = $db->query ('INSERT INTO pedclit (Numtemp_pedcli, FechaPedido, idTienda, idUsuario, idCliente, estado, total, fechaCreacion) VALUES ('.$datos['NPedidoTemporal'].' , "'.$datos['fecha'].'", '.$datos['idTienda']. ', '.$datos['idUsuario'].', '.$datos['idCliente'].' , "'.$datos['estado'].'", '.$datos['total'].', "'.$datos['fechaCreacion'].'")');
		$id=$db->insert_id;
		$smt = $db->query('UPDATE pedclit SET Numpedcli  = '.$id.' WHERE id ='.$id);
		}
		$productos = json_decode($datos['productos'], true); 
		foreach ( $productos as $prod){
			if ($prod['codBarras']){
				$codBarras=$prod['codBarras'];
			}else{
				$codBarras=0;
			}
			if ($idPedido>0){
			$smt=$db->query('INSERT INTO pedclilinea (idpedcli , Numpedcli, idArticulo, cref, ccodbar, cdetalle, ncant, nunidades, precioCiva, iva, nfila, estadoLinea ) VALUES ('.$id.', '.$idPedido.' , '.$prod['idArticulo'].', '.$prod['crefTienda'].', '.$codBarras.', "'.$prod['articulo_name'].'", '.$prod['cant'].' , '.$prod['cant'].', '.$prod['pvpCiva'].' , '.$prod['iva'].', '.$prod['nfila'].', "'. $prod['estado'].'" )' );

			}else{
			$smt=$db->query('INSERT INTO pedclilinea (idpedcli , Numpedcli, idArticulo, cref, ccodbar, cdetalle, ncant, nunidades, precioCiva, iva, nfila, estadoLinea ) VALUES ('.$id.', '.$id.' , '.$prod['idArticulo'].', '.$prod['crefTienda'].', '.$codBarras.', "'.$prod['articulo_name'].'", '.$prod['cant'].' , '.$prod['cant'].', '.$prod['pvpCiva'].' , '.$prod['iva'].', '.$prod['nfila'].', "'. $prod['estado'].'" )' );
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
	//Todos los pedidos guardados
	public function TodosPedidos(){
		$db=$this->db;
		$smt=$db->query('SELECT a.id , a.Numpedcli, a.FechaPedido, b.Nombre, a.total, a.estado FROM `pedclit` as a LEFT JOIN clientes as b on a.idCliente=b.idClientes ');
		$pedidosPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($pedidosPrincipal,$result);
		}
		return $pedidosPrincipal;
	}
	public function TodosPedidosFiltro($filtro){
		$db=$this->db;
		$smt=$db->query('SELECT a.id , a.Numpedcli, a.FechaPedido, b.Nombre, a.total, a.estado FROM `pedclit` as a LEFT JOIN clientes as b on a.idCliente=b.idClientes '.$filtro);
		$pedidosPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($pedidosPrincipal,$result);
		}
		return $pedidosPrincipal;
	}
	// Cuando un pedido pasa de temporal a pedidos se borran los registros temporales
	public function EliminarRegistroTemporal($idTemporal, $idPedido){
		$db=$this->db;
		if ($idPedido>0){
			$smt=$db->query('DELETE FROM pedcliltemporales WHERE idPedcli='.$idPedido);
		}else{
			$smt=$db->query('DELETE FROM pedcliltemporales WHERE id='.$idTemporal);
		}
	}
	//Mostrar todos los datos de un pedido
	public function datosPedidos($idPedido){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM pedclit WHERE id= '.$idPedido );
		if ($result = $smt->fetch_assoc () ){
			$pedido=$result;
		}
		return $pedido;
	}
	//Busca los articulos de un pedido
	public function ProductosPedidos($idPedido){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM pedclilinea WHERE idpedcli= '.$idPedido );
		$pedidosPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($pedidosPrincipal,$result);
		}
		return $pedidosPrincipal;
	}

	//Busca de la tabla pedcliIva todos los registros de un pedido
	public function IvasPedidos($idPedido){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM pedcliIva WHERE idpedcli= '.$idPedido );
		$pedidosPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($pedidosPrincipal,$result);
		}
		return $pedidosPrincipal;
	}
	//Eliminar los registros de in id de pedido real
	public function eliminarPedidoTablas($idPedido){
		$db=$this->db;
		$smt=$db->query('DELETE FROM pedclit where id='.$idPedido );
		$smt=$db->query('DELETE FROM pedclilinea where idpedcli='.$idPedido );
		$smt=$db->query('DELETE FROM pedcliIva where idpedcli='.$idPedido );
		
	}
	//Contar los registros temporales que tiene un id real
	public function contarPedidosTemporal($idPedido){
		$db=$this->db;
		$smt=$db->query('Select count(id) as numPedTemp FROM pedcliltemporales where idPedcli='.$idPedido );
		if ($result = $smt->fetch_assoc () ){
			$pedido=$result;
		}
		return $pedido;
		}
		//Suma importe iva y totoal base de todos los registro de un pedido determinado
	public function sumarIva($numPedido){
		$db=$this->db;
		$smt=$db->query('select sum(importeIva ) as importeIva , sum(totalbase) as  totalbase from pedcliIva where Numpedcli ='.$numPedido);
		if ($result = $smt->fetch_assoc () ){
			$pedido=$result;
		}
		return $pedido;
	}
	//Busca el número de pedido de un pedido temporal
	public function buscarNumPedido($idPedidoTemporal){
		$db=$this->db;
		$smt=$db->query('select  Numpedcli from pedclit where id='.$idPedidoTemporal);
		if ($result = $smt->fetch_assoc () ){
			$pedido=$result;
		}
		return $pedido;
	}
	
	public function buscarNumPedidoId($idPedidoTemporal){
		$db=$this->db;
		$smt=$db->query('select  Numpedcli, id from pedclit where Numpedcli='.$idPedidoTemporal);
		if ($result = $smt->fetch_assoc () ){
			$pedido=$result;
		}
		$pedido['Nitems']= $smt->num_rows;
		return $pedido;
	}
	public function PedidosClienteGuardado($busqueda, $idCliente){
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
			//$sql='SELECT  Numpedcli, FechaPedido , total from pedclit where idCliente='.$idCliente;
			//$pedido['sql']=$sql;
			$pedidosPrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
				array_push($pedidosPrincipal,$result);	
			}
			
			$pedido['datos']=$pedidosPrincipal;
			
		}
		return $pedido;
	}
	//MOdificar el estado de un pedido real indicado
	public function ModificarEstadoPedido($idPedido, $estado){
		$db=$this->db;
		$smt=$db->query('UPDATE pedclit SET estado="'.$estado.'" WHERE id='.$idPedido);
		$sql='UPDATE pedclit SET estado='.$estado.' WHERE id='.$idPedido;
		$resultado['sql']=$sql;
		return $resultado;
	}
	//Comprobar los pedidos de un cliente determinado con el estado guardado
	public function ComprobarPedidos($idCliente, $estado){
		$db=$this->db;
		$estado='"'.'Guardado'.'"';
		$smt=$db->query('SELECT  id from pedclit where idCliente='.$idCliente .' and estado='.$estado);
		$sql='SELECT  id from pedclit where idCliente='.$idCliente .' and estado='.$estado;
		$pedidos['sql']=$sql;
		while ( $result = $smt->fetch_assoc () ) {
			$pedidos['ped']=1;
		}
		return $pedidos;
	}
}

?>
