<!DOCTYPE html>
<html>
    <head>
        <?php
	include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	
	$fecha_dmYHora = 'd-m-Y H:m:s';
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
	$linkResumen = '<span title="Pon la fechas en filtro" class="glyphicon glyphicon-info-sign"> Ver Resumen </span>';
	// Obtenemos datos si hay GET y cambiamos valores por defecto.
	if (isset($_GET['pagina'])) {
		$PgActual = $_GET['pagina'];
	}
	if (isset($_GET['buscar'])) {  
		$stringPalabras = $_GET['buscar'];
		$palabraBuscar = explode(' ',$_GET['buscar']); 		
	} 
	if (isset ($_GET['fecha1']) & isset($_GET['fecha2'])){
		$fecha1=$_GET['fecha1'];
		$fecha2=$_GET['fecha2'];
		// Montamos link para mostrar para poder ver resumen
		$linkResumen = '<a href="ResumenFechas.php?fecha1='.$fecha1.'&fecha2='.$fecha2.'">Ver Resumen</a>';
		
		
		
		
		
	}
	// Creamos objeto controlado comun, para obtener numero de registros. 
	//parametro necesario para plugin de paginacion
	//funcion contarRegistro necesita:
	//$BDTpv 
	//$vista --> es la tabla en la que trabajamos
	//$filtro --> por defecto es vacio, suele ser WHERE x like %buscado%, caja de busqueda
	
	$Controler = new ControladorComun; 
	
	$vista = 'cierres';
	$LinkBase = './ListaCierres.php?';
	$OtrosParametros = '';	
	$filtro = '';
	$limite = '';
	$paginasMulti = $PgActual-1;
	if ($paginasMulti > 0) {
		$desde = ($paginasMulti * $LimitePagina); 
	} else {
		$desde = 0;
	}

	//si hay palabras a buscar
	if ($stringPalabras !== '' ){
		$campoBD='idCierre';
		$WhereLimite= $Controler->paginacionFiltroBuscar($stringPalabras,$LimitePagina,$desde,$campoBD,$campo2BD='',$campo3BD='');
		$filtro=$WhereLimite['filtro'];
		$OtrosParametros=$stringPalabras;
	}


	//$OtrosParametros = $palabraBuscar;	
	$CantidadRegistros = $Controler->contarRegistro($BDTpv,$vista,$filtro);
	$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros);
	if ($stringPalabras !== '' ){
		$filtro = $WhereLimite['filtro'].$WhereLimite['rango'];
	} else {
		$limite= " LIMIT ".$LimitePagina." OFFSET ".$desde;
	}
	
	
	if (isset($_GET['fecha1']) & isset($_GET['fecha2'])){
		// SI recibe por get las fechas añade el filtro a la consulta
		$filtro=' FechaCierre between "'.$fecha1. '" AND "'.$fecha2.'"';
	}
	
	$cierres = obtenerCierres($BDTpv,$filtro,$limite);
	
	
	?>
	<script>
	// Declaramos variables globales
	var checkID = [];
	</script> 
    <!-- Cargamos fuciones de modulo. -->
	<script src="<?php echo $HostNombre; ?>/modulos/mod_cierres/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script>
  
    </head>

<body>
        <?php
        include './../../header.php';
        ?>
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Listar Cierres </h2>
				</div>
	           
			<div class="col-sm-2">
				<div class="col-md-12">
					<h4> Cierres</h4>
					<h5> Opciones para una selección</h5>
					<ul class="nav nav-pills nav-stacked"> 
						<li><a href="#section2" onclick="metodoClick('VerCierre');";>Ver Cierre</a></li>
						<li><!-- Ya controlo en cierres que no pueda hacer caja si no hay tickets, pero tb podría quitar link -->
							<a href="../mod_cierres/CierreCaja.php?dedonde=cierre">Cierre Caja</a>
						</li>
						<li><?php echo $linkResumen;?></li>
					</ul>
				</div>
				<div class="col-md-12">
					<h4>Opciones administrador:</h4>
					
				</div>
			</div>
			<div class="col-md-10">
					<p>
					 -Cierres encontrados BD local filtrados:
						<?php echo $CantidadRegistros;?>
					</p>
					<?php 	// Mostramos paginacion 
						echo $htmlPG;
				//enviamos por get palabras a buscar, las recogemos al inicio de la pagina
					?>
					<br>
					<div class="col-md-10">
						<div class="col-md-5">
				<form action="./ListaCierres.php" method="GET" name="formBuscar">
					<div class=" ClaseBuscar">
						<label>Buscar en idCierre </label>
						<input type="text" name="buscar" value="">
						<input type="submit" value="buscar">
					</div>
				</form>
				</div>
				<div class="col-md-7">
				<form action="./ListaCierres.php" method="GET" name="formBuscar">
					<div class=" ClaseBuscar">
						<label>Buscar por fechas</label>
						<input type="date" name="fecha1" id="fecha1" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" placeholder='yyyy-mm-dd'  title=" Formato de entrada yyyy-mm-dd">
						<input type="date" name="fecha2" id="fecha2" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" placeholder='yyyy-mm-dd' title=" Formato de entrada yyyy-mm-dd">
						<input type="submit" value="buscar">
					</div>
				</form>
				</div>
				</div>
                 <!-- TABLA DE Cierres -->
			<div>
			<table class="table table-striped">
				<thead>
					<tr>
						<th></th>
						<th>ID CIERRE</th>
						<th>HIZO CIERRE</th>
						<th>FECHA: INICIAL->FINAL</th>
						<th>FECHA CIERRE </th>
						<th>TOTAL</th>
					</tr>
				</thead>
				
	
				<?php
				$checkUser = 0;
				foreach (array_reverse($cierres) as $cierre){ 
					$checkUser = $checkUser + 1; 
				?>

				<tr>
					<td class="rowUsuario">
						<input type="checkbox" name="checkUsu<?php echo $checkUser;?>" value="<?php echo $cierre['idCierre'];?>">
					</td>
					<td><?php echo $cierre['idCierre']; ?></td>
					<td><?php echo $cierre['nombreUsuario']; ?></td>					
					<td><?php echo '<small>'.date($fecha_dmYHora, strtotime($cierre['FechaInicio'])).'</small><b>//</b><small>'.date($fecha_dmYHora, strtotime($cierre['FechaFinal'])).'</small>'; ?></td>
					<td><?php echo $cierre['FechaCierre']; ?></td>
					<td><?php echo number_format($cierre['Total'],2); ?></td>
					
				</tr>

				<?php 
				}
				
				?>
				
			</table>
			<?php 
			//~ echo '<pre>';
			//~ print_r($cierres);
			//~ echo '</pre>';
			?>
			</div>
		</div>
	</div>
    </div>
		
</body>
</html>
