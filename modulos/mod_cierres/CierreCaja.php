<!DOCTYPE html>
<html>
	<head>
	<?php
    include_once './../../inicial.php';
    include $URLCom.'/head.php';
    include_once $URLCom.'/modulos/mod_cierres/funciones.php';
	include_once $URLCom.'/plugins/paginacion/paginacion.php';
	include_once $URLCom.'/controllers/Controladores.php';
	// Variables de control:
		$mensajes = array(); // Lo utilizo para los mensajes de advertencias,errores..
		$dedonde='';  // Indica de donde viene, ya que puede venir lista cierres y de tickets, por defecto viene de Lista Cierres
		$pattern = ' pattern="([012][0-9]|3[01])-(0[1-9]|1[012])-([0-9]{4})"';// Para control entrada input
		$estados = array(
					 'fechaFinal' 	=> '', // Input
					 'Aceptar' 		=> '', // Btn
					 'Recalcular' 	=> '', // Btn
					 'fecha'		=> '' // Input
					);
	
	// Varibles de fechas:
		$fechas = array();
		$MaxMin = fechaMaxMinTickets($BDTpv); 	//Obtenemos Fecha Max y Minima de tickets cobrados sin cerrar
												//Si no hay fecha, obtiene 01-01-1970
		
		$MaxMin['fechas']['Creacion'] = time(); // Fecha de creacion.
		$MaxMin['fechas']['Cierre'] = time(); // Por defecto , pongo la actual.
		
	//  ======   Comprobamos si venimos devuelta, es decir pulsamos Recalcular =================== 
			
			if (isset($_POST['fecha'])){
				// Cambiamos las fechas Cierres y Max al venir de vuelta, pulsamos Recalcular
				
				// Convertimos fecha a Epoch (Unix)
				$fechaNueva = $_POST['fecha'].' 23:59:59';
				$MaxMin['fechas']['Cierre']=strtotime($fechaNueva);

				// Convertimos fecha a Epoch (Unix)
				$fechaNueva = $_POST['fechaFinal'].' 23:59:59';
				$MaxMin['fechas']['fechaMax']= strtotime($fechaNueva);
				
			}

		// ====== Obtenemos array de fechas ===============================//.
		foreach ($MaxMin['fechas'] as $nombre => $Unix){
			$fecha = ArrayFechaUnix ($Unix,$nombre);
			$fechas = $fechas+$fecha;
		}
		
		
		// debug
		//~ echo '<pre>';
		//~ print_r($fechas);
		//~ echo '</pre>';
		// ========= Obtenemos tickets usuarios Cobrados y Abiertos ======================== //.
		
		$ResumenTicketsCierre = ticketsPorFechaUsuario($fechas['fechaMin']['String_d-m-y'],$BDTpv,$fechas['fechaMax']['String_d-m-y']);
		
		// Añadimos nombre usuarios a ResumenTicketsCierre
		foreach ($ResumenTicketsCierre['usuarios'] as $key => $user){
			$nombreUser=nombreUsuario($BDTpv,$key); 	//Obtenemos nombre del usuario, 
			$ResumenTicketsCierre['usuarios'][$key]['nombre'] = $nombreUser['datos']['nombre'];
		}
		
		// debug
		//~ echo '<pre>';
		//~ print_r($ResumenTicketsCierre);
		//~ echo '</pre>';
		
		
		// =================  COMPROBACIONES 	=============================== //
		
		// Comprobamos de donde venimos.
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
		
		// Comprobamos que haya tickets
		//si no existe fecha inicio de tickets
		if ($fechas['fechaMin']['String_d-m-y']  === '01-01-1970'){
			// Debemos deactivar todo, ya que no hay nada que hacer...
			// Añadimos mensaje
			$mensajes['danger'][] = "<strong>No hay tickets para cerrar.</strong>";
		}
		
		// Comprobamos que haya RANGO
		if ($fechas['fechaMin']['String_d-m-y'] !== $fechas['fechaMax']['String_d-m-y']){
			// Hay varios dias para hacer cierre, ya que fecha inicia y final son distintos dias
			// Añadimos mensaje
			$mensajes['warning'][] = "<strong>Varios dias sin cerrar caja.</strong> Hacer cierre de varios dias?";
			$estados['Aceptar'] = "disabled";
		}  else{ 
			//Es el mismo dia la fecha inicial de la final,
			$estados['fechaFinal'] = "disabled";
	
		}
		
		// Comprobamos que no haya ticket abiertos.
		if (count($ResumenTicketsCierre['tickets_abiertos']) >0 ) {
			// Añadimos mensaje
			$mensajes['danger'][] = "<strong>Hay tickets abiertos.</strong> Debes cobrarlos primero.";
		} 
		
	//  ======   Montamos Array con los datos que tenemos =================== 
		//monto string de numTickets para usar en funcion baseIva
		$sumasIvasBases =	baseIva($BDTpv,implode(',', $ResumenTicketsCierre['rangoTickets']));
	
		$Ccierre= array();
		$Ccierre['tienda']						= $_SESSION['tiendaTpv']['idTienda']; //recoger idTienda
		$Ccierre['idUsuarioLogin'] 				= $_SESSION['usuarioTpv']['id']; // Quien realiza el cierre
		$Ccierre['fechaInicio_tickets'] 		= $fechas['fechaMin']['String_d-m-y_hora'];
		$Ccierre['fechaFinal_tickets'] 			= $fechas['fechaMax']['String_d-m-y_hora'];
		$Ccierre['FinicioSINhora']				= $fechas['fechaMin']['String_d-m-y'];
		$Ccierre['FfinalSINhora']				= $fechas['fechaMax']['String_d-m-y'];
		$Ccierre['fechaCierre']					= $fechas['Cierre']['String_d-m-y'];
		$Ccierre['fechaCreacion'] 				= $fechas['Creacion']['String_d-m-y_hora'];
		$Ccierre['sumasIvas']					= $sumasIvasBases['items']; //iva, importeIva, importeBase
		$Ccierre['totalcaja']	 				= $ResumenTicketsCierre['totalcaja'];
		$Ccierre['rangoTickets']				= $ResumenTicketsCierre['rangoTickets'];
		
		foreach ($ResumenTicketsCierre['usuarios'] as $keyId => $usuario){
			$Ccierre['usuarios'][$keyId]['nombre']		=$usuario['nombre'];
			$Ccierre['usuarios'][$keyId]['NumInicial']	=$usuario['NumInicial'];
			$Ccierre['usuarios'][$keyId]['NumFinal']	=$usuario['NumFinal'];
			$Ccierre['usuarios'][$keyId]['subtotal']	= $usuario['total'];
			
			foreach ($usuario['formasPago'] as $key =>$fPago){ 
				$Ccierre['modoPago'][$keyId]['formasPago'][$key]=number_format($fPago,2); 
			}
		}
		if (isset($_POST['fecha'])){
				// Al venir de vuelta , pulsamos Recalcular
				// mostramos ya el btn de Aceptar
				$estados['Aceptar'] = '';
		}
		
		if (isset($mensajes['danger'])){
			// Quiere decir que hay error grave que no se puede hacer cierra.
			$estados = array(
					 'fechaFinal' 		=> 'disabled', // Input
					 'Aceptar' 			=> 'disabled', // Btn
					 'Recalcular' 	=> 'disabled', // Btn
					 'fecha'			=> 'disabled'  // Input
					);
		}
		
		//debug
		//~ echo '<pre>';
		//~ print_r($mensajes);
		//~ echo '</pre>';
		//~ echo json_encode($Ccierre,true);
	?>
	<script type="application/javascript">
	// var Ccierre = {};
	<?php //asi montamos array y lo convertimos en variable publica, en consola se coje
		echo "var Ccierre=".json_encode($Ccierre,true).";";

	 ?>
	</script>

    <!-- Cargamos fuciones de modulo.
    Cargamos JS del modulo de productos para no repetir funciones: BuscarProducto, metodoClick (pulsado, adonde)
    caja de busqueda en listado 
     -->
	<script src="<?php echo $HostNombre; ?>/modulos/mod_cierres/funciones.js"></script>
    
	</head>
	<body>
	<?php
	include_once $URLCom.'/modulos/mod_menu/menu.php';
	?>
