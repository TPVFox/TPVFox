<!DOCTYPE html>
<html>
    <head>
        <?php
        include_once './../../inicial.php';
        include_once $URLCom . '/head.php';
        include_once $URLCom . '/modulos/mod_producto/funciones.php';
        include_once $URLCom . '/modulos/mod_familia/funciones_familia.inc.php';
//        include_once $URLCom.'/controllers/Controladores.php';
        include_once $URLCom . '/modulos/mod_familia/clases/ClaseFamilias.php';

        $titulo = 'Familias:';
        $familias = new ClaseFamilias();
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
            $padres = $familias->todoslosPadres('familiaNombre', TRUE);
            $titulo .= "Crear";
        }

        $combopadres = ' <select name="padre" class="form-control" id="combopadre">';
        foreach ($padres['datos'] as $padre) {
            $combopadres .= '<option value=' . $padre['idFamilia'];
            if (($id != 0) && ($familia['familiaPadre'] == $padre['idFamilia'])) {
                $combopadres .= ' selected = "selected" ';
            }
            $combopadres .= '>' . $padre['familiaNombre'] . '</option>';
        }
        $combopadres .= '</select>';
        ?>

        <script src="<?php echo $HostNombre; ?>/jquery/jquery-ui.min.js"></script>
        <link rel="stylesheet" href="<?php echo $HostNombre; ?>/jquery/jquery-ui.min.css" type="text/css">
        <script src="<?php echo $HostNombre; ?>/lib/js/autocomplete.js"></script>    

        <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
        <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
    </head>
    <body>
        <?php
        include_once $URLCom . '/header.php';
        ?>


        <div class="container">

            <h2 class="text-center"> <?php echo $titulo; ?></h2>
            
            
            <!-- columna formulario -->
            <div class="col-md-8">
                <form action="" method="post" name="formFamilia" >
                    <div class="col-md-12">
                        <div class="btn-toolbar">
                            <a class="text-right" href="./ListaFamilias.php">Volver Atrás</a>
                            <button class="btn-link" id="btn-fam-grabar">Guardar</button>
                        </div>
                        <div class=" Datos">
                            <?php // si es nuevo mostramos Nuevo   ?>
                            <div class="col-md-7">
                                <h4>Datos de la familia con ID:<?php echo $id == 0 ? 'nueva' : $id; ?></h4>
                            </div>
                            <div class="col-md-5">
                                <input type="text" id="id" name="id" size="10" style="display:none;" value="<?php echo $id; ?>" >
                            </div>
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
                            <div class="form-label-group">
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
                    $titulo = 'Poductos';
                    echo htmlPanelDesplegable($num, $titulo, $htmlProductos);
                    ?>

                    <!-- Inicio collapse de Referencias Tiendas --> 

                    <!-- Fin de panel-group -->
                </div> 
                <!-- Fin div col-md-6 -->
            </div>


        </div>

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
        </style>
<script src="<?php echo $HostNombre; ?>/modulos/mod_familia/familias.js"></script>        
    </body>
</html>
