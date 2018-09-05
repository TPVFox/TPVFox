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
                $arrayDatos=array();
                $datosProductoTpv=$NCArticulo->GetProducto($comprobar[0]['idArticulo']);
                
                $comprobacion=comparacionesProductos($producto, $datosProductoTpv['datos']);
                if($comprobacion==1){
                    array_push($arrayDatos, $producto);
                    array_push($arrayDatos,$datosProductoTpv);
                    array_push($productosModificados, $arrayDatos);
                }
                
            }else{
                array_push($productosNuevos, $producto);
            }
        }
        if(count ($productosNuevos)>0){
            $htmlNuevos=lineaProductosNuevos($productosNuevos);
            $respuesta['htmlNuevos']=$htmlNuevos;
        }
        if(count($productosModificados)>0){
            $htmlMod=lineaProductosModificador($productosModificados);
            $respuesta['htmlMod']=$htmlMod;
        }
       $respuesta['productosModificados']=$productosModificados;
       $respuesta['productosNuevos']=$productosNuevos;
    break;
    
    
}
echo json_encode($respuesta);
?>
