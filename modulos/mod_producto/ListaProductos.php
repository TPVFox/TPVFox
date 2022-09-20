<?php
    include_once './../../inicial.php';
    include_once $URLCom.'/modulos/mod_producto/funciones.php';
    include_once $URLCom.'/controllers/Controladores.php';
    include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
    include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';
    include_once ($URLCom .'/controllers/parametros.php');
    include_once $URLCom.'/modulos/mod_familia/clases/ClaseFamilias.php';
    include_once $URLCom.'/clases/Proveedores.php';
    $OtrosVarJS ='';
    $htmlplugins = array();
    $CTArticulos = new ClaseProductos($BDTpv);
    $CFamilia=new ClaseFamilias($BDTpv);
    $CProveedor=new Proveedores($BDTpv);
    $Controler = new ControladorComun; // Controlado comun..
    // Añado la conexion
    $Controler->loadDbtpv($BDTpv);
    $id_tienda_principal = $Tienda['idTienda'];
    // Cargamos el plugin que nos interesa.

    //  Fin de carga de plugins.

    // Inicializo varibles por defecto.
   
    $ClasesParametros = new ClaseParametros('parametros.xml');
    $parametros = $ClasesParametros->getRoot();
    // Cargamos configuracion modulo tanto de parametros (por defecto) como si existen en tabla modulo_configuracion 
    $conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
    // Parametro de configuracion para indicar que por defecto no filtramos los productos seleccionados.
    $conf_defecto['filtro']->valor = 'No';

    // Ahora compruebo productos_seleccion:
    $botonSeleccion=0;
    $prod_seleccion = array('NItems' => 0, 'display' => '');
    if (isset($_SESSION['productos_seleccionados'])) {
        $prod_seleccion['Items'] = $_SESSION['productos_seleccionados'];
        $prod_seleccion['NItems'] = count($prod_seleccion['Items']);
    }
    if ($prod_seleccion['NItems'] === 0) {
        // No hay productos seleccionados, display none y No en parametro filtro.
        $prod_seleccion['display'] = 'style="display:none"';
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

    // Montar select Estado y añadir a configuracion seleccion estado.
    $option_sinFiltrar = '<option value="Sin Filtrar">Sin Filtrar</option>';
    if (!isset($configuracion['estado_filtro'])){
        // No existe estado_filtro por lo que ponemos por defecto
        $configuracion['estado_filtro'] ='';
        $option_sinFiltrar = '<option value="Sin Filtrar" selected>Sin Filtrar</option>';

    }
    $posibles_estados_producto = $CTArticulos->posiblesEstados('articulos');
    $htmlEstadosProducto =  $option_sinFiltrar;
    $htmlEstadosProducto .= htmlOptionEstados($posibles_estados_producto,$configuracion['estado_filtro']);
    $filtro_estado = '';
    if ($configuracion['estado_filtro'] !==''){
        $filtro_estado = 'a.estado="'.$configuracion['estado_filtro'].'"';
    }

    // --- Inicializamos objeto de Paginado --- //
    $NPaginado = new PluginClasePaginacion(__FILE__);
    $campos = array($htmlConfiguracion['campo_defecto']);
    $NPaginado->SetCamposControler($campos);
    // --- Ahora contamos registro que hay para es filtro --- //
    $filtro = $NPaginado->GetFiltroWhere();
    $CantidadRegistros = 0;
    if (trim($filtro)!== '') {
        // Solo contamos si tenemos filtro.
        if ($filtro_estado !== ''){
            $filtro = $filtro.' AND '.$filtro_estado;
        }
        $CantidadRegistros = count($CTArticulos->obtenerProductos($htmlConfiguracion['campo_defecto'],compact("filtro")));
    } else {
        if ($filtro_estado !== ''){
            // Si filtramos por estado.
            $filtro = 'WHERE '.$filtro_estado;
            $CantidadRegistros = count($CTArticulos->obtenerProductos($htmlConfiguracion['campo_defecto'], compact("filtro")));
        } else {
            $CantidadRegistros = $CTArticulos->GetNumRows();
        }
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
                $botonSeleccion=1;
                if (trim($filtro) !== '') {
                    $filtro .= ' AND (a.idArticulo IN (' . implode(',', $prod_seleccion['Items']) . '))';
                } else {
                    $filtro = ' WHERE (a.idArticulo IN (' . implode(',', $prod_seleccion['Items']) . '))';
                }
            }
        }
        $limite = $NPaginado->GetLimitConsulta();
        $productos = $CTArticulos->obtenerProductos($htmlConfiguracion['campo_defecto'], compact("filtro","limite"));
    }
    
    if (isset($productos['error'])){
        //Hubo un error a la ahora obtener los datos de los productos.
        $error = array('tipo' => 'danger',
            'dato' => $productos['error'],
            'mensaje' => $productos['consulta']
        );
        $CTArticulos->SetComprobaciones($error);
    }
    // Obtenemos todos los proveedores para realizar la busqueda producto por proveedores.
    $todosProveedores= $CProveedor->todosProveedores();
    if (isset( $todosProveedores['error'])){
        //Hubo un error a la ahora obtener los datos de los productos.
        $error = array('tipo' =>'warning',
            'dato' => $todosProveedores['error'],
            'mensaje' => $todosProveedores['error'].' :'.$todosProveedores['consulta']
        );
        $CTArticulos->SetComprobaciones($error);
    }
    $script_ObjVirtuemart = '';
    if ($CTArticulos->SetPlugin('ClaseVirtuemart') !== false){
        $ObjVirtuemart = $CTArticulos->SetPlugin('ClaseVirtuemart');
        $script_ObjVirtuemart = $ObjVirtuemart->htmlJava();
        $tiendaWeb=$ObjVirtuemart->getTiendaWeb();
    }

    // -------------- Obtenemos de parametros cajas con sus acciones ---------------  //
    $VarJS = $Controler->ObtenerCajasInputParametros($parametros).$OtrosVarJS;
