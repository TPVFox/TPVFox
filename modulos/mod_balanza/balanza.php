<!DOCTYPE html>
<html>
    <head>
          <?php 
            include_once './../../inicial.php';
            include_once $URLCom.'/head.php';
            include_once $URLCom . '/modulos/mod_balanza/clases/ClaseBalanza.php';
            include_once $URLCom . '/modulos/mod_balanza/funciones.php';
            $CBalanza=new ClaseBalanza($BDTpv);
            $titulo="Crear Balanza";
            $id=0;
            $nombreBalanza="";
            $modeloBalanza="";
            $plus=array();
       
       
            $puls=array();
            if(isset($_GET['id'])){
                $titulo="Modificar Balanza";
                $id=$_GET['id'];
                $datosBalanza=$CBalanza->datosBalanza($id);
                $nombreBalanza=$datosBalanza['datos'][0]['nombreBalanza'];
                $modeloBalanza=$datosBalanza['datos'][0]['modelo'];
                //faltra select con las opciones de tecla
                
                $buscarPlus=$CBalanza->pluDeBalanza($id);
                if(isset($buscarPlus['datos'])){
                    $plus=$buscarPlus['datos'];
                }
            }
             $htmlplus = htmlTablaPlus($plus);
          ?>
          <script src="<?php echo $HostNombre; ?>/jquery/jquery-ui.min.js"></script>
         <link rel="stylesheet" href="<?php echo $HostNombre;?>/jquery/jquery-ui.min.css" type="text/css">
         <script src="<?php echo $HostNombre; ?>/lib/js/autocomplete.js"></script>
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
                <?php 
                if($id>0){
                ?>
                 <input type="submit" value="Modificar" class="btn btn-primary" onclick="ModificarBalanza();">
                <?php 
                }else{
                ?>
                <input type="submit" value="Guardar" class="btn btn-primary" onclick="AgregarBalanza();">
                <?php 
                }
                ?>
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
              
                    <div class="panel-group">
                        <?php 
                        $num = 1 ; // Numero collapse;
                        $titulo = 'PLUs';
                        echo htmlPanelDesplegable($num,$titulo,$htmlplus);
                        
                        ?>
                    </div>
            </div>
        <?php // Incluimos paginas modales
    echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
    include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
    // hacemos comprobaciones de estilos 
    ?>
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
    </body>
</html>
