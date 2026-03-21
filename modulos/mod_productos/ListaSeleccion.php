<?php
include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_producto/funciones.php';
include_once $URLCom . '/controllers/Controladores.php';
include_once $URLCom . '/modulos/mod_producto/clases/ClaseProductos.php';
include_once __DIR__ . '/clases/ClaseSeleccionProductos.php';

$modo = $_GET['modo'] ?? 'etiquetas'; // 'etiquetas' | 'mayor'

$CTArticulos = new ClaseProductos($BDTpv);
$Controler   = new ControladorComun();
$CSeleccion  = new ClaseSeleccionProductos((int) $Usuario['id'], __DIR__ . '/cache');

$ids = $CSeleccion->getIds();
if (empty($ids)) {
    header('Location: ListaProductos.php');
    exit();
}

$Nproductos = [];
foreach ($ids as $key => $idProducto) {
    $articulo = $CTArticulos->GetProducto($idProducto);
    $Nproductos[$key]['idArticulo']    = $articulo['idArticulo'];
    $Nproductos[$key]['pvpCiva']       = $articulo['pvpCiva'];
    $Nproductos[$key]['ultimoCoste']   = $articulo['ultimoCoste'];
    $Nproductos[$key]['estado']        = $articulo['estado'];
    $Nproductos[$key]['articulo_name'] = $articulo['articulo_name'];
    $Nproductos[$key]['stock']         = $articulo['stocks']['stockOn'];
    $Nproductos[$key]['tipo']          = $articulo['tipo'];

    if ($modo === 'etiquetas') {
        $Nproductos[$key]['input'] = '<input type="text" size="4" value="1" style="text-align:right" '
            . 'class="cantidadEtiquetas" data-idarticulo="' . $articulo['idArticulo'] . '">';
    } else {
        $Nproductos[$key]['input'] = '<input type="text" size="6" value="0" style="text-align:right" '
            . 'id="stkini' . $articulo['idArticulo'] . '">';
    }
}

if ($modo === 'mayor') {
    $fecha          = date('Y-m-d');
    $fecha_inicial  = date('Y-m-d', strtotime('-15 days', strtotime($fecha)));
}

include __DIR__ . '/template/view_seleccion.php';
