<!DOCTYPE html>
<html>
    <head>
        <?php
	include './../../head.php';
	include ("./../../plugins/paginacion/paginacion.php");

	include ("./../mod_familias/ObjetoFamilias.php");
	include ("./ObjetoRecambio.php");
	// ===== Creamos objetos ================
	// familia y leemos familias para mostrar..
	$Dfamilias = new Familias;
	$Familias= $Dfamilias->LeerFamilias($BDRecambios);
	// Creamos objeto controlado comun.
	$Controler = new ControladorComun; // De momento no lo utilizamos.
	
	// Reinicio variables
	$palabraBuscar = ''; // por defecto
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
			$filtro =  " WHERE `Descripcion` LIKE '%".$palabraBuscar."%' or RefFabricanteCru LIKE '%".$palabraBuscar."%'";
		} 
	}
	
	// ===================  CONSULTAMOS CUANTOS RECAMBIOS HAY CON LA BUSQUEDA QUE PUSIMOS  =============   //	
	// Creamos objeto Recambio para realizar las consultas especificas..
	$Crecambios = new Recambio;
	// Creamos la vista a ver .. debería controlar esto para que lo cree constantemente..
	$vista = 'RecambiosTemporal'; // Vista temporal.
	$CrearVista = $Crecambios->CrearVistaRecambios($BDRecambios,$vista);
	$CantidadRegistros = $Controler->contarRegistro($BDRecambios,$vista,$filtro);	
	//~ echo '<pre>'.print_r($CantidadRegistros).'</pre>';
	$LinkBase = './ListaRecambios.php?';
	$OtrosParametros = $palabraBuscar;
	$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros);
	// Debug
	//~ echo '<pre>';
	//~ print_r($htmlPG);
	//~ echo '</pre>';
	// fin Debug
	
	
	// Ahora creamos array de resultado (Consulta).

	$paginasMulti = $PgActual-1;
	if ($paginasMulti > 0) {
	$desde = ($paginasMulti * $LimitePagina); 
	} else {
	$desde = 0;
	}
	// Realizamos consulta 
	if ($palabraBuscar !== '') {
		$filtro =  "WHERE `Descripcion` LIKE '%".$palabraBuscar."%' or RefFabricanteCru LIKE '%".$palabraBuscar."%'";
	} else {
		$filtro = '';
	}
	

	//~ $recambios  = $Crecambios->ConsultaRecambios($BDRecambios,$LimitePagina ,$desde,$filtro);
	$recambios  = $Crecambios->ObtenerRecambios($BDRecambios,$LimitePagina ,$desde,$filtro);
	
	/* Para depurar */
	//~ echo '<pre>';
	
	// Hay que activarlo en la ConsultaRecambios o ObtenerRecambios
	//~ $consulta1 = $recambios['consulta'];
	//~ echo $consulta1;
	
	//~ echo 'Total $CantidadRegistros='.$CantidadRegistros;
	//~ print_r($recambios) ;
	
	//~ echo '</pre>';
	
	
	
	
	?>
	<script>
	// Declaramos variables globales
	var checkID = [];
	var BRecambios ='';
	</script> 
    <!-- Cargamos fuciones de modulo. -->
   	<script src="<?php echo $HostNombre; ?>/modulos/mod_recambios/funciones.js"></script>

    
    <!-- Cargamos libreria control de teclado -->
	<script src="<?php echo $HostNombre; ?>/lib/shortcut.js"></script>
  
	
	<script>
	// Funciones para atajo de teclado.
	shortcut.add("Shift+V",function() {
		// Atajo de teclado para ver
		metodoClick('VerRecambio');
	});    
	    
	</script> 
    </head>

<body>
        <?php
        include './../../header.php';
        ?>
       
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Recambios: Editar, Añadir y Borrar Recambios </h2>
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
				<div data-spy="affix" data-offset-top="505">
				<h4> Recambios</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked">
					<li><a href="#section1">Añadir</a></li>
					<li><a href="#section2">Modificar</a></li>
					<li><a href="#section3">Borrar</a></li>
					<li><a href="#section3" onclick="metodoClick('VerRecambio');";>Ver</a></li>

				</ul>
				</div>
			
				<h4> Mostrar Familias</h4>
				<form name="FormFamilia">
					<input type="checkbox" name="cktodos" value="all"  onclick="metodoClick()">Todos
					<?php
					foreach ($Familias['items'] as $familia){ 
						echo '<h5>'. $familia['Nombre'].'</h5>';
						if ($familia['NumeroHijos'] > 0){
						?>
						<fieldset name="grupoCked" style="display:none;">
						<?php
						foreach ($familia['Hijos'] as $Nieto){
						
						?>
						<input type="checkbox" name="Nieto<?php echo $Nieto['id'];?>" value="">
						<?php echo $Nieto['Nombre'];?>
						
						<br/>
						<?php
						}
						?>
						</fieldset>
						<?php
						}
					
					}
					?>
				</form> 
			</nav>
			
				<!--==========  Contenido: Buscador, paginador y lista recambios ========== -->
			<div class="col-md-10">
					<p>
					 -Recambios encontrados BD local filtrados:
					 <?php echo $CantidadRegistros;?>
					 </p>
					<?php 	// Mostramos paginacion 
					  echo $htmlPG;
					?>
				
			<div class="form-group ClaseBuscar">
				<label>Buscar en descripcion / Referencia Fabricante</label>
				<input type="text" name="Buscar" value="">
				<input type="submit" name="BtnBuscar" value="Buscar" onclick="metodoClick('NuevaBusqueda');">
			</div>
			
                 <!-- TABLA DE PRODUCTOS -->
			<div>
			<table class="table table-striped">
				<thead>
					<tr>
						<th></th>
						<th>ID</th>
						<th>DESCRIPCION</th>
						<th>COSTE</th>
						<th>MARGEN</th>
						<th>PVP</th>
						<th>REF_FABR</th>
						<th>idWEB</th>

					</tr>
				</thead>
	
				<?php
				$checkRecam = 0;
				foreach ($recambios['items'] as $recambio){ 
					$checkRecam = $checkRecam + 1; 
				?>

				<tr>
					<td class="rowRecambio"><input type="checkbox" name="checkRec<?php echo $checkRecam;?>" value="<?php echo $recambio['id'];?>">
					</td>
					<td><?php echo $recambio['id']; ?></td>
					<td><?php echo $recambio['Descripcion']; ?></td>
					<td><?php echo $recambio['coste']; ?></td>
					<td><?php echo $recambio['margen']; ?></td>
					<td><?php echo $recambio['pvp']; ?></td>
					<td><?php echo $recambio['RefFabricanteCru'];?></td>
					<td>
						<?php
						if (isset ( $recambio['IDWeb']) ) {
						 echo $recambio['IDWeb'];
							if ($recambio['publicada'] == 0) {
								// Quiere decir que no esta publicado.
								?>
								<a title="No esta publicado"><span class="glyphicon glyphicon-remove-circle"></span></a>
								<?php
							}
						}
						 ?>
					</td>

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
