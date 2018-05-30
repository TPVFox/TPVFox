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
		$errores=array();
		if(isset($_GET['fechaIni']) & isset($_GET['fechaFin'])){
			$fechaIni=$_GET['fechaIni'];
			$fechaFin=$_GET['fechaFin'];
			$idCliente=$_GET['idCliente'];
			echo $fechaIni;
			echo $fechaFin;
		}else{
			$errores[1]=array ( 'tipo'=>'DANGER!',
								 'dato' => '',
								 'class'=>'alert alert-danger',
								 'mensaje' => 'Error no se han enviado corectamente las fechas'
								 );
		}
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
