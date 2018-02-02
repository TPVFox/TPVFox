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

include_once("../../clases/cliente.php");
$Ccliente=new Cliente($BDTpv);

include_once("clases/facturasVentas.php");
$CFac=new FacturasVentas($BDTpv);

switch ($pulsado) {
    
		case 'buscarProductos':
			$busqueda = $_POST['valorCampo'];
			$campoAbuscar = $_POST['campo'];
			$id_input = $_POST['cajaInput'];
			$idcaja=$_POST['idcaja'];
			$res = BuscarProductos($id_input,$campoAbuscar, $idcaja, $busqueda,$BDTpv);
			if ($res['Nitems']===1){
				$respuesta=$res;
				$respuesta['Nitems']=$res['Nitems'];	
			}else{
				// Cambio estado para devolver que es listado.
				$respuesta['listado']= htmlProductos($res['datos'],$id_input,$campoAbuscar,$busqueda);
				$respuesta['Estado'] = 'Listado';
				$respuesta['datos']=$res['datos'];
			}
			echo json_encode($respuesta);  
		break;
		
		case 'añadirProductos';
			$datos=$_POST['productos'];
			$idTemporal=$_POST['idTemporal'];
			$productos_para_recalculo = json_decode( json_encode( $_POST['productos'] ));
			$CalculoTotales = recalculoTotales($productos_para_recalculo);
			$total=round($CalculoTotales['total'],2);
			$respuesta['total']=$total;
			if($idTemporal){
			$modProducto=$CcliPed->AddProducto($idTemporal,$datos , $total);
			}
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
			$product=$_POST['producto'];
			$num_item=$_POST['num_item'];
			$CONF_campoPeso=$_POST['CONF_campoPeso'];
			$disable="";
			$style="";
			$res 	= htmlLineaPedido($product,$num_item,$CONF_campoPeso, $disable, $style);
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
			
			if ($idcaja=="id_clienteAl" || $idcaja=="id_clienteFac"){
				$res=$Ccliente->DatosClientePorId($busqueda);
				if ($res){
					$respuesta['res']=$res;
					$respuesta['idCliente']=$res['idClientes'];
					$respuesta['nombre']=$res['Nombre'];
					$respuesta['Nitems']=1;
					$respuesta['formasVenci']=$res['fomasVenci'];
				}
				
			}else{
				$res = array( 'datos' => array());
				//funcion de buscar clientes
				//luego html mostrar modal 
				$res = BusquedaClientes($busqueda,$BDTpv,$tabla, $idcaja);
				$respuesta['items']=$res['Nitems'];
				if ($res['Nitems']===1){
					$respuesta['nombre']=$res['datos'][0]['nombre'];
					$respuesta['idCliente']=$res['datos'][0]['idClientes'];
				}else{
					$respuesta = htmlClientes($busqueda,$dedonde, $idcaja, $res['datos']);
				}
			}
			echo json_encode($respuesta);
		break;	
		
		case 'escribirCliente':
			// Cuando la busqueda viene a traves de  la ventana modal
			$id=$_POST['idcliente'];
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
			$product=$_POST['producto'];
			$num_item=$_POST['num_item'];
			$CONF_campoPeso=$_POST['CONF_campoPeso'];
			$res = htmlLineaTicket($product,$num_item,$CONF_campoPeso);
			$respuesta['html'] =$res;
			echo json_encode($respuesta);
		break;
		
		
		case 'buscarPedido':
			$busqueda=$_POST['busqueda'];
			$idCliente=$_POST['idCliente'];
			$res=$CcliPed->PedidosClienteGuardado($busqueda, $idCliente);
			if ($res['Nitem']==1){
				$temporales=$CcliPed->contarPedidosTemporal($res['id']);
				if ($temporales['numPedTemp']==0){
					$respuesta['temporales']=$temporales;
					$respuesta['datos']['Numpedcli']=$res['Numpedcli'];
					$respuesta['datos']['idPedCli']=$res['id'];
					$respuesta['datos']['fecha']=$res['FechaPedido'];
					$respuesta['datos']['total']=$res['total'];
					$respuesta['Nitems']=$res['Nitem'];
					$productosPedido=$CcliPed->ProductosPedidos($res['id']);
					$respuesta['productos']=$productosPedido;
				}
			}else{
				$respuesta=$res;
				$modal=modalPedidos($res['datos']);
				$respuesta['html']=$modal['html'];
				
			}
			echo json_encode($respuesta);
		break;
		case 'buscarAlbaran':
			$busqueda=$_POST['busqueda'];
			$idCliente=$_POST['idCliente'];
			$res=$CalbAl->AlbaranClienteGuardado($busqueda, $idCliente);
			if ($res['Nitem']==1){
					
					$respuesta['temporales']=1;
					$respuesta['datos']['Numalbcli']=$res['Numalbcli'];
					$respuesta['datos']['idalbcli']=$res['id'];
					$respuesta['datos']['fecha']=$res['Fecha'];
					$respuesta['datos']['total']=$res['total'];
					$respuesta['Nitems']=$res['Nitem'];
					$productosAlbaran=$CalbAl->ProductosAlbaran($res['id']);
					$respuesta['productos']=$productosAlbaran;
				
			}else{
				$respuesta=$res;
				$modal=modalAlbaranes($res['datos']);
				$respuesta['html']=$modal['html'];
				
			}
			echo json_encode($respuesta);
		break;
	
		case 'añadirAlbaranTemporal':
			$idAlbaranTemp=$_POST['idAlbaranTemp'];
			$idUsuario=$_POST['idUsuario'];
			$idTienda=$_POST['idTienda'];
			$estadoAlbaran=$_POST['estadoAlbaran'];
			$numAlbaran=$_POST['numAlbaran'];
			$fecha=$_POST['fecha'];
			$pedidos=$_POST['pedidos'];
			$productos=$_POST['productos'];
			$idCliente=$_POST['idCliente'];
			$existe=0;
			if ($numAlbaran>0){
				$albaran=$CalbAl->buscarTemporalNumReal($numAlbaran);
				$idAlbaranTemp=$albaran['id'];
			}
			if ($idAlbaranTemp>0){
				$rest=$CalbAl->modificarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoAlbaran, $fecha , $pedidos, $idAlbaranTemp, $productos);
				$existe=1;
				$respuesta['sql']=$rest['sql'];
				$res=$rest['idTemporal'];
				$pro=$rest['productos'];
			}else{
				$rest=$CalbAl->insertarDatosAlbaranTemporal($idUsuario, $idTienda, $estadoAlbaran, $fecha , $pedidos, $productos, $idCliente);
				$existe=0;
				$pro=$rest['productos'];
				$res=$rest['id'];
				$idAlbaranTemp=$res;
			}
			$respuesta['numalbaran']=$numAlbaran;
			if ($numAlbaran>0){
				$modId=$CalbAl->addNumRealTemporal($idAlbaranTemp, $numAlbaran);
				$respuesta['sqlmodnum']=$modId;
			}
			if ($productos){
				$productos_para_recalculo = json_decode( json_encode( $_POST['productos'] ));
				$respuesta['productosre']=$productos_para_recalculo;
				$CalculoTotales = recalculoTotalesAl($productos_para_recalculo);
				$total=round($CalculoTotales['total'],2);
				$respuesta['total']=$total;
				$nuevoArray = array(
							'desglose'=> $CalculoTotales['desglose'],
							'total' => $CalculoTotales['total']
								);
				$respuesta['totales']=$nuevoArray;
				$totalivas=0;
				foreach($nuevoArray['desglose'] as $nuevo){
					$totalivas=$totalivas+$nuevo['iva'];
				}
			
				$modTotal=$CalbAl->modTotales($res, $total, $totalivas);
				$respuesta['sqlmodtotal']=$modTotal['sql'];
				$respuesta['total']=$total;
			}
			$respuesta['id']=$res;
			$respuesta['existe']=$existe;
			$respuesta['productos']=$_POST['productos'];
			
			echo json_encode($respuesta);
		break;
		
		
		case 'añadirfacturaTemporal':
			$idFacturaTemp=$_POST['idFacturaTemp'];
			$idUsuario=$_POST['idUsuario'];
			$idTienda=$_POST['idTienda'];
			$estadoFactura=$_POST['estadoFactura'];
			$numFactura=$_POST['numFactura'];
			$fecha=$_POST['fecha'];
			$albaranes=$_POST['albaranes'];
			$productos=$_POST['productos'];
			$idCliente=$_POST['idCliente'];
			$existe=0;
			if ($numFactura>0){
				$factura=$CFac->buscarTemporalNumReal($numFactura);
				$idFacturaTemp=$factura['id'];
			}
			if ($idFacturaTemp>0){
				$rest=$CFac->modificarDatosFacturaTemporal($idUsuario, $idTienda, $estadoFactura, $fecha , $albaranes, $idFacturaTemp, $productos);
				$existe=1;
				$respuesta['sql']=$rest['sql'];
				$res=$rest['idTemporal'];
				$pro=$rest['productos'];
			}else{
				$rest=$CFac->insertarDatosFacturaTemporal($idUsuario, $idTienda, $estadoFactura, $fecha , $albaranes, $productos, $idCliente);
				$existe=0;
				$pro=$rest['productos'];
				$res=$rest['id'];
				$idFacturaTemp=$res;
			}
			$respuesta['numFactura']=$numFactura;
			if ($numFactura>0){
				$modId=$CFac->addNumRealTemporal($idFacturaTemp, $numFactura);
				$respuesta['sqlmodnum']=$modId;
			}
			if ($productos){
				$productos_para_recalculo = json_decode( json_encode( $_POST['productos'] ));
				$respuesta['productosre']=$productos_para_recalculo;
				$CalculoTotales = recalculoTotalesAl($productos_para_recalculo);
				$total=round($CalculoTotales['total'],2);
				$respuesta['total']=$total;
				$nuevoArray = array(
							'desglose'=> $CalculoTotales['desglose'],
							'total' => $CalculoTotales['total']
								);
				$respuesta['totales']=$nuevoArray;
				$totalivas=0;
				foreach($nuevoArray['desglose'] as $nuevo){
					$totalivas=$totalivas+$nuevo['iva'];
				}
			
				$modTotal=$CFac->modTotales($res, $total, $totalivas);
				$respuesta['sqlmodtotal']=$modTotal['sql'];
				$respuesta['total']=$total;
			}
			$respuesta['id']=$res;
			$respuesta['existe']=$existe;
			$respuesta['productos']=$_POST['productos'];
			
			echo json_encode($respuesta);
		break;
		
		
		
		
		
		
		
		
		
		case 'modificarEstadoPedido':
			if ($_POST['dedonde']=="Pedido"){
				$idPedido=$_POST['idPedido'];
				$idTemporal=$_POST['numPedidoTemp'];
				if ($idPedido>0 & $idTemporal>0){
					$estado="Pendiende";
					$modEstado=$CcliPed->ModificarEstadoPedido($idPedido, $estado);
					$respuesta['sql']=$modEstado;
				}
			}else if ($_POST['dedonde']=="Albaran"){
				$idPedido=$_POST['idPedido'];
				$estado="Facturado";
				$modEstado=$CcliPed->ModificarEstadoPedido($idPedido, $estado);
				$respuesta['sql']=$modEstado;
			}else if($_POST['dedonde']=="factura"){
				$idAlbaran=$_POST['idAlbaran'];
				$estado="Facturado";
				$modEstado=$CalbAl->ModificarEstadoAlbaran($idAlbaran, $estado);
				
			}
		
			echo json_encode($respuesta);
		break;
		
		case 'comprobarPedidos':
			$idCliente=$_POST['idCliente'];
			$estado="Guardado";
			if ($idCliente>0){
				$comprobar=$CcliPed->ComprobarPedidos($idCliente, $estado);
				if ($comprobar['ped']==1){
					$respuesta['ped']=1;
					$respuesta['sql']=$comprobar['sql'];
				}else{
					$respuesta['ped']=0;
				}
			}
			echo json_encode($respuesta);
		break;
		
		case 'comprobarAlbaran':
		$idCliente=$_POST['idCliente'];
			$estado="Guardado";
			if ($idCliente>0){
				$comprobar=$CalbAl->ComprobarAlbaranes($idCliente, $estado);
				if ($comprobar['alb']==1){
					$respuesta['alb']=1;
					$respuesta['sql']=$comprobar['sql'];
				}else{
					$respuesta['alb']=0;
					$respuesta['sql']=$comprobar['sql'];
				}
			}
			echo json_encode($respuesta);
		break;
		
		
		case 'htmlAgregarFilaPedido':
			$res=lineaPedidoAlbaran($_POST['datos']);
			$respuesta['html']=$res['html'];
			echo json_encode($respuesta);
		break;
		case 'htmlAgregarFilaAlbaran':
			$res=lineaAlbaranFactura($_POST['datos']);
			$respuesta['html']=$res['html'];
			echo json_encode($respuesta);
		break;
	 
		case 'htmlAgregarFilasProductos':
		$productos=$_POST['productos'];
		$dedonde=$_POST['dedonde'];
			 foreach($productos as $producto){
				if (!is_array($producto)){
					$bandera=1;
				}else{
				$res=htmlLineaPedidoAlbaran($producto, $dedonde);
				 $respuesta['html'].=$res['html'];
				}
		 }
		 if ($bandera==1){
			 $res=htmlLineaPedidoAlbaran($productos, $dedonde);
				 $respuesta['html'].=$res['html'];
		 }
		
			
	
		echo json_encode($respuesta);
		break;
		 
		case 'buscarDatosPedido':
			$idPedido=$_POST['idPedido'];
			$res=$CcliPed->datosPedidos($idPedido);
			$respuesta['NumPedido']=$res['Numpedcli'];
			echo json_encode($respuesta);
		break;
		
		case 'htmlFomasVenci':
			$formasVenci=$_POST['formasVenci'];
			if ($_POST['formasVenci']){
				$formaPago=json_decode($formasVenci, true);
				$forma=$formaPago['formapago'];
				$venci=$formaPago['vencimiento'];
			}else{
				$forma=0;
				$venci=0;
			}
			
			$for=htmlFormasVenci($forma, $BDTpv);
			$respuesta['html1']=$for['html'];
			$fun=fechaVencimiento($venci, $BDTpv);
			$ven=htmlVencimiento($fun, $BDTpv);
			
			$respuesta['html2']=$ven['html'];
			$respuesta['fecha']=$fun;
			$respuesta['formas']=$html['formas'];
			
			
			echo json_encode($respuesta);
		break;
		
		
		case 'ModificarFormasVencimiento':
		$opcion=$_POST['opcion'];
		$fechaVenci=$_POST['fechaVenci'];
		$idTemporal=$_POST['idFacTem'];
		$formasVenci=array();
		$formasVenci['forma']=$opcion;
		$formasVenci['fechaVencimiento']=$fechaVenci;
		
		$json=json_encode($formasVenci);
		
		if ($idTemporal>0){
			$modTemporal=$CFac->formasVencimientoTemporal($idTemporal, $json);
		}
		$respuesta=$json;
		echo json_encode($modTemporal);
		break;
		
		
}
