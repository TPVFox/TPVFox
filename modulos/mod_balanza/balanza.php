<!DOCTYPE html>
<html>
    <head>
          <?php 
            include_once './../../inicial.php';
            include_once $URLCom.'/head.php';
          $titulos="Crear Balanza";
          $id=0;
          ?>
    </head>
    <body>
    <?php     
       include_once $URLCom.'/modulos/mod_menu/menu.php';
    ?>
    <div class="container">
        <h2 class="text-center"> <?php echo $titulo;?></h2>
        <form action="" method="post" name="formBalanza" onkeypress="return anular(event)">
            <div class="col-md-12 ">
                <a class="text-ritght" href="./ListaProductos.php">Volver Atrás</a>
                <input type="submit" value="Guardar" class="btn btn-primary">
            </div>
            <div class="col-md-6 Datos">
                 <div class="col-md-12">
                    <h4>Datos del producto con ID:<?php echo $id?></h4>
                </div>
            </div>
            <div class="col-md-6 text-center">
               
            </div>
        </form>
    </div>
    </body>
</html>
