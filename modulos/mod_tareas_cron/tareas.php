
<?php



include_once '/var/www/tpv/tpvfox/inicial_comandos.php';
include_once $URLCom . '/modulos/mod_tareas_cron/clases/CTareasCron.php';

$CTareasCron = new CTareasCron();

error_log('pasamos por tareas.php --------------          ');
var_dump($argv);

    $tareaid = $argv[1];

    $tarea = $CTareasCron->leer($tareaid);

    if ($tarea) {
        $ruta = './tareas/'.$tarea['nombre_clase'].'.php';

        if (file_exists($ruta)) {            
            include_once($ruta);
            
            $objeto = new ($tarea['nombre_clase'])($tareaid);
            $objeto->execute();
        } else {
            $CTareasCron->tareasCron()->updateEstado($tareaid, MTareasCron::ESTADO_FICHERO_NO_ENCONTRADO);
            echo 'NO ejecutable --->'. $ruta;            
        }        
    } else {
        echo json_encode(['data' => $tarea]);
    }


// Que pasa si no se devuelve nada ??
