<?php

/*
 * @Copyright 2018, Alagoro Software.
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción
 */

include_once $RutaServidor . $HostNombre . '/clases/ClaseConexion.php';

define('ARTICULOS_MAXLINPAG', 12);

define('K_TARIFACLIENTE_ESTADO_ACTIVO', '1');
define('K_TARIFACLIENTE_ESTADO_BORRADO', '2');

define('K_STOCKARTICULO_REGULARIZA', 0);
define('K_STOCKARTICULO_SUMA', 1);
define('K_STOCKARTICULO_RESTA', -1);

define('K_STOCKREGULARIZACION_ESTADO_ACTIVO', '1');
define('K_STOCKREGULARIZACION_ESTADO_BORRADO', '2');

/**
 * Description of claseModelo
 *
 * @author alagoro
 */
class ModeloP {

//    protected static $instance = null;
    protected static $db = null;
//    protected static $tabla;
    protected static $resultado = ['error' => 0, 'consulta' => ''];

//    public static function getInstance() {
//        if (is_null(self::$instance)) {
//            self::$instance = new ModeloP();
//        }
//        return self::$instance;
//    }


    /*
     * Método getDbo()
     * Devuelve la propiedad $db si contiene un valor distinto de nulo.
     * Si es la primera ejecución, $db será nulo, entonces obtiene la conexión 
     * a la base de datos de la clase conexión y la guarda en la propiedad $db
     */
    public static function getDbo() {
        if (is_null(self::$db)) {
            $objConexion = new ClaseConexion();
            if (!$objConexion) {
                self::setResult('', $objConexion->getErrorConexion());
            }
            self::$db = $objConexion->getConexion();
        }
        return self::$db;
    }

    protected static function setResult($sql, $code) {
        ModeloP::$resultado['consulta'] = $sql;
        ModeloP::$resultado['error'] = $code;
    }

    protected static function _consulta($sql) {
        $db = self::getDbo();

        // Realizamos la consulta.
        $error = 0;
        $respuesta = false;
        $smt = $db->query($sql);
        if ($smt) {
            $respuesta = $smt->fetch_all(MYSQLI_ASSOC);
            // (!$datos)||count($datos)==1?$datos[0]:$datos;
        } else {
            $error = $db->error;
        }
        ModeloP::setResult($sql, $error);
        return $respuesta;
    }

    protected function consulta($sql) {
        //Para compatibilidad con desarrollo anterior
        return ModeloP::_consulta($sql);
    }

    //devuelve 0 se es correcto y un código de error si hubo error
    // el mensaje y la consulta se obtienen con funciones: getSQLConsulta y getErrorConsulta.
    protected static function _consultaDML($sql) {
        $db = self::getDbo();

        $respuesta = $db->query($sql);

        ModeloP::setResult($sql, ($respuesta ? 0 : $db->error));

        return $respuesta;
    }

    protected function consultaDML($sql) {
        return ModeloP::_consultaDML($sql);
    }

    protected static function _insert($tabla, $datos, $soloSQL = false) {
        $respuesta = false;
        $updateStr = [];
        if (is_array($datos)) {
            foreach ($datos as $key => $value) {
                $updateStr[] = $key . ' = \'' . $value . '\'';
            }
        } else {
            $updateStr[] = $datos;
        }
        $updateString = implode(', ', $updateStr);

        $sql = 'INSERT ' . $tabla
                . ' SET ' . $updateString;

        ModeloP::setResult($sql, 0);

        if ($soloSQL) {
            $respuesta = ($sql !== '');
        } else {
            if (ModeloP::_consultaDML($sql)) {
                $respuesta = self::$db->insert_id;
            }
        }
        
        return $respuesta;
    }

    protected static function _delete($tabla, $condicion, $soloSQL = false) {
        $respuesta = false;
        $updateStr = [];
        if (!is_array($condicion)) {
            $updateWhere = $condicion;
        } else {
            $updateWhere = implode(' AND ', $condicion);
        }

        $sql = 'DELETE FROM ' . $tabla
                . ' WHERE ' . $updateWhere;

        ModeloP::setResult($sql, 0);

        if ($soloSQL) {
            $respuesta = ($sql !== '');
        } else {
            $respuesta = ModeloP::_consultaDML($sql);
        }

        return $respuesta;
    }

//    protected function insert($tabla,$datos, $soloSQL = false) {
//        return ModeloP::_insert($tabla,$datos, $soloSQL);
//    }

    protected static function _update($tabla, $datos, $condicion, $soloSQL = false) {
        $respuesta = false;
        $updateSet = [];
        if (is_array($datos)) {
            foreach ($datos as $key => $value) {
                $updateSet[] = $key . ' = \'' . $value . '\'';
            }
        } else {
            $updateSet[] = $datos;
        }

        $updateString = implode(', ', $updateSet);

        if (!is_array($condicion)) {
            $updateWhere = $condicion;
        } else {
            $updateWhere = implode(' AND ', $condicion);
        }

        $sql = 'UPDATE ' . $tabla
                . ' SET ' . $updateString
                . ' WHERE ' . $updateWhere;

        ModeloP::setResult($sql, 0);

        if ($soloSQL) {
            $respuesta = true;
        } else
            $respuesta = self::consultaDML($sql);

        return $respuesta;
    }

//    protected function update($tabla, $datos, $condicion, $soloSQL = false) {
//        return ModeloP::_update($tabla, $datos, $condicion, $soloSQL);
//    }

    public static function hayErrorConsulta() {
        return ModeloP::$resultado['error'] !== 0;
    }

    public static function getErrorConsulta() {
        return ModeloP::$resultado['error'];
    }

    public static function getSQLConsulta() {
        return ModeloP::$resultado['consulta'];
    }

    protected static function _leer($tabla, $condiciones='', $columnas = [], $joins = [], $limit = 0, $offset = 0, $soloSQL = false) {

        $columnasSql = count($columnas) > 0 ? implode(',', $columnas) : '*';

        if (!is_array($condiciones)) {
            $updateWhere = $condiciones;
        } else {
            $updateWhere = implode(' AND ', $condiciones);
        }

        $sql = 'SELECT ' . $columnasSql
                . ' FROM ' . $tabla;
        if ($joins) {
            if (!is_array($joins)) {
                $selectjoin = $joins;
            } else {
                $selectjoin = implode(', ', $joins);
            }
            $sql .= ' JOIN ' . $selectjoin;
        }

        if($updateWhere){
        $sql .= ' WHERE  ' . $updateWhere;
        }

        if ($limit != 0) {
            $sql .= ' LIMIT ' . $limit;
        }
        if ($offset != 0) {
            $sql .= ' OFFSET ' . $offset;
        }

        ModeloP::setResult($sql, 0);


        if ($soloSQL) {
            $resultado = true;
        } else
            $resultado = self::_consulta($sql);
        return $resultado;
    }

}
