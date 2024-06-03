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
class TFModelo extends ModeloP
{

    protected $tabla; // La tabla tenemos asignarla con setTabla
    protected $fallo = ['descripcion' => 0, 'consulta' => '', 'time_error' => '']; // error = 0 que no hubo.

    protected function consulta($sql)
    {
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

    protected function consultaDML($sql)
    {
        // @ Objetivo:
        // Hacer consultas SQL de tipo DML son las que permiten visualizar y modificar los datos de las tablas (INSERT, UPDATE, DELETE)
        // sin incluir los select
        // @ Parametro:
        // $sql = La consulta.
        // @ Devolvemos:
        // $ respuesta  = false si hubo error, que podemos recuperar con $this->getFallo.
        //               array() con affected_rows y insert_id
        $db = parent::getDbo();

        $smt = $db->query($sql);
        // $smt = Puede ser false o true en Delete,Insert,Update....
        if ($smt === false) {
            // hubo un error, lo añadimos a errores.
            $error = $db->error;
            $this->setFallo($sql, $error);
            $respuesta = false; // Devolvemos falso.

        } else {
            $respuesta = array();
            $respuesta['affected_rows'] = $db->affected_rows; // devolvemos cuantos fueron afectados
            $respuesta['insert_id'] = $db->insert_id;
        }

        return $respuesta;
    }

    protected function insert($datos, $soloSQL = false)
    {
        $respuesta = false;
        // No queremos SETs de valores nulos
        $insertString = $this->stringSet($datos, true);
        
        $sql = 'INSERT ' . $this->tabla
            . ' SET ' . $insertString;
        if ($soloSQL) {
            $respuesta = $sql;
        } else {
            $r = self::consultaDML($sql);
            if ($r !== false) {
                $respuesta = $r['insert_id'];
            }
        }
        return $respuesta;
    }

    protected function update($datos, $condicion, $soloSQL = false)
    {
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
            $respuesta = $sql;
        } else {
            $r = self::consultaDML($sql);
            if ($r !== false) {
                $respuesta = $r['affected_rows'];
            }
        }
        return $respuesta;

    }

    protected function setTabla($tabla)
    {
        $this->tabla = $tabla;

    }

    protected function setFallo($sql, $code)
    {
        $this->fallo['consulta'] = $sql;
        $this->fallo['descripcion'] = $code;
        $this->fallo['time_error'] = time();

    }

    public function getFallo()
    {
        return $this->fallo;
    }

    protected function stringSet($datos, $deleteNull = false)
    {
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
                if (!$deleteNull || ($deleteNull && !is_null($value))) {
                    $value = str_replace("'", "\'", $value);
                    $Set[] = $key . ' = \'' . $value . '\'';
                }
            }
        } else {
            $Set[] = $datos;
        }

        $stringSet = implode(', ', $Set);
        return $stringSet;
    }

    protected function stringCondicion($condiciones, $operador = 'AND')
    {
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
            $stringCondicion = implode(' ' . $operador . ' ', $condiciones);
        }
        return $stringCondicion;
    }

    public function InfoTabla($tabla, $tipo_campo = 'si')
    {
        // Funcion que nos proporciona informacion de la tabla que le indicamos
        /* Nos proporciona informacion como nombre tabla, filas, cuando fue creada, ultima actualizacion .. y mas campos interesantes:
         * Ejemplo de print_r de virtuemart_products
         *         [Name] => virtuemart_products  // Normal ya que el prefijo ....
         *        [Rows] => Numero registros  // ESTE ES IMPORTANTE, el que analizamos inicialmente.
         *        [Create_time] => 2016-10-31 18:23:52 // Normal ya que nunca coincidira... se crearía fechas distintas.
         *        [Update_time] => 2016-10-31 20:46:35 // Lo recomendable que la hora Update ser superior en nuestra BD , pero no siempre será
         */
        $Bd = parent::getDbo();
        $fila = array();
        $consulta = 'SHOW TABLE STATUS WHERE `name`="' . $tabla . '"';
        $Queryinfo = $Bd->query($consulta);
        // Hay que tener en cuenta que no produce ningún error...
        $Ntablas = $Bd->affected_rows;
        if ($Ntablas == 0) {
            $fila['error'] = 'Error tabla no encontrada - ' . $tabla;
        } else {
            $fila['info'] = $Queryinfo->fetch_assoc();
        }
        if (!isset($fila['error'])) {
            $campos = array();
            $sqlShow = 'SHOW COLUMNS FROM ' . $tabla;
            $fila['consulta_campos'] = $sqlShow;
            if ($res = $Bd->query($sqlShow)) {
                while ($dato_campo = $res->fetch_row()) {
                    if ($tipo_campo === 'si') {
                        // Obtenemos nombre campo y tipo de campo.
                        $campos[] = $dato_campo[0] . ' ' . $dato_campo[1];
                    } else {
                        $campos[] = $dato_campo[0];
                    }
                }
                $fila['campos'] = $campos;
            } else {
                // Si NO existe o no sale mal enviamos un error.
                $fila['error'] = $Bd->error;
            }
        }
        $fila['consulta_info'] = $consulta;

        return $fila;

    }

    public function conexionBDTPV()
    {
        // Devolvemos la conexion para versiones anteriores de modelos.
        return parent::getDbo();

    }

}
