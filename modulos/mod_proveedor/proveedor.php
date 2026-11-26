<?php
include_once './../../inicial.php';
// La ficha guarda: sin sesión no guarda nada, y la cabecera común enseña el formulario de entrada
require_once $URLCom . '/clases/exigirSesion.php';
$conSesion = sesionCorrecta();
include_once $URLCom . '/modulos/mod_proveedor/funciones.php';
include_once $URLCom . '/controllers/Controladores.php';
include_once $URLCom . '/controllers/parametros.php';
include_once $URLCom . '/modulos/mod_proveedor/clases/ClaseProveedor.php';
$ClasesParametros = new ClaseParametros('parametros.xml');
$Controler = new ControladorComun;
$Controler->loadDbtpv($BDTpv);
$CProveedor = new ClaseProveedor();
$dedonde = "proveedor";
$id = 0;
$anio = $_SESSION['tiendaTpv']['ano'];
$errores = array();
$tablaHtml = array(); // Al ser nuevo, al crear ClienteUnico ya obtenemos array vacio.
$conf_defecto = $ClasesParametros->ArrayElementos('configuracion');
$configuracion = $Controler->obtenerConfiguracion($conf_defecto, 'mod_proveedor', $Usuario['id']);
$configuracion = $configuracion['incidencias'];
$estados = $CProveedor->estados;
if (isset($_GET['id'])) {
	$id = intval($_GET['id']); // Obtenemos id para modificar.
}
// Una ficha recién creada ya enseña su número: si se vuelve a guardar, es ese proveedor el que se modifica
if ($id == 0 && isset($_POST['Guardar']) && isset($_POST['idProveedor']) && intval($_POST['idProveedor']) > 0) {
	$id = intval($_POST['idProveedor']);
}
// Modificar un proveedor pide el permiso de modificar, el mismo con el que el listado ofrece el lápiz
$puedeGuardar = $conSesion;
if ($conSesion && $id > 0) {
	$vistaListado = array('modulo' => 'mod_proveedor', 'vista' => 'ListaProveedores.php');
	$puedeGuardar = isset($ClasePermisos) && $ClasePermisos->getAccion('modificar', $vistaListado) == 1;
}
$ProveedorUnico = $CProveedor->getProveedorCompleto($id);
if (isset($ProveedorUnico['error'])) {
	// Un número que no es de un proveedor, o un fallo al leerlo: se avisa y se termina
	echo '<!DOCTYPE html><html><head>';
	include_once $URLCom . '/head.php';
	echo '</head><body>';
	include_once $URLCom . '/modulos/mod_menu/menu.php';
	echo '<div class="alert alert-warning">No existe el proveedor ' . $id . '</div>';
	echo '</body></html>';
	exit;
}

$existeResumenAnual = false;
foreach ($ProveedorUnico['adjuntos'] as $key => $adjunto) {
	if (isset($adjunto['error'])) {
		$errores[] = array(
			'tipo' => 'danger',
			'mensaje' => 'ERROR EN LA BASE DE !<br/>Consulta:' . $adjunto['consulta']
		);
	} else {
		$tablaHtml[] = htmlTablaGeneral($adjunto['datos'], $HostNombre, $key);
		// Comprobamos si tiene facturas
		if ($key == 'facturas' && count($adjunto['datos']) > 0) {
			$existeResumenAnual = true;
		}
	}
}


if ($existeResumenAnual) {
	$resumenAnual = $CProveedor->obtenerResumenAnualProveedor($id);
	$resumenAnual = validarResumenAnual($resumenAnual, $anio);
	if (isset($resumenAnual['error'])) {
		$errores[] = array(
			'tipo' => 'danger',
			'mensaje' => 'ERROR EN LA BASE DE !<br/>Consulta:' . $resumenAnual['consulta']
		);
	} else {
		$tablaHtml[] = htmlTablaResumenAnual($resumenAnual);
	}
}
// Solo permitimos guarfar si realmente no hay errores.
// ya que consideramos que son graves y no podemos continuar. ( bueno a lo mejor.. :-)
if (count($errores) === 0) {
	if (isset($_POST['Guardar']) && !$puedeGuardar) {
		$errores[] = $CProveedor->montarAdvertencia('danger', 'No tiene permiso para guardar este proveedor');
	}
	if (isset($_POST['Guardar']) && $puedeGuardar) {
		// El proveedor que se guarda es el de la dirección, no el que diga el formulario
		$_POST['idProveedor'] = $id;
		$guardar = $CProveedor->guardarProveedor($_POST);
		// Lo que no se guardó se vuelve a enseñar como se envió, con sus documentos de antes
		$ProveedorUnico = array_merge($ProveedorUnico, $guardar['datos']);
		if ($guardar['estado'] === 'OK') {
			// Un proveedor recién creado ya tiene su número: la ficha se queda con él
			$id = intval($ProveedorUnico['idProveedor']);
			// Todo fue bien , volvemos a listado.
			// Dos posibles opciones deberíamos tener un parametro configuracion.
			// 1.- Redirecionar
			// header('Location: ListaProveedores.php');
			// 2.- Recargar datos modificados.
			$mensaje = 'Fue guardo correctamente';
			$errores[] = $CProveedor->montarAdvertencia('info', $mensaje);
		} else {
			// Hubo error grave, estado = KO
			$errores[] = $CProveedor->montarAdvertencia('danger', 'No se grabo por un error grave');
			// Los errores llegan como una lista de avisos, o como un solo aviso o un texto
			$erroresGuardar = $guardar['error'];
			if (!is_array($erroresGuardar) || isset($erroresGuardar['mensaje'])) {
				$erroresGuardar = array($erroresGuardar);
			}
			foreach ($erroresGuardar as $errorGuardar) {
				if (is_array($errorGuardar)) {
					$errores[] = $errorGuardar;
				} else {
					$errores[] = $CProveedor->montarAdvertencia('danger', $errorGuardar);
				}
			}
		}
	}
}

