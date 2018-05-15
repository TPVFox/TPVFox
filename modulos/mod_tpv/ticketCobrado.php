<!DOCTYPE html>
<html>
    <head>
        <?php
		// Reinicio variables
        include './../../head.php';
        include './funciones.php';
        //~ include '../mod_cierres/funciones.php';
        //~ include ("./../mod_conexion/conexionBaseDatos.php");
		// Ya no hace falta, ya que lo contralomos head.
		//~ if ($Usuario['estado'] === "Incorrecto"){
			//~ return;	
		//~ }
		
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
		<script src="<?php echo $HostNombre; ?>/modulos/mod_tpv/funciones.js"></script>
		<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>

		
	</head>
	<body>
		<?php
        include './../../header.php';
		// ===========  datos cliente segun id enviado por url============= //
		$idTienda = $Tienda['idTienda'];
		$idUsuario = $Usuario['id'];
		
		if (isset($_GET['id'])) {
			// Modificar Ficha Cliente
			$id=$_GET['id']; // Obtenemos id para modificar.
			$datos = ObtenerUnTicket($BDTpv,$id,$idTienda);
			$idCliente=$datos['cabecera']['idClientes'];
			$nombreCliente =$datos['cabecera']['DatosCliente'];
			$datoTicket=$datos['cabecera'];
			// Ahora comprobamos si envio datos
			$enviado_stock = ObtenerEnvioIdTickets( $BDTpv,$id);
			$permitir_envio = 'Si';
			if (isset($enviado_stock['tickets'])){
				// Hay resultado consulta.
				if ($enviado_stock['tickets']['respuesta_envio_rows'] >= 1){
					// Quiere decir que se envio ...
					$permitir_envio = 'No';
				}
			}
		}
			
		$titulo = "Ticket Cobrado";
		if (isset($datos['error'])){
			$error='NOCONTINUAR';
			$tipomensaje= "danger";
			$mensaje = "Id de usuario incorrecto ( ver get) <br/>".$datos['consulta'];
		}
		// Añadimos productos a JS
		?>
		<script type="text/javascript">
			var id_ticketst = <?php echo $id; ?>;
			var productos = [];
			var datos_producto = [];
			<?php 
			$i = 0;
			foreach($datos['lineas'] as $product){
				echo "datos_producto['idArticulo']	=".$product['idArticulo'].';';
				echo "datos_producto['codBarras'] 	='".$product['ccodbar']."';";
				echo "datos_producto['crefTienda'] 	='".$product['cref']."';";
				echo "datos_producto['articulo_name'] 	=".'"'.$product['cdetalle'].'";';
				echo "datos_producto['iva']	='".$product['iva']."';";
				echo "datos_producto['pvpCiva']	=".$product['precioCiva'].';';
				echo "datos_producto['unidad'] = '".$product['nunidades']."';";
			// Ahora añadimos datos_productos a productos... creando objeto.
			echo 'productos.push(new ObjProducto(datos_producto));';
			echo "productos[".$i."].estado	='".$product['estadoLinea']."';";

			$i++;
			}
			?>
		
		</script>
     
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
			 
			<nav class="col-sm-2">
				<a href="./ListaTickets.php?estado=Cobrado">Volver Atrás</a>
				<div class="col-md-12">
				<h4> Opciones de Ticket</h4>
				<ul class="nav nav-pills nav-stacked"> 
				 <?php
				 if ($permitir_envio === 'Si'){?>
				 	<li><button id="DescontarStock" type="button" class="btn btn-primary" onclick="PrepararEnviarStockWeb();" >Descontar Stock en Web</button>
				 <?php } ?>
				 	<li><a href="#section2" onclick="metodoClick('imprimirTicket');">Imprimir</a></li>
				</ul>
				</div>	
			</nav>
			
			
			<div class="col-md-10">
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
					<a id="buscar" class="glyphicon glyphicon-search buscar" onclick="buscarClientes('cobrados')"></a>
			
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
							foreach ($datos['lineas'] as $key =>$dato) {?>
								<?php 
								if ($dato['estadoLinea'] === 'Eliminado'){
									$htmlEstado = '<span class="glyphicon glyphicon-trash"></span>';
									$classRow = 'class="tachado"';
								} else {
									$htmlEstado='';
									$classRow ='';
								}
								?> 
								<tr <?php echo $classRow?>>
									<td><?php echo $key+1; ?></td>
									<td><?php echo $dato['ccodbar'];  ?></td>
									<td><?php echo $dato['cref']; ?></td>
									<td><?php echo $dato['cdetalle']; ?></td>
									<td><?php echo number_format($dato['nunidades'],2); ?></td>
									<td><?php echo number_format($dato['precioCiva'],2); ?></td>
									<td><?php echo $dato['iva']; ?></td>
									<td><?php echo number_format($dato['nunidades'],2)*number_format($dato['precioCiva'],2); ?></td>
									<td> <?php echo $htmlEstado; ?>	</td>
								</tr>
								<?php
								
								
							}?>
							
						</tbody>
					</table>
				</div>
			</div>
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
				<?php 
			
				foreach ( $datos['basesYivas'] as $baseYiva){
				?>
				<tr>
					<td  class="tipo_iva">
						<?php echo $baseYiva['iva']."%";?>
					</td>
					<td class="base">
						<?php echo $baseYiva['importeBase'];?>
					</td>
					<td class="importe_iva">
						<?php echo $baseYiva['importeIva'];?>
					</td>
					
				</tr>
				<?php
				}
				?>
				
			</tbody>
			</table>
			<!--Fin tabla bases ivas-->
			<div class="col-md-6">
				<div class="col-md-4 text-right">
					<h3>TOTAL</h3>
					<p>Forma pago</p>
					<p>Entregado</p>
				</div>
				<div class="col-md-8 text-right totalImporte" >
					<div style="font-size: 3em;"><?php echo  $datos['totales']['total'];?></div>
					<p><?php echo  $datos['totales']['formaPago'];?></p>
					<p><?php echo  $datos['totales']['entregado'];?></p>
				</div>
				
			</div>	
			
		</div>
		<?php // Incluimos paginas modales
			include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
		?>
	</body>
</html>
