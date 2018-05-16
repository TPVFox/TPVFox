<?php

/* Fichero de tareas a realizar.
 * 
 * 
 * Con el switch al final y variable $pulsado
 * 
 *
 * 
 * OPTIMIZADO DE CARALLO!!! : se instancian al inicio, 
 * objetos innecesarios para todas las opctiones del case
 * 
 * 
 *   
 */




/* ===============  REALIZAMOS CONEXIONES  =============== */


$pulsado = $_POST['pulsado'];

include_once ("./../../configuracion.php");

// Crealizamos conexion a la BD Datos
include_once ("./../mod_conexion/conexionBaseDatos.php");


$rutaCompleta = $RutaServidor . $HostNombre;
include_once($rutaCompleta . '/clases/ClaseSession.php');

$CSession = new ClaseSession();

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
$CArticulo = new Articulos($BDTpv);

include_once ('../mod_compras/clases/albaranesCompras.php');
$CAlbaran = new AlbaranesCompras($BDTpv);

include_once('../../clases/Proveedores.php');
$CProveedor = new Proveedores($BDTpv);

include_once './clases/ClaseFamilias.php';
include_once './clases/ClaseArticulos.php';
include_once './funciones_mayor.inc.php';

switch ($pulsado) {

    case 'leerFamilias':
        $idpadre = $_POST['idpadre'];
        $resultado = [];
        $resultado['padre'] = $idpadre;
        if ($idpadre >= 0) {
            $familias = (new ClaseFamilias($BDTpv))->leerUnPadre($idpadre);
        } else {
            $familias['datos'] = [];
        }
        $resultado['datos'] = $familias['datos'];
        $resultado['html'] = familias2Html($familias['datos']);
        break;

    case 'BuscaNombreFamilia':
        $nombre = $_POST['nombre'];

        $resultado = [];
        $familias = (new ClaseFamilias($BDTpv))->buscaXNombre($nombre);
        if (!isset($familias['error'])) {
            $datos = $familias['datos'];
            foreach ($datos as $dato) {
                $resultado [] = ['label' => $dato['familiaNombre'], 'valor' => $dato['idFamilia']];
            }
        } else {
            $resultado = $familias['error'];
        }
        break;

    case 'grabarFamilia':

        // comprobar datos en el lado servidor    
        $idpadre = $_POST['idpadre'];
        $nombre = $_POST['nombre'];
        // Que no estan vacios
        // que idpadre es >= 0 y un id existente
        // generar $resultado['error']

        $resultado = [];
        $resultado['familiaPadre'] = $idpadre;
        $resultado['familiaNombre'] = $nombre;
        if ($idpadre >= 0) {
            $familia = new ClaseFamilias($BDTpv);
            $resultado['insert'] = $familia->grabar($resultado);
        }
        break;

    case 'grabarPadres':
        $idpadre = $_POST['idpadre'];
        $idsfamilia = $_POST['idsfamilia'];
        $resultado = (new ClaseFamilias($BDTpv))->actualizarpadre($idpadre, $idsfamilia);
        break;

    case 'imprimemayor':

        $idArticulo = $_POST['idproducto'];
        $stockinicial = $_POST['stockinicial'];
        $fechainicio = $_POST['fechainicio'];
        $fechafinal = $_POST['fechafin'];


        // Validar en el lado del servidor. Si no hay fechas o el stock es alfanumerico o el articulo no existe
        // mensaje de error

        $resultado = [];
        $articulo = new alArticulos($BDTpv);
        if ($articulo->existe($idArticulo)) {
            $miarticulo = $articulo->leer($idArticulo);
            $nombreArticulo = $miarticulo['datos'][0]['articulo_name'];

            $sqldata = $articulo->calculaMayor(compact("fechainicio", "fechafinal", "idArticulo"));

            if ($sqldata['datos']) {
                $sumastock = $stockinicial;
                foreach ($sqldata['datos'] as $indice => $linea) {
                    $sumastock += $linea['entrega'] - $linea['salida'];
                    $sqldata['datos'][$indice]['stock'] = $sumastock;
                }
                $cabecera = cabeceramayor2html(['titulo' => 'Mayor productos'
                    , 'empresa' => '01 Alimentaria Longueicap 2018'
                    , 'condiciones' => 'Periódo: ' . $fechainicio . ' / ' . $fechafinal
                ]);
                $cuerpo = datamayor2html($sqldata['datos']);
                $pdf = new imprimirPDF();
                $pdf->SetFont(PDF_FONT_NAME_MAIN, '', 8);
                $pdf->SetMargins(10, 70, 10);
                $pdf->setCabecera($cabecera);
                $pdf->AddPage();
                $pdf->writeHTML($cuerpo);
                $fichero = 'mayor'.time().'.pdf';
                $filename = $RutaServidor.$rutatmp.'/'.$fichero;
                $pdf->Output($filename, 'F');

                $resultado['html'] = $cuerpo;
                $resultado['fichero'] = $rutatmp.'/'.$fichero;
            } else {
                $resultado = 'Error en "sqldata"';
            }
        } else {
            $resultado = 'No existe articulo';
        }
        break;

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
		require_once('../../lib/tcpdf/tcpdf.php');
		include ('../../clases/imprimir.php');
		include('../../controllers/planImprimirRe.php');
		$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
		$respuesta['fichero']=$ficheroCompleto;
		//~ echo json_encode($ficheroCompleto);
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
		include('./tareas/imprimirEtiquetas.php');
	break;
	
	case 'productosSesion':
		$respuesta=array();
		$session = $CSession->GetSession();
		$respuesta=productosSesion($_POST['id']);
		break;
	
	case 'HtmlCajaBuscarProveedor':
		include ('./tareas/htmlCajaBuscarProveedor.php');
	break;
	
	case 'eliminarSeleccion':
		$eliminar=eliminarSeleccion();
	break;
	
	case 'obtenerCostesProveedor':
		include('./tareas/obtenerCostesProveedor.php');
	break;
	
}

        echo json_encode($resultado);
