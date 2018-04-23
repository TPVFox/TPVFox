<!DOCTYPE html>
<html>
<head>
	<?php 
	include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	include 'ClaseIncidencia.php';
	$Controler = new ControladorComun; 
	$CIncidencia= new incidencia($BDTpv);
	$palabraBuscar=array();
	$stringPalabras='';
	$PgActual = 1; // por defecto.
	$LimitePagina = 30; // por defecto.
	$filtro = ''; // por defecto
	if (isset($_GET['pagina'])) {
		$PgActual = $_GET['pagina'];
	}
	if (isset($_GET['buscar'])) {  
		//recibo un string con 1 o mas palabras
		$stringPalabras = $_GET['buscar'];
		$palabraBuscar = explode(' ',$_GET['buscar']); 
	} 
	$LinkBase = './ListadoIncidencias.php?';
	$OtrosParametros = '';
	$paginasMulti = $PgActual-1;
	if ($paginasMulti > 0) {
		$desde = ($paginasMulti * $LimitePagina); 
	} else {
		$desde = 0;
	}
	$WhereLimite = array();
	$WhereLimite['filtro'] = '';
	$NuevoRango = '';
	if ($stringPalabras !== '' ){
			$campo = array( 'a.Numalbpro','b.nombrecomercial');
			$NuevoWhere = $Controler->ConstructorLike($campo, $stringPalabras, 'OR');
			$NuevoRango=$Controler->ConstructorLimitOffset($LimitePagina, $desde);
			$OtrosParametros=$stringPalabras;
			$WhereLimite['filtro']='WHERE '.$NuevoWhere;
		}
	$CantidadRegistros=count($CIncidencia->todasIncidenciasLimite($WhereLimite['filtro']));
	$WhereLimite['rango']=$NuevoRango;
	$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros);

	if ($stringPalabras !== '' ){
			$filtro = $WhereLimite['filtro']." a.num_incidencia desc  ".$WhereLimite['rango'];
		} else {
			$filtro= " a.num_incidencia desc  LIMIT ".$LimitePagina." OFFSET ".$desde;
		}
	$incidenciasFiltro=$CIncidencia->todasIncidenciasLimite($filtro);
		
	?>
</head>
<body>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script>    
     <?php

	include '../../header.php';
	?>
		<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Listado de incidencias </h2>
				</div>
					<nav class="col-sm-2">
				<h4> Incidencias</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
				
					<li><a href="#section2">Añadir</a></li>
				
					<li><a href="#section2">Modificar</a></li>
				
				</ul>
				<div class="col-md-12">
					</div>
				</nav>
					
			
		<div class="col-md-10">
					<p>
					 -Incidencias encontradas BD local filtrados:
						<?php echo $CantidadRegistros; ?>
					</p>
					<?php 	// Mostramos paginacion 
						echo $htmlPG;
					?>
					
					<div>
			<table class="table table-bordered table-hover">
				<thead>
					<tr>
						<th></th>
						
						<th>Nª INCIDENCIA</th>
						<th>FECHA</th>
						<th>USUARIO</th>
						<th>DE DONDE</th>
						<th>MENSAJE</th>
						<th>ESTADO</th>
					</tr>
				</thead>
				<tbody>
				
				</tbody>
				</table>
			</div>
		
		</div>	
			</nav>
</body>
</html>
