<!DOCTYPE html>
<html>
    <head>
          
        <?php
        include_once './../../inicial.php';
        include_once $URLCom.'/head.php';
        include_once $URLCom.'/modulos/mod_producto/funciones.php';
        include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';
        include_once $URLCom.'/controllers/Controladores.php';
        include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
        include_once ($URLCom .'/controllers/parametros.php');
       
        $OtrosVarJS ='';
        $htmlplugins = array();
        $CTArticulos = new ClaseProductos($BDTpv);
        
        $Controler = new ControladorComun; // Controlado comun..
        // Añado la conexion
        $Controler->loadDbtpv($BDTpv);

        // Cargamos el plugin que nos interesa.

        //  Fin de carga de plugins.

        // Inicializo varibles por defecto.
        $Tienda = $_SESSION['tiendaTpv'];
        $Usuario = $_SESSION['usuarioTpv'];

        $ClasesParametros = new ClaseParametros('parametros.xml');
        $parametros = $ClasesParametros->getRoot();
        // Cargamos configuracion modulo tanto de parametros (por defecto) como si existen en tabla modulo_configuracion 
        $conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
        //~ echo '<pre>';
        //~ print_r($conf_defecto);
        //~ echo '</pre>';
        // Ahora compruebo productos_seleccion:
        $prod_seleccion = array('NItems' => 0, 'display' => '');
        if (isset($_SESSION['productos_seleccionados'])) {
            $prod_seleccion['Items'] = $_SESSION['productos_seleccionados'];
            $prod_seleccion['NItems'] = count($prod_seleccion['Items']);
        }
        if ($prod_seleccion['NItems'] === 0) {
            // No hay productos seleccionados, display none y No en parametro filtro.
            $prod_seleccion['display'] = 'style="display:none"';
            $conf_defecto['filtro']->valor = 'No';
        }
       
        // Obtenemos la configuracion del usuario o la por defecto
        $configuracion = $Controler->obtenerConfiguracion($conf_defecto, 'mod_productos', $Usuario['id']);
        // Compruebo que solo halla un campo por el que buscar por defecto.
        if (!isset($configuracion['tipo_configuracion'])) {
            // Hubo un error en la carga de configuracion.
            $error = array('tipo' => 'danger',
                'dato' => 'Fichero Parametros.xml',
                'mensaje' => 'Error al cargar configuracion, puede ser en el fichero como en tablas modulo_configuracion.'
            );
            $CTArticulos->SetComprobaciones($error);
        }
        $htmlConfiguracion = HtmlListadoCheckMostrar($configuracion['mostrar_lista']);

        if (isset($htmlConfiguracion['error'])) {
            // quiere decir que hubo error en la configuracion.
            $error = array('tipo' => 'danger',
                'dato' => 'Fichero Parametros.xml',
                'mensaje' => $htmlConfiguracion['error']
            );
            $CTArticulos->SetComprobaciones($error);
        }

        // --- Inicializamos objteto de Paginado --- //
        $NPaginado = new PluginClasePaginacion(__FILE__);
        $campos = array($htmlConfiguracion['campo_defecto']);
        $NPaginado->SetCamposControler($Controler, $campos);
        // --- Ahora contamos registro que hay para es filtro --- //
        $filtro = $NPaginado->GetFiltroWhere();
        $CantidadRegistros = 0;
        if (trim($NPaginado->GetFiltroWhere()) !== '') {
            // Solo contamos si tenemos filtro.
            $CantidadRegistros = count($CTArticulos->obtenerProductos($htmlConfiguracion['campo_defecto'], $filtro));
        } else {
            $CantidadRegistros = $CTArticulos->GetNumRows();
        }
        // --- Ahora envio a NPaginado la cantidad registros --- //
        if ($prod_seleccion['NItems'] > 0 && $configuracion['filtro']->valor === 'Si') {
            $NPaginado->SetCantidadRegistros($prod_seleccion['NItems']);
        } else {
            $NPaginado->SetCantidadRegistros($CantidadRegistros);
        }
        $htmlPG = '';
        if ($CantidadRegistros > 0 || $prod_seleccion['NItems'] > 0) {
            $htmlPG = $NPaginado->htmlPaginado();
            // Queremos filtrar o no. 
            if ($configuracion['filtro']->valor === 'Si') {
                if ($prod_seleccion['NItems'] > 0) {
                    if (trim($filtro) !== '') {
                        $filtro .= ' AND (a.idArticulo IN (' . implode(',', $prod_seleccion['Items']) . '))';
                    } else {
                        $filtro = ' WHERE (a.idArticulo IN (' . implode(',', $prod_seleccion['Items']) . '))';
                    }
                }
            }
            $productos = $CTArticulos->obtenerProductos($htmlConfiguracion['campo_defecto'], $filtro . $NPaginado->GetLimitConsulta());
        }


        // Añadimos a JS la configuracion
        echo '<script type="application/javascript"> '
        . 'var configuracion = ' . json_encode($configuracion);
        echo '</script>';
        ?>

        <script src="<?php echo $HostNombre; ?>/jquery/jquery-ui.min.js"></script>
   
        <!-- Cargamos fuciones de modulo. -->
        <script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
        <?php // -------------- Obtenemos de parametros cajas con sus acciones ---------------  //
			$VarJS = $Controler->ObtenerCajasInputParametros($parametros).$OtrosVarJS;
         
		?>	
        <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
        <script src="<?php echo $HostNombre; ?>/plugins/modal/func_modal_reutilizables.js"></script>
        <script type="text/javascript">
            // Declaramos variables globales
            var checkID = [];
        <?php echo $VarJS;?>
        </script>
        <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
        
    </head>

    <body>
		<script type="text/javascript">
			setTimeout(function() {   //pongo un tiempo de focus ya que sino no funciona correctamente
		jQuery('#buscar').focus(); 
	}, 50);
		</script>
