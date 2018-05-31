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
			
			//~ echo '<pre>';
			//~ print_r($arrayNums);
			//~ echo '</pre>';
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
			<div class="col-md-12 text-center" >
				<h2><?php echo $titulo?></h2>
			</div>
			
			<div class="col-md-8 " >
					<table class="table table-striped table-bordered">
						<thead>
							<tr>
								<th>PRODUCTO</th>
								<th>CANTIDAD</th>
								<th>PRECIO</th>
								<th>IMPORTE</th>
							</tr>
						</thead>
						<tbody>
						<?php 
						$totalProductos=0;
						foreach($arrayNums['productos'] as $producto){
							$precio=$producto['totalUnidades']*$producto['precioCiva'];
							echo '<tr>'
							. '<td>'.$producto['cdetalle'].'</td>'
							.'<td>'. number_format ($producto['totalUnidades'],2).'</td>'
							.'<td>'.number_format ($producto['precioCiva'],2).'</td>'
							. '<td>'.number_format ($precio,2).'</td>'
							. '</tr>';
							$totalProductos=$totalProductos+number_format ($precio,2);
						}
						?>
						</tbody>
					</table>
					<div class="col-md-12">
						<div class="col-md-9">
						</div>
						<div class="col-md-3">
							<div class="panel panel-success">
								<div class="panel-heading">
									<h3 class="panel-title">TOTAL: <?php echo $totalProductos;?></h3>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-md-4 " >
					<table class="table table-striped table-bordered">
						<thead>
							<tr>
								<th>FECHA</th>
								<th>TICKET</th>
								<th>BASE</th>
								<th>IVA</th>
								<th>TOTAL</th>
							</tr>
						</thead>
						<tbody>
						<?php 
						$totalLinea=0;
						$totalbases=0;
							foreach($arrayNums['resumenBases'] as $bases){
								$totalLinea=$bases['sumabase']+$bases['sumarIva'];
								$totalbases=$totalbases+$totalLinea;
								echo '<tr>
								<td>'.$bases['fecha'].'</td>
								<td></td>
								<td>'.$bases['sumabase'].'</td>
								<td>'.$bases['sumarIva'].'</td>
								<td>'.$totalLinea.'</td>
								</tr>';
							}
						?>
						
						</tbody>
					</table>
					<div class="col-md-12">
						<div class="col-md-5">
						</div>
						<div class="col-md-7">
							<div class="panel panel-success">
								<div class="panel-heading">
									<h3 class="panel-title">TOTAL: <?php echo $totalbases;?></h3>
								</div>
							</div>
						</div>
					</div>
					<table class="table table-striped table-bordered">
						<thead>
							<tr>
								<th></th>
								<th>BASE</th>
								<th>IVA</th>
								<th>TOTAL</th>
							</tr>
						</thead>
						<tbody>
						<?php 
						$totalLinea=0;
						$totalDesglose=0;
						foreach($arrayNums['desglose'] as $desglose){
							$totalLinea=$desglose['sumBase']+$desglose['sumiva'];
							$totalDesglose=$totalDesglose+$totalLinea;
							echo '<tr>
								<td>'.$desglose['iva'].'%</td>
								<td>'.$desglose['sumBase'].'</td>
								<td>'.$desglose['sumiva'].'</td>
								<td>'.$totalLinea.'</td>
							</tr>';
						}
						
						?>
						</tbody>
					</table>
					<div class="col-md-12">
						<div class="col-md-5">
						</div>
						<div class="col-md-7">
							<div class="panel panel-success">
								<div class="panel-heading">
									<h3 class="panel-title">TOTAL: <?php echo $totalbases;?></h3>
								</div>
							</div>
						</div>
					</div>
				
				</div>
			</div>
		</div>
		<?php 
		echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
		include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
		?>

	</body>
</html>
