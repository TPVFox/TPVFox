<!DOCTYPE html>
<html>
    <head>
		<?php
        include_once './../../inicial.php';
        include $URLCom.'/head.php';
        include_once $URLCom.'/modulos/mod_cierres/funciones.php';
		if (isset($_GET['id'])) {
			$idCierre=$_GET['id'];
			
		}
		$cierreUnico = obtenerCierreUnico($BDTpv,$idCierre);
		$fecha_dmY = 'd-m-Y';
		?>
	</head>
	
	<body>
		
	<?php
         include_once $URLCom.'/modulos/mod_menu/menu.php';
		// debug
		//~ echo '<pre>';
			//~ print_r($cierreUnico);
		//~ echo '</pre>';
		
		$rutaVolver = '../mod_cierres/ListaCierres.php';
		
	?>
	<div class="container">
		<div class="row">
			<nav class="col-sm-2" id="myScrollspy">
				<a class="text-ritght" href=<?php echo $rutaVolver;?>>Volver Atrás</a>
			</nav>
			<!-- CIERRE POR USUARIOS-->
			<div class="col-md-8 text-center">
				<h1> Cierre de caja <?php echo $idCierre; ?></h1>
			</div>
			<div class="col-md-10" style="float:right">
			<table class="table table-striped">
				<thead>
					<tr>
						<th>FECHA CIERRE</th>
						<th>ID TIENDA</th>
						<th>ID USUARIO</th>
						<th>FECHA INICIO</th>
						<th>FECHA FINAL</th>						
						<th>FECHA CREACION</th>
						<th>TOTAL</th>					
					</tr>
				</thead>
				<?php 
				foreach ($cierreUnico['cierres'] as $cierre){ 
				?>
				<tr>
					<td><?php echo date($fecha_dmY,strtotime($cierre['FechaCierre'])); ?></td>
					<td><?php echo $cierre['idTienda'];  ?></td>
					<td><?php echo $cierre['idUsuario']; ?></td>
					<td><?php echo date($fecha_dmY, strtotime($cierre['FechaInicio'])); ?></td>
					<td><?php echo date($fecha_dmY, strtotime($cierre['FechaFinal'])); ?></td>
					<td><?php echo date($fecha_dmY, strtotime($cierre['FechaCreacion'])); ?></td>
					<td><?php echo number_format($cierre['Total'],2); ?></td>
				</tr>
				<?php
				}
				?>
			</table>
			</div>
			<!-- CIERRE POR USUARIOS-->
			<div class="col-md-8 text-center">
				<h3> Cierre por Usuarios </h3>
			</div>	
			<div class="col-md-10" style="float:right">
			<table class="table table-striped">
				<thead>
					<tr>
						<th>ID</th>
						<th>NOMBRE USUARIO</th>
						<th>Nº TICKET INICIAL</th>
						<th>Nº TICKET FINAL</th>
						<th>Listado Tickets</th>
					</tr>
				</thead>
				<?php 
				foreach ($cierreUnico['usuario'] as $key =>$usuario){ 
				?>
				<tr>
					<td><?php echo $usuario['idUsuario']; ?></td>
					<td><?php echo $usuario['nombreUsuario'];  ?></td>
					<td><?php echo $usuario['Num_ticket_inicial']; ?></td>
					<td><?php echo $usuario['Num_ticket_final']; ?></td>
					<?php $linkTickets= 'ListaTickets.php?estado=Cerrado&idUsuario='.$usuario['idUsuario'].'&idCierre='.$idCierre;?>
					<td><a class="text-ritght" href="<?php echo $linkTickets ;?>"><span class="glyphicon glyphicon-list-alt"></span></a></td>
				</tr>
				<?php
				}
				?>
			</table>
			</div>
			<div class="col-md-10" style="float:right">
				<!-- formas pago-->
				<div class="row" >
					<!-- FORMAS DE PAGO -->
					<div class="col-md-4">
						<h3 class="text-left"> Formas de Pago por Usuarios: </h3>
						<?php 
						foreach ($cierreUnico['fpago'] as $keyFpago =>$usuario){
							//$idUsu = $usuario[$idUsuario]['idUsuario'];
						?>
						<table class="table table-striped">
							<thead>
								<tr class="info">
									<td><b><?php echo 'Nombre Empleado: ';?></b></td>
									<td><?php echo $usuario['nombre'];?></td>
								</tr>
								<tr>
									<th>Forma de Pago</th>	
									<th>Importe</th>
								</tr>
							</thead>
							<?php 
							foreach ($usuario['formas'] as $nombreFpago=>$importe){
							?>
							<tr>
								<td><?php echo $nombreFpago; ?></td>
								<td><?php echo number_format($importe,2); ?></td>
							</tr>
							<?php 
								if (!isset($suma[$nombreFpago])){
									$suma[$nombreFpago] = $importe;
								} else {
									$suma[$nombreFpago] += $importe;
								}
							} //foreach distintas fpago
							?>
						</table>						
						<?php						
						} //fin foreach Usuarios						
						?>
					</div> <!-- fin col 4 -->
					<div class="col-md-4">
						<!--Todos los usuarios total de tarjetas y contado-->
						<h3 class="text-left"> Totales: </h3>
						<table class="table table-striped">
							<thead>
								<tr>
									<td><?php echo 'Forma de Pago ';?></td>
									<td><?php echo 'Importe';?></td>
								</tr>
							</thead>
							<?php
							$totalFpago = 0;
							foreach ($suma as $nombre=>$importe){
                                //~ $importe = str_replace(",","",$importe);
                                echo '<tr><td>'.$nombre.'</td><td>'.number_format($importe,2,',','').'</td></tr>';
								$totalFpago += $importe;
							}
							echo '<tr><td><b>Total:</b></td><td><b>'.number_format($totalFpago,2,',','').'</b></td></tr>';
							?>
						</table>
					</div>   <!-- fin col 4  2º-->				
					<!-- IVAS -->
					<div class="col-md-4">
						<h3> Desglose de Ivas: </h3>
						<div class="form-group">
							<table class="table table-striped">
								<thead>
									<tr>
										<th></th>
										<th>Importe BASE</th>	
										<th>Importe IVA</th>
									</tr>
								</thead>
								<?php 
								//recorro lo obtenido en  		
								foreach($cierreUnico['ivas'][$idCierre] as $iva){
								?>
								<tr>
									<td><?php echo $iva['tipo_iva'].' %:';?></td>
									<td><?php echo number_format($iva['importe_base'],2);?></td>
									<td><?php echo number_format($iva['importe_iva'],2);?></td>
								</tr>
								<?php 
								if (!isset($sumaBase) || (!isset($sumaIvas))){
									$sumaBase = $iva['importe_base'];
									$sumaIvas = $iva['importe_iva'];
								} else {
									$sumaBase += $iva['importe_base'];
									$sumaIvas += $iva['importe_iva'];
								}
								$totalBasesIvas = number_format(($sumaBase+$sumaIvas),2);						
								}//fin foreach 
								?>
								<tr class="info">
									<td><b><?php echo 'Subtotal: ';?></b></td>
									<td><?php echo number_format($sumaBase,2); ?></td>
									<td><?php echo number_format($sumaIvas,2);  ?></td>
								</tr>
								<tr>
									<td></td>
									<td><b><?php echo 'Total: '; ?></b></td>
									<td><b><?php echo $totalBasesIvas; ?></b></td>
								</tr>
							</table>
						</div> <!-- Fin form-group -->
					</div> <!-- Fin col 4 3º -->
					<!-- Fin IVAS -->
				</div>  <!-- Fin row2 -->
			</div> <!--fin row-->
		</div><!--fin col-10 -->
	</div>	<!--fin container-->
	</body>
</html>
