<?php 

function comparacionesProductos($productoWeb, $productoTpv, $idTienda){
    $comprobacion=0;
    if($productoWeb['nombre'] <> $productoTpv['articulo_name']){
      
        $comprobacion=1;
    }
    foreach ($productoTpv['ref_tiendas'] as $ref){
         if($ref['idTienda'] == $idTienda){
             if($ref['crefTienda'] <> $productoWeb['refTienda']){
                 $comprobacion=1;
                
             }
         }
    }
    $ivaWeb=floatval($productoWeb['iva']);
    $ivaTpv=floatval($productoTpv['iva']);
    
    if(number_format($ivaWeb, 2) <> number_format($ivaTpv, 2)){
    
        $comprobacion=1;
    }
    if(floatval($productoWeb['precioSiva']) <> floatval($productoTpv['pvpSiva'])){
      
        $comprobacion=1;
    }
    $codBarras=explode(";",$productoWeb['codBarra']);
    $dif=array_diff($codBarras, $productoTpv['codBarras']);
    
       if(count($dif)>0){
           foreach ($dif as $cod){
              
               if($cod<>""){
                   $comprobacion=1;
               }
           }
           
       }
    
  
    
    return $comprobacion;
}
function lineaProductosNuevos($productosNuevos){
    $html="";
    $html='<h4><b>Productos Nuevos</b></h4>
                <tr>
                   
                    <th>Datos Web</th>
                    <th>Acciones</th>
                </tr>';
                $i=0;
    foreach ($productosNuevos as $nuevo){
        $html.='<tr id="nuevo_'.$i.'">
            <td><b>Nombre:</b>'.$nuevo['nombre'].'<br>
           <b>Referencia Tienda</b> '.$nuevo['refTienda'].'<br>
            <b>IVA:</b> '.$nuevo['iva'].'<br>
           <b>precio sin IVA :</b> '.$nuevo['precioSiva'].'<br>
            <b>Cod Barras:</b>'.$nuevo['codBarra'].'
            </td>
            <td><a class="btn btn-info" onclick="addProductoWeb('."'".$nuevo['nombre']."'".', 
            '."'".$nuevo['refTienda']."'".','."'".$nuevo['iva']."'".', '."'".$nuevo['precioSiva']."'".', 
            '."'".$nuevo['codBarra']."'".', '.$nuevo['id']. ', '.$i.')">Insertar Productos a TPV</a></td>
        </tr>';
        $i++;
    }
    return $html;
    
}
function lineaProductosModificador($productos, $idTienda){
     $html="";
    $html='<h4><b>Productos Modificados</b></h4>
                <tr>
                    <th>Datos Web</th>
                     <th>Datos tpv</th>
                    <th>Acciones</th>
                </tr>';
                $i=0;
    foreach ($productos as $producto){
        $html.='<tr id="mod_'.$i.'">
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
             //~ $html.= $producto[1]['cref_tienda_principal'];
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
             '."'".$producto[0]['codBarra']."'".', '."'".$producto[1]['idArticulo']."'".', '.$i.')">
            Modificar datos Tpv con los datos Web</a></td>
        </tr>';
        $i++;
    }
    return $html;
}

?>
