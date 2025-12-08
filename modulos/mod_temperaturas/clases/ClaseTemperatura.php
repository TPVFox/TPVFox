<?php
$rutaCompleta = $RutaServidor.$HostNombre;
require_once $rutaCompleta . '/modulos/claseModelo.php';

class ClaseTemperatura extends Modelo
{
    protected $tablaDispositivos = 'dispositivos';

    protected $tablaTemperaturas = 'temperaturas';

    public function addDispositivo($datos){
        // Ahora se esperan los nuevos campos en $datos
        $sql = "INSERT INTO " . $this->tablaDispositivos . " (nombre, ubicacion, estado) VALUES (
            '" . $datos['nombre'] . "',
            '" . $datos['ubicacion'] . "',
            '" . $datos['estado'] . "'
        )";
        $consulta = $this->consultaDML($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
    }

    public function getDispositivos(){
        $sql = "SELECT * FROM " . $this->tablaDispositivos;
        $consulta = $this->consulta($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
        return $consulta['datos'];
    }   
}