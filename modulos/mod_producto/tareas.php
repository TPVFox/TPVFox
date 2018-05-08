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
include_once ("./../../inicial.php");


$rutaCompleta = $RutaServidor.$HostNombre;
include_once($rutaCompleta.'/clases/ClaseSession.php');

$CSession =  new ClaseSession();

// Incluimos controlador.
include ("./../../controllers/Controladores.php");
$Controler = new ControladorComun; 
// Incluimos funciones
include_once ("./funciones.php");
// Añado la conexion a controlador.
$Controler->loadDbtpv($BDTpv);
// Nueva clase 
include ("./clases/ClaseProductos.php");
$NCArticulo = new ClaseProductos($BDTpv);

include_once('../../clases/articulos.php');
$CArticulo=new Articulos($BDTpv);

include_once ('../mod_compras/clases/albaranesCompras.php');
$CAlbaran=new AlbaranesCompras($BDTpv);

include_once('../../clases/Proveedores.php');
$CProveedor=new Proveedores($BDTpv);

switch ($pulsado) {

	case 'HtmlLineaCodigoBarras';
	$item=$_POST['fila'];
		$respuesta = array();
		$res 	= HtmlLineaCodigoBarras($item);
		$respuesta['html'] =$res;
		echo json_encode($respuesta);
		break;
		
	case 'Grabar_configuracion':
		// Grabamos configuracion
		$configuracion = $_POST['configuracion'];
		// Ahora obtenemos nombre_modulo y usuario , lo ponermos en variable y quitamos array configuracion.
		$nombre_modulo = $configuracion['nombre_modulo'];
		$idUsuario = $configuracion['idUsuario'];
		unset($configuracion['nombre_modulo'],$configuracion['idUsuario']);
		
		$respuesta = $Controler->GrabarConfiguracionModulo($nombre_modulo,$idUsuario,$configuracion);		
		$respuesta['configuracion'] = $configuracion ; 
		
		echo json_encode($respuesta);
		break;
		
	case 'eliminarCoste':
		$idArticulo=$_POST['idArticulo'];
		$dedonde=$_POST['dedonde'];
		$id=$_POST['id'];
		$tipo=$_POST['tipo'];
		$estado="Sin Cambios";
		//~ $respuesta['idArticulo'];
		$mod=$CArticulo->modEstadoArticuloHistorico($idArticulo, $id, $dedonde, $tipo, $estado);
		$respuesta['sql']=$mod;
		echo json_encode($respuesta);
		break;
		
	case 'retornarCoste':
		$idArticulo=$_POST['idArticulo'];
		$dedonde=$_POST['dedonde'];
		$id=$_POST['id'];
		$tipo=$_POST['tipo'];
		$estado="Pendiente";
		//~ $respuesta['idArticulo'];
		$mod=$CArticulo->modEstadoArticuloHistorico($idArticulo, $id, $dedonde, $tipo, $estado);
		$respuesta['sql']=$mod;
		echo json_encode($respuesta);
		break;
		
	case 'imprimir':
		$id=$_POST['id'];
		
		$dedonde="Recalculo";
		$nombreTmp=$dedonde."recalculo.pdf";
		//~ $htmlImprimir['cabecera']="";
		if ($_POST['bandera']==1){
			$htmlImprimir=montarHTMLimprimir($id, $BDTpv, $dedonde, $CArticulo, $CAlbaran, $CProveedor);
		}else{
			$dedonde="albaran";
			$htmlImprimir=montarHTMLimprimirSinGuardar($id, $BDTpv, $dedonde, $CArticulo, $CAlbaran, $CProveedor);
			
		}
		$cabecera=$htmlImprimir['cabecera'];
		$html=$htmlImprimir['html'];
		require_once('../../lib/tcpdf/tcpdf.php');
		include ('../../clases/imprimir.php');
		include('../../controllers/planImprimirRe.php');
		$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
		echo json_encode($ficheroCompleto);
	break;
	
	case 'ComprobarSiExisteCodbarras':
		$resultado = array();
		$codBarras = $_POST['codBarras'];
		$resultado = $NCArticulo->GetProductosConCodbarras($codBarras);
		
		echo json_encode($resultado);
	break;
	case 'productosSesion':
		$idProducto=$_POST['id'];
		$respuesta=array();
		$respuesta=productosSesion($idProducto);
		//~ if(count($respuesta['productos']>0)){
			//~ $respuesta['Nitems']=1;
		//~ }else{
			//~ $respuesta['Nitems']=0;
		//~ }
			echo json_encode($respuesta);
	break;
	case 'imprimirEtiquetas':
		$respuesta = array();
		$IdsProductos=json_decode($_POST['productos']);
		$idTienda=$_POST['idTienda'];
		$tamano=$_POST['tamano'];
		$productos = array();
		foreach ($IdsProductos as $id){
			$productos[]= $NCArticulo->getProducto($id);	
		}
		$dedonde="Etiqueta";
		$nombreTmp=$dedonde."etiquetas.pdf";
		switch ($tamano){
			case 1:
				$imprimir=ImprimirA8($productos);
			break;
			case 2:
				$imprimir=ImprimirA5($productos);
			break;
			case 3:
				$imprimir=ImprimirA7($productos);
			break;
		}
		
		$cabecera=$imprimir['cabecera'];
		$html=$imprimir['html'];
		 $ficheroCompleto=$html;
		$ficheroCompleto=$html;
		require_once('../../lib/tcpdf/tcpdf.php');
		include ('../../clases/imprimir.php');
		include('../../controllers/planImprimirRe.php');
		$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
		$respuesta['html']=$html;
		$respuesta['fichero'] = $ficheroCompleto;
		$respuesta['productos'] = $productos;
		echo json_encode($respuesta);
	break;
	
	case 'productosSesion':
		$idProducto=$_POST['id'];
		$respuesta=array();
		$session = $CSession->GetSession();
		$respuesta=productosSesion($idProducto);

		echo json_encode($respuesta);
	break;
	
	case 'HtmlCajaBuscarProveedor':
		$resultado 		= array();
		$dedonde 		= 'producto';
		$busqueda =  $_POST['busqueda']; // Este valor puede venir vacio , por lo que...
		$DescartIdsProv = $_POST['idsProveedores']; // Descartamos los ids de los proveedores que ya tiene el producto.
													// para que no pueda seleccionadlor.
		$descartados = array();
		if ($busqueda !==''){
			// Realizamos la busqueda todos los proveedores menos los que tiene añadidos en el producto..
			$proveedores = $CProveedor->buscarProveedorNombre($busqueda);
			// Ahora tengo que quitar del array proveedores[datos], aquellos que no ya estan añadidos para que no se muestre.
			foreach ($proveedores['datos'] as $key=>$proveedor){
				$idProveedor = $proveedor['idProveedor'];
				if (in_array ($idProveedor,$DescartIdsProv)){
					$descartados[] = $proveedor;
					unset($proveedores['datos'][$key]);
				};
			}
		} else {
			$proveedores = array();
			$proveedores['datos'] = array(); // ya enviamos datos... :-)
		}
		$resultado = htmlBuscarProveedor($busqueda,$dedonde,$proveedores['datos'],$descartados);
		$resultado['proveedores'] = $proveedores;
		$resultado['busqueda'] = $busqueda;
		$resultado['descartados'] = $descartados;
		
		echo json_encode($resultado);
	break;
	
	case 'eliminarSeleccion':
		$eliminar=eliminarSeleccion();
	break;
	
	case 'obtenerCostesProveedor':
		$resultado = array();
		$idProveedor = $_POST['idProveedor'];
		$idProducto = $_POST['idProducto'];
		// Compruebo que realmente no tenga coste para ese producto es proveedor.
		$comprobarCosteProveedor = $NCArticulo->ObtenerCostesDeUnProveedor($idProducto,$idProveedor);
		if (isset($comprobarCosteProveedor['error'])){
			// Quiere decir que realmente no encontro registros articuloProveedor para ese producto y proveedor.
			// Buscarmos datos para ese proveedor.
			$proveedores= $CProveedor->buscarProveedorId($idProveedor);
		} else {
			$resultado['error'] = $comprobarCosteProveedor['error'];
		}
		if ( count($proveedores) >0 && (!isset($resultado['error'])) ){
			//Quiere decir que fue correcto, obtuvimos un proveedor
			// montamos array de proveedor para enviar.
			$proveedor = $proveedores;
			$proveedor['fechaActualizacion']= date("Y-m-d H:i:s");
			$proveedor['estado']			= 'Nuevo';
			$proveedor['coste']				= '0.00' ; // Debería ser el ultimo coste... 
			$htmlFilaProveedor = htmlLineaProveedorCoste($proveedor);
			$resultado['htmlFilaProveedor'] = $htmlFilaProveedor ;
			$resultado['proveedores'] = $proveedores;
			$resultado['proveedor'] = $proveedor;


		}	else {
			$resultado['error'] ='Error se obtuvo mas de un proveedor no es posible';
			$resultado['proveedores'] = $proveedores;
			
		}
	echo json_encode($resultado);
	break;
	
}


 
?>
