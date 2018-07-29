<!DOCTYPE html>
<html>
    <head>
        <?php
        include_once './../../inicial.php';
        include_once $URLCom . '/head.php';
//        include_once $URLCom.'/modulos/mod_producto/funciones.php';
//        include_once $URLCom.'/controllers/Controladores.php';
        include_once $URLCom . '/modulos/mod_familia/clases/ClaseFamilias.php';

        $titulo = 'Familias:';
        $familias = new ClaseFamilias();
        // Obtenemos los datos del id, si es 0, quiere decir que es nuevo.
        if (isset($_GET['id'])) {
            // Modificar Ficha 
            $id = $_GET['id']; // Obtenemos id para modificar.
            $titulo .= "Modificar";
            $familia = $familias->leer($id);
        } else {
            // Quiere decir que no hay id, por lo que es nuevo
            $titulo .= "Crear";
        }

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
            <form action="" method="post" name="formFamilia" >
                <div class="col-md-12">
                    <div class="col-md-12 btn-toolbar">
                        <a class="text-right" href="./ListaFamilias.php">Volver Atrás</a>
                        <input type="submit" value="Guardar">
                    </div>
                    <div class="col-md-6 Datos">
                        <?php // si es nuevo mostramos Nuevo  ?>
                        <div class="col-md-7">
                            <h4>Datos de la familia con ID:<?php echo $id==0?'nueva': $id; ?></h4>
                        </div>
                        <div class="col-md-5">
                            <input type="text" id="id" name="id" size="10" style="display:none;" value="<?php echo $id; ?>" >
                        </div>
                    </div>
                </div>
            <div class="row">
                <div class="col-md-2">
                    <div class="form-label-group">
                        <label for="inputdescripcion">Descripción: </label>
                        <input type="text" name="descripcion" id="inputdescripcion" 
                               class="form-control" placeholder="Descripción"  autofocus>
                    </div>
                </div>
                <div class="col-md-2">
                </div>
            </div>


        </div>
    </form>
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
</body>
</html>
