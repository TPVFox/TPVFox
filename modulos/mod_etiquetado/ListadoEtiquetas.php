<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once './../../inicial.php';
			 include_once $URLCom.'/head.php';
			include_once $URLCom.'/modulos/mod_etiquetado/funciones.php';
			include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';
			include_once $URLCom.'/controllers/Controladores.php';
			include_once ($URLCom.'/controllers/parametros.php');
			include_once $URLCom.'/modulos/mod_etiquetado/clases/modulo_etiquetado.php';
			
			$Controler = new ControladorComun; 
			$Controler->loadDbtpv($BDTpv);
			$CEtiquetas=new Modulo_etiquetado($BDTpv);
			$errores=array();
			$todosTemporal=$CEtiquetas->todosTemporal();
			if(isset($todosTemporal['error'])){
				$errores[1]=array ( 'tipo'=>'Danger!',
										 'dato' => $todosTemporal['consulta'],
										 'class'=>'alert alert-danger',
										 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
										 );
			}
		
	$Tienda = $_SESSION['tiendaTpv'];
		
	// ===========    Paginacion  ====================== //
	$NPaginado = new PluginClasePaginacion(__FILE__);
	$campos = array( 'b.articulo_name','a.num_lote');

	$NPaginado->SetCamposControler($Controler,$campos);
    $NPaginado->SetOrderConsulta('a.num_lote');
	// --- Ahora contamos registro que hay para es filtro --- //
	$filtro= $NPaginado->GetFiltroWhere('OR'); // mando operador para montar filtro ya que por defecto es AND

	$CantidadRegistros=0;
	// Obtenemos la cantidad registros 
	$a = $CEtiquetas->todasEtiquetasLimite($filtro);
		
	$CantidadRegistros = count($a);

	// --- Ahora envio a NPaginado la cantidad registros --- //
	$NPaginado->SetCantidadRegistros($CantidadRegistros);
	$htmlPG = $NPaginado->htmlPaginado();
	
	$etiquetasFiltro=$CEtiquetas->todasEtiquetasLimite($filtro.$NPaginado->GetLimitConsulta());
	if (isset($etiquetasFiltro['error'])){
		$errores[1]=array ( 'tipo'=>'Danger!',
				 'dato' => $etiquetasFiltro['consulta'],
				 'class'=>'alert alert-danger',
				 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
			 );
	}	
			
		?>
	</head>
	<body>
		<?php

	//~ include '../../header.php';
     include_once $URLCom.'/modulos/mod_menu/menu.php';
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
						<li><a  onclick="metodoClick('Imprimir','etiquetaCodBarras');";>Imprimir</a></li>
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
						<th>ETIQUETAS</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$checkUser = 0;
					foreach($etiquetasFiltro as $etiqueta){
						$checkUser = $checkUser + 1;
						if(isset($etiqueta['productos'])){
							$productos=$etiqueta['productos'];
							$productos=json_decode($productos, true);
						}else{
							$productos=array();
						}
						
							?>
						<tr>
					<td class="rowUsuario"><input type="checkbox" name="checkUsu<?php echo $checkUser;?>" value="<?php echo $etiqueta['id'];?>"></td>
					<td><?php echo $etiqueta['num_lote'];?></td>
					<td><?php echo $etiqueta['articulo_name'];?></td>
					<td><?php echo $etiqueta['fecha_env'];?></td>
					<td><?php echo $etiqueta['fecha_cad'];?></td>
					<td><?php echo $etiqueta['estado'];?></td>
					<td><?php echo count($productos);?></td>
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
