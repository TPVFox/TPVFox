<?php

include_once $URLCom . '/clases/traits/MontarAdvertenciaTrait.php';

// @ Objetivo
// Las tres formas que puede tener una relación entre productos, y la admisión: lo que se
// comprueba de una relación antes de guardarla. No lee la base: recibe el tipo y las líneas.
class RelacionesFormas
{
    use MontarAdvertenciaTrait;

    // Cuántos productos entran y cuántos salen en cada forma. Por cada lado hay siempre al
    // menos uno; true indica que ese lado admite uno solo.
    const FORMAS = array(
        '1:N' => array('unaSolaEntrada' => true, 'unaSolaSalida' => false),
        'N:1' => array('unaSolaEntrada' => false, 'unaSolaSalida' => true),
        '1:1' => array('unaSolaEntrada' => true, 'unaSolaSalida' => true),
    );

    public function admitir($tipo, $lineas, $esNueva)
    {
        // @ Objetivo
        // Decidir si una relación se puede guardar. Las comprobaciones van en orden y la
        // primera que falla da el motivo: tipo, forma, productos repetidos y cantidades.
        // @ Parametros
        //      $tipo -> (array) la fila del tipo de relación, o null si no existe.
        //      $lineas -> (array) una línea por producto: idArticulo, sentido, cantidad,
        //          unidad y estado.
        //      $esNueva -> (bool) true si es una relación que todavía no ha regido.
        // @ Devolvemos
        //      ['datos'] = 'admitida', o ['error'] con el motivo del rechazo.
        $motivo = '';

        // El tipo
        if ($tipo === null) {
            $motivo = 'El tipo de relación no existe.';
        } elseif ($esNueva && $tipo['estado'] !== 'Activo') {
            $motivo = 'El tipo de relación está de baja y no admite relaciones nuevas.';
        } elseif (!isset(self::FORMAS[$tipo['forma']])) {
            $motivo = 'La forma del tipo de relación no es ninguna de las tres admitidas.';
        }

        // La forma: solo cuentan las líneas activas. Las eliminadas son productos que esta
        // versión quita.
        if ($motivo === '') {
            $entradas = 0;
            $salidas = 0;
            $sentidoDesconocido = false;
            foreach ($lineas as $linea) {
                if ($linea['estado'] === 'Activo') {
                    if ($linea['sentido'] === 'entrada') {
                        $entradas++;
                    } elseif ($linea['sentido'] === 'salida') {
                        $salidas++;
                    } else {
                        $sentidoDesconocido = true;
                    }
                }
            }

            $forma = self::FORMAS[$tipo['forma']];
            if ($sentidoDesconocido) {
                $motivo = 'Cada producto tiene que decir si entra o sale.';
            } elseif ($entradas === 0) {
                $motivo = 'La relación necesita al menos una entrada.';
            } elseif ($salidas === 0) {
                $motivo = 'La relación necesita al menos una salida.';
            } elseif ($forma['unaSolaEntrada'] && $entradas > 1) {
                $motivo = 'Este tipo de relación admite una sola entrada.';
            } elseif ($forma['unaSolaSalida'] && $salidas > 1) {
                $motivo = 'Este tipo de relación admite una sola salida.';
            }
        }

        // Productos repetidos: aquí cuentan también las líneas eliminadas.
        if ($motivo === '') {
            $articulosVistos = array();
            foreach ($lineas as $linea) {
                if (in_array($linea['idArticulo'], $articulosVistos)) {
                    $motivo = 'Hay un producto repetido en la relación.';
                }
                $articulosVistos[] = $linea['idArticulo'];
            }
        }

        // Cantidades de las líneas activas
        if ($motivo === '') {
            foreach ($lineas as $linea) {
                if ($linea['estado'] === 'Activo' && $motivo === '') {
                    $motivo = $this->motivoPorCantidad($linea, $tipo['llevaCantidad']);
                }
            }
        }

        if ($motivo === '') {
            $respuesta = array('datos' => 'admitida');
        } else {
            $respuesta = array('error' => $this->montarAdvertencia('warning', $motivo));
        }

        return $respuesta;
    }

    private function motivoPorCantidad($linea, $llevaCantidad)
    {
        // @ Objetivo
        // Comprobar la cantidad y la unidad de una línea.
        // @ Devolvemos
        //      (string) el motivo del rechazo, o '' si la línea es correcta.
        $motivo = '';
        $cantidad = $linea['cantidad'];
        $unidad = $linea['unidad'];

        if (!$llevaCantidad) {
            if ($cantidad !== null || $unidad !== null) {
                $motivo = 'Este tipo de relación no lleva cantidad ni unidad.';
            }
        } elseif ($cantidad === null) {
            $motivo = 'Falta la cantidad de un producto.';
        } elseif ($unidad !== 'kg' && $unidad !== 'ud') {
            $motivo = 'Falta la unidad de una cantidad, o no es kg ni ud.';
        } elseif (!is_numeric($cantidad) || $cantidad <= 0) {
            $motivo = 'La cantidad tiene que ser mayor que cero.';
        } elseif ($unidad === 'ud' && abs($cantidad - round($cantidad)) > 0.0000001) {
            $motivo = 'Las unidades tienen que ser enteras.';
        } elseif ($unidad === 'kg' && abs($cantidad * 1000 - round($cantidad * 1000)) > 0.0000001) {
            $motivo = 'El peso admite tres decimales como máximo.';
        }

        return $motivo;
    }
}
