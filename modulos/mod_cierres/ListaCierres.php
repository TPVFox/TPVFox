<!DOCTYPE html>
<html>
    <head>
	<?php
	include_once './../../inicial.php';
    include $URLCom.'/head.php';
    include_once $URLCom.'/modulos/mod_cierres/clases/ClaseCierres.php';
    include_once $URLCom.'/modulos/mod_cierres/funciones.php';
	include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';
    $CCierres = new ClaseCierres;
    
    
   
	$fecha_dmYHora = 'd-m-Y H:m:s';
	$linkResumen = '<span title="Pon la fechas en filtro" class="glyphicon glyphicon-info-sign"> Ver Resumen </span>';
	$vista = 'cierres';
	// --- Inicializamos objteto de Paginado --- //
	$NPaginado = new PluginClasePaginacion(__FILE__);
	$campos = array( 'idCierre');
	$NPaginado->SetCamposControler($campos);
	// --- Ahora contamos registro que hay para es filtro --- //
	$filtro= $NPaginado->GetFiltroWhere();
	
	
    if (isset ($_GET['fecha1']) & isset($_GET['fecha2'])){
		$fecha1=$_GET['fecha1'];
		$fecha2=$_GET['fecha2'];
		// Montamos link para mostrar para poder ver resumen
		$linkResumen = '<a href="ResumenFechas.php?fecha1='.$fecha1.'&fecha2='.$fecha2.'">Ver Resumen</a>';
		// SI recibe por get las fechas añade el filtro a la consulta
		$filtro=' WHERE FechaCierre between "'.$fecha1. '" AND "'.$fecha2.'"';
	}
    $CantidadRegistros=count($CCierres->obtenerCierres($filtro));
    
	$NPaginado->SetCantidadRegistros($CantidadRegistros);
	$htmlPG = $NPaginado->htmlPaginado();
	$limite = $NPaginado->GetLimitConsulta(); // Me hace falta limite para obtener cierre.
	// -- Fin de paginado -- //
	
	$cierres = $CCierres->obtenerCierres($filtro,$limite);
	
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
        include_once $URLCom.'/modulos/mod_menu/menu.php';

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
                <?php
                if ($Usuario['group_id'] === '9'){
                ?>    
                <div class="col-md-12">
					<h4>Opciones administrador:</h4>
					<li><a href="#section2" onclick="metodoClick('EliminarCierre');";>Eliminar Cierre</a></li>
				</div>
                <?php
                }
                ?>
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
						<input type="text" name="fecha1" id="fecha1" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" placeholder='yyyy-mm-dd'  title=" Formato de entrada yyyy-mm-dd">
						<input type="text" name="fecha2" id="fecha2" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" placeholder='yyyy-mm-dd' title=" Formato de entrada yyyy-mm-dd">
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
				$check = 0;
				foreach ($cierres as $cierre){ 
					$check ++ ; 
				?>

				<tr>
					<td class="rowUsuario">
						<input type="checkbox" name="checkUsu<?php echo $check;?>" value="<?php echo $cierre['idCierre'];?>">
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
			
			</div>
		</div>
	</div>
    </div>
		
</body>
</html>
