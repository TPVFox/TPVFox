<?php

namespace Clases;

class Log
{

    private string $fichero;

    public function __construct($ruta_datos)
    {
        dump('-1--->'.$ruta_datos);
        $this->fichero = $ruta_datos . '/'.date('Ymd').'.log';
        if(!file_exists($this->fichero)){            
            touch($this->fichero);
        }        
    }

    public function fichero()
    {        
        return $this->fichero;
    }

    public function log($mensaje)
    {
        error_log($mensaje, 3, $this->fichero);
    }
}
