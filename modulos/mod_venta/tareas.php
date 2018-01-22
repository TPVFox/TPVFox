<?php
/* Fichero de tareas a realizar.
 * 
 * 
 * Con el switch al final y variable $pulsado
 * 
 *  */
/* ===============  REALIZAMOS CONEXIONES  ===============*/

$pulsado = $_POST['pulsado'];

include_once ("./../../configuracion.php");

// Crealizamos conexion a la BD Datos
include_once ("./../mod_conexion/conexionBaseDatos.php");

// Incluimos funciones
include_once ("./funciones.php");

include_once("clases/pedidosVentas.php");
$CcliPed=new PedidosVentas($BDTpv);

include_once("../../clases/producto.php");
$Cprod=new Producto($BDTpv);

include_once ("clases/albaranesVentas.php");
$CalbAl=new AlbaranesVentas($BDTpv);

switch ($pulsado) {
    
    case 'buscarProductos':
		$busqueda = $_POST['valorCampo'];
		$campoAbuscar = $_POST['campo'];
		$id_input = $_POST['cajaInput'];
		$idcaja=$_POST['idcaja'];
		$deDonde = $_POST['dedonde']; // Obtenemos de donde viene
		$idPedidoTemporal=$_POST['idTemporal'];
		$productos=$_POST['productos'];
		$res = BuscarProductos($id_input,$campoAbuscar, $idcaja, $busqueda,$BDTpv);
		if ($res['Nitems']===1){
			$respuesta=$res;
			$respuesta['Nitems']=$res['Nitems'];	
		}else{
			// Cambio estado para devolver que es listado.
			$respuesta['listado']= htmlProductos($res['datos'],$id_input,$campoAbuscar,$busqueda);
			$respuesta['Estado'] = 'Listado';
			$respuesta['sql']=$res['sql'];
			$respuesta['datos']=$res['datos'];
		
		}
		
		
		echo json_encode($respuesta);  
		break;
		
		case 'añadirProductos';
		$datos=$_POST['productos'];
		$idTemporal=$_POST['idTemporal'];
		//$respuesta['datos']=$datos;		
		
		$productos_para_recalculo = json_decode( json_encode( $_POST['productos'] ));
		$CalculoTotales = recalculoTotales($productos_para_recalculo);
		$total=round($CalculoTotales['total'],2);
		
		$respuesta['total']=$total;
		$modProducto=$CcliPed->AddProducto($idTemporal,$datos , $total);
		
		$nuevoArray = array(
						'desglose'=> $CalculoTotales['desglose'],
						'total' => $CalculoTotales['total']
							);
		$respuesta['totales']=$nuevoArray;
		
		echo json_encode($respuesta);

		return $respuesta;
		break;
		
		case 'HtmlLineaTicket';
		$respuesta = array();
		$product 					=$_POST['producto'];
		$num_item					=$_POST['num_item'];
		$CONF_campoPeso		=$_POST['CONF_campoPeso'];
		$res 	= htmlLineaPedido($product,$num_item,$CONF_campoPeso);
		$respuesta['html'] =$res;
		$respuesta['producto']=$product;
		echo json_encode($respuesta);
		break;
	    case 'buscarClientes':
		// Abrimos modal de clientes
		$busqueda = $_POST['busqueda'];
		$dedonde = $_POST['dedonde'];
		$idcaja=$_POST['idcaja'];
		$tabla='clientes';
		$numPedidoTemp=$_POST['numPedidoTemp'];
		$idTienda=$_POST['idTienda'];
		$idUsuario=$_POST['idUsuario'];
		$estadoPedido=$_POST['estadoPedido'];
		$idPedido=$_POST['idPedido'];		
		$res = array( 'datos' => array());
		//funcion de buscar clientes
		//luego html mostrar modal 
		$res = BusquedaClientes($busqueda,$BDTpv,$tabla, $idcaja);
		$respuesta['items']=$res['Nitems'];
		if ($res['Nitems']===1 & $idPedido==0){
			if ($numPedidoTemp>0){
				//Si el número de busquedas es uno quiere decir que la busqueda fue por id
			$modCliente=$CcliPed->ModClienteTemp($busqueda, $numPedidoTemp, $idTienda, $idUsuario, $estadoPedido);
			$respuesta['sql']=$modCliente;
			$respuesta['busqueda']=$busqueda;
			$respuesta['numPedidoTemp']=$numPedidoTemp;
			$respuesta['idPedido']=$idPedido;
			}else{
			$addCliente=$CcliPed->AddClienteTemp($busqueda, $idTienda, $idUsuario, $estadoPedido);
			$respuesta['numPedidoTemp']=$addCliente['id'];
			$respuesta['sql']=$sql;
			$respuesta['idPedido']=$idPedido;
			}
			$respuesta['nombre']=$res['datos'][0]['nombre'];
		}elseif($res['Nitems']>1 & $idPedido===0){
			$respuesta = htmlClientes($busqueda,$dedonde, $idcaja, $res['datos']);
		}else if($res['Nitems']===1 & $idPedido>0){
		if ($numPedidoTemp>0){
			$modCliente=$CcliPed->ModClienteTemp($busqueda, $numPedidoTemp, $idTienda, $idUsuario, $estadoPedido);
			$respuesta['busqueda']=$busqueda;
			$respuesta['numPedidoTemp']=$numPedidoTemp;
			$respuesta['idPedido']=$idPedido;
			}else{
			$addCliente=$CcliPed->AddClienteTempPedidoGuardado($busqueda, $idTienda, $idUsuario, $estadoPedido, $idPedido);
			$respuesta['numPedidoTemp']=$addCliente['id'];
			$respuesta['sql']=$addCliente['sql'];
			$respuesta['idPedido']=$idPedido;
			}
			$respuesta['nombre']=$res['datos'][0]['nombre'];
		}else{
			$respuesta = htmlClientes($busqueda,$dedonde, $idcaja, $res['datos']);
		
	}
	
		//~ echo $respuesta;
		echo json_encode($respuesta);
		break;
		
		
		
		case 'escribirCliente':
		// Cuando la busqueda viene a traves de  la ventana modal
		$id=$_POST['idcliente'];
		$tabla='clientes';
		$numPedidoTemp=$_POST['numPedidoTemp'];
		$idTienda=$_POST['idTienda'];
		$idUsuario=$_POST['idUsuario'];
		$estadoPedido=$_POST['estadoPedido'];
		$idPedido=$_POST['idPedido'];
		if ($numPedidoTemp>0){
			$modCliente=$CcliPed->ModClienteTemp($id, $numPedidoTemp, $idTienda, $idUsuario, $estadoPedido);
			$respuesta['sql']=$modCliente;
			$respuesta['busqueda']=$id;
			$respuesta['numPedidoTemp']=$numPedidoTemp;
		}else{
			$addCliente=$CcliPed->AddClienteTemp($id, $idTienda, $idUsuario, $estadoPedido);
			$respuesta['numPedidoTemp']=$addCliente['id'];
			$numPedidoTemp=$addCliente['id'];
		}
		if ($idPedido>0){
			$modIdPedido=$CcliPed->ModNumPedidoTtemporal($numPedidoTemp, $idPedido);
			$respuesta['sqlMod']=$modIdPedido;
		}
		echo json_encode($respuesta);
		break;
		
		
		
		
		case 'HtmlLineaLinea':
		$respuesta = array();
		$product 					=$_POST['producto'];
		$num_item					=$_POST['num_item'];
		$CONF_campoPeso		=$_POST['CONF_campoPeso'];
		$res 	= htmlLineaTicket($product,$num_item,$CONF_campoPeso);
		$respuesta['html'] =$res;
		echo json_encode($respuesta);
		break;
		
		
	case 'buscarPedido':
		$busqueda=$_POST['busqueda'];
		$dedonde=$_POST['dedonde'];
		$idCaja=$_POST['idCaja'];
		$idAlbaranTemp=$_POST['idAlbaranTemp'];
		$idUsuario=$_POST['idUsuario'];
		$idTienda=$_POST['idTienda'];
		$estadoAlbaran=$_POST['estadoAlbaran'];
		$idAlbaran=$_POST['idAlbaran'];
		$numAlbaran=$_POST['numAlbaran'];
		$fecha=$_POST['fecha'];
		$res=$CcliPed->buscarNumPedidoId($busqueda);
		if ($res){
			
			$respuesta['datos']['Numpedcli']=$res['Numpedcli'];
			$respuesta['datos']['idPedCli']=$res['id'];
			$respuesta['Nitems']=$res['Nitems'];
			
			$productosPedido=$CcliPed->ProductosPedidos($res['id']);
			$respuesta['productos']=$productosPedido;
			
		}
		echo json_encode($respuesta);
		break;
		
	break;
	
	case 'añadirAlbaranTemporal':
		$idAlbaranTemp=$_POST['idAlbaranTemp'];
		$idUsuario=$_POST['idUsuario'];
		$idTienda=$_POST['idTienda'];
		$estadoAlbaran=$_POST['estadoAlbaran'];
		$idAlbaran=$_POST['idAlbaran'];
		$numAlbaran=$_POST['numAlbaran'];
		$fecha=$_POST['fecha'];
		$pedidos=$_POST['pedidos'];
		$productos=$_POST['productos'];
		if ($idAlbaranTemp>0){
			$res=$CalbAl->modificarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoAlbaran, $fecha , $pedidos, $idAlbaranTemp, $productos);
		}else{
			$res=$CalbAl->insertarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoAlbaran, $fecha , $pedidos, $productos);
		}
		if ($numAlbaran===0){
			$modId=$CalbAl->addNumRealTemporal($idAlbaranTemp, $numAlbaran);
		}
		$respuesta['id']=$res;
		if ($pedidos){
			//$respuesta['html']->
		}
		
		echo json_encode($respuesta);
		break;
		
	case 'buscarClienteAl':
		$busqueda=$_POST['busqueda'];
		$dedonde=$_POST['dedonde'];
		$idCaja=$_POST['idcaja'];
		$idAlbaranTemp=$_POST['idAlbaranTemp'];
		$idUsuario=$_POST['idUsuario'];
		$idTienda=$_POST['idTienda'];
		$estadoAlbaran=$_POST['estadoAlbaran'];
		$idAlbaran=$_POST['idAlbaran'];
		$numAlbaran=$_POST['numAlbaran'];
		$fecha=$_POST['fecha'];
		$tabla='clientes';
		$res = array( 'datos' => array());
		$res = BusquedaClientes($busqueda,$BDTpv,$tabla, $idCaja);
		$respuesta['items']=$res;
		$respuesta['idCliente']=$res['datos'][0]['idClientes'];
		$idCliente=(integer)$respuesta['idCliente'];
		if ($res['Nitems']===1 & $idAlbaranTemp===0){
			$addTemp=$CalbAl->insertarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoAlbaran, $fecha, $idCliente);
			$respuesta['nombre']=$res['datos'][0]['nombre'];
			$respuesta['idCliente']=$res['datos'][0]['idClientes'];
			$respuesta['idTemporal']=$addTemp['id'];
			$respuesta['sql']=$addTemp['sql'];
			$respuesta['en']=$addTemp['en'];
		}
		echo json_encode($respuesta);
		break;
	break;
		
}
