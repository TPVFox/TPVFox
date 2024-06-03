
<?php
include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_tareas_cron/clases/CTareasCron.php';

$CTareasCron = new CTareasCron();

$mensaje_cabecera = 'Crear Tarea';

if (isset($_GET['accion'])) {
    $accion = $_GET['accion'];

    switch ($accion) {
        case 'editar':
            $mensaje_cabecera = 'Modificar Tarea';            
            break;
        case 'guardar':
            $resultado = $CTareasCron->guardar($_POST);
            if (!isset($resultado['error'])) {
                header('Location: ListaTareasCron.php');
            }
    }

}

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
			<div class="col-md-12 text-center">
				<h2> <?php $mensaje_cabecera ?> </h2>
			</div>
	       <form action="TareaCron.php?accion=guardar" method="POST" name="formTareaCron">
                <div class="row">
                    <div class="col">
                        <label for="nombre" >Nombre:</label>
                        <input name="nombre" id="nombre" value="<?php $CTareasCron->tareaCron?->nombre ?>" />
  			        </div>
                      <div class="col">
                        <label for="periodo" >Periodo (minutos):</label>
                        <input name="periodo" id="periodo" value="" />
  			        </div>
                      <div class="col">
                        <label for="ruta" >Ruta:</label>
                        <input name="ruta" id="ruta" value="" />
  			        </div>
			    </div>
                <button type="submit" name="accion" value="guardar" >Guardar</button>
            </form>
    </div>

    <?php
//  echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
//  include $URLCom.'/plugins/modal/ventanaModal.php';
?>

</body>
</html>
