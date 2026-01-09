<?php
$rutaCompleta = $RutaServidor . $HostNombre;
require_once $rutaCompleta . '/modulos/claseModelo.php';

class ClaseTemperatura extends Modelo
{
    protected $tablaDispositivos = 'dispositivos';

    protected $tablaTemperaturas = 'temperaturas';

    public function addDispositivo($datos)
    {
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

    public function updateDispositivo($id, $datos)
    {
        $sql = "UPDATE " . $this->tablaDispositivos . " SET
            nombre = '" . $datos['nombre'] . "',
            ubicacion = '" . $datos['ubicacion'] . "',
            estado = '" . $datos['estado'] . "'
            WHERE idDispositivo = " . intval($id);
        $consulta = $this->consultaDML($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
    }

    public function getDispositivo($id)
    {
        $sql = "SELECT * FROM " . $this->tablaDispositivos . " WHERE idDispositivo = " . intval($id);
        $consulta = $this->consulta($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
        return $consulta['datos'][0];
    }

    public function getDispositivos()
    {
        $sql = "SELECT * FROM " . $this->tablaDispositivos;
        $consulta = $this->consulta($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
        return $consulta['datos'];
    }
    // Añadir temperaturas para varios dispositivos
    public function addTemperaturas($datosArray)
    {
        foreach ($datosArray as $datos) {
            $sql = "INSERT INTO " . $this->tablaTemperaturas . " (idDispositivo, temperatura, idUsuario, fechaRegistro) VALUES (
                " . $datos['idDispositivo'] . ",
                " . $datos['temperatura'] . ",
                " . $datos['idUsuario'] . ",
                NOW()
            )";
            $consulta = $this->consultaDML($sql);
        }
    }

    public function getTemperaturas()
    {
        $sql = "SELECT * FROM " . $this->tablaTemperaturas;
        $consulta = $this->consulta($sql);
        if (isset($consulta['error'])) {
            return $consulta;
        }
        return $consulta['datos'];
    }
}
