        <?php
        // Procesos que hago.
        // -Obtengo Familia y sus datos
        // -Obtengo todas las Familias:
        //     - Todas las familias de tpv
        //     - Todas las familias de web con su relacion en tpv 
        //     - Creo array de las familias web que no tiene relacion
        //      
        
        include_once './../../inicial.php';
        include_once $URLCom . '/modulos/mod_familia/funciones.php';
        include_once $URLCom . '/modulos/mod_familia/clases/ClaseFamilias.php';
        $erroresWeb = array();
        $titulo = 'Familias:';
        $id = 0;
        $idTiendaWeb = 0;
        $idsFamiliasWeb = 0;
        $htmlProductos= '';
        $htmlFamiliasWeb='';
        $bottonSubirHijos = ' ';    //  Boton de subir hijos si hay web
        $nombre_familia_web = '';
        $Cfamilias = new ClaseFamilias();
        $padres = $Cfamilias->todoslosPadres('familiaNombre', TRUE);
        // Permisos Web para poder cambiarlos segun advertencias.
        $permisos = array ( 'VerWeb' => $ClasePermisos->getAccion('VerFamiliaWeb'),
                            'SubirHijasWeb' => $ClasePermisos->getAccion('SubirFamiliasHijasWeb'),
                            'MostrarSinRelacion' =>$ClasePermisos->getAccion('MostrarFamiliasWebSinRelacionar')
                        );
        // Obtenemos los datos del id, si es 0, quiere decir que es nuevo.
        if (isset($_GET['id'])) {
            // Modificar Ficha 
            $id = $_GET['id']; // Obtenemos id para modificar.
        } 
        if ($id != 0) {
            $titulo .= "Modificar";
            $familia = $Cfamilias->datosFamilia($id);//$Cfamilias->leer($id);
            // Obtenemos los padres pero sin los hijos de la familia actual.
            $padres = $Cfamilias->familiasSinDescendientes($id, TRUE);
            $htmlProductos = htmlTablaFamiliaProductos($id);
        } else {
            // Quiere decir que no hay id, por lo que es nuevo          
            $titulo .= "Crear";
            $familia = array (  'familiaNombre' => '',
                                'productos'     => 0,
                                'familiaPadre'  => 0,
                                'beneficiomedio'=> 25.00 ,
                                'mostrar_tpv'   => 0
                                );
            // El beneficio podría ser un parametro de configuracion para este modulo.
            $permisos['VerWeb'] = 0;// Para evitar error que no permito continuar obteniendo datos web y verlos.

        }
        // Montamos el combo de familias de tpvfox
        $combopadres = $Cfamilias->htmlComboFamilias($padres['datos'], $familia['familiaPadre']);
        $idsFamilias = array_column($padres['datos'],'idFamilia'); // Array con ids familias tpv sin descendientes  de la familia actual, en una columna

       
        // Cargamos la clase virtuemart plugin y :
        //      -Montamos html con relacion
        //      -Montamos advertencias web (erroresweb)
        //         -- Que tenga una sola relacion con una tienda ( danger)
        //         -- Que familias hay en la web que no tenga relacion con tpv (warning)
        
        
        if ($permisos['VerWeb'] == 1 && $id >0) {
            $ObjVirtuemart = $Cfamilias->SetPlugin('ClaseVirtuemartFamilia');
            if(isset($ObjVirtuemart->TiendaWeb) && count($ObjVirtuemart->TiendaWeb)>0){
                // Obtenemos los datos de la tienda Web del plugin
                $tiendaWeb=$ObjVirtuemart->getTiendaWeb();
                $idTiendaWeb=$tiendaWeb['idTienda'];
                // Obtenemos todas las web
                $todasFamiliasWeb = array(); // Por defecto es un array vacio...
                $t = $ObjVirtuemart->todasFamilias();
                if (isset($t['error'])){
                        // Si conecto,pero no obtuvo items, por lo que debesmos informar que no se obtuvo ninguna familia
                        $erroresWeb[] = $Cfamilias->montarAdvertencia('danger',
                                            'Error de conexion con el siguiente error:<br/>'.json_encode($t['error'])
                                            );
                        $permisos['VerWeb'] = 0;// Error que no permito continuar obteniendo datos web y verlos.

                } else {
                    if (isset($t['Datos']['item'])){
                        // El array de todas las familias webs ($t['Datos']['item']) con las siguientes columnas, ejemplo:
                        //           [virtuemart_category_id] => 463
                        //           [nombre] => Fresco Veggie
                        //           [padre] => 462
                        $todasFamiliasWeb = $t['Datos']['item'];
                        $idsFamiliasWeb = array_column($todasFamiliasWeb,'virtuemart_category_id'); // Array todas familias web en una columna
                        // Ahora buscamos la relacion con las familias web y tienda
                        $r = $Cfamilias->anhadirRelacionArrayTiendaFamilia($todasFamiliasWeb,$idTiendaWeb);
                        if ( isset($r['familiasWebSinRelacion'])){
                            $familiasWebSinRelacion = $r['familiasWebSinRelacion'];
                        }
                        $todasFamiliasWeb = $r['todasFamiliasWeb'];
                        if (isset($r['error'])){
                            $erroresWeb[]= $r['error'];
                            $permisos['VerWeb'] = 0;// Error que no permito continuar obteniendo datos web y verlos.
                        }
                    } else {
                     // Si conecto,pero no obtuvo items, por lo que debesmos informar que no se obtuvo ninguna familia
                        $erroresWeb[] = $Cfamilias->montarAdvertencia('danger',
                                            'Hubo conexion pero No se obtuvo ninguna familia web.'
                                            );    
                        $permisos['VerWeb'] = 0;// Error que no permito continuar obteniendo datos web y verlos.
                    }
                }
            } else {
                $permisos['VerWeb'] = 0;// NO es un error , simplemen no entro obtuvo tiendaWeb.
            }
        }
       if ($permisos['VerWeb'] == 1) {
           if ( count($familia['familiaTienda']) == 0 ){
                // No esta relaciona con ninguna familia web, damos opcion de crear familia web
                if ($id > 0 && $idTiendaWeb >0) {
                    // No es nuevo, pero no tiene relacion en la con la web.
                    $erroresWeb[] = $Cfamilias->montarAdvertencia('warning'
                                            ,'No esta creada esta familia en la web o no existe registro en tabla familiasTienda.'
                                            );
                    // Montamos array $datos_familia_web ya que no esta montado:
                    
                    $datos_familia_web = array ('idFamilia' => $id,
                                                'idTienda' => $idTiendaWeb,
                                                'idFamilia_tienda' => 0,
                                                'id_padre' => $familia['familiaPadre'],
                                                'nombre' => $familia['familiaNombre'],
                                                'accion' => 'add',
                                                );
                    // Como existe, pues ponemos como id_padre_web la relacion del padre de tpv
                    
                   
                }
            } else {
                //~ // Hay relacion con una o mas tiendas y no hubo erroresWeb
                // por eso hacemos foreach, ya que puede haber mas de una.
                foreach ($familia['familiaTienda'] as $f){
                    if ($f['idTienda']==$idTiendaWeb){
                        $datos_familia_web = array ('idFamilia' => $f['idFamilia'],
                                                    'idTienda' => $idTiendaWeb,
                                                    'idFamilia_tienda' =>$f['idFamilia_tienda'] ,
                                                    'id_padre' => $familia['familiaPadre'],
                                                    'nombre' => $familia['familiaNombre'],
                                                    'accion' => 'edit'
                                                    );
                        // Ahora comprobamos si realmente existe esa relacion en la web
                        $Num_indice = array_search( $f['idFamilia_tienda'], $idsFamiliasWeb);
                        if ( gettype($Num_indice) == 'boolean' && count($familia['familiaTienda'])>0){
                              $erroresWeb[] = $Cfamilias->montarAdvertencia('danger'
                                                ,'La familia actual tienes relacion pero no existe en la web, realmente.'
                                                );
                              $datos_familia_web['nombre_familia_tienda']='No existe';
                        } else {
                            if (count($familia['familiaTienda'])>0){
                                // Guardamos en datos_familia_web el nombre utiliza esa familia
                                $datos_familia_web['nombre_familia_tienda'] = $todasFamiliasWeb[$Num_indice]['nombre'];
                            } else {
                                // Como no tiene relacion creada, ponemos el nombre tienda el mismo
                                $datos_familia_web['nombre_familia_tienda'] = $datos_familia_web['nombre'];
                            }
                        }
                    }
                }
            }   
        }
        // Comprobamos si realmente existe en la web los ids relacionados de la familia actual y la familia padre.
        if ($permisos['VerWeb'] == 1) {
            // Ahora comprobamos  el padre en en tpv ( tienda principal ) y en la tienda web.
            // comprobamos que sea el mismo y que exista en la web.
            // Si es 0 el padre es la raiz , por lo que no comprobamos padre.
            if ( $familia['familiaPadre']> 0){
                $p = $Cfamilias->obtenerRelacionFamilia ($idTiendaWeb, $familia['familiaPadre']);
                if (isset($p['datos'][0])){
                    // Si existe relacion comprobamos si en web realmente
                    $Num_indice = array_search( $p['datos'][0]['idFamilia_tienda'], $idsFamiliasWeb);
                    // Comprobamos que sea el mismo padre tpv y con el web, que esten realizacionados
                    if ( gettype($Num_indice) == 'boolean'){
                        $erroresWeb[] = $Cfamilias->montarAdvertencia('danger'
                                        ,'El padre de esta familia, tiene con relación con la Web , pero NO EXISTE ESA RELACIÓN EN LA WEB. <br/>No permito crear o modificar datos en la web.'
                                        );
                        $permisos['VerWeb'] = 0;// Error que no permito continuar obteniendo datos web y verlos.

                    } else {
                        $datos_familia_web['id_padre_web'] = $p['datos'][0]['idFamilia_tienda'];
                        $datos_familia_web['nombre_padre_web'] = $todasFamiliasWeb[$Num_indice]['nombre'];
                    }
                // Devuelve el numero indice del array  $todasFamiliasWeb, recuerda que empieza por 0, si no encuentra devuePonemoslve boolean
                
                } else {
                    // Si tiene familia Padre, pero no tiene relacion con la web es un error
                    // Buscamos el nombre del padre para mostrarlo.
                    $Num_indice = array_search($familia['familiaPadre'],$idsFamilias);
                    $nombre_padre = $padres['datos'][$Num_indice]['familiaNombre'];
                    
                       $erroresWeb[] = $Cfamilias->montarAdvertencia('danger'
                                        ,'No muestro campos web, ya no puede editar o añadir esta familia en la web, ya que el padre ('.$nombre_padre.') no existe en la web.'
                                        );
                        $permisos['VerWeb'] = 0;// Error que no permito continuar obteniendo datos web y verlos.

        
                }
            } else {
                // Cuando el padre de la familia es 0, pues la web tb es 0
                 $datos_familia_web['id_padre_web'] = 0;
                 $datos_familia_web['nombre_padre_web'] = 'Raiz';
            }
        }
        
        $htmlFamiliasHijas = '';
        // Este array lo necesito para controlar los hijos y saber si mostramos.
            $control_hijos = array ('NumeroHijos'=>0,
                        'conWeb' => 0,
                        'sinWeb' => 0,
                        'errorWeb' => 0
                        );  
        if (isset ($familia['hijos']) && count($familia['hijos'])>0){  
            // Este array lo necesito para controlar los hijos y saber si mostramos.
            $control_hijos ['NumeroHijos'] = count($familia['hijos']);
            // Ahora comprobamos si existe en la web las familias hijas, solo las que tiene relacion.
            foreach ($familia['hijos'] as $key=>$hijo){
                if (count($hijo['familiaTienda']) == 1){
                    // Si hay 1 relación continuamos, compramos que exita en listado ids de familias de la Web (idsFamiliasWeb)
                    $OK = FALSE;
                    if ($idsFamiliasWeb != 0){
                        // Compruebo si existe idsFamiliasweb, ya que si vamos comprobar que existe relacion de hijos, lo necesitamos.
                        $OK = in_array($hijo['familiaTienda'][0]['idFamilia_tienda'], $idsFamiliasWeb);
                    }
                    if ($OK === FALSE){    
                        $familia['hijos'][$key]['familiaTienda'][0]['existes_web']='KO';
                        // Boqueamos botton de subir hijos, ya que existe una relación y no existe en la web, hay que eliminarla 
                        // primero antes de subir mas hijos.
                        $control_hijos['errorWeb'] += 1; // Suma uno

                        // Creamos advertencia:
                        $erroresWeb[] =  $Cfamilias->montarAdvertencia('warning',
                                            'En  el hijo:<b>'.$hijo['familiaNombre'].'</b><br/>'
                                            .'Tiene una relación que no existe en la web o no se pudo comprobar.'
                                            );
                    } else {
                        // Si existe en los ids entonces creamos link_front_end_categoria
                        $control_hijos['conWeb'] += 1; // Suma uno
                        $familia['hijos'][$key]['familiaTienda'][0]['existes_web']='OK';
                        $familia['hijos'][$key]['familiaTienda'][0]['link_front_end_categoria']=$ObjVirtuemart->ruta_categoria.$hijo['familiaTienda'][0]['idFamilia_tienda'];
                    }

                } else {
                    // O no existe realizacion o existe mas de una relación.
                    // Si existe mas de una relacion añadimos advertencia.
                    if (count($hijo['familiaTienda']) > 1){
                        $control_hijos['errorWeb'] += 1; // Suma uno
                        $erroresWeb[] =  $Cfamilias->montarAdvertencia('warning',
                                            'En  el hijo:<b>'.$hijo['familiaNombre'].'</b><br/>'
                                            .'Tiene mas de una relacion con la web'
                                            );
                    } else {
                        $control_hijos['sinWeb'] += 1; // Suma uno
                        // El hijo en cuestion no tiene relación con la web.
                        // Por lo que creamos una advertencia para mostrar con el bottomSubirHijos
                    }
                }
            }
        }
        // Ahora montamos el html del desplegable de familias hijos.
        // Si tiene permisos de SubirHijasWeb  y tiene permisos ver montamos boton Subir hijos juntos.
        if ($permisos['SubirHijasWeb'] == 1 && $permisos['VerWeb'] == 1 ){
            if ($control_hijos['sinWeb'] > 0 && $control_hijos['errorWeb'] == 0 ){
                // Si familia no está creada en la web no permitimos subir hijos
                if ($datos_familia_web['accion'] == 'edit'){
                    $bottonSubirHijos='<a class="btn btn-info" onclick="subirHijosWeb('.$id.', '.$idTiendaWeb.')">Subir Hijos Web</a>';
                } else {
                    $bottonSubirHijos= 'No permite subir sino existe en web la familia actual';
                }
            }
        }
        if ($control_hijos['NumeroHijos']>0){
            $htmlFamiliasHijas = htmlTablaFamiliasHijas($familia['hijos'], $idTiendaWeb,$bottonSubirHijos);
        } 
        // La variable idTiendaWeb indica la tienda web, es la que enviamos a htmlFamiliasHijas
        // Si no hubiera obtenido idTiendaWeb entonces el valor 0.
        if ($permisos['VerWeb'] == 1){
            // Si tenemos permiso Ver Web y ademas no hubo errores grabes
            $htmlFamiliasWeb = $ObjVirtuemart->htmlDatosFamiliaWeb($datos_familia_web);
            // Si tenemos familias en tienda web sin relacionar queremos mostrarlas
            if (isset($familiasWebSinRelacion)){
                // Solo tiene sentido mostrar en la vista familia cuando la familia no tiene relacion, es decir cuando accion de datos_familia_web es add
                if ($permisos['MostrarSinRelacion'] == 1 ){
                    if( $datos_familia_web['accion']=='add' || ($control_hijos['sinWeb'] >0 && $control_hijos['errorWeb'] == 0)){
                        // Obtenemos columna de nombre.
                        $nombresFamiliasWebSinRelacion = array_column($familiasWebSinRelacion,'nombre');
                        $html_sin_relacion ='<ul>';
                        foreach ($familiasWebSinRelacion as $SinRelacion){
                            $html_sin_relacion .= '<li>Id_web:'.$SinRelacion['virtuemart_category_id'].' '.$SinRelacion['nombre'].'</li>';
                        }
                        $html_sin_relacion.='</ul>';

                        $erroresWeb[] =  $Cfamilias->montarAdvertencia('warning',
                                    'En la web existe <b>'.count($nombresFamiliasWebSinRelacion).'</b> familia(s) sin relacion con tpv:<br/>'
                                    .$html_sin_relacion
                                    );
                    }
                } 
            }
        }
        // Ahora montamos el htmlRelacionesTienda, para poder ver estado y poder eliminarla si fuera necesario.
        $htmlRelacionFamilia ='';
        if ( isset($familia['familiaTienda']) ){
            $htmlRelacionFamilia = htmlTablaRefTiendas($familia['familiaTienda'],$ObjVirtuemart->ruta_categoria,$permiso_borrar=0);  

        }
        // Si la familia tiene mostrar_tpv en 1 , lo ponemos en la variables $valor_check para mostrarlo
        $valor_check = 'value="0"';
        if ($familia['mostrar_tpv'] === '1'){
            $valor_check = 'value="1" checked';
        }
        //~ // Crear relacion famiias padre 
        //~ echo '<pre>';
        //~ print_r($familiasWebSinRelacion);
        //~ echo '</pre>';
        ?>
