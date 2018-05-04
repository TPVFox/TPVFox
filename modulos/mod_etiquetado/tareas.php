<?php 

$pulsado = $_POST['pulsado'];

include_once ("./../../configuracion.php");
include_once ("./../mod_conexion/conexionBaseDatos.php");
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
		$productos=$_POST['productos'];
		$numProd=count($productos);
		
		$htmlProductos=repetirLineasProducto($unidades, $idProducto, $BDTpv, $idTienda, $fechaCad, $numProd);
		//~ $respuesta['datos']=$htmlProductos['datos'];
		$respuesta['numProd']=$numProd;
		$respuesta['productos']=$htmlProductos['productos'];
		$respuesta['html']=$htmlProductos['html'];
	break;
	
}
 echo json_encode($respuesta);
 return $respuesta;
?>
