<?php

// @ Objetivo
// Convertir la composición del resultado a XML y de vuelta, y calcular el resumen de
// contenido que viaja dentro del fichero. La misma conversión sirve para emitir y
// para admitir: el resumen se recalcula igual en los dos sentidos porque se compone
// desde los mismos campos, en el mismo orden, nunca desde el marcado XML en bruto.
class ClaseComprobacionIntercambioXML
{
    public static function arrayToSimpleXML($composicion)
    {
        // @ Objetivo
        // Serializar la composición: contexto de cálculo como origen y criterio, filas,
        // y el resumen de contenido calculado sobre ambos.
        // @ Parametros
        //      $composicion -> array ['filas' => [...], 'contexto' => [...]].
        // @ Devolvemos
        //      SimpleXMLElement.
        $contexto = $composicion['contexto'];
        $filas = $composicion['filas'];

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><ComprobacionIntercambio></ComprobacionIntercambio>'
        );
        $xml->addAttribute('idOrigen', $contexto['ano'] . '-' . $contexto['idTienda']);

        $meta = $xml->addChild('Meta');
        $meta->addChild('Version', '1.0');
        $meta->addChild('Tipo', 'COMPROBACION_EXISTENCIAS');
        $meta->addChild('FechaExportacion', $contexto['momento']);

        self::anadirOrigen($xml->addChild('Origen'), $contexto);
        self::anadirCriterio($xml->addChild('Criterio'), $contexto);
        self::anadirFilas($xml->addChild('Filas'), $filas);

        $resumen = $xml->addChild('Resumen');
        $resumen->addChild('SHA256', self::calcularResumen($contexto, $filas));

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

        $contexto = array(
            'ano' => (string) $origen->Ejercicio,
            'idTienda' => (int) $origen->IdTienda,
            'momento' => (string) $origen->Momento,
            'autor' => (int) $origen->Autor,
            'proveedorCierre' => (int) $origen->Traspaso->IdProveedor,
            'ventanaDias' => (int) $criterio->VentanaDias,
            'umbralSobrestock' => (float) $criterio->UmbralSobrestock,
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
                'marcado' => (string) $fila->Marcado === 'true',
                'tipoIncidencia' => isset($fila->TipoIncidencia) ? (string) $fila->TipoIncidencia : null,
                'condicionesConocidas' => $condiciones,
            );
        }

        return array(
            'filas' => $filas,
            'contexto' => $contexto,
            'resumenDeclarado' => (string) $xml->Resumen->SHA256,
            'resumenRecalculado' => self::calcularResumen($contexto, $filas),
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
        $nodo->addChild('UmbralSobrestock', $contexto['umbralSobrestock']);
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
            $nodoFila->addChild('SaldoAlCorte', $fila['saldoAlCorte']);
            $nodoFila->addChild('MinimoAlcanzado', $fila['minimoAlcanzado']);
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

    private static function calcularResumen($contexto, $filas)
    {
        // @ Objetivo
        // Componer una cadena canónica desde los campos escalares del origen, el
        // criterio y las filas —en orden fijo, nunca desde el XML serializado— y
        // encadenar su SHA-256. Emitir y admitir parten de la misma cadena porque los
        // dos la construyen desde valores ya tipados, no desde el marcado.
        // @ Devolvemos
        //      string, 64 caracteres hexadecimales.
        $partes = array(
            $contexto['ano'],
            $contexto['idTienda'],
            $contexto['proveedorCierre'],
            $contexto['ventanaDias'],
            $contexto['umbralSobrestock'],
            $contexto['modoTrayectoria'],
            implode(',', $contexto['filtro']),
        );

        foreach ($filas as $fila) {
            $partes[] = implode('|', array(
                $fila['idArticulo'],
                $fila['saldoAlCorte'],
                $fila['minimoAlcanzado'],
                $fila['marcado'] ? '1' : '0',
                (string) $fila['tipoIncidencia'],
                implode(',', $fila['condicionesConocidas']),
            ));
        }

        return hash('sha256', implode(';', $partes));
    }
}
