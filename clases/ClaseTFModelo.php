<?php

/*
 *  DDL, DML y DCL  https://todopostgresql.com/diferencias-entre-ddl-dml-y-dcl/
 */

require_once $RutaServidor . $HostNombre . '/modulos/claseModeloP.php';

/**
 * Description of claseModelo
 *
 * @author alagoro
 */
class TFModelo extends ModeloP {

    protected $tabla; // La tabla tenemos asignarla con setTabla
    protected $fallo = ['descripcion' => 0, 'consulta' => '', 'time' => '']; // error = 0 que no hubo.


    protected function consulta($sql) {
        // Realizamos la consulta.
        $smt = parent::consulta($sql);
        $respuesta = [];
        $respuesta['consulta'] = $this->getSQLConsulta();
        if ($smt) {
            $respuesta['datos'] = $smt;
        } else {
            if ($this->getErrorConsulta() != '0') {
                $respuesta['error'] = $this->getErrorConsulta();
            }
        }
        return $respuesta;
    }

    protected function consultaDML($sql) {
        // Las consultas SQL de tipo DML son las que permiten visualizar y modificar los datos de las tablas (INSERT, UPDATE, DELETE)
        // No incluyo los select
        $db = parent::getDbo();

        $smt = $db->query($sql);
        // $smt = Puede ser false o true en Delete,Insert,Update....
        if ($smt === false){
            // hubo un error, lo añadimos a errores.
            $error = $db->error;
            $this->setFallo($sql, $error);
            $respuesta = false; // Devolvemos falso.

        } else {
            $respuesta =$db->affected_rows; // devolvemos cuantos fueron afectados

        }
        
        return $respuesta;
    }

    

    protected function insert($datos, $soloSQL = false) {
        $respuesta = false;
        $insertString = $this->stringSet($datos);
        $sql = 'INSERT '. $this->tabla
                    . ' SET '. $insertString;
        
        if ($soloSQL) {
            $respuesta = true;
        } else {
            $respuesta = self::consultaDML($sql);
        }
        return $respuesta;
    }
    // Paso directamente MODELOP en los siguiente metodo.
    // Poco a poco eliminare MODELOP

    protected function update($datos, $condicion, $soloSQL = false) {
        // @Objetivo
        
        // @ Parametros.

        // @ Respuesta

        // @ Nota:
        // La tabla es la que tenemos asignada en propiedad tabla. 
        $respuesta = false;
        
        $updateString = $this->stringSet($datos);
        $updateWhere = $this->stringCondicion($condicion);
        $sql = 'UPDATE ' . $this->tabla
                . ' SET ' . $updateString
                . ' WHERE ' . $updateWhere;

        if ($soloSQL) {
            $respuesta = true;
        } else {
            $respuesta = self::consultaDML($sql);
        }
        return $respuesta;

    }

    protected function setTabla($tabla){
        $this->tabla = $tabla;
    
    }

    protected function setFallo($sql, $code) {
        $this->fallo['consulta'] = $sql;
        $this->fallo['descripcion'] = $code;
        $this->fallo['time'] = time();

    }

    public function getFallo(){
        return $this->fallo;
    }

    protected function stringSet($datos){
        // @ Objetivo:
        // Devolver un string con campo = valor,... con todos los campos que mandemos en datos
        // @ Parametros:
        // Puede ser un array o un string. Ejemplo array (nombre_campo1 =valor;nombre_campo2 => valor2)
        // @ Devuelve:
        // String con campo = valor,... con todos los campos que mandemos en datos, separados por comas
        //          si no fuera array o string con datos devuelve un string vacio.
        $Set = [];
        if (is_array($datos)) {
            foreach ($datos as $key => $value) {
                $Set[] = $key . ' = \'' . $value . '\'';
            }
        } else {
            $Set[] = $datos;
        }

        $stringSet = implode(', ', $Set);
        return $stringSet;
    }

    protected function stringCondicion($condiciones,$operador ='AND'){
        // @ Objetivo:
        //   De un array devolver un string con los valores, separados por AND
        // @ Parametros:
        //   $condiciones = Array o  string : Array tiene solo se obtiene el valor, donde ya tiene que estas condicion creada
        //   y si es string es una condicion unica.
        //   $operador = 'AND' por defecto si no viene, o 'OR'
        // @ Devuelve:
        //   String con las condiciones separadas AND y si no fuera array y tampoco string con datos devuelve string vacio
        if (!is_array($condiciones)) {
            $stringCondicion = $condiciones;
        } else {
            $stringCondicion = implode(' '.$operador.' ', $condiciones);
        }
        return $stringCondicion;
    }
    
    

}
