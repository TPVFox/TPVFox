<!DOCTYPE html>
<html>
    <head>
<?php 
include './../../head.php';
include_once $RutaServidor . $HostNombre .'/modulos/mod_configuracion/clases/ClaseIva.php';
$iva=new ClaseIva($BDTpv);
$todosIvas=$iva->cargarDatos();



?>
  <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
</head>
<body>
<?php 

  include './../../header.php';
?>
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Tablas Principales de la BD </h2>
			</div>
			<div class="col-md-4  text-center">
				<h4>Tabla IVAS</h4>
				<table class="table table-condensed">
						<thead>
							<tr>
								<th>ID</th>
								<th>Descripción</th>
								<th>Iva</th>
								<th>Recargo</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php 
							foreach($todosIvas['datos'] as $iva){
								echo '<tr>';
								echo '<td>'.$iva['idIva'].'</td>';
								echo '<td>'.$iva['descripcionIva'].'</td>';
								echo '<td>'.$iva['iva'].'</td>';
								echo '<td>'.$iva['recargo'].'</td>';
								echo '<td></td>';
								echo '</tr>';
							}
							
							?>
						</tbody>
					</table>
			</div>
			<div class="col-md-4  text-center">
				<h4>Formas de Pago</h4>
				<table class="table table-condensed">
						<thead>
							<tr>
								<th>ID</th>
								<th>Descripción</th>
								<th></th>
							</tr>
						</thead>
					</table>
			</div>
			<div class="col-md-4  text-center">
				<h4>Tipos de Vencimiento</h4>
				<table class="table table-condensed">
						<thead>
							<tr>
								<th>ID</th>
								<th>Descripción</th>
								<th></th>
							</tr>
						</thead>
					</table>
			</div>
		</div>
	</div>
</body>
