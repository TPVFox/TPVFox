<?php 

/* Fichero de tareas a realizar.
 * 
 * 
 * Con el switch al final y variable $pulsado
 * 
 *  */
 $pulsado = $_POST['pulsado'];
 include_once ("./../../configuracion.php");
 // Crealizamos conexion a la BD Datos
include_once ("./../mod_conexion/conexionBaseDatos.php");
// Incluimos funciones
include_once ("./funciones.php");
include_once '../../clases/Proveedores.php';
$CProveedores=new Proveedores($BDTpv);
include_once "../../clases/articulos.php";
$CArticulos=new Articulos($BDTpv);
include_once "clases/pedidosCompras.php";
$CPed=new PedidosCompras($BDTpv);
include_once "clases/albaranesCompras.php";
$CAlb=new AlbaranesCompras($BDTpv);
include_once "clases/facturasCompras.php";
$CFac= new FacturasCompras($BDTpv);
switch ($pulsado) {
	case 'buscarProveedor':
		$busqueda=$_POST['busqueda'];
		$dedonde=$_POST['dedonde'];
		$idCaja=$_POST['idcaja'];
		if ($idCaja=="id_proveedor"){
			$buscarId=$CProveedores->buscarProveedorId($busqueda);
			$respuesta['id']=$buscarId['idProveedor'];
			$respuesta['nombre']=$buscarId['nombrecomercial'];
			$respuesta['Nitems']=1;
		}else{
			$buscarTodo=$CProveedores->buscarProveedorNombre($busqueda);
			$respuesta['html']=htmlProveedores($busqueda,$dedonde, $idCaja, $buscarTodo['datos']);
			$respuesta['datos']=$buscarTodo['datos'];
			
		}
		echo json_encode($respuesta);
	break;
	case 'buscarProductos':
			$busqueda = $_POST['valorCampo'];
			$campoAbuscar = $_POST['campo'];
			$id_input = $_POST['cajaInput'];
			$idcaja=$_POST['idcaja'];
			$idProveedor=$_POST['idProveedor'];
			$dedonde=$_POST['dedonde'];
			$res = BuscarProductos($id_input,$campoAbuscar, $idcaja, $busqueda,$BDTpv, $idProveedor);
			$respuesta['sql']=$res['sql'];
			if ($res['Nitems']===1){
				$respuesta=$res;
				$idArticulo=$res['datos'][0]['idArticulo'];
				
				$respuesta['Nitems']=$res['Nitems'];	
			}else{
				// Cambio estado para devolver que es listado.
				$respuesta['listado']= htmlProductos($res['datos'],$id_input,$campoAbuscar,$busqueda, $dedonde);
				$respuesta['Estado'] = 'Listado';
				$respuesta['datos']=$res['datos'];
			}
			echo json_encode($respuesta);  
	break;	
	case 'addPedidoTemporal';
		$numPedidoTemp=$_POST['numPedidoTemp'];
		$idUsuario=$_POST['idUsuario'];
		$idTienda=$_POST['idTienda'];
		$estadoPedido=$_POST['estadoPedido'];
		$idPedido=$_POST['idPedido'];
		$numPedido=$_POST['numPedido'];
		$fecha=$_POST['fecha'];
		$productos=$_POST['productos'];
		$idProveedor=$_POST['idProveedor'];
		$existe=0;
		if ($numPedidoTemp>0){
				$rest=$CPed->modificarDatosPedidoTemporal($idUsuario, $idTienda, $estadoPedido, $fecha ,  $numPedidoTemp, $productos);
				$existe=1;
				$respuesta['sql']=$rest['sql'];
				$res=$rest['idTemporal'];
				$pro=$rest['productos'];
		}else{
				$rest=$CPed->insertarDatosPedidoTemporal($idUsuario, $idTienda, $estadoPedido, $fecha ,  $productos, $idProveedor);
				$existe=0;
				$pro=$rest['productos'];
				$res=$rest['id'];
				$numPedidoTemp=$res;
		}
		$respuesta['numPedido']=$numPedido;
		 if ($idPedido>0){
			 $modId=$CPed->addNumRealTemporal($numPedidoTemp, $idPedido);
			 $estado="Sin Guardar";
			 $modEstado=$CPed->modEstadoPedido($idPedido, $estado);
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
			
				$modTotal=$CPed->modTotales($res, $total, $totalivas);
				$respuesta['sqlmodtotal']=$modTotal['sql'];
				$respuesta['total']=$total;
			}
			$respuesta['id']=$res;
			$respuesta['existe']=$existe;
			$respuesta['productos']=$_POST['productos'];
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
				 $respuesta['html']=$res['html'];
		 }
		echo json_encode($respuesta);
		break;
		case 'addProveedorArticulo':
			$fechaActualizacion=date('Y-m-d');
			$estado="activo";
			$datos=array(
				'idArticulo'=>$_POST['idArticulo'],
				'refProveedor'=>$_POST['refProveedor'],
				'idProveedor'=>$_POST['idProveedor'],
				'coste'=>$_POST['coste'],
				'fecha'=>$fechaActualizacion,
				'estado'=>$estado
			);
			$datosArticulo=$CArticulos->buscarReferencia($_POST['idArticulo'], $_POST['idProveedor']);
			if ($datosArticulo){
				$modArt=$CArticulos->modificarProveedorArticulo($datos);
			}else{
				$addNuevo=$CArticulos->addArticulosProveedores($datos);
			}
			
		$respuesta['sql']=$addNuevo;
		
		
		echo json_encode($respuesta);
		
		break;
		case 'buscarReferencia':
				$idArticulo=$_POST['idArticulo'];
				$idProveedor=$_POST['idProveedor'];
				$coste=$_POST['coste'];
				$fila=$_POST['fila'];
				$datosArticulo=$CArticulos->buscarReferencia($idArticulo, $idProveedor);
				$articulo=$CArticulos->buscarNombreArticulo($idArticulo);
				
				$html=htmlCambioRefProveedor($datosArticulo, $fila, $articulo, $coste);
				$respuesta['html']=$html['html'];
				
				echo json_encode($respuesta);
		
		break;
		case 'comprobarPedido':
			$estado="Guardado";
			$idProveedor=$_POST['idProveedor'];
			$buscar=$CPed->pedidosProveedorGuardado($idProveedor, $estado);
			if (count($buscar)>0){
				$bandera=1;
			}else{
				$bandera=2;
			}
			
			echo json_encode($bandera);
		break;
		case 'comprobarAlbaranes':
			$estado="Guardado";
			$idProveedor=$_POST['idProveedor'];
			$buscar=$CAlb->albaranesProveedorGuardado($idProveedor, $estado);
			if (count($buscar)>0){
				$bandera=1;
			}else{
				$bandera=2;
			}
			
			echo json_encode($bandera);
		break;
		case 'BuscarPedido':
		$numPedido=$_POST['numPedido'];
		$idProveedor=$_POST['idProveedor'];
		$estado="Guardado";
		$datosPedido=$CPed->buscarPedidoProveedorGuardado($idProveedor, $numPedido, $estado);
		if ($datosPedido['Nitem']==1){
			$respuesta['temporales']=1;
			$respuesta['datos']['Numpedpro']=$datosPedido['Numpedpro'];
			$respuesta['datos']['idPedido']=$datosPedido['id'];
			$respuesta['datos']['fecha']=$datosPedido['FechaPedido'];
			$respuesta['datos']['total']=$datosPedido['total'];
			$respuesta['Nitems']=$datosPedido['Nitem'];
			$productosPedido=$CPed->ProductosPedidos($datosPedido['id']);
			$respuesta['productos']=$productosPedido;
		}else{
			$respuesta=$datosPedido;
			$modal=modalPedidos($datosPedido['datos']);
			$respuesta['html']=$modal['html'];
		}
		echo json_encode($respuesta);
		break;
		case 'BuscarAlbaran':
		$numAlbaran=$_POST['numAlbaran'];
		$idProveedor=$_POST['idProveedor'];
		$estado="Guardado";
		$datosAlbaran=$CAlb->buscarAlbaranProveedorGuardado($idProveedor, $numAlbaran, $estado);
		
		if ($datosAlbaran['Nitem']==1){
			$respuesta['temporales']=1;
			$respuesta['datos']['Numalbpro']=$datosAlbaran['Numalbpro'];
			$respuesta['datos']['idAlbaran']=$datosAlbaran['id'];
			$date = new DateTime($datosAlbaran['Fecha']);
			$respuesta['datos']['fecha']=date_format($date, 'Y-m-d');
			$respuesta['datos']['total']=$datosAlbaran['total'];
			$respuesta['Nitems']=$datosAlbaran['Nitem'];
			$productosAlbaran=$CAlb->ProductosAlbaran($datosAlbaran['id']);
			$respuesta['productos']=$productosAlbaran;
		}else{
			$respuesta['datos']=$datosAlbaran;
			$modal=modalAlbaranes($datosAlbaran['datos']);
			$respuesta['html']=$modal['html'];
		}
		echo json_encode($respuesta);
		break;
		case 'addAlbaranTemporal':
			$idAlbaranTemporal=$_POST['idAlbaranTemp'];
			$idUsuario=$_POST['idUsuario'];
			$idTienda=$_POST['idTienda'];
			$estado=$_POST['estado'];
			$idAlbaran=$_POST['idAlbaran'];
			$numAlbaran=$_POST['numAlbaran'];
			$fecha=$_POST['fecha'];
			$productos=$_POST['productos'];
			$pedidos=$_POST['pedidos'];
			$idProveedor=$_POST['idProveedor'];
			$suNumero=$_POST['suNumero'];
			$existe=0;
			if ($idAlbaranTemporal>0){
				$rest=$CAlb->modificarDatosAlbaranTemporal($idUsuario, $idTienda, $estado, $fecha ,  $idAlbaranTemporal, $productos, $pedidos, $suNumero);
				$existe=1;
				$respuesta['sql']=$rest['sql'];
				$res=$rest['idTemporal'];
				$pro=$rest['productos'];
			}else{
				$rest=$CAlb->insertarDatosAlbaranTemporal($idUsuario, $idTienda, $estado, $fecha ,  $productos, $idProveedor, $pedidos, $suNumero);
				$existe=0;
				$pro=$rest['productos'];
				$res=$rest['id'];
				$idAlbaranTemporal=$res;
			}
			if ($idAlbaran>0){
				$modId=$CAlb->addNumRealTemporal($idAlbaranTemporal, $numAlbaran);
				$estado="Sin Guardar";
				$modEstado=$CAlb->modEstadoAlbaran($idAlbaran, $estado);
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
				$modTotal=$CAlb->modTotales($res, $total, $totalivas);
				$respuesta['sqlmodtotal']=$modTotal['sql'];
				$respuesta['total']=$total;
			}
			$respuesta['id']=$res;
			$respuesta['existe']=$existe;
			$respuesta['productos']=$_POST['productos'];
		echo json_encode($respuesta);
		break;
		
		
		case 'addFacturaTemporal':
			$idFacturaTemp=$_POST['idFacturaTemp'];
			$idUsuario=$_POST['idUsuario'];
			$idTienda=$_POST['idTienda'];
			$estado=$_POST['estado'];
			$idFactura=$_POST['idFactura'];
			$numFactura=$_POST['numFactura'];
			$fecha=$_POST['fecha'];
			$productos=$_POST['productos'];
			$albaranes=$_POST['albaranes'];
			$idProveedor=$_POST['idProveedor'];
			$suNumero=$_POST['suNumero'];
			if ($idFacturaTemp>0){
				$rest=$CFac->modificarDatosFacturaTemporal($idUsuario, $idTienda, $estado, $fecha ,  $idFacturaTemp, $productos, $albaranes, $suNumero);
				$existe=1;
				$respuesta['sql']=$rest['sql'];
				$res=$rest['idTemporal'];
				$pro=$rest['productos'];
			}else{
				$rest=$CFac->insertarDatosFacturaTemporal($idUsuario, $idTienda, $estado, $fecha ,  $productos, $idProveedor, $albaranes, $suNumero);
				$existe=0;
				$pro=$rest['productos'];
				$res=$rest['id'];
				$idFacturaTemp=$res;
				$respuesta['sql1']=$rest['sql'];
			}
			if ($idFactura>0){
				$modId=$CFac->addNumRealTemporal($idFacturaTemp, $numFactura);
				$respuesta['sql2']=$modId['sql'];
				$estado="Sin Guardar";
				$modEstado=$CFac->modEstadoFactura($idFactura, $estado);
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
		if ($_POST['dedonde']="Albaran"){
			$idPedido=$_POST['idPedido'];
			$estado="Facturado";
			$modEstado=$CPed->modEstadoPedido($idPedido, $estado);
		}
		echo json_encode($respuesta);
		break;
		case 'htmlAgregarFilaPedido':
			$res=lineaPedidoAlbaran($_POST['datos']);
			$respuesta['html']=$res['html'];
			echo json_encode($respuesta);
		break;
		case 'AddCosteProveedor':
			$idArticulo=$_POST['idArticulo'];
			$valor=$_POST['valor'];
			$idProveedor=$_POST['idProveedor'];
			$fecha=$_POST['fecha'];
			$buscar=$CArticulos->buscarReferencia($idArticulo, $idProveedor);
			$datos=array(
				'coste'=>$valor,
				'idArticulo'=>$idArticulo,
				'idProveedor'=>$idProveedor,
				'fecha'=>$fecha,
				'estado'=>"activo"
			);
			if ($buscar){
				if ($buscar['fechaActualizacion']>$fecha){
					$respuesta['error']=1;
				}else{
					$mod=$CArticulos->modificarCosteProveedorArticulo($datos);
					$respuesta=$mod['sql'];
				}
				
			}else{
				$datos['refProveedor']=0;
				$add=$CArticulos->addArticulosProveedores($datos);
				$respuesta['sql']=$add['sql'];
				$respuesta['array']=$datos;
			}
			echo json_encode($respuesta);
		break;
		
	
}



?>
