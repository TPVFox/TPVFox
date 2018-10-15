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
        $Cfamilias = new ClaseFamilias();
        $TodasFamilias = $Cfamilias->todoslosPadres('familiaNombre', TRUE);
        // Obtenemos los datos del id, si es 0, quiere decir que es nuevo.
        if (isset($_GET['id'])) {
            // Modificar Ficha 
            $id = $_GET['id']; // Obtenemos id para modificar.
        } 
        if ($id != 0) {
            $titulo .= "Modificar";
            $familia = $Cfamilias->datosFamilia($id);//$Cfamilias->leer($id);
            $padres = $Cfamilias->familiasSinDescendientes($id, TRUE);
            $htmlProductos = htmlTablaFamiliaProductos($id);
        } else {
            // Quiere decir que no hay id, por lo que es nuevo
            $padres = $TodasFamilias;
            $titulo .= "Crear";
            $familia = [];
            $familia['familiaNombre'] = '';
            $familia['productos'] = 0;

            $familia['beneficiomedio'] = 25.00; // cuando haya configuración, que salga de la configuracion
        }
        // Montamos el combo ( esto debería haber una funcion )
        $vp = '';
        $combopadres = ' <select name="padre" class="form-control " '
                . 'id="combopadre">';
        foreach ($padres['datos'] as $padre) {
            $combopadres .= '<option value=' . $padre['idFamilia'];
            if (($id != 0) && ($familia['familiaPadre'] == $padre['idFamilia'])) {
                $combopadres .= ' selected = "selected" ';
                $vp = $padre['idFamilia'];
            }
            $combopadres .= '>' . $padre['familiaNombre'] . '</option>';
        }
        $combopadres .= '</select>';
        $combopadres .= '<input type="hidden" name="idpadre" id="inputidpadre" value="'.$vp.'">'; 

        // Cargamos la clase virtuemart plugin
        $ObjVirtuemart = $Cfamilias->SetPlugin('ClaseVirtuemartFamilia');
              
        if(isset($ObjVirtuemart->TiendaWeb)){
            // Obtenemos los datos de la tienda Web del plugin
            $tiendaWeb=$ObjVirtuemart->getTiendaWeb();
            
            $idTiendaWeb=$tiendaWeb['idTienda'];
            // Obtenemos el id de la familia en tpv y id familia en la web
            $idsFamiliaTiendaWeb=$Cfamilias->obtenerRelacionFamilia($idTiendaWeb, $id);
            if (isset($idsFamiliaTiendaWeb['datos'])){
                if ( count($idsFamiliaTiendaWeb['datos'])> 1){
                    $errores[] =
                            array ( 'tipo'=>'danger',
                                     'mensaje' =>'Esta familia tienes varias relaciones en tpv, es un error grabe',
                                     'dato' => $idsFamiliaTiendaWeb['datos']
                                    );
                }
            }
            // Obtenemos todas las familias que existen en la web con su id, nombre y id padre en la web
            $todasFamiliasWeb = array(); // Por defecto es un array vacio...
            $t = $ObjVirtuemart->todasFamilias();
            if ( isset ($t['Datos']['item'])){
                $todasFamiliasWeb = $t['Datos']['item'];
                foreach ($todasFamiliasWeb as $key => $familiaWeb){
                    $existe = $Cfamilias->obtenerRelacionFamilia_tienda($idTiendaWeb,$familiaWeb['virtuemart_category_id']);
                    //~ echo '<pre>';
                    //~ print_r($existe);
                    //~ echo '</pre>';
                    if (!isset($existe['datos'])){
                        // Si no hay datos, es que no existe.. por lo que la borramos, pero mostramos un error.
                        $errores[] =
                        array ( 'tipo'=>'warning',
								 'mensaje' =>'No existe la relacion en tpv con la familia '.$familiaWeb['nombre'].' de la web, por eso no aparece en el select',
								 'dato' => $familiaWeb
								);
                        unset($todasFamiliasWeb[$key]);
                    } else {
                        // Existe relacion que con idFamilia_tienda.
                        // Añadimos a todasFamiliasWbe el idFamilia de tpv
                        $todasFamiliasWeb[$key]['idFamilia'] = $existe['datos'][0]['idFamilia'];

                        if(isset($idsFamiliaTiendaWeb['datos'])){
                            // Ahora comprobamos que si es el mismo idFamiliaTienda para crear $datosFamilia
                            if ($idsFamiliaTiendaWeb['datos']['0']['idFamilia'] === $todasFamiliasWeb[$key]['idFamilia']){
                                $datosFamilia = $todasFamiliasWeb[$key];
                            }
            
                        }
                    }
                }
            } 
                if (!isset($t['htmlAlerta'])){
                    // No hubo error de conexion por lo que continuamos.
                    
                    // Obtenemos el html de familias en la web para mostrar.
                    $htmlFamiliasWeb='';
                    if(isset($idsFamiliaTiendaWeb['datos'])){
                        $htmlFamiliasWeb=$ObjVirtuemart->datosWebdeFamilia($datosFamilia, $idTiendaWeb, $todasFamiliasWeb, $id);
                    }else{
                        // Si NO existe relacion es que esta familia no esta en la web.
                        // Por lo tanto creamos Html para apoder añadir , no modificar.
                        // Solo si ya esta grabado, no permitimos si su id es 0
                        if($id>0){
                            if($ObjVirtuemart->getTiendaWeb()!=false){
                                $htmlFamiliasWeb=$ObjVirtuemart->htmlDatosVacios($id, $combopadres, $idTiendaWeb);
                            }
                        }
                    }
                } else {
                    $htmlFamiliasWeb = $t['htmlAlerta'];
                }

        }
        // Ahora montamos el html del desplegable de familias hijos.
        $bottonSubir = ' ';    //  Boton de subir hijos si hay web
        if (isset($ObjVirtuemart->TiendaWeb)){
            // Si existe plugin de virtuemart  y tiene permisos montamos boton Subir
            if ($ClasePermisos->getAccion("SubirFamiliasHijasWeb") == 1){
                $bottonSubir='<a class="btn btn-info" onclick="subirHijosWeb('.$id.', '.$idTiendaWeb.')">Subir Hijos Web</a>';
                
            } 
        }
        
        
        // La variable idTiendaWeb indica la tienda web, es la que enviamos a htmlFamiliasHijas
        // Si no hubiera obtenido idTiendaWeb entonces el valor 0.
        $htmlFamiliasHijas = '';
        if (isset ($familia['hijos'])){    
        $htmlFamiliasHijas = htmlTablaFamiliasHijas($familia['hijos'], $idTiendaWeb,$bottonSubir);
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
        ?>


<div class="container">
    <?php 
        if (isset($familia['errores'])){
            foreach ($familia['errores'] as $comprobaciones){
                echo '<div class="alert alert-'.$comprobaciones['tipo'].'">'.$comprobaciones['mensaje'].'</div>';
            }
            echo '<div class="alert alert-danger">No permito continuar, ponte en contacto con administrador sistemas</div>';
            // No permito continuar, ya que hubo error grabe.
            exit;
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
                            <?php echo $combopadres ?>                                
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
                    <div class="col-md-3">
                        <div class="form-label-group">
                            <label for="inputProductos">Productos: </label>
                            <input type="text" name="productos" id="inputProductos" 
                                   value="<?php echo $familia['productos'] ?>"
                                   readonly="readonly"
                                   class="form-control" placeholder="productos con esta familia"  >
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
