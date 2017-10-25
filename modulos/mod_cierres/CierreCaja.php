<!DOCTYPE html>
<html>
	<head>
	<?php
	include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	
	$dedonde='';
	$aviso='';
	$desactivarInput='';
	//recojo la fecha min de los tickets cobrados sin cerrar y la fecha maxima.
	$fechaMaxMin = fechaMaxMinTickets($BDTpv);
	
	//array de fecha, date_parse --> para atacar al dia o lo que queramos	['year'],['day'],['month']
	$ArrayFechaMin = date_parse(strftime('%d-%m-%Y',$fechaMaxMin['fechaMin']))['day'];
	$ArrayFechaMax = date_parse(strftime('%d-%m-%Y',$fechaMaxMin['fechaMax']))['day'];
	echo '<pre>';
		print_r($ArrayFechaMin);
	echo '</pre>';
	
	//si son fechas distintas es que se saltaron algun dia sin hacer cierre caja, toca avisar
	if ($ArrayFechaMin !== $ArrayFechaMax){
		$tipomensaje= "danger";
		$mensaje = "<strong>Varios dias sin cerrar caja.</strong> Hacer cierre de varios dias?";
		$aviso='aviso';
	} else{ //si las fechas son iguales, es que van dia a dia con el cierre.
		//desactivamos fechaFinal, porque no existe rango de fechas.
		$desactivarInput = "disabled";
		
	}
	$stringFechaInicio = strftime('%d-%m-%Y',$fechaMaxMin['fechaMin']); //indico fecha d-m-y
	$stringFechaFinal = strftime('%d-%m-%Y',$fechaMaxMin['fechaMax']);
	$fechaCierre = strftime('%d-%m-%Y ',time());
	//fecha para obtener caja de ese dia , fecha que escribimos en vista
	//Asi le damos formato a la fecha
	//date("d-m-Y H:i:s",strtotime($fechaCierre));
	
	if ($_POST['fecha']){
		$fechaCierre=$_POST['fecha'];
		if(isset($_POST['fechaFinal'])){
			$stringFechaFinal = $_POST['fechaFinal'];
		}
		
		
		//recogemos usuarios, numTicket inicial, final de cada usuario,y formasPago segun la fecha indicada		
		$Users = ticketsPorFechaUsuario($stringFechaInicio,$BDTpv,$stringFechaFinal);

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
	$classAlert = ' class= "" '; //inicializo la clase de fondo rojo para alertar distintos totales de Fpago y desgloseIvas
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


	
	
	echo '<pre>';
	print_r($Users['sql']);
	echo '</pre>';
	
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
	<script src="<?php echo $HostNombre; ?>/modulos/mod_cierres/funciones.js"></script>
    
	</head>
	<body>
	<?php
	include './../../header.php';
	//~ echo '<pre>';
		//~ print_r($Users);
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
					<table class="table table-striped" style="border:2px solid black; font-size:small">
					<thead>
						<tr>
							<th>ID Usuario</th>
							<th>Tickets abiertos</th>
							<th>Fecha inicio</th>
						</tr>
					</thead>
					<?php 
					foreach($Users['abiertos'] as $abierto){
					?>
					<tr style="border:4px solid red">
						<td><?php echo $abierto['idUsuario']; ?></td>
						<td><?php echo $abierto['suma']; ?></td>
						<td><?php echo $abierto['fechaInicio']; ?></td>
					</tr>
					<?php 
					}
					?>
					</table>
			<?php } //fin de tickets abiertos?>
			</nav>
			
			<div class="col-md-10">
				<div class=" form-group">
					<form action="./CierreCaja.php?dedonde=<?php echo $dedonde;?>" method="post"> 
						<label class="control-label col-sm-2" > Fecha Cierre Caja:</label>
						<div class="col-sm-4"> 
							<input type="date" name="fecha" pattern="([012][0-9]|3[01])-(0[1-9]|1[012])-([0-9]{4})" autofocus value=<?php  echo $fechaCierre; //cojo la fecha del actual del dia?> >
							<input type="submit" value="Consulta caja">  
						</div>
						<!-- inicio de fechas max y min -->
			
						<div class="col-sm-6">
							<div class="col-sm-4"> 
								<label>Fecha Inicial:</label>
								<input type="date" name="fechaInicial"  disabled autofocus value="<?php  echo $stringFechaInicio;?>" >
								
							</div>
							<div class="col-sm-4"> 
								<label>Fecha Final:</label>
								<input type="date" name="fechaFinal" <?php echo $desactivarInput; ?> pattern="([012][0-9]|3[01])-(0[1-9]|1[012])-([0-9]{4})" autofocus value="<?php  echo $stringFechaFinal;?>" > 
							</div>
						</div>
				<?php if ($aviso === 'aviso' ){   ?> 
						<div class="alert alert-<?php echo $tipomensaje; ?>"><?php echo $mensaje;?></div>
					<?php } ?>
					 <!-- fin de fechas max y min -->
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
						$totalFpago = '0.00';
						foreach ($suma as $nombre=>$importe){
							echo '<tr><td>'.$nombre.'</td><td>'.number_format($importe,2).'</td></tr>';
							$totalFpago += number_format($importe,2);
						}
						echo '<tr><td><b>Total:</b></td><td><b>'.number_format($totalFpago,2).'</b></td></tr>';
						
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
							$totalBasesIvas = number_format(($sumaBase+$sumaIvas),2);
						$i++;
						}//fin foreach 
						//desactivo boton de aceptar si HAY tickets abiertos O si los totales 
						if (isset($Users['abiertos']) ) {
							$estadoInput = 'disabled';
							
						} 
						
						if  (number_format($totalFpago,2) != number_format($totalBasesIvas,2)){
							$classAlert = ' class="danger" ';
							$estadoInput = 'disabled';
						}
						?>
						<tr class="info">
							<td><b><?php echo 'Subtotal: ';?></b></td>
							<td><?php echo number_format($sumaBase,2); ?></td>
							<td><?php echo number_format($sumaIvas,2);  ?></td>
						</tr>
						<tr <?php echo $classAlert; ?>>
							<td></td>
							<td><b><?php echo 'Total: '; ?></b></td>
							<td><b><?php echo $totalBasesIvas; ?></b></td>
						</tr>
						</table>
						

					</div>
				</div>
				<!-- Fin IVAS -->
			
				
			</div> 	
			</div>
			<!-- fin row -->
			<div style="text-align:right">
				<form method="post" name="Aceptar" action="<?php echo $rutaVolver;?>" >
					<input type="submit" name="Cancelar" value="Cancelar">
					<?php  					
					//montaje arrays cierre
					$Ccierre['tienda']= $_SESSION['tiendaTpv']['idTienda']; //recoger idTienda
					$Ccierre['sumasIvas']=$sumasIvasBases['items']; //iva, importeIva, importeBase
					
					$Ccierre['totalFpago']=$totalFpago;
					$Ccierre['sumaFpago']=$suma; //suma formas pago de todos los usuarios : contado, tarjeta
					$Ccierre['idUsuarioLogin'] = $_SESSION['usuarioTpv']['id'];
					$Ccierre['fechaInicio_tickets'] =$stringFechaInicio;
					$Ccierre['fechaFinal_tickets'] = $stringFechaFinal;
					$Ccierre['fechaCierre'] = $fechaCierre; 
					$Ccierre['fechaCreacion'] = date('Y-m-d H:i:s');
					
					
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
	echo '<pre>';
		print_r($Ccierre);
	echo '</pre>';
	?>
		</div>
	</div>
    </div>
		
</body>
</html>
