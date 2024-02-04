<!DOCTYPE html>
<html>
    <head>
        <?php
        include './../../head.php';
        include_once'./clases/ClaseReorganizar.php';
        $CReorganizar = new ClaseReorganizar;
        $Tienda = $_SESSION['tiendaTpv'];
        $idTienda = $Tienda['idTienda'];
        $total_usuarios = count($CReorganizar->obtenerUsuarios());
        ?>
        <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
        <script src="<?php echo $HostNombre; ?>/modulos/mod_reorganizacion/reorganizar.js"></script> 
    </head>

    <body>
        <?php 
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
                                        <span class="glyphicon glyphicon-save"> </span>Regenerar Stock</button></td>
                                <td>Regenerar Stock según ventas y entradas tpv</td>
                                <td><div class="progress" style="margin:0 100px">
                                        <div id="bar0" class="progress-bar progress-bar-info" 
                                             role="progressbar" aria-valuenow="0" 
                                             aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                                            0 % completado
                                        </div>
                                    </div> </td>
                            </tr>
                            <?php if ( count($CReorganizar->SetPlugin('ClaseVirtuemart')->TiendaWeb)>0){;
                                // Solo mostramos si hay web conectada a tienda principal.
                                ?>
                            <tr>
                                <td><button id="boton_subir_stock" class="btn"  >
                                        <span class="glyphicon glyphicon-save"> </span>Subir Stock y Precios</button></td>
                                <td>Subir Stock y precios a web</td>
                                <td><div class="progress" style="margin:0 100px">
                                        <div id="bar1" class="progress-bar progress-bar-info" 
                                             role="progressbar" aria-valuenow="0" 
                                             aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                                            0 % completado
                                        </div>
                                    </div> </td>
                            </tr>
                            <?php } ?>

                            </tr>

                            <tr>
                                <td><button onclick= reorganizarPermisosModulos(0,<?php echo $total_usuarios ;?>) class="btn"  >
                                        <span class="glyphicon glyphicon-save"> </span>Reorganizar permisos</button></td>
                                <td>Limpiar y crea permisos de modulos inexistentes</td>
                                <td><div class="progress" style="margin:0 100px">
                                        <div id="bar2" class="progress-bar progress-bar-info" 
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

<script type="text/javascript">
$(function () {

    $("#boton-stock").on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();

        contarProductosEstoqueables(function (respuesta) {
            var obj = JSON.parse(respuesta);
            if (obj.totalProductos > 0) {
                var totalProductos = obj.totalProductos;
                $("#bar0").show();
                $("#boton-stock").prop("disabled",true);
                RegenerarStock(0, 100, totalProductos,'0');
            }

        });
    });

    $("#boton_subir_stock").on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();
        // La idea es subir fichero con todos los productos y su stock.
        // una vez subido en la web ejecutar en segundo plano.
        
        contarProductosWeb(function (respuesta) {
            var obj = JSON.parse(respuesta);
            if (obj.totalProductos > 0) {
                var totalProductos = obj.totalProductos;
                $("#bar1").show();
                $("#boton_subir_stock").prop("disabled",true);
                SubirStockWeb(0, 100, totalProductos,'1');
            }

        });
    });

});
</script>
