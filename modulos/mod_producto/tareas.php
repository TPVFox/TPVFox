<?php
/* Fichero de tareas a realizar.
 * 
 * 
 * Con el switch al final y variable $pulsado
 * 
 *  */
/* ===============  REALIZAMOS CONEXIONES  ===============*/


$pulsado = $_POST['pulsado'];
include_once ("./../../inicial.php");
include_once $URLCom.'/configuracion.php';

// Crealizamos conexion a la BD Datos

$rutaCompleta = $RutaServidor.$HostNombre;
include_once($rutaCompleta.'/clases/ClaseSession.php');

$CSession =  new ClaseSession();

// Incluimos controlador.
include_once $URLCom.'/controllers/Controladores.php';
$Controler = new ControladorComun; 
// Incluimos funciones
include_once $URLCom.'/modulos/mod_producto/funciones.php';
// Añado la conexion a controlador.
$Controler->loadDbtpv($BDTpv);
// Nueva clase 
include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
$NCArticulo = new ClaseProductos($BDTpv);
include_once $URLCom.'/clases/articulos.php';
$CArticulo=new Articulos($BDTpv);
include_once $URLCom.'/modulos/mod_compras/clases/albaranesCompras.php';
$CAlbaran=new AlbaranesCompras($BDTpv);
include_once $URLCom.'/clases/Proveedores.php';
$CProveedor=new Proveedores($BDTpv);
include_once $URLCom.'/modulos/mod_familia/clases/ClaseFamilias.php';
$CFamilia=new ClaseFamilias($BDTpv);
switch ($pulsado) {

	case 'HtmlLineaCodigoBarras';
		$respuesta = array();
		$respuesta['html']	= HtmlLineaCodigoBarras($_POST['fila']);
		break;
		
	case 'Grabar_configuracion':
		$respuesta = array();
		// Grabamos configuracion
		$configuracion = $_POST['configuracion'];
		// Ahora obtenemos nombre_modulo y usuario , lo ponermos en variable y quitamos array configuracion.
		$nombre_modulo = $configuracion['nombre_modulo'];
		$idUsuario = $configuracion['idUsuario'];
		unset($configuracion['nombre_modulo'],$configuracion['idUsuario']);
		
		$respuesta = $Controler->GrabarConfiguracionModulo($nombre_modulo,$idUsuario,$configuracion);		
		$respuesta['configuracion'] = $configuracion ; 
		
		break;
		
	case 'eliminarCoste':
		$respuesta = array();
		$idArticulo=$_POST['idArticulo'];
		$dedonde=$_POST['dedonde'];
		$id=$_POST['id'];
		$tipo=$_POST['tipo'];
		$mod=$CArticulo->modEstadoArticuloHistorico($idArticulo, $id, $dedonde, $tipo,'Sin Cambios');
		$respuesta['sql']=$mod;
		break;
		
	case 'retornarCoste':
		$idArticulo=$_POST['idArticulo'];
		$dedonde=$_POST['dedonde'];
		$id=$_POST['id'];
		$tipo=$_POST['tipo'];
		$mod=$CArticulo->modEstadoArticuloHistorico($idArticulo, $id, $dedonde, $tipo, 'Pendiente');
		$respuesta['sql']=$mod;
		break;
		
	case 'imprimir':
		$respuesta = array();
		// De momento no puedo pasar a tareas ya devuelve un fichero ... 
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
        require_once $URLCom.'/lib/tcpdf/tcpdf.php';
		include_once $URLCom.'/clases/imprimir.php';
        include_once $URLCom.'/controllers/planImprimirRe.php';
		
		$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
		$respuesta['fichero']=$ficheroCompleto;
		
	break;
	
	case 'ComprobarSiExisteCodbarras':
		$respuesta = array();
		$respuesta = $NCArticulo->GetProductosConCodbarras($_POST['codBarras']);
	break;
	
	case 'productosSesion':
		$respuesta=array();
		$respuesta=productosSesion($_POST['id']);
		
	break;
	
	case 'imprimirEtiquetas':
        include_once $URLCom.'/modulos/mod_producto/tareas/imprimirEtiquetas.php';
	break;
	
	case 'HtmlCajaBuscarProveedor':
        include_once $URLCom.'/modulos/mod_producto/tareas/htmlCajaBuscarProveedor.php';
	break;
	
	case 'eliminarSeleccion':
		$eliminar=eliminarSeleccion();
	break;
	
	case 'obtenerCostesProveedor':
        include_once $URLCom.'/modulos/mod_producto/tareas/obtenerCostesProveedor.php';
	break;
	case 'comprobarReferencia':
		$referencia=$_POST['referencia'];
		$comprobacion=$NCArticulo->buscarReferenciaProductoTienda( $referencia);
		$respuesta=$comprobacion;
	break;
    case 'modalFamiliaProducto':
        $idProducto=$_POST['idProducto'];
        $familias=$CFamilia->todoslosPadres();
        $modal=modalAutocompleteFamilias($familias['datos'], $idProducto);
        $respuesta['familias']=$familias;
        $respuesta['html']=$modal;
    break;
    case 'guardarProductoFamilia':
    $idProducto=$_POST['idProducto'];
    $idFamilia=$_POST['idfamilia'];
    $add=$CFamilia->guardarProductoFamilia($idProducto, $idFamilia);
    break;
}
echo json_encode($respuesta);
?>
