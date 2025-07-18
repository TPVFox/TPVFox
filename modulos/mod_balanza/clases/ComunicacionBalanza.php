<?php

/**
 * Clase ComunicacionBalanza
 * 
 * Esta clase centraliza todas las consultas y operaciones relacionadas con la comunicación
 * entre el sistema y las balanzas, utilizando consultas MySQL. Permite la gestión de balanzas
 * y sus PLUs asociados, así como la obtención de configuraciones y relaciones con artículos.
 * 
 * Métodos principales:
 * - getBalanza($idBalanza): Obtiene los datos de una balanza por su ID.
 * - listarBalanzas(): Lista todas las balanzas registradas.
 * - insertarBalanza($datos): Inserta una nueva balanza en la base de datos.
 * - actualizarBalanza($datos, $condicion): Actualiza los datos de una balanza según una condición.
 * 
 * Métodos para la tabla modulo_balanza_plus:
 * - getPLUsPorBalanza($idBalanza): Obtiene todos los PLUs asociados a una balanza.
 * - insertarPLU($datos): Inserta un nuevo PLU en una balanza.
 * - actualizarPLU($datos, $condicion): Actualiza un PLU existente.
 * - eliminarPLU($condicion): Elimina un PLU según una condición.
 * 
 * Métodos adicionales:
 * - getBalanzasPorArticulo($idArticulo): Obtiene las balanzas asociadas a un artículo.
 * - getPLUPorArticuloYBalanza($idArticulo, $idBalanza): Obtiene el PLU de un artículo en una balanza específica.
 * - getArticulosPesoPorBalanza($idBalanza): Obtiene los artículos de tipo "peso" asociados a una balanza.
 * - existePLUEnBalanzas($idArticulo): Verifica si un artículo tiene PLU en alguna balanza.
 * - getConfiguracionComunicacion($idBalanza): Obtiene la configuración de comunicación de una balanza.
 * 
 * Nota: Se recomienda centralizar todas las consultas relacionadas con balanzas en esta clase
 * para una mejor organización y mantenimiento del código.
 * 
 */

$rutaCompleta = $RutaServidor.$HostNombre;
require_once $rutaCompleta . '/modulos/claseModelo.php';

class ComunicacionBalanza extends Modelo
{
    protected $tabla = 'modulo_balanza';

    // Obtener una balanza por ID
    public function getBalanza($idBalanza)
    {
        $condicion = "idBalanza = '$idBalanza'";
        $resultado = $this->consulta("SELECT * FROM $this->tabla WHERE $condicion");
        return $resultado['datos'] ?? [];
    }

    // Listar todas las balanzas
    public function listarBalanzas()
    {
        $resultado = $this->consulta("SELECT * FROM $this->tabla");
        return $resultado['datos'] ?? [];
    }

    // Insertar una nueva balanza
    public function insertarBalanza($datos)
    {
        return $this->insert($datos);
    }

    // Actualizar una balanza
    public function actualizarBalanza($datos, $condicion)
    {
        return $this->update($datos, $condicion);
    }

    // Métodos para la tabla modulo_balanza_plus

    // Obtener PLUs de una balanza
    public function getPLUsPorBalanza($idBalanza)
    {
        $sql = "SELECT * FROM modulo_balanza_plus WHERE idBalanza = '$idBalanza'";
        $resultado = $this->consulta($sql);
        return $resultado['datos'] ?? [];
    }

    // Insertar un nuevo PLU
    public function insertarPLU($datos)
    {
        // Cambia la tabla temporalmente para el insert
        $tablaAnterior = $this->tabla;
        $this->tabla = 'modulo_balanza_plus';
        $res = $this->insert($datos);
        $this->tabla = $tablaAnterior;
        return $res;
    }

    // Actualizar un PLU
    public function actualizarPLU($datos, $condicion)
    {
        $tablaAnterior = $this->tabla;
        $this->tabla = 'modulo_balanza_plus';
        $res = $this->update($datos, $condicion);
        $this->tabla = $tablaAnterior;
        return $res;
    }

    // Eliminar un PLU
    public function eliminarPLU($condicion)
    {
        $sql = "DELETE FROM modulo_balanza_plus WHERE $condicion";
        return $this->consultaDML($sql);
    }


    public function getBalanzasPorArticulo($idArticulo)
    {
        $sql = "SELECT b.*, p.plu, p.seccion
            FROM modulo_balanza_plus p
            JOIN modulo_balanza b ON b.idBalanza = p.idBalanza
            WHERE p.idArticulo = '$idArticulo'";
        $resultado = $this->consulta($sql);
        return $resultado['datos'] ?? [];
    }
    public function getPLUPorArticuloYBalanza($idArticulo, $idBalanza)
    {
        $sql = "SELECT * FROM modulo_balanza_plus WHERE idArticulo = '$idArticulo' AND idBalanza = '$idBalanza'";
        $resultado = $this->consulta($sql);
        return $resultado['datos'][0] ?? null;
    }
    public function getArticulosPesoPorBalanza($idBalanza)
    {
        $sql = "SELECT a.*, p.plu, p.seccion
            FROM modulo_balanza_plus p
            JOIN articulos a ON a.idArticulo = p.idArticulo
            WHERE p.idBalanza = '$idBalanza' AND a.tipo = 'peso'";
        $resultado = $this->consulta($sql);
        return $resultado['datos'] ?? [];
    }


    public function existePLUEnBalanzas($idArticulo)
    {
        $sql = "SELECT COUNT(*) as total FROM modulo_balanza_plus WHERE idArticulo = '$idArticulo'";
        $resultado = $this->consulta($sql);
        return ($resultado['datos'][0]['total'] ?? 0) > 0;
    }


    public function getConfiguracionComunicacion($idBalanza)
    {
        $sql = "SELECT * FROM modulo_balanza_config WHERE idBalanza = '$idBalanza'";
        $resultado = $this->consulta($sql);
        return $resultado['datos'][0] ?? [];
    }
}
