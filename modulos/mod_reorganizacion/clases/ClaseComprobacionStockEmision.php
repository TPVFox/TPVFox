<?php

include_once $URLCom . '/clases/ClaseIOXML.php';
include_once $RutaServidor . $HostNombre . '/modulos/mod_reorganizacion/clases/ClaseComprobacionStockIntercambioXML.php';
include_once $RutaServidor . $HostNombre . '/modulos/mod_reorganizacion/clases/ClaseComprobacionStockCantidad.php';

// @ Objetivo
// Componer una sola vez el resultado de cada ejecución —la del ejercicio vigente y
// la del anterior—, adjuntarle su contexto de cálculo copiado por valor, y producir
// a partir de esa única composición la vista, el fichero de intercambio con el otro
// ejercicio y, en el anterior, el informe final. Ninguna salida repite la consulta
// ni recompone nada por su cuenta. No lee la base: recibe lo ya compuesto.
class ClaseComprobacionStockEmision
{
    private $rutaXSD = '/modulos/mod_reorganizacion/comprobacion_stock_intercambio_v1.xsd';

    // Lo que ocupa una fila del fichero de intercambio en el peor caso: la que lleva
    // incidencia y condiciones, con identificadores de seis cifras. Medido sobre la
    // propia serialización —una fila sin nada ocupa menos de la mitad—, y redondeado
    // hacia arriba a propósito: una estimación que se quede corta avisaría de que cabe
    // algo que después no cabe, que es peor que no avisar.
    const BYTES_POR_FILA = 380;

    public function avisoDeVolumen($numeroDeFilas, $limiteDeSubida, $limiteDePeticion)
    {
        // @ Objetivo
        // Avisar, cuando se compone el resultado del ejercicio vigente, de que un
        // fichero con todos estos productos no cabría por la subida del otro extremo,
        // y decir en partes de cuántos hay que emitirlo.
        //
        // Se comprueba aquí y no al emitir porque al emitir ya no hay dónde decirlo:
        // la respuesta de la descarga es el fichero. Aquí, en cambio, el operador
        // todavía no ha elegido nada y el filtro sigue siendo el remedio disponible.
        //
        // Se mide contra el conjunto completo, que es el mayor que se puede pedir: si
        // el completo cabe, ninguna selección deja de caber y no hay nada que decir.
        // @ Parametros
        //      $numeroDeFilas -> int, los productos que la composición contiene.
        //      $limiteDeSubida -> string, lo que el servidor admite por fichero.
        //      $limiteDePeticion -> string, lo que admite por petición entera. El que
        //          manda es el menor de los dos: un fichero que cabe por sí mismo no
        //          entra si la petición que lo lleva no cabe.
        // @ Devolvemos
        //      string con el aviso, o null si el conjunto completo cabe.
        $limite = min($this->bytesDelLimite($limiteDeSubida), $this->bytesDelLimite($limiteDePeticion));
        if ($limite <= 0) {
            return null;
        }

        $estimado = $numeroDeFilas * self::BYTES_POR_FILA;
        if ($estimado <= $limite) {
            return null;
        }

        $porParte = (int) floor($limite / self::BYTES_POR_FILA);

        return 'Un fichero con los ' . number_format($numeroDeFilas, 0, ',', '.')
            . ' productos ocuparía en torno a ' . $this->enMegas($estimado)
            . ', y este servidor admite ' . $this->enMegas($limite)
            . '. Seleccione productos para emitirlo por partes de hasta '
            . number_format($porParte, 0, ',', '.') . '.';
    }

    public function bytesDelLimite($declarado)
    {
        // @ Objetivo
        // Traducir a bytes un límite tal como lo declara el servidor, que lo abrevia
        // con un sufijo de unidad: «2M» son dos megas, «1024M» mil veinticuatro.
        // @ Parametros
        //      $declarado -> string.
        // @ Devolvemos
        //      int, los bytes, o 0 si no se declara ninguno.
        $declarado = trim((string) $declarado);
        if ($declarado === '') {
            return 0;
        }

        $bytes = (int) $declarado;
        switch (strtolower(substr($declarado, -1))) {
            case 'g':
                $bytes *= 1024;
                // Sin break: cada unidad se apoya en la anterior.
            case 'm':
                $bytes *= 1024;
            case 'k':
                $bytes *= 1024;
        }

        return $bytes;
    }

    private function enMegas($bytes)
    {
        return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
    }

