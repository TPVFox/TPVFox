<!DOCTYPE html>
<html>
	<head>
	<?php
	include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	
	//fecha para obtener caja de ese dia , fecha que escribimos en vista
	if ($_POST['fecha']){
		$fecha=$_POST['fecha'];
		//nuevafecha la calculamos, un dia mas de la fecha escrita
		$nuevafecha = strtotime ( '+1 day' , strtotime ( $fecha ) ) ;
		$nuevafecha = date ( 'Y-m-j' , $nuevafecha );
		//recogemos usuarios, numTicket inicial, final de cada usuario,y formasPago segun la fecha indicada
		$Users = ticketsPorFechaUsuario($fecha,$BDTpv,$nuevafecha);
		echo '<pre>';
		print_r($Users);
		echo '</pre>';
		// Saber que usuarios tienen ticket, key=idUsuario
		foreach ( $Users['usuarios'] as $key => $user){
			//print_r(' Usuario id'.$key. ' contiene:');
			//cojemos nombre del usuario, 
			$nombreUser=nombreUsuario($BDTpv,$key);
			$Users['usuarios'][$key]['nombre'] = $nombreUser['datos']['nombre'];
		}
		echo '<pre>';
		print_r($Users['abiertos']);
		echo '</pre>';
	}
	?>
	
	<script>
	// Declaramos variables globales
	</script> 
    <!-- Cargamos fuciones de modulo.
    Cargamos JS del modulo de productos para no repetir funciones: BuscarProducto, metodoClick (pulsado, adonde)
    caja de busqueda en listado 
     -->
	<script src="<?php echo $HostNombre; ?>/modulos/mod_tpv/funciones.js"></script>
    
	</head>
	<body>
	<?php
	include './../../header.php';
	//~ echo '<pre>';
		//~ print_r($Users['rangoTickets']);
	//~ echo '</pre>';
	?>
	<div class="container">		
		<div class="row">
			<div class="col-md-12 text-center">
				<h2> Cierre Caja </h2>
			</div>
	        <!--=================  Sidebar -- Menu y filtro =============== 
				Efecto de que permanezca fixo con Scroll , el problema es en
				movil
			-->
			<nav class="col-sm-2" id="myScrollspy">
			<a class="text-ritght" href="./tpv.php">Volver Atrás</a>
			<?php //si tengo tickets abiertos: muestro idUsuario y numTickets
			if (isset($Users['abiertos'])){?>
					<table class="table table-striped" style="border:2px solid black">
					<thead>
						<tr>
							<th>ID Usuario</th>
							<th>Tickets abiertos</th>
						</tr>
					</thead>
					<?php 
					foreach($Users['abiertos'] as $abierto){
					?>
					<tr style="border:4px solid red">
						<td><?php echo $abierto['idUsuario']; ?></td>
						<td><?php echo $abierto['suma']; ?></td>
					</tr>
					<?php 
					}
					?>
					</table>
			<?php } //fin de tickets abiertos?>
			</nav>
			<div class="col-md-10">
				<div class=" form-group">
					<form action="./CierreCaja.php" method="post"> <label class="control-label col-sm-2" > Fecha Caja:</label>
						<div class="col-sm-4"> 
							<input type="date" name="fecha" autofocus value=<?php echo date('Y-m-j'); //cojo la fecha del actual del dia?> >
							<input type="submit" value="Consulta caja">
						</div>
					</form>			
				</div>				
			<div>
									
				<!-- TABLA USUARIOS -->
			<div class="col-md-8 text-center">
				<h3> Usuario por Usuario </h3>
			</div>
			<table class="table table-striped">
			<thead>
				<tr>
					<th>ID</th>
					<th>NOMBRE USUARIO</th>
					<th>SUMA TICKETS</th>
					<th>Nº TICKET INICIAL</th>
					<th>Nº TICKET FINAL</th>
				</tr>
			</thead>
			<?php 
			foreach ($Users['usuarios'] as $key =>$usuario){ ?>
			<tr>
				<td><?php echo $key; ?></td>
				<td><?php echo $usuario['nombre']; ?></td>
				<td><?php echo count($usuario['ticket']); ?></td>
				<td><?php echo $usuario['NumInicial']; ?></td>
				<td><?php echo $usuario['NumFinal']; ?></td>
			</tr>
			<?php
				}
				?>
			</table>
			<div class="row">
				<!-- FORMAS DE PAGO -->
				<div class="col-md-4">
					<h3 class="text-left"> Desglose Modo de Pago: </h3>
					<?php 
					$suma = array();
					foreach ($Users['usuarios'] as $key =>$usuario){ ?>
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
						
						<?php //key id forma de pago, tarjeta o contado
						
						foreach ($usuario['formasPago'] as $key =>$fPago){ 	?>
							<tr>
								<td><?php echo $key; ?></td>
								<td><?php echo number_format($fPago,2); ?></td>
							</tr>
							<?php 	
						
							if (!isset($suma[$key])){
								$suma[$key] = $fPago;
							} else {
								$suma[$key] += $fPago;
							}
						} //fin foreach formasPago	
						?>
						</table>
						
					<?php
					} //fin foreach Usuarios
					?>
				</div>
				<div class="col-md-4">
					<!--Todos los usuarios total de tarjetas y contado-->
					<h3 class="text-left"> Todos los usuarios: </h3>
					<table class="table table-striped">
						<thead>
							<tr>
								<td><?php echo 'Forma de Pago ';?></td>
								<td><?php echo 'Importe';?></td>
							</tr>
						</thead>
						<?php
						$total = 0;
						foreach ($suma as $nombre=>$importe){
							echo '<tr><td>'.$nombre.'</td><td>'.number_format($importe,2).'</td></tr>';
							$total += $importe;
						}
						echo '<tr><td><b>Total:</b></td><td><b>'.number_format($total,2).'</b></td></tr>';
					
						?>
						</table>
				</div> 
					<!-- IVAS -->
				<div class="col-md-4">
					<h3> Desglose de Ivas: </h3>
					<div class="form-group">
					<?php 				 
						//monto string de numTickets para usar en funcion baseIva
						$stringNumTicket = implode(',', $Users['rangoTickets']);
						$sumasIvasBases =	baseIva($BDTpv,$stringNumTicket);
						//TABLA DE BASES E IVAS
						?>	
						<table class="table table-striped">
						<thead>
							<tr>
								<th></th>
								<th>Importe BASE</th>	
								<th>Importe IVA</th>
							</tr>
						</thead>
						<?php 
						//recorro lo obtenido en sumasIvasBases 
						$i=0;
						foreach($sumasIvasBases['items'] as $sumaBaseIva){
						?>
						<tr>
							<td><?php echo $sumaBaseIva['iva'].' %:';?></td>
							<td><?php echo $sumaBaseIva['importeBase'];?></td>
							<td><?php echo $sumaBaseIva['importeIva'];?></td>
						</tr>
						<?php //si no existe sumaBase o sumaIva, la creo y luego voy sumando importes encontrados
							if (!isset($sumaBase) || (!isset($sumaIvas))){
								$sumaBase = $sumasIvasBases['items'][$i]['importeBase'];
								$sumaIvas = $sumasIvasBases['items'][$i]['importeIva'];
							} else {
								$sumaBase += $sumasIvasBases['items'][$i]['importeBase'];
								$sumaIvas += $sumasIvasBases['items'][$i]['importeIva'];
							}
						$i++;
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
							<td><b><?php echo number_format($sumaBase+$sumaIvas,2); ?></b></td>
						</tr>
						</table>
						

					</div>
				</div>
				<!-- Fin IVAS -->
			
				
			</div> 	
			</div>
			<!-- fin row -->
			<!--Solo mostrar si hay datos 
			si existe post fecha y tiene datos se muestra-->
			<?php if ((isset($_POST['fecha'])) AND (($_POST['fecha']) !== '')){ ?>
			<div style="text-align:right">
				<form method="post" name="Aceptar" action="./CierreCaja.php">
					<input type="submit" name="Cancelar" value="Cancelar">
					
					<button id="Aceptar" type="button" onclick="guardarCierreCaja()">Aceptar</button>
				</form>
			</div>
			<?php 
			} //fin de si existe post para mostrar botones?>
		</div>
	</div>
    </div>
		
</body>
</html>
