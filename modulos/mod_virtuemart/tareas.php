<?php 

$pulsado = $_POST['pulsado'];
include_once ("./../../inicial.php");
include_once $URLCom.'/configuracion.php';
include_once $URLCom.'/modulos/mod_virtuemart/funciones.php';

include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
include_once $URLCom.'/modulos/mod_tienda/clases/ClaseTienda.php';
$Ctienda=new ClaseTienda($BDTpv);
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
                $respuesta['productostpv']=$datosProductoTpv;
                $comprobacion=comparacionesProductos($producto, $datosProductoTpv, $idTienda);
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
        $codBarras=array();
        $precioCiva=0;
        $estadoWeb="Publicado";
        if($_POST['optCodBarra']==1){
             $codBarrasTexto=explode(";",$_POST['codBarras']);
             foreach($codBarrasTexto as $cod){
                 array_push($codBarras , $cod);
             }
        }
        $iva=$_POST['iva']/100;
        $precioCiva=$iva+$_POST['precioSiva'];
        $tiendaPrincipal=$Ctienda->tiendaPrincipal();
       $tiendaPrincipal=$tiendaPrincipal['datos'][0]['idTienda'];
        $datos=array(
            'nombre'=>$_POST['nombre'],
            'refTienda'=>$_POST['refTienda'],
            'iva'=>$_POST['iva'],
            'precioSiva'=>$_POST['precioSiva'],
            'codBarras'=>$codBarras,
            'id'=>$_POST['id'],
            'precioCiva'=>$precioCiva,
            'tiendaPrincipal'=>$tiendaPrincipal,
            'optRefWeb'=>$_POST['optRef'],
            'tiendaWeb'=>$_POST['tiendaWeb'],
        );
        $modificar=$NCArticulo->modificarProductoTPVWeb($datos);
        $respuesta['modificar']=$modificar;
    break;
    case 'addProductoTpv':
        $ultimoCoste=0;
        $precioCiva=0;
        $codBarras=array();
        $tiendaPrincipal=$Ctienda->tiendaPrincipal();
       $tiendaPrincipal=$tiendaPrincipal['datos'][0]['idTienda'];
        if($_POST['ultimoCoste']==1){
            $beneficio=$_POST['beneficio']/100;
            $respuesta['beneficio']=$_POST['beneficio'];
            $ultimoCoste=$_POST['precioSiva']-$beneficio;
        }
        if($_POST['optCodBarra']==1){
             $codBarrasTexto=explode(";",$_POST['codBarras']);
             foreach($codBarrasTexto as $cod){
                 array_push($codBarras , $cod);
             }
        }
        $iva=$_POST['iva']/100;
        $precioCiva=$iva+$_POST['precioSiva'];
       $tiendaPrincipal=$Ctienda->tiendaPrincipal();
       $tiendaPrincipal=$tiendaPrincipal['datos'][0]['idTienda'];
       $respuesta['tienda']=$tiendaPrincipal;
       $estadoWeb="Publicado";
        $datosProducto=array( 
             'nombre'=>$_POST['nombre'],
              'iva'=>$_POST['iva'],
              'id'=>$_POST['id'],
              'ultimoCoste'=>$ultimoCoste,
              'beneficio'=>$_POST['beneficio'],
              'estado'=>$_POST['optEstado'],
              'costePromedio'=>$_POST['costePromedio'],
              'codBarras'=>$codBarras,
              'precioCiva'=>$precioCiva,
              'precioSiva'=>$_POST['precioSiva'],
              'tiendaPrincipal'=>$tiendaPrincipal,
              'tiendaWeb'=>$_POST['tiendaWeb'],
              'optRefWeb'=>$_POST['optRefWeb'],
              'refTienda'=>$_POST['refTienda'],
              'estadoWeb'=>$estadoWeb
              
        );
        $add=$NCArticulo->addProductoWebTPV($datosProducto);
        $respuesta['add']=$add;
        $respuesta['datos']=$datosProducto;
        
        
       
        
    
    break;
    
    
    
}
echo json_encode($respuesta);
?>
