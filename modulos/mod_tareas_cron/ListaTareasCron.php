
<?php
    include_once './../../inicial.php';	
    include_once $URLCom.'/modulos/mod_tareas_cron/clases/CTareasCron.php';

    $CTareasCron= new CTareasCron();
        
    $tareasCron = $CTareasCron->list();
?>    
	
    <!DOCTYPE html>
<html>
    <head>  
    <?php include_once $URLCom.'/head.php';?>	
    </head>
<body>
        <?php
         include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Listados de tareas </h2>
			</div>
	       
			<nav class="col-sm-2" id="myScrollspy">
				<div data-offset-top="505">
				<ul class="nav nav-pills nav-stacked"> 
					<li><a href="TareaCron.php">Nueva</a></li>
				</ul>
				</div>	

			</nav>		
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Nombre</th>
                            <th>Período (minutos)</th>
                            <th>Ruta</th>
                            <th>Última ejecución</th>
                        </tr>
                    </thead>
                   
                    <?php
                    foreach($tareasCron as $tareaCron){    
                        ?>
                    <tr>
                        <td><input class="rowCheck" type="checkbox" name="informe" value="1">
                        <td><?php echo $tareaCron['nombre'] ?></td>
                        <td><?php echo $tareaCron['periodo'] ?> </td>
                        <td><?php echo $tareaCron['ruta'] ?> </td>
                        <td><?php echo $tareaCron['ultima_ejecucion'] ?> </td>
                    </tr>
                    <?php 
                    }
                    ?>
			</div>
		</div>
	</div>
    </div>
    <script>
	// Declaramos variables globales
	var checkID = [];
	</script> 
    <!-- Cargamos funciones de modulo. -->
    <script src="<?php echo $HostNombre; ?>/modulos/mod_/funciones.js" type="module"></script>	
    <?php
     echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
     include $URLCom.'/plugins/modal/ventanaModal.php';
    ?>
</body>
</html>