// Ahora $input_disabled
$input_disabled = 'disabled'; // Por defecto solo dejo ver.
$titulo = "Ver";
if (isset($_GET['accion'])) {
	if ($_GET['accion'] == "editar" && $puedeGuardar) {
		$input_disabled = '';
		$titulo = "Modificar";
	}
}
// Después de guardar, la ficha sigue abierta para editar
if (isset($_POST['Guardar']) && $puedeGuardar && $id > 0) {
	$input_disabled = '';
	$titulo = "Modificar";
}

// Montamos titulo y campos que no se cubren cuando es nuevo.
if ($id == 0) {
	$titulo = "Crear";
	$ProveedorUnico['idProveedor']  = '';
	$ProveedorUnico['fecha_creado'] = '';
	$input_disabled = $puedeGuardar ? '' : 'disabled';
}
// Los datos del proveedor, como texto para los campos de la ficha
$campos = array();
foreach ($CProveedor->arrayProveedor as $campo => $vacio) {
	$campos[$campo] = htmlspecialchars((string) ($ProveedorUnico[$campo] ?? ''), ENT_QUOTES);
}
// El estado guardado se enseña aunque no sea de los que se pueden elegir
if ($ProveedorUnico['estado'] !== '' && !in_array($ProveedorUnico['estado'], $estados, true)) {
	$estados[] = $ProveedorUnico['estado'];
}

?>
<!DOCTYPE html>
<html>

<head>
	<?php include_once $URLCom . '/head.php'; ?>

</head>