<?php
//~ include_once $URLCom.'/header.php';
include_once $URLCom.'/modulos/mod_menu/menu.php';
?>

        <div class="container">
            <?php
// Control de errores..
            $comprobaciones = $CTArticulos->GetComprobaciones();
            if (count($comprobaciones) > 0) {
                foreach ($comprobaciones as $comprobacion) {
                    echo '<div class="alert alert-' . $comprobacion['tipo'] . '">' . $comprobacion['mensaje'] . '</div>';
                    if ($comprobacion['tipo'] === 'danger') {
                        // No permito continuar.
                        exit();
                    }
                }
            }
            ?>

            <div class="row">
                <div class="col-md-12 text-center">
                    <h2> Productos: Editar y Añadir Productos </h2>
                </div>
                <div class="col-sm-2">
                    <div class="nav">
                        <h4> Productos</h4>
                        <h5> Opciones para una selección</h5>
                        <ul class="nav nav-pills nav-stacked"> 
                            <?php
                          if($ClasePermisos->getAccion("crear")==1){
                                ?>
                                <li><a href="#section2" onclick="metodoClick('AgregarProducto');";>Añadir</a></li>
                                <?php
                           }
                            ?>
                            <li><a href="#section2" onclick="metodoClick('VerProducto', 'producto');";>Modificar</a></li>
                        </ul>
                    </div>
                    <div class="nav productos_seleccionados" <?php echo $prod_seleccion['display']; ?>>
                        <h4>Seleccionados <span class="label label-default textoCantidad"><?php echo $prod_seleccion['NItems']; ?></span></h4>
                        <p>Opcion de seleccion:</p>
                        <ul class="nav nav-pills nav-stacked"> 
                            <li><a onclick="filtrarSeleccionProductos();">Filtrar Seleccion</a></li>
                            <li><a onclick="eliminarSeleccionProductos();">Eliminar Selección</a></li>
                            <li><a href='ListaEtiquetas.php' onclick="metodoClick('ImprimirEtiquetas', 'listaEtiqueta');";>Imprimir Etiquetas</a></li>
                            <li><a href='ListaMayor.php'>Imprimir Mayor</a></li>                                                
                        </ul>
                    </div>
                    <div class ="nav">
                        <h4>Configuracion de usuario</h4>
                        <h5>Marca que campos quieres mostrar y por lo quieres buscar.</h5>
                        <?php
                        echo $htmlConfiguracion['htmlCheck'];
                        ?>
                    </div>

                </div>

                <div class="col-md-10">
                    <div class="col-md-12">
                      <?php
                      if (isset($htmlplugins['html'])){
                        echo $htmlplugins['html'];
                      }
                      ?>  
                    </div>
               
                  
					<div>
                    <p>
                        -Productos encontrados BD local filtrados:
                        <?php echo $CantidadRegistros; ?>
                    </p>
                    <?php
                    // Mostramos paginacion 
                    echo $htmlPG;
                    //enviamos por get palabras a buscar, las recogemos al inicio de la pagina
                    ?>
                    <form action="./ListaProductos.php" method="GET" name="formBuscar">
                        <div class="form-group ClaseBuscar">
                            <label>Buscar por:</label>
                            <select onchange="GuardarBusqueda(event);" name="SelectBusqueda" id="sel1"> <?php echo $htmlConfiguracion['htmlOption']; ?> </select>
                            <input id="buscar" type="text" name="buscar" value="<?php echo $NPaginado->GetBusqueda(); ?>">
                            <input type="submit" value="buscar">
                        </div>
                    </form>
                    <!-- TABLA DE PRODUCTOS -->
                    <div>
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>ID</th>
                                    <th>PRODUCTO</th>
                                    <?php
                                    if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'], 'codBarras') === 'Si') {
                                        echo '<th>CODIGO BARRAS</th>';
                                    }
                                    if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'], 'crefTienda') === 'Si') {
                                        echo '<th>REFERENCIA</th>';
                                    }
                                    ?>

                                    <th>COSTE <br/> ULTIMO</th>
                                    <th><span title="Beneficio que tiene ficha">%</span> </th>
                                    <th>Precio<br/>Sin Iva</th>
                                    <th>IVA</th>
                                    <th>P.V.P</th>
                                    <th>Estado</th>
                                    <th>Reg.Stock</th>

                                </tr>
                            </thead>

                            <?php
                            $checkUser = 0;
                            if (isset($productos)) {
                                foreach ($productos as $producto) {
                                    // [RECUERDA]
                                    // Utilizo una funcion js, en global para controlar que item tengo seleccionados,... 
                                    // por eso el uno rowUsuario cuando es productos.
                                    $checkUser = $checkUser + 1;
                                    $checked = "";
                                    if (isset($prod_seleccion['Items'])) {
                                        if (in_array($producto['idArticulo'], $prod_seleccion['Items'])) {
                                            $checked = "checked";
                                        }
                                    }
                                    ?>

                                    <tr>

                                        <td class="rowUsuario"><input type="checkbox" name="checkUsu<?php echo $checkUser; ?>" onclick="selecionarItemProducto(<?php echo $producto['idArticulo']; ?>, 'listaProductos')" value="<?php echo $producto['idArticulo']; ?>" <?php echo $checked; ?>>
                                        </td>
                                        <?php
                                        $htmltd = '<td style="cursor:pointer" onclick="UnProductoClick(' . "'" . $producto['idArticulo'] . "'" . ');">';
                                        echo $htmltd . $producto['idArticulo'] . '</td>';
                                        echo $htmltd . $producto['articulo_name'] . '</td>';
                                        if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'], 'crefTienda') === 'Si') {
                                            $CTArticulos->ObtenerCodbarrasProducto($producto['idArticulo']);
                                            $codBarrasProd = $CTArticulos->GetCodbarras();
                                            echo '<td>';
                                            if ($codBarrasProd) {
                                                foreach ($codBarrasProd as $cod) {
                                                    echo '<small>' . $cod . '</small><br>';
                                                }
                                            }
                                            echo '</td>';
                                        }
                                        ?>
                                        <?php
                                        if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'], 'codBarras') === 'Si') {
                                            $CTArticulos->ObtenerReferenciasTiendas($producto['idArticulo']);
                                            $refTiendas = $CTArticulos->GetReferenciasTiendas();
                                            echo '<td>';
                                            if ($refTiendas) {
                                                foreach ($refTiendas as $ref) {
                                                    echo $ref['crefTienda'];
                                                }
                                            }
                                            echo '</td>';
                                        }
                                        ?>

                                        <td><?php echo number_format($producto['ultimoCoste'], 2); ?></td>
                                        <td><?php echo $producto['beneficio']; ?></td>
                                        <td style="text-align:right;"><?php echo number_format($producto['pvpSiva'], 2); ?><small>€</small></td>
                                        <td><?php echo $producto['iva']; ?></td>
                                        <td style="text-align:right;"><?php echo number_format($producto['pvpCiva'], 2); ?><small>€</small></td>
                                        <td><?php echo $producto['estado']; ?></td>
                                        <td><button class="btn btn-sm boton-regularizar" data-idarticulo="<?php echo $producto['idArticulo']; ?>">regularizar</button></td>
                                    </tr>

                                    <?php
                                }
                            }
                            ?>

                        </table>
                    </div>
                </div>
            </div>
            <?php
            //~ echo '<pre>';
            //~ print_r($_SESSION['productos_seleccionados']);
            //~ echo '</pre>';
            //~ echo count($_SESSION['productos_seleccionados']);
            ?>
        </div>
        <!-- Modal -->
        <div id="regularizaStockModal" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header btn-primary">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h3 class="modal-title text-center">Titulo configurable</h3>
                    </div>
                    <form id="fregulariza" action="javascript:grabarRegularizacion();">
                        <div class="modal-body">
                            <table>
                                <tr>
                                    <td colspan="3">
                                        <h5>Articulo a regularizar:<p id="nombre" > </p> </h5></td>                                    
                                </tr>
                                <tr>
                                    <td>Stock Actual</td>
                                    <td>Stock a colocar</td>
                                    <td>SUMAR al stock</td>
                                </tr>
                                <tr>
                                    <td><input type="text" id="stockactual" value="000" readonly="readonly"/></td>
                                    <td><input type="text" id="stockcolocar" value="000" /></td>
                                    <td><input type="text" id="stocksumar" value="000" /></td>
                                </tr>
                            </table>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-default" >Guardar</button>
                            <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                        </div>
                        <input type="hidden" id="articuloid" value="000" />
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>
