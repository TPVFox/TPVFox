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

function familias2Html2($familias) {
    //objetivo: imprimir lineas de tabla con los datos de las familias
    $resultado = '';
    if ($familias && (count($familias) > 0)) {
        foreach ($familias as $indice => $familia) {
            $indices[] = $familia['idFamilia'];
            $resultado .= '<tr>';
            $resultado .= '<td>' . $familia['idFamilia'] . '</td>';
            $resultado .= '<td> ' . $familia['familiaNombre'] . ' </td>';
            $resultado .= '<td> ' . $familia['hijos'] . '</td>';
            $resultado .= '<td>' . $familia['productos'] . '</td>';

            $resultado .= '</tr>';
        }
    }
    return $resultado;
}

function htmlTablaFamiliasHijas($idfamilia) {
    // @ Objetivo
    // Montar la tabla html de familias descendientes
    // @ Parametros
    // 		$idfamilia

    $familias = leerFamilias($idfamilia);

    $htmlFamilias = familias2Html2($familias['datos']);
    $html = '<table id="tfamilias" class="table table-striped">'
            . '<thead>'
            . '<tr>'
            . '<th>idfamilia</th>'
            . '<th>Nombre de Familia</th>'
            . '<th>Hijos</th>'
            . '<th>Productos</th>'
            . '</tr>'
            . '</thead>';
    $html .= $htmlFamilias;
    $html .= '</table>	';
    return $html;
}

function htmlTablaFamiliaProductos($idfamilia) {
    // @ Objetivo
    // Montar la tabla html de familias descendientes
    // @ Parametros
    // 		$idfamilia


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
    $html .= '</table>	';
    return $html;
}
