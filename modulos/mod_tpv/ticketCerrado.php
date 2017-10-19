<!DOCTYPE html>
<html>
    <head>
        <?php
		// Reinicio variables
        include './../../head.php';
        include './funciones.php';
        include ("./../mod_conexion/conexionBaseDatos.php");
		if ($Usuario['estado'] === "Incorrecto"){
			return;	
		}
		
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
			<div class="col-md-10 col-md-offset-2 pie-ticket">
				<div class="col-md-8">
					<h3>TOTAL</h3>
				</div>
				<div class="col-md-2 text-rigth totalImporte" style="font-size: 3em;">
					<?php echo $dato['total'];?>
				</div>
			</div>	
			
		</div>
		<?php // Incluimos paginas modales
			include 'busquedaModal.php';
		?>
	</body>
</html>
