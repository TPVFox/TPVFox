
         <?php
        include_once './../../../inicial.php';
        include_once $URLCom.'/modulos/mod_proveedor/funciones.php';
        include_once $URLCom.'/controllers/Controladores.php';
        include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
        include_once $URLCom.'/modulos/mod_proveedor/clases/ClaseProveedor.php';
        include_once $URLCom.'/modulos/mod_familia/clases/ClaseFamilias.php';
        $CTArticulos = new ClaseProductos($BDTpv);
        $CProveedor= new ClaseProveedor();
        $CFamilia = new ClaseFamilias(); 
        $Controler = new ControladorComun; 
        $style='';
        $familiasProductos = array(); // Familias que hay en los productos de ese proveedor
        if(isset($_GET['campoorden'])){
            $campoOrden=$_GET['campoorden'];
        } else {
            $campoOrden = 'articulo_name';
        }

        if(isset($_GET['sentidoorden'])){
            $sentidoOrden=$_GET['sentidoorden'];
        } else {
            $sentidoOrden = 'ASC';
        }

        if(isset($_GET['id'])){
            $id=$_GET['id'];
            $datosProveedor=$CProveedor->getProveedor($id);
            if(isset($datosProveedor['error'])){
                $errores[1]=array ( 'tipo'=>'DANGER!',
                                 'dato' => $datosProveedor['consulta'],
                                 'class'=>'alert alert-danger',
                                 'mensaje' => 'Error sql'
                                 );
            
            }else{
                $titulo='Listado de Productos como principal Proveedor:';
            }
        }else{
            $errores[1]=array ( 'tipo'=>'DANGER!',
                                 'dato' => '',
                                 'class'=>'alert alert-danger',
                                 'mensaje' => 'Error no se ha enviado el id del proveedor'
                                 );
        }
        $ProductosPrincipales = $CTArticulos->GetProductosProveedor($id, $campoOrden, $sentidoOrden);
        if ($ProductosPrincipales['NItems']>0){
            $estados = [];
            $productos = [];
            $indice_familias = [];
            foreach ($ProductosPrincipales['Items'] as $key => $item){
                // Obtenemos datos producto, para añadir nombre Codbarras.
                $productos[$key] =$CTArticulos->GetProducto($item['idArticulo']);  
                //Costes proveedor.
                foreach ($productos[$key]['proveedores_costes'] as $pc){
                    if ($pc['idProveedor'] == $id){
                        $productos[$key]['costeProveedor'] = $pc['coste'];
                        $productos[$key]['Ref_proveedor'] = $pc['crefProveedor'];
                        $productos[$key]['fecha_actualizacion_proveedor']=$pc['fechaActualizacion'];
                    } 
                }
                // Eliminamos atributo array
                unset($productos[$key]['proveedores_costes']);
                $estado = $productos[$key]['estado'];
                // Contamos articulos que tienes el mismo estado.
                if (isset($estados[$estado])){
                    // Si existe , se suma.
                    $estados[$estado]++;
                } else {
                    // Añadimos array de estado 
                    $estados[$estado]= 1;
                }
                // Un producto puede estar en varias familias, por lo que 
                // La añadimos al array familiasProducto
                foreach($productos[$key]['familias'] as $familia){
                    // Comprobamos que no existe en familiasProductos creadas.
                    $i =$familia['idFamilia'];
                    if (in_array($i, $familiasProductos) == false){
                        $ascendentes =$CFamilia->buscarAscendientes($i);
                        $familiasProductos[$i]= array( 'idFamilia'=> $i,
                                                     'familiaNombre'=>$familia['familiaNombre'],
                                                     'nivel'=> count($ascendentes),
                                                     'ascendentes'=>$ascendentes
                                                    );
                    }
                }              
            }
        }
        $i=0;
        $html_estados = [];
        foreach ($estados as $estado=>$cantidad){
            $i++;
            // Recuerda que la estado en texto pueden ser varias palabras, por eso ponemos index
            $html = '<div class="checkbox">
                        <label title="'.$estado.'">
                        <input type="checkbox" value="1" id="Check'.$i
                        .'" onchange="filtroEstado(this,'.$i.')"checked>'
                        .$estado.'<span class="badge">'.$cantidad.'</span>
                    </label>
                    </div>';
            $html_estados[] =$html;
        }
        // Ahora tenemos buscar cada unn los ascendentes de cada familiaProducto si existe
        // Si no existe tenemos crearlo y buscar el nombre, y añadir nivel de los cremoas.
        // Tambien añado idFamilia a array familiasPorNiveles para poder ordenarlos.
        if ( count($familiasProductos) > 0 ){
            $familiasPorNiveles=[];
            $indice_niveles = [];
            $contador =0;
            foreach ($familiasProductos as $indice => $familia){
                $Nivel = $familia['nivel'];
                // Ahora añadimos indice de Familia al array familiasPorNiveles si no esta añadido
                if (in_array($indice,$indice_niveles) == false){
                    $familiasPorNiveles[$Nivel][] = $indice;
                }
                if ( isset($familia['ascendentes'])){
                    // Ahora recorremos los ascendentes
                    if ( count($familia['ascendentes']) >0){
                        $Nivel = $familia['nivel']- 1; // Restamos uno ya que $key empieza siempre por 0
                        foreach ($familia['ascendentes'] as $key => $id){
                            // Ahora compramos que no existe en familiasProductos
                            if ( $id >0){
                                if (!isset($familiasProductos[$id])){
                                    // No existe, entonces lo añadimos a familiaProductos
                                    $nivel_ascendente = $Nivel-$key;
                                    $nombre_familia = $CFamilia->buscarPorId($id);
                                    // Añadimos el ascendiente al array con sus descendientes.
                                    $familiasProductos[$id] = array('idFamilia'=> $id,
                                                                'familiaNombre'=>$nombre_familia['datos'][0]['familiaNombre'],
                                                                'nivel' => $Nivel-$key,
                                                                );
                                    // Necesito para luego ordenar, los ascendentes y un array por niveles y los indice del array FamliasProductos.
                                    $ascendentes_ascendente =$CFamilia->buscarAscendientes($id);
                                    $familiasProductos[$id]['ascendentes'] = $ascendentes_ascendente;
                                    $familiasPorNiveles[$nivel_ascendente][] = $id; // Guardamos indice del array, ya es mas facil obtener
                                }
                            }
                        }
                    }
                }
                $indice_niveles = array_values($familiasPorNiveles);
            }
            // Ahora para obtenemos un array de familiasProductos por nivel, genero indice_columna nivel.
            foreach ($familiasProductos as $familia){
                $descendientes = $CFamilia->descendientes($familia['idFamilia']);
                $descendientes_existen = array();
                if (isset ($descendientes['datos']) && count($descendientes['datos'])>0){
                    foreach ($descendientes['datos'] as $descendiente){
                       $id =$descendiente['idFamilia'];
                        if (isset($familiasProductos[$id])){
                            $descendientes_existen[] = $descendiente['idFamilia'];
                        }
                    }
                }
                $id = $familia['idFamilia'];
                $familiasProductos[$id]['descendientes_existen']=$descendientes_existen;
                
            }
            // Identificamos cual es nivel mas bajo
            $niveles = array_keys($familiasPorNiveles);
            // Ordenamos de menor a mayor
            sort($niveles);
            // Mostamos hmtl_familias
            $html_familias = '<div class="checkbox">';
            foreach ($niveles as $n){
                $html_familias .= '<div class="Nivel'.$n.'"><h5>Nivel '.$n.'</h5>';
                foreach ($familiasPorNiveles[$n] as $id){
                    $familia = $familiasProductos[$id];
                    $n_p = $n-1;
                    $padre = $familiasProductos[$id]['ascendentes'][$n_p];
                    $html_familias .= '
                    <label title="'.$familia['familiaNombre'].'">
                    <input type="checkbox" class="Padre_'.$padre.'" value="1" id="Familia_'.$familia['idFamilia']
                    .'" onchange="filtroFamilias(this,'.$familia['idFamilia'].' , '.$padre.')'
                    .'" checked="">'.$familia['familiaNombre'].' </label>';     
                }
                $html_familias .= '----------------------------------------'.'<br/>';
                $html_familias .= '</div>';
            }
            $html_familias .= '</div>';
            // dump($familiasPorNiveles);
            //dump($familiasProductos[3]);
        }
        // Obtenemos plugin virtuemart.
        if ($CTArticulos->SetPlugin('ClaseVirtuemart') !== false){
            $ObjVirtuemart = $CTArticulos->SetPlugin('ClaseVirtuemart');
            $script_ObjVirtuemart = $ObjVirtuemart->htmlJava();
            $tiendaWeb=$ObjVirtuemart->getTiendaWeb();
        }
        ?>

