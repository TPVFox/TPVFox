<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

include_once './../../inicial.php';

require_once './../mod_producto/clases/ClaseArticulos.php';
require_once './clases/claseTarifaCliente.php';

function datos2Html($datos) {
    $html = '<table class="table table-striped">';
    $html .= '<thead>';
    $html .= '<th> ' . 'idArticulo' . '</th>';
    $html .= '<th> ' . 'articulo_name' . '</th>';
    $html .= '<th> ' . 'referencia' . '</th>';
    $html .= '<th> ' . 'codBarras' . '</th>';
    $html .= '</thead>';
    $html .= '<tbody>';
    foreach ($datos as $unarticulo) {
        $html .= '<tr>';
        $html .= '<td> <button id="btn-select-' . $unarticulo['idArticulo'] . '"'
                . ' class="btn btn-default btn-sm btn-busca-art" data-id=' . $unarticulo['idArticulo'] . ' >'
                . '<span class="glyphicon glyphicon-asterisk">'
                . '</span>' . $unarticulo['idArticulo'] . '</button> </td>';
        $html .= '<td> ' . $unarticulo['articulo_name'] . '</td>';
        $html .= '<td> ' . $unarticulo['referencia'] . '</td>';
        $html .= '<td> ' . $unarticulo['codBarras'] . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody>';
    $html .= '</table>';
    return $html;
}

function noData2Html() {
    $html = '<p> No hay datos con esas características. </p>';
    return $html;
}

function cuentaPaginas($nItems, $itemsPerPage = ARTICULOS_MAXLINPAG){
if($itemsPerPage===0){ // No quiero excepciones de "division por 0"
    $itemsPerPage = ARTICULOS_MAXLINPAG;
}
$resto = $nItems % $itemsPerPage;

return (($nItems-$resto) / $itemsPerPage) + 1;


}

$caja = $_POST['caja'];
$usarlike = $_POST['usarlike'];
$valor = $_POST['valor'];  
$idcliente = isset($_POST['idcliente']) ? $_POST['idcliente'] : 0;
$idtienda = isset($POST['idtienda']) ? $POST['idtienda'] : 1;
$paginaBuscar = isset($_POST['pagina']) ? $_POST['pagina'] : 1;
        
$resultado = [];

$articulos = [];
$contador = 0;

if ($caja) {
    switch ($caja) {
        case 'idpreciocliente':
            $articulo = (new TarifaCliente($BDTpv))->leerPrecio($idcliente, $valor);
            if ($articulo) {                
                $articulos = $articulo;
            }
            break;

        case 'idArticulo':
            $articulo = (new alArticulos($BDTpv))->leerPrecio($valor);
            if ($articulo) {
                $articulos = $articulo;
            }
            break;

            case 'Referencia':
            $articulo = new alArticulos($BDTpv);
            if ($usarlike === 'si') {
                $contador = $articulo->contarLikeReferencia($valor, $idtienda);
                if ($contador == 0) {
                    $articulos['datos'] = [];
                    $articulos['html'] = noData2Html();
                } else {
                    $articulos = $articulo->leerLikeReferencia($valor, $paginaBuscar, $idtienda);
                    if ($articulos['datos']) {
                        $articulos['html'] = datos2Html($articulos['datos']);
                    }
                }
            } else {
                $articulos = $articulo->leerXReferencia($valor, $idtienda);
                if ($articulos) {
                    if (count($articulos['datos']) == 1) {
                        $idArticulo = $articulos['datos'][0]['idArticulo'];
                        $articulos = $articulo->leerPrecio($idArticulo);
                    }
                }
            }
            break;

        case 'Descripcion':
            $articulo = new alArticulos($BDTpv);
            $contador = $articulo->contarLikeDescripcion($valor);
            if ($contador == 0) {
                $articulos['datos'] = [];
                $articulos['html'] = noData2Html();
            } else {
                $articulos = $articulo->leerLikeDescripcion($valor, $paginaBuscar);
                if ($articulos['datos']) {
                    $articulos['html'] = datos2Html($articulos['datos']);
                }
            }
            break;

        case 'Codbarras':
            $articulo = new alArticulos($BDTpv);
            if ($usarlike === 'si') {
                $contador = $articulo->contarLikeCodBarras($valor);

                if ($contador == 0) {
                    $articulos['datos'] = [];
                    $articulos['html'] = noData2Html();
                } else {
                    $articulos = $articulo->leerLikeCodBarras($valor, $paginaBuscar);
                    if ($articulos['datos']) {
                        $articulos['html'] = datos2Html($articulos['datos']);
                    }
                }
            } else {
                $articulos = $articulo->leerXCodBarras($valor);
                if ($articulos) {
                    if (count($articulos['datos']) == 1) {
                        $idArticulo = $articulos['datos'][0]['idArticulo'];
                        $articulos = $articulo->leerPrecio($idArticulo);
                    }
                }
            }
            break;
    }
    $articulos['contador'] = $contador;   
    $articulos['pagina'] = $paginaBuscar;   // absurdo. Va y viene el mismo valor. ¿?
    $articulos['totalPaginas'] = $articulos['contador']== 0 ? 999 : cuentaPaginas($articulos['contador']);
    $resultado = $articulos;
    
}
echo json_encode($resultado);
