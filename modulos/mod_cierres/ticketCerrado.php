<!DOCTYPE html>
<html>
    <head>
        <?php
		// Reinicio variables
       include_once './../../inicial.php';
        include $URLCom.'/head.php';
        include_once $URLCom.'/modulos/mod_cierres/funciones.php';
        //~ include ("./../mod_conexion/conexionBaseDatos.php");
		?>
		<script type="text/javascript">
		var cajaBusquedacliente = {
		id_input : 'cajaBusquedacliente',
		acciones : { 
			13 : 'buscarClientes', // pulso intro
			40 : 'buscarClientes', // pulso abajo
			 9 : 'buscarClientes', // tabulador
			},
		parametros : {
		dedonde : 'tpv' 
			}
		}

		var idN = {
		after_constructor: 'Si',
		id_input : 'N_',
		acciones : {
			40 : 'mover_down', // pulso abajo
			38 : 'mover_up' // fecha arriba
			},
		parametros : {
			dedonde : 'cerrados',
			prefijo : 'N_'
			},
		before_constructor : 'Si' // Ejecutamos funcion before_constructor justo después crear objeto caja.
		}	
		</script>
		
		<!-- Cargamos libreria control de teclado -->
		<script src="<?php echo $HostNombre; ?>/modulos/mod_cierres/funciones.js"></script>

		<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>

		
	</head>
	<body>
		<?php
        include_once $URLCom.'/modulos/mod_menu/menu.php';
		// ===========  datos cliente segun id enviado por url============= //
		$idTienda = $Tienda['idTienda'];
		$tabla= 'ticketst'; // Tablas que voy utilizar.
		$idUsuario = $Usuario['id'];
		
		if (isset($_GET['id'])) {
			// Modificar Ficha Cliente
			$id=$_GET['id']; // Obtenemos id para modificar.
			$datos = verSelec($BDTpv,$id,$tabla,$idTienda);
			foreach($datos as $key => $dato){
				
				$idCliente=$dato['idClientes'];
				$nombreCliente =$dato['Nombre'];
				$datoTicket=$dato;
				// Ahora añadimos html para estado y clase row
				$datos[$key]['htmlEstado'] = '';
				$datos[$key]['classRow'] = '';
				if ($dato['estadoLinea'] === 'Eliminado'){
					$datos[$key]['htmlEstado'] = '<span class="glyphicon glyphicon-trash"></span>';
					$datos[$key]['classRow'] = 'class="tachado"';
				}
			}
			
			$titulo = "Ticket Cerrado";
			if (isset($datos['error'])){
				$error='NOCONTINUAR';
				$tipomensaje= "danger";
				$mensaje = "Id de usuario incorrecto ( ver get) <br/>".$datos['consulta'];
			}
		}
		//~ // debug
		//~ echo '<pre>';
		//~ print_r($datos);
		//~ echo '</pre>';
	
		
		?>
     
		<div class="container">
				
			<?php 
			
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
			<a class="text-ritght" href="./ListaTickets.php?estado=Cerrado">Volver Atrás</a>
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
					<a id="buscar" class="glyphicon glyphicon-search buscar" onclick="buscarClientes('cerrados')"></a>
			
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
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($datos as $key =>$dato) {?>
								<tr <?php echo $dato['classRow']?>>
									<td><?php echo $key+1; ?></td>
									<td><?php echo $dato['ccodbar'];  ?></td>
									<td><?php echo $dato['cref']; ?></td>
									<td><?php echo $dato['cdetalle']; ?></td>
									<td><?php echo number_format($dato['nunidades'],2); ?></td>
									<td><?php echo number_format($dato['precioCiva'],2); ?></td>
									<td><?php echo $dato['iva']; ?></td>
									<td><?php echo number_format($dato['nunidades'],2)*number_format($dato['precioCiva'],2); ?></td>
									<td> <?php echo $dato['htmlEstado']; ?>	</td>
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
			include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
		?>
	</body>
</html>
