<!DOCTYPE html>
<html>
    <head>
        <?php
		// Reinicio variables
        include './../../head.php';
        include './funciones.php';
        include '../mod_cierres/funciones.php';
        include ("./../mod_conexion/conexionBaseDatos.php");
		// Ya no hace falta, ya que lo contralomos head.
		//~ if ($Usuario['estado'] === "Incorrecto"){
			//~ return;	
		//~ }
		
		?>
		<!-- Cargamos libreria control de teclado -->
		<script src="<?php echo $HostNombre; ?>/modulos/mod_tpv/funciones.js"></script>

		
	</head>
	<body>
		<?php
        include './../../header.php';
		// ===========  datos cliente segun id enviado por url============= //
		$idTienda = $Tienda['idTienda'];
		$tabla= 'ticketst'; // Tablas que voy utilizar.
		
		
		if (isset($_GET['id'])) {
			// Modificar Ficha Cliente
			$id=$_GET['id']; // Obtenemos id para modificar.
			$datos = verSelec($BDTpv,$id,$tabla);
			foreach($datos as $dato){
				$idCliente=$dato['idClientes'];
				$nombreCliente =$dato['Nombre'];
				$datoTicket=$dato;
			}
			
			//~ echo '<pre>';
			//~ print_r($datos);
			//~ echo '</pre>';
			$titulo = "Tickets Cerrados";
			if (isset($datos['error'])){
				$error='NOCONTINUAR';
				$tipomensaje= "danger";
				$mensaje = "Id de usuario incorrecto ( ver get) <br/>".$datos['consulta'];
			}
		}
		
		
		
		?>
     
		<div class="container">
				
			<?php 
			//~ echo '<pre>';
			//~ print_r($_POST);
			//~ echo '</pre>';
			if (isset($mensaje) || isset($error)){   ?> 
				<div class="alert alert-<?php echo $tipomensaje; ?>"><?php echo $mensaje ;?></div>
				<?php 
				if (isset($error)){
				// No permito continuar, ya que hubo error grabe.
				return;
				}
				?>
			<?php
			}
			?>
			<h1 class="text-center"> <?php echo $titulo;?></h1>
			<a class="text-ritght" href="./ListaTickets.php">Volver Atrás</a>
			<div class="col-md-10 col-md-offset-2 ">
				<div class="col-md-12">
					<div class="col-md-7">
						<div class="col-md-6">
							<strong>Fecha Inicio:</strong><br/>
							<span id="Fecha"><?php echo $datoTicket['Fecha'];?></span><br/>
						</div>
						<div class="col-md-6">
							<strong>Estado:</strong>
							<span id="EstadoTicket"> <?php echo $datoTicket['estado'];?></span><br/>
							<strong>NºTicket:</strong>
							<span id="NTicket"><?php echo $datoTicket['Numticket'];?></span><br/>
						</div>
					</div>
					<div class="col-md-5">
						<label>Empleado:</label>
						<span id="Usuario"><?php echo $datoTicket['username'];?></span><br/>
					</div>
				</div> 
				<?php //Cliente  ?>
				<div class="form-group">
					<label>Cliente:</label>
					<input type="text" id="id_cliente" name="idCliente" value="<?php echo $idCliente;?>" size="2" readonly>
					<input type="text" id="Cliente" name="Cliente" placeholder="Sin identificar" value="<?php echo $nombreCliente; ?>" size="60" readonly>
					<a id="buscar" class="glyphicon glyphicon-search buscar" onclick="buscarClientes()"></a>
			
				</div>
			
			</div>
			<div class="col-md-10 col-md-offset-2 ">
				<div class="col-md-12">	
					<table id="tabla" class="table table-striped">
						<thead>
							<tr>
								<th>L</th>
								<th>Codbarras</th>
								<th>Referencia</th>
								<th>Descripcion</th>
								<th>Unid</th>
								<th>PVP</th>
								<th>Iva</th>
								<th>Importe</th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($datos as $key =>$dato) {?>
								<tr>
									<td><?php echo $key+1; ?></td>
									<td><?php echo $dato['ccodbar'];  ?></td>
									<td><?php echo $dato['cref']; ?></td>
									<td><?php echo $dato['cdetalle']; ?></td>
									<td><?php echo number_format($dato['nunidades'],2); ?></td>
									<td><?php echo number_format($dato['precioCiva'],2); ?></td>
									<td><?php echo $dato['iva']; ?></td>
									<td><?php echo number_format($dato['nunidades'],2)*number_format($dato['precioCiva'],2); ?></td>
								</tr>
								<?php
								
								
							}?>
							
						</tbody>
					</table>
				</div>
			</div>
			<?php 
			
			
			$datosIvas = baseIva($BDTpv,$datoTicket['idticketst']);
			
			foreach ($datosIvas['items'] as $datoIBase){
				switch ($datoIBase['iva']){
				case 4 :
					$base4 = $datoIBase['importeBase'];
					$iva4 = $datoIBase['importeIva'];

				break;
				case 10 :
					$base10 = $datoIBase['importeBase'];
					$iva10 = $datoIBase['importeIva'];
				break;
				case 21 :
					$base21 = $datoIBase['importeBase'];
					$iva21 = $datoIBase['importeIva'];
				break;
				}
			}
			//~ echo '<pre>';
			//~ print_r($datosIvas['items']);
			//~ echo '</pre>';
			
			
			
			?>
			<div class="col-md-10 col-md-offset-2 pie-ticket">
				<!-- TABLA IVAS BASES -->
				<table id="tabla-pie" class="col-md-6">
				<thead>
					<tr>
						<th>Tipo</th>
						<th>Base</th>
						<th>IVA</th>
					</tr>
				</thead>
				<tbody>
				<tr id="line4">
					<td id="tipo4">
						<?php echo (isset($base4) ? " 4%" : '');?>
					</td>
					<td id="base4">
						<?php echo (isset($base4) ? $base4 : '');?>
					</td>
					<td id="iva4">
						<?php echo (isset($iva4) ? $iva4 : '');?>
					</td>
					
				</tr>
				<tr id="line10">
					<td id="tipo10">
						<?php echo (isset($base10) ? "10%" : '');?>
					</td>
					<td id="base10">
						<?php echo (isset($base10) ? $base10 : '');?>
					</td>
					<td id="iva10">
						<?php echo (isset($iva10) ? $iva10 : '');?>
					</td>
					
				</tr>
				<tr id="line21">
					<td id="tipo21">
						<?php echo (isset($base21) ? "21%" : '');?>
					</td>
					<td id="base21">
						<?php echo (isset($base21) ? $base21 : '');?>
					</td>
					<td id="iva21">
						<?php echo (isset($iva21) ? $iva21 : '');?>
					</td>
					
				</tr>
			</tbody>
			</table>
			<!--Fin tabla bases ivas-->
			<div class="col-md-6">
				<div class="col-md-4">
					<h3>TOTAL</h3>
				</div>
				<div class="col-md-8 text-rigth totalImporte" style="font-size: 3em;">
					<?php echo  $dato['total'];?>
				</div>
			</div>	
			
		</div>
		<?php // Incluimos paginas modales
			include 'busquedaModal.php';
		?>
	</body>
</html>
