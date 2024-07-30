<?php

include_once __DIR__.'/../../../inicial.php';
//include_once 'inicial.php';
include_once $URLCom . '/clases/ClaseTFModelo.php';
//

class DiarioCron extends TFModelo
{

    static public function log($descripcion='',$tarea_id=0){
        $sql = "INSERT INTO diario_cron SET descripcion='". $descripcion ."'".($tarea_id !== 0 ? ", tarea_id='".$tarea_id."'" : '');        
        return self::_consultaDML($sql);
    }
}
