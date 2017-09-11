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
					<li><a href="<?php echo $HostNombre.'/modulos/mod_usuario/ListaUsuarios.php';?>">Usuarios</a></li>
					<li><a href="<?php echo $HostNombre.'/modulos/mod_tienda/ListaTiendas.php';?>">Tiendas</a></li>
					<li><a href="<?php echo $HostNombre.'/modulos/mod_importar/Importar.php';?>">Importar</a></li>
				</ul>
				
				<div class="nav navbar-nav navbar-right">
					<?php 
					if (isset($_SESSION)){
						// la ruta getcwd() no es la misma siempre.
						if (isset($DirectorioInicio)) {
							$Ruta = 'css/img/imgUsuario.png';
						} else {
							$Ruta = './../../css/img/imgUsuario.png'; // Porque estoy en modulo...
						}
					?>
					
						<span><img src="<?php echo $Ruta; ?>" class="img-responsive"  width="30" height="30"/> </span>
					<?php 
						print_r($_SESSION['usuario']);
					}
					?>
					
				</div>
			</div>
			
		</div>
	</nav>
<!-- Fin de menu -->
</header>
