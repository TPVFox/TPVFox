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
        include_once $URLCom.'/modulos/mod_familia/clases/ClaseFamilias.php';
        include_once $URLCom.'/modulos/mod_proveedor/clases/ClaseProveedor.php';
        include_once $URLCom.'/modulos/mod_tienda/clases/ClaseTienda.php';
        $OtrosVarJS ='';
        $htmlplugins = array();
        $CTArticulos = new ClaseProductos($BDTpv);
        $CFamilia=new ClaseFamilias($BDTpv);
        $CProveedor=new ClaseProveedor($BDTpv);
        $CTienda=new ClaseTienda($BDTpv);
        $Controler = new ControladorComun; // Controlado comun..
        // Añado la conexion
        $Controler->loadDbtpv($BDTpv);
        
        // Cargamos el plugin que nos interesa.

        //  Fin de carga de plugins.

        // Inicializo varibles por defecto.
       
        $ClasesParametros = new ClaseParametros('parametros.xml');
        $parametros = $ClasesParametros->getRoot();
        // Cargamos configuracion modulo tanto de parametros (por defecto) como si existen en tabla modulo_configuracion 
        $conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
        //~ echo '<pre>';
        //~ print_r($conf_defecto);
        //~ echo '</pre>';
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
                    $botonSeleccion=1;
                    if (trim($filtro) !== '') {
                        $filtro .= ' AND (a.idArticulo IN (' . implode(',', $prod_seleccion['Items']) . '))';
                    } else {
                        $filtro = ' WHERE (a.idArticulo IN (' . implode(',', $prod_seleccion['Items']) . '))';
                    }
                }
            }
            
            $productos = $CTArticulos->obtenerProductos($htmlConfiguracion['campo_defecto'], $filtro . $NPaginado->GetLimitConsulta());
        }

       
        
        $todosProveedores= $CProveedor->todosProveedores();
     
         
         if ($CTArticulos->SetPlugin('ClaseVirtuemart') !== false){
            $ObjVirtuemart = $CTArticulos->SetPlugin('ClaseVirtuemart');
            echo $ObjVirtuemart->htmlJava();
            $tiendaWeb=$ObjVirtuemart->getTiendaWeb();
         }
        
        // -------------- Obtenemos de parametros cajas con sus acciones ---------------  //
		$VarJS = $Controler->ObtenerCajasInputParametros($parametros).$OtrosVarJS;
        // Añadimos a JS la configuracion
        echo '<script type="application/javascript"> '
        . 'var configuracion = ' . json_encode($configuracion);
        echo '</script>';
        ?>
      
        <script src="<?php echo $HostNombre; ?>/jquery/jquery-ui.min.js"></script>
<!--
        <script src="<?php echo $HostNombre; ?>/jquery/jquery-ui.js"></script>
-->
        <script src="<?php echo $HostNombre; ?>/lib/js/autocomplete.js"></script>   
          <script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
        <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
        <script src="<?php echo $HostNombre; ?>/plugins/modal/func_modal_reutilizables.js"></script>
         
        <link rel="stylesheet" href="<?php echo $HostNombre;?>/jquery/jquery-ui.min.css" type="text/css">
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
                            if($ClasePermisos->getAccion("modificar")==1){
                            ?>
                            <li><a href="#section2" onclick="metodoClick('VerProducto', 'producto');";>Modificar</a></li>
                            <?php 
                            }
                            ?>
                        </ul>
                    </div>
                    <div class="nav productos_seleccionados" <?php echo $prod_seleccion['display']; ?>>
                        <h4>Seleccionados <span class="label label-default textoCantidad"><?php echo $prod_seleccion['NItems']; ?></span></h4>
                        <p>Opcion de seleccion:</p>
                        <ul class="nav nav-pills nav-stacked"> 
                            <input type="checkbox" id="checkSeleccion" name="checkSeleccion" onclick="seleccionProductos()"> Selección Productos
                            <?php 
                             //~ if($ClasePermisos->getAccion("filtrarSeleccion")==1){
                            ?>
<!--
                            <li><a onclick="filtrarSeleccionProductos();">Filtrar Seleccion</a></li>
