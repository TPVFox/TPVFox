<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

include_once './../../inicial.php';

require_once './../mod_producto/clases/ClaseArticulos.php';




$caja = $_POST['caja'];
$usarlike = $_POST['usarlike'];
$valor = $_POST['valor'];

$resultado = [];

if ($caja) {
    switch ($caja) {
        case 'idArticulo':
            $articulo = (new alArticulos($BDTpv))->leerPrecio($valor);

            if ($articulo) {
//                if (count($articulo['datos']) == 1) {
//                    $articulo['datos'] = $articulo['datos'][0];
//                }
                $resultado = $articulo;
            }
            break;

        case 'Codbarras':
            $articulos = [];
            $articulo = new alArticulos($BDTpv);
            if ($usarlike === 'si') {
                $articulos['contador'] = $articulo->contarLikeCodBarras($valor);
                if ($articulos['contador'] == 0) {
                    $articulos['datos'] = [];
                } else {
                    $articulos = $articulo->leerLikeCodBarras($valor,1);
                    if($articulos['datos']){
                        $html = '<table class="table table-striped">';
                            $html .= '<thead>';
                            $html .= '<th> '.'idArticulo'.'</th>';
                            $html .= '<th> '.'articulo_name'.'</th>';
                            $html .= '<th> '.'referencia'.'</th>';
                            $html .= '<th> '.'codBarras'.'</th>';                            
                            $html .= '</thead>';
                            $html .= '<tbody>';                            
                        foreach ($articulos['datos'] as $unarticulo) {
                            $html .= '<tr>';
                            $html .= '<td> <button id="btn-select-'.$unarticulo['idArticulo'].'"'
                                    .' class="btn btn-default btn-sm btn-busca-art" data-id='.$unarticulo['idArticulo'].' >'
                                    . '<span class="glyphicon glyphicon-asterisk">'
                                    . '</span>'.$unarticulo['idArticulo'].'</button> </td>';
                            $html .= '<td> '.$unarticulo['articulo_name'].'</td>';
                            $html .= '<td> '.$unarticulo['referencia'].'</td>';
                            $html .= '<td> '.$unarticulo['codBarras'].'</td>';
                            $html .= '</tr>';
                        }
                            $html .= '</tbody>';                            
                        $html .= '</table>';
                        $articulos['html'] = $html;
                    }
                    $articulos['like'] = 'pasa por like';
                }
            } else {
                $articulos = $articulo->leerXCodBarras($valor);
                if ($articulos) {
                    if (count($articulos['datos']) == 1) {
                        $idArticulo = $articulos['datos'][0]['idArticulo'];
                        $articulos = $articulo->leerPrecio($idArticulo);
                    }
//            if( count($articulos['datos'])==0){
//                $articulos =  $articulo->leerLikeCodBarras($valor, 1);
//                $articulos['like']='pasa por like';
//            }
                    
                }
            }
            $resultado = $articulos;
            break;

    }
}
echo json_encode($resultado);
