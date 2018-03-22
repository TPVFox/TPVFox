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
	public function TodosTemporal(){
		//@Objetivo:
		//Mostrar los datos principales de una factura temnporal
			$db = $this->db;
			$smt = $db->query ('SELECT tem.numfaccli, tem.id , tem.idClientes, tem.total, b.Nombre from faccliltemporales as tem left JOIN clientes as b on tem.idClientes=b.idClientes');
			$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
		
	}
	public function TodosFacturaFiltro($filtro){
		//@Objetivo:
		//Mostrar los datos principales de todas las facturas con el filtro de paginacion 
		$db=$this->db;
		$smt=$db->query('SELECT a.id , a.Numfaccli , a.Fecha , b.Nombre, a.total, a.estado FROM `facclit` as a LEFT JOIN clientes as b on a.idCliente=b.idClientes '.$filtro);
		$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
	}
	public function datosFactura($idFactura){
		//@Objetivo:
		//Mostrar los datos de una factura real según el id
		$tabla='facclit';
		$where='id='.$idFactura;
		$factura = parent::SelectUnResult($tabla, $where);
		return $factura;
	}
	public function buscarIdFactura($numFactura){
		//@Objetivo:
		//Buscar el id de una factura real 
		$db=$this->db;
		$smt=$db->query('SELECT id FROM facclit WHERE Numfaccli= '.$numFactura );
		if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	public function ProductosFactura($idFactura){
		//@Objetivo:
		//Buscar los productos de un número de factura
		$tabla='facclilinea';
		$where='idfaccli= '.$idFactura;
		$factura = parent::SelectVariosResult($tabla, $where);
		return $factura;
	}
	public function IvasFactura($idFactura){
		//@Objetivo:
		//Buscar los ivas de una factura real
		$tabla='faccliIva';
		$where='idfaccli= '.$idFactura;
		$factura = parent::SelectVariosResult($tabla, $where);
		return $factura;
	}
	public function AlbaranesFactura($idFactura){
		//@Objetivo:
		//Mostrar los albaranes que estan ligados a una determinada factura
		$tabla='albclifac';
		$where='idFactura= '.$idFactura;
		$factura = parent::SelectVariosResult($tabla, $where);
		return $factura;
	}
	public function buscarDatosFacturasTemporal($idFacturaTemporal) {
		//@Objetivo:
		//Buscar los datos de una factura temporal
		$tabla='faccliltemporales';
		$where='id='.$idFacturaTemporal;
		$factura = parent::SelectUnResult($tabla, $where);
		return $factura;
	}
	public function EliminarRegistroTemporal($idTemporal, $idFactura){
		//@Objetivo:
		//Eliminar el resgistro de un temporal indicado
		$db=$this->db;
		if ($idFactura>0){
			$smt=$db->query('DELETE FROM faccliltemporales WHERE numfaccli ='.$idFactura);
		}else{
			$smt=$db->query('DELETE FROM faccliltemporales WHERE id='.$idTemporal);
		}
		
	}
	public function buscarTemporalNumReal($idFactura){
		//@Objetivo:
		//Buscar un temporal por número de factura real
		$tabla='faccliltemporales';
		$where='numfaccli='.$idFactura;
		$factura = parent::SelectUnResult($tabla, $where);
		return $factura;
	}
	
	public function modificarDatosFacturaTemporal($idUsuario, $idTienda, $estadoFactura, $fecha , $albaranes, $idTemporal, $productos){
		//@Objetivo:
		//Modificar los datos de una factura temporal
		$db = $this->db;
		$UnicoCampoAlbaranes=json_encode($albaranes);
		$UnicoCampoProductos=json_encode($productos);
		$smt=$db->query('UPDATE faccliltemporales SET idUsuario='.$idUsuario.' , idTienda='.$idTienda.' , estadoFacCli="'.$estadoFactura.'" , fechaInicio='.$fecha.' , Albaranes ='."'".$UnicoCampoAlbaranes."'". ' ,Productos='."'".$UnicoCampoProductos."'".'  WHERE id='.$idTemporal);
		$respuesta['idTemporal']=$idTemporal;
		$respuesta['productos']=$UnicoCampoProductos;
	
		return $respuesta;
	}
	public function insertarDatosFacturaTemporal($idUsuario, $idTienda, $estadoFactura, $fecha , $albaranes, $productos, $idCliente){
		//@Objetivo:
		//Insertar nuevo registro de factura 
		$db = $this->db;
		$UnicoCampoAlbaranes=json_encode($albaranes);
		$UnicoCampoProductos=json_encode($productos);
		$smt = $db->query ('INSERT INTO faccliltemporales ( idUsuario , idTienda , estadoFacCli , fechaInicio, idClientes, Albaranes, Productos ) VALUES ('.$idUsuario.' , '.$idTienda.' , "'.$estadoFactura.'" , "'.$fecha.'", '.$idCliente.' , '."'".$UnicoCampoAlbaranes."'".', '."'".$UnicoCampoProductos."'".')');
		$id=$db->insert_id;
		$respuesta['id']=$id;
		$respuesta['productos']=$productos;
		return $respuesta;
	}
	public function addNumRealTemporal($idTemporal,  $numFactura){
		//@Objetivo:
		//Añadir a una factura temporal el número real de la factura en el caso de que exista 
		$db = $this->db;
		$smt=$db->query('UPDATE faccliltemporales SET numfaccli ='.$numFactura.' WHERE id='.$idTemporal);
	}
	public function modTotales($res, $total, $totalivas){
		//@Objetivo:
		//Modificar el total de una factura temporal
		$db=$this->db;
		$smt=$db->query('UPDATE faccliltemporales set total='.$total .' , total_ivas='.$totalivas .' where id='.$res);
	}
	public function modificarEstado($idFactura, $estado){
		//@Objetivo:
		//Modificar el estado de una factura real
		$db=$this->db;
		$smt=$db->query('UPDATE facclit set estado="'.$estado .'" where id='.$idFactura);
	}
	public function eliminarFacturasTablas($idFactura){
		//@Objetivo:
		//Eliminar todos los registros de un id de factura real
		$db=$this->db;
		$smt=$db->query('DELETE FROM  facclit where id='.$idFactura );
		$smt=$db->query('DELETE FROM  facclilinea where idfaccli ='.$idFactura );
		$smt=$db->query('DELETE FROM faccliIva where idfaccli ='.$idFactura );
		$smt=$db->query('DELETE FROM albclifac where idFactura  ='.$idFactura );	
	}
	public function AddFacturaGuardado($datos, $idFactura, $numFactura){
		//@Objetivo:
		//Añadir todos los registros de las diferentes tablas de una factura real
		$db = $this->db;
		if ($idFactura>0){
			$smt = $db->query ('INSERT INTO facclit (id, Numfaccli, Fecha, idTienda , idUsuario , idCliente , estado , total, fechaCreacion, formaPago, fechaVencimiento,  fechaModificacion) VALUES ('.$idFactura.' , '.$numFactura.' , "'.$datos['Fecha'].'", '.$datos['idTienda'].', '.$datos['idUsuario'].', '.$datos['idCliente'].', "'.$datos['estado'].'", '.$datos['total'].', "'.$datos['fechaCreacion'].'", '.$datos['formapago'].', "'.$datos['fechaVencimiento'].'", "'.$datos['fechaModificacion'].'")');
			$id=$idFactura;
		}else{
			$smt = $db->query ('INSERT INTO facclit (Numtemp_faccli , Fecha, idTienda , idUsuario , idCliente , estado , total, fechaCreacion, formaPago, fechaVencimiento, fechaModificacion) VALUES ('.$datos['Numtemp_faccli'].' , "'.$datos['Fecha'].'", '.$datos['idTienda']. ', '.$datos['idUsuario'].', '.$datos['idCliente'].' , "'.$datos['estado'].'", '.$datos['total'].', "'.$datos['fechaCreacion'].'", '.$datos['formapago'].', "'.$datos['fechaVencimiento'].'" ,  "'.$datos['fechaModificacion'].'")');
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
		if(is_array($datos['importes'])){
			foreach ($datos['importes'] as $importe){
				$sql='INSERT INTO fac_cobros (idFactura, idFormasPago, FechaPago, importe, Referencia) VALUES ('.$id.' , '.$importe['forma'].' , "'.$importe['fecha'].'", '.$importe['importe'].', '."'".$importe['referencia']."'".')';
				$smt=$db->query($sql);
				$resultado['sql']=$sql;
			}
		}
			
		
		
		
		return $resultado;
	}
	public function sumarIva($numFactura){
		//@Objetivo:
		//Selecciona el importe iva y total base de una factura real
		$db=$this->db;
		$smt=$db->query('select sum(importeIva ) as importeIva , sum(totalbase) as  totalbase from faccliIva where  Numfaccli  ='.$numFactura);
		if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	public function formasVencimientoTemporal($idTemporal, $json){
		//@Objetivo:
		//Modificar la forma de vencimiento de una factura temporal
		$db=$this->db;
		$smt=$db->query('UPDATE faccliltemporales set FacCobros='."'".$json."'".' where id='.$idTemporal);
	}
	//~ public function importesFacturaDatos($idFactura){
		//~ //@Objetivo:
		//~ //Buscar los importes añadidos a una factura 
		//~ $db=$this->db;
		//~ $smt=$db->query ('SELECT total , entregado, importes FROM facclit where id='.$idFactura );
			//~ if ($result = $smt->fetch_assoc () ){
			//~ $factura=$result;
		//~ }
		//~ return $factura;
	//~ }
	//~ public function modificarImportesFactura($idFactura, $jsonImporte, $entregado, $estado){
		//~ //@Objetivo:
		//~ //Modifica los importes de una factura
		//~ $db=$this->db;
		//~ $smt=$db->query('UPDATE facclit SET importes='."'".$jsonImporte."'".' , entregado='.$entregado.' , estado="'.$estado.'" where id='.$idFactura);
	//~ }
	public function modificarImportesTemporal($idTemporal, $importes){
		$db=$this->db;
		
		$sql='UPDATE faccliltemporales SET FacCobros='."'".$importes."'".' WHERE id='.$idTemporal;
		$smt=$db->query($sql);
		return $sql;
	}
	public function importesTemporal($idTemporal){
		$db=$this->db;
		$smt=$db->query ('SELECT FacCobros FROM faccliltemporales where id='.$idTemporal );
			if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	public function importesFactura($idFactura){
		$db=$this->db;
		$smt=$db->query ('SELECT * FROM fac_cobros where idFactura='.$idFactura );
		$importesPrincipal=array();
		while ($result = $smt->fetch_assoc () ){
			array_push($importesPrincipal,$result);
		}
		return $importesPrincipal;
	}
}


?>
