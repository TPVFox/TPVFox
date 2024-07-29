<?php

include_once __DIR__.'/../../../inicial.php';
//include_once 'inicial.php';
include_once $URLCom . '/modulos/mod_tareas_cron/clases/MTareasCron.php';
include_once $URLCom . '/modulos/mod_tareas_cron/clases/CTareasCron.php';
//

class TareasCron
{
    protected $URLCom;

    public function __construct($urlcom)
    {
        $this->URLCom = $urlcom;
    }


private function periodo_to_minutos($cantidad = 0, $tipo){

    
    switch($tipo){
        case 2: //Horas
            $cantidad = $cantidad * 60;
            break;
        case 3: //dias
            $cantidad = $cantidad * 24 * 60;
            break;
        case 3: //meses
            $cantidad = $cantidad * 30 * 24 * 60;
            break;
        }

    return $cantidad;

}


    public function execute(): void
    {
        error_log('Paso por aqui-ClaseTareasCron-->' . time());
        var_dump($this->URLCom);
        $tareaCron = new MTareasCron();

        $tareas = $tareaCron->getTareasActivas();
        error_log(json_encode($tareas));
        if ($tareas) {
            foreach ($tareas as $tarea) {
                $ruta = $this->URLCom . '/modulos/mod_tareas_cron/tareas/' . $tarea['nombre_clase'] . '.php';                
                if (file_exists($ruta)) {
                    $ejecutqar = false;
                    if ($tarea['ultima_ejecucion']) {
                        $fecha_inicio = date_create($tarea['inicio_ejecucion']);
                        $datetime1 = date_create($tarea['ultima_ejecucion']);
                        if($datetime1 >= $fecha_inicio){
                        $datetime2 = date_create();
                        $intervalo = date_diff($datetime1, $datetime2);
                        
                        $minutos_periodo = $this->periodo_to_minutos($tarea['cantidad_periodo'], $tarea['tipo_periodo']);

                        $minutos = $intervalo->days * 24 * 60;
                        $minutos += $intervalo->h * 60;
                        $minutos += $intervalo->i;
                        
                        $ejecutar = $minutos >= $minutos_periodo;
                        error_log( $ruta);
                        error_log( $ejecutar ? 'SI ejecutar' : 'NO ejecutar');
                        }
                    } else {
                        $ejecutar = true;
                    }
                    if ($ejecutar) {
                        error_log('ejecutar');                        
                        var_dump('estoy:'.$ruta);

                        include_once $ruta;
                        if (!class_exists($tarea['nombre_clase'], false)) {
                            error_log("No carga la clase: " . $tarea['nombre_clase']);
                        } else {
                            var_dump('ejecutando---->' . $tarea['nombre_clase'] . PHP_EOL);
                            var_dump($tarea['id']);
                            $objeto = new $tarea['nombre_clase']($tarea['id']);
                            $objeto->execute();                        
                            //$tareaCron->updateEstado(MTareasCron::ESTADO_ACTIVO, $tarea['id']);
                        //         $tareaCron->updateFechaEjecucion($tarea['id']);
                        //         //$tareaCron->updateEstado($tarea['id']);
                        //         error_log($tarea['ruta']);
                        //         error_log($ejecutar);
                        }
                    } else {
                        $tareaCron->updateEstado(MTareasCron::ESTADO_FICHERO_NO_ENCONTRADO, $tarea['id']);
                        error_log('fichero no encontrado-->' . $tarea['ruta']);
                    }
                }
            }
        }
    }

}
