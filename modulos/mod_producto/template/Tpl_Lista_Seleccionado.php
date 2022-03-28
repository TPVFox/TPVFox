<!DOCTYPE html>
<html>
    <head>
        <?php include_once $URLCom.'/head.php'; ?>
        <script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
        <script src="<?php echo $HostNombre; ?>/lib/js/tpvfoxSinExport.js"></script> 
    </head>
    <body>
        <?php
        include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h2> <?php echo $Tpl['Titulo'] ;?> </h2>
                </div>
                    <div class="col-sm-2">
                        <?php echo $Controler->getHtmlLinkVolver('Volver ');?>
                        <br><br>        
                        <?php include_once './template/'.$Tpl['view_columna'];?>
                    </div>
                    <div class="col-md-10">
                        <?php include_once './template/'.$Tpl['view_tabla'];?>
                    </div>
            </div>
		 </div>
    </body>
</html>
