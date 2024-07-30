<?php

include_once $URLCom . '/clases/ClaseTFModelo.php';

class MTareasCron extends TFModelo
{
    const ESTADO_ACTIVO = 1,
    ESTADO_EN_PROCESO = 2,
    ESTADO_BAJA = 0,
    ESTADO_FICHERO_NO_ENCONTRADO = 3,
    ESTADO_ERROR_EN_PROCESO = 4;

    const PERIODO_MINUTOS = 1,
    PERIODO_HORAS = 2,
    PERIODO_DIAS = 3,
    PERIODO_MESES = 4;

    private array $textosEstado = [
        'Baja',
        'activo',
        'En proceso',
        'Fichero no encontrado',

    ];

    private array $textosPeriodo = [
        1=>'Minutos',
        2=>'Horas',
        3=>'Días',
        4=>'Meses',

    ];

    private array $tareaCron = [];

    public function textoEstado($estado = 1)
    {
        return $this->textosEstado[$estado];
    }

    public function textoPeriodo($tipo_periodo = 1)
    {
        return $this->textosPeriodo[$tipo_periodo];
    }

    public function getTipoPeriodos()
    {
        return $this->textosPeriodo;
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
            'cantidad_periodo' => '',
            'tipo_periodo' => self::PERIODO_MINUTOS,
            'nombre_clase' => '',
            'inicio_ejecucion' => date(FORMATO_FECHA_ES),
            'ultima_ejecucion' => null,
            'estado' => self::ESTADO_ACTIVO,
        ];

    }

    public function getTareaCron(){
        return $this->tareaCron;
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

    public function updateEstado($estado = Self::ESTADO_ACTIVO, $id=0)
    {
        $tareaId = $id != 0 ? $id : $this->tareaCron['id'];
        $sql = 'UPDATE  ' . $this->tabla . ' SET estado=' . $estado . ' WHERE id=' . $tareaId;        
        return $this->consultaDML($sql);
    }

    public function updateFechaEjecucion($tareaId, $withEstadoActivo = true)
    {
        $sql = 'UPDATE  ' . $this->tabla . ' SET ultima_ejecucion="' . date(FORMATO_FECHA_MYSQL) . '"';
        if($withEstadoActivo){
            $sql .= ', estado=' . Self::ESTADO_ACTIVO;
        }
        $sql .= ' WHERE id=' . $tareaId;
        
        return $this->consultaDML($sql);
    }
    
}
