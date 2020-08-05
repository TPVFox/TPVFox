<!DOCTYPE html>
<html>
    <head>
        <?php
        include_once './../../inicial.php';
        include_once $URLCom . '/head.php';
        include_once $URLCom . '/modulos/mod_producto/funciones.php';
        include_once $URLCom . '/modulos/mod_familia/funciones.php';
        include_once $URLCom . '/modulos/mod_familia/clases/ClaseFamilias.php';
        $errores = array();
        $titulo = 'Familias:';
        $id = 0;
        $idTiendaWeb= 0;
        $htmlProductos= '';
        $htmlFamiliasWeb='';
        $bottonSubirHijos = ' ';    //  Boton de subir hijos si hay web
        $nombre_familia_web = '';
        $Cfamilias = new ClaseFamilias();
        $padres = $Cfamilias->todoslosPadres('familiaNombre', TRUE);
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
        echo '<pre>';
        print_r($familia['familiaTienda']);
        echo '</pre>';
        // Montamos el combo 
        $combopadres = $Cfamilias->htmlComboFamilias($padres['datos'], $familia['familiaPadre']);
       
        // Cargamos la clase virtuemart plugin
        $ObjVirtuemart = $Cfamilias->SetPlugin('ClaseVirtuemartFamilia');
              
        if(isset($ObjVirtuemart->TiendaWeb)){
            // Obtenemos los datos de la tienda Web del plugin
            $tiendaWeb=$ObjVirtuemart->getTiendaWeb();
            $idTiendaWeb=$tiendaWeb['idTienda'];
            // Obtenemos el id de la familia en tpv y id familia en la web
            $idsFamiliaTiendaWeb=$Cfamilias->obtenerRelacionFamilia($idTiendaWeb, $id);
            if (isset($idsFamiliaTiendaWeb['datos'])){
                if (count($idsFamiliaTiendaWeb['datos'])> 1){
                    $errores[] = $Cfamilias->montarAdvertencia('danger'
                                        ,'Esta familia tienes varias relaciones en tpv, es un error grabe'
                                        );
                }
            }
        }
        echo '<pre>';
        echo ' EL problema esta aqui, debemos montar todasfamiliasweb igualmente aunque no tengamos relacion';
        print_r($idsFamiliaTiendaWeb);
        echo '</pre>';
        if (isset($idsFamiliaTiendaWeb['datos'])){
            // Solo ejecutamos si existe familia relacionada en tpv con la web
            if(isset($ObjVirtuemart->TiendaWeb) && count($errores) === 0){
                // No hubo error y obtuvo id de tienda we, continuamos obteniendo todas las familias que existen en la web
                $todasFamiliasWeb = array(); // Por defecto es un array vacio...
                $t = $ObjVirtuemart->todasFamilias();
                if ( isset ($t['Datos']['item'])){
                    $idsFamilias = array_column($padres['datos'],'idFamilia'); // Array con ids familias tpv sin descendientes
                    $todasFamiliasWeb = $t['Datos']['item'];
                    foreach ($todasFamiliasWeb as $key => $familiaWeb){
                        // Comprobamos si existe relacion de todas las familias Web 
                        $existe = $Cfamilias->obtenerRelacionFamilia_tienda($idTiendaWeb,$familiaWeb['virtuemart_category_id']);
                        if (!isset($existe['datos'])){
                            // Si no existe relacion de esa familia web la eliminamos del array y mostramos un error.
                            $errores[] =  $Cfamilias->montarAdvertencia('warning',
                                            'No existe la relacion en tpv con la familia '
                                            .$familiaWeb['nombre'].' de la web, por eso no aparece en el select'
                                            );
                            unset($todasFamiliasWeb[$key]);
                        } else {
                            // Existe relacion familia web con tppv.
                            // Añadimos a todasFamiliasWeb el idFamilia de tpv
                            $todasFamiliasWeb[$key]['idFamilia'] = $existe['datos'][0]['idFamilia'];
                            if ($idsFamiliaTiendaWeb['datos']['0']['idFamilia'] == $existe['datos'][0]['idFamilia']){
                                // Obtenemos el nombre para cubrir en el formulario
                                $nombre_familia_web =  $todasFamiliasWeb[$key]['nombre'];
                                $id_padre_web = $familiaWeb['padre'];
                            }
                            // El nombre familia puede ser distinto, por lo tomamos el nombre familia de la web
                            // la referencia en el array "familiaNombre" hace falta para el combo.
                            $todasFamiliasWeb[$key]['familiaNombre'] = $todasFamiliasWeb[$key]['nombre']; 
                            // Ahora tenemos que comprobar:
                            //     1.- Tenemos que quitar las familias hijas de la familia Padre seleccionada.
                            //        ya que un hijo no puede ser padre de su padre.
                            $OK = in_array($todasFamiliasWeb[$key]['idFamilia'], $idsFamilias);
                            if ($OK === FALSE){
                                // Eliminamos ya que puede ser descendente o no existe en tpv
                                 unset($todasFamiliasWeb[$key]);
                            }
                            
                        }
                    }
                    if ($nombre_familia_web === ''){
                        // No se encontro el nombre de la familia en la web para familia o esta en la web tiene titulo blanco (ojo)
                        $errores[] =  $Cfamilias->montarAdvertencia('danger',
                                            'Error intentar obtener virtuemart_category_id:'
                                            .$idsFamiliaTiendaWeb['datos']['0']['idFamilia'] .' o esta el titulo en blanco en la web.'
                                            );
                    } else {
                        // Fue correcto por lo que ejecutamos htmlComboFamilias
                        
                        $combopadresWeb = $Cfamilias->htmlComboFamilias($todasFamiliasWeb, $id_padre_web,'virtuemart_category_id');
                    }

                } else {
                        // Si conecto,pero no obtuvo items, por lo que debesmos informar que no se obtuvo ninguna familia
                        $errores[] = $Cfamilias->montarAdvertencia('warning',
                                            'No se pudo obtener ninguna familia.'
                                            );
                }
                
                if (isset($t['htmlAlerta'])){
                    // Si hubo un error en conexion lo mostramos.
                    $htmlFamiliasWeb = $t['htmlAlerta'];
                } else {
                    // Ahora montamos htmlFamiliasWeb si no htmlAlert ( elimino if id > 0 ya que no debería entrar nunca )
                    $datos_familia_web = $idsFamiliaTiendaWeb['datos']['0'];
                    $datos_familia_web += array( 'nombre' =>$nombre_familia_web,
                                                 'accion' =>'modificar',
                                                 'id_padre' => $familia['familiaPadre'],
                                                 'id_padre_web' =>$id_padre_web
                                                );
                    // Montamos  html de formulario de familia web
                    $htmlFamiliasWeb = $ObjVirtuemart->htmlDatosFamiliaWeb($datos_familia_web, $combopadresWeb);
                }
                // Si existe plugin de virtuemart  y tiene permisos montamos boton Subir hijos juntos.
                if ($ClasePermisos->getAccion("SubirFamiliasHijasWeb") == 1){
                    $bottonSubirHijos='<a class="btn btn-info" onclick="subirHijosWeb('.$id.', '.$idTiendaWeb.')">Subir Hijos Web</a>';
                    
                } 
            }
        } else {
            // No esta creada familia web
            if ($id > 0){
                // No es nuevo, pero no tiene relacion en la con la web.
                $errores[] = $Cfamilias->montarAdvertencia('warning'
                                        ,'No esta creada esta familia en la web o no existe registro en tabla familiasTienda.'
                                        );
                // Montamos array $datos_familia_web ya que no esta montado:
                $datos_familia_web = array ('idFamilia' => $id,
                                            'idFamilia_tienda' => 0,
                                            'nombre' => $familia['familiaNombre'],
                                            'accion' => 'add',
                                            'id_padre' => $familia['familiaPadre'],
                                            'id_padre_web' =>$id_padre_web
                                            );
                //NO Creamos formulario de familia web si no existe.
                $htmlFamiliasWeb = $ObjVirtuemart->htmlDatosFamiliaWeb($datos_familia_web, $combopadres);
            }

        }
        // Ahora montamos el html del desplegable de familias hijos.
        // La variable idTiendaWeb indica la tienda web, es la que enviamos a htmlFamiliasHijas
        // Si no hubiera obtenido idTiendaWeb entonces el valor 0.
        $htmlFamiliasHijas = '';
        if (isset ($familia['hijos']) && count($familia['hijos'])>0){    
            $htmlFamiliasHijas = htmlTablaFamiliasHijas($familia['hijos'], $idTiendaWeb,$bottonSubirHijos);
        }
        // Ahora montamos valorCheck de mostrar familia en tpv
        $valor_check = 'value="0"';
        if ($familia['mostrar_tpv'] === '1'){
            $valor_check = 'value="1" checked';
        }
        ?>

        <script src="<?php echo $HostNombre; ?>/jquery/jquery-ui.min.js"></script>
        <link rel="stylesheet" href="<?php echo $HostNombre; ?>/jquery/jquery-ui.min.css" type="text/css">
        <link rel="stylesheet" href="<?php echo $HostNombre; ?>/modulos/mod_familia/familias.css" type="text/css">
        <script src="<?php echo $HostNombre; ?>/lib/js/autocomplete.js"></script>    
        <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
        <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
        <?php 
        
        
        ?>
    </head>
    <body>
        <?php
        include_once $URLCom . '/modulos/mod_menu/menu.php';
        //~ echo '<pre>';
        //~ print_r($familia);
        //~ echo '</pre>';
        ?>


<div class="container">
    <?php
        
        if (isset($familia['errores'])){
            foreach ($familia['errores'] as $comprobaciones){
                echo $Cfamilias->montarAdvertencia($comprobaciones['tipo'],$comprobaciones['mensaje'],'OK');
                // No permito continuar, ya que hubo error grabe.
                exit;
            }
        }
        if (isset($errores) && count($errores)>0){
            foreach ($errores as $error){
                echo $Cfamilias->montarAdvertencia($error['tipo'],$error['mensaje'],'OK');
                if ($error['tipo'] === 'danger'){
                    // No permito continuar ya que es un error grabe.
                    exit;
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
            echo $htmlFamiliasWeb;
        ?>
        </div>
</div>   
<script src="<?php echo $HostNombre; ?>/modulos/mod_familia/funciones.js"></script>        
</body>
</html>
