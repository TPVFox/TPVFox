<!DOCTYPE html>
<html>
    <head>
          <?php 
            include_once './../../inicial.php';
            include_once $URLCom.'/head.php';
          $titulo="Crear Balanza";
          $id=0;
          $nombreBalanza="";
          $modeloBalanza="";
          ?>
          <script src="<?php echo $HostNombre; ?>/modulos/mod_balanza/funciones.js"></script>
    </head>
    <body>
    <?php     
       include_once $URLCom.'/modulos/mod_menu/menu.php';
    ?>
    <div class="container">
        <h2 class="text-center"> <?php echo $titulo;?></h2>
      
            <div class="col-md-12 ">
                <a class="text-ritght" href="./ListaProductos.php">Volver Atrás</a>
                <input type="submit" value="Guardar" class="btn btn-primary" onclick="AgregarBalanza();">
            </div>
            <div class="col-md-6 Datos">
                <div class="col-md-12" id="errores">
                </div>
                 <div class="col-md-12">
                    <h4>Datos de la balanza con ID:<?php echo $id?></h4>
                </div>
                <div class="col-md-12">
                    <div class="col-md-6">
                        <label>Nombre de la balanza</label>
                        <input type="text" name="nombreBalanza" id="nombreBalanza" value="<?php echo $nombreBalanza;?>">
                    </div>
                    <div class="col-md-6">
                        <label>Modelo de la balanza</label>
                        <input type="text" name="modeloBalanza" id="modeloBalanza" value="<?php echo $modeloBalanza;?>">
                    </div>
                </div>
                <div class="col-md-12">
                    <label>Teclas en la balanza</label>
                    <select id="teclas" name="teclas">
                        <option value="si">Si</option>
                        <option value="no">No</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6 text-center">
               
            </div>
       
    </div>
    </body>
</html>
