<?php
// Esta clase sirve para validar datos con relación temporal (temperaturas registradas en fechas consecutivas)
// Sigue el sistema Levey Jennings para detectar anomalías en las lecturas
class ClaseValidacion
{
    private $datos = array(
        'fechas' => array(),
        'valores' => array(),
        'usuario' => array(),
        'desviacion' => array(),
        'reglas' => array(),
        'tipo' => array(),
        'acciones' => array()
    );

    private $media;
    private $desviacionEstandar;
    
    public function __construct($datos)
    {
        $this->datos = $datos;
        $this->calcularEstadisticas();
        $this->validarReglas();
    }
    
    private function calcularEstadisticas()
    {
        $valores = $this->datos['valores'];
        $n = count($valores);
        if ($n == 0) {
            $this->media = 0;
            $this->desviacionEstandar = 0;
            return;
        }
        $suma = array_sum($valores);
        $this->media = $suma / $n;

        $sumaCuadrados = 0;
        foreach ($valores as $valor) {
            $sumaCuadrados += pow($valor - $this->media, 2);
        }
        $this->desviacionEstandar = sqrt($sumaCuadrados / $n);
        if ($this->desviacionEstandar == 0) {
            $this->desviacionEstandar = 0.00001; // Evitar división por cero en validaciones
        }
    }
    
    // Validar reglas de Levey Jennings
    // | Regla | Tipo              | Acción principal            |
    // | ----- | ----------------- | --------------------------- |
    // | 1:2s  | Advertencia       | Repetir medición            |
    // | 1:3s  | Crítica           | Verificar puertas y esperar |
    // | 2:2s  | Tendencia         | Evaluar entorno             |
    // | R:4s  | Uso incorrecto    | Reforzar hábitos            |
    // | 4:1s  | Cambio progresivo | Validar entorno             |
    // | 10:x  | Sesgo             | Solo válido en estación     |
    private function validarReglas()
    {
        $valores = $this -> datos['valores'];
        $n = count($valores);
        $counter_2s = 0; // Contador para la regla 2:2s
        $counter_1s = 0; // Contador para la regla 4:1s
        $counter_x = 0;  // Contador para la regla 10:x
        $DSanterior = null; // Diferencia con la DS anterior para la regla R:4s

        for ($i = 0; $i < $n; $i++) {
            $valor = $valores[$i];
            $desviacion = $valor - $this->media;
            $this->datos['desviacion'][$i] = $desviacion/$this->desviacionEstandar;
            $absDesviacion = abs($desviacion);
            $reglaAplicada = null;
            $tipo = null;
            $accion = null;
            // Regla 1:2s
            if ($desviacion > 2 * $this->desviacionEstandar or $desviacion < -2 * $this->desviacionEstandar) {
                $reglaAplicada = '1:2s';
                $tipo = 'Advertencia';
                $accion = 'Repetir medición';
            }
            // Regla 1:3s
            if ($absDesviacion > 3 * $this->desviacionEstandar) {
                $reglaAplicada = '1:3s';
                $tipo = 'Crítica';
                $accion = 'Verificar puertas y esperar';
            }
            // Si lo valores desviación anterior es positiva y la actual negativa o viceversa reiniciar contador
            if ($DSanterior !== null) {
                if (($desviacion > 0 && $DSanterior < 0) || ($desviacion < 0 && $DSanterior > 0)) {
                    $counter_2s = 0;
                    $counter_1s = 0;
                    $counter_x = 0;
                }
            }
            // Regla 2:2s (tiene que estar en la misma dirección)
            if ($desviacion > 2 * $this->desviacionEstandar) {
                $counter_2s++;
                if ($counter_2s >= 2) {
                    $reglaAplicada = '2:2s';
                    $tipo = 'Tendencia';
                    $accion = 'Evaluar entorno';
                }
            } elseif ($desviacion < -2 * $this->desviacionEstandar) {
                $counter_2s++;
                if ($counter_2s >= 2) {
                    $reglaAplicada = '2:2s';
                    $tipo = 'Tendencia';
                    $accion = 'Evaluar entorno';
                }
            } else {
                $counter_2s = 0; // Resetear si no cumple la condición
            }
            // Regla R:4s
            if ($DSanterior !== null) {
                if (abs($desviacion - $DSanterior) > 4 * $this->desviacionEstandar) {
                    $reglaAplicada = 'R:4s';
                    $tipo = 'Uso incorrecto';
                    $accion = 'Reforzar hábitos';
                }
            }
            // Regla 4:1s (tiene que estar en la misma dirección)
            if ($desviacion > $this->desviacionEstandar) {
                $counter_1s++;
                if ($counter_1s >= 4) {
                    $reglaAplicada = '4:1s';
                    $tipo = 'Cambio progresivo';
                    $accion = 'Validar entorno';
                }
            } elseif ($desviacion < -$this->desviacionEstandar) {
                $counter_1s++;
                if ($counter_1s >= 4) {
                    $reglaAplicada = '4:1s';
                    $tipo = 'Cambio progresivo';
                    $accion = 'Validar entorno';
                }
            } else {
                $counter_1s = 0; // Resetear si no cumple la condición
            }
            // Regla 10:x
            if ($desviacion > 0) {
                $counter_x++;
                if ($counter_x >= 10) {
                    $reglaAplicada = '10:x';
                    $tipo = 'Sesgo';
                    $accion = 'Solo válido en estación';
                }
            } elseif ($desviacion < 0) {
                $counter_x++;
                if ($counter_x >= 10) {
                    $reglaAplicada = '10:x';
                    $tipo = 'Sesgo';
                    $accion = 'Solo válido en estación';
                }
            } else {
                $counter_x = 0; // Resetear si no cumple la condición
            }
            $DSanterior = $desviacion;

            // si no se ha aplicado ninguna regla, marcar como 'Normal'
            if ($reglaAplicada === null) {
                $reglaAplicada = 'Normal';
                $tipo = 'Normal';
                $accion = 'Ninguna acción requerida';
            }
            $this->datos['reglas'][$i] = $reglaAplicada;
            $this->datos['tipo'][$i] = $tipo;
            $this->datos['acciones'][$i] = $accion;
        }
    } 

    // getter de los resultados de la validación
    public function getResultados()
    {
        return $this->datos;
    }

    public function getMedia()
    {
        return $this->media;
    }

    public function getDesviacionEstandar()
    {
        return $this->desviacionEstandar;
    }
}
