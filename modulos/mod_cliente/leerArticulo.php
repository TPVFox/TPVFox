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


$caja = $_POST['caja'];
$valor = $_POST['valor'];  
$idcliente = isset($_POST['idcliente']) ? $_POST['idcliente'] : 0;
$idtienda = isset($POST['idtienda']) ? $POST['idtienda'] : 1;
$paginaBuscar = isset($_POST['pagina']) ? $_POST['pagina'] : 1;
        
$resultado = [];

$articulos = [];

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
            // Buscamos articulos =
            $articulos = $articulo->leerXReferencia($valor, $idtienda);
                if ($articulos) {
                    if (count($articulos['datos']) == 1) {
                        $idArticulo = $articulos['datos'][0]['idArticulo'];
                        $articulos = $articulo->leerPrecio($idArticulo);
                    } else {
						// Quiere decir que no hubo resultados o que hubo mas de uno ( es posible)
							$articulos = $articulo->leerLikeReferencia($valor, $paginaBuscar, $idtienda);
							if ($articulos['datos']) {
								$articulos['html'] = datos2Html($articulos['datos']);
							}
					}
				}
            break;

        case 'Descripcion':
            $articulo = new alArticulos($BDTpv);
            $articulos = $articulo->leerLikeDescripcion($valor, $paginaBuscar);
                if ($articulos['datos']) {
                    $articulos['html'] = datos2Html($articulos['datos']);
                }

            break;

        case 'Codbarras':
            $articulo = new alArticulos($BDTpv);
            $articulos = $articulo->leerXCodBarras($valor);
                if ($articulos) {
                    if (count($articulos['datos']) == 1) {
                        $idArticulo = $articulos['datos'][0]['idArticulo'];
                        $articulos = $articulo->leerPrecio($idArticulo);
                    } else {
						// Quiere decir que no hubo resultados o que hubo mas de uno ( es posible)
						  $articulos = $articulo->leerLikeCodBarras($valor, $paginaBuscar);
						if ($articulos['datos']) {
							$articulos['html'] = datos2Html($articulos['datos']);
						}
						
					}
                }
            break;

    }
    $articulos['contador'] = $contador;   
    $articulos['pagina'] = $paginaBuscar;   // absurdo. Va y viene el mismo valor. ¿?
    $resultado = $articulos;
    
}
echo json_encode($resultado);
