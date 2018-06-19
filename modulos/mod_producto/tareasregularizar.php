<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


include_once '../../inicial.php';
include_once './clases/ClaseArticulos.php';


$pulsado = $_POST['pulsado'];


switch ($pulsado) {

    case 'leerarticulo':
        $idArticulo = $_POST['idarticulo'];
        $respuesta = [];
        $alarticulo = new alArticulos();
        $articulo = $alarticulo->leer($idArticulo);
        if($articulo){
            $articulo = $articulo[0];
            $respuesta['idarticulo'] = $idArticulo;
            $respuesta['nombreArticulo'] = $articulo['articulo_name'];
            $stocks = $alarticulo->getStock($idArticulo);            
            $respuesta['stock'] = number_format($stocks['stockOn'],2);
        }
        echo json_encode($respuesta);        
        break;
    case 'grabar' :
        $idArticulo = $_POST['idarticulo'];
        $stocksumar = $_POST['stocksumar'];

        $respuesta = alArticulosStocks::actualizarStock($idArticulo, 1, $stocksumar, K_STOCKARTICULO_SUMA);
        
        echo json_encode($respuesta);        
        break;
    default : echo json_encode([]);        
}
