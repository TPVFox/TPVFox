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
    case 'comprobarProductos':
        $productos=$_POST['productos'];
        $idTienda=$_POST['idTienda'];
        $productosModificados=array();
        $productosNuevos=array();
       
        foreach ($productos as $producto){
            
            $comprobar=$NCArticulo->comprobarIdWebTpv($idTienda, $producto['id']);
            
            if(isset($comprobar[0])){
                array_push($productosModificados, $producto);
            }else{
                array_push($productosNuevos, $producto);
            }
        }
       $respuesta['productosModificados']=$productosModificados;
       $respuesta['productosNuevos']=$productosNuevos;
    break;
    
    
}
echo json_encode($respuesta);
?>
