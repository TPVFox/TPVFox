<!DOCTYPE html>
<html>
<head>
	<?php 
	include_once ("./../../inicial.php");
	include_once $URLCom.'/head.php';
	include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';
	include_once $URLCom.'/controllers/Controladores.php';
	include_once ($URLCom.'/controllers/parametros.php');
	include_once $URLCom.'/modulos/mod_incidencias/clases/ClaseIncidencia.php';
	$Controler = new ControladorComun; 
	$Controler->loadDbtpv($BDTpv);
	$CIncidencia= new ClaseIncidencia($BDTpv);
	
	$ClasesParametros = new ClaseParametros('parametros.xml');
	$parametros = $ClasesParametros->getRoot();
	// Cargamos configuracion modulo tanto de parametros (por defecto) como si existen en tabla modulo_configuracion 
	$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
	
	$errores=array();
	$dedonde='listado Incidencias';
	// --- Inicializamos objteto de Paginado --- //
	$NPaginado = new PluginClasePaginacion(__FILE__);
	$campos = array( 'a.Numalbpro','b.nombrecomercial');
	$NPaginado->SetCamposControler($Controler,$campos);
	// --- Ahora contamos registro que hay para es filtro --- //
	$filtro= $NPaginado->GetFiltroWhere();
	$CantidadRegistros=0;
	if ( $NPaginado->GetFiltroWhere() !== ''){
		// Contar con  filtro..
		$CantidadRegistros=count($CIncidencia->todasIncidenciasLimite($NPaginado->GetFiltroWhere()));
	} else {
		// Obtengo num_registros sin filtro.
		$CantidadRegistros = $CIncidencia->GetNumRows(); 
	}
	$NPaginado->SetCantidadRegistros($CantidadRegistros);
	$htmlPG = $NPaginado->htmlPaginado();	
	// -- Fin de paginado -- //
	
		
	$parametros = $ClasesParametros->getRoot();
	$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
	$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_incidencias',$Usuario['id']);
	
	$configuracion=json_decode(json_encode($configuracion),true);
	$configuracion=$configuracion['incidencias'];

	$incidenciasFiltro= $CIncidencia->todasIncidenciasLimite($filtro.$NPaginado->GetLimitConsulta());
	if (isset($incidenciasFiltro['error'])){
		$errores[1]=array ( 'tipo'=>'Danger!',
								 'dato' => $incidenciasFiltro['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
	}
	?>
</head>
<body>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script>    
    <script type="text/javascript" >
		<?php echo 'var configuracion='.json_encode($configuracion).';';?>	
	</script>
     <?php

	//~ include '../../header.php';
     include_once $URLCom.'/modulos/mod_menu/menu.php';
	//Mostrar los errores que tiene los sql;
	if (isset($errores)){
		foreach($errores as $error){
				echo '<div class="'.$error['class'].'">'
				. '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br>Sentencia: '.$error['dato']
				. '</div>';
		}
	}
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
				
					<li><a onclick="abrirModalIndicencia('<?php echo $dedonde;?>' , configuracion);">Añadir</a></li>
				
					<li><a href="#section2" onclick="metodoClick('Ver','incidencia');";>Modificar</a></li>
				
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
				<?php 
				$checkUser = 0;
				$numInci=1;
				foreach($incidenciasFiltro['NItems'] as $incidencia){
							$checkUser = $checkUser + 1;
							$date=date_create($incidencia['fecha']);
							//Contar el número de incidencias que tiene un número determinado
							$numInci=count($CIncidencia->incidenciasNumero($incidencia['num_incidencia']));
					?>
					<tr>
					<td class="rowUsuario"><input type="checkbox" name="checkUsu<?php echo $checkUser;?>" value="<?php echo $incidencia['num_incidencia'];?>">
					<td><?php echo $incidencia['num_incidencia'];?>
					<?php 
						if($numInci>1){
							?>
							<div style="float:right">
							<a  class="glyphicon glyphicon-envelope"></a> <?php echo $numInci;?>
							</div>
							<?php
						}
					
					?>
					</td>
					<td><?php echo date_format($date,'Y-m-d');?></td>
					<td><?php echo $incidencia['nombre'];?></td>
					<td><?php echo $incidencia['dedonde'];?></td>
					<td><?php echo $incidencia['mensaje'];?></td>
					<td><?php echo $incidencia['estado'];?></td>
					</tr>
					<?php
				}
				
				?>
				</tbody>
				</table>
			</div>
		
		</div>	
			<?php // Incluimos paginas modales
			echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
			include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
			// hacemos comprobaciones de estilos 
			?>
</body>
</html>
