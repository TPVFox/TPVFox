<?php
// A partir de un array de temeperaturas y otro de dispositivos añadir a cada dispositivo su última temperatura registrada y la fecha de la misma
function agregarUltimasTemperaturas(&$dispositivos, $temperaturas) {
    // Crear un array asociativo para acceder rápidamente a la última temperatura por idDispositivo
    $ultimasTemperaturas = array();
    foreach ($temperaturas as $temp) {
        $idDispositivo = $temp['idDispositivo'];
        // Si no existe o si la fecha es más reciente, actualizar
        if (!isset($ultimasTemperaturas[$idDispositivo]) || strtotime($temp['fechaRegistro']) > strtotime($ultimasTemperaturas[$idDispositivo]['fechaRegistro'])) {
            $ultimasTemperaturas[$idDispositivo] = $temp;
        }
    }
    // Ahora agregar la última temperatura y fecha a cada dispositivo
    foreach ($dispositivos as &$dispositivo) {
        $idDispositivo = $dispositivo['idDispositivo'];
        if (isset($ultimasTemperaturas[$idDispositivo])) {
            $dispositivo['ultima_temperatura'] = $ultimasTemperaturas[$idDispositivo]['temperatura'];
            $dispositivo['ultimo_registro'] = $ultimasTemperaturas[$idDispositivo]['fechaRegistro'];
        } else {
            $dispositivo['ultima_temperatura'] = null;
            $dispositivo['ultimo_registro'] = null;
        }
    }
    return $dispositivos;
}