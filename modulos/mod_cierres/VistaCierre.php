<!DOCTYPE html>
<html>
    <head>
		<?php
			include './../../head.php';
			include './funciones.php';
		if (isset($_GET['id'])) {
			$idCierre=$_GET['id'];
			
		}
		$cierreUnico = obtenerCierreUnico($BDTpv,$idCierre);
		
		?>
	</head>
	
	<body>
	<?php
	
		echo '<pre>';
			print_r($cierreUnico);
		echo '</pre>';
		
	?>
	</body>
</html>
