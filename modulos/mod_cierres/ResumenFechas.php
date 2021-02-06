<!DOCTYPE html>
<html>
    <head>
		<?php
        include_once './../../inicial.php';
        include $URLCom.'/head.php';
        include_once $URLCom.'/modulos/mod_cierres/funciones.php';
        include_once $URLCom.'/clases/iva.php';
        include_once $URLCom.'/modulos/mod_cierres/clases/ClaseCierres.php';
        $CCierres = new ClaseCierres;
        $Civas = new iva($BDTpv);
        //LLega mediente get las dos fechas  
		if (isset($_GET['fecha1'])& isset($_GET['fecha2'])) {
			$fecha1=$_GET['fecha1'];
			$fecha2=$_GET['fecha2'];
			$filtro=' FechaCierre between "'.$fecha1. '" AND "'.$fecha2.'"';
		}
		
		$total=0;
		$fecha_dmY = 'd-m-Y';
		//Obterer los cierres entre dos fechas
		$cierres =$CCierres->obtenerCierres($filtro);
		foreach ($cierres as $cierre){ 
			// almacenamos en una variable el total de los cierres seleccionados 
				$total=$total+$cierre['Total'];
		}
		//llamar a la función que devuelve los tipos de iva
		//$tablaIVA="iva";
		//$ivas=tiposIva($BDTpv, $tablaIVA);
		$ivas=$Civas->todoIvas();
		
		//llamar a la función que devuelve el total de las formas de pago utilizadas entre los cierres indicados
		$formasPago=cantMOdPago($BDTpv, $fecha1, $fecha2);
		?>
	</head>
	<body>
		
	<?php
        include_once $URLCom.'/modulos/mod_menu/menu.php';
		$rutaVolver = '../mod_cierres/ListaCierres.php';
		
	?>
	<div class="container">
		<div class="row">
			<nav class="col-sm-2" id="myScrollspy">
				<a class="text-ritght" href=<?php echo $rutaVolver;?>>Volver Atrás</a>
			</nav>
			<div class="col-md-8 text-center">
				<h1> Resumen cierres entre <?php echo date($fecha_dmY,strtotime($fecha1));?> - <?php echo date($fecha_dmY,strtotime($fecha2));?> </h1>
			</div>
			<div class="col-md-10" style="float:right">
				<div class="col-md-4">
						<h3> Resumen fechas: </h3>
			<table class="table table-striped">
				<thead>
					<tr>
						<th>FECHA CIERRE</th>
						<th>FECHA FINAL</th>		
						<th>TOTAL</th>					
					</tr>
				</thead>
				<tr>
				<td><?php echo date($fecha_dmY,strtotime($fecha1));?></td>
				<td><?php echo date($fecha_dmY,strtotime($fecha2));?></td>
				<td><?php echo $total;?></td>
				
				</tr>
			</table>
			</div>
					<div class="col-md-4">
						<h3> Resumen IVA  </h3>
			<table class="table table-striped">
				<thead>
					<tr>
						<th>TIPO</th>
						<th>BASE</th>
						<th>IVA</th>	
					</tr>
				</thead>
				<?php 
				foreach ($ivas  as $iva){
				
					//$consultaResIva=sumDatosIva($BDTpv, $iva['iva']);
						
					$consultaResIva=sumDatosIva($BDTpv, $iva['iva'],$filtro);
						
					if ($consultaResIva['base']){
						$importeBase=$consultaResIva['base'];
					
					if ($consultaResIva['iva']){
						$importeIva=$consultaResIva['iva'];
					}else{
						$importeIva=0;
					}
					?>
				<tr>
					<td><?php echo $iva['iva'].'%';?></td>
				<td><?php echo number_format($importeBase,2);?></td>
				<td ><?php echo number_format($importeIva, 2);?></td>
				
				</tr>
				<?php 
			}
			}
				?>
			</table>
			</div>
			</div>
				<div class="col-md-10" style="float:right">
				<div class="col-md-4">
						<h3> Resumen Usuarios: </h3>
			<table class="table table-striped">
				<thead>
					<tr>
						<th>NOMBRE</th>
						<th>TOTAL</th>					
					</tr>
				</thead>
				<?php 
					$usuarios=UsuariosCierre($BDTpv, $fecha1, $fecha2);
					foreach ($usuarios as $usu){
				$nombre=datosUsuario($BDTpv, $usu['idUsuario']);
				?>
				<tr>
				<td><?php echo $nombre['nombre'];?></td>
				<td><?php echo number_format($usu['importe'],2)?></td>
				</tr>
				<?php 
			}
				?>
			</table>
			</div>
					<div class="col-md-4">
						<h3> Formas de pago </h3>
			<table class="table table-striped">
				<thead>
					<tr>
						<th>TIPO</th>
						<th>CANTIDAD</th>
						<th>IMPORTE</th>
					</tr>
				</thead>
				<?php 
				foreach($formasPago as $forma){
				?>
				<tr>
				<td><?php echo $forma['FormasPago'];?></td>
				<td><?php echo $forma['total'];?></td>
				<td><?php echo number_format($forma['importe'],2);?></td>
				</tr>
				<?php 
			}
				?>
			</table>
			</div>
			</div>
				</div>  <!-- Fin row2 -->
			</div> <!--fin row-->
		</div><!--fin col-10 -->
	</div>	<!--fin container-->
	</body>
</html>
