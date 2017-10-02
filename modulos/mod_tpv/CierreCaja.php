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
		// Saber que usuarios tienen ticket, key=idUsuario
		foreach ( $Users['usuarios'] as $key => $user){
			//print_r(' Usuario id'.$key. ' contiene:');
			//cojemos nombre del usuario, 
			$nombreUser=nombreUsuario($BDTpv,$key);
			$Users['usuarios'][$key]['nombre'] = $nombreUser['datos']['nombre'];
		}
		//~ echo '<pre>';
		//~ print_r($Users);
		//~ echo '</pre>';
	}
	?>
	
	<script>
	// Declaramos variables globales
	</script> 
    <!-- Cargamos fuciones de modulo.
    Cargamos JS del modulo de productos para no repetir funciones: BuscarProducto, metodoClick (pulsado, adonde)
    caja de busqueda en listado 
     -->
	<script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
    
    <!-- Cargamos libreria control de teclado -->
	<script src="<?php echo $HostNombre; ?>/lib/shortcut.js"></script>
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
				<div data-spy="affix" data-offset-top="505">
					<h4> Cierre Caja</h4>
					<h5> Opciones para una selección</h5>
					<ul class="nav nav-pills nav-stacked"> 
						<li><a href="#section2" >Aceptar</a></li>
						<li><a href="#section2" >Cancelar</a></li>
						<li><a href="#section2" >Fechas</a></li>
						<?php //	 <li><a href="#section2" onclick="metodoClick('VerProducto','ticket');";>Aceptar</a></li>
						?><?php		//metodoClick js case pulsado 
									//agregarUsuario nos lleva a formulario usuario
									//verUsuario si esta checkado nos lleva vista usuario de ese id
												//si NO nos indica que tenemos que elegir uno de la lista ?>
					</ul>
				</div>	
			</nav>
			<div class="col-md-10">
				<div class=" form-group">
					<form action="./CierreCaja.php" method="post"> <label class="control-label col-sm-2" > Fecha Caja:</label>
						<div class="col-sm-10"> 
							<input type="date" name="fecha" autofocus placeholder="2017-09-30" value=<?php echo (!isset($_POST['fecha']) ? $_POST['fecha'] : $_POST['fecha']); ?>>
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
							<tr>
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
								<td><?php echo $fPago; ?></td>
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
							echo '<tr><td>'.$nombre.'</td><td>'.$importe.'</td></tr>';
							$total += $importe;
						}
						echo '<tr><td><b>Total:</b></td><td>'.$total.'</td></tr>';
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
						$sumasIvasBases =	baseIva($BDTpv,$stringNumTicket,$iva);
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
						<tr>
							<td><b><?php echo 'Subtotal: ';?></b></td>
							<td><?php echo $sumaBase; ?></td>
							<td><?php echo $sumaIvas;  ?></td>
						</tr>
						<tr>
							<td></td>
							<td><b><?php echo 'Total: '; ?></b></td>
							<td><?php echo $sumaBase+$sumaIvas; ?></td>
						</tr>
						</table>
					</div>
				</div>
				<!-- Fin IVAS -->

				
			</div> 	
			</div>
			<!-- fin row -->
		</div>
	</div>
    </div>
		
</body>
</html>