<!DOCTYPE html>
<html>
    <head>
         
        <?php
        include_once $URLCom.'/head.php';
        ?>
        <script src="<?php echo $HostNombre; ?>/jquery/jquery-ui.min.js"></script>
        <link rel="stylesheet" href="<?php echo $HostNombre; ?>/jquery/jquery-ui.min.css" type="text/css">
        <link rel="stylesheet" href="<?php echo $HostNombre; ?>/modulos/mod_familia/familias.css" type="text/css">
        <script src="<?php echo $HostNombre; ?>/lib/js/autocomplete.js"></script>    
        <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
        <script src="<?php echo $HostNombre; ?>/modulos/mod_familia/funciones.js"></script>        

        <script>
            var familia=<?php echo json_encode($familia);?>;
            var idTiendaWeb = <?php echo $idTiendaWeb;?>;

        
        </script>
    </head>
    <body>
        <?php
        include_once $URLCom . '/modulos/mod_menu/menu.php';
        
        ?>


<div class="container">
    <?php
        
        if (isset($familia['errores'])){
            foreach ($familia['errores'] as $comprobaciones){
                echo $Cfamilias->montarAdvertencia($comprobaciones['tipo'],$comprobaciones['mensaje'].'<br/>'.$comprobaciones['dato'],'OK');
                // No permito continuar, ya que hubo error grabe.
                if ($comprobaciones['tipo']=='danger'){
                    exit;
                }
            }
        }
        if (isset($erroresWeb) && count($erroresWeb)>0){
            foreach ($erroresWeb as $error){
                echo $Cfamilias->montarAdvertencia($error['tipo'],$error['mensaje'],'OK');
                if ($error['tipo'] === 'danger'){
                    // No  muestro formulario de Web, ya que hay una advertencia danger en Web
                    $permisos['VerWeb'] = 0; // Cambio permiso
                }
            }
        }
    ?>

    
    <h2 class="text-center"> <?php echo $titulo; ?></h2>
    <div class="col-md-12">

        <!-- columna formulario -->
        <div class="col-md-7">
            <form action="javascript:guardarClick();" method="post" name="formFamilia" >
                <div class="col-md-12">
                    <div class="btn-toolbar">
                        <a class="btn btn-link"  id="btn-fam-volver"  href="ListaFamilias.php">Volver Atrás</a>
                        <button class="btn btn-primary" type="button" id="btn-fam-grabar" onclick="guardarFamilia()" data-href="./ListaFamilias.php">Guardar</button>
                    </div>
                    <div class=" Datos">
                        <?php // si es nuevo mostramos Nuevo   ?>
                        <div class="col-md-7">
                            <h4>Datos de la familia con ID:<?php echo $id == 0 ? 'nueva' : $id; ?></h4>
                        </div>
                        <input type="hidden" id="idfamilia" name="idfamilia" value="<?php echo $id; ?>" >

                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-label-group">
                            <label for="inputnombre">Nombre: </label>
                            <input type="text" name="nombrefamilia" id="inputnombre"
                                   value="<?php echo $familia['familiaNombre']; ?>"
                                   class="form-control" placeholder="Nombre descriptivo"  autofocus>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="ui-widget" id="inputPadre">
                            <label for="inputpadre">Padre: </label>
                             <select name="padre" class="form-control " id="combopadre">
                            <?php echo $combopadres ?>
                            </select>
                            <input type="hidden" name="idpadre" id="inputidpadre" value="<?php echo $familia['familiaPadre'];?>">                               
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-label-group">
                            <label for="inputbeneficio">Beneficio medio: </label>
                            <input type="text" name="beneficio" id="inputbeneficio" 
                                   value="<?php echo $familia['beneficiomedio']; ?>"
                                   class="form-control" placeholder="Beneficio medio unitario >= 0,01"  autofocus>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-label-group col-md-6">
                            <label for="inputProductos">Productos: </label>
                            <input type="text" name="productos" id="inputProductos" 
                                   value="<?php echo $familia['productos'] ?>"
                                   readonly="readonly"
                                   class="form-control" placeholder="productos con esta familia"  >
                        </div>
                        <div class="form-check col-md-6">
                            <br/>
                            <label>
                            <input id="marcar_tpv" type="checkbox" name="mostrar_tpv" <?php echo $valor_check;?>>
                            <span class="label-text">Mostrar en tpv</span>
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- columna desplegables -->
        <div class="col-md-5 text-center">
            <div class="panel-group">
                <!-- Inicio collapse de CobBarras -->                            
                <?php
                $num = 1; // Numero collapse;
                $titulo = 'Familias Hijas';
                echo htmlPanelDesplegable($num, $titulo, $htmlFamiliasHijas);
                ?>
                <!-- Inicio collapse de Proveedores --> 
                <?php
                $num = 2; // Numero collapse;
                $titulo = 'Productos';
                echo htmlPanelDesplegable($num, $titulo, $htmlProductos);
                ?>

                <!-- Inicio collapse de Referencias Tiendas --> 
                <!-- Inicio collapse de Proveedores --> 
                <?php
                $num = 3; // Numero collapse;
                $titulo = 'Referencias tienda';
                echo htmlPanelDesplegable($num, $titulo, $htmlRelacionFamilia);
                ?>
                <!-- Fin de panel-group -->
            </div> 
            <!-- Fin div col-md-6 -->
            
        </div>
    </div>
        <div class="col-md-12" >
        <?php
            if ($permisos['VerWeb'] == 1) {
                echo $htmlFamiliasWeb;
            }
        ?>
        </div>
        <!--fin de div container-->
        <?php // Incluimos paginas modales
        echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
        include $RutaServidor.'/'.$HostNombre.'/plugins/modal/ventanaModal.php';
        ?>
</div>   
</body>
</html>
