<?php 

$pulsado = $_POST['pulsado'];
include_once ("./../../inicial.php");
include_once $URLCom.'/configuracion.php';
include_once $URLCom.'/modulos/mod_virtuemart/funciones.php';

include_once $URLCom.'/modulos/mod_virtuemart/clases/ClaseVirtuemart.php';
include_once $URLCom.'/modulos/mod_tienda/clases/ClaseTienda.php';
$Ctienda=new ClaseTienda($BDTpv);
$CVirtuemart = new ClaseVirtuemart($BDTpv);
switch ($pulsado) {
    case 'contarProductostpv':
    //@Objetivo_ contar los productos de tpv
        $productosTpv=$CVirtuemart->contarProductosTpv();
        $respuesta['productosTpv']=$productosTpv;
    break;
    case 'nuevosEnTpv':
     //@Objetivo_ contar los productos nuevo en tpv
        $idTienda=$_POST['tiendaWeb'];
        $productosTpv=$CVirtuemart->productosEnTpvNoWeb($idTienda);
        $respuesta['productos']=$productosTpv;
    break;
    case 'nuevosEnWeb':
     //@Objetivo_ contar los productos de web
         $idTienda=$_POST['tiendaWeb'];
         $productosWeb=$CVirtuemart->productosTienda($idTienda);
          $respuesta['productos']=$productosWeb;
    break; 
    case 'comprobarProductos':
    //@Objetivo: comprobaciones de productos
    //dividir en dos arrays uno los nuevos y otros los modificados
    //devolver los html
        $productos=$_POST['productos'];
        $idTienda=$_POST['idTienda'];
        $conf = array (
                'Sel_codBarras' => $_POST['sel_codigoBarras'],
                'Sel_referencia'=> $_POST['sel_referencia']
                );
        $productosModificados=array();
        $productosNuevos=array();
        $respuesta['productos']=$productos;
        foreach ($productos as $producto){
            if(isset($producto['id'])){
                $comprobar=$CVirtuemart->comprobarIdWebTpv($idTienda, $producto['id']);
                $respuesta['comprobar']=$comprobar;
                if(isset($comprobar[0])){
                    $arrayDatos=array();
                    $datosProductoTpv=$CVirtuemart->GetProducto($comprobar[0]['idArticulo']);
                    $respuesta['productostpv']=$datosProductoTpv;
                    $comprobacion=comparacionesProductos($producto, $datosProductoTpv, $idTienda,$conf);
                    if($comprobacion==1){
                        array_push($arrayDatos, $producto);
                        array_push($arrayDatos,$datosProductoTpv);
                        array_push($productosModificados, $arrayDatos);
                    }
                    
                }else{
                    array_push($productosNuevos, $producto);
                }
            }else{
                    array_push($productosNuevos, $producto);
            }
        }
        if(count($productosNuevos)>0){
            $htmlNuevos=lineaProductosNuevos($productosNuevos, $_POST['prodNuevos']);
            $respuesta['htmlNuevos']=$htmlNuevos;
        }
        if(count($productosModificados)>0){
            $htmlMod=lineaProductosModificador($productosModificados,  $idTienda, $_POST['prodModif']);
            $respuesta['htmlMod']=$htmlMod;
        }
        $respuesta['totalNuevos']=count($productosNuevos);
        $respuesta['totalModificados']=count($productosModificados);
        $respuesta['productosModificados']=$productosModificados;
        $respuesta['productosNuevos']=$productosNuevos;
    break;
    case 'modificarProducto':
    //@Objetivo: modificar los productos en tpv
        $codBarras=array();
        $precioCiva=0;
        $estadoWeb="Publicado";
        if($_POST['optCodBarra']==1){
             $codBarrasTexto=explode(";",$_POST['codBarras']);
             foreach($codBarrasTexto as $cod){
                 array_push($codBarras , $cod);
             }
        }
        $caliva=($_POST['iva']/100)+1;
        $precioCiva=$_POST['precioSiva']*$caliva;
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
        $modificar=$CVirtuemart->modificarProductoTPVWeb($datos);
        $respuesta['modificar']=$modificar;
    break;
    case 'addProductoTpv':
     //@Objetivo: añadir los productos en tpv
        $ultimoCoste=0;
        $precioCiva=0;
        $codBarras=array();
        $tiendaPrincipal=$Ctienda->tiendaPrincipal();
        $tiendaPrincipal=$tiendaPrincipal['datos'][0]['idTienda'];
        if($_POST['optCoste']=="1"){
            // Si la opcion selecciona fue calcular coste
            $respuesta['beneficio']= (int) $_POST['beneficio'];
            $beneficio=($respuesta['beneficio']/100)+1;
            $ultimoCoste=(int)$_POST['precioSiva']/$beneficio;
        }
        if($_POST['optCodBarra']=="1"){
             $codBarrasTexto=explode(";",$_POST['codBarras']);
             foreach($codBarrasTexto as $cod){
                 array_push($codBarras , $cod);
             }
        }
        $caliva=($_POST['iva']/100)+1;
        $precioCiva=$_POST['precioSiva']*$caliva;
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
              'codBarras'=>$codBarras,
              'precioCiva'=>$precioCiva,
              'precioSiva'=>$_POST['precioSiva'],
              'tiendaPrincipal'=>$tiendaPrincipal,
              'tiendaWeb'=>$_POST['tiendaWeb'],
              'optRefWeb'=>$_POST['optRefWeb'],
              'refTienda'=>$_POST['refTienda'],
              'estadoWeb'=>$estadoWeb
              
        );
        $add=$CVirtuemart->addProductoWebTPV($datosProducto);
        $respuesta['add']=$add;
        $respuesta['datos']=$datosProducto;
    break;
    
    case 'obtenerProductosRelacionados':
        // Obtenemos los registros de articulosTienda
        // solo los que nos indica reg_inicial  y reg_final incluidos.
        $respuesta = array();
        $reg_inicial    = $_POST['reg_inicial'];
        $reg_final      = $_POST['reg_final'];
        $r = $CVirtuemart->obtenerIdVirtuemartRelacionado($reg_inicial);
        $respuesta['obtener'] = $r;
        if ( $r['Items'] > 0){
            // Se obtuvo registros.. ahora tenemos que buscar el idCategoria
            // Aunque se podría hacer una consulta y obtenerlo, de momento lo hago asi.
            // Uno a uno..
            foreach ($r['Items'] as $key=>$Item){
                $p = $CVirtuemart->GetProducto($Item['idArticulo']);
                // Aunque tenga mas de una familia solo va buscar en carpeta de la primera familia.
                $r['Items'][$key]['IdFamilia'] = $p['familias'][0]['idFamilia'];

            }
        // Ahora enviamos los registros por curl
        $respuesta['imagenes'] = $CVirtuemart->buscarImagenesParaRelacionar($r['Items']);
        }
        
    break;

    case 'obtenerIdVirtuemart':
        // Obtenemos el idVirtuemar del producto que idTpv que recibimos.
        $idTpv = $_POST['idProductoTpv'];
        $producto = $CVirtuemart->GetProducto($idTpv);
        $respuesta['idVirtuemart'] = $CVirtuemart->GetIdVirtuemartRefTiendas($producto['ref_tiendas']);
    break;

    case 'anhadirCamposIdVirtuemart':
        // @ Objetivo
        // Añadimos los campos personalizados de 100grs, 200grs y 500grs  al idvirtuemar que nos indica, pero
        // solo si no tiene ningun campo personalizado peso ya creado para ese id.
        // NOTA:
        // Hay que tener en cuenta que id del campo personalizado ahora lo pongo por defecto el 3, pero esto
        // tendría que ser un parametro de configuracion ,sino no tiene sentido..
        $idVirtuemart = $_POST['idVirtuemart'];
        $respuesta = $CVirtuemart->AnhadirCamposPersonalizadosIdVirtuemart($idVirtuemart);

    break;
    
}
echo json_encode($respuesta);
?>
