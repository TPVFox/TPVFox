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
	// Obtenemos datos si hay GET y cambiamos valores por defecto.
	if (isset($_GET['pagina'])) {
		$PgActual = $_GET['pagina'];
	}
	if (isset($_GET['buscar'])) {  
		$stringPalabras = $_GET['buscar'];
		$palabraBuscar = explode(' ',$_GET['buscar']); 		
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
		$filtro= " LIMIT ".$LimitePagina." OFFSET ".$desde;
	}
	
	$cierres = obtenerCierres($BDTpv,$filtro);
	
	?>
	<script>
	// Declaramos variables globales
	var checkID = [];
	var BRecambios ='';
	</script> 
    <!-- Cargamos fuciones de modulo. -->
	<script src="<?php echo $HostNombre; ?>/modulos/mod_cierres/funciones.js"></script>
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
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Listar Cierres </h2>
				</div>
	        <!--=================  Sidebar -- Menu y filtro =============== 
				Efecto de que permanezca fixo con Scroll , el problema es en
				movil
	        -->
	       
			<nav class="col-sm-2" id="myScrollspy">
				<div data-offset-top="505">
					<h4> Cierres</h4>
					<h5> Opciones para una selección</h5>
					<ul class="nav nav-pills nav-stacked"> 
						<li><a href="#section2" onclick="metodoClick('VerCierre');";>Ver Cierre</a></li>
					</ul>
				
					
				</div>
				<div style="position: fixed" >
					<h4>Opciones administrador:</h4>
					<ul class=" nav nav-pills nav-stacked " > 
						<li><a  class="btn btn-warning btn-lg active" role="button" aria-pressed="true" href="../mod_cierres/CierreCaja.php?dedonde=cierre">Cierre Caja</a></li>
					</ul>
				</div>
			</nav>
			<div class="col-md-10">
					<p>
					 -Cierres encontrados BD local filtrados:
						<?php echo $CantidadRegistros;?>
					</p>
					<?php 	// Mostramos paginacion 
						echo $htmlPG;
				//enviamos por get palabras a buscar, las recogemos al inicio de la pagina
					?>
				<form action="./ListaCierres.php" method="GET" name="formBuscar">
					<div class="form-group ClaseBuscar">
						<label>Buscar en idCierre </label>
						<input type="text" name="buscar" value="">
						<input type="submit" value="buscar">
					</div>
				</form>
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
