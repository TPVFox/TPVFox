<?php
/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción 
 */
include_once $URLCom . '/modulos/mod_familia/clases/ClaseFamilias.php';
include_once $URLCom . '/modulos/mod_producto/clases/ClaseArticulos.php';

function _leerFamilias($idpadre) {
    //@objetivo: leer todas lod hijos y productos de un padre.
    //Funcionamiento:
    //1-Al llamar a la función se le indica el id del padre
    //2-Si el padre es mayor o igual a 0
    //  -Lee los datos de esa familia
    //  -Cuenta el número de hijos 
    //  -Cuenta el número de productos de esa familia
    // Devuelto todo, datos y el total hijos y productos.
    $resultado = [];
    $resultado['padre'] = $idpadre;
    $objfamilia = new ClaseFamilias();
    if ($idpadre >= 0) {
        $familias = $objfamilia->leerUnPadre($idpadre);
        $familias['datos'] = $objfamilia->cuentaHijos($familias['datos']);
        $familias['datos'] = $objfamilia->cuentaProductos($familias['datos']);
    } else {
        $familias['datos'] = [];
    }
    $resultado['datos'] = $familias['datos'];

    return $resultado;
}

function leerFamilias($idpadre) {
    //@Objetivo: leer las familias y montar el html
    
    $resultado = _leerFamilias($idpadre);
    $resultado['html'] = familias2Html($resultado['datos']);

    return $resultado;
}

function familias2Html($familias) {
    //@objetivo: mostrar el html con los datos de las familias as entran por cabecera
    $resultado = '';
    if (count($familias) > 0) {
        $indices = [];
        $contador = 0;
        foreach ($familias as $indice => $familia) {
            $indices[] = $familia['idFamilia'];
            $resultado .= '<tr id="fila0-' . $familia['idFamilia'] . '" '
                    . ' data-idfamilia="' . $familia['idFamilia'] . '"'
                    . '>';
            $contador += 1;
            $resultado .= '<td> <input type="checkbox" class="form-check-input"'
                    . ' name="checkFamilia" id="check' . $familia['idFamilia'] . '"> </td>';
            $resultado .= '<td>' . $familia['idFamilia'] . '</td>';
            $resultado .= '<td> <button type="button" class="btn btn-link" onclick="window.location.href=\'familia.php?id=' . $familia['idFamilia'] . '\'">' . $familia['familiaNombre'] . '</button> </td>';
            $resultado .= $familia['familiaPadre'] == 0 ? '<td> </td>' : '<td> ' . $familia['familiaPadre'] . ' (' . $familia['nombrepadre'] . ')</td>';
            $resultado .= '<td>';
            $resultado .= $familia['hijos'] . ' Hijos ';
            if ($familia['hijos'] > 0) {
                $resultado .= '<button name="btn-expandir" id="botonexpandir-' . $familia['idFamilia']
                        . '" data-alseccion="' . $familia['idFamilia']
                        . '" class="btn btn-primary btn-sm btn-expandir" onclick="expandir('.$familia['idFamilia'].')">'
                        . '<span class="glyphicon glyphicon-plus"></span> expandir </button> ';
                $resultado .= '<button name="btn-compactar" id="botoncompactar-' . $familia['idFamilia']
                        . '" data-alseccion="' . $familia['idFamilia']
                        . '" class="btn btn-primary btn-sm " style="display:none" onclick="compactar('.$familia['idFamilia'].')">'
                        . '<span class="glyphicon glyphicon-minus"></span> compactar </button> ';
            } else {

                $resultado .= '<button name="btn-marcaeliminar" id="botonMarcaEliminar-' . $familia['idFamilia'] . '" '
                        . ' data-alseccion="' . $familia['idFamilia'] . '"'
                        . ' data-productos="' . $familia['productos'] . '"'
                        . ' class="btn btn-primary btn-sm " onclick="marcarFamiliaEliminar('.$familia['productos'].', '.$familia['idFamilia'].')">'
                        . '<span class="glyphicon glyphicon-trash"></span> </button> ';
            }
            $resultado .= '</td>';
            $resultado .= '<td>' . $familia['productos'] . ' productos </td>';

            $resultado .= '</tr>';
            $resultado .= '<tr id="fila-' . $familia['idFamilia'] . '" style="display:none"><td colspan=5><table class="table table-bordered table-hover table-striped"><tbody id="seccion-' . $familia['idFamilia'] . '"  > </tbody></table></td></tr>';
        }
        $indices_str = implode(", ", $indices);
    }
    return $resultado;
}

