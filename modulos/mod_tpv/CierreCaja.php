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
		
		
		
		
		//~ $efectivo = formaPago('Contado',$BDTpv,$fecha,$nuevafecha); //mysql contado o tarjeta
		//~ $tarjeta = formaPago('tarjeta',$BDTpv,$fecha,$nuevafecha);
	}
	
	//recoger datos... variables del html
	
	
	
	//$idUsuario = $datos_tickets['idUsuario'];
	//~ $usuar=nombreUsuario($BDTpv,$idUsuario);
	//~ $nombreUsuario = $usuar['datos']['username'];
	//~ echo '<pre>';
	//~ print_r($tickets);
	//~ echo '</pre>';
	
	//rango numticket en dicha fecha, ticket inicial el array[0], ticket final count[3] restar 1 porque empieza en 0.
	//~ $arrayNumTickets= $datos_tickets['rangoTickets'];
	
	//~ $numTicketInicial= $arrayNumTickets[0]; 
	//~ $numTicketFinal = $arrayNumTickets[count($arrayNumTickets)-1]; 
	//~ $totalTickets = $numTicketFinal - $numTicketInicial+1; 

	//
		//recojo sumaIva, sumaBase de cada iva	
		//$datos ['iva'];
		//$datos ['base'];
	

	//~ echo '<pre>';
	//~ print_r($datos_tickets);
	//~ echo '</pre>';
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
        ?>
        <?php
				//~ echo '<pre>';
					//~ print_r($tickets);
				//~ echo '</pre>';
		?>
		
	<?php 
			echo '<pre>';
				print_r($Users['rangoTickets']);
			echo '</pre>';	
			
			
			
			//hacer array IVAS, recorrerlos segun haya ver el resultado
			$ivas = ivas($BDTpv);
			echo '<pre>';
				print_r($ivas);
			echo '</pre>';	
			
		?>
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Total Caja </h2>
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
			<div class="col-md-10 text-center">
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
				<!-- Cobrado por -->
				<div class="col-md-4">
					<h3 class="text-left"> Cobrado por: </h3>
					<?php 
				$totalFormaPago = array();
				foreach ($Users['usuarios'] as $key =>$usuario){ 
				?> <b>Nombre usuario: </b><?php	echo ' '.$usuario['nombre']; ?>
					<div class="form-group">	
							<?php   //el id es tarjeta o contado
					foreach ($usuario['formasPago'] as $key =>$fPago){ 
									?>					
						<label class="control-label col-sm-2" ><?php echo $key; ?></label>
						<div class="col-sm-10"> 
							<input type="text" value="<?php echo $fPago;?>" disabled>
						
						</div>
						
						<?php 
						//sumaremos el importe al array de formas de pago.
						//~ $formasPago=$usuario['formasPago'];
						//~ foreach ($formasPago as $formaPago){
							//~ if ($formaPago === $key){
								//~ if (!isset($totalFormaPago[$formaPago])){
									//~ $totalFormaPago[$formaPago]=$fPago;
								//~ } else {
									//~ $totalFormaPago[$formaPago]+=$fPago;
								//~ }
							//~ }
						//~ }		
					}
					?>
					</div>
					<?php //	echo 'suma tarjetas + contado: '.$totalFormaPago; ?></br><?php
					
					
				}
					?>
						
				</div>
			
					
				
					<!-- BASES -->
				<div class="col-md-4">
					<h3 class="text-left"> Bases: </h3>
					<div class=" form-group">
						<label class="control-label col-sm-2" >1ª:</label>
						<div class="col-sm-10"> 
							<input type="text" id="base4" name="base4" value="<?php echo $base4;?>" disabled>
						</div>
						<label class="control-label col-sm-2">2ª:</label>
						<div class="col-sm-10"> 
							<input type="text" id="base10" name="base10" value="<?php echo $base10;?>" disabled>
						</div>
						<label class="control-label col-sm-2">3ª:</label>
						<div class="col-sm-10"> 
							<input type="text" id="base21" name="base21" value="<?php echo $base21;?>" disabled>
						</div>
						<label class="control-label col-sm-2">Suma bases:</label>
						<div class="col-sm-10"> 
							<input type="text" id="totalBase" name="totalBase" value="<?php echo $totalBase;?>" disabled>
						</div>
					</div>
					<div class="col-md-8">
						<label class="control-label ">Total Bases e Ivas:</label>
						<div class="col-sm-10"> 
							<input type="text" id="totalBasesEivas" name="totalBasesEivas" value="<?php echo $totalBasesEivas;?>" disabled>
						</div>
					</div>
				
				</div>
			
				
					<!-- IVAS -->
				<div class="col-md-4">
					<h3 class="text-left"> Ivas: </h3>
					
					<div class="form-group">
					<?php //recorro ivas
				
					foreach($ivas as $key =>$iva){ 
						$i=0;
						foreach ($Users['rangoTickets'] as $key =>$numTicket){
							//consigo sumabase y sumaIva de cada iva de cada ticket
							//montar estructura de ivas
							echo ' tickt '.$numTicket.' iva '.$iva.' </br>';
							
							$sumasIvaBase =	baseIva($BDTpv,$numTicket,$iva);
							
							if (!isset($sumasIvaBase['sumaiva'])){
								$sumasIvaBase['sumaiva'][$iva]=$sumasIvaBase['ivas'][$iva]['sumaiva'];
							} else {
								$sumasIvaBase['sumaiva'][$iva] +=$sumasIvaBase['ivas'][$iva]['sumaiva'];
							}
							
						$i++;
						
						
						?>
						<label><?php echo $iva.' %:';?></label>
						<div> 
							<input type="text" id="iva4" name="iva4" value="<?php echo 'xx ';?>" disabled>
						</div>
					<?php 
						echo '<pre>';
							print_r($sumasIvaBase['sumaiva']);
						echo '</pre>';
						}//cierro foreach de numTicket
					} //se cierra foreach de ivas
					?>	
					</div>
						
						
						
						
						<label class="control-label col-sm-2">Suma ivas:</label>
						<div class="col-sm-10"> 
							<input type="text" id="totalIva" name="totalIva" value="<?php echo $totalIva;?>" disabled>
						</div>				
					
				<div> 
					
					
					<!-- Fin IVAS -->
			
			</div> 
				
			</div> 	
				<div class="col-md-8">
				<label class="control-label col-sm-2">Total Caja:</label>
					<div class="col-sm-10"> 
						<input type="text"  value="<?php echo $Users['totalcaja'];?>" disabled>
					</div>
				</div>
			</div>
			<!-- fin row -->
		</div>
	</div>
    </div>
		
</body>
</html>
