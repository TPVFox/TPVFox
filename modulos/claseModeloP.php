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

define('K_STOCKARTICULO_SUMA', 1);
define('K_STOCKARTICULO_RESTA', -1);

/**
 * Description of claseModelo
 *
 * @author alagoro
 */
class ModeloP {

    protected $db;
    protected $tabla;
    protected $resultado;

    public function __construct($conexion = null ) {
        $this->resultado = ['error' => 0, 'consulta' => ''];
        if(is_null($conexion)){
            $objConexion = new ClaseConexion();            
            if(!$objConexion){
                $this->resultado['error'] = $objConexion->getErrorConexion();
            }
            $conexion = $objConexion->getConexion();
        }
        $this->db = $conexion;
    }

    protected function consulta($sql) {
        // Realizamos la consulta.
        $respuesta = false;
        $this->resultado['consulta'] = $sql;

        $smt = $this->db->query($sql);
        if ($smt) {
            $respuesta = $smt->fetch_all(MYSQLI_ASSOC);
            $this->resultado['error'] = 0;
            // (!$datos)||count($datos)==1?$datos[0]:$datos;
        } else {
            $this->resultado['error'] = $this->db->error;
        }
        return $respuesta;
    }

    protected function consultaDML($sql) {
        // Realizamos la consulta.
        $respuesta = $this->db->query($sql);

        $this->resultado['consulta'] = $sql;

        $this->resultado['error'] = $respuesta ? 0 : $this->db->error;

        return $respuesta;
    }

    protected function insert($datos, $soloSQL = false) {
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
        
        $sql = 'INSERT ' . $this->tabla
                . ' SET ' . $updateString;

        $this->resultado['consulta'] = $sql;
        $this->resultado['error'] = 0;

        if ($soloSQL) {
            $respuesta = ($sql!=='');
        } else {
            if ($this->consultaDML($sql)) {
                $respuesta = $this->db->insert_id();
            }
        }

        return $respuesta;
    }

    protected function update($datos, $condicion, $soloSQL = false) {
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

        $sql = 'UPDATE ' . $this->tabla
                . ' SET ' . $updateString
                . ' WHERE ' . $updateWhere;

        $this->resultado['consulta'] = $sql;
        $this->resultado['error'] = 0;
        if ($soloSQL) {
            $respuesta = true;            
        } else
            $respuesta = $this->consultaDML($sql);

        return $respuesta;
    }

    public function hayErrorConsulta(){
        return $this->resultado['error'] !== 0;
    }
    
    public function getErrorConsulta(){
        return $this->resultado['error'];
    }
    
    public function getSQLConsulta(){
        return $this->resultado['consulta'] ;
    }
}
