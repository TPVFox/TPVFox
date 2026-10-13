<!DOCTYPE html>
<html>

<head>
	<?php
	include_once './../../inicial.php';
	include_once $URLCom . '/head.php';
	include_once $URLCom . '/controllers/Controladores.php';

	include_once $URLCom . '/modulos/mod_configuracion/clases/ClaseIva.php';
	include_once $URLCom . '/modulos/mod_configuracion/clases/ClaseFormasPago.php';
	include_once $URLCom . '/modulos/mod_configuracion/clases/ClaseVencimiento.php';
	include_once $URLCom . '/modulos/mod_configuracion/funciones.php';
	include_once $URLCom . '/modulos/mod_configuracion/funcionesTiposRelacion.php';
	$iva = new ClaseIva($BDTpv);
	$formas = new ClaseFormasPago($BDTpv);
	$Vencimiento = new ClaseVencimiento($BDTpv);
	$todosIvas = $iva->cargarDatos();
	$todosFormas = $formas->cargarDatos();
	$todosVencimiento = $Vencimiento->cargarDatos();
	$bloqueTipos = datosBloqueTiposRelacion();



	?>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_configuracion/funciones.js"></script>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_configuracion/tiposRelacion.js?v=<?php echo filemtime(__DIR__ . '/tiposRelacion.js'); ?>"></script>
	<script src="<?php echo $HostNombre; ?>/controllers/global.js"></script>
</head>

<body>
	<?php

	//~ include './../../header.php';
	include_once $URLCom . '/modulos/mod_menu/menu.php';
	?>
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
				<h2> Tablas Principales de la BD </h2>
			</div>
			<div class="col-md-4  text-center">
				<h4>Tabla IVAS <a class="glyphicon glyphicon-plus" onclick="abrirmodal(0, 'iva')"></a></h4>
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
						foreach ($todosIvas['datos'] as $iva) {
							echo '<tr>';
							echo '<td>' . $iva['idIva'] . '</td>';
							echo '<td>' . $iva['descripcionIva'] . '</td>';
							echo '<td>' . $iva['iva'] . '</td>';
							echo '<td>' . $iva['recargo'] . '</td>';
							echo '<td><a class="glyphicon glyphicon-pencil" onclick="abrirmodal(' . $iva['idIva'] . ', ' . "'" . 'iva' . "'" . ')"></a></td>';
							echo '</tr>';
						}

						?>
					</tbody>
				</table>
			</div>
			<div class="col-md-4  text-center">
				<h4>Formas de Pago <a class="glyphicon glyphicon-plus" onclick="abrirmodal(0, 'forma')"></a></h4>
				<table class="table table-condensed">
					<thead>
						<tr>
							<th>ID</th>
							<th>Descripción</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($todosFormas['datos'] as $forma) {
							echo '<tr>';
							echo '<td>' . $forma['id'] . '</td>';
							echo '<td>' . $forma['descripcion'] . '</td>';
							echo '<td><a class="glyphicon glyphicon-pencil" onclick="abrirmodal(' . $forma['id'] . ', ' . "'" . 'forma' . "'" . ')"></a></td>';
							echo '</tr>';
						}

						?>
					</tbody>
				</table>
			</div>
			<div class="col-md-4  text-center">
				<h4>Tipos de Vencimiento <a class="glyphicon glyphicon-plus" onclick="abrirmodal(0, 'vencimiento')"></a></h4>
				<table class="table table-condensed">
					<thead>
						<tr>
							<th>ID</th>
							<th>Descripción</th>
							<th>Días</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($todosVencimiento['datos'] as $venci) {
							echo '<tr>';
							echo '<td>' . $venci['id'] . '</td>';
							echo '<td>' . $venci['descripcion'] . '</td>';
							echo '<td>' . $venci['dias'] . '</td>';
							echo '<td><a class="glyphicon glyphicon-pencil" onclick="abrirmodal(' . $venci['id'] . ', ' . "'" . 'vencimiento' . "'" . ')"></a></td>';
							echo '</tr>';
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12 text-center" id="bloqueTiposRelacion">
				<h4>Tipos de relación
					<?php
					if ($bloqueTipos['aviso'] === '' && permisoTipoRelacion($ClasePermisos, 'crear_tipo_relacion')) {
						echo '<a class="glyphicon glyphicon-plus" id="btnTipoRelacionNuevo" onclick="abrirModalTipoRelacion(0)"></a>';
					}
					?>
				</h4>
				<?php
				if ($bloqueTipos['aviso'] !== '') {
					echo '<div class="alert alert-warning">' . htmlspecialchars($bloqueTipos['aviso']) . '</div>';
				} elseif (count($bloqueTipos['tipos']) === 0) {
					echo '<div class="alert alert-info">Todavía no hay ningún tipo de relación.</div>';
				} else {
				?>
					<table class="table table-condensed" id="tablaTiposRelacion">
						<thead>
							<tr>
								<th>ID</th>
								<th>Nombre</th>
								<th>Forma</th>
								<th>Cantidad</th>
								<th>Estado</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($bloqueTipos['tipos'] as $tipo) {
								echo '<tr>';
								echo '<td>' . (int) $tipo['id'] . '</td>';
								echo '<td>' . htmlspecialchars($tipo['nombre']) . '</td>';
								echo '<td>' . htmlspecialchars(nombreDeLaForma($tipo['forma'])) . '</td>';
								echo '<td>' . ($tipo['llevaCantidad'] == 1 ? 'Sí' : 'No') . '</td>';
								echo '<td>' . htmlspecialchars($tipo['estado']) . '</td>';
								echo '<td><a class="glyphicon glyphicon-pencil" onclick="abrirModalTipoRelacion(' . (int) $tipo['id'] . ')"></a></td>';
								echo '</tr>';
							}
							?>
						</tbody>
					</table>
				<?php
				}
				?>
			</div>
		</div>
	</div>
	<?php // Incluimos paginas modales
	echo '<script src="' . $HostNombre . '/plugins/modal/func_modal.js"></script>';
	include $RutaServidor . '/' . $HostNombre . '/plugins/modal/ventanaModal.php';
	// hacemos comprobaciones de estilos
	?>
</body>