    public function componer($estadoProducto, $contextoOperacion, $modoTrayectoria, $filtro = null, $contextoVigente = null)
    {
        // @ Objetivo
        // Resolver el filtro sobre el conjunto, si lo hay, y adjuntar el contexto de
        // cálculo copiado por valor. La vista y el fichero de intercambio consumen
        // esta misma estructura y no vuelven a leer nada. En el anterior, esta misma
        // composición alimenta también el informe final: por eso lleva el contexto
        // del vigente cuando se aporta, en vez de que el informe vaya a buscarlo aparte.
        // @ Parametros
        //      $estadoProducto -> array, la salida de ClaseComprobacionStockExtraccion::extraer()
        //          en el vigente, o de ClaseComprobacionStockClasificacion::clasificar() en el anterior.
        //      $contextoOperacion -> array, la salida de ClaseComprobacionStockContexto::abrir().
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

    public function conjuntoPedido($crudo, $declarados)
    {
        // @ Objetivo
        // Reconstruir el conjunto que se pidió emitir desde el campo único en que
        // viaja, y comprobar que llegó entero: quien lo envía declara cuántos
        // identificadores manda, y aquí se cuentan los que han llegado.
        //
        // El contraste es la garantía, no el camino: un conjunto que llega a medias
        // compone, valida y se resume exactamente igual de bien que el completo, de
        // modo que sin comparar el recuento nada distinguiría después «llegó todo»
        // de «llegó lo que cupo». Comparándolo, cualquier recorte por el camino
        // —venga de donde venga— detiene la emisión en vez de encogerla.
        // @ Parametros
        //      $crudo -> string|null, los identificadores separados por coma.
        //      $declarados -> int|string|null, cuántos dice haber enviado quien pide.
        // @ Devolvemos
        //      array ['ok' => false, 'motivo' => ..] o ['ok' => true, 'ids' => [...]].
        if ($declarados === null || $declarados === '') {
            return array('ok' => false, 'motivo' => 'La selección de productos no llegó al servidor');
        }

        $declarados = (int) $declarados;
        if ($declarados === 0) {
            return array('ok' => false, 'motivo' => 'No hay ningún producto seleccionado: no se emite nada');
        }

        $ids = array();
        foreach (explode(',', (string) $crudo) as $trozo) {
            $trozo = trim($trozo);
            if ($trozo !== '') {
                $ids[] = (int) $trozo;
            }
        }

        if (count($ids) !== $declarados) {
            return array('ok' => false, 'motivo' => 'La selección no llegó entera: se enviaron '
                . $declarados . ' productos y llegaron ' . count($ids) . '. No se emite nada');
        }

        return array('ok' => true, 'ids' => $ids);
    }

    public function filtroDeclarable($estadoProducto, $pedidos)
    {
        // @ Objetivo
        // Decidir si lo pedido delimita un subconjunto de lo compuesto. Solo cuando
        // lo pedido y lo compuesto son el mismo conjunto no hay subconjunto que
        // declarar: entonces el fichero sale sin bloque de filtro, y esa ausencia es
        // la declaración de que el conjunto emitido es completo.
        //
        // Cualquier otra diferencia se declara tal como se pidió, falte alguno de los
        // compuestos o sobre alguno que no está entre ellos. Las dos son información
        // sobre lo que se buscaba, y el fichero es el único sitio donde puede quedar.
        // La comparación se hace contra lo que esta ejecución acaba de componer, que
        // es el único conjunto del que aquí se sabe algo.
        // @ Parametros
        //      $estadoProducto -> array, el conjunto completo ya compuesto.
        //      $pedidos -> array de int (idArticulo).
        // @ Devolvemos
        //      array de int con lo pedido, o null si no hay subconjunto que declarar.
        $compuestos = array();
        foreach ($estadoProducto as $fila) {
            $compuestos[(int) $fila['idArticulo']] = true;
        }

        $pedidosPorId = array_flip(array_map('intval', $pedidos));

        $mismoConjunto = count($pedidosPorId) === count($compuestos)
            && count(array_diff_key($compuestos, $pedidosPorId)) === 0;

        return $mismoConjunto ? null : $pedidos;
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
        //      bool true si se guardó. No devuelve false nunca: el único desenlace
        //      distinto es la excepción, de modo que quien llama ha de capturarla
        //      para que el motivo llegue a constar en algún sitio.
        global $RutaServidor, $HostNombre;

        $xml = ClaseComprobacionStockIntercambioXML::arrayToSimpleXML($composicion);
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
            'UmbralFraccionado;' . $contexto['umbralFraccionado'],
            'UmbralMagnitud;' . $contexto['umbralMagnitud'],
            'UmbralPorVenta;' . $contexto['umbralPorVenta'],
            'TimingVentanaDias;' . $contexto['timingVentanaDias'],
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
                ClaseComprobacionStockCantidad::comoTexto($fila['existenciaExigida']),
                ($fila['stockJustificado'] !== null) ? ClaseComprobacionStockCantidad::comoTexto($fila['stockJustificado']) : '',
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
            'umbralFraccionado' => $contextoOperacion['umbralFraccionado'],
            'umbralMagnitud' => $contextoOperacion['umbralMagnitud'],
            'umbralPorVenta' => $contextoOperacion['umbralPorVenta'],
            'timingVentanaDias' => $contextoOperacion['timingVentanaDias'],
            'modoTrayectoria' => $modoTrayectoria ? 'estricto' : 'normal',
            'filtro' => ($filtro !== null) ? $filtro : array(),
        );
    }
}
