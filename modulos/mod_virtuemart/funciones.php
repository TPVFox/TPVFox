<?php 

function comparacionesProductos($productoWeb, $productoTpv, $idTienda,$conf){
    //@Objetivo: comparar un el producto web con el tpv
    $comprobacion=0;
    if(trim($productoWeb['nombre']) <> trim($productoTpv['articulo_name'])){
        error_log('Diferencia por Nombre'.'nombre web:'.$productoWeb['nombre'].'nombre tpv:'.$productoTpv['articulo_name']);
        $comprobacion=1;
    }
    if ($conf['Sel_referencia']!== '3'){
        // Seleciono opcion 3, que no importa.. es decir que no comprueba y no guarda en referencia principal
        if($productoTpv['cref_tienda_principal'] <> $productoWeb['refTienda']){
            error_log('cref_tienda_principal:'.$productoTpv['cref_tienda_principal'].'Diferencia por Creferencua');
            $comprobacion=1;
            
        }
    }
    $ivaWeb=floatval($productoWeb['iva']);
    $ivaTpv=floatval($productoTpv['iva']);
    
    if(number_format($ivaWeb, 2) <> number_format($ivaTpv, 2)){
        error_log('Iva:'.$productoWeb['$ivaWeb'].'Diferencia por iva');

        $comprobacion=1;
    }
    if( round(floatval($productoWeb['precioSiva']),2) <> round(floatval($productoTpv['pvpSiva']),2) ){
        error_log('Precio:'.$productoWeb['precioSiva'].'Diferencia por precio');

        $comprobacion=1;
    }
    if ($conf['Sel_codBarras']!== '2'){
        // Option seleccionada no es "No importa" 
        $codBarras=explode(";",$productoWeb['codBarra']);
        $dif=array_diff($codBarras, $productoTpv['codBarras']);
        
           if(count($dif)>0){
               foreach ($dif as $cod){
                  
                   if($cod<>""){
                    error_log('codigoBarras:'.$cod.'Diferencia por codigo Barras');
                       $comprobacion=1;
                   }
               }
               
           }
    }
    
  
    
    return $comprobacion;
}
function lineaProductosNuevos($productosNuevos, $cantProdNuevos){
    //Objetivo: imprimir la linea cuando el producto es nuevo en tpv
    $html="";
   
                if($cantProdNuevos==""){
                    $cantProdNuevos=0;
                }
    foreach ($productosNuevos as $nuevo){
        $html.='<tr id="nuevo_'.$cantProdNuevos.'">
            <td><b>Nombre:</b>'.$nuevo['nombre'].'<br>
           <b>Referencia Tienda</b> '.$nuevo['refTienda'].'<br>
            <b>IVA:</b> '.$nuevo['iva'].'<br>
           <b>precio sin IVA :</b> '.$nuevo['precioSiva'].'<br>
            <b>Cod Barras:</b>'.$nuevo['codBarra'].'
            </td>
            <td><a class="btn btn-info" onclick="addProductoWeb('."'".$nuevo['nombre']."'".', 
            '."'".$nuevo['refTienda']."'".','."'".$nuevo['iva']."'".', '."'".$nuevo['precioSiva']."'".', 
            '."'".$nuevo['codBarra']."'".', '.$nuevo['id']. ', '.$cantProdNuevos.')">Insertar Productos a TPV</a></td>
        </tr>';
        $cantProdNuevos++;
        
    }
    return $html;
    
}
function lineaProductosModificador($productos, $idTienda, $cantProdModif){
     //Objetivo: imprimir la linea cuando el producto es modificado
    $html="";
   if($cantProdModif==""){
        $cantProdModif=0;
    }

    foreach ($productos as $producto){
        $html.='<tr id="mod_'.$cantProdModif.'">
             <td><b>Nombre:</b>'.$producto[0]['nombre'].'<br>
            <b>Referencia Tienda</b> '.$producto[0]['refTienda'].'<br>
            <b>IVA:</b> '.$producto[0]['iva'].'<br>
            <b>precio sin IVA :</b> '.$producto[0]['precioSiva'].'<br>
            
            <b>Cod Barras:</b>';
            $codBarras=explode(";",$producto[0]['codBarra']);
            foreach ($codBarras as $cod){
                $html.=$cod.'  ';
            }
           
           $html.='</td>
             <td><b>Nombre:</b>'.$producto[1]['articulo_name'].'<br>
            <b>Referencia Tienda Web :</b> ';
             foreach ($producto[1]['ref_tiendas'] as $ref){
                 
                 if($ref['idTienda'] == $idTienda){
                   
                     $html.= $ref['crefTienda'];
                 }
             }
             
           
           $html.=' <br><b>IVA:</b> '.$producto[1]['iva'].'<br>
            <b>precio sin IVA :</b> '.$producto[1]['pvpSiva'].'<br>
            <b>Cod Barras:</b>';
            
            foreach ($producto[1]['codBarras'] as $cod){
                $html.=$cod.'  ';
            }
            $html.='
            </td>
            <td><a class="btn btn-info" onclick="modificarProductosTpvWeb('."'".$producto[0]['nombre']."'".', 
            '."'".$producto[0]['refTienda']."'".','."'".$producto[0]['iva']."'".', '."'".$producto[0]['precioSiva']."'".',
             '."'".$producto[0]['codBarra']."'".', '."'".$producto[1]['idArticulo']."'".', '.$cantProdModif.')">
            Modificar datos Tpv con los datos Web</a></td>
        </tr>';
        $cantProdModif++;
    }
    return $html;
}

?>
