<!DOCTYPE html>
<html>
    <head>
<?php 
        include_once './../../inicial.php';
        include_once $URLCom.'/head.php';
        include_once $URLCom . '/modulos/mod_balanza/clases/ClaseBalanza.php';
        include_once $URLCom.'/controllers/Controladores.php';
        include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';
        $Controler = new ControladorComun; 
        $CBalanza=new ClaseBalanza($BDTpv);
        
        
        $NPaginado = new PluginClasePaginacion(__FILE__);
        $campos = array( 'nombreBalanza');
        $NPaginado->SetCamposControler($Controler,$campos);
        $NPaginado->SetOrderConsulta('nombreBalanza');
        $filtro= $NPaginado->GetFiltroWhere('OR'); // mando operador para montar filtro ya que por defecto es AND
        $CantidadRegistros=0;
        $a =$CBalanza->todasBalanzasLimite($filtro);
        $CantidadRegistros = count($a['datos']);
        $NPaginado->SetCantidadRegistros($CantidadRegistros);
        $htmlPG = $NPaginado->htmlPaginado();
        $a=$CBalanza->todasBalanzasLimite($filtro.$NPaginado->GetLimitConsulta());
        
        $balanzas=$a['datos'];
?>
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
            </div>
            <div class="col-md-10">
                <p>
                    -Balanzas encontradas BD local filtrados:
                    <?php echo $CantidadRegistros; ?>
                </p>
                <?php 	// Mostramos paginacion 
                echo $htmlPG;
                //enviamos por get palabras a buscar, las recogemos al inicio de la pagina
                ?>
                <table class="table table-bordered table-hover tablaPrincipal">
                    <thead>
                        <tr>
                            <td></td>
                            <td>Id</td>
                            <td>Nombre</td>
                            <td>Modelo</td>
                            <td>Tecla</td>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                     $checkUser = 0;
                    foreach ($balanzas as $balanza){
                       $checkUser++;  
                       ?>
                       <tr>
                        <td class="rowUsuario">
                       <?php 
                        $check_name = 'checkUsu'.$checkUser;
                        echo '<input type="checkbox" id="'.$check_name.'" name="'.$check_name.'" 
                            value="'.$balanza['id'].'" class="check_balanza">';
                            ?>
                            </td>
                            <td><?php echo $balanza['idBalanza']?></td>
                            <td><?php echo $balanza['nombreBalanza']?></td>
                            <td><?php echo $balanza['modelo']?></td>
                            <td><?php echo $balanza['conTecla']?></td>
                            </tr>
                            <?php
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    
    </div>
</body>
</html>
