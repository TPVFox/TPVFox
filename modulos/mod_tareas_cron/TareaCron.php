
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
            
            if (isset($resultado) && count($resultado[1])==0 && $resultado[0]) {
                header('Location: ListaTareasCron.php');
            } else {
                foreach($resultado[1] as $campo=>$mensaje){
                    $errores[] = ['tipo'=>'warning','mensaje'=>$campo.': '.$mensaje];
                }
                $errores[] = ['tipo'=>'danger','mensaje'=>'Por favor complete correctamnete el formulario y pulse nuevamente \'guardar\''];
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
            <?php
            if (isset($errores) && count($errores)>0){
                foreach($errores as $error){
                    echo '<div class="alert alert-'.$error['tipo'].'">'
                    . '<strong>'.$error['tipo'].' </strong><br/> ';
                    if (is_array($error['mensaje'])){
                        echo '<pre>';
                        print_r($error['mensaje']);
                        echo '</pre>';
                    } else {
                        echo $error['mensaje'];
                    }
                    echo '</div>';
                }
            }
            ?>

			<div class="col-md-12 text-center">
				<h2> <?php echo $mensaje_cabecera ?> </h2>
			</div>
	       <form action="TareaCron.php?accion=guardar" method="POST" name="formTareaCron">
                <div class="row">
                    <div class="col-4">
                        <label for="nombre" >Nombre:</label>
                        <input name="nombre" id="nombre" value="<?php $CTareasCron?->tareaCron['nombre'] ?>" />
  			        </div>
                      <div class="col-2">
                        <label for="periodo" >Periodo (minutos):</label>
                        <input name="periodo" id="periodo" value="" />
  			        </div>
                      <div class="col-4">
                        <label for="ruta" >Ruta:</label>
                        <input name="ruta" id="ruta" value="" />
  			        </div>
                      <div class="col-4">                        
                        Activo <input type="radio" name="estado" id="estado-activo" value="1" <?php if($CTareasCron->tareaCron['estado'] == MTareasCron::ESTADO_ACTIVO){ echo 'checked'; } ?> >
                          Baja <input type="radio" name="estado" id="estado-baja" value="0"   <?php if($CTareasCron->tareaCron['estado'] == MTareasCron::ESTADO_BAJA){ echo 'checked'; } ?> >
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
