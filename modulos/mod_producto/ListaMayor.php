<?php
    include_once './../../inicial.php';
    include_once $URLCom.'/modulos/mod_producto/funciones.php';
    include_once $URLCom.'/controllers/Controladores.php';
    include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
    $dedonde = "ListaMayor";
    $ClassProductos = new ClaseProductos($BDTpv);
    $Controler = new ControladorComun; 
    $idTienda = $Tienda['idTienda'];
    $Nproductos = array();
    foreach ($_SESSION['productos_seleccionados'] as $key =>$producto) {
        $articulo = $ClassProductos->GetProducto($producto);
        $Nproductos[$key]['idArticulo']     = $articulo['idArticulo'];
        $Nproductos[$key]['pvpCiva']        = $articulo['pvpCiva'];
        $Nproductos[$key]['ultimoCoste']    = $articulo['ultimoCoste'];
        $Nproductos[$key]['estado']         = $articulo['estado'];
        $Nproductos[$key]['articulo_name']  = $articulo['articulo_name'];
        $Nproductos[$key]['stock']          = $articulo['stocks']['stockOn'];
        $Nproductos[$key]['tipo']           = $articulo['tipo'];
    }
    $fecha = date('Y-m-d');
    $fecha_inicial = strtotime ( '- 15 days' , strtotime ( $fecha ) ) ;
    $fecha_inicial = date ( 'Y-m-d' , $fecha_inicial );


    /* Ahora montamos array Tpl para esta vista
     * asi podemos utilizar la misma vita para todo.
     * */
     $Tpl = array(  'Titulo' => 'Mayor del productos seleccionados',
                    'view_columna' =>'view_columna_mayor.php',
                    'view_tabla' =>'view_tabla_mayor.php',
                );
    include_once $URLCom.'/modulos/mod_producto/template/Tpl_Lista_Seleccionado.php';
?>

