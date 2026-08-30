<?php

include_once $RutaServidor . $HostNombre . '/modulos/mod_reorganizacion/clases/ClaseComprobacionStockCantidad.php';

// @ Objetivo
// Aplicar la regla ordenada que compara la existencia exigida por los movimientos
// con el stock mínimo justificado y su margen, y componer el estado del producto —
// seguro, no seguro, dudoso o no comparable— con el marcado de existencia negativa,
// que no se absorbe en el estado. No lee la base: opera sobre lo que ya le llega
// compuesto.
class ClaseComprobacionStockClasificacion
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
            // La cantidad que los movimientos exigen es cuánto bajó la curva por debajo
            // de donde arrancó, y eso es la resta entre el saldo con que abrió y el punto
            // más bajo que alcanzó. Escrita así, el saldo registrado de apertura entra en
            // los dos términos y se cancela: la magnitud sale de los movimientos y de
            // nada más, que es la condición de todo este cálculo. Sumar el punto más bajo
            // en valor absoluto daría el mismo número, pero solo mientras ese punto sea
            // negativo, y esa condición la establece el ejercicio que emitió el fichero,
            // no este: aquí quedaría el saldo declarado decidiendo la clasificación en
            // cuanto llegara una fila que no la cumpliera.
            $existenciaExigida = ClaseComprobacionStockCantidad::normalizar(
                $fila['saldoDeApertura'] - $fila['minimoAlcanzado']
            );

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

        // El orden importa: primero si la diferencia cabe en el margen y solo después
        // hacia qué lado cae. Una diferencia pequeña y en contra sigue estando dentro
        // del margen, y preguntar antes por el sentido la sacaría del estado seguro por
        // una imprecisión de pesaje que el propio margen existe para admitir. En los
        // productos que no se registran por peso el margen es cero y la comparación es
        // de igualdad: se sostiene porque las dos cantidades llegan ya llevadas a los
        // decimales con que existen, de modo que dos cantidades iguales son el mismo
        // número y no dos que se parecen.
        $diferencia = $existenciaExigida - $fila['stockJustificado'];
        if (abs($diferencia) <= $fila['margen']) {
            return 'seguro';
        }
        return ($diferencia < 0) ? 'no_seguro' : 'dudoso';
    }
}