<div class="container">		
	<div class="row">
		<div class="col-sm-2" style="padding:3em 10px;">
				
			<a class="text-ritght" href=<?php echo $rutaVolver;?>>Volver Atrás</a>
			<?php 
			//si tengo tickets abiertos: muestro idUsuario y numTickets
			if (count($ResumenTicketsCierre['tickets_abiertos'])>0)
			{ ?>
			<h2>Tickets Abiertos</h2>
			<table class="table table-striped" style="border:2px solid black; font-size:small">
				<thead>
					<tr>
						<th>ID Usuario</th>
						<th>Usuario</th>
						<th>Cantidad tickets</th>
					</tr>
				</thead>
				<?php 
				foreach($ResumenTicketsCierre['tickets_abiertos'] as $abierto){
				?>
				<tr style="border:4px solid red">
					<td><?php echo $abierto['idUsuario']; ?></td>
					<td><?php echo $abierto['username']; ?></td>
					<td><?php echo $abierto['suma']; ?></td>
				</tr>
				<?php 
				} //fin foreach
				?>
			</table>
			
			<?php 
			} //fin de tickets abiertos?>
		</div>
		<div class="col-md-10">
			<h2 class="text-center"> Cierre Caja </h2>
			<?php 
			if (count($mensajes)>0){  	 
				foreach($mensajes as $tipo => $mensaje){
					echo '<div class="alert alert-'.$tipo.'">';
					echo implode('<br/>',$mensaje);
					echo '</div>';
				}
				
			 } 
			 ?>
			<form action="./CierreCaja.php?dedonde=<?php echo $dedonde;?>" method="post"> 
				<div class="col-md-3 ">	
					<label class="control-label " > Fecha Cierre Caja:</label>
					<input type="date" name="fecha" <?php echo $estados['fecha'].$pattern;?> value=<?php  echo $fechas['Cierre']['String_d-m-y']; //cojo la fecha del actual del dia?> >
				</div>
				<!-- inicio de fechas max y min -->			
				<div class="col-md-3"> 
					<label>Fecha Inicial:</label>
					<input type="date" name="fechaInicial"  disabled value="<?php  echo $fechas['fechaMin']['String_d-m-y'];?>" >								
				</div>
				<div class="col-md-3"> 
					<label>Fecha Final:</label>
					<input type="date" name="fechaFinal" <?php echo $estados['fechaFinal'].$pattern;?> value="<?php  echo $fechas['fechaMax']['String_d-m-y'];?>" > 
				</div>
				<div class="col-md-3">
					<label>(*) Si cambiaste algún dato.</label>
					<input class="btn btn-primary" name="Recalcular" <?php echo $estados['Recalcular'];?> type="submit" value="Recalcular">  
				</div>
			</form>	
			<div class="col-md-12">
				<!-- TABLA USUARIOS -->
				<h3 class="text-center"> Cierre por Usuarios </h3>
				<table class="table table-striped">
					<thead>
						<tr>
							<th>ID</th>
							<th>NOMBRE USUARIO</th>
							<th>Nº TICKET INICIAL</th>
							<th>Nº TICKET FINAL</th>
							<th>CANT. TICKETS</th>
							<th>SUBTOTAL USUARIO</th>
						</tr>
					</thead>
					<?php 
					foreach ($ResumenTicketsCierre['usuarios'] as $key =>$usuario){ ?>
					<tr>
						<td><?php echo $key; ?></td>
						<td><?php echo $usuario['nombre'];  ?></td>
						<td><?php echo $usuario['NumInicial']; ?></td>
						<td><?php echo $usuario['NumFinal']; ?></td>
						<td><?php echo count($usuario['ticket']); ?></td>
						<th><?php echo number_format($usuario['total'],2);?><small>€</small></th>
					</tr>
					<?php
					}
					?>
				</table>
			</div>
			<!-- FORMAS DE PAGO -->
			<div class="col-md-4">
				<h3 class="text-left"> Formas de Pago por Usuarios: </h3>
				<?php 
				foreach ($ResumenTicketsCierre['usuarios'] as $keyUsuario =>$usuario){ 
					$Ccierre['modoPago'][$keyUsuario]['nombre']=$usuario['nombre'];
					?>
					<table class="table table-striped">
						<thead>
							<th>Nombre Empleado</th>
							<th>Forma de Pago</th>	
							<th>Importe</th>
						</thead>						
						<tbody>
						<tr><td rowspan="0"><?php echo $usuario['nombre'];?></td></tr>
						<?php //key id forma de pago, tarjeta o contado						
						foreach ($usuario['formasPago'] as $key =>$fPago){ ?>
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
						</tbody>
					</table>						
				<?php
				} //fin foreach Usuarios					
				?>
			</div>
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
				$totalFpago = '0.00';
				foreach ($suma as $nombre=>$importe){
					echo '<tr><td>'.$nombre.'</td><td>'.number_format($importe,2).'</td></tr>';
					$totalFpago += number_format($importe,2);
				}
					echo '<tr><td><b>Total:</b></td><td><b>'.number_format($totalFpago,2).'</b></td></tr>'; ?>
				</table>
			</div> 
				<!-- IVAS -->
			<div class="col-md-4">
				<h3> Desglose de Ivas: </h3>
				<div class="form-group">
				<?php 				 
					
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
						foreach($sumasIvasBases['items'] as $sumaBaseIva){	?>
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
				</div> <!-- Fin form-group  -->
			</div> <!-- Fin col-4  --> 
			<div class="col-md-12" style="text-align:right">
				<form method="post" name="Aceptar" action="<?php echo $rutaVolver;?>" >
					<input type="submit" name="Cancelar" value="Cancelar">
					<input id="Aceptar" name = "Aceptar" type="button" <?php echo $estados['Aceptar']?> onclick="guardarCierreCaja()" value="Aceptar">
				
				</form>
			</div>
		</div>
    </div>
</div>		
</body>
</html>
