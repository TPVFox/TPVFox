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
		$numProd=0;
		if(isset($_POST['productos'])){
			$numProd=count($_POST['productos']);
		}
		$htmlProductos=repetirLineasProducto($unidades, $idProducto, $BDTpv, $idTienda, $fechaCad, $numProd);
		$respuesta['numProd']=$numProd;
		$respuesta['productos']=$htmlProductos['productos'];
		$respuesta['html']=$htmlProductos['html'];
	break;
	case 'addEtiquetadoTemporal':
		$idTemporal=0;
		$productos=array();
		if(isset($_POST['productos'])){
			$productos=$_POST['productos'];
		}
		$productos=json_decode($productos);
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
				$respuesta['Nitem']=1;
				$respuesta['datos']=$buscarId;
			}
		}else{
			$buscarTodo=$CArticulos->buscarPorNombre($valor, $idTienda);
			if(($buscarTodo['error'])){
				$respuesta['error']=$buscarTodo['error'];
				$respuesta['consulta']=$buscarTodo['consulta'];
			}else{
				$html=htmlProductos($valor, $buscarTodo);
				$respuesta['html']=$html['html'];
			}
		}
	
	break;
	
	
}
 echo json_encode($respuesta);
 return $respuesta;
?>
