<?php

include_once $RutaServidor . $HostNombre . '/modulos/mod_reorganizacion/clases/ClaseComprobacionStockCantidad.php';

// @ Objetivo
// Convertir la composición del resultado a XML y de vuelta, y calcular el resumen de
// contenido que viaja dentro del fichero. La misma conversión sirve para emitir y
// para admitir: el resumen se recalcula igual en los dos sentidos porque se compone
// desde los mismos campos, en el mismo orden, nunca desde el marcado XML en bruto.
class ClaseComprobacionStockIntercambioXML
{
    const VERSION_FORMATO = '1.0';
    const TIPO_FORMATO = 'COMPROBACION_EXISTENCIAS';

    public static function arrayToSimpleXML($composicion)
    {
        // @ Objetivo
        // Serializar la composición: la cabecera del formato, el contexto de cálculo
        // como origen y criterio, las filas, y el resumen de contenido calculado
        // sobre todo ello.
        // @ Parametros
        //      $composicion -> array ['filas' => [...], 'contexto' => [...]].
        // @ Devolvemos
        //      SimpleXMLElement.
        $contexto = $composicion['contexto'];
        $filas = $composicion['filas'];
        $cabecera = self::cabeceraDe($contexto);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><ComprobacionIntercambio></ComprobacionIntercambio>'
        );
        $xml->addAttribute('idOrigen', $cabecera['idOrigen']);

        $meta = $xml->addChild('Meta');
        $meta->addChild('Version', $cabecera['version']);
        $meta->addChild('Tipo', $cabecera['tipo']);
        $meta->addChild('FechaExportacion', $cabecera['fechaExportacion']);

        self::anadirOrigen($xml->addChild('Origen'), $contexto);
        self::anadirCriterio($xml->addChild('Criterio'), $contexto);
        self::anadirFilas($xml->addChild('Filas'), $filas);

        $resumen = $xml->addChild('Resumen');
        $resumen->addChild('SHA256', self::calcularResumen($cabecera, $contexto, $filas));

