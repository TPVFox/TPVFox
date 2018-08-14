<?php 
include_once $URLCom.'/modulos/mod_venta/clases/ClaseVentas.php';

class FacturasVentas extends ClaseVentas{
	
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM facclit';
		$respuesta = $this->consulta($sql);
		// Controlamos el resultado.
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
	public function TodosTemporal(){
		//@Objetivo:
		//Mostrar los datos principales de una factura temnporal
			$db = $this->db;
			$sql='SELECT tem.numfaccli, tem.id , tem.idClientes,
			 tem.total, b.Nombre from faccliltemporales as tem left JOIN 
			 clientes as b on tem.idClientes=b.idClientes';
			 $smt=$this->consulta($sql);
			if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
			}else{
				$facturaPrincipal=array();
				while ( $result = $smt->fetch_assoc () ) {
					array_push($facturaPrincipal,$result);
				}
				return $facturaPrincipal;
			}
		
	}
	public function TodosFacturaFiltro($filtro){
		//@Objetivo:
		//Mostrar los datos principales de todas las facturas con el filtro de paginacion 
		$db=$this->db;
		$sql = 'SELECT a.id , a.Numfaccli , a.Fecha , b.Nombre, a.total, a.estado 
		FROM `facclit` as a LEFT JOIN clientes as b on a.idCliente=b.idClientes  '.$filtro;
		$smt=$this->consulta($sql);
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}else{
			$facturaPrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
				array_push($facturaPrincipal,$result);
			}
			$respuesta = array();
			$respuesta['Items'] = $facturaPrincipal;
			$respuesta['consulta'] = $sql;
			return $respuesta;
		}
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
			$sql='DELETE FROM faccliltemporales WHERE numfaccli ='.$idFactura;
		}else{
			$sql='DELETE FROM faccliltemporales WHERE id='.$idTemporal;
		}
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
		
	}
	public function modificarDatosFacturaTemporal($idUsuario, $idTienda, $estadoFactura, $fecha , $albaranes, $idTemporal, $productos){
		//@Objetivo:
		//Modificar los datos de una factura temporal
		$db = $this->db;
		$respuesta=array();
		$UnicoCampoAlbaranes=json_encode($albaranes);
		$UnicoCampoProductos=json_encode($productos);
		$PrepProductos = $db->real_escape_string($UnicoCampoProductos);
		$PrepAlbaranes = $db->real_escape_string($UnicoCampoAlbaranes);
		$sql='UPDATE faccliltemporales SET idUsuario='.$idUsuario
		.' , idTienda='.$idTienda.' , estadoFacCli="'.$estadoFactura.'" , fechaInicio='
		.$fecha.' , Albaranes ="'.$PrepAlbaranes.'" ,Productos="'.$PrepProductos
		.' " WHERE id='.$idTemporal;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}else{
			$respuesta['idTemporal']=$idTemporal;
			$respuesta['productos']=$UnicoCampoProductos;
		}
	
		return $respuesta;
	}
	public function insertarDatosFacturaTemporal($idUsuario, $idTienda, $estadoFactura, $fecha , $albaranes, $productos, $idCliente){
		//@Objetivo:
		//Insertar nuevo registro de factura 
		$db = $this->db;
		$respuesta=array();
		$UnicoCampoAlbaranes=json_encode($albaranes);
		$UnicoCampoProductos=json_encode($productos);
		$PrepProductos = $db->real_escape_string($UnicoCampoProductos);
		$PrepAlbaranes = $db->real_escape_string($UnicoCampoAlbaranes);
		$sql='INSERT INTO faccliltemporales ( idUsuario , idTienda ,
		 estadoFacCli , fechaInicio, idClientes, Albaranes, Productos ) VALUES ('
		 .$idUsuario.' , '.$idTienda.' , "'.$estadoFactura.'" , "'.$fecha.'", '
		 .$idCliente.' , "'.$PrepAlbaranes.'", "'.$PrepProductos.'")';
		 $smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
		}else{
			$id=$db->insert_id;
			$respuesta['id']=$id;
			$respuesta['productos']=$productos;
		}
		return $respuesta;
	}
	public function addNumRealTemporal($idTemporal,  $numFactura){
		//@Objetivo:
		//Añadir a una factura temporal el número real de la factura en el caso de que exista 
		$db = $this->db;
		$sql='UPDATE faccliltemporales SET numfaccli ='.$numFactura.' WHERE id='.$idTemporal;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
	}
	public function modTotales($res, $total, $totalivas){
		//@Objetivo:
		//Modificar el total de una factura temporal
		$db=$this->db;
		$sql='UPDATE faccliltemporales set total='.$total .' , total_ivas='.$totalivas .' where id='.$res;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
	}
	public function modificarEstado($idFactura, $estado){
		//@Objetivo:
		//Modificar el estado de una factura real
		$db=$this->db;
		$sql='UPDATE facclit set estado="'.$estado .'" where id='.$idFactura;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
	}
	public function eliminarFacturasTablas($idFactura){
		//@Objetivo:
		//Eliminar todos los registros de un id de factura real
		$respuesta=array();
		$db=$this->db;
		$sql[0]='DELETE FROM  facclit where id='.$idFactura ;
		$sql[1]='DELETE FROM  facclilinea where idfaccli ='.$idFactura ;
		$sql[2]='DELETE FROM faccliIva where idfaccli ='.$idFactura ;
		$sql[3]='DELETE FROM albclifac where idFactura  ='.$idFactura ;	
		foreach($sql as $consulta){
			$smt=$this->consulta($consulta);
			if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				break;
			}
		}
		return $respuesta;
	}
	public function AddFacturaGuardado($datos, $idFactura){
		//@Objetivo:
		//Añadir todos los registros de las diferentes tablas de una factura real
		$respuesta=array();
		$db = $this->db;
		if ($idFactura>0){
			$sql='INSERT INTO facclit (id, Numfaccli, Fecha, idTienda 
			, idUsuario , idCliente , estado , total, fechaCreacion, formaPago, 
			fechaVencimiento,  fechaModificacion) VALUES ('.$idFactura.' , '.$idFactura
			.' , "'.$datos['Fecha'].'", '.$datos['idTienda'].', '.$datos['idUsuario'].', '
			.$datos['idCliente'].', "'.$datos['estado'].'", '.$datos['total'].', "'
			.$datos['fechaCreacion'].'", "'.$datos['formapago'].'", "'.$datos['fechaVencimiento']
			.'", "'.$datos['fechaModificacion'].'")';
			$smt=$this->consulta($sql);
			if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
			}else{
				$id=$idFactura;
			}
		}else{
			$sql='INSERT INTO facclit (Numtemp_faccli , Fecha, 
			idTienda , idUsuario , idCliente , estado , total, fechaCreacion,
			 formaPago, fechaVencimiento, fechaModificacion) VALUES ('
			 .$datos['Numtemp_faccli'].' , "'.$datos['Fecha'].'", '.$datos['idTienda']
			 . ', '.$datos['idUsuario'].', '.$datos['idCliente'].' , "'.$datos['estado']
			 .'", '.$datos['total'].', "'.$datos['fechaCreacion'].'", "'.$datos['formapago']
			 .'", "'.$datos['fechaVencimiento'].'" ,  "'.$datos['fechaModificacion'].'")';
			 $smt=$this->consulta($sql);
			if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
			}else{
				$id=$db->insert_id;
				$sql='UPDATE facclit SET Numfaccli  = '.$id.' WHERE id ='.$id;
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
			if ($prod['estadoLinea']=="Activo"){
				$codBarras="";
				$numAl=0;
				if (isset($prod['ccodbar'])){
					$codBarras=$prod['ccodbar'];
				}
				if (isset($prod['Numalbcli'])){
					$numAl=$prod['Numalbcli'];
				}else{
					if (isset($prod['NumalbCli'])){
						$numAl=$prod['NumalbCli'];
					}
				}
				
				$sql='INSERT INTO facclilinea (idfaccli  , Numfaccli ,
				 idArticulo , cref, ccodbar, cdetalle, ncant, nunidades, precioCiva, 
				 iva, nfila, estadoLinea, NumalbCli, pvpSiva ) VALUES ('.$id.', '.$id.' , '
				 .$prod['idArticulo'].', '."'".$prod['cref']."'".', "'.$codBarras.'", "'
				 .$prod['cdetalle'].'", '.$prod['ncant'].' , '.$prod['nunidades'].', '
				 .$prod['precioCiva'].' , '.$prod['iva'].', '.$i.', "'. $prod['estadoLinea']
				 .'" , '.$numAl.', '.$prod['pvpSiva'].')' ;
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
			$sql='INSERT INTO faccliIva (idfaccli  ,  Numfaccli  , iva , 
			importeIva, totalbase) VALUES ('.$id.', '.$id.' , '.$iva.', '
			.$basesYivas['iva'].' , '.$basesYivas['base'].')';
			$smt=$this->consulta($sql);
					if (gettype($smt)==='array'){
						$respuesta['error']=$smt['error'];
						$respuesta['consulta']=$smt['consulta'];
						break;
					}
		}
		$albaranes = json_decode($datos['albaranes'], true); 
		if (isset($albaranes)){
		foreach ($albaranes as $albaran){
			if ($albaran['estado']=="activo" || $albaran['estado']=="Activo"){
					$sql='INSERT INTO albclifac (idFactura  ,  numFactura  
					 , idAlbaran , numAlbaran) VALUES ('.$id.', '.$id.' ,  '
					 .$albaran['idAlbaran'].' , '.$albaran['Numalbcli'].')';
					$smt=$this->consulta($sql);
					if (gettype($smt)==='array'){
						$respuesta['error']=$smt['error'];
						$respuesta['consulta']=$smt['consulta'];
						break;
					}
			}
		}
		}
		if(is_array($datos['importes'])){
			foreach ($datos['importes'] as $importe){
				$sql='INSERT INTO fac_cobros (idFactura, idFormasPago, FechaPago, 
				importe, Referencia) VALUES ('.$id.' , '.$importe['forma'].' , "'
				.$importe['fecha'].'", '.$importe['importe'].', '."'".$importe['referencia']."'".')';
				$smt=$this->consulta($sql);
					if (gettype($smt)==='array'){
						$respuesta['error']=$smt['error'];
						$respuesta['consulta']=$smt['consulta'];
						break;
					}
			}
		}
		return $respuesta;
	}
	public function sumarIva($numFactura){
		//@Objetivo:
		//Selecciona el importe iva y total base de una factura real
		$db=$this->db;
		$smt=$db->query('select sum(importeIva ) as importeIva , sum(totalbase) as
		  totalbase from faccliIva where  Numfaccli  ='.$numFactura);
		if ($result = $smt->fetch_assoc () ){
			$factura=$result;
		}
		return $factura;
	}
	public function formasVencimientoTemporal($idTemporal, $json){
		//@Objetivo:
		//Modificar la forma de vencimiento de una factura temporal
		$db=$this->db;
		$sql='UPDATE faccliltemporales set FacCobros='."'".$json."'".' where id='.$idTemporal;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
	}
	public function modificarImportesTemporal($idTemporal, $importes){
		$db=$this->db;
		$sql='UPDATE faccliltemporales SET FacCobros='."'".$importes."'".' WHERE id='.$idTemporal;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
	}
	public function importesTemporal($idTemporal){
		$db=$this->db;
		$sql='SELECT FacCobros FROM faccliltemporales where id='.$idTemporal ;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}else{
			if ($result = $smt->fetch_assoc () ){
				$factura=$result;
			}
			return $factura;
		}
	}
	public function importesFactura($idFactura){
		$db=$this->db;
		$sql='SELECT * FROM fac_cobros where idFactura='.$idFactura ;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}else{
			$importesPrincipal=array();
			while ($result = $smt->fetch_assoc () ){
				array_push($importesPrincipal,$result);
			}
			return $importesPrincipal;
		}
	}
	public function eliminarRealImportes($idFactura){
		$db=$this->db;
		$sql='DELETE FROM  fac_cobros where idFactura='.$idFactura ;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
	}
	public function modificarFechaFactura($idFactura, $Fecha, $formaPago, $fechaVenci){
		$respuesta=array();
		$db=$this->db;
		$sql='UPDATE  facclit set Fecha="'.$Fecha.'" , formaPago="'.$formaPago.'" ,  fechaVencimiento="'.$fechaVenci.'" where id='.$idFactura ;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta=array();
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				
		}
		$respuesta['sql']=$sql;
		return $respuesta;
	}
}
	


?>
