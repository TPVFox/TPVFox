<?php 

include_once ('./clases/ClaseCompras.php');

class PedidosCompras extends ClaseCompras{
	private $num_rows; // (array) El numero registros qure tiene la tabal pedprot
	
	public function __construct($conexion){
		parent::__construct($conexion);
		// Obtenemos el numero registros.
		$sql = 'SELECT count(*) as num_reg FROM pedprot';
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
	
	
	public function consulta($sql){
		// Realizamos la consulta.
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
	
	
	public function modificarDatosPedidoTemporal($idUsuario, $idTienda, $estadoPedido, $fecha ,  $numPedidoTemp, $productos){
		// @ Objetivo:
		//Modificar los datos de pedidos temporal cada vez que hacemos agregamos un producto, modificamos una candidad ...
		// @ Parametros:
		//Todos los datos del pedido temporal
		$db = $this->db;
		$productos_json=json_encode($productos);
		$UnicoCampoProductos 	=$productos_json;
		$PrepProductos = $db->real_escape_string($UnicoCampoProductos);
		$sql='UPDATE pedprotemporales SET idUsuario='.$idUsuario.' , idTienda='.$idTienda
		.' , estadoPedPro="'.$estadoPedido.'" , fechaInicio="'.$fecha.'"  ,Productos="'
		.$PrepProductos.'"  WHERE id='.$numPedidoTemp;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}
	}
	public function insertarDatosPedidoTemporal($idUsuario, $idTienda, $estadoPedido, $fecha ,  $productos, $idProveedor){
		//@Objetivo:
		// Insertar un pedido temporal , cuando el pedido temporal no exste lo insertamos
		//@ Parametros:
		// Todos los parametros que tenemos incialmente cuando creamos el pedido temporal
		$db = $this->db;
		$productos_json=json_encode($productos);
		$UnicoCampoProductos 	=$productos_json;
		$PrepProductos = $db->real_escape_string($UnicoCampoProductos);
		$sql = 'INSERT INTO pedprotemporales ( idUsuario , idTienda , estadoPedPro , 
		fechaInicio, idProveedor,  Productos ) VALUES ('.$idUsuario.' , '.$idTienda.' , "'
		.$estadoPedido.'" , "'.$fecha.'", '.$idProveedor.' , "'.$PrepProductos.'")';
		//~ $smt = $db->query ($sql);
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
		}else{
			$id=$db->insert_id;
			$respuesta['id']=$id;
			$respuesta['sql']=$sql;
			$respuesta['productos']=$productos;
		}
		return $respuesta;
	}
	public function modTotales($res, $total, $totalivas){
		//@ Objetivo:
		// El objetico principal es que cada vez que modificamos una cantidad o añadimos un producto nuevo , modificar en el pedido temporal los datos de total
		//@ Parametros:
		// $res-> id del pedido temporal
		//$total->El total del pedido
		//$total_ivas->la suma de todos los ivas 
		$db=$this->db;
		$sql='UPDATE pedprotemporales set total='.$total .' , total_ivas='.$totalivas .' where id='.$res;
		$smt=$db->query($sql);
		$resultado['sql']=$sql;
		return $resultado;
	}
	public function addNumRealTemporal($idTemporal, $idReal){
		// @Objetivo: Si el pedido es modificado en el temporal tenemos que registrar el id del pedido real 
		// @Parametros:
		// $idTemporal-> id del pedido temporal que hemos creado anteriormente
		// $idReal-> id del pedido real que estamos modificando
		$db=$this->db;
		$sql='UPDATE pedprotemporales set idPedpro='.$idReal .'  where id='.$idTemporal;
		$smt=$db->query($sql);
		//~ return $resultado;
	}
	public function modEstadoPedido($idPedido, $estado){
		//@Objetivo: Mofificar el estado del pedido real 
		// @estado :
			//-Facturado: que ese pedido ya está en el albarán
			//-Guardado: que el pedido no tiene ninguna modificación pendiente
			//- Sin guardar : que el pedido tiene un pedido temporal
		//@Parametros : 
		// $idPedido-> id del pedio real
		// $estado-> string del estado
		$db=$this->db;
		$sql='UPDATE pedprot set estado="'.$estado .'"  where id='.$idPedido;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}
	}
	public function DatosTemporal($idTemporal){
		// @ Objetivo:
		// Obtener todos los datos de temporal
		// @ Parametros:
		// $idTemporal -> (string) Numero de idTemporal
		$tabla='pedprotemporales';
		$where='id='.$idTemporal;
		$pedido = parent::SelectUnResult($tabla, $where);
		return $pedido;
	}
	public function DatosPedido($idPedido){
		//@Objetivo : Mostrar todo los datos de un pedido de la tabla pedprot
		//@Parametros:
		//idPedido: id del pedido
		$tabla='pedprot';
		$where='id='.$idPedido;
		$pedido = parent::SelectUnResult($tabla, $where);
		return $pedido;
	}
	public function eliminarPedidoTablas($idPedido){
		//@Objetivo: Eliminar todo los datos de un id de pedido completo
		//@Parametros:
			//idPedido->id del pedido real
		$sql=array();
		$respuesta=array();
		$db=$this->db;
		$sql[1]='DELETE FROM pedprot where id='.$idPedido ;
		$sql[2]='DELETE FROM pedprolinea where idpedpro ='.$idPedido;
		$sql[3]='DELETE FROM pedproIva where idpedpro ='.$idPedido;
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
	
	public function AddPedidoGuardado($datos, $idPedido){
		//@Objetivo: GUardar todos los datos de un pedido real nuevo , los datos se guardan en tres tablas 
		//@tablas:
		//pedprot->tabla donde se almacenan los pedidos guardados
		//pedprolinea->tabla que contiene las lineas de los productos
		//pedproIva->tabla que contiene los registros de los distintos ivas de los productos
		$db = $this->db;
		if ($idPedido>0){
			$sql='INSERT INTO pedprot (id, Numpedpro, Numtemp_pedpro, FechaPedido, idTienda, idUsuario, 
			idProveedor, estado, total, fechaCreacion) VALUES ('.$idPedido.' , '
			.$idPedido.', '.$datos['Numtemp_pedpro'].', "'.$datos['FechaPedido']
			.'", '.$datos['idTienda'].' , '.$datos['idUsuario'].', '.$datos['idProveedor']
			.', "'.$datos['estado'].'", '.$datos['total'].', "'.$datos['fechaCreacion'].'")';
			
			$smt=$this->consulta($sql);
			if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				
			}else{
				$id=$idPedido;
				$respuesta['id']=$id;
			}
		}else{
			$sql='INSERT INTO pedprot ( Numtemp_pedpro, FechaPedido, idTienda, idUsuario, idProveedor, 
			estado, total, fechaCreacion) VALUES ('.$datos['Numtemp_pedpro']
			.', "'.$datos['FechaPedido'].'", '.$datos['idTienda'].' , '
			.$datos['idUsuario'].', '.$datos['idProveedor'].', "'.$datos['estado']
			.'", '.$datos['total'].', "'.$datos['fechaCreacion'].'")';
			$smt=$this->consulta($sql);
			if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				
			}else{
				$id=$db->insert_id;
				if (isset($id)){
					$respuesta['id']=$id;
					$sql='UPDATE pedprot set Numpedpro='.$id.' WHERE id='.$id;
					$smt=$this->consulta($sql);
					if (gettype($smt)==='array'){
						$respuesta['error']=$smt['error'];
						$respuesta['consulta']=$smt['consulta'];
					}
				}else{
					$respuesta['error']=$smt['error'];
					$respuesta['consulta']=$smt['consulta'];
				}
			}
		}
		if (!isset($respuesta['error'])){
		$productos = json_decode($datos['Productos'], true); 
		$i=1;
		$numPedido=$id;
		foreach ( $productos as $prod){
			if ($prod['estado']=='Activo'){
				$codBarras="";
				$refProveedor="";
				if ($prod['ccodbar']){
					$codBarras=$prod['ccodbar'];
				}
				if ($prod['crefProveedor']){
					$refProveedor=$prod['crefProveedor'];
				}
				$sql='INSERT INTO  pedprolinea (idpedpro, Numpedpro, idArticulo, cref, ref_prov , 
				ccodbar, cdetalle, ncant, nunidades, costeSiva, iva, nfila, estadoLinea) values ('
				.$id.', '.$numPedido.', '.$prod['idArticulo'].', '."'".$prod['cref']."'".', '."'"
				.$refProveedor."'".', "'.$codBarras.'", "'.$prod['cdetalle'].'", '.$prod['ncant']
				.', "'.$prod['nunidades'].'", "'.$prod['ultimoCoste'].'", '.$prod['iva'].', '.$i.', "'
				.$prod['estado'].'")';
				
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
			$sql='INSERT INTO pedproIva (idpedpro, Numpedpro, iva, importeIva, totalbase) 
			values ('.$id.', '.$numPedido.', '.$iva.', '.$basesYivas['iva'].', '
			.$basesYivas['base'].')';
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

	public function eliminarTemporal($idTemporal, $idPedido){
		//@Objetivo : eliminar el registro temporal a la hora de guardar un pedido real
		
		$db=$this->db;
		if ($idPedido>0){
			$sql='DELETE FROM pedprotemporales WHERE idPedpro='.$idPedido;
		}else{
			$sql='DELETE FROM pedprotemporales WHERE id='.$idTemporal;
		}
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
				$respuesta['error']=$smt['error'];
				$respuesta['consulta']=$smt['consulta'];
				return $respuesta;
		}
		
	}
	public function TodosTemporal(){
		//Muestra todos los temporales, esta función la utilizamos en el listado de pedidos
		$db = $this->db;
		$Sql= 'SELECT tem.idPedpro, tem.id , tem.idProveedor, tem.total, b.nombrecomercial, 
		c.Numpedpro from pedprotemporales as tem left JOIN proveedores as b on 
		tem.idProveedor=b.idProveedor left JOIN pedprot as c on tem.idPedpro=c.id';
		$smt=$this->consulta($Sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}else{
			$pedidosPrincipal=array();
			while ( $result = $smt->fetch_assoc () ) {
				array_push($pedidosPrincipal,$result);
			}
			return $pedidosPrincipal;
		}
		
	}
	
	
	public function TodosPedidosLimite($limite = ''){
		//MUestra todos los pedidos dependiendo del límite que tengamos en listado pedidos
		$db	=$this->db;
		$sql = 'SELECT a.id , a.Numpedpro , a.FechaPedido, b.nombrecomercial, 
		a.total, a.estado FROM `pedprot` as a LEFT JOIN proveedores as b on 
		a.idProveedor=b.idProveedor   '. $limite ;
		$smt=$this->consulta($sql);
		$pedidosPrincipal=array();
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
		}else{
			while ( $result = $smt->fetch_assoc () ) {
				array_push($pedidosPrincipal,$result);
			}
				$respuesta = array();
			$respuesta['Items'] = $pedidosPrincipal;
			$respuesta['consulta'] = $sql;
			$respuesta['limite']=$limite;
			
		}
		return $respuesta;
	}
	
	public function sumarIva($numPedido){
		//Función para sumar los ivas de un pedido
		$from_where= 'from pedproIva where Numpedpro ='.$numPedido;
		$pedido = parent::sumarIvaBases($from_where);
		return $pedido;
	}
	
	public function ProductosPedidos($idPedido){
        //@Objetivo:
        //Buscar todos los productos que tenga un id de pedido real
        //@Parametros :
        //idPedido-> id del pedido real
		$tabla='pedprolinea';
		$where='idpedpro= '.$idPedido;
		$pedido = parent::SelectVariosResult($tabla, $where);
		return $pedido;
	}
	//Muestra los ivas de un pedido
	public function IvasPedidos($idPedido){
		//@Objetivo:
		//Extraer todos los ivas que tengamos de un pedido ya guardado
		//@Parametros: 
		//idPedido->id del pedido guardado
		$tabla='pedproIva';
		$where='idpedpro= '.$idPedido;
		$pedido = parent::SelectVariosResult($tabla, $where);
		return $pedido;
	}
	
	public function pedidosProveedorGuardado($idProveedor, $estado){
		//@Objetivo:
		//Mostrar datos de los pedidos de un proveedor según el estado para mostrar en albaranes
		//@parametros:
		//idProveedor: id del proveedor
		//estado: estado del que queremos buscar los datos 
		$tabla='pedprot';
		$where='idProveedor= '.$idProveedor.' and estado='."'".$estado."'";
		$pedido = parent::SelectVariosResult($tabla, $where);
		return $pedido;
	}
	
	public function buscarPedidoProveedorGuardado($idProveedor, $numPedido, $estado){
		
		$db=$this->db;
		if ($numPedido>0){
			$sql='SELECT Numpedpro, FechaPedido, total, id FROM pedprot 
			WHERE idProveedor= '.$idProveedor.' and estado='."'".$estado."'"
			.' and Numpedpro='.$numPedido;
			$smt=$this->consulta($sql);
			if (gettype($smt)==='array'){
				$pedido['error']=$smt['error'];
				$pedido['consulta']=$smt['consulta'];
			}else{
				$pedidosPrincipal=array();
				if ($result = $smt->fetch_assoc () ){
					$pedido=$result;
				}
				$pedido['Nitem']=1; // No lo entiendo , y si la consulta obtiene mas.
			}
		}else{
			$sql='SELECT Numpedpro, FechaPedido, total, id FROM pedprot
			 WHERE idProveedor= '.$idProveedor.'  and estado='."'".$estado."'";
			$smt=$this->consulta($sql);
			if (gettype($smt)==='array'){
				$pedido['error']=$smt['error'];
				$pedido['consulta']=$smt['consulta'];
			}else{
				$pedidosPrincipal=array();
				while ( $result = $smt->fetch_assoc () ) {
					array_push($pedidosPrincipal,$result);	
				}
				$pedido['datos']=$pedidosPrincipal;
			}
		}
		
		
		return $pedido;
	}
	public function modFechaPedido($fecha, $idPedido){
		$db=$this->db;
		$sql='UPDATE pedprot SET FechaPedido= "'.$fecha.'" where id='.$idPedido;
		$smt=$this->consulta($sql);
		if (gettype($smt)==='array'){
			$respuesta['error']=$smt['error'];
			$respuesta['consulta']=$smt['consulta'];
			return $respuesta;
		}
		
	}
	
	
}

?>
