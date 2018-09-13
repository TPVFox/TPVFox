<!DOCTYPE html>
<html>
    <head>
        <?php
        include_once './../../inicial.php';
        include_once $URLCom . '/head.php';
        include_once $URLCom . '/modulos/mod_producto/funciones.php';
        include_once $URLCom . '/modulos/mod_familia/funciones.php';
//        include_once $URLCom.'/controllers/Controladores.php';
        include_once $URLCom . '/modulos/mod_familia/clases/ClaseFamilias.php';

        $titulo = 'Familias:';
        $familias = new ClaseFamilias();
        $TodasFamilias = $familias->todoslosPadres('familiaNombre', TRUE);
        // Obtenemos los datos del id, si es 0, quiere decir que es nuevo.
        if (isset($_GET['id'])) {
            // Modificar Ficha 
            $id = $_GET['id']; // Obtenemos id para modificar.
        } else {
            $id = 0;
        }
        if ($id != 0) {
            $titulo .= "Modificar";
            $familia = $familias->leer($id);

            if ($familia['datos']) {
                $familia = $familia['datos'][0];
            }
            $familia['productos'] = $familias->contarProductos($id);
            $padres = $familias->familiasSinDescendientes($id, TRUE);
            $htmlFamiliasHijas = htmlTablaFamiliasHijas($id);
            $htmlProductos = htmlTablaFamiliaProductos($id);
        } else {
            // Quiere decir que no hay id, por lo que es nuevo
            $padres = $TodasFamilias;
            $titulo .= "Crear";
            $familia = [];
            $familia['beneficiomedio'] = 25.00; // cuando haya configuración, que salga de la configuracion
        }
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
//        include_once $URLCom . '/header.php';
        include_once $URLCom . '/modulos/mod_menu/menu.php';
        ?>


        <div class="container">

            <h2 class="text-center"> <?php echo $titulo; ?></h2>


            <!-- columna formulario -->
            <div class="col-md-8">
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
                            <div class="ui-widget">
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
            <div class="col-md-4 text-center">
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

        <!-- Modal -->
        <?php
        $combofamilias = ' <select name="padre" class="form-control" id="combopadremodal">';
        foreach ($padres['datos'] as $padre) {
            $combofamilias .= '<option value=' . $padre['idFamilia'];
            if (($id != 0) && ($familia['familiaPadre'] == $padre['idFamilia'])) {
                $combofamilias .= ' selected = "selected" ';
            }
            $combofamilias .= '>' . $padre['familiaNombre'] . '</option>';
        }
        $combofamilias .= '</select>';
        ?>        



        <div id="cambioFamiliaModal" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header btn-primary">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h3 class="modal-title text-center">Cambiar Familia de productos Seleccionados</h3>
                    </div>
                    <div class="modal-body">
                        <div class="row  ui-front">
                            Familia actual: <input class="form-control" id="inputFamiliaActualModal" 
                                                   value="--- Padre raíz ---"/>
                            <input id="inputIdactualModal" type="hidden" value="0">
                        </div>
                        <div id="formularioFamiliaModal" >
                            <div class="row  ui-front">
                                Cambiar familia a: <?php echo $combofamilias ?>

                                <input id="inputIdNuevaModal" type="hidden" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button id="btn-padre-grabar" class="btn btn-primary" >
                            <span class="glyphicon glyphicon-save"> </span>Grabar</button>&nbsp;
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="<?php echo $HostNombre; ?>/modulos/mod_familia/funciones.js"></script>        
    </body>
</html>