-->
                            <?php 
                            //~ }
                             if($ClasePermisos->getAccion("eliminarSeleccion")==1){
                            ?>
                            <li><a onclick="eliminarSeleccionProductos();">Eliminar Selección</a></li>
                            <?php
                            }
                            if($ClasePermisos->getAccion("imprimirEtiquetas")==1){
                             ?>
                            <li><a href='ListaEtiquetas.php' onclick="metodoClick('ImprimirEtiquetas', 'listaEtiqueta');";>Imprimir Etiquetas</a></li>
                           <?php 
                            }
                            if($ClasePermisos->getAccion("imprimirMayor")==1){
                           ?>
                            <li><a href='ListaMayor.php'>Imprimir Mayor</a></li>  
                            <?php 
                            }
                            if($ClasePermisos->getAccion("subirProductosWeb")==1){
                            ?>      
                            <li><a onclick="subirProductosWeb(<?php echo $tiendaWeb['idTienda'];?>);">Subir Productos Web</a></li>                                   
                            <?php 
                            }
                            if($ClasePermisos->getAccion("agregarProductosFamilia")==1){
                            ?>
                             <li><a onclick="modalFamiliaProducto();">Guardar por familia</a></li>   
                            <?php 
                            }
                             if($ClasePermisos->getAccion("eliminarProductos")==1){
                             ?>     
                                <li><a onclick="eliminarProductos(<?php echo $tiendaWeb['idTienda'];?>);">Eliminar Productos</a></li>        
                            <?php 
                            }
                             if($ClasePermisos->getAccion("cambiarEstado")==1){
                            ?> 
                            <li><a onclick="modalEstadoProductos();">Cambiar estado productos</a></li>   
                            <?php 
                            }
                            ?>             
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
                        <div class="form-group ClaseBuscar col-md-5">
                            <label>Buscar por:</label>
                            <select onchange="GuardarBusqueda(event);" name="SelectBusqueda" id="sel1"> <?php echo $htmlConfiguracion['htmlOption']; ?> </select>
                            <input id="buscar" type="text" name="buscar" size="10" value="<?php echo $NPaginado->GetBusqueda(); ?>">
                            <input type="submit" value="buscar">
                        </div>
                        <div id="familiasDiv" class="col-md-3">
                            <div class="ui-widget">
                             <label for="tags">Buscar por Familias:</label>
                             <select id="combobox" class="familiasLista">
                                <option value="0"></option>
                                <option value="01">-Productos sin familia</option>
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
                                       
                                       foreach ($todosProveedores['datos'] as $pro){
                                            echo '<option value="'.$pro['idProveedor'].'">'.$pro['nombrecomercial'].'</option>';
                                       }
                                       ?>
                                    </select>
                            </div>
                            <p id="botonEnviarPro"></p>
                        </div>
                    </form>
                    <!-- TABLA DE PRODUCTOS -->
                    <div>
                        <table class="table table-bordered table-hover tablaPrincipal">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="checkUsuTodos" name="checkUsuTodos" onclick="seleccionarTodo()"></th>
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
                                    <?php 
                                    if(isset($tiendaWeb)){
                                        ?>
                                        <th>WEB</th>
                                        <?php
                                    }
                                    ?>
                                    

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
                                    $textoFamilia="";
                                    $familia=$CFamilia->familiaDeProducto($producto['idArticulo']);
                                    if(isset($familia['datos'])){
                                        foreach ($familia['datos'] as $nombreFamilia){
                                            $textoFamilia.=' '.$nombreFamilia['nombreFamilia'];
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
                                            $tiendaPrincipal=$CTienda->tiendaPrincipal();
                                            
                                            echo '<td>';
                                            if ($refTiendas) {
                                                foreach ($refTiendas as $ref) {
                                                    if($ref['idTienda']==$tiendaPrincipal['datos'][0]['idTienda']){
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
                                        <td><?php echo $producto['estado']; ?></td>
                                        <td>
                                            <?php 
                                             if($ClasePermisos->getAccion("regularizar")==1){
                                            ?>
                                            <button class="btn btn-sm boton-regularizar" data-idarticulo="<?php echo $producto['idArticulo']; ?>">regularizar</button>
                                            <?php 
                                        }
                                            ?>
                                            </td>
                                            <?php 
                                             if(isset($tiendaWeb)){
                                            ?>
                                        <td>
                                        <?php
                                        if(isset($refTiendas)){
                                            foreach ($refTiendas as $ref){
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
		include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
		?>
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
    background: url('imagenes/loading.gif') 50% 50% no-repeat rgb(249,249,249);
    opacity: .8;
    display:none;
}
</style>
    </body>
</html>
