<?php

include_once $URLCom . '/clases/ClaseTFModelo.php';

class MTareasCron extends TFModelo
{
    const ESTADO_ACTIVO = 1,
    ESTADO_EN_PROCESO = 2,
    ESTADO_BAJA = 0,
    ESTADO_FICHERO_NO_ENCONTRADO = 3;

    private array $textosEstado = [
        'Baja',
        'activo',
        'En proceso',
        'Fichero no encontrado',

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
            'nombre_clase' => '',
            'ultima_ejecucion' => null,
            'estado' => self::ESTADO_ACTIVO,
        ];

    }

    public function leer(int $id = 0)
    {
        $sql = 'SELECT * FROM ' . $this->tabla . ' WHERE id= ' . $id;

        return $this->consulta($sql)['datos'] ? $this->consulta($sql)['datos'][0] : null;
    }

    public function existe(int $id = 0)
    {
        $sql = 'SELECT count(id) AS contador FROM ' . $this->tabla . ' WHERE id= ' . $id;
        $resultado = $this->consulta($sql)['datos'] ? $this->consulta($sql)['datos'][0] : ['contador' => null];

        return $resultado['contador'];
    }

    public function getTareas($estado = '')
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

    public function find(int $tareaId)
    {
        $this->_initTareaCron();
        $sql = 'SELECT * FROM ' . $this->tabla . ' WHERE id=' . $tareaId . ' LIMIT 1';
        $resultado = $this->consulta($sql);
        $resultado = $resultado['datos'] ? ($resultado['datos'][0]) : [];
        if ($resultado) {
            foreach ($this->tareaCron as $indice => $valor) {
                $this->tareaCron[$indice] = $resultado[$indice];
            }
        }
        return $this->tareaCron;
    }

    public function crear($datos)
    {        
        return $this->insert($datos);
    }

   public function actualizar($datos, $tareaId = null)
    {
        $tareaId = $tareaId ?: $datos['id'];
        return $this->update($datos, ['id =' . $tareaId], false, true);
    }

    public function eliminar($tareaId = 0)
    {        
        $sql = 'DELETE FROM  ' . $this->tabla . ' WHERE id=' . $tareaId;
        return $this->consultaDML($sql);
    }

    public function updateEstado($estado = Self::ESTADO_ACTIVO)
    {
        $tareaId = $this->tareaCron['id'];
        $sql = 'UPDATE  ' . $this->tabla . ' SET estado=' . $estado . ' WHERE id=' . $tareaId;
        return $this->consultaDML($sql);
    }

    public function updateFechaEjecucion($tareaId, $withEstadoActivo = true)
    {
        $sql = 'UPDATE  ' . $this->tabla . ' SET ultima_ejecucion="' . date_create('now')->format('Y-m-d') . '", estado=' . Self::ESTADO_ACTIVO . ' WHERE id=' . $tareaId;
        return $this->consultaDML($sql);
    }
    
}
