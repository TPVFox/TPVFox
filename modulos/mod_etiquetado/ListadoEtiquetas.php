<!DOCTYPE html>
<html>
	<head>
		<?php 
			include './../../head.php';
			include './funciones.php';
			include ("./../../plugins/paginacion/paginacion.php");
			include ("./../../controllers/Controladores.php");
			include_once ($RutaServidor.$HostNombre.'/controllers/parametros.php');
			include 'clases/modulo_etiquetado.php';
			$Controler = new ControladorComun; 
			$Controler->loadDbtpv($BDTpv);
			$CEtiquetas=new Modulo_etiquetado($BDTpv);
			
			$todosTemporal=$CEtiquetas->todosTemporal();
			if(isset($todosTemporal['error'])){
				$errores[1]=array ( 'tipo'=>'Danger!',
										 'dato' => $todosTemporal['consulta'],
										 'class'=>'alert alert-danger',
										 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
										 );
			}
			//~ echo '<pre>';
			//~ print_r($todosTemporal);
			//~ echo '</pre>'; 
			$palabraBuscar=array();
			$stringPalabras='';
			$PgActual = 1; // por defecto.
			$LimitePagina = 30; // por defecto.
			$filtro = ''; // por defecto
			$errores=array();
			
			if (isset($_GET['pagina'])) {
				$PgActual = $_GET['pagina'];
			}
			if (isset($_GET['buscar'])) {  
				//recibo un string con 1 o mas palabras
				$stringPalabras = $_GET['buscar'];
				$palabraBuscar = explode(' ',$_GET['buscar']); 
			} 
			$LinkBase = './ListadoEtiquetas.php?';
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
					$campo = array( 'b.articulo_name');
					$NuevoWhere = $Controler->ConstructorLike($campo, $stringPalabras, 'OR');
					$NuevoRango=$Controler->ConstructorLimitOffset($LimitePagina, $desde);
					$OtrosParametros=$stringPalabras;
					$WhereLimite['filtro']='WHERE '.$NuevoWhere;
			}
			$CantidadRegistros=count($CEtiquetas->todasEtiquetasLimite($WhereLimite['filtro']));
			$WhereLimite['rango']=$NuevoRango;
			$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros);
			if ($stringPalabras !== '' ){
				$filtro = $WhereLimite['filtro']." ORDER BY a.fecha_env desc  ".$WhereLimite['rango'];
			} else {
				$filtro= " ORDER BY a.fecha_env desc  LIMIT ".$LimitePagina." OFFSET ".$desde;
			}	
			$etiquetasFiltro=$CEtiquetas->todasEtiquetasLimite($filtro);
			if (isset($etiquetasFiltro['error'])){
				$errores[1]=array ( 'tipo'=>'Danger!',
										 'dato' => $etiquetasFiltro['consulta'],
										 'class'=>'alert alert-danger',
										 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
										 );
			}	
			//~ echo '<pre>';
			//~ print_r($etiquetasFiltro);
			//~ echo '</pre>';	
		?>
	</head>
	<body>
		<?php

	include '../../header.php';
	if (isset($errores)){
		foreach($errores as $error){
				echo '<div class="'.$error['class'].'">'
				. '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br>Sentencia: '.$error['dato']
				. '</div>';
		}
	}
	?>
		<script src="<?php echo $HostNombre; ?>/modulos/mod_etiquetado/funciones.js"></script>
		<script src="<?php echo $HostNombre; ?>/controllers/global.js"></script>    
		<div class="container">
			<div class="row">
				<div class="col-md-12 text-center">
					<h2> Lotes Etiquetas: Añadir Lotes </h2>
				</div>
				<nav class="col-sm-4">
					<h4> Lotes</h4>
					<h5> Opciones para una selección</h5>
					<ul class="nav nav-pills nav-stacked"> 
						<li><a onclick="metodoClick('Agregar' , 'etiquetaCodBarras')">Añadir</a></li>
						<li><a  onclick="metodoClick('Ver','etiquetaCodBarras');";>Modificar</a></li>
					</ul>
					<h4 class="text-center"> Lotes Abiertos</h4>
					<table class="table table-striped">
						<thead>
							<th>Nª Temp</th>
							<th>Lote</th>
							<th>Fecha</th>
							<th>Producto</th>
						</thead>
						<tbody>
						<?php 
						foreach($todosTemporal as $temporal){
							?>
							<tr>
								<td><a href="etiquetaCodBarras.php?tActual=<?php echo $temporal['id'];?>"><?php echo $temporal['id'];?></a></td>
								<td><?php echo $temporal['num_lote'];?></td>
								<td><?php echo $temporal['fecha_env'];?></td>
								<td><?php echo $temporal['articulo_name'];?></td>
							</tr>
							<?php
						}
						?>
						</tbody>
					</table>
				</nav>
				<div class="col-md-8">
					<p>
					 -Lotes de etiquetas encontrados  BD local filtrados:
						<?php echo $CantidadRegistros; ?>
					</p>
					<?php 	// Mostramos paginacion 
						echo $htmlPG;
				//enviamos por get palabras a buscar, las recogemos al inicio de la pagina
					?>
					<table class="table table-bordered table-hover">
						<thead>
					<tr>
						<th></th>
						<th>Nª LOTE</th>
						<th>PRODUCTO</th>
						<th>FECHA ENV</th>
						<th>FECHA CAD</th>
						<th>ESTADO</th>
						<th>IMPRIMIR</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$checkUser = 0;
					foreach($etiquetasFiltro as $etiqueta){
						$checkUser = $checkUser + 1;
						
						?>
						<tr>
					<td class="rowUsuario"><input type="checkbox" name="checkUsu<?php echo $checkUser;?>" value="<?php echo $etiqueta['id'];?>"></td>
					<td><?php echo $etiqueta['num_lote'];?></td>
					<td><?php echo $etiqueta['articulo_name'];?></td>
					<td><?php echo $etiqueta['fecha_env'];?></td>
					<td><?php echo $etiqueta['fecha_cad'];?></td>
					<td><?php echo $etiqueta['estado'];?></td>
					<td></td>
					</tr>
						<?php
					}
					
					?>
					
				
				</tbody>
					</table>
					</div>
			</div>
		</div>	

	</body>
</html>
