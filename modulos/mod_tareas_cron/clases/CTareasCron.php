<?php

use Clases\Log;

include_once 'MTareasCron.php';
include_once $URLCom . '/clases/traits/MontarAdvertenciaTrait.php';

class CTareasCron
{

    use MontarAdvertenciaTrait;

    protected MTareasCron $tareasCron;

    public $tareaCron;
    protected $logger;

    public function __construct($ruta_datos = '')
    {
        $this->tareasCron = new MTareasCron();
        $this->tareaCron = $this->tareasCron->initTareaCron();        
        dump('--0--->'.$ruta_datos);
        $this->logger = new Log($ruta_datos);
    }

    public function list()
    {        
        return $this->tareasCron->getTareas();
    }

    public function leer($tareaid = 0)
    {
        return $this->tareasCron->leer((int) $tareaid);
    }

    public function edit(int $tareaid = 0)
    {
        $this->logger->log('Editar Tarea');
        $this->tareaCron = $this->tareasCron->find($tareaid);
    }

    public function crear(): void
    {
        $this->logger->log('Crear Tarea');
        $this->tareaCron = $this->tareasCron->initTareaCron;
    }

    protected function validar($datos): array
    {
        $errores = [];
        if (empty($datos['nombre'])) {
            $errores['nombre.empty'] = 'No puede ser vacío';
        }
        if (empty($datos['nombre_clase'])) {
            $errores['nombre_clase.empty'] = 'No puede ser vacío';
        }

        if (!is_numeric($datos['periodo'])) {
            $errores['periodo'] = 'Sólo números, por favor';
        }

        if (strlen('nombre') > 50) {
            $errores['nombre.max'] = 'Longitud máxima 50';
        }

        if (strlen('nombre_clase') > 50) {
            $errores['nombre_clase.max'] = 'Longitud máxima 50';
        }

        return $errores;
    }

    public function guardar($datos)
    {
        $tarea = [];
        $erroresValidacion = $this->validar($datos);
        if (count($erroresValidacion) == 0) {
            foreach ($this->tareaCron as $indice => $valor) {
                if (array_key_exists($indice, $datos)) {
                    $this->tareaCron[$indice] = $datos[$indice];
                }
            }
            if ($datos['id']) {
                if ($this->tareasCron->existe($datos['id'])) {
                    $tarea = $this->tareasCron->actualizar($this->tareaCron);
                } else {
                    $erroresValidacion['id.notfound'] = 'Tarea no encontrada';
                }
            } else {
                $this->tareaCron['id']=null;
                $tarea = $this->tareasCron->crear($this->tareaCron);
            }

        }
        return [$tarea, $erroresValidacion];
    }

    public function eliminar(int $tareaId = 0)
    {
        $erroresValidacion = [];

        if ($this->tareasCron->existe($tareaId)) {
            $tarea = $this->tareasCron->eliminar($tareaId);
        } else {
            $erroresValidacion['id.notfound'] = 'Tarea no encontrada';
        }

        return [$erroresValidacion];
    }

    // public function modificar($tareaid, $datos)
    // {
    //     $tarea = [];
    //     $erroresValidacion = $this->validar($datos);
    //     if (count($erroresValidacion) == 0) {
    //         foreach ($this->tareaCron as $indice => $valor) {
    //             if (array_key_exists($indice, $datos)) {
    //                 $this->tareaCron[$indice] = $datos[$indice];
    //             }
    //         }

    //         $tarea = $this->tareasCron->actualizar($this->tareaCron);
    //     }
    //     return [$tarea, $erroresValidacion];
    // }

    public function tareasCron(): MTareasCron
    {
        return $this->tareasCron;
    }
}
