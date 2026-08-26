<?php

// @ Objetivo
// Con qué precisión existe una cantidad de producto, y cómo se escribe.
//
// Las existencias se guardan con seis decimales, de modo que por debajo de una
// millonésima no hay cantidad: hay el residuo de haber sumado en coma flotante. Un
// producto cuyos movimientos se cancelan exactamente no da cero, da unas diezmilésimas
// de billonésima negativas, y eso es menor que cero para el lenguaje aunque no lo sea
// para el negocio. Sin normalizar, ese residuo decide que un producto entró en negativo
// y lo mete en el conjunto que se examina.
//
// Escribir también necesita decisión propia: el lenguaje pasa a notación científica por
// debajo de una cienmilésima, y «1.0E-6» no es un número decimal para el esquema del
// fichero de intercambio, aunque una millonésima sí sea una cantidad legítima. Las dos
// cosas viven juntas porque son la misma: qué es una cantidad aquí.
class ClaseComprobacionStockCantidad
{
    // Los decimales con que el esquema de la base guarda las existencias.
    const DECIMALES = 6;

    public static function normalizar($valor)
    {
        // @ Objetivo
        // Dejar un valor calculado en la precisión en que la cantidad existe. Se aplica
        // en cuanto el valor se produce, no en cuanto se compara: el valor viaja a la
        // pantalla, al fichero y al informe, y un saldo con residuo mostrado es tan
        // falso como uno comparado mal.
        // @ Parametros
        //      $valor -> float|string|int.
        // @ Devolvemos
        //      float, sin cero negativo: -0,0 y 0,0 son la misma cantidad y han de dar
        //      el mismo texto y la misma comparación.
        $normalizado = round((float) $valor, self::DECIMALES);
        return ($normalizado == 0.0) ? 0.0 : $normalizado;
    }

    public static function comoTexto($valor)
    {
        // @ Objetivo
        // Escribir una cantidad como número decimal, nunca en notación científica. Lo
        // usan por igual el fichero de intercambio, el resumen de contenido que lo
        // acredita y el informe final: si los tres no escribieran igual, el resumen
        // dejaría de cuadrar sobre un fichero intacto.
        // @ Parametros
        //      $valor -> float|string|int.
        // @ Devolvemos
        //      string, sin ceros finales sobrantes y sin cero negativo.
        $texto = sprintf('%.' . self::DECIMALES . 'F', self::normalizar($valor));

        if (strpos($texto, '.') !== false) {
            $texto = rtrim(rtrim($texto, '0'), '.');
        }

        return ($texto === '' || $texto === '-0') ? '0' : $texto;
    }
}
