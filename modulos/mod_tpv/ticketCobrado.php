<!DOCTYPE html>
<html>
    <head>
        <?php
        include_once './../../inicial.php';
        include_once $URLCom.'/head.php';
        include_once $URLCom.'/modulos/mod_tpv/funciones.php';
		include_once $URLCom.'/controllers/Controladores.php';
       	include_once $URLCom.'/modulos/mod_tpv/clases/ClaseTickets.php';
        include_once $URLCom.'/modulos/mod_producto/clases/ClaseProductos.php';
        $Controler = new ControladorComun; 
		// Añado la conexion
          $CTArticulos = new ClaseProductos($BDTpv);
		$Controler->loadDbtpv($BDTpv);
		// Cargamos clase y creamos objeto de tickets
		$CTickets = new ClaseTickets();
		
        // Cargamos los fichero parametros.
		include_once ($URLCom.'/controllers/parametros.php');
		$ClasesParametros = new ClaseParametros('parametros.xml');
		$parametros = $ClasesParametros->getRoot();
		
		// Cargamos configuracion modulo tanto de parametros (por defecto) como si existen en tabla modulo_configuracion 
		$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
		$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_tpv',$Usuario['id']);
	
		
				
		if (isset($_GET['id'])) {
			// Modificar Ficha Cliente
			$id=$_GET['id']; // Obtenemos id para modificar.
			$ticket = $CTickets->obtenerUnTicket($id);
            // Debía controlar si hay virtumart o no .. antes mostralo.
            $enviado_stock = ObtenerEnvioIdTickets($BDTpv,$id);
            $permitir_envio = 'Si';
            if (isset($enviado_stock['tickets'])){
             // Hay resultado consulta.
                if ($enviado_stock['tickets']['respuesta_envio_rows'] >= 1){
                // Quiere decir que se envio ...
                    $permitir_envio = 'No';
                }
            }
		}
			
		$titulo = 'Ticket '.$ticket['cabecera']['estado'];
		if ($CTArticulos->SetPlugin('ClaseVirtuemart') !== false){
            $ObjVirtuemart = $CTArticulos->SetPlugin('ClaseVirtuemart');
            echo $ObjVirtuemart->htmlJava();
            $tiendaWeb=$ObjVirtuemart->getTiendaWeb();
         }
		// Añadimos productos a JS
		// -------------- Obtenemos de parametros cajas con sus acciones ---------------  //
		$VarJS = $Controler->ObtenerCajasInputParametros($parametros);
		//~ echo '<pre>';
		//~ print_r($ticket);
		//~ echo '</pre>';
		?>
		<script type="text/javascript">
		// Objetos cajas de tpv
		<?php echo $VarJS;?>
		</script>
		

		<!-- Cargamos libreria control de teclado -->
		<script src="<?php echo $HostNombre; ?>/modulos/mod_tpv/funciones.js"></script>
		<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
				
	</head>
	<body>
		<?php
        //~ include_once $URLCom.'/header.php';
         include_once $URLCom.'/modulos/mod_menu/menu.php';
       
		?>
		
     
		<div class="container">
				
			<?php 
			
			if (isset($ticket['error'])) {   
				foreach ($ticket['error'] as $error){
				?> 
					<div class="alert alert-<?php echo $error['tipo']; ?>"><?php echo $error['mensaje'] ;?></div>
					<?php 
					if ($error['tipo']==='danger'){
						// No permito continuar, ya que hubo error grabe.
						exit();
					}
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
				 	<li><button id="DescontarStock" type="button" class="btn btn-primary" onclick="PrepararEnviarStockWeb(<?php echo $id;?>);" >Descontar Stock en Web</button>
				 <?php } ?>
				 	<li><a onclick="imprimirTicketCerrado(<?php echo $id;?>);">Imprimir</a></li>
				</ul>
				</div>	
			</nav>
			
			
			<div class="col-md-10">
				<div class="col-md-12">
					<div class="col-md-7">
						<div class="col-md-6">
							<strong>Fecha Inicio:</strong><br/>
							<span id="Fecha"><?php echo $ticket['cabecera']['Fecha'];?></span><br/>
						</div>
						<div class="col-md-6">
							<strong>Estado:</strong>
							<span id="EstadoTicket"> <?php echo $ticket['cabecera']['estado'];?></span><br/>
							<strong>NºTicket:</strong>
							<span id="NTicket"><?php echo $ticket['cabecera']['Numticket'];?></span><br/>
						</div>
					</div>
					<div class="col-md-5">
						<label>Empleado:</label>
						<span id="Usuario"><?php echo $ticket['cabecera']['username'];?></span><br/>
					</div>
				</div> 
				<?php //Cliente  ?>
				<div class="form-group">
					<label>Cliente:</label>
					<?php 
					echo '<input type="text" id="id_cliente" name="idCliente" value="'
						.$ticket['cabecera']['idCliente'].'" size="2" readonly>';
					echo '<input type="text" id="Cliente" name="Cliente" placeholder="Sin identificar" value="'
						.$ticket['cabecera']['Nombre'].' - '.$ticket['cabecera']['razonsocial'].'" size="60" readonly>';?>
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
							$total = 0;
							foreach ($ticket['lineas'] as $key =>$dato) {?>
								<?php 
								if ($dato['estadoLinea'] === 'Eliminado'){
									$htmlEstado = '<span class="glyphicon glyphicon-trash"></span>';
									$classRow = 'class="tachado"';
								} else {
									$htmlEstado='';
									$classRow ='';
								}
								$nunidades = number_format($dato['nunidades'],3);
								$precioCiva = number_format($dato['precioCiva'],2);
								$importe = number_format($nunidades*$precioCiva,2);
								if ($dato['estadoLinea'] !== 'Eliminado'){
									// Solo sumo si estado es distinto Eliminado
									$total = $total + $importe;
								}
								?> 
								<tr <?php echo $classRow?>>
									<td><?php echo $key+1; ?></td>
									<td><?php echo $dato['ccodbar'];  ?></td>
									<td><?php echo $dato['cref']; ?></td>
									<td><?php echo $dato['cdetalle']; ?></td>
									<td><?php echo $nunidades; ?></td>
									<td><?php echo $precioCiva; ?></td>
									<td><?php echo $dato['iva']; ?></td>
									<td><?php echo $importe ?></td>
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
			
				foreach ( $ticket['basesYivas'] as $baseYiva){
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
					<?php
					if ( strval($total) !=  $ticket['cabecera']['total']){
						echo '<pre>';
						print_r('Total no coincide, me da:'.$total);
						echo '</pre>';
					}
					?>
					<div style="font-size: 3em;"><?php echo  $ticket['cabecera']['total'];?></div>
					<p><?php echo  $ticket['cabecera']['formaPago'];?></p>
					<p><?php echo  $ticket['cabecera']['entregado'];?></p>
				</div>
				
			</div>	
			
		</div>
		<?php // Incluimos paginas modales
		echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
		include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
		// hacemos comprobaciones de estilos 
		?>
	</body>
</html>
