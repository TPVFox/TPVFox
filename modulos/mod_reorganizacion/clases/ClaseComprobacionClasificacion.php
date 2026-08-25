<?php

// @ Objetivo
// Aplicar la regla ordenada que compara la existencia exigida por los movimientos
// con el stock mínimo justificado y su margen, y componer el estado del producto —
// seguro, no seguro, dudoso o no comparable— con el marcado de existencia negativa,
// que no se absorbe en el estado. No lee la base: opera sobre lo que ya le llega
// compuesto.
class ClaseComprobacionClasificacion
{
    public function clasificar($filas)
    {
        // @ Objetivo
        // Para cada fila, calcular la existencia que los movimientos del ejercicio
        // vigente exigen y aplicar la regla ordenada y total: sin correspondencia o
        // sin stock justificado, no comparable; si no, seguro, no seguro o dudoso
        // según la diferencia frente al margen del producto.
        // @ Parametros
        //      $filas -> array de filas ya compuestas: 'comparable' (admisión del
        //          resultado), 'minimoAlcanzado' y 'saldoDeApertura' (del vigente, a
        //          través del fichero admitido), 'stockJustificado' y 'margen' (del
        //          cálculo del mínimo).
        // @ Devolvemos
        //      array de filas con 'existenciaExigida' y 'estado' añadidos. El
        //      marcado que ya traía la fila no se toca.
        $resultado = array();
        foreach ($filas as $fila) {
            $existenciaExigida = abs($fila['minimoAlcanzado']) + $fila['saldoDeApertura'];

            $fila['existenciaExigida'] = $existenciaExigida;
            $fila['estado'] = $this->estadoDe($fila, $existenciaExigida);
            $resultado[] = $fila;
        }
        return $resultado;
    }

    private function estadoDe($fila, $existenciaExigida)
    {
        if (!$fila['comparable']) {
            return 'no_comparable';
        }
        if ($fila['stockJustificado'] === null) {
            return 'no_comparable';
        }

        $diferencia = $existenciaExigida - $fila['stockJustificado'];
        if (abs($diferencia) <= $fila['margen']) {
            return 'seguro';
        }
        return ($diferencia < 0) ? 'no_seguro' : 'dudoso';
    }
}
