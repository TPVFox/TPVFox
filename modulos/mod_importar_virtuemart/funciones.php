<?php 

function comparacionesProductos($productoWeb, $productoTpv){
    $comprobacion=0;
    if($productoWeb['nombre'] <> $productoTpv['articulo_name']){
        $comprobacion=1;
    }
    if($productoWeb['refTienda'] <> $productoTpv['cref_tienda_principal']){
        $comprobacion=1;
    }
    if($productoWeb['iva'] <> $productoTpv['iva']){
        $comprobacion=1;
    }
    if(floatval ($productoWeb['precioSiva']) <> floatval($productoTpv['pvpSiva'])){
        $comprobacion=1;
    }
    foreach ($productoTpv['codBarras'] as $cod){
        if(int($cod)<>$productoWeb['codBarra']){
            $comprobacion=1;
        }
    }
    
    return $comprobacion;
}
function lineaProductosNuevos($productosNuevos){
    $html="";
    $html='<h4>Productos Nuevos</h4>
                <tr>
                   
                    <th>Datos Web</th>
                    <th>Acciones</th>
                </tr>';
                $i=0;
    foreach ($productosNuevos as $nuevo){
        $html.='<tr id="nuevo_'.$i.'">
            
            <td>Nombre:'.$nuevo['nombre'].'<br>
            Referencia Tienda '.$nuevo['refTienda'].'<br>
            IVA: '.$nuevo['iva'].'<br>
            precio sin IVA : '.$nuevo['precioSiva'].'<br>
            Cod Barras:'.$nuevo['codBarra'].'
            </td>
            <td><a class="glyphicon glyphicon-plus" onckick="addProductoWeb("'.$nuevo['nombre'].'", 
            "'.$nuevo['refTienda'].'","'.$nuevo['iva'].'", "'.$nuevo['precioSiva'].'", "'.$nuevo['codBarra'].'"'.$i.')"></a></td>
        </tr>';
        $i++;
    }
    return $html;
    
}
function lineaProductosModificador($productos, $idTienda){
     $html="";
    $html='<h4>Productos MOdificados</h4>
                <tr>
                    <th>Datos Web</th>
                     <th>Datos tpv</th>
                    <th>Acciones</th>
                </tr>';
                $i=0;
    foreach ($productos as $producto){
        $html.='<tr id="mod_'.$i.'">
             <td>Nombre:'.$producto[0]['nombre'].'<br>
            Referencia Tienda '.$producto[0]['refTienda'].'<br>
            IVA: '.$producto[0]['iva'].'<br>
            precio sin IVA : '.$producto[0]['precioSiva'].'<br>
            Cod Barras:'.$producto[0]['codBarra'].'
            </td>
             <td>Nombre:'.$producto[1]['articulo_name'].'<br>
            Referencia Tienda : ';
             
             foreach ($producto[1]['ref_tiendas'] as $ref){
                 if($ref['idTienda'] ==$idTienda){
                     $html.=$ref['idTienda'].'<br>';
                 }else{
                     $html.='<br>';
                 }
             }
             
           
           $html.=' IVA: '.$producto[1]['iva'].'<br>
            precio sin IVA : '.$producto[1]['pvpSiva'].'<br>
            Cod Barras:';
            
            foreach ($producto[1]['codBarras'] as $cod){
                $html.=$cod.'  ';
            }
            $html.='
            </td>
            <td><a class="glyphicon glyphicon-pencil"></a></td>
        </tr>';
    }
    return $html;
}

?>
