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
	}
	
		public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		return $smt;
	}
	
	public function AddClienteTemp($idCliente, $idTienda, $idUsuario, $estadoPedido){
		$db = $this->db;
		$smt = $db->query ('INSERT INTO pedcliltemporales (idClientes, idTienda, idUsuario, estadoPedCli) VALUES ('.$idCliente.', '.$idTienda.', '.$idUsuario.', "'.$estadoPedido.'")');
		$sql='INSERT INTO pedcliltemporales (idClientes, idTienda, idUsuario, estadoPedCli) VALUES ('.$idCliente.', '.$idTienda.', '.$idUsuario.', "'.$estadoPedido.'")';
		$id=$db->insert_id;
		return $id;
	}
	public function ModClienteTemp($idCLiente, $numPedidoTemp, $idTienda, $idUsuario, $estadoPedido){
		$db = $this->db;
		$smt = $db->query ('UPDATE pedcliltemporales set idClientes ='.$idCLiente.' , idTienda='.$idTienda.' , idUsuario='.$idUsuario.' ,  estadoPedCli="'.$estadoPedido.'" WHERE id='.$numPedidoTemp);
		//$sql='UPDATE pedcliltemporales set idClientes ='.$idCLiente.' , idTienda='.$idTienda.' , idUsuario='.$idUsuario.' ,  estadoPedCli="'.$estadoPedido.'" WHERE id='.$numPedido;
		return $sql;
	}
	public function BuscarIdTemporal($idTemporal){
		$db = $this->db;
		$smt = $db->query ('SELECT * from pedcliltemporales WHERE id='.$idTemporal);
		if ($result = $smt->fetch_assoc () ){
			$pedido=$result;
		}
		return $pedido;
	}
	
	public function AddProducto($idTemporal, $productos, $total){
		$total=round($total, 2);
		$UnicoCampoProductos=json_encode($productos);
		$db = $this->db;
		$PrepProductos=$db->real_escape_string($UnicoCampoProductos);
		$smt = $db->query ('UPDATE pedcliltemporales set total='.$total.' ,  Productos ='."'".$PrepProductos ."'".' WHERE id='.$idTemporal);
		$resultado="Correcto Add Id";
		return $resultado;
	}
	public function TodosTemporal(){
			$db = $this->db;
			$smt = $db->query ('SELECT * from pedcliltemporales');
			$pedidosPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($pedidosPrincipal,$result);
		}
		return $pedidosPrincipal;
		
	}
	
	public function AddPedidoGuardado($datos){
		$db = $this->db;
		$smt = $db->query ('INSERT INTO pedclit (Numtemp_pedcli, FechaPedido, idTienda, idUsuario, idCliente, estado, total, fechaCreacion) VALUES ('.$datos['NPedidoTemporal'].' , "'.$datos['fecha'].'", '.$datos['idTienda']. ', '.$datos['idUsuario'].', '.$datos['idCliente'].' , "'.$datos['estado'].'", '.$datos['total'].', "'.$datos['fechaCreacion'].'")');
		$id=$db->insert_id;
		$smt = $db->query('UPDATE pedclit SET Numpedcli  = '.$id.' WHERE id ='.$id);
		$productos = json_decode($datos['productos'], true); 
		foreach ( $productos as $prod){
			if ($prod['codBarras']){
				$codBarras=$prod['codBarras'];
			}else{
				$codBarras=0;
			}
			$smt=$db->query('INSERT INTO pedclilinea (idpedcli , Numpedcli, idArticulo, cref, ccodbar, cdetalle, ncant, nunidades, precioCiva, iva, nfila, estadoLinea ) VALUES ('.$id.', '.$id.' , '.$prod['idArticulo'].', '.$prod['crefTienda'].', '.$codBarras.', "'.$prod['articulo_name'].'", '.$prod['cant'].' , '.$prod['cant'].', '.$prod['pvpCiva'].' , '.$prod['iva'].', '.$prod['nfila'].', "'. $prod['estado'].'" )' );
		}
		foreach ($datos['DatosTotales']['desglose'] as  $iva => $basesYivas){
			$smt=$db->query('INSERT INTO pedcliIva (idpedcli ,  Numpedcli , iva , importeIva, totalbase) VALUES ('.$id.', '.$id.' , '.$iva.', '.$basesYivas['iva'].' , '.$basesYivas['base'].')');
		}
		
		return $resultado;
	}
	public function TodosPedidos(){
		$db=$this->db;
		$smt=$db->query('SELECT a.id , a.Numpedcli, a.FechaPedido, b.Nombre, a.total, a.estado FROM `pedclit` as a LEFT JOIN clientes as b on a.idCliente=b.idClientes ');
		$pedidosPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($pedidosPrincipal,$result);
		}
		return $pedidosPrincipal;
	}
	
	public function EliminarRegistroTemporal($idTemporal){
		$db=$this->db;
		$smt=$db->query('DELETE FROM pedcliltemporales WHERE id='.$idTemporal);
	}
	public function datosPedidos($idPedido){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM pedclit WHERE id= '.$idPedido );
		if ($result = $smt->fetch_assoc () ){
			$pedido=$result;
		}
		return $pedido;
	}
	public function ProductosPedidos($idPedido){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM pedclilinea WHERE idpedcli= '.$idPedido );
		$pedidosPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($pedidosPrincipal,$result);
		}
		return $pedidosPrincipal;
	}
	public function IvasPedidos($idPedido){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM pedcliIva WHERE idpedcli= '.$idPedido );
		$pedidosPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($pedidosPrincipal,$result);
		}
		return $pedidosPrincipal;
	}
	
}

?>
