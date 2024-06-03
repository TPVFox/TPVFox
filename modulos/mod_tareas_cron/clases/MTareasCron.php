<?php

include_once $URLCom . '/clases/ClaseTFModelo.php';

class MTareasCron extends TFModelo
{
    private array $tareaCron = [];

    public function __construct()
    {
        $this->tabla = 'tareas_cron';
        $this->_initTareaCron();
    }

    protected function _initTareaCron() : void
    {
        $this->tareaCron = [
            'id' => null,
            'nombre' => '',
            'periodo' => '',
            'ruta' => '',
            'ultima_ejecucion' => null,
        ];

    }

    public function getTareas()
    {
        $sql = 'SELECT * FROM ' . $this->tabla;
        return $this->consulta($sql)['datos'];
    }

    public function initTareaCron()
    {
        $this->_initTareaCron();
        return $this->tareaCron;
    }

    public function find(int $tareaid)
    {
        $this->_initTareaCron();
        $sql = 'SELECT * FROM ' . $this->tabla . ' WHERE id=' . $tareaid;
        $resultado = $this->consulta($sql)['datos'];
        if ($resultado) {
            foreach ($tareaCron as $indice => $valor) {
                $this->tareaCron[$indice] = $resultado[$indice];
            }
        }
        return $this->tareaCron;
    }

    public function crear($datos)
    {
        return $this->insert($datos);
    }

}
