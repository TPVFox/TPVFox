<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */


/* Fichero de tareas a realizar.
 * 
 * 
 * Con el switch al final y variable $pulsado
 * 
 *
 *   
 */


/* ===============  REALIZAMOS CONEXIONES  =============== */


$pulsado = $_POST['pulsado'];

include_once ("./../../inicial.php");

// Crealizamos conexion a la BD Datos
include_once '../mod_producto/clases/ClaseArticulos.php';
include_once '../mod_producto/clases/ClaseArticulosStocks.php';

switch ($pulsado) {

    case 'contarproductos':
        $totalProductos = (new alArticulos())->contar();
        echo json_encode(compact('totalProductos'));
        break;
    case 'generastock':
        $inicial = $_POST['inicial'];
        $pagina = $_POST['pagina'];
        $totalProductos = $_POST['totalProductos'];

        if($inicial == 0){
            alArticulosStocks::limpiaStock(); //idTienda = 1
            $resultado['stocks'][] = ['id'=>0,'stock'=> '000'];
        }
        
        $resultado = ['totalProductos' => $totalProductos, 'pagina' => $pagina];
        $articulo = new alArticulos();
        $seleccionArticulos = $articulo->leer(0, $inicial, $pagina);
        if ($seleccionArticulos) {
            $resultado['elementos'] = count($seleccionArticulos);
            $resultado['actual'] = $inicial + $resultado['elementos'];
            $idTienda = 1;
            foreach ($seleccionArticulos as $seleccionado) {
                
                $idArticulo = $seleccionado['idArticulo'];
                if ($articulo->existe($idArticulo)) {
                    $stock = $articulo->calculaStock($idArticulo);
                    if($stock != 0){
                    alArticulosStocks::actualizarStock($idArticulo, $idTienda, $stock, K_STOCKARTICULO_SUMA);
                    $resultado['stocks'][] = ['id'=>$idArticulo,'stock'=> $stock];
                    }
                    
                } else {
                    $resultado = 'No existe articulo';
                }
            }
        }
        echo json_encode($resultado);
        break;
}