function htlmLineasFamiliasHijas($familias,$idTiendaWeb) {
    // @ Objetivo
    // Obtener el html lineas de tabla con los datos de las familias
    // [PENDIENTE]
    // Si hay tiendaWeb comprobar que todos los hijos estan subido o no.
    // Si estan todos subido en vez del boton ponemos texto advertencia.
    
    $html = '';
    if ($familias && (count($familias) > 0)) {
        foreach ($familias as $indice => $familia) {
            $html .= '<tr id="Relacion'.$indice.'">';
            $html .= '<td>' . $familia['idFamilia'] . '</td>';
            $html .= '<td> '. $familia['familiaNombre'] . ' </td>';
            $html .= '<td> '. $familia['hijos'] . '</td>';
            $html .= '<td>' . $familia['productos'] . '</td>';
            if ($idTiendaWeb >0 ){
                foreach ($familia['familiaTienda'] as $tienda){
                    if ( $tienda['idTienda'] === $idTiendaWeb ) {
                        $titulo = 'Id de familia web '.$tienda['idFamilia_tienda'];
                        if ($tienda['existes_web']=='OK'){
                        $link = $tienda['link_front_end_categoria'];
                        $color = '';
                        } else {
                            $link = '';
                            if ($tienda['existes_web'] == 'KO'){
                                $color = 'style="color:red"';
                                $titulo = 'ERROR OBTENER '.$titulo;
                              
                            }
                        }
                        $html .= '<td> '
                                .'<a href="'.$link.'"><span title="'.$titulo.'"'.$color.' class="glyphicon glyphicon-globe"></span></a>'
                                .'<a class="glyphicon glyphicon-trash" onclick="EliminarReferenciaTienda('. $familia['idFamilia'].','.$idTiendaWeb.',this)"></a>'
                                .'</td>';
                        }
                }
            }
            $html .= '</tr>';
        }
    }
    
    return $html;
}

function htmlTablaFamiliasHijas($familiasHijos,$idTienda, $bottonSubir) {
    // @ Objetivo
    // Montar la tabla html de familias descendientes
    // @ Parametros
    //      $familiasHijos -> (array) con todos los datos del hijo
    
   
    $htmlFamilias = htlmLineasFamiliasHijas($familiasHijos,$idTienda);
    $html = '<table id="tfamilias" class="table table-striped">'
            . '<thead>'
            . '<tr>'
            . '<th>idfamilia</th>'
            . '<th>Nombre de Familia</th>'
            . '<th>Hijos</th>'
            . '<th>Productos</th>';
    if( $idTienda > 0 ){
        $html .= '<th title="Tienda Web '.$idTienda.'"> Web</th>';
    }
    $html .='</tr>'
            . '</thead>';
    $html .= $htmlFamilias;
    $html .= '</table>  ';
    $html .= $bottonSubir;
   
    return $html;
}

function htmlTablaFamiliaProductos($idfamilia) {
    // @ Objetivo
    // Montar la tabla html de familias descendientes
    // @ Parametros
    //      $idfamilia


    $productos = alArticulos::leerArticulosXFamilia($idfamilia);

    $html = '<table id="tproductos" class="table table-striped">'
            . '<thead>'
            . '<tr>'
            . '<th>id</th>'
            . '<th>Nombre</th>'
            . '<th>'
            . '<button id="btn-borrarfamilia" type="button" onclick="borrarProductoFamilia()">'
            . '<span class="glyphicon glyphicon-trash"> </span> Eliminar de esta familia</button> </th>'
            . '</tr>'
            . '</thead>';
    if (count($productos) > 0) {
        foreach ($productos as $indice => $producto) {
            $html .= '<tr id="tr_' . $producto['idArticulo'] . '" data-idproducto="' . $producto['idArticulo'] . '">';
            $html .= '<td>' . $producto['idArticulo'] . '</td>';
            $html .= '<td> ' . $producto['articulo_name'] . ' </td>';
            $html .= '<td> <button type="button" class="btn btn-seleccionar" onclick="seleccionarProductos('.$producto['idArticulo'].')" id="selproducto' . $producto['idArticulo']
                    . '" data-idproducto="' . $producto['idArticulo'] . '"> select </button> </td>';
            $html .= '</tr>';
        }
    }
    $html .= '</table>  ';
    return $html;
}

