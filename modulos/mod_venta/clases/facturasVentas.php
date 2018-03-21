<?php 
include_once ('./clases/ClaseVentas.php');
class FacturasVentas extends ClaseVentas{
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
	//Muestra los datos de todos los temporales
	public function TodosTemporal(){
			$db = $this->db;
			$smt = $db->query ('SELECT tem.numfaccli, tem.id , tem.idClientes, tem.total, b.Nombre from faccliltemporales as tem left JOIN clientes as b on tem.idClientes=b.idClientes');
			$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
		
	}
		//Muestra algunos datos de todos las facturas con un filtro
	public function TodosFacturaFiltro($filtro){
		$db=$this->db;
		$smt=$db->query('SELECT a.id , a.Numfaccli , a.Fecha , b.Nombre, a.total, a.estado FROM `facclit` as a LEFT JOIN clientes as b on a.idCliente=b.idClientes '.$filtro);
		$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
	}
	//Muestra los datos de una factura real
	public function datosFactura($idFactura){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM facclit WHERE id= '.$idFactura );
		if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	
	public function buscarIdFactura($numFactura){
		$db=$this->db;
		$smt=$db->query('SELECT id FROM facclit WHERE Numfaccli= '.$numFactura );
		if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	//Busca los productos de un número de factura
	public function ProductosFactura($idFactura){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM facclilinea WHERE idfaccli= '.$idFactura );
		$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
	}
	//Busca los ivas de una factura real
	public function IvasFactura($idFactura){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM faccliIva WHERE idfaccli= '.$idFactura );
		$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
	}
	//MUestra los albaranes que estan ligados a una determinada factura
	public function AlbaranesFactura($idFactura){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM albclifac WHERE idFactura= '.$idFactura );
		$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
	}
	//Busca los datos de una factura temporal
	public function buscarDatosFacturasTemporal($idFacturaTemporal) {
		$db=$this->db;
		$smt=$db->query('SELECT * FROM faccliltemporales WHERE id='.$idFacturaTemporal);
		if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	//Elimina el resgistro de un temporal indicado
	public function EliminarRegistroTemporal($idTemporal, $idFactura){
		$db=$this->db;
		if ($idFactura>0){
			$smt=$db->query('DELETE FROM faccliltemporales WHERE numfaccli ='.$idFactura);
		}else{
			$smt=$db->query('DELETE FROM faccliltemporales WHERE id='.$idTemporal);
		}
		
	}
	
	//Busca un temporal por número de factura real
	
	public function buscarTemporalNumReal($idFactura){
		$db=$this->db;
		$smt=$db->query('SELECT * FROM faccliltemporales WHERE 	numfaccli ='.$idFactura);
		if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	
	
	//Modificar los datos de una factura temporal
	public function modificarDatosFacturaTemporal($idUsuario, $idTienda, $estadoFactura, $fecha , $albaranes, $idTemporal, $productos){
		$db = $this->db;
		$UnicoCampoAlbaranes=json_encode($albaranes);
		$UnicoCampoProductos=json_encode($productos);
		$smt=$db->query('UPDATE faccliltemporales SET idUsuario='.$idUsuario.' , idTienda='.$idTienda.' , estadoFacCli="'.$estadoFactura.'" , fechaInicio='.$fecha.' , Albaranes ='."'".$UnicoCampoAlbaranes."'". ' ,Productos='."'".$UnicoCampoProductos."'".'  WHERE id='.$idTemporal);
		$respuesta['idTemporal']=$idTemporal;
		$respuesta['productos']=$UnicoCampoProductos;
	
		return $respuesta;
	}
	//Insertar nuevo registro de factura 
	
	public function insertarDatosFacturaTemporal($idUsuario, $idTienda, $estadoFactura, $fecha , $albaranes, $productos, $idCliente){
		$db = $this->db;
		$UnicoCampoAlbaranes=json_encode($albaranes);
		$UnicoCampoProductos=json_encode($productos);
		$smt = $db->query ('INSERT INTO faccliltemporales ( idUsuario , idTienda , estadoFacCli , fechaInicio, idClientes, Albaranes, Productos ) VALUES ('.$idUsuario.' , '.$idTienda.' , "'.$estadoFactura.'" , "'.$fecha.'", '.$idCliente.' , '."'".$UnicoCampoAlbaranes."'".', '."'".$UnicoCampoProductos."'".')');
		$id=$db->insert_id;
		$respuesta['id']=$id;
		$respuesta['productos']=$productos;
		return $respuesta;
	}
	
	//Añade a una factura temporal el número real de la factura en el caso de que exista 
		public function addNumRealTemporal($idTemporal,  $numFactura){
		$db = $this->db;
		$smt=$db->query('UPDATE faccliltemporales SET numfaccli ='.$numFactura.' WHERE id='.$idTemporal);
	}
	//Modifica el total de una factura temporal
	public function modTotales($res, $total, $totalivas){
		$db=$this->db;
		$smt=$db->query('UPDATE faccliltemporales set total='.$total .' , total_ivas='.$totalivas .' where id='.$res);
	}
	//Modificar el estado de una factura real
	public function modificarEstado($idFactura, $estado){
		$db=$this->db;
		$smt=$db->query('UPDATE facclit set estado="'.$estado .'" where id='.$idFactura);
	}
	//Eliminar todos los registros de un id de factura real
	public function eliminarFacturasTablas($idFactura){
		$db=$this->db;
		$smt=$db->query('DELETE FROM  facclit where id='.$idFactura );
		$smt=$db->query('DELETE FROM  facclilinea where idfaccli ='.$idFactura );
		$smt=$db->query('DELETE FROM faccliIva where idfaccli ='.$idFactura );
		$smt=$db->query('DELETE FROM albclifac where idFactura  ='.$idFactura );
		
	}
	
	//Añadir todos los registros de las diferentes tablas de una factura real
		public function AddFacturaGuardado($datos, $idFactura, $numFactura){
		$db = $this->db;
		if ($idFactura>0){
			$smt = $db->query ('INSERT INTO facclit (id, Numfaccli, Fecha, idTienda , idUsuario , idCliente , estado , total, fechaCreacion, formaPago, fechaVencimiento, importes, entregado, fechaModificacion) VALUES ('.$idFactura.' , '.$numFactura.' , "'.$datos['Fecha'].'", '.$datos['idTienda'].', '.$datos['idUsuario'].', '.$datos['idCliente'].', "'.$datos['estado'].'", '.$datos['total'].', "'.$datos['fechaCreacion'].'", '.$datos['formapago'].', "'.$datos['fechaVencimiento'].'", '."'".$datos['importes']."'".', '.$datos['entregado'].', "'.$datos['fechaModificacion'].'")');
			$id=$idFactura;
		}else{
			$smt = $db->query ('INSERT INTO facclit (Numtemp_faccli , Fecha, idTienda , idUsuario , idCliente , estado , total, fechaCreacion, formaPago, fechaVencimiento, importes, entregado, fechaModificacion) VALUES ('.$datos['Numtemp_faccli'].' , "'.$datos['Fecha'].'", '.$datos['idTienda']. ', '.$datos['idUsuario'].', '.$datos['idCliente'].' , "'.$datos['estado'].'", '.$datos['total'].', "'.$datos['fechaCreacion'].'", '.$datos['formapago'].', "'.$datos['fechaVencimiento'].'", '."'".$datos['importes']."'".' , '.$datos['entregado'].' , "'.$datos['fechaModificacion'].'")');
			$id=$db->insert_id;
			$smt = $db->query('UPDATE facclit SET Numfaccli  = '.$id.' WHERE id ='.$id);
		}
		$productos = json_decode($datos['productos'], true); 
		foreach ( $productos as $prod){
			if ($prod['estadoLinea']=="Activo"){
				if ($prod['ccodbar']){
					$codBarras=$prod['ccodbar'];
				}else{
					$codBarras=0;
				}
				if ($prod['Numalbcli']){
					$numAl=$prod['Numalbcli'];
				}else{
					if ($prod['NumalbCli']){
						$numAl=$prod['NumalbCli'];
					}else{
						$numAl=0;
					}
				
				}
				if ($idFactura>0){
				$smt=$db->query('INSERT INTO facclilinea (idfaccli  , Numfaccli , idArticulo , cref, ccodbar, cdetalle, ncant, nunidades, precioCiva, iva, nfila, estadoLinea, NumalbCli ) VALUES ('.$id.', '.$idFactura.' , '.$prod['idArticulo'].', '."'".$prod['cref']."'".', '.$codBarras.', "'.$prod['cdetalle'].'", '.$prod['ncant'].' , '.$prod['nunidades'].', '.$prod['precioCiva'].' , '.$prod['iva'].', '.$prod['nfila'].', "'. $prod['estadoLinea'].'" , '.$numAl.')' );
				}else{
				$smt=$db->query('INSERT INTO facclilinea (idfaccli  , Numfaccli , idArticulo , cref, ccodbar, cdetalle, ncant, nunidades, precioCiva, iva, nfila, estadoLinea, NumalbCli ) VALUES ('.$id.', '.$id.' , '.$prod['idArticulo'].', '."'".$prod['cref']."'".', '.$codBarras.', "'.$prod['cdetalle'].'", '.$prod['ncant'].' , '.$prod['nunidades'].', '.$prod['precioCiva'].' , '.$prod['iva'].', '.$prod['nfila'].', "'. $prod['estadoLinea'].'" , '.$numAl.')' );
				}
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
			if ($albaran['estado']=="activo" || $albaran['estado']=="Activo"){
			if($idFactura>0){
				$smt=$db->query('INSERT INTO albclifac (idFactura  ,  numFactura   , idAlbaran , numAlbaran) VALUES ('.$id.', '.$idFactura.' ,  '.$albaran['idalbcli'].' , '.$albaran['Numalbcli'].')');
				}else{
				$smt=$db->query('INSERT INTO albclifac (idFactura  ,  numFactura   , idAlbaran , numAlbaran) VALUES ('.$id.', '.$id.' ,  '.$albaran['idalbcli'].' , '.$albaran['Numalbcli'].')');
				}
			}
		}
		
		}
		return $resultado;
	}
	//Selecciona el importe iva y total base de una factura real
	public function sumarIva($numFactura){
		$db=$this->db;
		$smt=$db->query('select sum(importeIva ) as importeIva , sum(totalbase) as  totalbase from faccliIva where  Numfaccli  ='.$numFactura);
		if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	//Modifica la forma de vencimiento de una factura temporal
	public function formasVencimientoTemporal($idTemporal, $json){
		$db=$this->db;
		$smt=$db->query('UPDATE faccliltemporales set FacCobros='."'".$json."'".' where id='.$idTemporal);
	}
	//BUscamos los importes añadidos a una factura 
	public function importesFacturaDatos($idFactura){
		$db=$this->db;
		$smt=$db->query ('SELECT total , entregado, importes FROM facclit where id='.$idFactura );
			if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	//Modifica los importes de una factura
	public function modificarImportesFactura($idFactura, $jsonImporte, $entregado, $estado){
		$db=$this->db;
		$smt=$db->query('UPDATE facclit SET importes='."'".$jsonImporte."'".' , entregado='.$entregado.' , estado="'.$estado.'" where id='.$idFactura);
	}
	
	
	

}


?>