?>
<!DOCTYPE html>
<html>
    <head>
        <?php
        include_once $URLCom.'/head.php';
        // Añadimos a JS la configuracion
        echo $script_ObjVirtuemart.'<script type="application/javascript"> '
        . 'var configuracion = ' . json_encode($configuracion);
        echo '</script>';
        ?>
        <script src="<?php echo $HostNombre; ?>/jquery/jquery-ui.min.js"></script>
        <script src="<?php echo $HostNombre; ?>/lib/js/autocomplete.js"></script>   
        <script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
        <script src="<?php echo $HostNombre; ?>/modulos/mod_producto/js/AccionesDirectas.js"></script>
        <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
        <link rel="stylesheet" href="<?php echo $HostNombre;?>/jquery/jquery-ui.min.css" type="text/css">
        <script type="text/javascript">
            // Declaramos variables globales
            var checkID = [];
        <?php echo $VarJS;?>
        </script>
        <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
    </head>

    <body>
        <?php include_once $URLCom.'/modulos/mod_menu/menu.php'; ?>
		<script type="text/javascript">
			setTimeout(function()
            {   //pongo un tiempo de focus ya que sino no funciona correctamente
                jQuery('#buscar').focus(); 
            }, 50);
		</script>
       
        

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
                <div class="col-sm-12 text-center">
                    <h2> Productos: Editar y Añadir Productos </h2>
                </div>
                <div class="col-sm-2 col-xs-12">
                    <div>
                        <h4> Productos</h4>
                        <h5> Opciones para una selección</h5>
                        <ul class="nav nav-pills nav-stacked"> 
                            <?php
                          if($ClasePermisos->getAccion("crear")==1){
                                ?>
                                <li><a onclick="metodoClick('AgregarProducto');">Añadir</a></li>
                                <?php
                           }
                            if($ClasePermisos->getAccion("modificar")==1){
                            ?>
                            <li><a onclick="metodoClick('VerProducto', 'producto');">Modificar</a></li>
                            <?php 
                            }
                            ?>
                        </ul>
                    </div>
                    <div class="productos_seleccionados" <?php echo $prod_seleccion['display']; ?>>
                        <h4>Opciones Seleccionados <span class="label label-default textoCantidad"><?php echo $prod_seleccion['NItems']; ?></span></h4>
                        <p>Filtrar seleccionados:
                        <input type="checkbox" id="checkSeleccion" name="checkSeleccion" onclick="seleccionProductos()">
                        </p>
                        <ul class=""> 
                            <?php 
                             if($ClasePermisos->getAccion("eliminarSeleccion")==1){
                            ?>
                                <li><a onclick="eliminarSeleccionProductos();">Eliminar Selección</a></li>
                            <?php
                            }
                            if($ClasePermisos->getAccion("imprimirEtiquetas")==1){
                             ?>
                                <li><a href='ListaEtiquetas.php' onclick="metodoClick('ImprimirEtiquetas', 'listaEtiqueta');">Imprimir Etiquetas</a></li>
                           <?php 
                            }
                            if($ClasePermisos->getAccion("imprimirMayor")==1){
                           ?>
                                <li><a href='ListaMayor.php'>Imprimir Mayor</a></li>  
                            <?php 
                            }
                            if($ClasePermisos->getAccion("subirProductosWeb")==1){
                                if( isset($tiendaWeb['idTienda'])){
                            ?>      
                                <li><a onclick="subirProductosWeb(<?php echo $tiendaWeb['idTienda'];?>);">Subir Productos Web</a></li>
                            <?php
                                }
                            }
                            if($ClasePermisos->getAccion("agregarProductosFamilia")==1){
                            ?>
                                <li><a onclick="modalFamiliaProducto();">Guardar por familia</a></li>   
                            <?php 
                            }
                            if($ClasePermisos->getAccion("cambiarEstado")==1){
                            ?> 
                                <li><a onclick="modalEstadoProductos();">Cambiar estado productos</a></li>   
                            <?php 
                            }
                            ?>             
                        </ul>
                        <?php
                        if($ClasePermisos->getAccion("eliminarProductos")==1){
                                $id_ti = 0;
                                if( isset($tiendaWeb['idTienda'])){
                                    // Si existe ponemos valor
                                    $id_ti = $tiendaWeb['idTienda'];
                                }
                             ?>     
                                <div><a class="btn btn-danger" onclick="eliminarProductos(<?php echo $id_ti;?>);">Eliminar Productos</a></div>        
                            <?php
                        }
                        ?>
                    </div>
                    <div class ="nav_configuracion">
                        <h4>Configuracion de usuario</h4>
                        <h5>Marca que campos quieres mostrar y por lo quieres buscar.</h5>
                        <?php
                        echo $htmlConfiguracion['htmlCheck'];
                        ?>
                    </div>
                </div>

                <div class="col-sm-10 col-xs-12">
                    <div class="col-md-12">
                      <?php
                      if (isset($htmlplugins['html'])){
                        echo $htmlplugins['html'];
                      }
                      ?>  
                    </div>
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
                        <div class="form-group ClaseBuscar col-md-4">
                            <label>Buscar por:
                            <select onchange="GuardarBusqueda(event);" name="SelectBusqueda" id="sel1"> <?php echo $htmlConfiguracion['htmlOption']; ?> </select></label>
                            <input id="buscar" type="text" name="buscar" size="25" value="<?php echo $NPaginado->GetBusqueda(); ?>">
                            <input type="submit" value="buscar">
                        </div>
                        <div id="familiasDiv" class="col-md-3">
                            <div class="ui-widget">
                             <label for="tags">Buscar por Familias:</label>
                             <select id="combobox" class="familiasLista">
                                <option></option>
                                <option value="0">Productos sin familia</option>
                                 <?php 
                                   $arbolfamilias=selectFamilias(0, '', array(), $BDTpv);
                                   foreach($arbolfamilias as $familia){
                                       echo '<option title ="'.$familia['title'].'" value="'.$familia['id'].'">'.$familia['name'].'</option>';
                                   }
                                 ?>
                            </select>
                            </div>
                            <p id="botonEnviar"></p>
                        </div>
                         <div id="ProveedoresDiv" class="col-md-3">
                            <div class="ui-widget">
                                <label for="tags">Buscar por Proveedores:</label>
                                   <select id="combobox" class="proveedoresLista">
                                        <option value="0"></option>
                                       <?php
                                       foreach ($todosProveedores as $pro){
                                            echo '<option value="'.$pro['idProveedor'].'">'.$pro['nombrecomercial'].'</option>';
                                       }
                                       ?>
                                    </select>
                            </div>
                            <p id="botonEnviarPro"></p>
                        </div>
                        <div id="EstadoDiv" class="col-md-2">
                            <label>Filtrar estado</label>
                            <select onchange="GuardarFiltroEstado(event);" name="FiltroEstado" id="sel1"> <?php echo $htmlEstadosProducto; ?> </select>
                        </div>
                    </form>
                    <!-- TABLA DE PRODUCTOS -->
                    <div>
                        <?php
                        // Generamos Script con array de los productos de esta pagina para poder ejecutar ajax
                        // para comprobar el estado en la web.
                        if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'], 't.idVirtuemart')==='Si'){
                            if ($CTArticulos->SetPlugin('ClaseVirtuemart') !== false){
                                if (isset($productos)) {
                                // Si existen productos.
                                $ids= array_column($productos, 'idArticulo');
                                echo '<script type="text/javascript">
                                            var ids_productos='.json_encode($ids).';
                                            var id_tiendaWeb ='.$tiendaWeb['idTienda'].';';
                                echo '</script>';
                                }
                            }
                        }
                        ?>
                        <table class="table table-bordered table-hover tablaPrincipal">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="checkUsuTodos" name="checkUsuTodos" onclick="seleccionarTodo()"></th>
                                    <th>ID</th>
                                    <th>PRODUCTO</th>
                                    <?php
                                    if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'], 'codBarras') === 'Si') {
                                        echo '<th><span class="glyphicon glyphicon-barcode" title="CODIGO BARRAS"></span></th>';
                                    }
                                    if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'], 't.crefTienda') === 'Si') {
                                        echo '<th>REFERENCIA</th>';
                                    }
                                    ?>
                                    <th>COSTE <br/> ULTIMO</th>
                                    <th><span title="Beneficio que tiene ficha">%</span> </th>
                                    <th>Precio<br/>Sin Iva</th>
                                    <th>IVA</th>
                                    <th>P.V.P</th>
                                    <th>Stock</th>
                                    <th>Estado</th>
                                    <?php 
                                    if(isset($tiendaWeb)){
                                        if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'], 't.idVirtuemart') === 'Si'){
                                            echo'<th>WEB</th>';
                                        }
                                    }
                                    ?>
                                </tr>
                            </thead>

                            <?php
                            $checkUser = 0;
                            if (isset($productos)) {
                                foreach ($productos as $prod) {
                                    $producto=$CTArticulos->GetProducto($prod['idArticulo']);
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
                                    $textoFamilia="";
                                    if(is_array($producto['familias'])){
                                        $familias=$producto['familias'];
                                        foreach ($familias as $familia){
                                            $textoFamilia.=' '.$familia['familiaNombre'];
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td class="rowUsuario"><input type="checkbox" id="checkUsu<?php echo $checkUser; ?>" name="checkUsu<?php echo $checkUser; ?>" onclick="selecionarItemProducto(<?php echo $producto['idArticulo']; ?>, 'listaProductos')" value="<?php echo $producto['idArticulo']; ?>" <?php echo $checked; ?>>
                                        </td>
                                        <?php
                                        $htmltd = '<td style="cursor:pointer" onclick="UnProductoClick(' . "'" . $producto['idArticulo'] . "'" . ');">';
                                        echo $htmltd . $producto['idArticulo'] . '</td>';
                                        echo $htmltd . $producto['articulo_name'] . '<br><SUB>'.$textoFamilia.'</SUB></td>';
                                        if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'], 'codBarras') === 'Si') {
                                            echo '<td>';
                                            if (count($producto['codBarras'])>0) {
                                                foreach ($producto['codBarras'] as $cod) {
                                                    echo '<small>' . $cod . '</small><br>';
                                                }
                                            }
                                            echo '</td>';
                                        }
                                        if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'], 't.crefTienda') === 'Si') {
                                            echo '<td>';
                                            if (count($producto['ref_tiendas'])>0) {
                                                foreach ($producto['ref_tiendas'] as $ref) {
                                                    if($ref['idTienda']==$id_tienda_principal){
                                                        echo $ref['crefTienda'];
                                                    }
                                                    
                                                }
                                            }
                                            echo '</td>';
                                        }
                                        ?>

                                        <td><?= number_format($producto['ultimoCoste'], 2)?></td>
                                        <td><?= $producto['beneficio']?></td>
                                        <td style="text-align:right;"><?= number_format($producto['pvpSiva'], 2)?><small>€</small></td>
                                        <td><?=$producto['iva']?></td>
                                        <td style="text-align:right;"><?php echo number_format($producto['pvpCiva'], 2); ?><small>€</small></td>
                                        <td>
                                            <?php
                                            $decimal = 0;
                                            if ($producto['tipo'] == 'peso'){
                                                $decimal = 3;
                                            } 
                                            echo number_format($producto['stocks']['stockOn'],$decimal);
                                            if($ClasePermisos->getAccion("regularizar")==1){
                                            ?>
                                                <button class="btn btn-sm boton-regularizar" data-idarticulo="<?php echo $producto['idArticulo']; ?>"><span class="glyphicon glyphicon-pencil"></span></button>
                                            <?php 
                                            }
                                            ?>
                                            </td>
                                        <td><?php echo $producto['estado']; ?></td>

                                            <?php 
                                        if(isset($tiendaWeb)){
                                            if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'], 't.idVirtuemart') === 'Si'){
                                            ?>
                                            <td id="idProducto_estadoWeb_<?php echo $producto['idArticulo'];?>" class="icono_web despublicado">
                                            <?php
                                            if($CTArticulos->GetReferenciasTiendas()){
                                                foreach ($CTArticulos->GetReferenciasTiendas() as $ref){
                                                    if($ref['idVirtuemart']>0){
                                                        $ObjVirtuemart = $CTArticulos->SetPlugin('ClaseVirtuemart');     
                                                        $link=  $ObjVirtuemart->ruta_producto.$ref['idVirtuemart'];
                                                        echo '  <a target="_blank" class="glyphicon glyphicon-globe" href="'.$link.'"></a>';
                                                    }
                                                }
                                            } 
                                     
                                            ?>
                                            
                                            </td>
                                            <?php
                                            }
                                        }
                                            ?>
                                    </tr>

                                    <?php
                                }
                            }
                            ?>

                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal -->
        <?php // Incluimos paginas modales
		echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
		include $URLCom.'/plugins/modal/ventanaModal.php';
		?>
        
        </div>
        <div class="loader"></div>
        <script>
        <?php 
        if($botonSeleccion==1){
            ?>
             $("#checkSeleccion").prop( "checked", true );
            <?php
        }else{
            ?>
             $("#checkSeleccion").prop( "checked", false );
            <?php
        }
        
        ?>
        <?php
        // Solo ejecutamos si hay producto y hay web,
        if (MostrarColumnaConfiguracion($configuracion['mostrar_lista'], 't.idVirtuemart') === 'Si'){
            if ($CTArticulos->SetPlugin('ClaseVirtuemart') !== false){
                if (isset($productos)) {
            ?>
                    $(document).ready(function() {
                        obtenerEstadoProductoWeb(ids_productos,id_tiendaWeb);
                    });
            <?php
                }
            }
        }
        ?>

        
        </script>
         <style>
#enlaceIcon{
    height: 2.2em;
}
 .custom-combobox {
    position: relative;
    display: inline-block;
  }
  .custom-combobox-toggle {
    position: absolute;
    top: 0;
    bottom: 0;
    margin-left: -1px;
    padding: 0;
  }
  .custom-combobox-input {
    margin: 0;
    padding: 5px 10px;
  }
  ul.ui-autocomplete {
    z-index: 1050;
}
.loader {
    position: fixed;
    left: 0px;
    top: 0px;
    width: 100%;
    height: 100%;
    z-index: 9999;
    background: url('<?php echo $HostNombre?>/css/img/loading.gif') 50% 50% no-repeat rgb(249,249,249);
    opacity: .8;
    display:none;
}
</style>
    </body>
</html>
