        <?php
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
        // Permisos de Ver web y Subir Hijo a web
        $permisos = array ( 'VerWeb' => $ClasePermisos->getAccion('VerFamiliaWeb'),
                            'SubirHijasWeb' => $ClasePermisos->getAccion('SubirFamiliasHijasWeb')
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
        }
        // Montamos el combo de familias de tpvfox
        $combopadres = $Cfamilias->htmlComboFamilias($padres['datos'], $familia['familiaPadre']);
       
        // Cargamos la clase virtuemart plugin y :
        //      -Montamos html con relacion
        //      -Montamos advertencias web (erroresweb)
        //         -- Que tenga una sola relacion con una tienda ( danger)
        //         -- Que familias hay en la web que no tenga relacion con tpv (warning)
        
        
        if ($permisos['VerWeb'] == 1) {
            $ObjVirtuemart = $Cfamilias->SetPlugin('ClaseVirtuemartFamilia');
            if(isset($ObjVirtuemart->TiendaWeb) && count($ObjVirtuemart->TiendaWeb)>0){
                // Obtenemos los datos de la tienda Web del plugin
                $tiendaWeb=$ObjVirtuemart->getTiendaWeb();
                $idTiendaWeb=$tiendaWeb['idTienda'];
                // Obtenemos el id de la familia en tpv y id familia en la web
                $RelacionIdsFamiliaWeb=$Cfamilias->obtenerRelacionFamilia($idTiendaWeb, $id);
                if (isset($RelacionIdsFamiliaWeb['datos'])){
                    if (count($RelacionIdsFamiliaWeb['datos'])> 1){
                        $erroresWeb[] = $Cfamilias->montarAdvertencia('danger'
                                            ,'Esta familia tienes varias relaciones en tpv, es un error grabe'
                                            );
                    }
                }
            }
            // No hay relacion con familia Web
           if (!isset($RelacionIdsFamiliaWeb['datos']) && count($erroresWeb) == 0){
                // No esta creada familia web
                if ($id > 0 && $idTiendaWeb >0) {
                    // No es nuevo, pero no tiene relacion en la con la web.
                    $erroresWeb[] = $Cfamilias->montarAdvertencia('warning'
                                            ,'No esta creada esta familia en la web o no existe registro en tabla familiasTienda.'
                                            );
                    // Montamos array $datos_familia_web ya que no esta montado:
                    
                    $datos_familia_web = array ('idFamilia' => $id,
                                                'idFamilia_tienda' => 0,
                                                'nombre' => $familia['familiaNombre'],
                                                'accion' => 'add',
                                                'id_padre' => $familia['familiaPadre'],
                                                );
                    // Ahora tengo obtener el id del padre para web $id_padre_web
                    $p = $Cfamilias->obtenerRelacionFamilia ($idTiendaWeb, $familia['familiaPadre']);
                    $datos_familia_web['id_padre_web'] = $p['datos'][0]['idFamilia_tienda'];
                    //NO Creamos formulario de familia web si no existe.
                    $htmlFamiliasWeb = $ObjVirtuemart->htmlDatosFamiliaWeb($datos_familia_web, $combopadres);
                }

            }
            // Si hubo relacion con Familia WEB
            if (isset($RelacionIdsFamiliaWeb['datos']) && count($erroresWeb) == 0 ){
                // Solo ejecutamos si existe familia relacionada en tpv con la web
                // No hubo error y obtuvo id de tienda we, continuamos obteniendo todas las familias que existen en la web
                $todasFamiliasWeb = array(); // Por defecto es un array vacio...
                $t = $ObjVirtuemart->todasFamilias();
                // El array de todas las familias webs ($t['Datos']['item']) con las siguientes columnas, ejemplo:
                //           [virtuemart_category_id] => 463
                //           [nombre] => Fresco Veggie
                //           [padre] => 462
                if (!isset ($t['Datos']['item'])){
                        // Si conecto,pero no obtuvo items, por lo que debesmos informar que no se obtuvo ninguna familia
                        $erroresWeb[] = $Cfamilias->montarAdvertencia('warning',
                                            'Hubo conexion pero No se obtuvo ninguna familia web.'
                                            );
                }
                if ( isset ($t['Datos']['item'])){
                    $idsFamilias = array_column($padres['datos'],'idFamilia'); // Array con ids familias tpv sin descendientes  de la familia actual, en una columna
                    $todasFamiliasWeb = $t['Datos']['item'];
                    $idsFamiliasWeb = array_column($todasFamiliasWeb,'virtuemart_category_id');
                    foreach ($todasFamiliasWeb as $key => $familiaWeb){
                        // Comprobamos si existe relacion de todas las familias Web con tpv
                        $existe = $Cfamilias->obtenerRelacionFamilia_tienda($idTiendaWeb,$familiaWeb['virtuemart_category_id']);
                        if (!isset($existe['datos'])){
                            // Registramos las familias creadas en la web que no tenga relación
                            $familiasWebSinRelacion[] = $familiaWeb;
                            // Si no existe relacion de una familia web de todas, la eliminamos del array y mostramos una advertencia.
                            unset($todasFamiliasWeb[$key]);
                        } else {
                            // Existe relacion familia web con tpv.
                            // Añadimos a todasFamiliasWeb el idFamilia de tpv
                            $todasFamiliasWeb[$key]['idFamilia'] = $existe['datos'][0]['idFamilia'];
                            if ($RelacionIdsFamiliaWeb['datos']['0']['idFamilia'] == $existe['datos'][0]['idFamilia']){
                                // Obtenemos el nombre en la web de la familia relacionada con tpv, es necesario para cubrir en el formulario, ya que en las siguiente lineas, se elimina está 
                                // familia en todasFamiliasWeb ya qye no podemos ponerala como padre.
                                $nombre_familia_web =  $todasFamiliasWeb[$key]['nombre'];
                                $id_padre_web = $familiaWeb['padre'];
                            }
                            // El nombre familia puede ser distinto, por lo tomamos el nombre familia de la web
                            // la referencia en el array "familiaNombre" hace falta para el combo.
                            $todasFamiliasWeb[$key]['familiaNombre'] = $todasFamiliasWeb[$key]['nombre']; 
                            
                            // Eliminamos si es un descendiente de la familia actual , ya que tenemos que no podemos montar combo con descendente.
                            $OK = in_array($todasFamiliasWeb[$key]['idFamilia'], $idsFamilias);
                            if ($OK === FALSE){
                                // Eliminamos ya que puede ser descendente o no existe en tpv
                                 unset($todasFamiliasWeb[$key]);
                            } 
                        }
                    }
                    // Ahora comprobamos si tenemos familias en la web sin relacion con tpv y generamos advertencia.
                    if (isset($familiasWebSinRelacion)){
                         // Obtenemos columna de nombre.
                        $nombresFamiliasWebSinRelacion = array_column($familiasWebSinRelacion,'nombre');
                        $erroresWeb[] =  $Cfamilias->montarAdvertencia('warning',
                                        'En la web existe <b>'.count($nombresFamiliasWebSinRelacion).'</b> familia(s):<b>'.implode(',',$nombresFamiliasWebSinRelacion).'</b><br/>'
                                        .'No tiene relacion con tpv, por este motivo no está en el select de la web'
                                        );
                                                
                    }
                    // Array con ids familias tpv sin descendientes en una columna
                    // ¿ Montamos combo igualmente hubiera errores danger ? 
                    array_unshift($todasFamiliasWeb, ['virtuemart_category_id' => 0, 'nombre' => 'Raíz: la madre de todas las familias', 'familiaNombre'=>'Raíz: el padre de las familias','idFamilia'=>0,'padre'=>0]);
                    $combopadresWeb = $Cfamilias->htmlComboFamilias($todasFamiliasWeb, $id_padre_web,'virtuemart_category_id');

                } 
                $OK = 'OK';
                if (isset($t['htmlAlerta'])){
                    // Si hubo un error en conexion lo mostramos y no mostramos el formulario.
                    $htmlFamiliasWeb = $t['htmlAlerta'];
                } else {
                    // Controlamos que obtuvimos el id_padre_web.
                   
                    if (!isset($id_padre_web)){
                        $erroresWeb[] =  $Cfamilias->montarAdvertencia('danger',
                                                'No podemos obtener el id Padre de Web del virtuemart_category_id:'
                                                .$RelacionIdsFamiliaWeb['datos']['0']['idFamilia'] .' o esta el titulo en blanco en la web.'
                                                );
                        $OK = 'KO';
                    } 
                    if ($nombre_familia_web == ''){
                        $erroresWeb[] =  $Cfamilias->montarAdvertencia('danger',
                                                'El nombre se obtuvo mal o esta vacio'
                                                .$RelacionIdsFamiliaWeb['datos']['0']['idFamilia'] 
                                                );
                        $OK = 'KO';
                    } 
                    // Ahora montamos htmlFamiliasWeb si no htmlAlert ( elimino if id > 0 ya que no debería entrar nunca )
                    if ($OK = 'OK') {
                        $datos_familia_web = $RelacionIdsFamiliaWeb['datos']['0'];
                        $datos_familia_web += array( 'nombre' =>$nombre_familia_web,
                                                     'accion' =>'modificar',
                                                     'id_padre' => $familia['familiaPadre'],
                                                     'id_padre_web' =>$id_padre_web
                                                    );
                        // Montamos  html de formulario de familia web
                        $htmlFamiliasWeb = $ObjVirtuemart->htmlDatosFamiliaWeb($datos_familia_web, $combopadresWeb);
                    }
                }
                // Si existe plugin de virtuemart  y tiene permisos montamos boton Subir hijos juntos.
                if ($permisos['SubirHijasWeb'] == 1 && $OK='OK'){
                    $bottonSubirHijos='<a class="btn btn-info" onclick="subirHijosWeb('.$id.', '.$idTiendaWeb.')">Subir Hijos Web</a>';
                } 
            } 
        }   // --- Fin de datos de para formulario de relacion de la Web   ---
        // Ahora montamos el html del desplegable de familias hijos.
        // La variable idTiendaWeb indica la tienda web, es la que enviamos a htmlFamiliasHijas
        // Si no hubiera obtenido idTiendaWeb entonces el valor 0.
        $htmlFamiliasHijas = '';
        if (isset ($familia['hijos']) && count($familia['hijos'])>0){    
            // Ahora comprobamos si existe en la web las familias hijas, solo las que tiene relacion.
            foreach ($familia['hijos'] as $key=>$hijo){
                if (count($hijo['familiaTienda']) == 1){
                    // Si hay 1 relación continuamos, compramos que exita en listado ids de familias de la Web (idsFamiliasWeb)
                    $OK = FALSE;
                    if ($idsFamiliasWeb != 0){
                        // Compruebo si existe idsFamiliasweb, ya que si vamos comprobar que existe relacion de hijos, lo necesitamos.
                        $OK = in_array($hijo['familiaTienda'][0]['idFamilia_tienda'], $idsFamiliasWeb);
                    
                        if ($OK === FALSE){    
                            $familia['hijos'][$key]['familiaTienda'][0]['exites_web']='KO';
                            // Boqueamos botton de subir hijos, ya que existe una relación y no existe en la web, hay que eliminarla 
                            // primero antes de subir mas hijos.
                            $bottonSubirHijos = ' ';
                            // Creamos advertencia:
                            $erroresWeb[] =  $Cfamilias->montarAdvertencia('warning',
                                                'En  el hijo:<b>'.$hijo['familiaNombre'].'</b><br/>'
                                                .'Tiene una relación que no existe en la web'
                                                );
                        } else {
                            // Si existe en los ids entonces creamos link_front_end_categoria
                            $familia['hijos'][$key]['familiaTienda'][0]['exites_web']='OK';
                            $familia['hijos'][$key]['familiaTienda'][0]['link_front_end_categoria']=$ObjVirtuemart->ruta_categoria.$RelacionIdsFamiliaWeb['familiaTienda']['idFamilia_tienda'];
                        }
                    } else {
                        // No obtuvo $idFamiliasWeb por lo mostramos advertencia
                        // Esto esta repetido, ya 
                    
                    }
                } else {
                    // O no existe realizacion o existe mas de una relación.
                    // Si existe mas de una relacion añadimos advertencia.
                    if (count($hijo['familiaTienda']) > 1){
                        $erroresWeb[] =  $Cfamilias->montarAdvertencia('warning',
                                            'En  el hijo:<b>'.$hijo['familiaNombre'].'</b><br/>'
                                            .'Tiene mas de una relacion con la web'
                                            );
                    }
                }
            }
            $htmlFamiliasHijas = htmlTablaFamiliasHijas($familia['hijos'], $idTiendaWeb,$bottonSubirHijos);
        }
        // Si la familia tiene mostrar_tpv en 1 , lo ponemos en la variables $valor_check para mostrarlo
        $valor_check = 'value="0"';
        if ($familia['mostrar_tpv'] === '1'){
            $valor_check = 'value="1" checked';
        }
        //~ if (isset($RelacionIdsFamiliaWeb['datos'])){
            //~ echo  $ObjVirtuemart->ruta_categoria.$RelacionIdsFamiliaWeb['datos'][0]['idFamilia_tienda'];
            
        //~ }
          
        
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
        <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
        <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
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
</div>   
<script src="<?php echo $HostNombre; ?>/modulos/mod_familia/funciones.js"></script>        
</body>
</html>
