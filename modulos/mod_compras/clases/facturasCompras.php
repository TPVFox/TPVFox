<?php 
include_once ('./clases/ClaseCompras.php');
class FacturasCompras extends ClaseCompras{
	public function consulta($sql){
		$db = $this->db;
		$smt = $db->query($sql);
		return $smt;
	}
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM facprot';
		$respuesta = $this->consulta($sql);
		$this->num_rows = $respuesta->fetch_object()->num_reg;
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	public function TodosTemporal(){
		//@Objetivo:
		//Mostrar tod los temporales para el listado principal
		$db = $this->db;
		$smt=$db->query('SELECT tem.numfacpro, tem.id , tem.idProveedor, tem.total, b.nombrecomercial from facproltemporales as tem left JOIN proveedores as b on tem.idProveedor=b.idProveedor ');
			$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
		
	}
	
	public function TodosFactura(){
		//@Objetivo:
		//Mostrar solo los datos principales de todas las facturas
		$db=$this->db;
		$smt=$db->query('SELECT a.id , a.Numfacpro , a.Fecha , b.nombrecomercial, a.total, a.estado FROM `facprot` as a LEFT JOIN proveedores as b on a.idProveedor=b.idProveedor ');
		$facturaPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($facturaPrincipal,$result);
		}
		return $facturaPrincipal;
	}
	
	public function TodosFacturaLimite($limite){
		//@Objetivo:
		//Mostrar los datos principales de una factura con un límite de registros
		$db=$this->db;
		$smt=$db->query('SELECT a.id , a.Numfacpro , a.Fecha , b.nombrecomercial, a.total, a.estado FROM `facprot` as a LEFT JOIN proveedores as b on a.idProveedor=b.idProveedor '.$limite);
		$pedidosPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($pedidosPrincipal,$result);
		}
		return $pedidosPrincipal;
	}
	
	public function sumarIva($numFactura){
		//@Objetivo:
		//Sumar los resultado de importe iva y total base de una factura determinada
		//~ $db=$this->db;
		//~ $smt=$db->query('select sum(importeIva ) as importeIva , sum(totalbase) as  totalbase from facproIva where Numfacpro ='.$numFactura);
		//~ if ($result = $smt->fetch_assoc () ){
			//~ $factura=$result;
		//~ }
		//~ return $factura;
		$from_where= 'from facproIva where Numfacpro ='.$numFactura;
		$factura = parent::sumarIvaBases($from_where);
		
		return $factura;
	}
	
	public function datosFactura($idFactura){
		//@Objetivo:
		//Mostrar los datos de una factura determinada buscada por id
		//~ $db=$this->db;
		//~ $smt = $db->query ('SELECT * from facprot where id='.$idFactura);
			//~ if ($result = $smt->fetch_assoc () ){
			//~ $factura=$result;
		//~ }
		//~ return $factura;
		$tabla='facprot';
		$where='id='.$idFactura;
		$factura = parent::SelectUnResult($tabla, $where);
		return $factura;
	}
	
	public function ProductosFactura($idFactura){
		//@Objetivo:
		//Buscar los productos de una factura determinada
		//~ $db=$this->db;
		//~ $smt=$db->query('SELECT * from  facprolinea where idfacpro='.$idFactura);
		//~ $facturaPrincipal=array();
		//~ while ( $result = $smt->fetch_assoc () ) {
			//~ array_push($facturaPrincipal,$result);
		//~ }
		//~ return $facturaPrincipal;
		$tabla='facprolinea';
		$where='idfacpro= '.$idFactura;
		$factura = parent::SelectVariosResult($tabla, $where);
		return $factura;
	}
	
	public function IvasFactura($idFactura){
		//@Objetivo:
		//Buscar los ivas de una factura determinada
		//~ $db=$this->db;
		//~ $smt=$db->query('SELECT * from  facproIva where idfacpro='.$idFactura);
		//~ $facturaPrincipal=array();
		//~ while ( $result = $smt->fetch_assoc () ) {
			//~ array_push($facturaPrincipal,$result);
		//~ }
		//~ return $facturaPrincipal;
		$tabla='facproIva';
		$where='idfacpro= '.$idFactura;
		$factura = parent::SelectVariosResult($tabla, $where);
		return $factura;
	}
	
	public function albaranesFactura($idFactura){
		//@Objetivo:
		//Buscar todos los albaranes de un id de factura determinado 
		//~ $db=$this->db;
		//~ $smt=$db->query('SELECT * from  albprofac where idFactura='.$idFactura);
		//~ $facturaPrincipal=array();
		//~ while ( $result = $smt->fetch_assoc () ) {
			//~ array_push($facturaPrincipal,$result);
		//~ }
		//~ return $facturaPrincipal;
		$tabla='albprofac';
		$where='idFactura= '.$idFactura;
		$factura = parent::SelectVariosResult($tabla, $where);
		return $factura;
	}
	
	public function buscarFacturaTemporal($idFacturaTemporal){
		//@Objetivo:
		//Buscar los datos de una factura temporal
		//~ $db=$this->db;
		//~ $smt=$db->query('SELECT * FROM facproltemporales WHERE id='.$idFacturaTemporal);
		//~ if ($result = $smt->fetch_assoc () ){
			//~ $factura=$result;
		//~ }
		//~ return $factura;
		$tabla='facproltemporales';
		$where='id='.$idFacturaTemporal;
		$factura = parent::SelectUnResult($tabla, $where);
		return $factura;
	}
	
	public function buscarFacturaNumero($numFactura){
		//@Objetivo:
		//Buscar los datos de un número de factura
		//~ $db=$this->db;
		//~ $smt=$db->query('SELECT * FROM facprot WHERE Numfacpro='.$numFactura);
		//~ if ($result = $smt->fetch_assoc () ){
			//~ $factura=$result;
		//~ }
		//~ return $factura;
		$tabla='facprot';
		$where='Numfacpro='.$numFactura;
		$factura = parent::SelectUnResult($tabla, $where);
		return $factura;
	}
	
	public function modificarDatosFacturaTemporal($idUsuario, $idTienda, $estado, $fecha ,  $idFacturaTemp, $productos, $albaranes, $suNumero){
		//@Objetivo:
		//MOdficar los datos de una factura temporal
		$db = $this->db;
		$UnicoCampoProductos=json_encode($productos);
		$UnicoCampoAlbaranes=json_encode($albaranes);
		$smt=$db->query('UPDATE facproltemporales SET idUsuario ='.$idUsuario.' , idTienda='.$idTienda.' , estadoFacPro="'.$estado.'" , fechaInicio="'.$fecha.'"  ,Productos='."'".$UnicoCampoProductos."'".', Albaranes='."'".$UnicoCampoAlbaranes."'".' , Su_numero='.$suNumero.' WHERE id='.$idFacturaTemp);
		
		$respuesta['idTemporal']=$idFacturaTemp;
		$respuesta['productos']=$UnicoCampoProductos;
		$respuesta['pedidos']=$UnicoCampoAlbaranes;
		return $respuesta;
	}
	
	public function insertarDatosFacturaTemporal($idUsuario, $idTienda, $estado, $fecha ,  $productos, $idProveedor, $albaranes, $suNumero){
		//@Objetivo:
		//Insertar los datos de una factura temporal nueva
		$db = $this->db;
		$UnicoCampoProductos=json_encode($productos);
		$UnicoCampoAlbaranes=json_encode($albaranes);
		$smt = $db->query ('INSERT INTO facproltemporales ( idUsuario , idTienda , estadoFacPro , fechaInicio, idProveedor,  Productos, Albaranes , Su_numero) VALUES ('.$idUsuario.' , '.$idTienda.' , "'.$estado.'" , "'.$fecha.'", '.$idProveedor.' , '."'".$UnicoCampoProductos."'".' , '."'".$UnicoCampoAlbaranes."'".', '.$suNumero.')');
		$id=$db->insert_id;
		$respuesta['id']=$id;
		$respuesta['productos']=$productos;
		
		return $respuesta;
	}
	
	public function addNumRealTemporal($idTemporal, $idReal){
		//@Objetivo:
		//Añadir a una factura temporal el número de la factura real en el caso de que exista factura real
		$db=$this->db;
		$smt=$db->query('UPDATE facproltemporales set numfacpro ='.$idReal .'  where id='.$idTemporal);
	}
	
	public function modEstadoFactura($idFactura, $estado){
		//@Objetivo:
		//Modificar el estado de una factura
		$db=$this->db;
		$smt=$db->query('UPDATE facprot set estado="'.$estado .'"  where id='.$idFactura);
	}
	
	public function modTotales($res, $total, $totalivas){
		//@Objetivo:
		//Modificar el total de una factura temporal, lo hacemos cada vez que añadimos un producto nuevo
		$db=$this->db;
		$smt=$db->query('UPDATE facproltemporales set total='.$total .' , total_ivas='.$totalivas .' where id='.$res);
		return $resultado;
	}
	
	public function eliminarFacturasTablas($idFactura){
		//@Objetivo:
		//Eliminar todos los datos de una determinada factura
		$db=$this->db;
		$smt=$db->query('DELETE FROM facprot where id='.$idFactura );
		$smt=$db->query('DELETE FROM facprolinea where 	idfacpro ='.$idFactura );
		$smt=$db->query('DELETE FROM facproIva where idfacpro ='.$idFactura );
		$smt=$db->query('DELETE FROM albprofac where idFactura ='.$idFactura );
		
	}
	
	public function AddFacturaGuardado($datos, $idFactura, $numFactura){
		//@Objetivo:
		//Añadir todos los datos de una factura nueva en las diferentes tablas
		$db = $this->db;
		if ($idFactura>0){
			$smt = $db->query ('INSERT INTO facprot (id, Numfacpro, Fecha, idTienda , idUsuario , idProveedor , estado , total, Su_num_factura ) VALUES ('.$idFactura.' , '.$numFactura.', "'.$datos['fecha'].'", '.$datos['idTienda'].', '.$datos['idUsuario'].', '.$datos['idProveedor'].', "'.$datos['estado'].'", '.$datos['total'].', '.$datos['suNumero'].')');
			$id=$idFactura;
		
		}else{
			$smt = $db->query ('INSERT INTO facprot (Numtemp_facpro, Fecha, idTienda , idUsuario , idProveedor , estado , total, Su_num_factura ) VALUES ('.$datos['Numtemp_facpro'].' , "'.$datos['fecha'].'", '.$datos['idTienda']. ', '.$datos['idUsuario'].', '.$datos['idProveedor'].' , "'.$datos['estado'].'", '.$datos['total'].', '.$datos['suNumero'].')');
			$id=$db->insert_id;
			$resultado['id']=$id;
			$smt = $db->query('UPDATE facprot SET Numfacpro  = '.$id.' WHERE id ='.$id);
		}
		$productos = json_decode($datos['productos'], true);
		foreach ( $productos as $prod){
			if ($prod['estado']=='Activo' || $prod['estado']=='activo'){
			if ($prod['ccodbar']){
				$codBarras=$prod['ccodbar'];
			}else{
				$codBarras=0;
			}
			if ($prod['numAlbaran']){
				$numPed=$prod['numAlbaran'];
			}else{
				$numPed=0;
			}
			if ($prod['crefProveedor']){
				$refProveedor=$prod['crefProveedor'];
			}else{
				$refProveedor=0;
			}
			if ($idFactura>0){
			$smt=$db->query('INSERT INTO facprolinea (idfacpro  , Numfacpro  , idArticulo , cref, ccodbar, cdetalle, ncant, nunidades, costeSiva, iva, nfila, estadoLinea, ref_prov , Numalbpro ) VALUES ('.$id.', '.$idFactura.' , '.$prod['idArticulo'].', '."'".$prod['cref']."'".', '.$codBarras.', "'.$prod['cdetalle'].'", '.$prod['ncant'].' , '.$prod['ncant'].', '.$prod['ultimoCoste'].' , '.$prod['iva'].', '.$prod['nfila'].', "'. $prod['estado'].'" , '."'".$refProveedor."'".', '.$numPed.')' );
			}else{
			$smt=$db->query('INSERT INTO facprolinea (idfacpro  , Numfacpro  , idArticulo , cref, ccodbar, cdetalle, ncant, nunidades, costeSiva, iva, nfila, estadoLinea, ref_prov  , Numalbpro ) VALUES ('.$id.', '.$id.' , '.$prod['idArticulo'].', '."'".$prod['cref']."'".', '.$codBarras.', "'.$prod['cdetalle'].'", '.$prod['ncant'].' , '.$prod['ncant'].', '.$prod['ultimoCoste'].' , '.$prod['iva'].', '.$prod['nfila'].', "'. $prod['estado'].'" , '."'".$refProveedor."'".', '.$numPed.')' );
			}
		}
			
		} 
		foreach ($datos['DatosTotales']['desglose'] as  $iva => $basesYivas){
			if($idFactura>0){
			$smt=$db->query('INSERT INTO facproIva (idfacpro  ,  Numfacpro  , iva , importeIva, totalbase) VALUES ('.$id.', '.$idFactura.' , '.$iva.', '.$basesYivas['iva'].' , '.$basesYivas['base'].')');
			}else{
			$smt=$db->query('INSERT INTO facproIva (idfacpro  ,  Numfacpro  , iva , importeIva, totalbase) VALUES ('.$id.', '.$id.' , '.$iva.', '.$basesYivas['iva'].' , '.$basesYivas['base'].')');
			}
		}
		$albaranes = json_decode($datos['albaranes'], true); 
		foreach ($albaranes as $albaran){
			if ($albaran['estado']=='activo'){
				if($idAlbaran>0){
				
				$smt=$db->query('INSERT INTO albprofac (idFactura  ,  numFactura   , idAlbaran , numAlbaran) VALUES ('.$id.', '.$idFactura.' ,  '.$albaran['idAlbaran'].' , '.$albaran['Numalbpro'].')');
				}else{
				$smt=$db->query('INSERT INTO albprofac (idFactura  ,  numFactura   , idAlbaran , numAlbaran) VALUES ('.$id.', '.$id.' ,  '.$albaran['idAlbaran'].' , '.$albaran['Numalbpro'].')');
				}
			}
		}
		return $resultado;
	}
	
	public function EliminarRegistroTemporal($idTemporal, $idFactura){
		//@Objetivo:
		//CAda vez que guardamos una factura nueva o ya existente eliminamos su temporal
		$db=$this->db;
		if ($idFactura>0){
			$smt=$db->query('DELETE FROM facproltemporales WHERE numfacpro ='.$idFactura);
		}else{
			$smt=$db->query('DELETE FROM facproltemporales WHERE id='.$idTemporal);
		}
	}
}

?>
