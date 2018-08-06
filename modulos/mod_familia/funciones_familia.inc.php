<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

function familias2Html($objfamilia, $familias) {
    $resultado = '';
    if (count($familias) > 0) {
        $indices = [];
        foreach ($familias as $indice => $familia) {
            $indices[] = $familia['idFamilia'];
            $resultado .= '<tr>';
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
                        . '" class="btn btn-primary btn-sm">'
                        . '<span class="glyphicon glyphicon-plus"></span> expandir </button> ';
                $resultado .= '<button name="btn-compactar" id="botoncompactar-' . $familia['idFamilia']
                        . '" data-alseccion="' . $familia['idFamilia']
                        . '" class="btn btn-primary btn-sm " style="display:none">'
                        . '<span class="glyphicon glyphicon-minus"></span> compactar </button> ';
            }
            $resultado .= '</td>';
            $resultado .= '<td>'. $objfamilia->contarProductos($familia['idFamilia']).' productos </td>';
            
            $resultado .= '</tr>';
            $resultado .= '<tr id="fila-' . $familia['idFamilia'] . '" style="display:none"><td colspan=5><table class="table table-bordered table-hover table-striped"><tbody id="seccion-' . $familia['idFamilia'] . '"  > </tbody></table></td></tr>';
        }
        $indices_str = implode(", ", $indices);
        $resultado .= '<script language="javascript">' .
                'capturaevento_click([' . $indices_str . ']);' .
                '</script>';
    }
    return $resultado;
}

