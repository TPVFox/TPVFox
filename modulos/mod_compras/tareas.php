<?php 

/* Fichero de tareas a realizar.
 * 
 * 
 * Con el switch al final y variable $pulsado
 * 
 *  */
$pulsado = $_POST['pulsado'];
include_once ("./../../configuracion.php");
include_once ("./../mod_conexion/conexionBaseDatos.php");
include_once ("./funciones.php");
include_once ("../mod_incidencias/popup_incidencias.php");
include_once "clases/pedidosCompras.php";
include_once '../../clases/Proveedores.php';
include_once "clases/albaranesCompras.php";
include_once "clases/facturasCompras.php";
include_once "../../clases/articulos.php";

$CProveedores=new Proveedores($BDTpv);
$CPed=new PedidosCompras($BDTpv);
$CAlb=new AlbaranesCompras($BDTpv);
$CFac= new FacturasCompras($BDTpv);
$CArticulos=new Articulos($BDTpv);
switch ($pulsado) {
	case 'buscarProveedor':
	//@Objetivo:
	//Busca el proveedor según el dato insertado , si el dato viene de la caja idProveedor entonces busca por id
	//Si no busca por nombre del proveedor y muestra un modal con las coincidencias ,
	//Si no recibe busqueda muestra un modal con todos los nombres de los proveedores 
	// Contiene el control de errores de las funciones que llama a la clase proveedor
		if ($_POST['idcaja']=="id_proveedor"){
			$buscarId=$CProveedores->buscarProveedorId($_POST['busqueda']);
			if (isset($buscarId['error'])){
				$respuesta['error']=$buscarId['error'];
				$respuesta['consulta']=$buscarId['consulta'];
			}else{
				if (isset($buscarId['idProveedor'])){
					$respuesta['id']=$buscarId['idProveedor'];
					$respuesta['nombre']=$buscarId['nombrecomercial'];
					$respuesta['Nitems']=1;
				}else{
					$respuesta['Nitems']=2;
				}
			}
		}else{
			$buscarTodo=$CProveedores->buscarProveedorNombre($_POST['busqueda']);
			if (isset($buscarTodo['error'])){
				$respuesta['error']=$buscarTodo['error'];
				$respuesta['consulta']=$buscarTodo['consulta'];
			}else{
				$respuesta['html']=htmlProveedores($_POST['busqueda'],$_POST['dedonde'], $_POST['idcaja'], $buscarTodo['datos']);
				$respuesta['datos']=$buscarTodo['datos'];
			}
			
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
			if ($res['Nitems']===1 && $idcaja<>"cajaBusqueda"){
					$respuesta=$res;
					$respuesta['Nitems']=$res['Nitems'];
				
			}else{
				if (isset($res['datos'])){
					$respuesta['listado']= htmlProductos($res['datos'],$id_input,$campoAbuscar,$busqueda, $dedonde);
					$respuesta['Estado'] = 'Listado';
					$respuesta['html']=$respuesta['listado'];
				}else{
					$respuesta['Nitems']=2;
				}
			}
			$respuesta['sql']=$res['sql'];
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
		
		case 'addProveedorArticulo':
		//@Objetivo: comprobar si ya existe un registro de proveedores articulos si es así modificarlo y si nno crearlo
			$fechaActualizacion=date('Y-m-d');
			$estado="activo";
			$respuesta=array();
			$datos=array(
				'idArticulo'=>$_POST['idArticulo'],
				'refProveedor'=>$_POST['refProveedor'],
				'idProveedor'=>$_POST['idProveedor'],
				'coste'=>$_POST['coste'],
				'fecha'=>$fechaActualizacion,
				'estado'=>$estado
			);
			$datosArticulo=$CArticulos->buscarReferencia($_POST['idArticulo'], $_POST['idProveedor']);
			if (isset($datosArticulo['error'])){
					$respuesta['error']=$datosArticulo['error'];
					$respuesta['consulta']=$datosArticulo['consulta'];
			}else{
				if (isset($datosArticulo['idArticulo'])){
					$modArt=$CArticulos->modificarProveedorArticulo($datos);
					if (isset($modArt['error'])){
						$respuesta['error']=$modArt['error'];
						$respuesta['consulta']=$modArt['consulta'];
					}
				}else{
					$addNuevo=$CArticulos->addArticulosProveedores($datos);	
					if (isset($addNuevo['error'])){
						$respuesta['error']=$addNuevo['error'];
						$respuesta['consulta']=$addNuevo['consulta'];
					}
				}
			}
			echo json_encode($respuesta);
		break;
		
		case 'comprobarAdjunto':
			//@Objetivo:
			//comprobar que el proveedor tiene albaran o pedido en estado guardado
			$estado="Guardado";
			$idProveedor=$_POST['idProveedor'];
			$dedonde=$_POST['dedonde'];
			$respuesta=array();
			if ($dedonde=="factura"){
				$buscar=$CAlb->albaranesProveedorGuardado($idProveedor, $estado);
				if (isset($buscar['error'])){
						$respuesta['error']=$buscar['error'];
						$respuesta['consulta']=$buscar['consulta'];
				}
			}else{
				$buscar=$CPed->pedidosProveedorGuardado($idProveedor, $estado);
				if (isset($buscar['error'])){
						$respuesta['error']=$buscar['error'];
						$respuesta['consulta']=$buscar['consulta'];
				}
			}
			if (count($buscar)>0){
					$respuesta['bandera']=1;
			}else{
					$respuesta['bandera']=2;
			}
			
			echo json_encode($respuesta);
		break;
	
		case 'buscarAdjunto':
		//@objetivo:
		//buscar si el numero de adjunto (número de pedido o albarán )
		//carga los datos principales y sus productos
			$respuesta=array();
			$numAdjunto=$_POST['numReal'];
			$idProveedor=$_POST['idProveedor'];
			$estado="Guardado";
			$dedonde=$_POST['dedonde'];
			if ($dedonde=="albaran"){
				$datosAdjunto=$CPed->buscarPedidoProveedorGuardado($idProveedor, $numAdjunto, $estado);
				if (isset($datosAdjunto['error'])){
					$respuesta['error']=$datosAdjunto['error'];
					$respuesta['consola']=$datosAdjunto['consulta'];
				}
			}else{
				$datosAdjunto=$CAlb->buscarAlbaranProveedorGuardado($idProveedor, $numAdjunto, $estado);
				if (isset($datosAdjunto['error'])){
					$respuesta['error']=$datosAdjunto['error'];
					$respuesta['consola']=$datosAdjunto['consulta'];
				}
			}
			if (isset($datosAdjunto['Nitem'])){
				$respuesta['temporales']=1;
				if ($dedonde=="albaran"){
					$respuesta['datos']['NumAdjunto']=$datosAdjunto['Numpedpro'];
					$respuesta['datos']['idAdjunto']=$datosAdjunto['id'];
					$productosAdjunto=$CPed->ProductosPedidos($datosAdjunto['id']);
					if (isset($productosAdjunto['error'])){
						$respuesta['error']=$productosAdjunto['error'];
						$respuesta['consulta']=$productosAdjunto['consulta'];
					}else{
						$respuesta['productos']=$productosAdjunto;
					}
				}else{
					$respuesta['datos']['NumAdjunto']=$datosAdjunto['Numalbpro'];
					$respuesta['datos']['idAdjunto']=$datosAdjunto['id'];
					$productosAdjunto=$CAlb->ProductosAlbaran($datosAdjunto['id']);
					if (isset($productosAdjunto['error'])){
						$respuesta['error']=$productosAdjunto['error'];
						$respuesta['consulta']=$productosAdjunto['consulta'];
					}else{
						$respuesta['productos']=$productosAdjunto;
					}
				}
				$date = new DateTime($datosAdjunto['Fecha']);
				$respuesta['datos']['fecha']=date_format($date, 'Y-m-d');
				$respuesta['datos']['total']=$datosAdjunto['total'];
				$respuesta['datos']['estado']="activo";
				
				$respuesta['Nitems']=$datosAdjunto['Nitem'];
				
			}else{
				$respuesta['datos']=$datosAdjunto;
				$modal=modalAdjunto($datosAdjunto['datos'], $dedonde, $BDTpv);
				$respuesta['html']=$modal['html'];
			}
		echo json_encode($respuesta);
		break;
		
		case 'addPedidoTemporal';
			//@Objetivo:
			//Añadir un pedido temporal, recibe los campos necesarios para añadir el pedido
			//Si ya existe modifica el registro si no lo crea, devuelve siempre el id del temporal
			$numPedidoTemp=$_POST['idTemporal'];
			$idUsuario=$_POST['idUsuario'];
			$idTienda=$_POST['idTienda'];
			$estadoPedido=$_POST['estado'];
			$idPedido=$_POST['idReal'];
			$fecha=$_POST['fecha'];
			$productos=json_decode($_POST['productos']);
			$idProveedor=$_POST['idProveedor'];
			$existe=0; // Variable para devolver y saber si modifico o insert.
			//Existe la utilizo como bandera para que el javascript solo me cree una vez la url del temporal
			if ($numPedidoTemp>0){
				//Si existe el número temporal se modifica el temporal
				$rest=$CPed->modificarDatosPedidoTemporal($idUsuario, $idTienda, $estadoPedido, $fecha ,  $numPedidoTemp, $productos);
				if (isset($rest['error'])){
						$respuesta['error']=$rest['error'];
						$respuesta['consulta']=$rest['consulta'];
						echo json_encode($respuesta);
						break;
				}else{
					$existe=1;
				}
			}else{
				//Si no existe crea un temporal nuevo
				$rest=$CPed->insertarDatosPedidoTemporal($idUsuario, $idTienda, $estadoPedido, $fecha ,  $productos, $idProveedor);
				if (isset($rest['error'])){// Control de errores
						$respuesta['error']=$rest['error'];
						$respuesta['consulta']=$rest['consulta'];
						echo json_encode($respuesta);
						break;
				}else{
					$existe=0;
					$numPedidoTemp=$rest['id'];
				}
			}
			$pro=$rest['productos'];
			 if ($idPedido>0){
				 //Si existe u pedido real se modifica el temporal para indicarle que tiene un numero temporal
				//Existe idPedido, estamos modificacion de un pedido,añadimos el número del pedido real al registro temporal
				//y modificamos el estado del pedido real a sin guardar.
				$modId=$CPed->addNumRealTemporal($numPedidoTemp, $idPedido);
				if (isset($modId['error'])){
						$respuesta['error']=$modId['error'];
						$respuesta['consulta']=$modId['consulta'];
						echo json_encode($respuesta);
						break;
				}
				$estado="Sin Guardar";
				// Se modifica el estado del pedido real a sin guardar
				$modEstado=$CPed->modEstadoPedido($idPedido, $estado);
				if (isset($modId['error'])){
						$respuesta['error']=$modEstado['error'];
						$respuesta['consulta']=$modEstado['consulta'];
						echo json_encode($respuesta);
						break;
				}
			 }
			if ($productos){
				//Recalcula el valor de los productos
					$CalculoTotales = recalculoTotales($productos);
					$total=round($CalculoTotales['total'],2);
					$respuesta['total']=round($CalculoTotales['total'],2);
					$respuesta['totales']=$CalculoTotales;
					$modTotal=$CPed->modTotales($numPedidoTemp, $respuesta['total'], $CalculoTotales['subivas']);
					$respuesta['sqlmodtotal']=$modTotal['sql'];
					$htmlTotales=htmlTotales($CalculoTotales);
					$respuesta['htmlTabla']=$htmlTotales['html'];
				}
				$respuesta['id']=$numPedidoTemp;
				$respuesta['existe']=$existe;
				$respuesta['productos']=$_POST['productos'];
				echo json_encode($respuesta);
		break;
		

		case 'addAlbaranTemporal':
			//@Objetivo:
			//Añade un albaran temporal es igual que la de pedidos pero esta vez en la tabla temporal de albaranes
			$idAlbaranTemporal=$_POST['idTemporal'];
			$idUsuario=$_POST['idUsuario'];
			$idTienda=$_POST['idTienda'];
			$estado=$_POST['estado'];
			$idAlbaran=$_POST['idReal'];
			$fecha=$_POST['fecha'];
			//~ error_log($fecha);
			$fecha = new DateTime($fecha);
			$fecha = $fecha->format('Y-m-d');
			//~ error_log($fecha);
			//~ $fecha=date_format($fecha, 'Y-m-d');
			//~ error_log($fecha);
			$productos=json_decode($_POST['productos']);
			if (isset($_POST['pedidos'])){
				$pedidos=$_POST['pedidos'];
			}else{
				$pedidos=array();
			}
			$suNumero=$_POST['suNumero'];
			$idProveedor=$_POST['idProveedor'];
			$existe=0;
		//Si existe el albaran  temporal se modifica , devuelve el control de errores
		//Si no tiene  errores devuelve el idTemporal y la bandera que se utiliza el el js de existe
			if ($idAlbaranTemporal>0){
				$rest=$CAlb->modificarDatosAlbaranTemporal($idUsuario, $idTienda, $estado, $fecha ,  $idAlbaranTemporal, $productos, $pedidos, $suNumero);
					if (isset($rest['error'])){
						$respuesta['error']=$rest['error'];
						$respuesta['consulta']=$rest['consulta'];
						echo json_encode($respuesta);
						break;
					}else{
						$existe=1;
						$res=$rest['idTemporal'];
						$respuesta['id']=$rest['idTemporal'];
					}
			}else{
				//Si no existe el temporal se crea , con control de errores 
				$rest=$CAlb->insertarDatosAlbaranTemporal($idUsuario, $idTienda, $estado, $fecha ,  $productos, $idProveedor, $pedidos, $suNumero);
				if (isset($rest['error'])){
					$respuesta['error']=$rest['error'];
					$respuesta['consulta']=$rest['consulta'];
					$existe=0;
						echo json_encode($respuesta);
						break;
					
				}else{
					$existe=0;
					$idAlbaranTemporal=$rest['id'];
					$respuesta['id']=$rest['id'];
					$respuesta['sqlTemporal']=$rest['sql'];
				}
			}
			//Si es un albarán que se está modificando se guarda en el Real el idTemporal
			//Y se cambia el estado a Sin guardar
			//Con control de errores las dos funciones
			if ($idAlbaran>0){
				$modId=$CAlb->addNumRealTemporal($idAlbaranTemporal, $idAlbaran);
				if (isset($modId['error'])){
						$respuesta['error']=$modId['error'];
						$respuesta['consulta']=$modId['consulta'];
						echo json_encode($respuesta);
						break;
				}
				$estado="Sin Guardar";
				$modEstado=$CAlb->modEstadoAlbaran($idAlbaran, $estado);
				if (isset($modEstado['error'])){
						$respuesta['error']=$modEstado['error'];
						$respuesta['consulta']=$modEstado['consulta'];
						echo json_encode($respuesta);
						break;
				}
			}
			if ($productos){
				$CalculoTotales = recalculoTotales($productos);
				$total=round($CalculoTotales['total'],2);
				$respuesta['total']=round($CalculoTotales['total'],2);
				$respuesta['totales']=$CalculoTotales;
				$modTotal=$CAlb->modTotales($idAlbaranTemporal, $respuesta['total'], $CalculoTotales['subivas']);
				if (isset($modTotal['error'])){
						$respuesta['error']=$modTotal['error'];
						$respuesta['consulta']=$modTotal['consulta'];
						echo json_encode($respuesta);
						break;
				}
				$respuesta['sqlmodtotal']=$modTotal['sql'];
				$htmlTotales=htmlTotales($CalculoTotales);
				$respuesta['htmlTabla']=$htmlTotales['html'];
				
			}
			$respuesta['existe']=$existe;
			$respuesta['productos']=$_POST['productos'];
			
		echo json_encode($respuesta);
		break;
		
		
		case 'addFacturaTemporal':
		//@Objetivo:
			//Añadir factura temporal 
			// [NOTA] Es igual que añadir pedido temporal con la diferencia de que cambia la tabla temporal de facturas
			$idFacturaTemp=$_POST['idTemporal'];
			$idUsuario=$_POST['idUsuario'];
			$idTienda=$_POST['idTienda'];
			$estado=$_POST['estado'];
			$idFactura=$_POST['idReal'];
			$fecha=$_POST['fecha'];
			$respuesta=array();
			$productos=json_decode($_POST['productos']);
			if(isset ($_POST['albaranes'])){
				$albaranes=$_POST['albaranes'];
			}else{
				$albaranes=array();
			}
			$idProveedor=$_POST['idProveedor'];
			$suNumero=$_POST['suNumero'];
			
			
			if ($idFacturaTemp>0){
				$rest=$CFac->modificarDatosFacturaTemporal($idUsuario, $idTienda, $estado, $fecha ,  $idFacturaTemp, $productos, $albaranes, $suNumero);
				if(isset($rest['error'])){
					$respuesta['error']=$rest['error'];
					$respuesta['consulta']=$rest['consulta'];
				}else{
					$existe=1;
					$res=$rest['idTemporal'];
					$pro=$rest['productos'];
				}
			}else{
				$rest=$CFac->insertarDatosFacturaTemporal($idUsuario, $idTienda, $estado, $fecha ,  $productos, $idProveedor, $albaranes, $suNumero);
				if(isset($rest['error'])){
					$respuesta['error']=$rest['error'];
					$respuesta['consulta']=$rest['consulta'];
				}else{
					$existe=0;
					$pro=$rest['productos'];
					$res=$rest['id'];
					$idFacturaTemp=$res;
				}
			}
			if ($idFactura>0){
				$modId=$CFac->addNumRealTemporal($idFacturaTemp, $idFactura);
				if (isset($modId['error'])){
					$respuesta['error']=$modId['error'];
					$respuesta['consulta']=$modId['consulta'];
				}else{
					$estado="Sin Guardar";
					$modEstado=$CFac->modEstadoFactura($idFactura, $estado);
					if (isset($modEstado['error'])){
						$respuesta['error']=$modEstado['error'];
						$respuesta['consulta']=$modEstado['consulta'];
					}
				}
			}
			if ($productos){
				$CalculoTotales = recalculoTotales($productos);
				$total=round($CalculoTotales['total'],2);
				$respuesta['total']=round($CalculoTotales['total'],2);
				$respuesta['totales']=$CalculoTotales;
				$modTotal=$CFac->modTotales($res, $respuesta['total'], $CalculoTotales['subivas']);
				if (isset($modTotal['error'])){
						$respuesta['error']=$modTotal['error'];
						$respuesta['consulta']=$modTotal['consulta'];
				}
				$respuesta['sqlmodtotal']=$modTotal['sql'];
				$htmlTotales=htmlTotales($CalculoTotales);
				$respuesta['htmlTabla']=$htmlTotales['html'];
				
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
			$respuesta=array();
			$estado=$_POST['estado'];
			if ($_POST['dedonde']=="albaran"){
				$modEstado=$CPed->modEstadoPedido($_POST['id'], $estado);
				if (isset($mosEstado['error'])){
						$respuesta['error']=$mosEstado['error'];
						$respuesta['consulta']=$modEstado['consulta'];
				}
			}
			if ($_POST['dedonde']=="factura"){
				$modEstado=$CAlb->modEstadoAlbaran($_POST['id'], $estado);
				if (isset($mosEstado['error'])){
						$respuesta['error']=$mosEstado['error'];
						$respuesta['consulta']=$modEstado['consulta'];
				}
			}
			echo json_encode($respuesta);
		break;
		case 'htmlAgregarFilaAdjunto':
		//OBjetivo: agregar la fila con los datos del albaran o pedido adjunto
			$res=lineaAdjunto($_POST['datos'], $_POST['dedonde']);
			$respuesta['html']=$res['html'];
			echo json_encode($respuesta);
		break;
		
		case 'datosImprimir':
			//@Objetivo:
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
		case 'insertarImporte':
			//@Objetivo:
			//Insertar un nuevo importe a una factura
			//@Proceso:
			//Primero se buscan los importes que tiene ya esa factura, si tiene elimina el registro
			 $importe=$_POST['importe'];
			 $fecha=$_POST['fecha'];
			 $idFactura=$_POST['idTemporal'];
			 $formaPago=$_POST['forma'];
			 $referencia=$_POST['referencia'];
			 $total=$_POST['total'];
			 $idReal=$_POST['idReal'];
			 $arrayPrincipal=array();
			 $error=0;
			 $bandera=$importe;
			 $respuesta=array();
			 $importesReal=array();
			 $importesReal=$CFac->importesFactura($idReal);
			 if (isset($importesReal['error'])){
				$respuesta['error']=$importesReal['error'];
				$respuesta['consulta']=$importesReal['consulta'];
			}
			 $respuesta['importeReal']=$importesReal;
			 if(count($importesReal)>0){
				$importesReal=modificarArraysImportes($importesReal, $total);
				$importesTemporal=json_encode($importesReal);
				$eliminarReal=$CFac->eliminarRealImportes($idReal);
				if (isset($eliminarReal['error'])){
					$respuesta['error']=$eliminarReal['error'];
					$respuesta['consulta']=$eliminarReal['consulta'];
				}
				$respuesta['impTemporal']=$importesTemporal;
			 }else{
				 $importesTemporal=$CFac->importesTemporal($idFactura);
				if (isset($importesTemporal['error'])){
					$respuesta['error']=$importesTemporal['error'];
					$respuesta['consulta']=$importesTemporal['consulta'];
				}else{
				 $importesTemporal=$importesTemporal['FacCobros'];
				 $bandera=$importe;
				}
			 }
			 if ($importesTemporal){
				$importes=json_decode($importesTemporal, true);
				$respuesta['importes']= $importes;
				 foreach ($importes as $import){
					 $bandera=$bandera+(string)$import['importe'];
					 array_push($arrayPrincipal, $import);
				 }
				 if ($bandera>$total){
					 $respuesta['mensaje']=1;
					 $error=1;
				 }
				 $respuesta['bandera']=$bandera;
			 }
			 if ($error==0){
				$pendiente=$total-$bandera;
				$nuevo=array();
				$nuevo['importe']=$importe;
				$nuevo['fecha']=$fecha;
				$nuevo['forma']=$formaPago;
				$nuevo['referencia']=$referencia;
				$nuevo['pendiente']=$pendiente;
				$respuesta['nuevo']=$nuevo;
				array_push($arrayPrincipal, $nuevo);
				$jsonImporte=json_encode($arrayPrincipal);
				$modImportes=$CFac->modificarImportesTemporal($idFactura, $jsonImporte);
				if (isset($modImportes['error'])){
					$respuesta['error']=$modImportes['error'];
					$respuesta['consulta']=$modImportes['consulta'];
				}
				$html=htmlImporteFactura($nuevo, $BDTpv);
				$respuesta['html']=$html['html'];
			}
			echo json_encode($respuesta);
		break;
		case 'abririncidencia':
		//@OBJETIVO:
		//Mostrar el modal de incidencias con los datos de compras, según en el archivo en el que está 
		//situado envía los datos de este. El idReal es el id del albarán, pedido o factura guardado si no lo 
		//envía en 0
		//@Retornar: devuelve el html para insertar en el js del modal
			$dedonde=$_POST['dedonde'];
			$usuario=$_POST['usuario'];
			$idReal=0;
			if(isset($_POST['idReal'])){
				$idReal=$_POST['idReal'];
			}
			
			$configuracion=$_POST['configuracion'];
			$numInicidencia=0;
			$tipo="mod_compras";
			$fecha=date('Y-m-d');
			$datos=array(
			'dedonde'=>$dedonde,
			'idReal'=>$idReal
			);
			$datos=json_encode($datos);
			$estado="No resuelto";
			$html=modalIncidencia($usuario, $datos, $fecha, $tipo, $estado, $numInicidencia, $configuracion, $BDTpv);
			$respuesta['html']=$html;
			$respuesta['datos']=$datos;
			echo json_encode($respuesta);
		break;
		
		case 'nuevaIncidencia':
		//@Objetivo: Agregar una nueva incidencia, dirigimos los datos a la función addIncidencia
		//esta está situada en el modulo de incidencias e inserta una nueva fila a la tabla de módulo
		//de incidencias con los datos seleccionado en el modal.
			$usuario= $_POST['usuario'];
			$fecha= $_POST['fecha'];
			$datos= $_POST['datos'];
			$dedonde= $_POST['dedonde'];
			$estado= $_POST['estado'];
			$mensaje= $_POST['mensaje'];
			$usuarioSelect=0;
			if(isset($_POST['usuarioSelec'])){
				$usuarioSelect=$_POST['usuarioSelec'];
			}
			if($usuarioSelect>0){
				$datos=json_decode($datos);
				$datos->usuarioSelec=$usuarioSelect;
				$datos=json_encode($datos);
			}
			$numInicidencia=0;
			if($mensaje){
				$nuevo=addIncidencia($usuario, $fecha, $dedonde, $datos, $estado, $mensaje, $BDTpv,  $numInicidencia);
				$respuesta=$nuevo['sql'];
			}
		echo json_encode($respuesta);
		
		break;
		case 'cancelarTemporal':
		//@Objetivo: cancelar el archivo temporal , cuando cancelamos un temporal muestra de primeros una alert
		//donde aceptamos en caso de querrer eliminar, a  continuación dependiendo de del archivo donde estemos
		//situados ejecuta su función 
		//@Retorno: en principio devuelve un array vacio a no ser que se tenga un error en la función ejecutada
			$idTemporal=$_POST['idTemporal'];
			$dedonde=$_POST['dedonde'];
			$respuesta=array();
			switch($dedonde){
				case 'pedidos':
					$cancelar=cancelarPedido( $idTemporal, $BDTpv);
					$respuesta=$cancelar;
				break;
				case 'albaran':
					$cancelar=cancelarAlbaran( $idTemporal, $BDTpv);
					$respuesta=$cancelar;
				break;
				case 'factura':
					$cancelar=cancelarFactura( $idTemporal, $BDTpv);
					$respuesta=$cancelar;
				break;
			 }
			 echo json_encode($respuesta);
		break;
		
	
}
?>
