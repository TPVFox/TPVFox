<?php 

include_once __DIR__.'/../inicial.php';

include_once $URLCom . '/modulos/mod_tareas_cron/clases/TareasCron.php';
include_once $URLCom . '/modulos/mod_tareas_cron/clases/DiarioCron.php';

DiarioCron::log('executeWithCron');

$tareasCron = new TareasCron($URLCom);
$tareasCron->execute();