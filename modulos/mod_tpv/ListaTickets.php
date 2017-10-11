<!DOCTYPE html>
<html>
    <head>
        <?php
	include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	
	
	
	//INICIALIZAMOS variables para el plugin de paginado:
	//$PgActual = 1 por defecto
	//$CantidadRegistros , usamos la funcion contarRegistro de la class controladorComun /controllers/Controladores  
	//$LimitePagina = 40 o los que queramos
	//$LinkBase --> en la vista que estamos trabajando ListaProductos.php? para moverse por las distintas paginas
	//$OtrosParametros
	$filtro = ''; // por defecto
	$PgActual = 1; // por defecto.
	$LimitePagina = 40; // por defecto.
	// Obtenemos datos si hay GET y cambiamos valores por defecto.
	if ($_GET) {
		if ($_GET['pagina']) {
			$PgActual = $_GET['pagina'];
		}
		if ($_GET['buscar']) {
			$palabraBuscar = $_GET['buscar'];
			$filtro =  'WHERE `Numticket` LIKE "%'.$palabraBuscar.'%"';
		} 
	}
	
	// Creamos objeto controlado comun, para obtener numero de registros. 
	//parametro necesario para plugin de paginacion
	//funcion contarRegistro necesita:
	//$BDTpv 
	//$vista --> es la tabla en la que trabajamos
	//$filtro --> por defecto es vacio, suele ser WHERE x like %buscado%, caja de busqueda
	
	$Controler = new ControladorComun; 
	
	$vista = 'ticketst';
	$LinkBase = './ListaTickets.php?';
	$OtrosParametros = '';
	$CantidadRegistros = $Controler->contarRegistro($BDTpv,$vista,$filtro);
	$paginasMulti = $PgActual-1;
	if ($paginasMulti > 0) {
		$desde = ($paginasMulti * $LimitePagina); 
	} else {
		$desde = 0;
	}
	// Realizamos consulta 
	//si existe palabraBuscar introducida en buscar, la usamos en la funcion obtenerProductos

	if ($palabraBuscar !== '') {
		$filtro = "$palabraBuscar";
		//$filtro =  "AND `Numticket` LIKE '%".$palabraBuscar."%'";
	} else {
		$filtro = '';
	}

	$OtrosParametros = $palabraBuscar;	
	$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros);
	$tickets = obtenerTickets($BDTpv,$LimitePagina ,$desde,$filtro);

	?>
	
	<script>
	// Declaramos variables globales
	var checkID = [];
	var BRecambios ='';
	</script> 
    <!-- Cargamos fuciones de modulo.
    Cargamos JS del modulo de productos para no repetir funciones: BuscarProducto, metodoClick (pulsado, adonde)
    caja de busqueda en listado 
     -->
	<script src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
      
	
	<script>
	// Funciones para atajo de teclado.
	//~ shortcut.add("Shift+V",function() {
		//~ // Atajo de teclado para ver
		//~ metodoClick('VerUsuario');
	//~ });    
	    
	</script> 
    </head>

<body>
        <?php
        include './../../header.php';
        ?>
        <?php
				//~ echo '<pre>';
					//~ print_r($_SESSION['usuarioTpv']['id']);
				//~ echo '</pre>';
	//~ echo '<pre>';
	//~ print_r($tickets['sql']);	
	//~ echo '</pre>';
		?>
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Tickets Cerrados: Tickets ya cobrados </h2>
					<?php 
					//~ echo 'Numero filas'.$Familias->num_rows.'<br/>';
					//~ echo '<pre class="text-left">';
					//~ print_r($Familias);
					//~ 
					//~ echo '</pre>';
					?>
				</div>
	        <!--=================  Sidebar -- Menu y filtro =============== 
				Efecto de que permanezca fixo con Scroll , el problema es en
				movil
	        -->
	       
			<nav class="col-sm-2" id="myScrollspy">
				<a class="text-ritght" href="./tpv.php">Volver Atrás</a>
				<div data-offset-top="505">
				<h4> Tickets cerrados</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
				 	<li><a href="#section1" onclick="metodoClick('VerTicket','ticket');";>Modificar</a></li>
				 	<li><a href="#section2">Imprimir</a></li>
				<?php		//metodoClick js case pulsado 
								//agregarUsuario nos lleva a formulario usuario
								//verUsuario si esta checkado nos lleva vista usuario de ese id
											//si NO nos indica que tenemos que elegir uno de la lista ?>
				</ul>
				</div>	
			</nav>
			<div class="col-md-10">
					<p>
					 -Tickets cerrados encontrados BD local filtrados:
						<?php echo count($tickets);?>
					</p>
					<?php 	// Mostramos paginacion 
						echo $htmlPG;
					?>
					<form action="./ListaTickets.php" method="GET" name="formBuscar">
						<div class="form-group ClaseBuscar">
							<label>Buscar en Formas de pago y en Num Ticket </label>
							<input type="text" name="buscar" value="">
							<input type="submit" value="buscar">
						</div>
					</form>				
                 <!-- TABLA DE TICKETS -->
			<div>
			<table class="table table-striped">
				<thead>
					<tr>
						<th></th>
						<th>ID</th>
						<th>NUM TICKET</th>
						<th>FECHA</th>
						<th>ID TIENDA</th>
						<th>ID USUARIO</th>
						<th>ID CLIENTE</th>
						<th>NOMBRE CLIENTE</th>
						<th>ESTADO</th>
						<th>FORMA PAGO</th>
						<th>TOTAL</th>
					</tr>
				</thead>
	
				<?php
				$checkUser = 0;
				foreach (array_reverse($tickets) as $ticket){ 
					$checkUser = $checkUser + 1; 
				?>

				<tr>
					<td class="rowUsuario"><input type="checkbox" name="checkUsu<?php echo $checkUser;?>" value="<?php echo $ticket['id'];?>">
					</td>
					<td><?php echo $ticket['id'];  ?></td>
					<td><?php echo $ticket['Numticket'];  ?></td>
					<td><?php echo $ticket['Fecha']; ?></td>
					<td><?php echo $ticket['idTienda']; ?></td>
					<td><?php echo $ticket['idUsuario']; ?></td>
					<td><?php echo $ticket['idCliente']; ?></td>
					<td><?php echo $ticket['Nombre']; ?></td>
					
					<td><?php echo $ticket['estado']; ?></td>
					<td><?php echo $ticket['formaPago']; ?></td>
					<td><?php echo $ticket['total']; ?></td>
					
				</tr>

				<?php 
				}
				?>
				
			</table>
			</div>
		</div>
	</div>
    </div>
		
</body>
</html>
