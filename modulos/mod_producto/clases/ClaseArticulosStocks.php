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
class alArticulosStocks extends ModeloP
{ // hereda de clase modelo. 

    protected static $tabla = 'articulosStocks';

    public static function leer($idArticulo, $idTienda, $creaSiNoExiste = false)
    {
        if ($creaSiNoExiste) {
            if (!self::existe($idArticulo, $idTienda)) {
                self::crearStock([$idArticulo, $idTienda, 0, 0, 0]);
            }
        }
        $sql = 'SELECT * '
            . 'FROM ' . self::$tabla
            . ' WHERE idArticulo =' . $idArticulo
            . ' AND idTienda = ' . $idTienda
            . ' LIMIT 1';
        $resultado = alArticulosStocks::_consulta($sql);
        if ($resultado) {
            $resultado = $resultado[0];
        }
        return $resultado;
    }

    public static function existe($idArticulo, $idTienda)
    {
        $sql = 'SELECT COUNT(idArticulo) AS contador '
            . 'FROM ' . self::$tabla
            . ' WHERE idArticulo =' . $idArticulo
            . ' AND idTienda = ' . $idTienda
            . ' LIMIT 1';

        $resultado = alArticulosStocks::_consulta($sql);
        return $resultado && ($resultado[0]['contador'] == 1);
    }

    public static function getIdbyArticulo($idArticulo, $idTienda)
    {
        $sql = 'SELECT id '
            . 'FROM ' . self::$tabla
            . ' WHERE idArticulo =' . $idArticulo
            . ' AND idTienda = ' . $idTienda
            . ' LIMIT 1';

        $resultado = alArticulosStocks::_consulta($sql);
        if ($resultado) {
            $resultado = $resultado[0]['id'];
        }
        return $resultado;
    }

    public static function leerStockXId($idStock)
    {
        $sql = 'SELECT stockOn '
            . 'FROM ' . self::$tabla
            . ' WHERE id =' . $idStock
            . ' LIMIT 1';
        $resultado = alArticulosStocks::_consulta($sql);
        if ($resultado) {
            $resultado = $resultado[0]['stockOn'];
        }
        return $resultado;
    }

    public static function getStockMin($idArticulo, $idTienda = 1)
    {
        if ($stock = self::leer($idArticulo, $idTienda)) {
            $resultado = $stock['stockMin'];
        } else {
            $resultado = -1;
        }
        return $resultado;
    }

    public static function crearStock($valores)
    {
        return ModeloP::_insert(alArticulosStocks::$tabla, ['idArticulo' => $valores[0],
                'idTienda' => $valores[1],
                'stockMin' => $valores[2],
                'stockOn' => $valores[3],
                'stockMax' => $valores[4],
        ]);
    }

    public static function limpiaStock($idTienda = 1)
    {
        $sql = 'DELETE FROM articulosStocks '
            . ' WHERE idTienda = ' . $idTienda;
        return self::_consultaDML($sql);
    }

    private static function _actualizarStock($idarticulostock, $nunidades, $operador)
    {
        $stockon = self::leerStockXId($idarticulostock);
        if ($operador === K_STOCKARTICULO_REGULARIZA) {
            return alArticulosStocks::_update(alArticulosStocks::$tabla, [
                    'stockOn' => $nunidades, 'fechaRegularizacion' => date(FORMATO_FECHA_MYSQL)
                    , 'usuarioRegularizacion' => 0], ['id =' . $idarticulostock]);
        } else {
            return alArticulosStocks::_update(alArticulosStocks::$tabla, [
                    'stockOn' => $stockon + ($nunidades * $operador)
                    , 'fechaRegularizacion' => date(FORMATO_FECHA_MYSQL)
                    , 'usuarioRegularizacion' => 0], ['id =' . $idarticulostock]);
        }
    }

    public static function actualizarStock($idArticulo, $idTienda, $nunidades, $operador)
    {
        $resultado = false;
        if ($articulostock = self::existe($idArticulo, $idTienda)) {
            $resultado = self::_actualizarStock(self::getIdbyArticulo($idArticulo, $idTienda), $nunidades, $operador);
        } else {
            $idstock = self::crearStock([$idArticulo, $idTienda, 0, 0, 0]);
            if ($idstock) {
                $resultado = self::_actualizarStock($idstock, $nunidades, $operador);
            }
        }
        return $resultado;
    }

    public static function regularizaStock($idArticulo, $idTienda, $nunidades, $idUsuario)
    {
        $resultado = self::actualizarStock($idArticulo, $idTienda, $nunidades, K_STOCKARTICULO_SUMA);
        if ($resultado) {
            $resultado = alArticulosStocks::_update(alArticulosStocks::$tabla, [
                    'fechaRegularizacion' => date(FORMATO_FECHA_MYSQL)
                    , 'usuarioRegularizacion' => $idUsuario], ['id =' . self::getIdbyArticulo($idArticulo, $idTienda)]);
        }
        return $resultado;
    }
}
