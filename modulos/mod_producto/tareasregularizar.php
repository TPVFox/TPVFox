<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


include_once '../../inicial.php';
include_once './clases/ClaseArticulos.php';
include_once './clases/ClaseRegularizaciones.php';

$pulsado = $_POST['pulsado'];


switch ($pulsado) {

    case 'leerarticulo':
        $idArticulo = $_POST['idarticulo'];
        $respuesta = [];
        $alarticulo = new alArticulos();
        $articulo = $alarticulo->leer($idArticulo);
        if ($articulo) {
            $articulo = $articulo[0];
            $respuesta['idarticulo'] = $idArticulo;
            $respuesta['nombreArticulo'] = $articulo['articulo_name'];
            $stocks = $alarticulo->getStock($idArticulo);
            $respuesta['stock'] = number_format($stocks['stockOn'], 2);
        }
        echo json_encode($respuesta);
        break;
        
    case 'grabar' :
        $idArticulo = $_POST['idarticulo'];
        $stocksumar = $_POST['stocksumar'];

        $idTienda = isset($_SESSION['tiendaTpv']) ? $_SESSION['tiendaTpv']['idTienda'] : 1;
        $idUsuario = isset($_SESSION['usuarioTpv']) ? $_SESSION['usuarioTpv']['id'] : 0;

        $alarticulo = new alArticulos();
        $stockinicial = $alarticulo->getStock($idArticulo);
        $respuesta = alArticulosStocks::regularizaStock($idArticulo, $idTienda, $stocksumar, K_STOCKARTICULO_SUMA);
        $stockfinal = $alarticulo->getStock($idArticulo);

        alArticulosRegularizacion::grabar([
            'idArticulo' => $idArticulo,
            'idTienda' => $idTienda,
            'stockActual' => $stockinicial['stockOn'],
            'stockModif' => $stocksumar,
            'stockFinal' => $stockfinal['stockOn'],
            'stockOperacion' => K_STOCKARTICULO_SUMA,
            'idUsuario' => $idUsuario
        ]);

//                ->addColumn('fechaRegularizacion', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
//                ->addColumn('stockActual', 'decimal', ['precision' => 17, 'scale' => 6])
//                ->addColumn('stockModif', 'decimal', ['precision' => 17, 'scale' => 6])
//                ->addColumn('stockFinal', 'decimal', ['precision' => 17, 'scale' => 6])
//                ->addColumn('stockOperacion', 'integer', ['limit' => 1, 'default' => 1])
//                ->addColumn('idUsuario', 'integer', ['limit' => 11])




        echo json_encode($respuesta);
        break;
    default : echo json_encode([]);
}
