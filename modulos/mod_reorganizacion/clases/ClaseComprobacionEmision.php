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

    public function componer($estadoProducto, $contextoOperacion, $modoTrayectoria, $filtro = null)
    {
        // @ Objetivo
        // Resolver el filtro sobre el conjunto, si lo hay, y adjuntar el contexto de
        // cálculo copiado por valor. La vista y el fichero de intercambio consumen
        // esta misma estructura y no vuelven a leer nada.
        // @ Parametros
        //      $estadoProducto -> array, la salida de ClaseComprobacionExtraccion::extraer().
        //      $contextoOperacion -> array, la salida de ClaseComprobacionContexto::abrir().
        //      $modoTrayectoria -> bool, si el saldo de partida se truncó a cero al extraer.
        //      $filtro -> array de int (idArticulo), opcional. Si se aporta, resuelve el
        //          subconjunto antes de componer.
        // @ Devolvemos
        //      array ['filas' => [...], 'contexto' => [...]].
        $filas = ($filtro !== null) ? $this->filtrar($estadoProducto, $filtro) : $estadoProducto;

        return array(
            'filas' => $filas,
            'contexto' => $this->contextoDeCalculo($contextoOperacion, $modoTrayectoria, $filtro),
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
