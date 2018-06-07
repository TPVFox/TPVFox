<?php

/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

include_once $RutaServidor . $HostNombre . '/modulos/claseModeloP.php';

/**
 * Description of ClaseArticulos
 *
 * @author alagoro
 */
class alArticulosStocks extends ModeloP { // hereda de clase modelo. 

    protected static $tabla = 'articulosStocks';
    
    public static function leer($idArticulo, $idTienda) {
        $sql = 'SELECT * '
                . 'FROM '.self::$tabla
                . ' WHERE idArticulo =' . $idArticulo
                . ' AND idTienda = ' . $idTienda
                . ' LIMIT 1';
        return alArticulosStocks::_consulta($sql);
    }

    public static function existe($idArticulo, $idTienda) {
        $sql = 'SELECT COUNT(idArticulo) AS contador '
                . 'FROM '.self::$tabla
                . ' WHERE idArticulo =' . $idArticulo
                . ' AND idTienda = ' . $idTienda
                . ' LIMIT 1';

        $resultado = alArticulosStocks::_consulta($sql);
        return $resultado && ($resultado[0]['contador'] == 1);
    }

    public static function getIdbyArticulo($idArticulo, $idTienda) {
        $sql = 'SELECT id '
                . 'FROM '.self::$tabla
                . ' WHERE idArticulo =' . $idArticulo
                . ' AND idTienda = ' . $idTienda
                . ' LIMIT 1';

        $resultado = alArticulosStocks::_consulta($sql);
        if($resultado){
            $resultado = $resultado[0]['id'];
        }
        return $resultado;
    }

    public static function leerStockXId($idStock) {
        $sql = 'SELECT stockOn '
                . 'FROM '.self::$tabla
                . ' WHERE id =' . $idStock
               . ' LIMIT 1';
        $resultado = alArticulosStocks::_consulta($sql);
        if($resultado){
            $resultado = $resultado[0]['stockOn'];
        }
        return $resultado;
    }

    public static function getStockMin($idArticulo, $idTienda = 1) {
        if ($stock = self::leer($idArticulo, $idTienda)) {
            $resultado = $stock['stockMin'];
        } else {
            $resultado = -1;
        }
        return $resultado;
    }

    
    public static function crearStock($valores){
        return ModeloP::insert(alArticulosStocks::$tabla,['idArticulo'=>$valores[0],
            'idTienda'=>$valores[1],
            'stockMin'=>$valores[2],
            'stockOn'=>$valores[3],
            'stockMax'=>$valores[4],
            ]);
    }


    private static function _actualizarStock($idarticulostock, $nunidades, $operador) {
//            $sql='UPDATE articulosStocks SET '
//                    . ' stockOn = '.($nunidades * $operador) // operador {1, -1} = {SUMA, RESTA}
//                . ' WHERE id =' . $idarticulostock;
//            return $this->consultaDML($sql);
        $stockon = self::leerStockXId($idarticulostock);
        return alArticulosStocks::update(alArticulosStocks::$tabla,['stockOn'=>$stockon +($nunidades * $operador)], ['id ='.$idarticulostock]);
    }

    public static function actualizarStock($idArticulo, $idTienda, $nunidades, $operador) {
        $resultado = false;
        if($articulostock = self::existe($idArticulo, $idTienda)){            
            $resultado = self::_actualizarStock(self::getIdbyArticulo($idArticulo, $idTienda), $nunidades, $operador);
        } else {
            $idstock = self::crearStock([$idArticulo, $idTienda, 0,0,0]);
            if($idstock){
                $resultado = self::_actualizarStock($idstock, $nunidades, $operador);
            }
        }
        return $resultado;
    }

}
