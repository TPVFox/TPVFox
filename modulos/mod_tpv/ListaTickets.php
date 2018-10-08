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
	include_once $URLCom.'/head.php';
    include_once $URLCom.'/modulos/mod_tpv/funciones.php';
	include_once $URLCom.'/plugins/paginacion/paginacion.php';
    include_once $URLCom.'/controllers/Controladores.php';
	// Creamos objeto controlado comun, para obtener numero de registros. 
	$Controler = new ControladorComun; 
			
	//INICIALIZAMOS variables para el plugin de paginado:
	$mensaje_error = array();
	$campos = array();
	$palabraBuscar=array();
	$stringPalabras='';
	$filtro = ''; // por defecto
	$PgActual = 1; // por defecto.
	$LimitePagina = 40; // por defecto.
	$LinkBase = './ListaTickets.php?';
	$OtrosParametros = '';
	$desde = 0;
	$sufijo = '';
	$prefijo = '';
	$estado_ticket  = '';
	$htmlPG = '';
	// Obtenemos datos si hay GET y cambiamos valores por defecto.
	if (count($_GET)>0 ){
		// Quiere decir que hay algún get
		if (isset($_GET['estado'])){
			$estado_ticket = $_GET['estado']; // Este va indicar si filtramos por algun estado.
		}
		if (isset($_GET['pagina'])) {
			// En que pagina estamos.
			$PgActual = $_GET['pagina'];
		}
		if (isset($_GET['buscar'])) {
			//recibo un string con 1 o mas palabras
			$stringPalabras = $_GET['buscar'];
			$palabraBuscar = explode(' ',$_GET['buscar']); 
			// Montamos array de campos
			$campos = array (
				'0' => array(
					'nombre_campo'		=> 'formaPago',
					'tipo_comparador'	=> 'LIKE'
				),
				'1' => array(
					'nombre_campo'		=> 'Numticket',
					'tipo_comparador'	=> 'LIKE'
				),
				'2' => array(
					'nombre_campo'		=> 'Nombre',//nombre cliente
					'tipo_comparador'	=> 'LIKE'
				)
			);
			// Enviamos desde donde buscamos.
			$desde = (($PgActual-1) * $LimitePagina); 
		}
		
	}
	
	$OtrosParametros=$stringPalabras;	// Lo necesitamos en paginacion.
			
	if ($estado_ticket !== ''){
		$prefijo = 'estado="'.$estado_ticket.'" ';
		$OtrosParametros .= '&estado='.$estado_ticket; // Lo necesitamos en paginado.

	}
	if ($stringPalabras != ''){
		$prefijo .= ' AND  '; 
	}
	// Creamos filtro para contar.	
	$filtroContar = $Controler->paginacionFiltro($campos,$stringPalabras,$prefijo,$sufijo);
	// Contamos Registros.	
	$CantidadRegistros = $Controler->contarRegistro($BDTpv,'ticketst',$filtroContar);
	if (gettype($CantidadRegistros) !== 'integer'){
		// Quiere decir que hubo un error en la consulta.
		$mensaje_error = ' Algo salio mal en la primera consulta... ';
	}
	
	// Obtenemos paginación si $CantidadRegistros es mayo al Limite
	if ( $CantidadRegistros > $LimitePagina){
		$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros);
	}
	if ($desde > 0 ){
		// Montamos $sufijo...
		$sufijo = ' LIMIT '.$LimitePagina.' OFFSET '.$desde;
	}
	if ($estado_ticket !== ''){
		$prefijo = 't.estado ="'.$estado_ticket.'" ';
	}
	if ($stringPalabras != ''){
		$prefijo .= ' AND  '; 
	}
	
	// Creamos filtro pero con sufijo para mostrar solo los registros de la pagina.
	$filtro = $Controler->paginacionFiltro($campos,$stringPalabras,$prefijo,$sufijo);
	$Obtenertickets = ObtenerTickets($BDTpv,$filtro);
	$tickets = $Obtenertickets['tickets'];
	
	//esta MAL // si la busqueda es menos de 40 lo siguiente es un apaño..
	if (!isset($CantidadRegistros)){
		$CantidadRegistros = count($tickets);
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
	<script src="<?php echo $HostNombre; ?>/modulos/mod_tpv/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
	
 
    </head>

<body>
        <?php
         include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Tickets <?php echo $estado_ticket;?>s</h2>
				</div>
	        <!--=================  Sidebar -- Menu y filtro =============== 
				Efecto de que permanezca fixo con Scroll , el problema es en
				movil
	        -->
	       
			<nav class="col-sm-2" id="myScrollspy">
				<a class="text-ritght" href="./tpv.php">Volver Atrás</a>
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
						<form action="./ListaTickets.php" method="GET" name="formBuscar">
							<div class="form-group ClaseBuscar">
								<label>Buscar en Formas de pago, en Num Ticket y por Nombre Cliente.</label>
								<input type="text" name="buscar" value="<?php echo $stringPalabras;?>">
								<input type="hidden" name ="estado" value="<?php echo $estado_ticket;?>">
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
						<th>DESCONTAR WEB</th>
					</tr>
				</thead>
	
				<?php
				$checkUser = 0;
				$i=0;
				$tickets = array_reverse($tickets);
				foreach ($tickets as $ticket){ 
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
						echo (isset($ticket['idCierre']) ? $ticket['idCierre']['idCierre']:''); ?>
					</td>
					<td>
						<?php 
							if (!isset($ticket['respuesta_envio_rows'])){
								// Quiere decir que se encontro registro
								echo '<span title="'.$ticket['respuesta_envio'].'">'.$ticket['enviado_stock'].'</span>';
							}  ;?>
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
