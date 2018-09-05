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
    foreach ($productosNuevos as $nuevo){
        $html.='<tr>
            
            <td>Nombre:'.$nuevo['nombre'].'<br>
            Referencia Tienda '.$nuevo['refTienda'].'<br>
            IVA: '.$nuevo['iva'].'<br>
            precio sin IVA : '.$nuevo['precioSiva'].'<br>
            Cod Barras:'.$nuevo['codBarra'].'
            </td>
            <td><a class="glyphicon glyphicon-plus" onckick="addProductoWeb('.$nuevo['id'].')"></a></td>
        </tr>';
    }
    return $html;
    
}
?>
