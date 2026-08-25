<?php

include_once $URLCom . '/clases/ClaseIOXML.php';
include_once $RutaServidor . $HostNombre . '/modulos/mod_reorganizacion/clases/ClaseComprobacionIntercambioXML.php';

// @ Objetivo
// Componer una sola vez el resultado de cada ejecución —la del ejercicio vigente y
// la del anterior—, adjuntarle su contexto de cálculo copiado por valor, y producir
// a partir de esa única composición la vista, el fichero de intercambio con el otro
// ejercicio y, en el anterior, el informe final. Ninguna salida repite la consulta
// ni recompone nada por su cuenta. No lee la base: recibe lo ya compuesto.
class ClaseComprobacionEmision
{
    private $rutaXSD = '/modulos/mod_reorganizacion/comprobacion_intercambio_v1.xsd';

    public function componer($estadoProducto, $contextoOperacion, $modoTrayectoria, $filtro = null, $contextoVigente = null)
    {
        // @ Objetivo
        // Resolver el filtro sobre el conjunto, si lo hay, y adjuntar el contexto de
        // cálculo copiado por valor. La vista y el fichero de intercambio consumen
        // esta misma estructura y no vuelven a leer nada. En el anterior, esta misma
        // composición alimenta también el informe final: por eso lleva el contexto
        // del vigente cuando se aporta, en vez de que el informe vaya a buscarlo aparte.
        // @ Parametros
        //      $estadoProducto -> array, la salida de ClaseComprobacionExtraccion::extraer()
        //          en el vigente, o de ClaseComprobacionClasificacion::clasificar() en el anterior.
        //      $contextoOperacion -> array, la salida de ClaseComprobacionContexto::abrir().
        //      $modoTrayectoria -> bool, si el saldo de partida se truncó a cero al extraer.
        //      $filtro -> array de int (idArticulo), opcional. Si se aporta, resuelve el
        //          subconjunto antes de componer.
        //      $contextoVigente -> array, opcional. Solo en el anterior: el contexto que
        //          trajo el fichero admitido, para que el informe final lo arrastre junto
        //          al propio.
        // @ Devolvemos
        //      array ['filas' => [...], 'contexto' => [...], 'contextoVigente' => [...]|null].
        $filas = ($filtro !== null) ? $this->filtrar($estadoProducto, $filtro) : $estadoProducto;

        return array(
            'filas' => $filas,
            'contexto' => $this->contextoDeCalculo($contextoOperacion, $modoTrayectoria, $filtro),
            'contextoVigente' => $contextoVigente,
        );
    }

    public function emitir($composicion, $rutaDestino)
    {
        // @ Objetivo
        // Guardar la composición como fichero de intercambio, validado contra su
        // esquema. Si la validación falla no se produce el fichero: ClaseIOXML lanza
        // la excepción antes de escribir.
        // @ Parametros
        //      $composicion -> array, la salida de componer().
        //      $rutaDestino -> string, ruta del servidor donde guardar el XML.
        // @ Devolvemos
        //      bool true si se guardó.
        global $RutaServidor, $HostNombre;

        $xml = ClaseComprobacionIntercambioXML::arrayToSimpleXML($composicion);
        $io = new ClaseIOXML($rutaDestino, $RutaServidor . $HostNombre . $this->rutaXSD);
        return $io->guardar($xml);
    }

    public function emitirInforme($composicion, $rutaDestino)
    {
        // @ Objetivo
        // Escribir el informe final del ejercicio anterior: texto separado por punto y
        // coma con BOM UTF-8, con los dos contextos de cálculo en filas de cabecera
        // —el de esta emisión y el que trajo el fichero del vigente— y una fila por
        // producto con su estado, su marcado, sus condiciones y los dos números que
        // lo sostienen. Sin ramas: no valida ni transforma, solo escribe lo que ya
        // trae la composición.
        // @ Parametros
        //      $composicion -> array, la salida de componer() con $contextoVigente aportado.
        //      $rutaDestino -> string, ruta del servidor donde guardar el informe.
        // @ Devolvemos
        //      bool true si se guardó.
        $contenido = $this->bloqueContexto('Anterior', $composicion['contexto'])
            . "\n"
            . $this->bloqueContexto('Vigente', $composicion['contextoVigente'])
            . "\n"
            . $this->bloqueFilas($composicion['filas']);

        return file_put_contents($rutaDestino, "\xEF\xBB\xBF" . $contenido) !== false;
    }

    private function bloqueContexto($etiqueta, $contexto)
    {
        $lineas = array(
            'Contexto;' . $etiqueta,
            'Ejercicio;' . $contexto['ano'],
            'Tienda;' . $contexto['idTienda'],
            'Momento;' . $contexto['momento'],
            'Autor;' . $contexto['autor'],
            'VentanaDias;' . $contexto['ventanaDias'],
            'UmbralSobrestock;' . $contexto['umbralSobrestock'],
            'ModoTrayectoria;' . $contexto['modoTrayectoria'],
        );
        return implode("\n", $lineas) . "\n";
    }

    private function bloqueFilas($filas)
    {
        $lineas = array('IdArticulo;Estado;Marcado;Condiciones;ExistenciaExigida;StockJustificado');
        foreach ($filas as $fila) {
            $lineas[] = implode(';', array(
                $fila['idArticulo'],
                $fila['estado'],
                $fila['marcado'] ? '1' : '0',
                implode(',', $fila['condicionesConocidas']),
                $fila['existenciaExigida'],
                ($fila['stockJustificado'] !== null) ? $fila['stockJustificado'] : '',
            ));
        }
        return implode("\n", $lineas) . "\n";
    }

    private function filtrar($estadoProducto, $filtro)
    {
        $idsFiltro = array_flip($filtro);
        $resultado = array();
        foreach ($estadoProducto as $fila) {
            if (isset($idsFiltro[$fila['idArticulo']])) {
                $resultado[] = $fila;
            }
        }
        return $resultado;
    }

    private function contextoDeCalculo($contextoOperacion, $modoTrayectoria, $filtro)
    {
        // @ Objetivo
        // Componer el contexto de cálculo: copia por valor de lo que ya
        // resolvió el contexto de operación, el modo con que se ejecutó, el filtro
        // resuelto y el momento y autor de esta ejecución.
        // @ Devolvemos
        //      array con los campos del contexto de cálculo.
        return array(
            'ano' => $contextoOperacion['ano'],
            'idTienda' => $contextoOperacion['idTienda'],
            'momento' => date('c'),
            'autor' => isset($_SESSION['usuarioTpv']['id']) ? (int) $_SESSION['usuarioTpv']['id'] : null,
            'proveedorCierre' => $contextoOperacion['proveedorCierre'],
            'ventanaDias' => $contextoOperacion['ventanaDias'],
            'umbralSobrestock' => $contextoOperacion['umbralSobrestock'],
            'modoTrayectoria' => $modoTrayectoria ? 'estricto' : 'normal',
            'filtro' => ($filtro !== null) ? $filtro : array(),
        );
    }
}
