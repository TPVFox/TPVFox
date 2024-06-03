<?php

include_once 'MTareasCron.php';

class CTareasCron
{

    protected MTareasCron $tareasCron;

    public $tareaCron;

    public function __construct()
    {
        $this->tareasCron = new MTareasCron();
        $this->tareaCron = $this->tareasCron->initTareaCron();
    }

    public function list()
    {
        return $this->tareasCron->getTareas();
    }





    public function edit(int $tareaid = 0)
    {        
        return $this->tareasCron->find($tareaid);
    }

    public function crear() : void
    {        
        $this->tareaCron = $this->tareasCron->initTareaCron;        
    }

    public function guardar($datos)
    {
        
        foreach ($this->tareaCron as $indice => $valor) {
            if (array_key_exists($indice, $datos)) {
                $this->tareaCron[$indice] = $datos[$indice];
            }
        }

        $tarea = $this->tareasCron->crear($this->tareaCron);

        return $tarea;
    }
}
