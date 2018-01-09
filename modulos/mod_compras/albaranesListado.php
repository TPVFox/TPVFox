
<!DOCTYPE html>
<html>
<head>
<?php
include './../../head.php';
?>

</head>

<body>
<?php

	include '../../header.php';
	?>
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Albaranes Compras: Editar y Añadir albaranes </h2>
				</div>
					<nav class="col-sm-2">
				<h4> Albaranes</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
				<?php 
					if ($Usuario['group_id'] === '1'){
				?>
					<li><a href="#section2" onclick="metodoClick('AgregarAlbaran');";>Añadir</a></li>
					<?php 
				}
					?>
					<li><a href="#section2" onclick="metodoClick('Ver','albaran');";>Modificar</a></li>
				
				</ul>	
			</nav>
	</body>
</html>
