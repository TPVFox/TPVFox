<!DOCTYPE html>
<html>
    <head>
		 <?php
		// Reinicio variables
        include './../../../head.php';
        //~ include './../funciones.php';
        include ("./../../../controllers/Controladores.php");
        include_once ($RutaServidor.$HostNombre.'/controllers/parametros.php');
        $ClasesParametros = new ClaseParametros('../parametros.xml');  
        $Controler = new ControladorComun; 
		$Controler->loadDbtpv($BDTpv);
		?>
			</head>
	<body>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_cliente/funciones.js"></script>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
		<?php
        include './../../../header.php';
		?>
		<div class="container">
			
			</div>
		<?php 
echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
?>

	</body>
</html>
