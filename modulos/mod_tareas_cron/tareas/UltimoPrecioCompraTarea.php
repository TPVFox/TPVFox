<?php

// var_dump('------------------------------------------------------------------------');
// $real_path = realpath(__DIR__ . '/../../../');
// var_dump($real_path);
// include_once $real_path.'/inicial.php';
// $URLCom = $real_path;
// // var_dump('ellll');
// var_dump($URLCom);
// var_dump('------------------------------------------------------------------------');

if (!isset($URLCom)) {
    var_dump('------------------------------------------------------------------------');
    $URLCom = realpath(__DIR__ . '/../../../');
    var_dump('------------------------------------------------------------------------');
}

include_once $URLCom . '/clases/ClaseTFModelo.php';
include_once $URLCom . '/modulos/mod_tareas_cron/clases/MTareasCron.php';
include_once $URLCom . '/modulos/mod_tareas_cron/clases/MUltimoPrecioCompra.php';

class UltimoPrecioCompraTarea
{

    protected MTareasCron $tarea;
    protected MUltimoPrecioCompra $ultimo_precio_compra;

    public function __construct(int $tareaid = 0)
    {
        $this->tarea = new MTareasCron();
        $this->tarea->find($tareaid);
        $this->ultimo_precio_compra = new MUltimoPrecioCompra();
    }

    public function execute()
    {
        error_log('pasamos por execute() ------UltimoPrecioCompraTarea--------          ');
        error_log('Tarea-> ' . ($this->tarea->getTareaCron())['id']);
        //$this->tarea->updateEstado(MTareasCron::ESTADO_EN_PROCESO);
        $articulos_compra = $this->ultimo_precio_compra->leer();
        if ($datos = $articulos_compra['datos']) {
            $this->volcarUltimoPrecio($datos);
            //$this->tarea->updateEstado(MTareasCron::ESTADO_ACTIVO);
            $this->tarea->updateFechaEjecucion(($this->tarea->getTareaCron())['id']);
        } else {
            error_log($articulos_compra['consulta']);
            error_log(json_encode($articulos_compra['error']));
            $this->tarea->updateEstado(MTareasCron::ESTADO_ERROR_EN_PROCESO);
            // enviar aviso al administrador de tareas
        }
    }

    public function volcarUltimoPrecio(array $articulos_compra)
    {
        if (count($articulos_compra) > 0) {
            $ultimo_articulo = 0;
            foreach ($articulos_compra as $indice => $articulo_compra) {
                error_log(json_encode($articulo_compra));
                if ($ultimo_articulo != $articulo_compra['idArticulo']) {
                    $this->ultimo_precio_compra->actualizar_articulo($articulo_compra['idArticulo'], $articulo_compra['costeSIva']);
                    error_log('Precio articulo: ' . $articulo_compra['idArticulo']);
                    error_log('---> ' . $articulo_compra['costeSIva']);
                }
                $ultimo_articulo = $articulo_compra['idArticulo'];
            }
        }
    }
}
