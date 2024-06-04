<?php

include_once 'MTareasCron.php';
include_once $URLCom . '/clases/traits/MontarAdvertenciaTrait.php';
class CTareasCron
{

    use MontarAdvertenciaTrait;

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

    public function crear(): void
    {
        $this->tareaCron = $this->tareasCron->initTareaCron;
    }

    protected function validar($datos): array
    {        
        $errores = [];
        if (empty($datos['nombre'])) {
            $errores['nombre'] = 'No puede ser vacío';
        }        
        if (empty($datos['ruta'])) {
            $errores['ruta'] = 'No puede ser vacío';
        }
        if (!is_int($datos['periodo'])) {
            $errores['periodo'] = 'Sólo números, por favor';
        }

        if (strlen('nombre') > 50) {
            $errores['nombre'] = 'Longitud máxima 50';
        }
        
        if (strlen('ruta') > 50) {
            $errores['ruta'] = 'Longitud máxima 50';
        }

        return $errores;
    }

    public function guardar($datos)
    {
        $tarea = [];
        $validacion = $this->validar($datos);
        if (count($validacion) == 0) {
            foreach ($this->tareaCron as $indice => $valor) {
                if (array_key_exists($indice, $datos)) {
                    $this->tareaCron[$indice] = $datos[$indice];
                }
            }

            $tarea = $this->tareasCron->crear($this->tareaCron);
        }
        return [$tarea, $validacion];
    }

    public function tareasCron(): MTareasCron
    {
        return $this->tareasCron;
    }
}
