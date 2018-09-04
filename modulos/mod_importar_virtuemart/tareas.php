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
    
    
    
}
echo json_encode($respuesta);
?>
