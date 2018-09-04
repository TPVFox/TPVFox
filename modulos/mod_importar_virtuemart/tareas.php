<?php 

$pulsado = $_POST['pulsado'];
include_once ("./../../inicial.php");
include_once $URLCom.'/configuracion.php';
include_once $URLCom.'/modulos/mod_importar_virtuemart/funciones.php';

include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
$NCArticulo = new ClaseProductos($BDTpv);
switch ($pulsado) {
    case 'contarProductostpv':
        $productosTpv=$NCArticulo->contarProductosTpv();
        $respuesta['productosTpv']=$productosTpv;
    break;
    case 'nuevosEnTpv':
        $idTienda=$_POST['tiendaWeb'];
        $productosTpv=$NCArticulo->productosEnTpvNoWeb($idTienda);
        $respuesta['productos']=$productosTpv;
    break;
    case 'nuevosEnWeb':
         $idTienda=$_POST['tiendaWeb'];
         $productosWeb=$NCArticulo->productosTienda($idTienda);
          $respuesta['productos']=$productosWeb;
    break; 
    
    
}
echo json_encode($respuesta);
?>