<!DOCTYPE html>
<html>
    <head>
    <?php
        include_once $URLCom.'/head.php';
    ?>
    <script src="<?php echo $HostNombre; ?>/lib/js/tpvfoxSinExport.js"></script>   
    <script src="<?php echo $HostNombre; ?>/modulos/mod_proveedor/funciones.js"></script>

    </head>
    <body>
    <?php
        include_once $URLCom.'/modulos/mod_menu/menu.php';
        if (isset($errores)){
            foreach($errores as $error){
                echo '<div class="'.$error['class'].'">'
                . '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br>Sentencia: '.$error['dato']
                . '</div>';
            }
        }
        // Generamos Script con array de los productos de esta pagina para poder ejecutar ajax
        // para comprobar el estado en la web.
            if ($CTArticulos->SetPlugin('ClaseVirtuemart') !== false){
                if( isset($tiendaWeb['idTienda'])){
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
        <div class="container">
            <div class="col-md-12 text-center" >
                    <h2 class="text-center"> <?php echo $titulo;?></h2>
            </div>
            <div class="col-md-12" >
                <div class="col-md-3">
                <?php 
                        echo $Controler->getHtmlLinkVolver().'<br/>';
                        echo '<div>';
                        echo '<h4>Datos del proveedor</h4>';
                        echo '<strong>Nombre Comercial:</strong>'.$datosProveedor['datos'][0]['nombrecomercial'].'<br/>';
                        echo '<strong>Razon Social:</strong>'.$datosProveedor['datos'][0]['razonsocial'].'<br/>';
                        echo '<strong>email:</strong>'.$datosProveedor['datos'][0]['email'].'<br/>';
                        echo '<strong>Telefono:</strong>'.$datosProveedor['datos'][0]['telefono'].'<br/>';
                        echo '<strong>fax:</strong>'.$datosProveedor['datos'][0]['fax'].'<br/>';
                        echo '<strong>movil:</strong>'.$datosProveedor['datos'][0]['movil'].'<br/>';
                        echo '</div>';
                        echo '<div>';
                        echo '<h4>Otros Datos</h4>';
                        echo '<strong>Productos:</strong>'.count($productos);
                        echo '<br/><br/><a onclick="imprimirSeleccion('.$id.')">Imprimir selección</a>';
                        echo '</div>';
                        echo '<div><h4>Filtrar por estado:</h4>';
                        foreach ($html_estados as $estado){
                            echo $estado;
                        }
                        echo '<h4>Filtrar por Familias:</h4>';
                        echo $html_familias;
                        echo '</div>';
                    ?>
                </div>
                <div class="col-md-9">  
                    <table class="table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id= "chekArticuloAll" name="chekArticuloAll" value="0" onchange="SeleccionarTodos()"></th>
                                <th>ID</th>
                                <th></th>
                                <?php $icon = obtenerIconoOrden($campoOrden,$sentidoOrden,'articulo_name');?>
                                <th class="ordenar" data-campo="articulo_name">Nombre Producto
                                    <?php echo $icon;?>
                                </th>
                                <th>Ultimo</th>
                                <?php $icon = obtenerIconoOrden($campoOrden,$sentidoOrden,'crefProveedor');?>
                                <th class="ordenar" data-campo="crefProveedor">Ref_Proveedor
                                    <?php echo $icon;?>
                                </th>
                                <th>Coste Prov</th>
                                <?php $icon = obtenerIconoOrden($campoOrden,$sentidoOrden,'fechaActualizacion');?>
                                <th class="ordenar" data-campo="fechaActualizacion">Fecha_Actualiza
                                    <?php echo $icon;?>
                                </th>
                                <th>Precio</th>
                                <th>Código de Barras</th>
                                <th>Stock</th>
                                <?php $icon = obtenerIconoOrden($campoOrden,$sentidoOrden,'articulo_name');?>
                                <th class="ordenar" data-campo="a.estado">Estado
                                    <?php echo $icon;?>
                                </th>
                                <th></th>
                            </tr>
                        </thead>
                            <tbody>

                    <?php
                    
                        foreach ($productos as $producto){
                        $link_producto = '<a class="glyphicon glyphicon-eye-open" target="_blank" href="./../../mod_producto/producto.php?id='.$producto['idArticulo'].'"></a>';
                        $link_mayor = '<a class="glyphicon glyphicon-list" target="_blank" href="./../../mod_producto/DetalleMayor.php?idArticulo='
                                .$producto['idArticulo'].'"></a>';
                        
                        if(number_format($producto['stocks']['stockOn'],0)< $producto['stocks']['stockMin']){
                                $lineaRoja='danger';
                            }else{
                                $lineaRoja='';
                            }
                        $htmlFamilia = "";
                        $claseFamilias = "";
                        foreach($producto['familias'] as $familia){
                            $claseFamilias .=' Familia_'.$familia['idFamilia'];
                            $htmlFamilia  .='<span class="label label-info">'.$familia['familiaNombre'].'</span>';
                        }       
                        echo
                            '<tr class="'.$lineaRoja.' Row'.$claseFamilias.'">
                                <td><input type="checkbox" class="chekArticulo" name="chekArticulo" value="'.$producto['idArticulo'].'">
                                <td>'.$producto['idArticulo'].'</td>
                                <td>'.$link_producto.'</td>
                                <td>'.$producto['articulo_name'].'<br>'.$htmlFamilia.'</td>
                                <td>'.number_format($producto['ultimoCoste'],2).'</td>
                                <td>'.$producto['Ref_proveedor'].'</td>
                                <td>'.number_format($producto['costeProveedor'],2).'</td>
                                <td>'.$producto['fecha_actualizacion_proveedor'].'</td>
                                <td>'.number_format($producto['pvpCiva'],2).'€</td>
                                <td>';
                                foreach ($producto['codBarras'] as $codBarras){
                                    echo $codBarras.'   ';
                                    
                                }
                                echo '</td>
                                <td>';
                                
                                if ($producto['tipo'] == 'peso'){
                                        echo '<p>'.number_format($producto['stocks']['stockOn'],3).'</p>';
                                } else {
                                         echo '<p>'.number_format($producto['stocks']['stockOn'],0).'</p>';
                                }
                                echo '</td>
                                <td>'.$producto['estado'].'</td>
                                <td>'.$link_mayor.'<td>'
                                .'<td id="idProducto_estadoWeb_'.$producto['idArticulo'].'" class="icono_web despublicado">';

                                            if($CTArticulos->GetReferenciasTiendas()){
                                                foreach ($CTArticulos->GetReferenciasTiendas() as $ref){
                                                    if($ref['idVirtuemart']>0){
                                                        $ObjVirtuemart = $CTArticulos->SetPlugin('ClaseVirtuemart');     
                                                        $link=  $ObjVirtuemart->ruta_producto.$ref['idVirtuemart'];
                                                        echo '  <a target="_blank" class="glyphicon glyphicon-globe" href="'.$link.'"></a>';
                                                    }
                                                }
                                            } 
                                     
                                echo'</td>
                            </tr>';
                        }
                    ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <script>
        catchEvents();
    </script>
    </body>
</html>
