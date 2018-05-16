<?php 

$pulsado = $_POST['pulsado'];

include_once ("./../../configuracion.php");
include_once ("./../../inicial.php");
include_once ("./funciones.php");
include_once "../../clases/articulos.php";
include_once "clases/modulo_etiquetado.php";

$CArticulos=new Articulos($BDTpv);
$CEtiquetado= new Modulo_etiquetado($BDTpv);

$respuesta=array();
switch ($pulsado) {
	case 'repetirProductos':
		$unidades=$_POST['unidades'];
		$idProducto=$_POST['idProducto'];
		$idTienda=$_POST['idTienda'];
		$fechaCad=$_POST['fechaCad'];
		$tipo=$_POST['tipo'];
		$numProd=0;
		if(isset($_POST['productos'])){
			$numProd=count($_POST['productos']);
		}
		$htmlProductos=repetirLineasProducto($unidades, $idProducto, $BDTpv, $idTienda, $fechaCad, $numProd, $tipo);
		$respuesta['numProd']=$numProd;
		$respuesta['productos']=$htmlProductos['productos'];
		$respuesta['html']=$htmlProductos['html'];
	break;
	case 'addEtiquetadoTemporal':
		$idTemporal=0;
		$idReal=0;
		$existe=0;
		$productos=array();
		if(isset($_POST['productos'])){
			$productos=$_POST['productos'];
		}
		if(isset($_POST['idReal'])){
			$idReal=$_POST['idReal'];
		}
		$productos=json_encode($productos, true);
		$respuesta['productos']=$productos;
		if(isset($_POST['idTemporal'])){
			$idTemporal=$_POST['idTemporal'];
		}
		if($idTemporal>0){
			//MOdificar temporal
			$modif=$CEtiquetado->modificarTemporal($_POST, $productos, $idTemporal);
			if(isset($modif['error'])){
				$respuesta['consulta']=$modif['consulta'];
			}
		}else{
			//crear temporal y devolver idTemporal
			$nuevo=$CEtiquetado->addTemporal($_POST, $productos);
			if(isset($nuevo['error'])){
				$respuesta['consulta']=$nuevo['consulta'];
			}else{
				$idTemporal=$nuevo['id'];
			}
			
		}
		if($idReal>0){
			$modReal=$CEtiquetado->modifEstadoReal("Sin guardar", $idReal);
			if(isset($modReal['error'])){
				$respuesta['consulta']=$nuevo['consulta'];
			}
		}
		$respuesta['existe']=$existe;
		$respuesta['idTemporal']=$idTemporal;
		$respuesta['productos']=$_POST['productos'];
	
	break;
	case 'buscarProducto':
		$valor=$_POST['valor'];
		$caja=$_POST['caja'];
		$idTienda=$_POST['idTienda'];
		if($caja=='id_producto'){
			$buscarId=$CArticulos->datosArticulosPrincipal($valor, $idTienda);
			if(isset($buscarId['error'])){
				$respuesta['error']=$buscarId['error'];
				$respuesta['consulta']=$buscarId['consulta'];
			}else{
				$comprobar = strpos($buscarId['crefTienda'],'-');
				if($comprobar==false){
					$respuesta['Nitem']=1;
					$respuesta['datos']=$buscarId;
				}else{
					$respuesta['error']='Error';
					$respuesta['consulta']='La referencia del producto tiene que ser *****';
				}
				
			}
		}else{
			$buscarTodo=$CArticulos->buscarPorNombre($valor, $idTienda);
			if(isset($buscarTodo['error'])){
				$respuesta['error']=$buscarTodo['error'];
				$respuesta['consulta']=$buscarTodo['consulta'];
			}else{
				$html=htmlProductos($valor, $buscarTodo);
				$respuesta['html']=$html['html'];
			}
		}
	
	break;
	case 'modificarCodigoBarras':
		$tipo=$_POST['tipo'];
		$producto=$_POST['producto'];
		switch($tipo){
			case 1:
				$codigoBarras=codigoBarrasUnidades($producto['crefTienda'], $producto['peso']);
			break;
			case 2:
				$codigoBarras=codigoBarrasPeso($producto['crefTienda'],  $producto['peso']);
			break;
		}
		$respuesta['codBarras']=$codigoBarras;
	
	break;
	
	case 'contarEtiquetas':
		$lotes=$_POST['lotes'];
		$etiquetas=0;
		foreach($lotes as $lote){
			$etiquetaReal=$CEtiquetado->datosLote($lote);
			$productos=json_decode($etiquetaReal['productos'], true);
			$etiquetas=$etiquetas+count($productos);
		}
		$respuesta['etiquetas']=$etiquetas;
	break;
	case 'imprimirEtiquetas':
		$lotes=$_POST['lotes'];
		$nombreTmp="etiquetas.pdf";
		//~ $html=imprimirEtiquetas($lotes);
		include('../../lib/tcpdf/tcpdf.php');
		include ('../../clases/imprimir.php');
		include('../../controllers/planImprimirCodBarras.php');
		$ficheroCompleto=$rutatmp.'/'.$nombreTmp;
		$respuesta=$ficheroCompleto;
	
	break;
	
	
}
 echo json_encode($respuesta);
 return $respuesta;
?>
