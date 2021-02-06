<!DOCTYPE html>
<html>
    <head>
          <?php 
            include_once './../../inicial.php';
            include_once $URLCom.'/head.php';
            include_once $URLCom . '/modulos/mod_balanza/clases/ClaseBalanza.php';
           
            include_once $URLCom . '/modulos/mod_balanza/funciones.php';
            include_once ($URLCom.'/controllers/parametros.php');
            include_once $URLCom.'/controllers/Controladores.php';
            $ClasesParametros = new ClaseParametros('parametros.xml');
            $Controler = new ControladorComun; 
            $Controler->loadDbtpv($BDTpv);
            $CBalanza=new ClaseBalanza($BDTpv);
            $titulo="Crear Balanza";
            $id=0;
            $nombreBalanza="";
            $modeloBalanza="";
            $plus=array();
            $parametros = $ClasesParametros->getRoot();	
            $VarJS = $Controler->ObtenerCajasInputParametros($parametros);
            $puls=array();
            $htmlTecla=htmlTecla("si");
            if(isset($_GET['id'])){
                $titulo="Modificar Balanza";
                $id=$_GET['id'];
                $datosBalanza=$CBalanza->datosBalanza($id);
                $nombreBalanza=$datosBalanza['datos'][0]['nombreBalanza'];
                $modeloBalanza=$datosBalanza['datos'][0]['modelo'];
                $htmlTecla=htmlTecla($datosBalanza['datos'][0]['conTecla']);
                
                //faltra select con las opciones de tecla
                
                $buscarPlus=$CBalanza->pluDeBalanza($id, 'a.plu');
                if(isset($buscarPlus['datos'])){
                    $plus=$buscarPlus['datos'];
                }
            }
             $htmlplus = htmlTablaPlus($plus, $id);
          ?>
        <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
        <script src="<?php echo $HostNombre; ?>/modulos/mod_balanza/funciones.js"></script>
        <script type="text/javascript">
              <?php echo $VarJS;?>
        </script>
    </head>
    <body>
    <?php     
       include_once $URLCom.'/modulos/mod_menu/menu.php';
    ?>
    <div class="container">
        <h2 class="text-center"> <?php echo $titulo;?></h2>
      
            <div class="col-md-12 ">
                <a class="text-ritght" href="./ListaBalanzas.php">Volver Atrás</a>
                <?php 
                if($id>0){
                ?>
                 <input type="submit" value="Modificar" class="btn btn-primary" onclick="ModificarBalanza(<?php echo $id;?>);">
                <?php 
                }else{
                ?>
                <input type="submit" value="Guardar" class="btn btn-primary" onclick="AgregarBalanza();">
                <?php 
                }
                ?>
            </div>
            <div class="col-md-3 Datos">
                <div class="col-md-12" id="errores">
                </div>
                 <div class="col-md-12">
                    <h4>Datos de la balanza con ID:<?php echo $id?></h4>
                </div>
                <div class="col-md-12">
                    <label>Nombre de la balanza</label>
                    <input type="text" name="nombreBalanza" id="nombreBalanza" value="<?php echo $nombreBalanza;?>">
                </div>
                 <div class="col-md-12">
                    <label>Modelo de la balanza</label>
                    <input type="text" name="modeloBalanza" id="modeloBalanza" value="<?php echo $modeloBalanza;?>">
                </div>
                <div class="col-md-12">
                    <label>Teclas en la balanza</label>
                    <select id="teclas" name="teclas">
                        <?php echo $htmlTecla;?>
                    </select>
                </div>
            </div>
            <div class="col-md-9 text-center">
              
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
  
    </body>
</html>
