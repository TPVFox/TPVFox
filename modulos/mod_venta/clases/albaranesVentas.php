<?php 
class AlbaranesVentas{
	private $idalbcli;
	private $numalbcli;
	private $numtemp_albcli;
	private $fecha;
	private $idTienda;
	private $idUsuario;
	private $idCliente;
	private $estado;
	private $formaPago;
	private $entregado;
	private $total;
	private $idalbcclitemporal;
	private $fechaInicioTemporal;
	private $fechaFinalTemporal;
	private $totalTemporal;
	private $total_ivasTemporal;
	private $productos;
	private $idabcclilinea;
	private $idArticulo;
	private $cref;
	private $ccodbar;
	private $cdetalle;
	private $ncant;
	private $nunidades;
	private $precioCiva;
	private $iva;
	private $nfila;
	private $estadoLinea;
	private $idalbIva;
	private $ivaalbIva;
	private $importeIva;
	private $totalbase;
	
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM albclit';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		return $smt;
	}
	//Insertar un nuevo registro de albaranes temporales
	public function insertarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoAlbaran, $fecha , $pedidos, $productos, $idCliente){
		$db = $this->db;
		$UnicoCampoPedidos=json_encode($pedidos);
		$UnicoCampoProductos=json_encode($productos);
		$smt = $db->query ('INSERT INTO albcliltemporales ( idUsuario , idTienda , estadoAlbCli , fechaInicio, idClientes, Pedidos, Productos ) VALUES ('.$idUsuario.' , '.$idTienda.' , "'.$estadoAlbaran.'" , "'.$fecha.'", '.$idCliente.' , '."'".$UnicoCampoPedidos."'".', '."'".$UnicoCampoProductos."'".')');
		$sql='INSERT INTO albcliltemporales ( idUsuario , idTienda , estadoAlbCli , fechaInicio, idClientes, Productos, Pedidos) VALUES ('.$idUsuario.' , '.$idTienda.' , "'.$estadoAlbaran.'" , "'.$fecha.'", '.$idCliente.' , '."'".$UnicoCampoProductos."'".', '."'".$UnicoCampoPedidos."'".')';

		$id=$db->insert_id;
		$respuesta['id']=$id;
		$respuesta['sql']=$sql;
		$respuesta['productos']=$productos;
		
		return $respuesta;
	}
	//Modificar un registro de albaranes temporales
	public function modificarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoAlbaran, $fecha , $pedidos, $idTemporal, $productos){
		$db = $this->db;
		$UnicoCampoPedidos=json_encode($pedidos);
		$UnicoCampoProductos=json_encode($productos);
		$smt=$db->query('UPDATE albcliltemporales SET idUsuario='.$idUsuario.' , idTienda='.$idTienda.' , estadoAlbCli="'.$estadoAlbaran.'" , fechaInicio='.$fecha.' , Pedidos='."'".$UnicoCampoPedidos."'". ' ,Productos='."'".$UnicoCampoProductos."'".'  WHERE id='.$idTemporal);
		$sql='UPDATE albcliltemporales SET idUsuario='.$idUsuario.' , idTienda='.$idTienda.' , estadoAlbCli='.$estadoAlbaran.' , fechaInicio='.$fecha.' , Pedidos='."'".$UnicoCampoPedidos."'". ' ,Productos='."'".$UnicoCampoProductos."'".'  WHERE id='.$idTemporal;
		$respuesta['sql']=$sql;
		$respuesta['idTemporal']=$idTemporal;
		$respuesta['productos']=$UnicoCampoProductos;
	
		return $respuesta;
	}
	//SI tenemos un número de albarán real lo metemos en el albarán temporal
	public function addNumRealTemporal($idTemporal,  $numAlbaran){
		$db = $this->db;
		$UnicoCampoPedidos=json_encode($pedidos);
		$smt=$db->query('UPDATE albcliltemporales SET numalbcli ='.$numAlbaran.' WHERE id='.$idTemporal);
		$sql='UPDATE albcliltemporales SET numalbcli ='.$numAlbaran.' WHERE id='.$idTemporal;
		return $sql;
	}
	//Buscar todos los datos de un albarán temporal
	public function buscarDatosAlabaranTemporal($idAlbaranTemporal) {
		$db=$this->db;
		$smt=$db->query('SELECT * FROM albcliltemporales WHERE id='.$idAlbaranTemporal);
		if ($result = $smt->fetch_assoc () ){
			$albaran=$result;
		}
		return $albaran;
	}
	//Buscar todos los datos de un albarán temporal por numero real de albarán cliente
	public function buscarTemporalNumReal($idAlbaran){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM albcliltemporales WHERE numalbcli ='.$idAlbaran);
		if ($result = $smt->fetch_assoc () ){
			$albaran=$result;
		}
		return $albaran;
	}
	//Modificar el total de un albarán temporal
	public function modTotales($res, $total, $totalivas){
		$db=$this->db;
		$smt=$db->query('UPDATE albcliltemporales set total='.$total .' , total_ivas='.$totalivas .' where id='.$res);
		$sql='UPDATE albcliltemporales set total='.$total .' , total_ivas='.$totalivas .' where id='.$res;
		$resultado['sql']=$sql;
		return $resultado;
	}
	//Eliminar todas los registros de un id de albarán real 
	public function eliminarAlbaranTablas($idAlbaran){
		$db=$this->db;
		$smt=$db->query('DELETE FROM albclit where id='.$idAlbaran );
		$smt=$db->query('DELETE FROM albclilinea where idalbcli ='.$idAlbaran );
		$smt=$db->query('DELETE FROM albcliIva where idalbcli ='.$idAlbaran );
		$smt=$db->query('DELETE FROM pedcliAlb where idAlbaran ='.$idAlbaran );
		
	}
	//Añadir nuevos registros de un albaran real 
		public function AddAlbaranGuardado($datos, $idAlbaran){
		$db = $this->db;
		if ($idAlbaran>0){
		$smt = $db->query ('INSERT INTO albclit (Numalbcli, Fecha, idTienda , idUsuario , idCliente , estado , total) VALUES ('.$idAlbaran.', "'.$datos['Fecha'].'", '.$datos['idTienda'].', '.$datos['idUsuario'].', '.$datos['idCliente'].', "'.$datos['estado'].'", '.$datos['total'].')');
		$id=$db->insert_id;
		$resultado='INSERT INTO albclit (Numalbcli, Fecha, idTienda , idUsuario , idCliente , estado , total) VALUES ('.$idAlbaran.', "'.$datos['Fecha'].'", '.$datos['idTienda'].', '.$datos['idUsuario'].', '.$datos['idCliente'].', "'.$datos['estado'].'", '.$datos['total'].')';
		}else{
		$smt = $db->query ('INSERT INTO albclit (Numtemp_albcli, Fecha, idTienda , idUsuario , idCliente , estado , total) VALUES ('.$datos['Numtemp_albcli'].' , "'.$datos['Fecha'].'", '.$datos['idTienda']. ', '.$datos['idUsuario'].', '.$datos['idCliente'].' , "'.$datos['estado'].'", '.$datos['total'].')');
		$id=$db->insert_id;
		$smt = $db->query('UPDATE albclit SET Numalbcli  = '.$id.' WHERE id ='.$id);
		$resultado='INSERT INTO albclit (Numtemp_albcli, Fecha, idTienda , idUsuario , idCliente , estado , total) VALUES ('.$datos['Numtemp_albcli'].' , "'.$datos['Fecha'].'", '.$datos['idTienda']. ', '.$datos['idUsuario'].', '.$datos['idCliente'].' , "'.$datos['estado'].'", '.$datos['total'].')';
		}
		$productos = json_decode($datos['productos'], true); 
		foreach ( $productos as $prod){
			if ($prod['ccodbar']){
				$codBarras=$prod['ccodbar'];
			}else{
				$codBarras=0;
			}
			if ($prod['Numpedcli']){
				$numPed=$prod['Numpedcli'];
			}else{
				$numPed=0;
			}
			if ($idAlbaran>0){
			$smt=$db->query('INSERT INTO albclilinea (idalbcli  , Numalbcli , idArticulo , cref, ccodbar, cdetalle, ncant, nunidades, precioCiva, iva, nfila, estadoLinea, NumpedCli ) VALUES ('.$id.', '.$idAlbaran.' , '.$prod['idArticulo'].', '.$prod['cref'].', '.$codBarras.', "'.$prod['cdetalle'].'", '.$prod['ncant'].' , '.$prod['ncant'].', '.$prod['precioCiva'].' , '.$prod['iva'].', '.$prod['nfila'].', "'. $prod['estadoLinea'].'" , '.$numPed.')' );

			}else{
			$smt=$db->query('INSERT INTO albclilinea (idalbcli  , Numalbcli , idArticulo , cref, ccodbar, cdetalle, ncant, nunidades, precioCiva, iva, nfila, estadoLinea, NumpedCli ) VALUES ('.$id.', '.$id.' , '.$prod['idArticulo'].', '.$prod['cref'].', '.$codBarras.', "'.$prod['cdetalle'].'", '.$prod['ncant'].' , '.$prod['ncant'].', '.$prod['precioCiva'].' , '.$prod['iva'].', '.$prod['nfila'].', "'. $prod['estadoLinea'].'" , '.$numPed.')' );
			}
		}
		foreach ($datos['DatosTotales']['desglose'] as  $iva => $basesYivas){
			if($idAlbaran>0){
			$smt=$db->query('INSERT INTO albcliIva (idalbcli  ,  Numalbcli  , iva , importeIva, totalbase) VALUES ('.$id.', '.$idAlbaran.' , '.$iva.', '.$basesYivas['iva'].' , '.$basesYivas['base'].')');

			}else{
			$smt=$db->query('INSERT INTO albcliIva (idalbcli  ,  Numalbcli  , iva , importeIva, totalbase) VALUES ('.$id.', '.$id.' , '.$iva.', '.$basesYivas['iva'].' , '.$basesYivas['base'].')');

			}
		}
		$pedidos = json_decode($datos['pedidos'], true); 
		foreach ($pedidos as $pedido){
			if($idAlbaran>0){
				$smt=$db->query('INSERT INTO pedcliAlb (idAlbaran  ,  numAlbaran   , idPedido , numPedido) VALUES ('.$id.', '.$idAlbaran.' ,  '.$pedido['idPedCli'].' , '.$pedido['Numpedcli'].')');

				}else{
				$smt=$db->query('INSERT INTO pedcliAlb (idAlbaran  ,  numAlbaran   , idPedido , numPedido) VALUES ('.$id.', '.$id.' ,  '.$pedido['idPedCli'].' , '.$pedido['Numpedcli'].')');
				$resultado='INSERT INTO pedcliAlb (idAlbaran  ,  numAlbaran   , idPedido , numPedido) VALUES ('.$id.', '.$id.' ,  '.$pedido['idPedCli'].' , '.$pedido['Numpedcli'].')';
				}
		}
		return $resultado;
	}
	
	//Elimina el albarán temporal indicado
	public function EliminarRegistroTemporal($idTemporal, $idAlbaran){
		$db=$this->db;
		if ($idAlbaran>0){
			$smt=$db->query('DELETE FROM albcliltemporales WHERE numalbcli ='.$idAlbaran);
			$sql='DELETE FROM albcliltemporales WHERE numalbcli ='.$idAlbaran;
		}else{
			$smt=$db->query('DELETE FROM albcliltemporales WHERE id='.$idTemporal);
		}
		return $sql;
	}
	//Muestra algunos datos de todos los albaranes reales
	public function TodosAlbaranes(){
		$db=$this->db;
		$smt=$db->query('SELECT a.id , a.Numalbcli , a.Fecha , b.Nombre, a.total, a.estado FROM `albclit` as a LEFT JOIN clientes as b on a.idCliente=b.idClientes ');
		$albaranesPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($albaranesPrincipal,$result);
		}
		return $albaranesPrincipal;
	}
		//Muestra algunos datos de todos los albaranes reales con un filtro
	public function TodosAlbaranesFiltro($filtro){
		$db=$this->db;
		$smt=$db->query('SELECT a.id , a.Numalbcli , a.Fecha , b.Nombre, a.total, a.estado FROM `albclit` as a LEFT JOIN clientes as b on a.idCliente=b.idClientes '.$filtro);
		$albaranesPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($albaranesPrincipal,$result);
		}
		return $albaranesPrincipal;
	}
	//Muestra los suma de los impirtes ivas y total base   de un albaran real
		public function sumarIva($numAlbaran){
		$db=$this->db;
		$smt=$db->query('select sum(importeIva ) as importeIva , sum(totalbase) as  totalbase from albcliIva where  Numalbcli  ='.$numAlbaran);
		if ($result = $smt->fetch_assoc () ){
			$albaran=$result;
		}
		return $albaran;
	}
	//MUestra todos los datos temporales
		public function TodosTemporal(){
			$db = $this->db;
			$smt = $db->query ('SELECT * from albcliltemporales');
			$albaranPrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
				array_push($albaranPrincipal,$result);
			}
			return $albaranPrincipal;
		
		}
		//Datos de un albarán real según id
	public function datosAlbaran($idAlbaran){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM albclit WHERE id= '.$idAlbaran );
		if ($result = $smt->fetch_assoc () ){
			$albaran=$result;
		}
		return $albaran;
	}
		//Datos de un albarán real según numero de cliente
	public function datosAlbaranNum($numAlbaran){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM albclit WHERE numalbcli = '.$numAlbaran );
		if ($result = $smt->fetch_assoc () ){
			$albaran=$result;
		}
		return $albaran;
	}
	//Muestros los productos de un id de cliente real 
	public function ProductosAlbaran($idAlbaran){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM albclilinea WHERE idalbcli= '.$idAlbaran );
		$albaranPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($albaranPrincipal,$result);
		}
		return $albaranPrincipal;
	}
	//BUsca en la tabla ivas cliente los datos de un albarán real
	public function IvasAlbaran($idAlbaran){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM albcliIva WHERE idalbcli= '.$idAlbaran );
		$albaranPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($albaranPrincipal,$result);
		}
		return $albaranPrincipal;
	}
	//Busca los pedidos de un albarán real
	public function PedidosAlbaranes($idAlbaran){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM pedcliAlb WHERE idAlbaran= '.$idAlbaran );
		$albaranPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($albaranPrincipal,$result);
		}
		return $albaranPrincipal;
	}
	//Modificar estado de un albarán real
	public function ModificarEstadoAlbaran($idAlbaran, $estado){
		$db=$this->db;
		$smt=$db->query('UPDATE albclit SET estado="'.$estado.'" WHERE id='.$idAlbaran);
		$sql='UPDATE albclit SET estado='.$estado.' WHERE id='.$idAlbaran;
		$resultado['sql']=$sql;
		return $resultado;
	}
	//Comprobar los albaranes de un determinado estado
		public function ComprobarAlbaranes($idCliente, $estado){
		$db=$this->db;
		$estado='"'.'Guardado'.'"';
		$smt=$db->query('SELECT  id from albclit where idCliente='.$idCliente .' and estado='.$estado);
		$sql='SELECT  id from albclit where idCliente='.$idCliente .' and estado='.$estado;
		$albaranes['sql']=$sql;
		while ( $result = $smt->fetch_assoc () ) {
			$albaranes['alb']=1;
		}
		return $albaranes;
	}
	
	
		public function AlbaranClienteGuardado($busqueda, $idCliente){
		$db=$this->db;
		$pedido['busqueda']=$busqueda;
		if ($busqueda>0){
		$smt=$db->query('select  Numalbcli , id , Fecha  , total from albclit where Numalbcli ='.$busqueda.' and  idCliente='. $idCliente);
		if ($smt){
			if ($result = $smt->fetch_assoc () ){
				$pedido=$result;
			}
			$pedido['Nitem']=1;
		}
		}else{
			$smt=$db->query('SELECT  Numalbcli , Fecha  , total , id from albclit where idCliente='.$idCliente .' and estado="Guardado"');
		
			$pedidosPrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
				array_push($pedidosPrincipal,$result);	
			}
			
			$pedido['datos']=$pedidosPrincipal;
			
		}
		return $pedido;
	}

	
	
}

?>
