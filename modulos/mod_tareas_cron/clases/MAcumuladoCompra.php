<?php

include_once $URLCom . '/clases/ClaseTFModelo.php';

class MAcumuladoCompra extends TFModelo
{

    public function __construct()
    {
        $this->tabla = 'acumulado_compras';
    }

    public function leer()
    {
        $sql = 'SELECT lineas.idArticulo AS idarticulo, YEAR(albaranes.Fecha) as year, MONTH(albaranes.Fecha) as month,';
        $sql .= ' SUM(costeSiva) as coste, IF( SUM(ncant) <> 0, (SUM(costeSiva * ncant)/SUM(ncant)),0) as costemedio, SUM(ncant) as cantidad ';
        $sql .= 'FROM `albprolinea` as lineas';
        $sql .= ' LEFT OUTER JOIN albprot as albaranes ON (albaranes.id=lineas.idalbpro) GROUP BY idarticulo, year, month';
        $sql .= ' ORDER BY idarticulo, year, month';
        return $this->consulta($sql);
    }

    public function crear($datos)
    {
        return $this->insert($datos);
    }

    public function actualizar($acumulado)
    {
        $datos['id']=null;        
        $datosKey = $this->desglosaDatosPorNombre($acumulado, ['year', 'month', 'idarticulo']);
        $datos = $this->desglosaNoDatosPorNombre($acumulado, ['year', 'month', 'idarticulo']);
        $datos['update_at'] = date(FORMATO_FECHA_MYSQL);
        return $this->insertOrUpdate($datosKey, $datos);
    }

    // public function updateFechaEjecucion($tareaId, $withEstadoActivo = true)
    // {
    //     $sql = 'UPDATE  ' . $this->tabla . ' SET ultima_ejecucion="' . date_create('now')->format('Y-m-d') . '", estado=' . Self::ESTADO_ACTIVO . ' WHERE id=' . $tareaId;
    //     return $this->consultaDML($sql);
    // }

}
