
<?php
include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_tareas_cron/clases/CTareasCron.php';

$CTareasCron = new CTareasCron();

$mensaje_cabecera = 'Crear Tarea';


if (isset($_GET['accion'])) {
    $accion = $_GET['accion'];
    
    switch ($accion) {
        case 'modificar':
            $mensaje_cabecera = 'Modificar Tarea';            
            if (isset($_GET['tarea'])) {
                $tareaid = $_GET['tarea'];
                $CTareasCron->edit($tareaid);
            }
            break;
        case 'guardar':
            $resultado = $CTareasCron->guardar($_POST);            
            if (isset($resultado) && count($resultado[1])==0 && $resultado[0]) {
                header('Location: ListaTareasCron.php');
            } else {
                foreach($resultado[1] as $campo=>$mensaje){
                    $errores[] = ['tipo'=>'warning','mensaje'=>$campo.': '.$mensaje];
                }
                $errores[] = ['tipo'=>'danger','mensaje'=>'Por favor complete correctamente el formulario y pulse nuevamente \'guardar\''];
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
                <input type="hidden" name="id" id="tareaid" value="<?php echo $CTareasCron?->tareaCron['id'] ?>" />
                <div class="row">
                    <div class="col-4">
                        <label for="nombre" >Nombre:</label>
                        <input name="nombre" id="nombre" value="<?php echo $CTareasCron?->tareaCron['nombre'] ?>" />
  			        </div>
                      <div class="col-2">
                        <label for="periodo" >Periodo (minutos):</label>
                        <input name="periodo" id="periodo" value="<?php echo $CTareasCron?->tareaCron['periodo'] ?>" />
  			        </div>
                      <div class="col-4">
                        <label for="nombre_clase" >Clase:</label>
                        <input name="nombre_clase" id="nombre_clase" value="<?php echo $CTareasCron?->tareaCron['nombre_clase'] ?>" />
  			        </div>
                      <div class="col-4">                        
                        Activo <input type="radio" name="estado" id="estado-activo" value="1" <?php if($CTareasCron->tareaCron['estado'] == MTareasCron::ESTADO_ACTIVO){ echo 'checked'; } ?> >
                          Baja <input type="radio" name="estado" id="estado-baja" value="0"   <?php if($CTareasCron->tareaCron['estado'] == MTareasCron::ESTADO_BAJA){ echo 'checked'; } ?> >
  			        </div>
			    </div>
                <button type="submit" name="accion" >Guardar</button>
                <button type="button" name="volver" 
                data-url="ListaTareasCron.php"
                onclick="metodoClick('volver',this)" >Volver</button>
            </form>
    </div>

    <?php
//  echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
//  include $URLCom.'/plugins/modal/ventanaModal.php';
?>
<script src="funciones.js"></script>
</body>
</html>
