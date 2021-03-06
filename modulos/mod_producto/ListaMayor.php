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
        $Nproductos[$key]['td_acciones']    = '<a class="btn" onclick="selecionarItemProducto('
                                            . $articulo['idArticulo'].",'ListaMayor')".'">'
                                            . '<span class="glyphicon glyphicon-trash"></span>'
                                            .'</a>'
                                            . '<a class="btn" onclick="redirecionarMayor('
                                            . $articulo['idArticulo'].",'DetalleMayor')".'">'
                                            . '<span class="glyphicon glyphicon-eye-open"></span>'
                                            . '</a>'
                                            . '<span class="glyphicon glyphicon-print"></span>';
        $Nproductos[$key]['input']          = '<input type="text" size="6" value="0" style="text-align: right" '
                                            . 'id="stkini' . $articulo['idArticulo'].'">';
    }
    $fecha = date('Y-m-d');
    $fecha_inicial = strtotime ( '- 15 days' , strtotime ( $fecha ) ) ;
    $fecha_inicial = date ( 'Y-m-d' , $fecha_inicial );


    /* Ahora montamos array Tpl para esta vista
     * asi podemos utilizar la misma vita para todo.
     * */
     $Tpl = array(  'Titulo' => 'Mayor del productos seleccionados',
                    'view_columna' =>'view_columna_mayor.php',
                    'view_tabla' =>'view_tabla.php',
                    'th_columnas_mayores' => '<th>
                                            Acciones<br/>
                                            <span class="glyphicon glyphicon-trash" title="Eliminamos listado etiquetas"></span>
                                            <span class="glyphicon glyphicon-eye-open" title="Ver por pantalla mayor"></span>
                                            <span class="glyphicon glyphicon-print" title="Crear pdf para imprimir Mayor"></span>
                                            </th>
                                            <th>Stock<br>INICIAL<span class="glyphicon glyphicon-info-sign" title="Indicamos con cuanto stock quieres que empiece el mayor"></span></th>'
                );
    include_once $URLCom.'/modulos/mod_producto/template/Tpl_Lista_Seleccionado.php';
?>









