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
class alArticulosRegularizacion extends ModeloP { // hereda de clase modelo.

    protected static $tabla = 'articulosRegularizaciones';

    public static function leer() {
        return parent::leer(self::$tabla, ['idArticulo =' . $idArticulo,
                ' idTienda = ' . $idTienda]);
    }

    public static function existe($tabla, $idArticulo, $idTienda) {
        $resultado = alArticulosRegularizacion::leer(self::$tabla, ['idArticulo =' . $idArticulo,
                ' idTienda = ' . $idTienda],['COUNT(idArticulo) AS contador'],1);

        return $resultado && ($resultado[0]['contador'] == 1);
    }

    public static function getIdbyArticulo($idArticulo, $idTienda) {
        $sql = 'SELECT id '
                . 'FROM ' . self::$tabla
                . ' WHERE idArticulo =' . $idArticulo
                . ' AND idTienda = ' . $idTienda
                . ' LIMIT 1';

        $resultado = alArticulosRegularizacion::_consulta($sql);
        if ($resultado) {
            $resultado = $resultado[0]['id'];
        }
        return $resultado;
    }


    public static function limpia($idArticulo, $idTienda = 1) {
        $sql = 'DELETE FROM articulosStocks '
                . ' WHERE idTienda = ' . $idTienda;
        return self::_consultaDML($sql);
    }


    public static function grabar($datos) {
        if(!isset($datos['fechaRegularizacion'])){
            $datos['fechaRegularizacion'] = date(FORMATO_FECHA_MYSQL);
        }
        return parent::_insert(self::$tabla, $datos);
    }

}
