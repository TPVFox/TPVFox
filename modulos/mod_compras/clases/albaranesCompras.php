<?php 
//~ include_once ('./clases/ClaseCompras.php');
 include_once('../mod_compras/clases/ClaseCompras.php');
class AlbaranesCompras extends ClaseCompras{
	public function consulta($sql){
		// Realizamos la consulta.
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
	public function __construct($conexion){
		$this->db = $conexion;
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM albprot';
		$respuesta = $this->consulta($sql);
		if (gettype($respuesta)==='object'){
			$this->num_rows = $respuesta->fetch_object()->num_reg;
		} else {
			// Es un array porque hubo un fallo
			echo '<pre>';
			print_r($respuesta);
			echo '</pre>';
		}
		// Ahora deberiamos controlar que hay resultado , si no hay debemos generar un error.
	}
	public function modificarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoPedido, $fecha ,  $idAlbaranTemporal, $productos, $pedidos, $suNumero){
	//@Objetivo;
	//Modificamos los datos del pedido temporal, cada vez que hacemos cualquier modificación en el albarán,
	// modificamos el temporal

		$db = $this->db;
		$UnicoCampoProductos=json_encode($productos);
		$UnicoCampoPedidos=json_encode($pedidos);
		$smt=$db->query('UPDATE albproltemporales SET idUsuario ='.$idUsuario.' , idTienda='.$idTienda.' , estadoAlbPro="'.$estadoPedido.'" , fechaInicio="'.$fecha.'"  ,Productos='."'".$UnicoCampoProductos."'".', Pedidos='."'".$UnicoCampoPedidos."'".' , Su_numero='.$suNumero.' WHERE id='.$idAlbaranTemporal);
		$respuesta['idTemporal']=$idAlbaranTemporal;
		$respuesta['productos']=$UnicoCampoProductos;
		$respuesta['pedidos']=$UnicoCampoPedidos;
		return $respuesta;
	}

