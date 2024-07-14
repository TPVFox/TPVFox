
<?php
include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_tareas_cron/clases/CTareasCron.php';

include_once $URLCom . '/clases/CorreoElectronico.php';

$CTareasCron = new CTareasCron();
if (isset($_GET['accion'])) {
    $accion = $_GET['accion'];

    switch ($accion) {
        case 'correo':
            $resultadoCorreo = CorreoElectronico::enviar('informatica@alagoro.com', 'Este es un correo automatico', 'Importante para el resto de tu vida');
            break;
    }
}
$tareasCron = $CTareasCron->list();
?>

<!DOCTYPE html>
<html>
    <head>
    <?php include_once $URLCom . '/head.php';?>
    </head>
<body>
        <?php
include_once $URLCom . '/modulos/mod_menu/menu.php';
?>

	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Listados de tareas </h2>
			</div>

			<nav class="col-sm-2" id="myScrollspy">
				<div data-offset-top="505">
				<ul class="nav nav-pills nav-stacked">
					<li><a href="TareaCron.php">Nueva</a></li>
				</ul>
				</div>

			</nav>
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Nombre</th>
                            <th>Período (minutos)</th>
                            <th>Clase</th>
                            <th>Última ejecución</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                <?php foreach ($tareasCron as $tareaCron) {?>
                    <tr>
                        <td><input class="rowCheck" type="checkbox" name="informe" value="1">
                        <td><?php echo $tareaCron['nombre'] ?></td>
                        <td><?php echo $tareaCron['periodo'] ?> </td>
                        <td><?php echo $tareaCron['nombre_clase'] ?></td>
                        <td><?php echo $tareaCron['ultima_ejecucion'] ?> </td>
                        <td><?php echo $CTareasCron->tareasCron()->textoEstado($tareaCron['estado']) ?> </td>
                        <td>
                        <a class="btn btn-primary" href="TareaCron.php?accion=modificar&tarea=<?php echo $tareaCron['id'] ?>">Edit</a>
                        <button class="btn btn-info" type="button" onclick="metodoClick('btn-ejecutar-cron',this)"
                        data-tareaid="<?php echo $tareaCron['id'] ?>"
                        <?php if($tareaCron['estado'] != MTareasCron::ESTADO_ACTIVO){ echo('disabled'); } ?>>Ejecutar</button> </td>
                    </tr>
                <?php }?>
			</div>
		</div>
	</div>
    </div>
    <a class="btn btn-success" href="ListaTareasCron.php?accion=correo" name="enviarCorreo">Correo</a>
    <script>
	// Declaramos variables globales
	var checkID = [];
	</script>
    <!-- Cargamos funciones de modulo. -->
    <!-- <script src="<?php echo $HostNombre; ?>/modulos/mod_/funciones.js" type="module"></script> -->
    <?php
if (isset($resultadoCorreo)) {
    dump($resultadoCorreo);
    if ($resultadoCorreo == 1) {
        $CTareasCron->montarAdvertencia('success', 'Enviado con éxito', 'OK');
    }
}
echo '<script src="' . $HostNombre . '/plugins/modal/func_modal.js"></script>';
include $URLCom . '/plugins/modal/ventanaModal.php';
?>
<!-- <script src="clases/claseTareasCron.js"></script> -->
<script src="funciones.js"></script>
</body>
</html>
