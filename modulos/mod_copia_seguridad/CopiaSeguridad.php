<!DOCTYPE html>
<html>
    <head>
        <?php
	include './../../head.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	
	
	//~ echo '<pre>';
	//~ print_r($tickets);
	//~ echo '</pre>';

	
	?>
	
	<script>
	// Declaramos variables globales
	var checkID = [];
	</script> 
	<script src="<?php echo $HostNombre; ?>/modulos/mod_copia_seguridad/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
	
 
    </head>

<body>
        <?php
        //~ include './../../header.php';
         include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Copias de Seguridad </h2>
				</div>
	        <!--=================  Sidebar -- Menu y filtro =============== 
				Efecto de que permanezca fixo con Scroll , el problema es en
				movil
	        -->
	       
			<nav class="col-sm-2" id="myScrollspy">
				<a class="text-ritght" href="./tpv.php">Volver Atrás</a>
				<div data-offset-top="505">
				<h5> Opciones de copias de seguridad</h5>
				<ul class="nav nav-pills nav-stacked"> 
					<li><a href="./exportarBD_backup.php";>Hacer Copia Seguridad</a></li>
				 	<li><a href="#section2" onclick="metodoClick('restaurar');";>Restaurar</a></li>				
				</ul>
				</div>	
			</nav>
			<div class="col-md-10">
				<div>
					<div class="alert-info" style="width:30%" >
					<?php 	// Mostramos paginacion 
						$mensaje='Para restaurar <strong>indicar/pasar tabla</strong>.';
						echo $mensaje; 
					?>
					</div>
				</div>
		</div>
	</div>
    </div>		
</body>
</html>
