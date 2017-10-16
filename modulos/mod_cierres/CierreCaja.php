<!DOCTYPE html>
<html>
	<head>
	<?php
	include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	
	$dedonde='';
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
		//si existe de donde al cancelar volvemos a donde estabamos
		if (isset($_GET['dedonde'])){
			$dedonde = $_GET['dedonde'];
		
		}
	}
	$estadoInput =''; //inicializo variable para desactivar boton aceptar, si hay tickets abiertos

	//si existe de donde al cancelar volvemos a donde estabamos
	if (isset($_GET['dedonde'])){
		$dedonde = $_GET['dedonde'];
		//dedonde = tpv o cierre 
		if ($dedonde === 'tpv'){
			$rutaVolver = '../mod_tpv/tpv.php';
		} else { //doy por hecho que estoy en cierres y vuelvo al listado
			$rutaVolver = './ListaCierres.php';
		}
	}

	
	//array cierre
	$Ccierre= array();
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
			<a class="text-ritght" href=<?php echo $rutaVolver;?>>Volver Atrás</a>
			<?php 
			//si tengo tickets abiertos: muestro idUsuario y numTickets
		
			if (isset($Users['abiertos'])){?>
				<div class="alert alert-danger">
					<strong>Tickets Abiertos!</strong></br> No se permite cerrar caja si hay tickets abiertos.
				</div>
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
					<form action="./CierreCaja.php?dedonde=<?php echo $dedonde;?>" method="post"> <label class="control-label col-sm-2" > Fecha Caja:</label>
						<div class="col-sm-4"> 
							<input type="date" name="fecha" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[0-9]|1[0-9]|2[0-9]|3[01])" autofocus value=<?php echo date('Y-m-d'); //cojo la fecha del actual del dia?> >
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
			foreach ($Users['usuarios'] as $key =>$usuario){ 
			//~ $Cusuarios['NumInicUsuar'][$key]=$usuario['NumInicial'];
			//~ $Cusuarios['NumFinalUsuar'][$key]=$usuario['NumFinal'];
				?>
			<tr>
				<td><?php echo $key; ?></td>
				<td><?php echo $usuario['nombre'];  ?></td>
				<td><?php echo count($usuario['ticket']); ?></td>
				<td><?php echo $usuario['NumInicial']; ?></td>
				<td><?php echo $usuario['NumFinal']; ?></td>
			</tr>
			<?php
				$Ccierre['usuarios'][$key]['nombre']=$usuario['nombre'];
				$Ccierre['usuarios'][$key]['NumInicial']=$usuario['NumInicial'];
				$Ccierre['usuarios'][$key]['NumFinal']=$usuario['NumFinal'];
				
				}
				?>
			</table>
			<div class="row">
				<!-- FORMAS DE PAGO -->
				<div class="col-md-4">
					<h3 class="text-left"> Desglose Modo de Pago: </h3>
					<?php 
					foreach ($Users['usuarios'] as $key =>$usuario){ 
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
							//~ $Civas['sumasIvas']=$sumasIvasBases['items'];
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
				<form method="post" name="Aceptar" action="<?php echo $rutaVolver;?>" >
					<input type="submit" name="Cancelar" value="Cancelar">
					<?php  //desactivo boton de aceptar si HAY tickets abiertos 
					if (isset($Users['abiertos'])) {
						$estadoInput = 'disabled';
					} 
					
					//montaje arrays cierre
					$Ccierre['tienda']= $_SESSION['tiendaTpv']['idTienda']; //recoger idTienda
					$Ccierre['sumasIvas']=$sumasIvasBases['items']; //iva, importeIva, importeBase
					
					$Ccierre['totalFpago']=$total;
					$Ccierre['sumaFpago']=$suma; //suma formas pago de todos los usuarios : contado, tarjeta
					$Ccierre['idLogin'] = $_SESSION['usuarioTpv']['id'];
					
					//$Ccierre =  $Civas+$Cusuarios;
					
					?>
					<script type="application/javascript">
					var Ccierre = [];
					<?php //asi montamos array y lo convertimos en variable publica, en consola se coje
						echo "Ccierre.push(".json_encode($Ccierre).");";

					 ?>
					</script>

					<input id="Aceptar" type="button" <?php echo $estadoInput;?> onclick="guardarCierreCaja()" value="Aceptar"></input>
				
				</form>
			</div>
			<?php 
			} //fin de si existe post para mostrar botones
	echo '<pre>';
		print_r($Ccierre);
	echo '</pre>';
	?>
		</div>
	</div>
    </div>
		
</body>
</html>
