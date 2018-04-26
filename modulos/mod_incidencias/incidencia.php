<!DOCTYPE html>
<html>
<head>
	<?php 
	include './../../head.php';
	include './funciones.php';
	include ("./../../controllers/Controladores.php");
	include_once ($RutaServidor.$HostNombre.'/controllers/parametros.php');
	$ClasesParametros = new ClaseParametros('parametros.xml');
	$Controler = new ControladorComun; 
	include 'ClaseIncidencia.php';
	$Controler = new ControladorComun; 
	$Controler->loadDbtpv($BDTpv);
	$CIncidencia= new incidencia($BDTpv);
	$Usuario = $_SESSION['usuarioTpv'];
	$dedonde='incidencia';
	$parametros = $ClasesParametros->getRoot();
	$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
	$configuracion = $Controler->obtenerConfiguracion($conf_defecto,'mod_incidencias',$Usuario['id']);
	
	$configuracion=json_decode(json_encode($configuracion),true);
	$configuracion=$configuracion['incidencias'];
	$id="";
	
	
	if(isset($_GET['id'])){
		$id=$_GET['id'];
		$datosIncidencias=$CIncidencia->incidenciasNumero($_GET['id']);
		echo '<pre>';
		print_r($datosIncidencias);
		echo '</pre>';
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
	include '../../header.php';
?>
<script type="text/javascript">
// Objetos cajas de tpv
     function anular(e) {
          tecla = (document.all) ? e.keyCode : e.which;
          return (tecla != 13);
      }
</script>
<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
	<div class="container">
		
			<h2 class="text-center">Datos de la incidencia Nº <?php echo $datosIncidencia['num_incidencia'];?></h2>
			
			<a  href="./ListadoIncidencias.php">Volver Atrás</a><br><br>
			<a onclick="abrirIndicencia('<?php echo $dedonde;?>' , <?php echo $Usuario['id'];?>, configuracion, <?php echo $id;?>);">Responder incidencia</a><br><br>
			<?php 
			
			foreach($datosIncidencias as $datosIncidencia){
			?>
			<div class="col-md-12" >
				<div class="col-md-2">
					<strong>Fecha:</strong><br>
					<input type="date" name="fecha" id="fecha" size="10"   value="<?php echo $datosIncidencia['fecha_creacion'];?>" onkeydown="controlEventos(event)" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" placeholder='yyyy-mm-dd' title=" Formato de entrada yyyy-mm-dd" readonly>
				</div>
				<div class="col-md-2">
					<strong>Número de incidencia:</strong><br>
					<span id="numeroInci"> <input type="text" id="numInci" name="numInci" value="<?php echo $datosIncidencia['num_incidencia'];?>" size="10" readonly></span><br>
				</div>
			
				<div class="col-md-2">
					<strong>Estado:</strong><br>
					<input type="text" id="Estado" name="Estado" value="<?php echo  $datosIncidencia['estado'];?>" size="10" readonly>
				</div>
				<div class="col-md-2">
					<strong>Dedonde:</strong><br>
					<input type="text" id="dedonde" name="dedonde" value="<?php echo  $datosIncidencia['dedonde'];?>" size="10" readonly>
				</div>
				<div class="col-md-2">
					<strong>Usuario:</strong><br>
					<?php 
					
					
					?>
					<input type="text" id="usuario" name="usuario" value="<?php echo  $datosIncidencia['username'];?>" size="10" readonly>
				</div>
			</div>
			<div class="col-md-12" >
				<div class="col-md-2">
					<strong>Mensaje:</strong><br>
					<input type="text" name="mensaje" id="mensaje"   size="50" value="<?php echo $datosIncidencia['mensaje'];?>" readonly>
					<hr/>
				</div>
			</div>
			<div class="col-md-12" >
				<div class="col-md-2">
					<strong>Datos:</strong><br>
					<input type="text" name="datos" id="datos"   size="50" value="<?php echo $datosIncidencia['datos'];?>" readonly>
					<hr/>
				</div>
			</div>
		
		<?php 
	}
		?>
	</div>
	<?php // Incluimos paginas modales
	echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
// hacemos comprobaciones de estilos 
?>
</body>
</html>
