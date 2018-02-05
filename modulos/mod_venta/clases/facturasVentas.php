<?php 
class FacturasVentas{
	private $id;
	private $Numfaccli;
	private $Numtemp_faccli;
	private $fecha;
	private $idTienda;
	private $idUsuario;
	private $idCliente;
	private $estado;
	private $formaPago;
	private $entregado;
	private $total;
	private $idfacclilinea;
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
	private $idfaccliiva;
	private $importeiva;
	private $totalbase;
	private $fechaCreacion;
	private $fechaModificacion;
	private $fechaVencimiento;
	
	
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM facclit';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	
	public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		return $smt;
	}
	
	public function TodosTemporal(){
			$db = $this->db;
			$smt = $db->query ('SELECT * from faccliltemporales');
			$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
		
	}

	
	
	public function TodosFactura(){
		$db=$this->db;
		$smt=$db->query('SELECT a.id , a.Numfaccli , a.Fecha , b.Nombre, a.total, a.estado FROM `facclit` as a LEFT JOIN clientes as b on a.idCliente=b.idClientes ');
		$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
	}
	
	public function datosFactura($idFactura){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM facclit WHERE id= '.$idFactura );
		if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	public function ProductosFactura($idFactura){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM facclilinea WHERE idfaccli= '.$idFactura );
		$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
	}
	public function IvasFactura($idFactura){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM faccliIva WHERE idfaccli= '.$idFactura );
		$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
	}
	public function AlbaranesFactura($idFactura){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM albfaccli WHERE idFactura= '.$idFactura );
		$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
	}
	public function buscarDatosFacturasTemporal($idFacturaTemporal) {
		$db=$this->db;
		$smt=$db->query('SELECT * FROM faccliltemporales WHERE id='.$idFacturaTemporal);
		if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	public function EliminarRegistroTemporal($idTemporal, $idFactura){
		$db=$this->db;
		if ($idAlbaran>0){
			$smt=$db->query('DELETE FROM faccliltemporales WHERE numfaccli ='.$idFactura);
			$sql='DELETE FROM faccliltemporales WHERE numfaccli ='.$idFactura;
		}else{
			$smt=$db->query('DELETE FROM faccliltemporales WHERE id='.$idTemporal);
		}
		return $sql;
	}
	
	
	
	public function buscarTemporalNumReal($idFactura){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM faccliltemporales WHERE 	numfaccli ='.$idFactura);
		if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	
	
	
	public function modificarDatosFacturaTemporal($idUsuario, $idTienda, $estadoFactura, $fecha , $albaranes, $idTemporal, $productos){
		$db = $this->db;
		$UnicoCampoAlbaranes=json_encode($albaranes);
		$UnicoCampoProductos=json_encode($productos);
		$smt=$db->query('UPDATE faccliltemporales SET idUsuario='.$idUsuario.' , idTienda='.$idTienda.' , estadoFacCli="'.$estadoFactura.'" , fechaInicio='.$fecha.' , Albaranes ='."'".$UnicoCampoAlbaranes."'". ' ,Productos='."'".$UnicoCampoProductos."'".'  WHERE id='.$idTemporal);
		$sql='UPDATE faccliltemporales SET idUsuario='.$idUsuario.' , idTienda='.$idTienda.' , estadoFacCli='.$estadoFactura.' , fechaInicio='.$fecha.' , Albaranes ='."'".$UnicoCampoAlbaranes."'". ' ,Productos='."'".$UnicoCampoProductos."'".'  WHERE id='.$idTemporal;
		$respuesta['sql']=$sql;
		$respuesta['idTemporal']=$idTemporal;
		$respuesta['productos']=$UnicoCampoProductos;
	
		return $respuesta;
	}
	
	
	public function insertarDatosFacturaTemporal($idUsuario, $idTienda, $estadoFactura, $fecha , $albaranes, $productos, $idCliente){
		$db = $this->db;
		$UnicoCampoAlbaranes=json_encode($albaranes);
		$UnicoCampoProductos=json_encode($productos);
		$smt = $db->query ('INSERT INTO faccliltemporales ( idUsuario , idTienda , estadoFacCli , fechaInicio, idClientes, Albaranes, Productos ) VALUES ('.$idUsuario.' , '.$idTienda.' , "'.$estadoFactura.'" , "'.$fecha.'", '.$idCliente.' , '."'".$UnicoCampoAlbaranes."'".', '."'".$UnicoCampoProductos."'".')');
		$sql='INSERT INTO faccliltemporales ( idUsuario , idTienda , estadoFacCli , fechaInicio, idClientes, Productos, Albaranes) VALUES ('.$idUsuario.' , '.$idTienda.' , "'.$estadoFactura.'" , "'.$fecha.'", '.$idCliente.' , '."'".$UnicoCampoProductos."'".', '."'".$UnicoCampoAlbaranes."'".')';

		$id=$db->insert_id;
		$respuesta['id']=$id;
		$respuesta['sql']=$sql;
		$respuesta['productos']=$productos;
		
		return $respuesta;
	}
	
	
		public function addNumRealTemporal($idTemporal,  $numFactura){
		$db = $this->db;
		//$UnicoCampoPedidos=json_encode($albaranes);
		$smt=$db->query('UPDATE faccliltemporales SET numfaccli ='.$numFactura.' WHERE id='.$idTemporal);
		$sql='UPDATE faccliltemporales SET numfaccli ='.$numFactura.' WHERE id='.$idTemporal;
		return $sql;
	}
	
	public function modTotales($res, $total, $totalivas){
		$db=$this->db;
		$smt=$db->query('UPDATE faccliltemporales set total='.$total .' , total_ivas='.$totalivas .' where id='.$res);
		$sql='UPDATE faccliltemporales set total='.$total .' , total_ivas='.$totalivas .' where id='.$res;
		$resultado['sql']=$sql;
		return $resultado;
	}
	
	public function modificarEstado($idFactura, $estado){
		$db=$this->db;
		$smt=$db->query('UPDATE facclit set estado="'.$estado .'" where id='.$idFactura);
		$sql='UPDATE facclit set estado='.$estado .' where id='.$idFactura;
		return $sql;
	}
	
	public function eliminarFacturasTablas($idFactura){
		$db=$this->db;
		$smt=$db->query('DELETE FROM  facclit where id='.$idFactura );
		$smt=$db->query('DELETE FROM  facclilinea where idfaccli ='.$idFactura );
		$smt=$db->query('DELETE FROM faccliIva where idfaccli ='.$idFactura );
		$smt=$db->query('DELETE FROM albfaccli where idFactura  ='.$idFactura );
		
	}
	
	
		public function AddFacturaGuardado($datos, $idFactura){
		$db = $this->db;
		if ($idFactura>0){
			$smt = $db->query ('INSERT INTO facclit (Numfaccli, Fecha, idTienda , idUsuario , idCliente , estado , total, fechaCreacion, formaPago, fechaVencimiento, importes, entregado, fechaModificacion) VALUES ('.$idFactura.', "'.$datos['Fecha'].'", '.$datos['idTienda'].', '.$datos['idUsuario'].', '.$datos['idCliente'].', "'.$datos['estado'].'", '.$datos['total'].', "'.$datos['fechaCreacion'].'", '.$datos['formapago'].', "'.$datos['fechaVencimiento'].'", '."'".$datos['importes']."'".', '.$datos['entregado'].', "'.$datos['fechaModificacion'].'")');
			$id=$db->insert_id;
			$resultado['insert']='INSERT INTO facclit (Numfaccli, Fecha, idTienda , idUsuario , idCliente , estado , total, fechaCreacion, formaPago, fechaVencimiento, importes, entregado, fechaModificacion) VALUES ('.$idFactura.', "'.$datos['Fecha'].'", '.$datos['idTienda'].', '.$datos['idUsuario'].', '.$datos['idCliente'].', "'.$datos['estado'].'", '.$datos['total'].', "'.$datos['fechaCreacion'].'", '.$datos['formapago'].', "'.$datos['fechaVencimiento'].'", '."'".$datos['importes']."'".', '.$datos['entregado'].', "'.$datos['fechaModificacion'].'")';
		}else{
			$smt = $db->query ('INSERT INTO facclit (Numtemp_faccli , Fecha, idTienda , idUsuario , idCliente , estado , total, fechaCreacion, formaPago, fechaVencimiento, importes, entregado, fechaModificacion) VALUES ('.$datos['Numtemp_faccli'].' , "'.$datos['Fecha'].'", '.$datos['idTienda']. ', '.$datos['idUsuario'].', '.$datos['idCliente'].' , "'.$datos['estado'].'", '.$datos['total'].', "'.$datos['fechaCreacion'].'", '.$datos['formapago'].', "'.$datos['fechaVencimiento'].'", '."'".$datos['importes']."'".' , '.$datos['entregado'].' , "'.$datos['fechaModificacion'].'")');
			$id=$db->insert_id;
			$smt = $db->query('UPDATE facclit SET Numfaccli  = '.$id.' WHERE id ='.$id);
			$resultado['insert']='INSERT INTO facclit (Numtemp_faccli , Fecha, idTienda , idUsuario , idCliente , estado , total, fechaCreacion, formaPago, fechaVencimiento, importes, entregado, fechaModificacion) VALUES ('.$datos['Numtemp_faccli'].' , "'.$datos['Fecha'].'", '.$datos['idTienda']. ', '.$datos['idUsuario'].', '.$datos['idCliente'].' , "'.$datos['estado'].'", '.$datos['total'].', "'.$datos['fechaCreacion'].'", '.$datos['formapago'].', "'.$datos['fechaVencimiento'].'", '."'".$datos['importes']."'".' , '.$datos['entregado'].' , "'.$datos['fechaModificacion'].'")';
		}
		$productos = json_decode($datos['productos'], true); 
		foreach ( $productos as $prod){
			if ($prod['ccodbar']){
				$codBarras=$prod['ccodbar'];
			}else{
				$codBarras=0;
			}
			if ($prod['Numalbcli']){
				$numAl=$prod['Numalbcli'];
			}else{
				$numAl=0;
			}
			if ($idFactura>0){
			$smt=$db->query('INSERT INTO facclilinea (idfaccli  , Numfaccli , idArticulo , cref, ccodbar, cdetalle, ncant, nunidades, precioCiva, iva, nfila, estadoLinea, NumalbCli ) VALUES ('.$id.', '.$idFactura.' , '.$prod['idArticulo'].', '.$prod['cref'].', '.$codBarras.', "'.$prod['cdetalle'].'", '.$prod['ncant'].' , '.$prod['ncant'].', '.$prod['precioCiva'].' , '.$prod['iva'].', '.$prod['nfila'].', "'. $prod['estadoLinea'].'" , '.$numAl.')' );

			}else{
			$smt=$db->query('INSERT INTO facclilinea (idfaccli  , Numfaccli , idArticulo , cref, ccodbar, cdetalle, ncant, nunidades, precioCiva, iva, nfila, estadoLinea, NumalbCli ) VALUES ('.$id.', '.$id.' , '.$prod['idArticulo'].', '.$prod['cref'].', '.$codBarras.', "'.$prod['cdetalle'].'", '.$prod['ncant'].' , '.$prod['ncant'].', '.$prod['precioCiva'].' , '.$prod['iva'].', '.$prod['nfila'].', "'. $prod['estadoLinea'].'" , '.$numAl.')' );
			}
		}
		foreach ($datos['DatosTotales']['desglose'] as  $iva => $basesYivas){
			if($idFactura>0){
			$smt=$db->query('INSERT INTO faccliIva (idfaccli  ,  Numfaccli  , iva , importeIva, totalbase) VALUES ('.$id.', '.$idFactura.' , '.$iva.', '.$basesYivas['iva'].' , '.$basesYivas['base'].')');

			}else{
			$smt=$db->query('INSERT INTO faccliIva (idfaccli  ,  Numfaccli  , iva , importeIva, totalbase) VALUES ('.$id.', '.$id.' , '.$iva.', '.$basesYivas['iva'].' , '.$basesYivas['base'].')');

			}
		}
		$albaranes = json_decode($datos['albaranes'], true); 
		if ($albaranes){
		foreach ($albaranes as $albaran){
			if($idFactura>0){
				$smt=$db->query('INSERT INTO albfaccli (idFactura  ,  numFactura   , idAlbaran , numAlbaran) VALUES ('.$id.', '.$idFactura.' ,  '.$pedido['idalbcli'].' , '.$pedido['Numalbcli'].')');

				}else{
				$smt=$db->query('INSERT INTO albfaccli (idFactura  ,  numFactura   , idAlbaran , numAlbaran) VALUES ('.$id.', '.$id.' ,  '.$pedido['idalbcli'].' , '.$pedido['Numalbcli'].')');
				$resultado='INSERT INTO albfaccli (idFactura  ,  numFactura   , idAlbaran , numAlbaran) VALUES ('.$id.', '.$id.' ,  '.$pedido['idalbcli'].' , '.$pedido['Numalbcli'].')';
				}
		}
		}
		return $resultado;
	}
	
	public function sumarIva($numFactura){
		$db=$this->db;
		$smt=$db->query('select sum(importeIva ) as importeIva , sum(totalbase) as  totalbase from faccliIva where  Numfaccli  ='.$numFactura);
		if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	
	public function formasVencimientoTemporal($idTemporal, $json){
		$db=$this->db;
		$smt=$db->query('UPDATE faccliltemporales set FacCobros='."'".$json."'".' where id='.$idTemporal);
		$sql='UPDATE faccliltemporales set FacCobros='."'".$json."'".' where id='.$idTemporal;
		return $sql;
	}
	
	public function importesFacturaDatos($idFactura){
		$db=$this->db;
		$smt=$db->query ('SELECT total , entregado, importes FROM facclit where id='.$idFactura );
			if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	
	public function modificarImportesFactura($idFactura, $jsonImporte, $entregado, $estado){
		$db=$this->db;
		$smt=$db->query('UPDATE facclit SET importes='."'".$jsonImporte."'".' , entregado='.$entregado.' , estado="'.$estado.'" where id='.$idFactura);
		$sql='UPDATE facclit SET importes='."'".$jsonImporte."'".' , entregado='.$entregado.' , estado="'.$estado.'" where id='.$idFactura;
		return $sql;
	}
	
	
	

}


?>
