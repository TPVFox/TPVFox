<?php 
/*
 * @Objetivo es mostrar listado de ticket.
 * Pueden ser cobrados o cerrados según de donde se ejecute.
 * Get 
 * 		'tickets->
 * 				Cerrados -> Estos son los ticket ya se hizo cierre.
 * 				Cobrados -> Estos son los tickets cobrados.
 * */
?>


<!DOCTYPE html>
<html>
    <head>
        <?php
	include_once './../../inicial.php';
    include $URLCom.'/head.php';
    include_once $URLCom.'/modulos/mod_cierres/funciones.php';
	include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';
	include_once $URLCom.'/controllers/Controladores.php';
	// Creamos objeto controlado comun, para obtener numero de registros. 
	$Controler = new ControladorComun; 
	// Esto esta en header.. pienso que deberíamos pasarlo para head
	$Tienda = (isset($_SESSION['tiendaTpv']) ? $_SESSION['tiendaTpv']: array('razonsocial'=>''));
		
	//INICIALIZAMOS variables para el plugin de paginado:
	$mensaje_error = array();
	
	$idTienda = $Tienda['idTienda'];
    


	// Obtenemos datos si hay GET y cambiamos valores por defecto.
	if (count($_GET)>0 ){
		// Quiere decir que hay algún get
		$estado_ticket 	= $_GET['estado'];
		$idUsuario 		= $_GET['idUsuario'];
		$idCierre 		= $_GET['idCierre'];
        // ===========    Paginacion  ====================== //
        $NPaginado = new PluginClasePaginacion(__FILE__);
        // Ahora añadimos parametros estado,idusuario,idCierre para que en todas las paginas se envie tb.
        $otrosParametros = 'estado='.$_GET['estado'].'&idUsuario='.$_GET['idUsuario'].'&idCierre='. $_GET['idCierre'].'&';
        $NPaginado->AnahadirLinkBase($otrosParametros);
        $campos = array ('formaPago','Numticket','Nombre');
        $NPaginado->SetCamposControler($campos);
        //~ $NPaginado->SetOrderConsulta('a.Numalbpro');
        $filtro= $NPaginado->GetFiltroWhere('OR'); // mando operador para montar filtro ya que por defecto es AND
        $t =  obtenerTicketsUsuariosCierre($BDTpv,$idUsuario,$idCierre,$idTienda);
        $CantidadRegistros = count($t['tickets']);
        $NPaginado->SetCantidadRegistros($CantidadRegistros);
        $t =  obtenerTicketsUsuariosCierre($BDTpv,$idUsuario,$idCierre,$idTienda,$filtro.$NPaginado->GetLimitConsulta());
        $Tickets = $t['tickets'];
        $htmlPG = $NPaginado->htmlPaginado();
            
        //~ echo '<pre>';
        //~ print_r($t);
        //~ echo '</pre>';

        // El paginado creo que no funciona , ya que no ponemos link base, los get idUsuario y idCierre
        
	}
	?>
	
	<script>
	// Declaramos variables globales
	var checkID = [];
	</script> 
    <!-- Cargamos fuciones de modulo.
    Cargamos JS del modulo de productos para no repetir funciones: BuscarProducto, metodoClick (pulsado, adonde)
    caja de busqueda en listado 
     -->
	<script src="<?php echo $HostNombre; ?>/modulos/mod_cierres/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
	
 
    </head>

<body>
        <?php
        include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>
        <?php
	//~ echo '<pre>';
	//~ print_r($Tickets);	
	//~ echo '</pre>';
		?>
       
	<div class="container">
		<?php 
		if (count($mensaje_error)>0){   ?> 
			<div class="alert alert-danger">
				<?php 	echo '<pre>';
						print_r($mensaje_error) ;
						echo '</pre>';
				?>
			</div>
			<?php 
			if (isset($error)){
				// No permito continuar, ya que hubo error grabe.
				return;
			}
			?>
		<?php
		}
		?>
		
		
		
		
		
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Tickets <?php echo $estado_ticket;?>s</h2>
				</div>
	        <!--=================  Sidebar -- Menu y filtro =============== 
				Efecto de que permanezca fixo con Scroll , el problema es en
				movil
	        -->
	       
			<nav class="col-sm-2" id="myScrollspy">
				<a class="text-ritght" href="./ListaCierres.php">Volver Atrás</a>
				<div data-offset-top="505">
				<h4> Tickets <?php echo $estado_ticket;?>s</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
				 	<li><a href="#section1" onclick="metodoClick('VerTicket');";>Ver Ticket</a></li>
				 	<li><a href="#section2" onclick="metodoClick('imprimirTicket');";>Imprimir</a></li>
				</ul>
				</div>	
			</nav>
			<div class="col-md-10">
					<p>
					 -Tickets cerrados encontrados BD local filtrados:
						<?php echo $CantidadRegistros;?>
					</p>
					<div>
						<div class="alert-info" style="width:30%" >
						<?php 	// Mostramos paginacion 
							$mensaje='Pulsar <strong>Ultima</strong> para ver <strong>ultimos tickets</strong> cobrados.';
							echo $mensaje; 
						?>
						</div>
						<div>
							<?php
								echo $htmlPG;
							?>	
						</div>
						
					</div>
					<div>
						<form action="./ListaTickets.php?" method="GET" name="formBuscar">
							<div class="form-group ClaseBuscar">
								<label>Buscar en Formas de pago, en Num Ticket y por Nombre Cliente.</label>
								<input type="hidden" name ="estado" value="<?php echo $estado_ticket;?>">
								<input type="hidden" name ="idUsuario" value="<?php echo $idUsuario;?>">
								<input type="hidden" name ="idCierre" value="<?php echo $idCierre;?>">


								<input type="text" name="buscar" value="" placerholder="<?php echo $stringPalabras;?>">

								<input type="submit" value="buscar">
								
							</div>
						</form>		
					</div>		
                 <!-- TABLA DE TICKETS -->
			<div>
			<table class="table table-striped">
				<thead>
					<tr>
						<th title='Este es el id de ticketst, el mismo que idticketst de ticketstIva'>ID</th>
						<th>FECHA</th>
						<th>Nº TICKET (<a title="Recuerda que el numero ticket es IdTienda-IdUsuario-NºTicket">*</a>)</th>
						<th>ID CLIENTE</th>
						<th>NOMBRE CLIENTE</th>
						<th>ESTADO</th>
						<th>FORMA PAGO</th>
						<th>TOTAL</th>
						<th>Entregado</th>
					</tr>
				</thead>
	
				<?php
				$checkUser = 0;
				//
				$i=0;
				foreach ($Tickets as $ticket){ 
					$checkUser = $checkUser + 1; 
				?>

				<tr>
					<td class="rowUsuario"><input type="checkbox" name="checkUsu<?php echo $checkUser;?>" 
							value="<?php echo $ticket['id'];?>">
					</td>
					<td><?php echo $ticket['Fecha']; ?></td>
					<td><?php echo $ticket['idTienda'].'-'.$ticket['idUsuario'].'-'.$ticket['Numticket']; ?></td>
					<td><?php echo $ticket['idCliente']; ?></td>
					<td><?php echo $ticket['Nombre']; ?></td>
					<td><?php echo $ticket['estado']; ?></td>
					<td><?php echo $ticket['formaPago']; ?></td>
					<td><?php echo $ticket['total']; ?></td>
					<td><?php 
						echo (isset($ticket['entregado']) ? $ticket['entregado']:''); ?>
					</td>
					
				</tr>

				<?php 
				$i++;
				}
				?>
				
			</table>
			</div>
		</div>
	</div>
    </div>
		
</body>
</html>
