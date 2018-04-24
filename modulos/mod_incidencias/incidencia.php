<!DOCTYPE html>
<html>
<head>
	<?php 
	include './../../head.php';
	include './funciones.php';
	include 'ClaseIncidencia.php';
	$Controler = new ControladorComun; 
	$CIncidencia= new incidencia($BDTpv);
	
	
	?>
</head>
<body>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
<?php
	include '../../header.php';
?>
<script type="text/javascript">
// Objetos cajas de tpv
<?php echo $VarJS;?>
     function anular(e) {
          tecla = (document.all) ? e.keyCode : e.which;
          return (tecla != 13);
      }
</script>
<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
	<div class="container">
		<form action="" method="post" name="formIncidencia" onkeypress="return anular(event)">
			<a  href="./ListadoIncidencias.php">Volver Atrás</a>
			<input type="submit" value="Guardar" name="Guardar" id="bGuardar">
			<div class="col-md-12" >
				<div class="col-md-4">
					<strong>Fecha:</strong><br>
					<input type="date" name="fecha" id="fecha" size="10" data-obj= "cajaFecha"  value="<?php echo $fecha;?>" onkeydown="controlEventos(event)" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" placeholder='yyyy-mm-dd' title=" Formato de entrada yyyy-mm-dd">
				</div>
				<div class="col-md-4">
					<strong>Número de incidencia:</strong><br>
					<span id="numeroInci"> <input type="text" id="numInci" name="numInci" value="<?php echo $estado;?>" size="10" readonly></span><br>
				</div>
			
				<div class="col-md-4">
					<strong>Estado:</strong><br>
					<input type="text" id="Estado" name="Estado" value="<?php echo $Usuario['nombre'];?>" size="10" readonly>
				</div>
			</div>
		</form>
	</div>
</body>
</html>
