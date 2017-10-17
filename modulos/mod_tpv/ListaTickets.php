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
	$palabraBuscar=array();
	$stringPalabras='';
	$filtro = ''; // por defecto
	$PgActual = 1; // por defecto.
	$LimitePagina = 40; // por defecto.
	// Obtenemos datos si hay GET y cambiamos valores por defecto.
	if ($_GET) {
		if ($_GET['pagina']) {
			$PgActual = $_GET['pagina'];
		}
		if ($_GET['buscar']) {
			//recibo un string con 1 o mas palabras
			$stringPalabras = $_GET['buscar'];
			$palabraBuscar = explode(' ',$_GET['buscar']); 
		} 
	}
	
	// Creamos objeto controlado comun, para obtener numero de registros. 
	//parametro necesario para plugin de paginacion
	//funcion contarRegistro necesita:
	//$BDTpv 
	//$vista --> es la tabla en la que trabajamos
	//$filtro --> por defecto es vacio, suele ser WHERE x like %buscado%, caja de busqueda
	
	$Controler = new ControladorComun; 
	$usuario = $_SESSION['usuarioTpv']['id']; //para consultar por usuario tickets cobrados
	$vista = 'ticketst';
	$LinkBase = './ListaTickets.php?';
	$OtrosParametros = '';
	//~ $CantidadRegistros = $Controler->contarRegistro($BDTpv,$vista,$filtro);
	$paginasMulti = $PgActual-1;
	if ($paginasMulti > 0) {
		$desde = ($paginasMulti * $LimitePagina); 
	} else {
		$desde = 0;
	}
	// Realizamos consulta MONTAMOS WHERE 
	//si tiene palabras , busca por formaPago, por Numticket y por nombreCliente
	if ($stringPalabras !== '' ){
		$campoBD='formaPago';
		$campo2BD = 'Numticket';
		$campo3BD = 'Nombre'; //nombre cliente
		$WhereLimite= $Controler->paginacionFiltroBuscar($BDTpv,$stringPalabras,$LimitePagina,$desde,$campoBD,$campo2BD,$campo3BD);
		$filtro=$WhereLimite['filtro'];
		
		$OtrosParametros=$stringPalabras;
	}
	
	//filtro necesario para contarRegistros , solo lee sobre una tabla, ticketst 
	if ($filtro !== '') {
		$mostrarPorIdUser = ' AND `idUsuario` = '.$usuario;
		$filtro = $filtro.$mostrarPorIdUser;
	} else {
		$filtro = ' WHERE `idUsuario` = '.$usuario;
	}
	
	
	//OTRA BUSQUEDA para CONTAR Registros 
	//consultamos 2 veces: 1 para obtner numero de registros y el otro los datos.
	$CantidadRegistros = $Controler->contarRegistro($BDTpv,$vista,$filtro);
	echo 'filtro -> '.$filtro.'</br> registro '.$CantidadRegistros['sql'].'</br>';
	$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros);
	
	//BUSQUEDA PARA OBTENER DATOS Y MOSTRARLOS
	//si hay palabras se monta WHERE con idUser=usuario logueado
	if ($stringPalabras !== '' ){
		$filtro = $WhereLimite['filtro'].$mostrarPorIdUser.$WhereLimite['rango'];
		
	} else { //si no hay busqueda, se muestra por usuario logueado
		$filtro= " WHERE `idUsuario`= ".$usuario." LIMIT ".$LimitePagina." OFFSET 0";
	}
	
	$tickets = obtenerTickets($BDTpv,$LimitePagina ,$desde,$filtro);
	
	
	$CantidadRegistros=count($tickets);
	
	
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
	echo '<pre>';
	print_r($tickets['sql']);	
	echo '</pre>';
		?>
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Tickets Cerrados y Cobrados </h2>
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
				 	<li><a href="#section1" onclick="metodoClick('VerTicket');";>Ver Ticket</a></li>
				 	<li><a href="#section2" onclick="metodoClick('imprimirTicket');";>Imprimir</a></li>
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
						<?php echo $CantidadRegistros;?>
					</p>
					<?php 	// Mostramos paginacion 
						echo $htmlPG;
					?>
					<form action="./ListaTickets.php" method="GET" name="formBuscar">
						<div class="form-group ClaseBuscar">
							<label>Buscar en Formas de pago, en Num Ticket y por Nombre Cliente.</label>
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
						<th>Nº TICKET</th>
						<th>FECHA</th>
						<th>ID TIENDA</th>
						<th>ID USUARIO</th>
						<th>ID CLIENTE</th>
						<th>NOMBRE CLIENTE</th>
						<th>ESTADO</th>
						<th>FORMA PAGO</th>
						<th>TOTAL</th>
						<th>ID CIERRE</th>
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
					<td></td>
					
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
