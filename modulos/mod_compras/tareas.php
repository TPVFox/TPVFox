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
	//@Objetivo:
	//Busca el proveedor según el dato insertado , si el dato viene de la caja idProveedor entonces busca por id
	//Si no busca por nombre del proveedor y muestra un modal con las coincidencias ,
	//Si no recibe busqueda muestra un modal con todos los nombres de los proveedores 
		
		if ($_POST['idcaja']=="id_proveedor"){
			$buscarId=$CProveedores->buscarProveedorId($_POST['busqueda']);
			if ($buscarId){
				$respuesta['id']=$buscarId['idProveedor'];
				$respuesta['nombre']=$buscarId['nombrecomercial'];
				$respuesta['Nitems']=1;
			}else{
				$respuesta['Nitems']=2;
			}
			
		}else{
			$buscarTodo=$CProveedores->buscarProveedorNombre($_POST['busqueda']);
			$respuesta['html']=htmlProveedores($_POST['busqueda'],$_POST['dedonde'], $_POST['idcaja'], $buscarTodo['datos']);
			$respuesta['datos']=$buscarTodo['datos'];
			
		}
		echo json_encode($respuesta);
	break;
	case 'buscarProductos':
			//@Objetivo;
			//Busqueda de productos: Recive el valor a buscar el campo por el que tiene que buscar 
			$busqueda = $_POST['valorCampo'];
			$campoAbuscar = $_POST['campo'];
			$id_input = $_POST['id_input'];
			$idcaja=$_POST['idcaja'];
			$idProveedor=$_POST['idProveedor'];
			$dedonde=$_POST['dedonde'];
			$res = BuscarProductos($id_input,$campoAbuscar, $idcaja, $busqueda,$BDTpv, $idProveedor);
			if ($res['Nitems']===1){
				$respuesta=$res;
				$respuesta['Nitems']=$res['Nitems'];	
			}else{
				$respuesta['listado']= htmlProductos($res['datos'],$id_input,$campoAbuscar,$busqueda, $dedonde);
				$respuesta['Estado'] = 'Listado';
			}
			echo json_encode($respuesta);  
	break;	
	
	
	case 'htmlAgregarFilasProductos':
	//@objetivo:
	//Agrega la fila de productos : crea las filas de los productos para posteriormente insertar en la tabla 
			$productos=$_POST['productos'];
			$dedonde=$_POST['dedonde'];
			$respuesta=array('html'=>"");
			foreach($productos as $producto){
					 if (!is_array($producto)){ 
						 $res=htmlLineaProducto($productos, $dedonde);
						 $respuesta['html'].=$res['html'];
						 break;
					 }else{
					$res=htmlLineaProducto($producto, $dedonde);
					$respuesta['html'].=$res['html'];
					}
			 }
			echo json_encode($respuesta);
		break;
		//Añadir un registro a la tabla articuloproveedor
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
			//Busca si un articulo tiene referencia de proveedor
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
		//Comprueba los pedidos con el estado guardado
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
			//Comprueba los albaranes con el estado guardado
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
		//Buscar los pedidos con el estado guardado de un proveedor determinado 
		//Si solo resicibe un resultado monta el array si no muestra un modal con los pedidos de ese proveedor 
		case 'BuscarPedido':
		$numPedido=$_POST['numPedido'];
		$idProveedor=$_POST['idProveedor'];
		$estado="Guardado";
		$datosPedido=$CPed->buscarPedidoProveedorGuardado($idProveedor, $numPedido, $estado);
		if (isset($datosPedido)){
			if ($datosPedido['Nitem']==1){
				$respuesta['temporales']=1;
				$respuesta['datos']['Numpedpro']=$datosPedido['Numpedpro'];
				$respuesta['datos']['idPedido']=$datosPedido['id'];
				$respuesta['datos']['fecha']=$datosPedido['FechaPedido'];
				$respuesta['datos']['total']=$datosPedido['total'];
				$respuesta['datos']['estado']="activo";
				$respuesta['Nitems']=$datosPedido['Nitem'];
				$productosPedido=$CPed->ProductosPedidos($datosPedido['id']);
				$respuesta['productos']=$productosPedido;
			}else{
				$respuesta=$datosPedido;
				$modal=modalPedidos($datosPedido['datos']);
				$respuesta['html']=$modal['html'];
			}
		}
		echo json_encode($respuesta);
		break;
		
		case 'BuscarAlbaran':
			//Busca los albaranes con el estado guardado 
			//Si obtiene un resultado crea el arra con los datos necesarios 
			// si no muestra un modal
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
				$respuesta['datos']['estado']="activo";
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
			
		case 'addPedidoTemporal';
			//Añadir un pedido temporal, recibe los campos necesarios para añadir el pedido
			//Si ya existe modifica el registro si no lo crea, devuelve siempre el id del temporal
			$numPedidoTemp=$_POST['idTemporal'];
			$idUsuario=$_POST['idUsuario'];
			$idTienda=$_POST['idTienda'];
			$estadoPedido=$_POST['estado'];
			$idPedido=$_POST['idReal'];
			$fecha=$_POST['fecha'];
			$productos=$_POST['productos'];
			$idProveedor=$_POST['idProveedor'];
			$existe=0; // Variable para devolver y saber si modifico o insert.
			if ($numPedidoTemp>0){
				$rest=$CPed->modificarDatosPedidoTemporal($idUsuario, $idTienda, $estadoPedido, $fecha ,  $numPedidoTemp, $productos);
				$existe=1;
				$respuesta['sql']=$rest['sql'];
				$res=$rest['idTemporal'];
			}else{
				$rest=$CPed->insertarDatosPedidoTemporal($idUsuario, $idTienda, $estadoPedido, $fecha ,  $productos, $idProveedor);
				$existe=0;
				$res=$rest['id'];
				$numPedidoTemp=$res;
			}
			$pro=$rest['productos'];
		//	$respuesta['numPedido']=$numPedido;
			
			 if ($idPedido>0){
				//Existe idPedido, estamos modificacion de un pedido,añadimos el número del pedido real al registro temporal
				//y modificamos el estado del pedido real a sin guardar.
				$modId=$CPed->addNumRealTemporal($numPedidoTemp, $idPedido);
				$estado="Sin Guardar";
				$modEstado=$CPed->modEstadoPedido($idPedido, $estado);
			 }
			if ($productos){
				//Recalcula el valor de los productos
					$productos_para_recalculo = json_decode( json_encode( $_POST['productos'] ));
					$respuesta['productosre']=$productos_para_recalculo;
					$CalculoTotales = recalculoTotalesAl($productos_para_recalculo);
					$total=round($CalculoTotales['total'],2);
					$respuesta['total']=round($CalculoTotales['total'],2);
					//~ $nuevoArray = array(
								//~ 'desglose'=> $CalculoTotales['desglose'],
								//~ 'total' => $CalculoTotales['total']
									//~ );
					$respuesta['totales']=$CalculoTotales;
					//$totalivas=0;
					//~ foreach($nuevoArray['desglose'] as $nuevo){
						//~ $totalivas=$totalivas+$nuevo['iva'];
					//~ }
				
					$modTotal=$CPed->modTotales($res, $respuesta['total'], $CalculoTotales['subivas']);
					$respuesta['sqlmodtotal']=$modTotal['sql'];
					//~ $respuesta['total']=$total;
				}
				$respuesta['id']=$res;
				$respuesta['existe']=$existe;
				$respuesta['productos']=$_POST['productos'];
				echo json_encode($respuesta);
		break;
		

		case 'addAlbaranTemporal':
			//Añade un albaran temporal es igual que la de pedidos pero esta vez en la tabla temporal de albaranes
			$idAlbaranTemporal=$_POST['idTemporal'];
			$idUsuario=$_POST['idUsuario'];
			$idTienda=$_POST['idTienda'];
			$estado=$_POST['estado'];
			$idAlbaran=$_POST['idReal'];
		//	$numAlbaran=$_POST['numAlbaran'];
			$fecha=$_POST['fecha'];
			$productos=$_POST['productos'];
			if (isset($_POST['pedidos'])){
				$pedidos=$_POST['pedidos'];
			}else{
				$pedidos="";
			}
			
			$idProveedor=$_POST['idProveedor'];
			$suNumero=$_POST['suNumero'];
			$existe=0;
			if ($idAlbaranTemporal>0){
				$rest=$CAlb->modificarDatosAlbaranTemporal($idUsuario, $idTienda, $estado, $fecha ,  $idAlbaranTemporal, $productos, $pedidos, $suNumero);
				$existe=1;
		
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
				//$modId=$CAlb->addNumRealTemporal($idAlbaranTemporal, $numAlbaran);
				$modId=$CAlb->addNumRealTemporal($idAlbaranTemporal, $idAlbaran);
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
			//Añadir factura temporal 
			// [NOTA] Es igual que añadir pedido temporal con la diferencia de que cambia la tabla temporal de facturas
			$idFacturaTemp=$_POST['idFacturaTemp'];
			$idUsuario=$_POST['idUsuario'];
			$idTienda=$_POST['idTienda'];
			$estado=$_POST['estado'];
			$idFactura=$_POST['idFactura'];
			$numFactura=$_POST['numFactura'];
			$fecha=$_POST['fecha'];
			$productos=$_POST['productos'];
			if(isset ($_POST['albaranes'])){
				$albaranes=$_POST['albaranes'];
			}else{
				$albaranes='';
			}
			$idProveedor=$_POST['idProveedor'];
			$suNumero=$_POST['suNumero'];
			if ($idFacturaTemp>0){
				$rest=$CFac->modificarDatosFacturaTemporal($idUsuario, $idTienda, $estado, $fecha ,  $idFacturaTemp, $productos, $albaranes, $suNumero);
				$existe=1;
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
		
		case 'modificarEstado':
		//@Objetivo: 
		//Modificar el estado dependiento de donde venga 
		//@Parámetros que recibe: 
		//id -> id que recibimos , puede ser id de pedido o id de albaran
		//dedonde->Para poder filtrar que función tenemos que ejecutar	
			
			$estado=$_POST['estado'];
			if ($_POST['dedonde']=="albaran"){
				$modEstado=$CPed->modEstadoPedido($_POST['id'], $estado);
			}
			if ($_POST['dedonde']=="factura"){
				$modEstado=$CAlb->modEstadoAlbaran($_POST['id'], $estado);
			}
			
		break;
		case 'htmlAgregarFilaPedido':
			//Agrega tanto la fila de pedido como la de alabaranes
			$res=lineaPedidoAlbaran($_POST['datos'], $_POST['dedonde']);
			$respuesta['html']=$res['html'];
			echo json_encode($respuesta);
		break;
		
		case 'AddCosteProveedor':
		//@objetivo: Añadir o modificar los registros de referencia
		//@Parametros :
		//idProveedor: El id del proveedor 
		//idArticulo: id del articulo
		//Valor: valor que hemos colocado en el input
		//fecha: la fecha que la necesitamos para compararla con la del registro, si la fecha nuestra es menor que la del registro muestra un error
		
			$buscar=$CArticulos->buscarReferencia($_POST['idArticulo'], $_POST['idProveedor']);
			$datos=array(
				'coste'=>$_POST['valor'],
				'idArticulo'=>$_POST['idArticulo'],
				'idProveedor'=>$_POST['idProveedor'],
				'fecha'=>$_POST['fecha'],
				'estado'=>"activo"
			);
			if ($buscar){
				if ($buscar['fechaActualizacion']>$_POST['fecha']){
					$respuesta['error']=1;
				}else{
					$mod=$CArticulos->modificarCosteProveedorArticulo($datos);
				}
				
			}else{
				$datos['refProveedor']=0;
				$add=$CArticulos->addArticulosProveedores($datos);
			}
			echo json_encode($respuesta);
		break;
		
		case 'datosImprimir':
			//Imprimir un documento , dependiendo de donde venga se pone el nombre y envía todos los datos  
			//a la función montarHTMLimprimir que lo que realiza es simplemente montar el html una parte copn la cabecera y 
			//otra con el cuerpo del documento
			//debajo cargamos las clases de imprimir y la plantilla una vez generada y lista la plantilla devolvemos la ruta
			//para así desde javascript poder abrirla
			$id=$_POST['id'];
			$dedonde=$_POST['dedonde'];
			$idTienda=$_POST['idTienda'];
			$nombreTmp=$dedonde."compras.pdf";
			$htmlImprimir=montarHTMLimprimir($id, $BDTpv, $dedonde, $idTienda);
			$cabecera=$htmlImprimir['cabecera'];
			$html=$htmlImprimir['html'];
			require_once('../../lib/tcpdf/tcpdf.php');
			include ('../../clases/imprimir.php');
			include('../../controllers/planImprimir.php');
			$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
			echo json_encode($ficheroCompleto);
		break;
		
	
}



?>
