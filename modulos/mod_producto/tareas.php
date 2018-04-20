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
		$productos=$_POST['productos'];
		$idTienda=$_POST['idTienda'];
		$dedonde="Etiqueta";
		$nombreTmp=$dedonde."etiquetas.pdf";
		$imprimir=mostarImprirmiA9($productos, $BDTpv, $idTienda);
		$cabecera=$imprimir['cabecera'];
		$html=$imprimir['html'];
		 //~ $ficheroCompleto=$html;
		require_once('../../lib/tcpdf/tcpdf.php');
		include ('../../clases/imprimir.php');
		include('../../controllers/planImprimirRe.php');
		$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
	
		echo json_encode($ficheroCompleto);
	break;
	
	case 'HtmlCajaBuscarProveedor':
		$resultado 		= array();
		$dedonde 		= $_POST['dedonde'];
		$busqueda =  $_POST['busqueda']; // Este valor puede venir vacio , por lo que...
		$DescartIdsProv = $_POST['idsProveedores']; // Descartamos los ids de los proveedores que ya tiene el producto.
													// para que no pueda seleccionadlor.
		if ($busqueda !==''){
			// Realizamos la busqueda todos los proveedores menos los que tiene añadidos en el producto..
			$proveedores = $CProveedor->buscarProveedorNombre($busqueda);
			// Ahora tengo que quitar del array proveedores[datos], aquellos que no ya estan añadidos para que no se muestre.
			$descartado = array();

			foreach ($proveedores['datos'] as $key=>$proveedor){
				$idProveedor = $proveedor['idProveedor'];
				if (in_array ($idProveedor,$DescartIdsProv)){
					$descartados[] = $idProveedor;
					unset($proveedores['datos'][$key]);
				};
			}
		} else {
			$proveedores = array();
			$proveedores['datos'] = array(); // ya enviamos datos... :-)
		}
		$resultado = htmlBuscarProveedor($busqueda,$dedonde,$proveedores['datos']);
		$resultado['proveedores'] = $proveedores;
		$resultado['busqueda'] = $busqueda;
		$resultado['descartados'] = $descartados;

		echo json_encode($resultado);
	break;
	
}


 
?>