	public function insertarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoPedido, $fecha ,  $productos, $idProveedor, $pedidos, $suNumero){
		//Objetivo:
		//insertar un nuevo albaran temporal
		$db = $this->db;
		$UnicoCampoProductos=json_encode($productos);
		$UnicoCampoPedidos=json_encode($pedidos);
		$sql='INSERT INTO albproltemporales ( idUsuario , idTienda , estadoAlbPro , fechaInicio, idProveedor,  Productos, Pedidos , Su_numero) VALUES ('.$idUsuario.' , '.$idTienda.' , "'.$estadoPedido.'" , "'.$fecha.'", '.$idProveedor.' , '."'".$UnicoCampoProductos."'".' , '."'".$UnicoCampoPedidos."'".', '.$suNumero.')';
		//~ $smt = $db->query ('INSERT INTO albproltemporales ( idUsuario , idTienda , estadoAlbPro , fechaInicio, idProveedor,  Productos, Pedidos , Su_numero) VALUES ('.$idUsuario.' , '.$idTienda.' , "'.$estadoPedido.'" , "'.$fecha.'", '.$idProveedor.' , '."'".$UnicoCampoProductos."'".' , '."'".$UnicoCampoPedidos."'".', '.$suNumero.')');
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
		}else{
			//$id=$db->insert_id;
			$respuesta['id']=$db->insert_id;
			$respuesta['productos']=$productos;
		}
		return $respuesta;
	}
	public function addNumRealTemporal($idTemporal, $idReal){
		//Objetivo:
		//Modificar el albarán tempoal en el caso de que tengamos un numeroReal
		$db=$this->db;
		$smt=$db->query('UPDATE albproltemporales set numalbpro ='.$idReal .'  where id='.$idTemporal);
	}
	public function modEstadoAlbaran($idAlbaran, $estado){
		// @Objetivo:
		//Modificar el estado del albarán
		$db=$this->db;
		$smt=$db->query('UPDATE albprot set estado="'.$estado .'"  where id='.$idAlbaran);
	}
	
	public function modTotales($res, $total, $totalivas){
		//@Objetivo:Modificar los totales del albarán temporal
		$db=$this->db;
		$smt=$db->query('UPDATE albproltemporales set total='.$total .' , total_ivas='.$totalivas .' where id='.$res);
	}
	
	public function buscarAlbaranTemporal($idAlbaranTemporal){
		//@Objetivo:
		//Buscar los datos del un albarán temporal
		$db=$this->db;
		$smt=$db->query('SELECT * FROM albproltemporales WHERE id='.$idAlbaranTemporal);
		if ($result = $smt->fetch_assoc () ){
			$albaran=$result;
		}
		return $albaran;
	}
	public function buscarAlbaranNumero($numAlbaran){
		//@Objetivo:
		//Buscamos los datos de un albarán real según el número del albarán.
		$tabla='albprot';
		$where='Numalbpro='.$numAlbaran;
		$albaran = parent::SelectUnResult($tabla, $where);
		return $albaran;
	}
	
	public function eliminarAlbaranTablas($idAlbaran){
		//@Objetivo:
		//Eliminamos todos los registros de un albarán determinado. Lo hacemos cuando vamos a crear uno nuevo
		$db=$this->db;
		$smt=$db->query('DELETE FROM albprot where id='.$idAlbaran );
		$smt=$db->query('DELETE FROM albprolinea where idalbpro ='.$idAlbaran );
		$smt=$db->query('DELETE FROM albproIva where idalbpro ='.$idAlbaran );
		$smt=$db->query('DELETE FROM pedproAlb where idAlbaran ='.$idAlbaran );
		
	}
	
	public function AddAlbaranGuardado($datos, $idAlbaran){
		//@Objetivo:
		//Añadimos los registro de un albarán nuevo, cada uno en una respectiva tabla
		$db = $this->db;
		if ($idAlbaran>0){
			$smt = $db->query ('INSERT INTO albprot (id, Numalbpro, Fecha, idTienda , idUsuario , idProveedor , estado , total, Su_numero, formaPago,FechaVencimiento) VALUES ('.$idAlbaran.' , '.$idAlbaran.', "'.$datos['fecha'].'", '.$datos['idTienda'].', '.$datos['idUsuario'].', '.$datos['idProveedor'].', "'.$datos['estado'].'", '.$datos['total'].', '.$datos['suNumero'].', '.$datos['formaPago'].', "'.$datos['fechaVenci'].'")');
			$id=$idAlbaran;
	$resultado['id']=$id;
		}else{
			$smt = $db->query ('INSERT INTO albprot (Numtemp_albpro, Fecha, idTienda , idUsuario , idProveedor , estado , total, Su_numero, formaPago, FechaVencimiento) VALUES ('.$datos['Numtemp_albpro'].' , "'.$datos['fecha'].'", '.$datos['idTienda']. ', '.$datos['idUsuario'].', '.$datos['idProveedor'].' , "'.$datos['estado'].'", '.$datos['total'].', '.$datos['suNumero'].', '.$datos['formaPago'].', "'.$datos['fechaVenci'].'")');
			$id=$db->insert_id;
			$resultado['id']=$id;
			$smt = $db->query('UPDATE albprot SET Numalbpro  = '.$id.' WHERE id ='.$id);
			$sql='INSERT INTO albprot (Numtemp_albpro, Fecha, idTienda , idUsuario , idProveedor , estado , total, Su_numero, formaPago, FechaVencimiento) VALUES ('.$datos['Numtemp_albpro'].' , "'.$datos['fecha'].'", '.$datos['idTienda']. ', '.$datos['idUsuario'].', '.$datos['idProveedor'].' , "'.$datos['estado'].'", '.$datos['total'].', '.$datos['suNumero'].', '.$datos['formaPago'].', "'.$datos['fechaVenci'].'")';
		}
		$productos = json_decode($datos['productos'], true);
		$i=1;
		foreach ( $productos as $prod){
			if($prod['estado']=='Activo' || $prod['estado']=='activo'){
				if ($prod['ccodbar']){
					$codBarras=$prod['ccodbar'];
				}else{
					$codBarras=0;
				}
				if (isset($prod['numPedido'])){
					if ($prod['numPedido']){
						$numPed=$prod['numPedido'];
					}else{
						$numPed=0;
					}
				}else{
					$numPed=0;
				}
				if (isset ($prod['crefProveedor'])){
					if ($prod['crefProveedor']){
						$refProveedor=$prod['crefProveedor'];
					}else{
						$refProveedor=0;
					}
				}else{
					$refProveedor=0;
				}
			
			
				if ($idAlbaran>0){
				$smt=$db->query('INSERT INTO albprolinea (idalbpro  , Numalbpro  , idArticulo , cref, ccodbar, cdetalle, ncant, nunidades, costeSiva, iva, nfila, estadoLinea, ref_prov , Numpedpro ) VALUES ('.$id.', '.$idAlbaran.' , '.$prod['idArticulo'].', '."'".$prod['cref']."'".', '.$codBarras.', "'.$prod['cdetalle'].'", '.$prod['ncant'].' , '.$prod['nunidades'].', '.$prod['ultimoCoste'].' , '.$prod['iva'].', '.$i.', "'. $prod['estado'].'" , '."'".$refProveedor."'".', '.$numPed.')' );
				}else{
				$smt=$db->query('INSERT INTO albprolinea (idalbpro  , Numalbpro  , idArticulo , cref, ccodbar, cdetalle, ncant, nunidades, costeSiva, iva, nfila, estadoLinea, ref_prov  , Numpedpro ) VALUES ('.$id.', '.$id.' , '.$prod['idArticulo'].', '."'".$prod['cref']."'".', '.$codBarras.', "'.$prod['cdetalle'].'", '.$prod['ncant'].' , '.$prod['nunidades'].', '.$prod['ultimoCoste'].' , '.$prod['iva'].', '.$i.', "'. $prod['estado'].'" , '."'".$refProveedor."'".', '.$numPed.')' );
				$sql='INSERT INTO albprolinea (idalbpro  , Numalbpro  , idArticulo , cref, ccodbar, cdetalle, ncant, nunidades, costeSiva, iva, nfila, estadoLinea, ref_prov  , Numpedpro ) VALUES ('.$id.', '.$id.' , '.$prod['idArticulo'].', '."'".$prod['cref']."'".', '.$codBarras.', "'.$prod['cdetalle'].'", '.$prod['ncant'].' , '.$prod['nunidades'].', '.$prod['ultimoCoste'].' , '.$prod['iva'].', '.$i.', "'. $prod['estado'].'" , '."'".$refProveedor."'".', '.$numPed.')';
				$resultado['sql']=$sql;
				}
				$i++;
			}
		} 
		foreach ($datos['DatosTotales']['desglose'] as  $iva => $basesYivas){
			if($idAlbaran>0){
			$smt=$db->query('INSERT INTO albproIva (idalbpro  ,  Numalbpro  , iva , importeIva, totalbase) VALUES ('.$id.', '.$idAlbaran.' , '.$iva.', '.$basesYivas['iva'].' , '.$basesYivas['base'].')');
			}else{
			$smt=$db->query('INSERT INTO albproIva (idalbpro  ,  Numalbpro  , iva , importeIva, totalbase) VALUES ('.$id.', '.$id.' , '.$iva.', '.$basesYivas['iva'].' , '.$basesYivas['base'].')');
			}
		}
		$pedidos = json_decode($datos['pedidos'], true); 
		if (is_array($pedidos)){
			foreach ($pedidos as $pedido){
				if ($pedido['estado']=='activo'){
				
					if($idAlbaran>0){
						$smt=$db->query('INSERT INTO pedproAlb (idAlbaran  ,  numAlbaran   , idPedido , numPedido) VALUES ('.$id.', '.$idAlbaran.' ,  '.$pedido['idAdjunto'].' , '.$pedido['NumAdjunto'].')');
							}else{
						$smt=$db->query('INSERT INTO pedproAlb (idAlbaran  ,  numAlbaran   , idPedido , numPedido) VALUES ('.$id.', '.$id.' ,  '.$pedido['idAdjunto'].' , '.$pedido['NumAdjunto'].')');
					}
				}
				
			}
		}
		return $resultado;
	}
	
	public function EliminarRegistroTemporal($idTemporal, $idAlbaran){
		//@Objetivo:
		//Cadas vez que añadimos un albarán como guardado tenemos que eliminar el registro temporal
		$db=$this->db;
		if ($idAlbaran>0){
			$smt=$db->query('DELETE FROM albproltemporales WHERE numalbpro ='.$idAlbaran);
		}else{
			$smt=$db->query('DELETE FROM albproltemporales WHERE id='.$idTemporal);
		}
	}
	
	public function TodosTemporal(){
		//@Objetivo:
		//Mostramos todos los albaranes temporales
			$db = $this->db;
			$smt=$db->query('SELECT tem.numalbpro, tem.id , tem.idProveedor, tem.total, b.nombrecomercial from albproltemporales as tem left JOIN proveedores as b on tem.idProveedor=b.idProveedor');
			$albaranPrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
				array_push($albaranPrincipal,$result);
			}
			return $albaranPrincipal;
		
	}
	public function TodosAlbaranesLimite($limite){
		//@Objetivo:
		//MOstramos todos los datos principales de los albaranes de la tabla principal pero con un límite para la paginación
		$db=$this->db;
		$smt=$db->query('SELECT a.id , a.Numalbpro , a.Fecha , b.nombrecomercial, a.total, a.estado FROM `albprot` as a LEFT JOIN proveedores as b on a.idProveedor =b.idProveedor '.$limite);
		$pedidosPrincipal=array();
		while ( $result = $smt->fetch_assoc () ) {
			array_push($pedidosPrincipal,$result);
		}
		return $pedidosPrincipal;
	}
	
	public function sumarIva($numAlbaran){
		//@Objetivo:
		//Sumamos los importes iva y el total de la base de un número de albarán
		$from_where= 'from albproIva where  Numalbpro  ='.$numAlbaran;
		$albaran = parent::sumarIvaBases($from_where);
		
		return $albaran;
	}
	
	public function datosAlbaran($idAlbaran){
		//@Objetivo:
		//MOstramos los datos de un albarán buscando por ID
		$tabla='albprot';
		$where='id='.$idAlbaran;
		$albaran = parent::SelectUnResult($tabla, $where);
		return $albaran;
	}
	
	public function ProductosAlbaran($idAlbaran){
		//@Objetivo:
		//BUscamos los productos de un determinado id de albarán
		$tabla='albprolinea';
		$where='idalbpro= '.$idAlbaran;
		$albaran = parent::SelectVariosResult($tabla, $where);
		return $albaran;
	}
	
	public function IvasAlbaran($idAlbaran){
		//@Objetivo:
		//Mostramos los registros de iva de un determinado albarán
		$tabla='albproIva';
		$where='idalbpro= '.$idAlbaran;
		$albaran = parent::SelectVariosResult($tabla, $where);
		return $albaran;
	}
	
	public function PedidosAlbaranes($idAlbaran){
		//@Objetivo:
		//MUestra los pedidos de un número de albarán
		$tabla='pedproAlb';
		$where='idAlbaran= '.$idAlbaran;
		$albaran = parent::SelectVariosResult($tabla, $where);
		return $albaran;
	}
		public function albaranesProveedorGuardado($idProveedor, $estado){
		//@Objetivo:
		//Muestra los albaranes de un proveedor determinado con el estado indicado. Principalmente la utilizamos para saber los
		//albaranes de guardados de un proveedor para poder incluirlo en facturas
		$tabla='albprot';
		$where='idProveedor= '.$idProveedor.' and estado='."'".$estado."'";
		$albaran = parent::SelectVariosResult($tabla, $where);
		return $albaran;
	}
	
	public function buscarAlbaranProveedorGuardado($idProveedor, $numAlbaran, $estado){
		//@Objetivo:
		//Buscar datos principal de un albarán de proveedor y estado guardado
		$db=$this->db;
		if ($numAlbaran>0){
			$smt=$db->query('SELECT Numalbpro , Fecha , total, id , FechaVencimiento , formaPago FROM albprot WHERE idProveedor= '.$idProveedor.' and estado='."'".$estado."'".' and Numalbpro='.$numAlbaran);
			$albaranesPrincipal=array();
			if ($result = $smt->fetch_assoc () ){
				$albaran=$result;
			}
			$albaran['Nitem']=1;
		}else{
			$smt=$db->query('SELECT Numalbpro, Fecha, total, id , FechaVencimiento , formaPago  FROM albprot WHERE idProveedor= '.$idProveedor.'  and estado='."'".$estado."'");
			$albaranesPrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
				array_push($albaranesPrincipal,$result);	
			}
			$albaran['datos']=$albaranesPrincipal;
		}
		return $albaran;
	}
	
	public function modFechaNumero($id, $suNumero, $fecha){
		$db=$this->db;
		$smt=$db->query('UPDATE albprot set Su_numero='.$suNumero.' , Fecha="'.$fecha.'" where id='.$id);
	}
}
?>