<body>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_incidencias/funciones.js"></script>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_proveedor/funciones.js?v=<?php echo filemtime(__DIR__ . '/funciones.js'); ?>"></script>
	<script type="text/javascript">
		<?php echo 'var configuracion=' . json_encode($configuracion) . ';'; ?>
	</script>
	<?php
	include_once $URLCom . '/modulos/mod_menu/menu.php';
	?>
	<div class="container">
		<?php
		if (isset($errores) && count($errores) > 0) {
			foreach ($errores as $error) {
				echo '<div class="alert alert-' . $error['tipo'] . '">'
					. '<strong>' . $error['tipo'] . ' </strong><br/> ';
				if (is_array($error['mensaje'])) {
					echo '<pre>';
					echo htmlspecialchars(print_r($error['mensaje'], true), ENT_QUOTES);
					echo '</pre>';
				} else {
					echo $error['mensaje'];
				}
				echo '</div>';
			}
		}
		?>
		<h1 class="text-center"> Proveedor: <?php echo $titulo; ?></h1>
		<form action="" method="post" name="formProveedor">
			<a class="text-ritght" href="./ListaProveedores.php">Volver Atrás</a>
			<a class="btn btn-warning" onclick="abrirModalIndicencia('<?php echo $dedonde; ?>' , configuracion , 0, <?php echo $id; ?>);">Añadir Incidencia </a>
			<?php if (intval($id) > 0 && puedeLimpiarProveedor($ClasePermisos ?? null, $Usuario ?? array())) { ?>
				<a class="btn btn-danger" onclick="comprobarLimpiezaProveedor(<?php echo intval($id); ?>);">Limpiar proveedor</a>
			<?php } ?>
			<?php
			if ($input_disabled == '') {
			?>
				<input type="submit" value="Guardar" name="Guardar" id="Guardar" class="btn btn-primary">
			<?php
			}
			?>
			<div class="row">
				<div class="col-md-1">
					<img src="./../../css/img/imgUsuario.png" style="width:100%;">
				</div>
				<div class="col-md-11">
					<h4>Proveedor ID:<input size="5" type="text" id="idProveedor" name="idProveedor" value="<?php echo htmlspecialchars((string) $ProveedorUnico['idProveedor'], ENT_QUOTES); ?>" readonly></h4>
				</div>
			</div>
			<div class="row">
				<div class="col-md-8">
					<div class="Datos">
						<div class="col-md-6 form-group">
							<label>Nombre comercial Proveedor:</label>
							<input type="text" id="nombrecomercial" name="nombrecomercial" <?php echo $input_disabled; ?> size="40" placeholder="nombre" value="<?php echo $campos['nombrecomercial']; ?>" required>
						</div>
						<div class="col-md-6 form-group">
							<label>Razon Social:</label> <!--//al enviar con POST los inputs se cogen con name="xx" PRE-->
							<input type="text" id="razonsocial" name="razonsocial" <?php echo $input_disabled; ?> size="40" placeholder="razon social" value="<?php echo $campos['razonsocial']; ?>">
						</div>
						<div class="col-md-6 form-group">
							<label>NIF:</label>
							<input type="text" id="nif" name="nif" <?php echo $input_disabled; ?> value="<?php echo $campos['nif']; ?>">
						</div>
						<div class="col-md-6 form-group">
							<label>Direccion:</label>
							<input type="text" id="direccion" name="direccion" <?php echo $input_disabled; ?> size="50" value="<?php echo $campos['direccion']; ?>">
						</div>
						<div class="col-md-6 form-group">
							<label>Telefono:</label>
							<input type="text" id="telefono" name="telefono" <?php echo $input_disabled; ?> value="<?php echo $campos['telefono']; ?>">
						</div>
						<div class="col-md-6 form-group">
							<label>Registro Sanitario:</label>
							<input type="text" id="registro_sanitario" name="registro_sanitario" <?php echo $input_disabled; ?> value="<?php echo $campos['registro_sanitario']; ?>">
						</div>
						<div class="col-md-6 form-group">
							<label>Movil:</label>
							<input type="text" id="movil" name="movil" <?php echo $input_disabled; ?> value="<?php echo $campos['movil']; ?>">
						</div>
						<div class="col-md-6 form-group">
							<label>Email:</label>
							<input type="text" id="email" name="email" <?php echo $input_disabled; ?> size="40" value="<?php echo $campos['email']; ?>">
						</div>
						<div class="col-md-6 form-group">
							<label>Fax:</label>
							<input type="text" id="fax" name="fax" <?php echo $input_disabled; ?> value="<?php echo $campos['fax']; ?>">
						</div>
						<div class="col-md-6 form-group">
							<label>Fecha alta:</label>
							<input type="text" id="fechaalta" name="fecha_creado" value="<?php echo htmlspecialchars((string) ($ProveedorUnico['fecha_creado'] ?? ''), ENT_QUOTES); ?>" readonly>
						</div>
						<div class="col-md-6 form-group">
							<label for="sel1">Estado:</label>
							<div class="col-md-6">
								<select class="form-control" name="estado" <?php echo $input_disabled; ?> id="sel1">
									<?php
									foreach ($estados as $estado) {
										$default = '';
										if ($ProveedorUnico['estado'] === $estado) {
											$default = "selected";
										}
										echo '<option size="10" value="' . htmlspecialchars($estado, ENT_QUOTES)
											. '" ' . $default . '>' . htmlspecialchars($estado, ENT_QUOTES) . '</option>';
									}
									?>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class="col-md-4">
					<div class="panel-group">
						<?php
						$num = 1; // Numero collapse;
						$titulo = 'Facturas';
						echo htmlPanelDesplegable($num, $titulo, $tablaHtml[0]);
						?>
						<?php
						$num = 2; // Numero collapse;
						$titulo = 'Albaranes';
						echo htmlPanelDesplegable($num, $titulo, $tablaHtml[1]);
						?>
						<?php
						$num = 3; // Numero collapse;
						$titulo = 'Pedidos';
						echo htmlPanelDesplegable($num, $titulo, $tablaHtml[2]);
						?>
						<?php
						if ($existeResumenAnual) {
							$num = 4; // Numero collapse;
							$titulo = 'Resumen Anual ' . $anio;
							echo htmlPanelDesplegable($num, $titulo, $tablaHtml[3]);
						}
						?>
					</div>
				</div>
			</div>
		</form>
		<?php // Incluimos paginas modales
		echo '<script src="' . $HostNombre . '/plugins/modal/func_modal.js"></script>';
		include $RutaServidor . '/' . $HostNombre . '/plugins/modal/ventanaModal.php';
		// hacemos comprobaciones de estilos
		?>
	</div>
</body>

</html>
