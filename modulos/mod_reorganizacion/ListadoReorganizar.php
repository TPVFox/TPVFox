<!DOCTYPE html>
<html>
    <head>
        <?php
        include './../../head.php';

        $Tienda = $_SESSION['tiendaTpv'];
        $idTienda = $Tienda['idTienda'];
        ?>
        <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
        <script src="<?php echo $HostNombre; ?>/modulos/mod_reorganizacion/reorganizar.js"></script> 
    </head>

    <body>
        <?php 
        //~ include './../../header.php'; 
         include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>

        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h2> Reorganización y Limpieza </h2>
                </div>
                <div class="col-md-10" id="tablareorg">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>L</th>
                                <th>Item a reorganizar</th>
                                <th>Progreso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><button id="boton-stock" class="btn"  >
                                        <span class="glyphicon glyphicon-save"> </span>Regenerar</button></td>
                                <td>Stock</td>
                                <td><div class="progress" style="margin:0 100px">
                                        <div id="bar0" class="progress-bar progress-bar-info" 
                                             role="progressbar" aria-valuenow="0" 
                                             aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                                            0 % completado
                                        </div>
                                    </div> </td>
                            </tr>
                        </tbody>
                    </table>
                    <div id="kaka"></div>
                </div>
            </div>
        </div>
    </body>
</html>
