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
                
                $comprobacion=comparacionesProductos($producto, $datosProductoTpv);
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
            $htmlMod=lineaProductosModificador($productosModificados,  $idTienda);
            $respuesta['htmlMod']=$htmlMod;
        }
       $respuesta['productosModificados']=$productosModificados;
       $respuesta['productosNuevos']=$productosNuevos;
    break;
    case 'modificarProducto':
        $datos=array(
            'nombre'=>$_POST['nombre'],
            'refTienda'=>$_POST['refTienda'],
            'iva'=>$_POST['iva'],
            'precioSiva'=>$_POST['precioSiva'],
            'codBarras'=>$_POST['codBarras'],
            'id'=>$_POST['id']
        );
        $modificar=$NCArticulo->modificarProductoTPVWeb($datos);
        $respuesta['modificar']=$modificar;
    break;
    case 'addProductoTpv':
        $optPublicado=$_POST['optPublicado'];
        switch ($optPublicado) {
            case '1':
                $estado="Activo";
            break;
            case '2':
                $estado="Nuevo";
            break;
            case '3':
                $estado="Temporal";
            break;
            case '4':
                $estado="Baja";
            break;
            case '5':
                $estado="Importado";
            break;
        }
        
    
    break;
    
    
    
}
echo json_encode($respuesta);
?>
