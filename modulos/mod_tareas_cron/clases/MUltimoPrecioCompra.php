<?php

include_once $URLCom . '/clases/ClaseTFModelo.php';

class MUltimoPrecioCompra extends TFModelo
{

    public function __construct()
    {
        $this->tabla = '';
    }

    public function leer()
    {
        $sql = 'SELECT fecha, idArticulo, costeSIva';
        $sql .= ' FROM (';
        $sql .= ' SELECT ROW_NUMBER() OVER (PARTITION BY idArticulo ORDER BY fecha DESC ) AS row_num, ';
        $sql .= ' fecha, idArticulo, costeSIva ';
        $sql .= ' FROM `albprolinea` as lineas LEFT OUTER JOIN albprot as albaranes ON (albaranes.id=lineas.idalbpro) ';
        $sql .= ' order by idArticulo, fecha DESC) ranked';
        $sql .= ' WHERE row_num = 1;';
        return $this->consulta($sql);

        // $resultado = $this->consulta($sql);
        // return $resultado['datos'] ? : $resultado['error'];
    }

    public function actualizar_articulo($idarticulo, $ultimo_precio_compra)
    {
        $sql = "UPDATE articulos SET ultimoCoste = '" . $ultimo_precio_compra . "' WHERE IdArticulo=" . $idarticulo;
        return $this->consultaDML($sql);
    }

}
