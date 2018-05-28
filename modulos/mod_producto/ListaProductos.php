<!DOCTYPE html>
<html>
    <head>
        <?php
        include './../../head.php';
        include './funciones.php';
        //~ include ("./../../plugins/paginacion/paginacion.php");
        include ("./../../plugins/paginacion/ClasePaginacion.php");


        include ("./../../controllers/Controladores.php");
        include ("./clases/ClaseProductos.php");
        include_once ($RutaServidor . $HostNombre . '/controllers/parametros.php');
        $CTArticulos = new ClaseProductos($BDTpv);
        $Controler = new ControladorComun; // Controlado comun..
        // Añado la conexion
        $Controler->loadDbtpv($BDTpv);
        // Inicializo varibles por defecto.
        $Tienda = $_SESSION['tiendaTpv'];
        $Usuario = $_SESSION['usuarioTpv'];

        $ClasesParametros = new ClaseParametros('parametros.xml');
        $parametros = $ClasesParametros->getRoot();
        // Cargamos configuracion modulo tanto de parametros (por defecto) como si existen en tabla modulo_configuracion 
        $conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
        // Ahora compruebo productos_seleccion:
        $prod_seleccion = array('NItems' => 0, 'display' => '');
        if (isset($_SESSION['productos_seleccionados'])) {
            $prod_seleccion['Items'] = $_SESSION['productos_seleccionados'];
            $prod_seleccion['NItems'] = count($prod_seleccion['Items']);
        }
        if ($prod_seleccion['NItems'] === 0) {
            // No hay productos seleccionados, diplay none y No en parametro filtro.
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
        if ($NPaginado->GetFiltroWhere() !== '') {
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
                    if ($filtro !== '') {
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

        <script>
            // Declaramos variables globales
            var checkID = [];

        </script> 
        <!-- Cargamos fuciones de modulo. -->
        <script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
        <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
    </head>

    <body>
<?php
include './../../header.php';
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
if ($Usuario['group_id'] > '0') {
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
                            <input type="text" name="buscar" value="<?php echo $NPaginado->GetBusqueda(); ?>">
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

    </body>
</html>
