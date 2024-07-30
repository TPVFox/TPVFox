
<?php

//include_once '/var/www/tpv/tpvfox/inicial.php';
include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_tareas_cron/clases/CTareasCron.php';

$CTareasCron = new CTareasCron();

$tareaid = $_POST['tareaid'];

$tarea = $CTareasCron->leer($tareaid);

if ($tarea) {
    $ruta = './tareas/' . $tarea['nombre_clase'] . '.php';

    if (file_exists($ruta)) {
        include_once $ruta;
        $objeto = new ($tarea['nombre_clase'])($tareaid);
        $objeto->execute();
        $respuesta = ['mensaje' => 'ejecutado --->' . $ruta, 'error'=>0];
    } else {
        $CTareasCron->tareasCron()->updateEstado($tareaid, MTareasCron::ESTADO_FICHERO_NO_ENCONTRADO);
        $respuesta = ['mensaje' => 'NO ejecutable --->' . $ruta, 'error'=>1];
    }
} else {
    $respuesta = ['data' => $tarea, 'error'=>2];
}
echo json_encode($respuesta);
// Que pasa si no se devuelve nada ??
