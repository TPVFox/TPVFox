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
        //      ['datos'] = 'admitida', o ['error'] con el motivo del rechazo. El motivo dice qué
        //      falla, dónde —la línea por su lado y su número entre las activas de ese lado, que es
        //      como la numera la pantalla— y cómo se resuelve.
        $motivo = '';

        // El tipo
        if ($tipo === null) {
            $motivo = 'El tipo de relación no existe: elige uno de la lista.';
        } elseif ($esNueva && $tipo['estado'] !== 'Activo') {
            $motivo = 'El tipo de relación está de baja y no admite relaciones nuevas: elige otro tipo.';
        } elseif (!isset(self::FORMAS[$tipo['forma']])) {
            $motivo = 'La forma del tipo de relación no es ninguna de las tres admitidas: revisa el tipo en Sistema, Tablas principales.';
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
                $motivo = 'Cada producto tiene que decir si entra o sale: quita el que no lo dice y añádelo en su lado.';
            } elseif ($entradas === 0) {
                $motivo = 'La relación necesita al menos una entrada: añade el producto que entra.';
            } elseif ($salidas === 0) {
                $motivo = 'La relación necesita al menos una salida: añade el producto que sale.';
            } elseif ($forma['unaSolaEntrada'] && $entradas > 1) {
                $motivo = 'Este tipo de relación admite una sola entrada y hay ' . $entradas . ': quita las que sobran.';
            } elseif ($forma['unaSolaSalida'] && $salidas > 1) {
                $motivo = 'Este tipo de relación admite una sola salida y hay ' . $salidas . ': quita las que sobran.';
            }
        }

        // Productos repetidos: aquí cuentan también las líneas eliminadas.
        if ($motivo === '') {
            $articulosVistos = array();
            foreach ($lineas as $linea) {
                if ($motivo === '' && in_array($linea['idArticulo'], $articulosVistos)) {
                    $motivo = 'Hay un producto repetido en la relación (artículo ' . $linea['idArticulo'] . '): quita una de sus líneas.';
                }
                $articulosVistos[] = $linea['idArticulo'];
            }
        }

        // Cantidades de las líneas activas, cada una nombrada por su lado y su número
        if ($motivo === '') {
            $numeroEnSuLado = array('entrada' => 0, 'salida' => 0);
            foreach ($lineas as $linea) {
                if ($linea['estado'] === 'Activo' && $motivo === '') {
                    $numeroEnSuLado[$linea['sentido']]++;
                    $nombre = 'la ' . $linea['sentido'] . ' ' . $numeroEnSuLado[$linea['sentido']];
                    $motivo = $this->motivoPorCantidad($linea, $tipo['llevaCantidad'], $nombre);
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

    private function motivoPorCantidad($linea, $llevaCantidad, $nombre)
    {
        // @ Objetivo
        // Comprobar la cantidad y la unidad de una línea.
        // @ Parametros
        //      $nombre -> (string) cómo se nombra la línea en el aviso: «la salida 2».
        // @ Devolvemos
        //      (string) el motivo del rechazo, o '' si la línea es correcta.
        $motivo = '';
        $cantidad = $linea['cantidad'];
        $unidad = $linea['unidad'];

        if (!$llevaCantidad) {
            if ($cantidad !== null || $unidad !== null) {
                $motivo = 'Este tipo de relación no lleva cantidad ni unidad, y ' . $nombre . ' la tiene: bórrala de su línea.';
            }
        } elseif ($cantidad === null) {
            $motivo = 'Falta la cantidad de ' . $nombre . ': escríbela en su línea.';
        } elseif ($unidad !== 'kg' && $unidad !== 'ud') {
            $motivo = 'Falta la unidad de ' . $nombre . ', o no es kg ni ud: elige kg o ud en su línea.';
        } elseif (!is_numeric($cantidad) || $cantidad <= 0) {
            $motivo = 'La cantidad de ' . $nombre . ' tiene que ser mayor que cero: corrígela en su línea.';
        } elseif ($unidad === 'ud' && abs($cantidad - round($cantidad)) > 0.0000001) {
            $motivo = 'Las unidades tienen que ser enteras, y ' . $nombre . ' no lo es: corrígela en su línea.';
        } elseif ($unidad === 'kg' && abs($cantidad * 1000 - round($cantidad * 1000)) > 0.0000001) {
            $motivo = 'El peso admite tres decimales como máximo, y ' . $nombre . ' tiene más: redondéalo al gramo.';
        }

        return $motivo;
    }
}
