
<?php
include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_tareas_cron/clases/CTareasCron.php';

$CTareasCron = new CTareasCron();

$mensaje_cabecera = 'Crear Tarea';

if (isset($_GET['tareaid'])) {
    $tareaid = $_GET['tareaid'];

    $tarea = $CTareasCron->leer($tareaid);
    
    if ($tarea) {
        $ruta = './tareas/'.$tarea['nombre_clase'].'.php';

        if (file_exists($ruta)) {            
            include_once($ruta);
            $tarea = 'tarea1';
            $objeto = new $tarea();            
            $objeto->execute();
        } else {
            $CTareasCron->tareasCron()->updateEstado($tareaid, MTareasCron::ESTADO_FICHERO_NO_ENCONTRADO);
            echo 'NO ejecutable --->'. $ruta;            
        }        
    } else {
        echo json_encode(['data' => $tarea]);
    }
}

// Que pasa si no se devuelve nada ??
