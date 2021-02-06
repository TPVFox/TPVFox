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
include_once'./clases/ClaseReorganizar.php';
include_once '../mod_producto/clases/ClaseArticulos.php';
include_once '../mod_producto/clases/ClaseArticulosStocks.php';

switch ($pulsado) {

    case 'contarproductos':
        $tipo = $_POST['tipo'];
        $CReorganizar = new ClaseReorganizar();
        if (isset($CReorganizar->SetPlugin('ClaseVirtuemart')->TiendaWeb)){
            $TiendaWeb = $CReorganizar->SetPlugin('ClaseVirtuemart')->TiendaWeb;
            $CReorganizar->setIdTiendaWeb($TiendaWeb['idTienda']);
        }
        $totalProductos = $CReorganizar->contar($tipo);
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

    case 'subirStockYPrecio':
        $inicial = $_POST['inicial'];
        $cantidad = $_POST['cantidad'];
        $totalProductos = $_POST['totalProductos'];
        $CReorganizar = new ClaseReorganizar();
        if (isset($CReorganizar->SetPlugin('ClaseVirtuemart')->TiendaWeb)){
            $CVirtuemart = $CReorganizar->SetPlugin('ClaseVirtuemart');
            $TiendaWeb = $CVirtuemart->TiendaWeb;
            $CReorganizar->setIdTiendaWeb($TiendaWeb['idTienda']);
            
        }
        // Ahora obtenemos los ids productos de la web
        $idsWeb =$CReorganizar->obtenerIdsWeb($inicial,$cantidad);
        // Ahora enviamos a la web.
        $productos= json_encode($idsWeb['datos']);
        $r =$CVirtuemart->enviarStockYPrecio($productos);
        $resultado = array ( 'elementos' => $r['Datos']['consulta1'],
                             'elementos_precios' => $r['Datos']['consulta2'],
                             'actual'=> $inicial+ count($idsWeb['datos']) + 1,
                             'totalProductos' => $totalProductos
                            );
        // Si hubo un error deberías añadirlo.
        if (isset($r['Datos']['error'])){
            $resultado['error'] = $r;
        }

        echo json_encode($resultado);
    break;

    case 'limpiarPermisosModulos':
        // Objetivo limpiar los permisos de modulos que no existen.
        $resultado = $ClasePermisos->limpiarPermisosModulosInexistentes();
        echo json_encode($resultado);

    break;

        

}

