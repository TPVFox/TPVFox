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
			$res = BuscarProductos($id_input,$campoAbuscar, $idcaja, $busqueda,$BDTpv, $idProveedor);
			$respuesta['sql']=$res['sql'];
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
	
	
}



?>
