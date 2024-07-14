<?php

//include_once __DIR__.'/../../../inicial.php';
include_once 'inicial.php';
include_once $URLCom . '/modulos/mod_tareas_cron/clases/MTareasCron.php';

class TareasCron
{

    public function execute(): void
    {
        error_log('Paso por aqui--->' . time());

        $tareaCron = new MTareasCron();

        $tareas = $tareaCron->getTareasActivas();
        //error_log(json_encode($tareas));
        if ($tareas) {
            foreach ($tareas as $tarea) {
                $ruta = './tareas/'.$tarea['nombre_clase'].'.php';
                if (file_exists($ruta)) {            

                    if ($tarea['ultima_ejecucion']) {
                    $datetime1 = date_create($tarea['ultima_ejecucion']);
                    $datetime2 = date_create();
                    $intervalo = date_diff($datetime1, $datetime2);
                    error_log(json_encode($intervalo));

                    $minutos = $intervalo->days * 24 * 60;
                    $minutos += $intervalo->h * 60;
                    $minutos += $intervalo->i;
                    error_log(json_encode($minutos));
                    $ejecutar = $minutos >= $tarea['periodo'];
                } else {
                    $ejecutar = true;
                }
                //if($ejecutar)
                $tareaCron->updateEstado($tarea['id'], MTareasCron::ESTADO_EN_PROCESO);

                include_once $ruta;
                if (!class_exists($tarea['nombre_clase'], false)) {
                    error_log("No carga la clase: ".$tarea['nombre_clase']);
                }
                                        
                $objeto = new $tarea['nombre_clase']();
                $objeto->execute();

                $tareaCron->updateFechaEjecucion($tarea['id']);
                //$tareaCron->updateEstado($tarea['id']);
                error_log($tarea['ruta']);
                error_log($ejecutar);
            } else {
                $tareaCron->updateEstado($tarea['id'], MTareasCron::ESTADO_FICHERO_NO_ENCONTRADO);
                error_log('fichero no encontrado-->'.$tarea['ruta']);
            }
            }
        }
    }

}
