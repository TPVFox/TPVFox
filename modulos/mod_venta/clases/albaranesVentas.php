<?php 
include_once ('./clases/ClaseVentas.php');
class AlbaranesVentas extends ClaseVentas{
	
	
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
		if ($smt) {
			return $smt;
		} else {
			$respuesta = array();
			$respuesta['consulta'] = $sql;
			$respuesta['error'] = $db->error;
			return $respuesta;
		}
	}
	
	public function insertarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoAlbaran, $fecha , $pedidos, $productos, $idCliente){
		//@Objetivo:
		//Insertar un nuevo registro de albaranes temporales
		$db = $this->db;
		$UnicoCampoPedidos=json_encode($pedidos);
		$UnicoCampoProductos=json_encode($productos);
		$smt = $db->query ('INSERT INTO albcliltemporales ( idUsuario , idTienda , estadoAlbCli , fechaInicio, idClientes, Pedidos, Productos ) VALUES ('.$idUsuario.' , '.$idTienda.' , "'.$estadoAlbaran.'" , "'.$fecha.'", '.$idCliente.' , '."'".$UnicoCampoPedidos."'".', '."'".$UnicoCampoProductos."'".')');
		$id=$db->insert_id;
		$respuesta['id']=$id;
		$respuesta['productos']=$productos;
		
		return $respuesta;
	}
	
	public function modificarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoAlbaran, $fecha , $pedidos, $idTemporal, $productos){
		//@Objetivo:
		//Modificar un registro de albaranes temporales
		$db = $this->db;
		$UnicoCampoPedidos=json_encode($pedidos);
		$UnicoCampoProductos=json_encode($productos);
		$smt=$db->query('UPDATE albcliltemporales SET idUsuario='.$idUsuario.' , idTienda='.$idTienda.' , estadoAlbCli="'.$estadoAlbaran.'" , fechaInicio='.$fecha.' , Pedidos='."'".$UnicoCampoPedidos."'". ' ,Productos='."'".$UnicoCampoProductos."'".'  WHERE id='.$idTemporal);
		$respuesta['idTemporal']=$idTemporal;
		$respuesta['productos']=$UnicoCampoProductos;
	
		return $respuesta;
	}
	
	public function addNumRealTemporal($idTemporal,  $numAlbaran){
		//@Objetivo:
		//SI tenemos un número de albarán real lo metemos en el albarán temporal
		$db = $this->db;
		$UnicoCampoPedidos=json_encode($pedidos);
		$smt=$db->query('UPDATE albcliltemporales SET numalbcli ='.$numAlbaran.' WHERE id='.$idTemporal);
	}
	
	public function buscarDatosAlabaranTemporal($idAlbaranTemporal) {
		//@Objetivo:
		//Buscar todos los datos de un albarán temporal
		$tabla='albcliltemporales';
		$where='id='.$idAlbaranTemporal;
		$albaran = parent::SelectUnResult($tabla, $where);
		return $albaran;
	}
	
	public function buscarTemporalNumReal($idAlbaran){
		//@Objetivo:
		//Buscar todos los datos de un albarán temporal por numero real de albarán cliente
		$tabla='albcliltemporales';
		$where='numalbcli='.$idAlbaran;
		$albaran = parent::SelectUnResult($tabla, $where);
		return $albaran;
	}

	public function modTotales($res, $total, $totalivas){
		//@Objetivo:
		//Modificar el total de un albarán temporal
		$db=$this->db;
		$smt=$db->query('UPDATE albcliltemporales set total='.$total .' , total_ivas='.$totalivas .' where id='.$res);
	}
	
	public function eliminarAlbaranTablas($idAlbaran){
		//@Objetivo:
		//Eliminar todas los registros de un id de albarán real 
		$db=$this->db;
		$smt=$db->query('DELETE FROM albclit where id='.$idAlbaran );
		$smt=$db->query('DELETE FROM albclilinea where idalbcli ='.$idAlbaran );
		$smt=$db->query('DELETE FROM albcliIva where idalbcli ='.$idAlbaran );
		$smt=$db->query('DELETE FROM pedcliAlb where idAlbaran ='.$idAlbaran );
		
	}
	
		public function AddAlbaranGuardado($datos, $idAlbaran, $numAlbaran){
			//@Objetivo:
			//Añadir nuevos registros de un albaran real 
		$db = $this->db;
		if ($idAlbaran>0){
		$smt = $db->query ('INSERT INTO albclit (id, Numalbcli, Fecha, idTienda , idUsuario , idCliente , estado , total) VALUES ('.$idAlbaran.' , '.$numAlbaran.', "'.$datos['Fecha'].'", '.$datos['idTienda'].', '.$datos['idUsuario'].', '.$datos['idCliente'].', "'.$datos['estado'].'", '.$datos['total'].')');
		$id=$idAlbaran;
		}else{
		$smt = $db->query ('INSERT INTO albclit (Numtemp_albcli, Fecha, idTienda , idUsuario , idCliente , estado , total) VALUES ('.$datos['Numtemp_albcli'].' , "'.$datos['Fecha'].'", '.$datos['idTienda']. ', '.$datos['idUsuario'].', '.$datos['idCliente'].' , "'.$datos['estado'].'", '.$datos['total'].')');
		$id=$db->insert_id;
		$smt = $db->query('UPDATE albclit SET Numalbcli  = '.$id.' WHERE id ='.$id);
		}
		$productos = json_decode($datos['productos'], true); 
		$i=1;
		foreach ( $productos as $prod){
			if ($prod['estadoLinea']== 'Activo'){
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
			$smt=$db->query('INSERT INTO albclilinea (idalbcli  , Numalbcli , idArticulo , cref, ccodbar, cdetalle, ncant, nunidades, precioCiva, iva, nfila, estadoLinea, NumpedCli ) VALUES ('.$id.', '.$idAlbaran.' , '.$prod['idArticulo'].', '."'".$prod['cref']."'".', '.$codBarras.', "'.$prod['cdetalle'].'", '.$prod['ncant'].' , '.$prod['nunidades'].', '.$prod['precioCiva'].' , '.$prod['iva'].', '.$i.', "'. $prod['estadoLinea'].'" , '.$numPed.')' );
			}else{
			$smt=$db->query('INSERT INTO albclilinea (idalbcli  , Numalbcli , idArticulo , cref, ccodbar, cdetalle, ncant, nunidades, precioCiva, iva, nfila, estadoLinea, NumpedCli ) VALUES ('.$id.', '.$id.' , '.$prod['idArticulo'].', '."'".$prod['cref']."'".', '.$codBarras.', "'.$prod['cdetalle'].'", '.$prod['ncant'].' , '.$prod['nunidades'].', '.$prod['precioCiva'].' , '.$prod['iva'].', '.$i.', "'. $prod['estadoLinea'].'" , '.$numPed.')' );
			}
			$i++;
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
			if ($pedido['estado']=="activo" || $pedido['estado']=="Activo"){
			if($idAlbaran>0){
				$smt=$db->query('INSERT INTO pedcliAlb (idAlbaran  ,  numAlbaran   , idPedido , numPedido) VALUES ('.$id.', '.$idAlbaran.' ,  '.$pedido['idPedCli'].' , '.$pedido['Numpedcli'].')');

				}else{
				$smt=$db->query('INSERT INTO pedcliAlb (idAlbaran  ,  numAlbaran   , idPedido , numPedido) VALUES ('.$id.', '.$id.' ,  '.$pedido['idPedCli'].' , '.$pedido['Numpedcli'].')');
				}
			}
		}
	}
	
	
	public function EliminarRegistroTemporal($idTemporal, $idAlbaran){
		//@Objetivo:
		//Eliminar el albarán temporal indicado
		$db=$this->db;
		if ($idAlbaran>0){
			$smt=$db->query('DELETE FROM albcliltemporales WHERE numalbcli ='.$idAlbaran);
		}else{
			$smt=$db->query('DELETE FROM albcliltemporales WHERE id='.$idTemporal);
		}
	}
		
	public function TodosAlbaranesFiltro($filtro){
		//@Objetivo:
		//Mostrar algunos datos de todos los albaranes reales con un filtro
		$db=$this->db;
		$smt=$db->query('SELECT a.id , a.Numalbcli , a.Fecha , b.Nombre, a.total, a.estado FROM `albclit` as a LEFT JOIN clientes as b on a.idCliente=b.idClientes '.$filtro);
		$albaranesPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($albaranesPrincipal,$result);
		}
		return $albaranesPrincipal;
	}
	
		public function sumarIva($numAlbaran){
		//@Objetivo:
		//Mostrar la suma de los impirtes ivas y total base   de un albaran real
		$db=$this->db;
		$smt=$db->query('select sum(importeIva ) as importeIva , sum(totalbase) as  totalbase from albcliIva where  Numalbcli  ='.$numAlbaran);
		if ($result = $smt->fetch_assoc () ){
			$albaran=$result;
		}
		return $albaran;
	}
	
		public function TodosTemporal(){
			//@Objetivo:
			//Mostrar todos los datos temporales
			$db = $this->db;
			$smt = $db->query ('SELECT tem.numalbcli, tem.id , tem.idClientes, tem.total, b.Nombre from albcliltemporales as tem left JOIN clientes as b on tem.idClientes=b.idClientes');
			$albaranPrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
				array_push($albaranPrincipal,$result);
			}
			return $albaranPrincipal;
		
		}
		
	public function datosAlbaran($idAlbaran){
		//@Objetivo:
		//Datos de un albarán real según id
		$tabla='albclit';
		$where='id='.$idAlbaran;
		$albaran = parent::SelectUnResult($tabla, $where);
		return $albaran;
	}
		
	public function datosAlbaranNum($numAlbaran){
		//@Objetivo:
		//Datos de un albarán real según numero de cliente
		$tabla='albclit';
		$where='numalbcli='.$numAlbaran;
		$albaran = parent::SelectUnResult($tabla, $where);
		return $albaran;
	}
	
	public function ProductosAlbaran($idAlbaran){
		//@Objetivo:
		//Muestros los productos de un id de cliente real 
		$tabla='albclilinea';
		$where='idalbcli= '.$idAlbaran;
		$albaran = parent::SelectVariosResult($tabla, $where);
		return $albaran;
	}
	
	public function IvasAlbaran($idAlbaran){
			//@Objetivo:
			//BUsca en la tabla ivas cliente los datos de un albarán real
		$tabla='albcliIva';
		$where='idalbcli= '.$idAlbaran;
		$albaran = parent::SelectVariosResult($tabla, $where);
		return $albaran;
		
	}
	
	public function PedidosAlbaranes($idAlbaran){
		//@Objetivo:
		//Busca los pedidos de un albarán real
		$tabla='pedcliAlb';
		$where='idAlbaran= '.$idAlbaran;
		$albaran = parent::SelectVariosResult($tabla, $where);
		return $albaran;
	}
	
	public function ModificarEstadoAlbaran($idAlbaran, $estado){
		//@Objetivo:
		//Modificar estado de un albarán real
		$db=$this->db;
		$smt=$db->query('UPDATE albclit SET estado="'.$estado.'" WHERE id='.$idAlbaran);
		
	}
	
		public function ComprobarAlbaranes($idCliente, $estado){
				//@Objetivo:
				//Comprobar los albaranes de un determinado estado
		$db=$this->db;
		$estado='"'.'Guardado'.'"';
		$smt=$db->query('SELECT  id from albclit where idCliente='.$idCliente .' and estado='.$estado);
		$albaranes=0;
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
