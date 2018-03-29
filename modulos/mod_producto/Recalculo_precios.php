<!DOCTYPE html>
<html>
	<head>
		<?php 
		include './../../head.php';
        include './funciones.php';
        include ("./../mod_conexion/conexionBaseDatos.php");
        include ("./../../controllers/Controladores.php");
        
        $titulo="Recalculo precios PVP ";
        if ($_GET['id']){
			$id=$_GET['id'];
			$dedonde="albaran";
			
			$subtitulo='de '.$dedonde.' :'.$id;
			$titulo=$titulo.' '.$subtitulo;
		}
		?>
		
		
	</head>
	<body>
		<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
		<div class="container">
			<h2 class="text-center"><?php echo $titulo;?></h2>
			
		</div>
	</body>	
</html>
