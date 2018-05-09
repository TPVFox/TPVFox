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
		$respuesta = array();
		$item=$_POST['fila'];
		$respuesta['html']	= HtmlLineaCodigoBarras($item);
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
		$estado="Sin Cambios";
		$mod=$CArticulo->modEstadoArticuloHistorico($idArticulo, $id, $dedonde, $tipo, $estado);
		$respuesta['sql']=$mod;
		break;
		
	case 'retornarCoste':
		$idArticulo=$_POST['idArticulo'];
		$dedonde=$_POST['dedonde'];
		$id=$_POST['id'];
		$tipo=$_POST['tipo'];
		$estado="Pendiente";
		$mod=$CArticulo->modEstadoArticuloHistorico($idArticulo, $id, $dedonde, $tipo, $estado);
		$respuesta['sql']=$mod;
		break;
		
	case 'imprimir':
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
		require_once('../../lib/tcpdf/tcpdf.php');
		include ('../../clases/imprimir.php');
		include('../../controllers/planImprimirRe.php');
		$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
		echo json_encode($ficheroCompleto);
	break;
	
	case 'ComprobarSiExisteCodbarras':
		$respuesta = array();
		$codBarras = $_POST['codBarras'];
		$respuesta = $NCArticulo->GetProductosConCodbarras($codBarras);
	break;
	
	case 'productosSesion':
		$respuesta=array();
		$idProducto=$_POST['id'];
		$respuesta=productosSesion($idProducto);
		
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
		require_once('../../lib/tcpdf/tcpdf.php');
		include ('../../clases/imprimir.php');
		include('../../controllers/planImprimirRe.php');
		$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
		$respuesta['html']=$html;
		$respuesta['fichero'] = $ficheroCompleto;
		$respuesta['productos'] = $productos;
	break;
	
	case 'productosSesion':
		$respuesta=array();
		$idProducto=$_POST['id'];
		$session = $CSession->GetSession();
		$respuesta=productosSesion($idProducto);
		break;
	
	case 'HtmlCajaBuscarProveedor':
		include ('./tareas/htmlCajaBuscarProveedor.php');
	break;
	
	case 'eliminarSeleccion':
		$eliminar=eliminarSeleccion();
	break;
	
	case 'obtenerCostesProveedor':
		include('.tareas/obtenerCostesProveedor.php');
	break;
	
}
echo json_encode($respuesta);
?>
