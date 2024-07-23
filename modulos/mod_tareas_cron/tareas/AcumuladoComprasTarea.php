<?php

include_once $URLCom . '/clases/ClaseTFModelo.php';
include_once $URLCom . '/modulos/mod_tareas_cron/clases/MTareasCron.php';
include_once $URLCom . '/modulos/mod_tareas_cron/clases/MAcumuladoCompra.php';
include_once $URLCom . '/modulos/mod_producto/clases/ClaseArticulos.php';

class AcumuladoComprasTarea
{
    protected MTareasCron $tarea;
    protected MAcumuladoCompra $acumulado_compra;

    public function __construct(int $tareaid = 0)
    {
        $this->tarea = new MTareasCron();
        $this->tarea->find($tareaid);
        $this->acumulado_compra = new MAcumuladoCompra();
    }

    public function execute()
    {
        $this->tarea->updateEstado(MTareasCron::ESTADO_EN_PROCESO);
        $acumulados = $this->acumulado_compra->leer();
        $datos_acumulados = $acumulados['datos'];
        $this->volcarAcumulados($datos_acumulados);
        $this->tarea->updateEstado(MTareasCron::ESTADO_ACTIVO);
        //ejecutaFinTarea('AcumuladoComprasTarea');
    }

    public function volcarAcumulados(array $acumulados)
    {
        error_log('Volcar acumulados ' . PHP_EOL);
        if (count($acumulados) > 0) {
            $articulo = new alArticulos();
            $suma_cantidad = 0;
            $suma_coste = 0;
            $idarticulo = $acumulados[0]['idarticulo'];
            foreach ($acumulados as $indice => $acumulado) {
                if ($acumulado['idarticulo'] != $idarticulo) {
                    if ($suma_cantidad != 0) {
                        //Grabar acumulado
                        $resultado = $articulo->update(['costepromedio' => $suma_coste / $suma_cantidad], ['IdArticulo = '.$idarticulo]);
                        
                    }
                    //inicializar acumulado
                    $suma_cantidad = 0;
                    $suma_coste = 0;
                    $idarticulo = $acumulado['idarticulo'];
                }

                $suma_cantidad += $acumulado['cantidad'];
                $suma_coste += ($acumulado['costemedio'] * $acumulado['cantidad']);
                $this->acumulado_compra->actualizar($acumulado);

            }
            // $coste_medio = $suma_cantidad != 0 ? ($suma_coste / $suma_cantidad) : 0;
            // $articulo = new alArticulos();
            // $articulo->update(['costepromedio'=>$coste_medio],['IdArticulo'=>1]);
        }
    }
}