function htmlPanelDesplegable($num_desplegable,$titulo,$body){
    // @ Objetivo:
    // Montar html de desplegable.
    // @ Parametros:
    //      $num_desplegable -> (int) que indica el numero deplegable para un correcto funcionamiento.
    //      $titulo-> (string) El titulo que se muestra en desplegable
    //      $body-> (String) lo que contiene el desplegable.
    // Ejemplo tomado de:
    // https://www.w3schools.com/bootstrap/tryit.asp?filename=trybs_collapsible_panel&stacked=h 
    
    $collapse = 'collapse'.$num_desplegable;
    $html ='<div class="panel panel-default">'
            .       '<div class="panel-heading">'
            .           '<h2 class="panel-title">'
            .           '<a data-toggle="collapse" href="#'.$collapse.'">'
            .           $titulo.'</a>'
            .           '</h2>'
            .       '</div>'
            .       '<div id="'.$collapse.'" class="panel-collapse collapse">'
            .           '<div class="panel-body">'
            .               $body
            .           '</div>'
            .       '</div>'
            .'</div>';
    return $html;
}
function  htmlTablaRefTiendas($crefTiendas,$link,$permiso_borrar=0){
    // @ Objetivo
    // Montar la tabla html de codbarras
    // @ Parametros
    //      //      $crefTiendas-> (array) de Arrays con datos de productos en otras tiendas.
    //  Link: index.php?option=com_virtuemart&view=category&virtuemart_category_id=   a vista web
    //  Link: index.php?option=com_virtuemart&view=category&task=edit&cid=            a administador
    $btnAnhadir = '';
    if (count($crefTiendas) == 0){
            $btnAnhadir ='<a  title="Añade una referencia directa a una familia de la web"'
                        .'id="addIdRefTienda" class="glyphicon glyphicon-plus" onclick="HtmlRefFamiliaTienda()"></a>';
    }
    $html =  '<table id="tReferenciaTienda" class="table table-striped">'
            .'  <thead>'
            .'      <tr>'
            .'          <th>idTienda</th>'
            .'          <th>Id de la tienda / id </th>'
            .'          <th>link</th>'
            .'          <th>'.$btnAnhadir
            .'      </tr>'
            .'  </thead>';
    if (count($crefTiendas)>0){
        foreach ($crefTiendas as $item=>$crefTienda){
                $html .= htmlLineaRefTienda($item,$crefTienda,$link,$permiso_borrar);
        }
    }
    $html .= '</table>  ';
    return $html;
} 

function htmlLineaRefTienda($item,$crefTienda,$link,$permiso_borrar){
    // @ Objetivo:
    // Montar linea de proveedores_coste, para añadir o para modificar.
    // @ Parametros :
    //      $item -> (int) Numero item
    //      $crefTienda-> (array) Datos de crefTienda: idTienda,crefTienda,idVirtuemart,...
    
    $link = '<a href="'.$link.$crefTienda['idFamilia_tienda'].'">icono</a>';
    
    $nuevaFila = '<tr id="ref_tienda_'.$item.'">';
    $nuevaFila .= '<td>'.$crefTienda['idTienda'].'</td>';
    $nuevaFila .= '<td>';
    $nuevaFila .='<small>'.$crefTienda['idFamilia_tienda'].'</small>';
    $nuevaFila .='</td>';
    $nuevaFila .= '<td>'.$link.'</td>';
    $nuevaFila .= '<td>';
    if ($permiso_borrar != 1){
        $nuevaFila .='<a id="eliminarref_tienda_'.$item.'" class="glyphicon glyphicon-trash" onclick="EliminarReferenciaTienda('.$crefTienda['idFamilia'].','.$crefTienda['idTienda'].',this)"></a>';
    }
    $nuevaFila .= '</td></tr>';
    return $nuevaFila;
}