        return $xml;
    }

    public static function simpleXMLToArray($xml)
    {
        // @ Objetivo
        // Reconstruir la composición desde el XML cargado, y devolver junto a ella el
        // resumen recalculado para que quien admite lo compare con el que viajó.
        // @ Devolvemos
        //      array ['filas' => [...], 'contexto' => [...], 'resumenDeclarado' => string,
        //          'resumenRecalculado' => string].
        $origen = $xml->Origen;
        $criterio = $xml->Criterio;

        // La cabecera se lee del fichero, no se deriva del origen: si se derivara,
        // una edición de la meta o del atributo pasaría inadvertida al recalcular
        // porque el recálculo la habría vuelto a construir bien.
        $cabecera = array(
            'version' => (string) $xml->Meta->Version,
            'tipo' => (string) $xml->Meta->Tipo,
            'fechaExportacion' => (string) $xml->Meta->FechaExportacion,
            'idOrigen' => (string) $xml['idOrigen'],
        );

        $contexto = array(
            'ano' => (string) $origen->Ejercicio,
            'idTienda' => (int) $origen->IdTienda,
            'momento' => (string) $origen->Momento,
            'autor' => (int) $origen->Autor,
            'proveedorCierre' => (int) $origen->Traspaso->IdProveedor,
            'ventanaDias' => (int) $criterio->VentanaDias,
            'umbralFraccionado' => (float) $criterio->UmbralFraccionado,
            'umbralMagnitud' => (float) $criterio->UmbralMagnitud,
            'umbralPorVenta' => (float) $criterio->UmbralPorVenta,
            'timingVentanaDias' => (int) $criterio->TimingVentanaDias,
            'modoTrayectoria' => (string) $criterio->ModoTrayectoria,
            'filtro' => array(),
        );

        if (isset($criterio->Filtro)) {
            foreach ($criterio->Filtro->Articulo as $idArticulo) {
                $contexto['filtro'][] = (int) $idArticulo;
            }
        }

        $filas = array();
        foreach ($xml->Filas->Fila as $fila) {
            $condiciones = array();
            if (isset($fila->CondicionesConocidas)) {
                foreach ($fila->CondicionesConocidas->Condicion as $condicion) {
                    $condiciones[] = (string) $condicion;
                }
            }

            $filas[] = array(
                'idArticulo' => (int) $fila->IdArticulo,
                'saldoAlCorte' => (float) $fila->SaldoAlCorte,
                'minimoAlcanzado' => (float) $fila->MinimoAlcanzado,
                'saldoDeApertura' => (float) $fila->SaldoDeApertura,
                'marcado' => (string) $fila->Marcado === 'true',
                'tipoIncidencia' => isset($fila->TipoIncidencia) ? (string) $fila->TipoIncidencia : null,
                'condicionesConocidas' => $condiciones,
            );
        }

        return array(
            'filas' => $filas,
            'contexto' => $contexto,
            'resumenDeclarado' => (string) $xml->Resumen->SHA256,
            'resumenRecalculado' => self::calcularResumen($cabecera, $contexto, $filas),
        );
    }

    private static function cabeceraDe($contexto)
    {
        // @ Objetivo
        // Los cuatro campos que el fichero declara de sí mismo antes de su contenido:
        // la versión y el tipo del formato, la fecha de emisión y el identificador de
        // origen del elemento raíz.
        // @ Devolvemos
        //      array con los cuatro campos de cabecera.
        return array(
            'version' => self::VERSION_FORMATO,
            'tipo' => self::TIPO_FORMATO,
            'fechaExportacion' => $contexto['momento'],
            'idOrigen' => $contexto['ano'] . '-' . $contexto['idTienda'],
        );
    }

    private static function anadirOrigen($nodo, $contexto)
    {
        $nodo->addChild('Ejercicio', $contexto['ano']);
        $nodo->addChild('IdTienda', $contexto['idTienda']);
        $nodo->addChild('Momento', $contexto['momento']);
        $nodo->addChild('Autor', $contexto['autor']);
        $traspaso = $nodo->addChild('Traspaso');
        $traspaso->addChild('IdProveedor', $contexto['proveedorCierre']);
    }

    private static function anadirCriterio($nodo, $contexto)
    {
        $nodo->addChild('VentanaDias', $contexto['ventanaDias']);
        $nodo->addChild('UmbralFraccionado', $contexto['umbralFraccionado']);
        $nodo->addChild('UmbralMagnitud', $contexto['umbralMagnitud']);
        $nodo->addChild('UmbralPorVenta', $contexto['umbralPorVenta']);
        $nodo->addChild('TimingVentanaDias', $contexto['timingVentanaDias']);
        $nodo->addChild('ModoTrayectoria', $contexto['modoTrayectoria']);

        if (!empty($contexto['filtro'])) {
            $filtro = $nodo->addChild('Filtro');
            foreach ($contexto['filtro'] as $idArticulo) {
                $filtro->addChild('Articulo', $idArticulo);
            }
        }
    }

    private static function anadirFilas($nodo, $filas)
    {
        foreach ($filas as $fila) {
            $nodoFila = $nodo->addChild('Fila');
            $nodoFila->addChild('IdArticulo', $fila['idArticulo']);
            // Como número decimal y nunca en notación científica: el lenguaje pasa a
            // exponente por debajo de una cienmilésima, y el esquema no admite «1.0E-6»
            // aunque una millonésima sí sea una cantidad legítima.
            $nodoFila->addChild('SaldoAlCorte', ClaseComprobacionStockCantidad::comoTexto($fila['saldoAlCorte']));
            $nodoFila->addChild('MinimoAlcanzado', ClaseComprobacionStockCantidad::comoTexto($fila['minimoAlcanzado']));
            $nodoFila->addChild('SaldoDeApertura', ClaseComprobacionStockCantidad::comoTexto($fila['saldoDeApertura']));
            $nodoFila->addChild('Marcado', $fila['marcado'] ? 'true' : 'false');

            if (!empty($fila['tipoIncidencia'])) {
                $nodoFila->addChild('TipoIncidencia', $fila['tipoIncidencia']);
            }

            if (!empty($fila['condicionesConocidas'])) {
                $nodoCondiciones = $nodoFila->addChild('CondicionesConocidas');
                foreach ($fila['condicionesConocidas'] as $condicion) {
                    $nodoCondiciones->addChild('Condicion', $condicion);
                }
            }
        }
    }

    private static function calcularResumen($cabecera, $contexto, $filas)
    {
        // @ Objetivo
        // Componer una cadena canónica desde todos los campos escalares que el
        // fichero declara —cabecera, origen, criterio y filas, en orden fijo y nunca
        // desde el XML serializado— y encadenar su SHA-256. Emitir y admitir parten
        // de la misma cadena porque los dos la construyen desde valores ya tipados,
        // no desde el marcado.
        //
        // Entra todo lo declarado y no una parte: el esquema solo comprueba el tipo
        // de cada campo, así que un campo que el resumen no cubra puede cambiarse por
        // otro valor del mismo tipo sin que nada lo delate. El momento y el autor de
        // la ejecución son el caso claro —una fecha y un número— y son justamente lo
        // que acredita de qué ejecución procede el fichero.
        // @ Devolvemos
        //      string, 64 caracteres hexadecimales.
        $partes = array(
            $cabecera['version'],
            $cabecera['tipo'],
            $cabecera['fechaExportacion'],
            $cabecera['idOrigen'],
            $contexto['ano'],
            $contexto['idTienda'],
            $contexto['momento'],
            (int) $contexto['autor'],
            $contexto['proveedorCierre'],
            $contexto['ventanaDias'],
            $contexto['umbralFraccionado'],
            $contexto['umbralMagnitud'],
            $contexto['umbralPorVenta'],
            $contexto['timingVentanaDias'],
            $contexto['modoTrayectoria'],
            implode(',', $contexto['filtro']),
        );

        foreach ($filas as $fila) {
            $partes[] = implode('|', array(
                $fila['idArticulo'],
                ClaseComprobacionStockCantidad::comoTexto($fila['saldoAlCorte']),
                ClaseComprobacionStockCantidad::comoTexto($fila['minimoAlcanzado']),
                ClaseComprobacionStockCantidad::comoTexto($fila['saldoDeApertura']),
                $fila['marcado'] ? '1' : '0',
                (string) $fila['tipoIncidencia'],
                implode(',', $fila['condicionesConocidas']),
            ));
        }

        return hash('sha256', implode(';', $partes));
    }
}
