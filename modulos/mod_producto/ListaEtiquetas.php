<?php
    include_once './../../inicial.php';
	include_once $URLCom.'/modulos/mod_producto/funciones.php';
    include_once $URLCom.'/controllers/Controladores.php';
	include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
	$dedonde="ListaEtiquetas";
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
        $Nproductos[$key]['td_acciones']    ='<a onclick="selecionarItemProducto('
                                            .$articulo['idArticulo'].",'".$dedonde."'".')">'
                                            .'<span class="glyphicon glyphicon-trash"></span>'
                                            .'</a>';
        $Nproductos[$key]['input']          ='<input type="text" size="4" value="1" style="text-align: right" class="cantidadEtiquetas"'
                                            .' data-idarticulo="'.$articulo['idArticulo'].'">';
    }
    /* Ahora montamos array Tpl para esta vista
     * asi podemos utilizar la misma vita para todo.
     * */
     $Tpl = array(  'Titulo' => 'Etiquetas: Imprimir etiquetas del producto',
                    'view_columna' =>'view_columna_etiquetas.php',
                    'view_tabla' =>'view_tabla.php',
                    'th_columnas_mayores' => '<th>Acciones<br/>
                                            <span class="glyphicon glyphicon-trash" title="Eliminamos listado etiquetas"></span>
                                            </th>
                                            <th>Cantidad<br/>Etiquetas</th>'
                );
    include_once $URLCom.'/modulos/mod_producto/template/Tpl_Lista_Seleccionado.php';
?>
