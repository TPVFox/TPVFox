<?php

include_once $URLCom . '/clases/ClaseTFModelo.php';

class MTareasCron extends TFModelo
{
    const ESTADO_ACTIVO = 1,
    ESTADO_EN_PROCESO = 2,
    ESTADO_BAJA = 0;

    private array $textosEstado = [
        'Baja',
        'activo',
        'En proceso',
        
    ];

    private array $tareaCron = [];

    public function textoEstado($estado = 1)
    {
        return $this->textosEstado[$estado];
    }
    
    public function __construct()
    {
        $this->tabla = 'tareas_cron';
        $this->_initTareaCron();
    }

    protected function _initTareaCron(): void
    {
        $this->tareaCron = [
            'id' => null,
            'nombre' => '',
            'periodo' => '',
            'ruta' => '',
            'ultima_ejecucion' => null,
            'estado' => self::ESTADO_ACTIVO,
        ];

    }

    public function getTareas($estado='')
    {
        $sql = 'SELECT * FROM ' . $this->tabla;
        
        return $this->consulta($sql)['datos'];
    }

    public function getTareasActivas()
    {

        $sql = 'SELECT * FROM ' . $this->tabla
        . ' WHERE estado = 1';

        
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

    public function updateEstado($tareaId,$estado=Self::ESTADO_ACTIVO){
        $sql = 'UPDATE  ' . $this->tabla . ' SET estado='.$estado.' WHERE id=' . $tareaId;
        return $this->consultaDML($sql);
    }

    public function updateFechaEjecucion($tareaId, $withEstadoActivo=true){        
        $sql = 'UPDATE  ' . $this->tabla . ' SET ultima_ejecucion="'.date_create('now')->format('Y-m-d').'", estado='.Self::ESTADO_ACTIVO.' WHERE id=' . $tareaId;
        return $this->consultaDML($sql);
    }
}
