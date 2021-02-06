<!DOCTYPE html>
<html>
<head>
	<?php 
	include_once ("./../../inicial.php");
	include_once $URLCom.'/head.php';
	include_once $URLCom.'/controllers/Controladores.php';
	include_once ( $URLCom.'/controllers/parametros.php');
	include_once $URLCom.'/modulos/mod_incidencias/clases/ClaseIncidencia.php';
	$ClasesParametros = new ClaseParametros('parametros.xml');
	
	$Controler = new ControladorComun; 
	
	$Controler = new ControladorComun; 
	$Controler->loadDbtpv($BDTpv);
	$CIncidencia= new ClaseIncidencia($BDTpv);
	
	$dedonde='incidencia';
	$parametros = $ClasesParametros->getRoot();
	$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
	$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_incidencias',$Usuario['id']);
	
	$configuracion=json_decode(json_encode($configuracion),true);
	$configuracion=$configuracion['incidencias'];
	$id="";
	
	
	if(isset($_GET['id'])){
		//Si recibe el numero de la incidencia carga todas las incidencias de ese número determminado
		$id=$_GET['id'];
		$datosIncidencias=$CIncidencia->incidenciasNumero($_GET['id']);
	}
	?>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
    <script type="text/javascript" >
		<?php echo 'var configuracion='.json_encode($configuracion).';';?>	
	</script>
	
</head>
<body>
	<?php
	//~ include '../../header.php';
     include_once $URLCom.'/modulos/mod_menu/menu.php';
	?>
	<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
	<div class="container">
		<h2 class="text-center">Datos de la incidencia Nº <?php echo $id;?></h2>
		<div class="col-md-2">
		<a  href="./ListadoIncidencias.php">Volver Atrás</a><br><br>
		<a onclick="abrirModalIndicencia('<?php echo $dedonde;?>' , configuracion, <?php echo $id;?>);">Responder incidencia</a>
		</div>
		<div class="col-md-10" >
		<!-- Contenedor de incidencia --> 
		<?php 
		foreach($datosIncidencias as $datosIncidencia){
			
			$datosPrincipales=json_decode($datosIncidencia['datos']);
		
			if(isset($datosPrincipales->usuarioSelec)){
					$sql='select * from usuarios where id='.$datosPrincipales->usuarioSelec;
					
					$smt = $BDTpv->query($sql);
					if ($result = $smt->fetch_assoc () ){
					$usuarioSelect=$result;
					}
			}
			$fecha =date_format(date_create($datosIncidencia['fecha_creacion']), 'Y-m-d');
			
			?>
			<div class="col-md-12">
				<h4>Fecha:<?php echo $datosIncidencia['fecha_creacion'];?></h2>
				<div class ="col-md-6">
					<div class="col-md-4">
						<strong>Estado:</strong><br>
						<input type="text" id="Estado" name="Estado" value="<?php echo  $datosIncidencia['estado'];?>" size="10" readonly>
					</div>
					
					<div class="col-md-4">
						<strong>Desde donde:</strong><br>
						<input type="text" id="dedonde" name="dedonde" value="<?php echo  $datosIncidencia['dedonde'];?>" size="15" readonly>
					</div>
					
					<?php 
					if (isset($usuarioSelect['id'])){
					?>
					<div class="col-md-4">
						<strong>Usuario Asignado:</strong><br>
						<input type="text" id="usuarioAsig" name="usuarioAsig" value="<?php echo  $usuarioSelect['username'];?>" size="10" readonly>
					</div>
					<?php 
					}
					?>
					<div class="col-md-12">
						<strong>Datos:</strong><br>
						<textarea rows="4" cols="60" readonly><?php echo $datosIncidencia['datos'];?></textarea>
					</div>
				</div>
				<div class="col-md-6" >
					<div class="col-md-12">
						<strong>Creado por :</strong><br>
						<input type="text" id="usuario" name="usuario" value="<?php echo  $datosIncidencia['username'];?>" size="10" readonly>
					</div>
					<div class="col-md-12">
						<strong>Mensaje:</strong><br>
						<textarea rows="4" cols="60" readonly><?php echo $datosIncidencia['mensaje'];?></textarea>
					</div>
				</div>
				 <div class="col-xs-12 col-sm-12 "><hr></div>
			</div>
		<?php
		}
		?>
		</div>
	</div>
	<?php // Incluimos paginas modales
	echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
	include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
	?>
</body>
</html>
