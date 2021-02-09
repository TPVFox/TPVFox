<!DOCTYPE html>
<html>
    <head>
<?php 
        include_once './../../inicial.php';
        include_once $URLCom.'/head.php';
        include_once $URLCom . '/modulos/mod_balanza/clases/ClaseBalanza.php';
       
        $CBalanza=new ClaseBalanza($BDTpv);
        $balanzas=$CBalanza->todasBalanzas();
        $balanzas=$balanzas['datos'];
        
      
        
        
?>
<script src="<?php echo $HostNombre; ?>/controllers/global.js"></script>
<script src="<?php echo $HostNombre; ?>/modulos/mod_balanza/funciones.js"></script>
</head>

<body>
<?php
    include_once $URLCom.'/modulos/mod_menu/menu.php';
?>

    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center">
                    <h2> Balanzas: Editar y Añadir Balanzas </h2>
            </div>
            <div class="col-sm-2">
                <div class="nav">
                    <h4> Balanzas</h4>
                    <h5> Opciones para una selección</h5>
                    <ul class="nav nav-pills nav-stacked">
                          <li><a href="#section2" onclick="metodoClick('AgregarBalanza');";>Añadir</a></li>
                          <li><a href="#section2" onclick="metodoClick('VerBalanza', 'balanza');";>Modificar</a></li>
                    </ul>
                </div>
                <div class="nav">
                    Selecciona una balanza:
                   <table class="table table-striped table-hover">
                       <tr>
                       <td>Id</td>
                       
                       <td>Nombre</td>
                       </tr>
                    <?php 
                    $checkUser = 0;
                    
                    foreach ($balanzas as $balanza){
                        $checkUser = $checkUser + 1; 
                        ?>
                        <tr>
                        
                            <td>
                             <?php
                            $check_name = 'checkUsu'.$checkUser;
                            echo '<input type="checkbox" id="'.$check_name.'" name="'.$check_name.'" value="'.$balanza['idBalanza'].'" class="check_balanza">';
                            ?>    
                            </td>
                            <td onclick="mostrarDatosBalanza(<?php echo $balanza['idBalanza'];?>)"><?php echo $balanza['nombreBalanza'];?></td>
                      </tr>
                        <?php 
                        
                    }
                    
                    ?>
                   </table>
                </div>
            </div>
            <div class="col-md-10">
                <div class="col-md-12" id="infoBalanza">
                    
                </div>
                <table class="table table-bordered table-hover tablaPrincipal">
                    <thead>
                    
                    </thead>
                    <tbody>
                    
                    </tbody>
                </table>
            </div>
        </div>
    
    </div>
</body>
</html>
