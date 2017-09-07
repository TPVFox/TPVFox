<?php
    //añadido
	/* Deberíamos hacer un pequeño proceso comprobaciones.
	 * */
	
?>

<header>
<!-- Debería generar un fichero de php que se cargue automaticamente el menu -->
	<nav class="navbar navbar-default">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
				  <span class="sr-only">Desplegar navegación</span>
				  <span class="icon-bar"></span>
				  <span class="icon-bar"></span>
				  <span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="#">TpvFox</a>
			</div>
			<div class="collapse navbar-collapse navbar-ex1-collapse">
				<ul class="nav navbar-nav navbar-left ">
					<li><a href="<?php echo $HostNombre.'/index.php'?>">Home</a></li>
					<li><a href="<?php echo $HostNombre.'/modulos/mod_recambios/ListaArticulos.php';?>">Articulos</a></li>
					<li><a href="./estatico">Documentacion</a></li>
					<li><a href="<?php echo $HostNombre.'/modulos/mod_tpv/tpv.php';?>">Tickets</a></li>

					<li><a href="<?php echo $HostNombre.'/modulos/mod_importar/Importar.php';?>">Importar</a></li>
				</ul>
			</div>
			<div>
				<?php if (isset($_SESSION)){
					echo 'usuario: '$_SESSION['usr'];
				}
				?>
			</div>
		</div>
	</nav>
<!-- Fin de menu -->
</header>
