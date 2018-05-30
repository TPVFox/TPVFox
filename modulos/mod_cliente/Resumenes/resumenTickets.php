<!DOCTYPE html>
<html>
    <head>
		 <?php
		// Reinicio variables
        include './../../../head.php';
         include './../funciones.php';
        include ("./../../../controllers/Controladores.php");
        include_once ($RutaServidor.$HostNombre.'/controllers/parametros.php');
        $ClasesParametros = new ClaseParametros('../parametros.xml');  
        include '../clases/ClaseCliente.php';
		$Cliente= new ClaseCliente($BDTpv);
        $Controler = new ControladorComun; 
		$Controler->loadDbtpv($BDTpv);
		$errores=array();
		$titulo="";
		if(isset($_GET['fechaIni']) & isset($_GET['fechaFin'])){
			$fechaIni=$_GET['fechaIni'];
			$fechaFin=$_GET['fechaFin'];
			$idCliente=$_GET['idCliente'];
			$titulo='Tickets del cliente '.$idCliente .' entre '.$fechaIni.' y '.$fechaFin;
			$arrayNums=$Cliente->ticketClienteFechas($idCliente, $fechaIni, $fechaFin);
			echo '<pre>';
			print_r($arrayNums);
			echo '</pre>';
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
			<div class="col-md-12">
				<h2><?php echo $titulo?></h2>
				<div  class="col-md-12">
					<table class="col-md-12">
						<thead>
							<tr>
								<td>Producto</td>
								<td>Cantidad</td>
								<td>Importe</td>
							</tr>
						</thead>
						<tbody>
						<?php 
						
						foreach($arrayNums['productos'] as $producto){
							echo '<tr>';
							echo '<td>'.$producto['cdetalle'].'</td>';
							echo '<td>'.$producto['nunidades'].'</td>';
							echo '<td>'.$producto['precioCiva'].'</td>';
							echo '</tr>';
						}
						?>
						</tbody>
					</table>
					<table class="col-md-12">
						<thead>
							<tr>
								<td>Fecha</td>
								<td>Ticket</td>
								<td>Base</td>
								<td>IVA</td>
								<td>Total</td>
							</tr>
						</thead>
					</table>
					<table class="col-md-12">
						<thead>
							<tr>
								<td>Base</td>
								<td>IVA</td>
								<td>Total</td>
							</tr>
						</thead>
					</table>
				
				</div>
			</div>
		</div>
		<?php 
		echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
		include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
		?>

	</body>
</html>
