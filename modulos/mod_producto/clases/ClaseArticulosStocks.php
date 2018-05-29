<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

include_once $RutaServidor . $HostNombre . '/modulos/claseModelo.php';

/**
 * Description of ClaseArticulos
 *
 * @author alagoro
 */
class alArticulosStocks extends Modelo { // hereda de clase modelo. 

    protected $tabla = 'articulosstocks';

    public function leer($idArticulo, $idTienda) {
        $sql = 'SELECT * '
                . 'FROM articulosstocks '
                . ' WHERE idArticulo =' . $idArticulo
                . ' AND idTienda = ' . $idTienda
                . ' LIMIT 1';
        return $this->consulta($sql);
    }

    public function existe($idArticulo, $idTienda) {
        $sql = 'SELECT COUNT(idArticulo) AS contador '
                . 'FROM articulosstocks '
                . ' WHERE idArticulo =' . $idArticulo
                . ' AND idTienda = ' . $idTienda
                . ' LIMIT 1';

        $resultado = $this->consulta($sql);
        return $resultado['datos'][0]['contador'] == 1;
    }

    public function getStockMin($idArticulo, $idTienda = 1) {
        if ($stock = $this->leer($idArticulo, $idTienda)) {
            $resultado = $stock['stockMin'];
        } else {
            $resultado = -1;
        }
        return $resultado;
    }

    
    public function crearStock($valores){
        $this->insert(['idArticulo'=>$valores[0],
            'idTienda'=>$valores[1],
            'stockMin'=>$valores[2],
            'stockOn'=>$valores[3],
            'stockMax'=>$valores[4],
            ]);
    }


    private function _actualizarStock($idarticulostock, $nunidades, $operador) {
            $sql='UPDATE articulosstocks SET '
                    . ' stockOn = '.($nunidades * $operador) // operador {1, -1} = {SUMA, RESTA}
                . ' WHERE $id =' . $idarticulostock;
    }

    public function actualizarStock($idArticulo, $idTienda, $nunidades, $operador) {
        $resultado = false;
        if($articulostock = $this->existe($idArticulo, $idTienda)){
            $resultado = $this->_actualizarStock($articulostock['id'], $nunidades, $operador);
        } else {
            $articulostock = $this->crearStock([$idArticulo, $idTienda, 0,0,0]);
            if($articulostock){
                $resultado = $this->_actualizarStock($articulostock['id'], $nunidades, $operador);
            }
        }
        return $resultado;
    }

}
