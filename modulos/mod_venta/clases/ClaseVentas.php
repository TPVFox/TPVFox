<?php
// Clase base para modulo de compras.
require_once __DIR__ . '/../../../clases/DB.php';
include_once $URLCom . '/clases/traits/MontarAdvertenciaTrait.php';

class ClaseVentas
{
    use MontarAdvertenciaTrait;

    public $db; //(Objeto) Es la conexion;
    public $affected_rows; // Numero filas que afecto la consulta, se guarda cuando hacemos una consulta.
    public $insert_id; // id del registro insertado. ( ojo.. como sabe que el campo es id)
    public function consulta($sql, $params = [])
    {
        // Realizamos la consulta a traves de la capa DB parametrizada.
        $db = $this->db;
        $smt = (new DB($db))->pquery($sql, $params);
        if ($smt) {
            return $smt;
        } elseif ($db->error === '') {
            // INSERT/UPDATE/DELETE con exito: get_result() devuelve false sin error.
            return true;
        } else {
            $respuesta = array();
            $respuesta['consulta'] = $sql;
            $respuesta['error'] = $db->error;
        }
        // Guardamos la filas afectadas.
        $this->affected_rows = $db->affected_rows;
        $this->insert_id = $db->insert_id;
        return $respuesta;
    }

    public function __construct($conexion)
    {
        $this->db = $conexion;
    }
    public function sumarIvaBases($from_where)
    {
        //Función para sumar los ivas de un pedido
        $db = $this->db;
        // TODO: revisar: $from_where es un fragmento SQL (from+where) construido por el llamante,
        // no un valor ligable con ?. Los llamantes deberian pasar valores parametrizados.
        $smt = (new DB($db))->pquery('select sum(importeIva ) as importeIva , sum(totalbase) as  totalbase ' . $from_where);
        if ($result = $smt->fetch_assoc()) {
            $sumaIvasBases = $result;
        }
        return $sumaIvasBases;
    }

    public function deleteRegistrosTabla($tabla, $where = '')
    {
        //@ Objetivo:
        // Eliminar registros de una tabla y si indicamos where, pues eliminamos solo los registros de la condicion,.
        //@ Parametros:
        //   $tabla-> (string) nombre de la tabla.
        //   $where-> (string) completo where y lo queramos indicar..
        $sql = 'DELETE FROM ' . $tabla . ' ' . $where;
        $smt = $this->consulta($sql);
        return $smt;
    }
    public function SelectUnResult($tabla, $where)
    {
        $db = $this->db;
        $sql = 'SELECT * from ' . $tabla . ' where ' . $where;
        $smt = $this->consulta($sql);
        $resultado  = array();
        if (gettype($smt) === 'array') {
            $respuesta['error'] = $smt['error'];
            $respuesta['consulta'] = $smt['consulta'];
            return $respuesta;
        } else {
            if ($result = $smt->fetch_assoc()) {
                $resultado = $result;
            }
        }
        return $resultado;
    }
    public function SelectVariosResult($tabla, $where)
    {
        $respuesta = array();
        $db = $this->db;
        $sql = 'SELECT * from ' . $tabla . ' where ' . $where;
        $smt = $this->consulta($sql);
        if (gettype($smt) === 'array') {
            // Devuelve array con error y consulta
            $respuesta = $smt;
        } else {
            while ($result = $smt->fetch_assoc()) {
                array_push($respuesta, $result);
            }
        }
        return $respuesta;
    }
    // ------------------- METODOS COMUNES ----------------------  //
    // -  Al final de cada clase suelo poner aquellos metodos   -  //
    // - que considero que puede ser añadimos algun controlador -  //
    // - comun del core, ya que pienso son necesarios para mas  -  //
    // - modulos.                                                  //
    // ----------------------------------------------------------  //
    public function obtenerDatosUsuario($id)
    {
        // @ Objetivo:
        // Obtener los datos de un usuario del id que recibe, es necesarios para obtener el nombre de
        // creadores o quienes modificaron.
        $tabla = 'usuarios';
        $where = '`id`=' . $id;
        $datosUsuario = $this->SelectUnResult($tabla, $where);
        return $datosUsuario;
    }

    // public function montarAdvertencia($tipo,$mensaje,$html='KO'){
    //     // @ Objetivo:
    //     // Montar array para error/advertencia , tb podemos devolver el html
    //     // @ Parametros
    //     //  $tipo -> (string) Indica tipo error/advertencia puede ser : danger,warning,success y info
    //     //  $mensaje -> puede ser string o array. Este ultimos es comodo por ejemplo en las cosultas.
    //     //  $html -> (string) Indicamos si queremos que devuelva html en vez del array.
    //     // @ Devolvemos
    //     //  Array ( tipo, mensaje ) o html con advertencia o error.
    //     $advertencia = array ( 'tipo'    =>$tipo,
    //                       'mensaje' => $mensaje
    //                     );
    //     if ($html === 'OK'){
    //         $advertencia = '<div class="alert alert-'.$tipo.'">'
    //                       . '<strong>'.$tipo.' </strong><br/> ';
    //                 if (is_array($mensaje)){
    //                     $p = print_r($mensaje,TRUE);
    //                     $advertencia .= '<pre>'.$p.'</pre>';
    //                 } else {
    //                     $advertencia .= $mensaje;
    //                 }
    //                 $advertencia .= '</div>';

    //     }

    //     return $advertencia;
    // }

}
