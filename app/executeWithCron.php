<?php 

include_once __DIR__.'/../inicial.php';

include_once $URLCom . '/modulos/mod_tareas_cron/clases/TareasCron.php';

error_log('Paso por aqui--->'.time());        

$tareasCron = new TareasCron($URLCom);
$tareasCron->execute();