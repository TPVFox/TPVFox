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
$valor = $_POST['valor'];


switch ($caja) {
    case 'idArticulo':
        $articulo = (new Articulos($BDTpv))->leerPrecio($valor);
        
        if($articulo){            
            if(count($articulo['datos'])==1){
                $articulo['datos'] = $articulo['datos'][0];
            }
            $resultado = $articulo;
        }

        break;

    default:
        break;
}
echo json_encode($resultado);
