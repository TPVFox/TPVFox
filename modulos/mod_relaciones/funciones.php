<?php

// @ Objetivo
// El trabajo del listado de productos relacionados: componer sus filas, una por relación
// que rige, y aplicarles la búsqueda, el filtro, el orden y la página. Nada
// de aquí consulta la base: recibe lo que ya leyó la clase Relaciones.

function filasDelListado($vigentes)
{
    // @ Objetivo
    // Una fila por relación que rige.
    // @ Parametros
    //      $vigentes -> (array) las relaciones que rigen, como las devuelve Relaciones::vigentes().
    // @ Devolvemos
    //      (array) filas con numRelacion, relacion, tipo, version, fecha, numProductos y
    //      productos, que son los nombres de sus productos y solo se usan para buscar.
    $filas = array();
    foreach ($vigentes as $relacion) {
        // Si la relación no tiene nombre, se llama como el producto que está solo en su lado.
        $ladoQueDaNombre = 'entrada';
        if ($relacion['forma'] === 'N:1') {
            $ladoQueDaNombre = 'salida';
        }
        $nombre = $relacion['nombre'];
        $productos = array();
        foreach ($relacion['lineas'] as $linea) {
            $productos[] = $linea['articulo'];
            if (($nombre === null || $nombre === '') && $linea['sentido'] === $ladoQueDaNombre) {
                $nombre = $linea['articulo'];
            }
        }
        $filas[] = array(
            'numRelacion' => (int) $relacion['numRelacion'],
            'relacion' => (string) $nombre,
            'tipo' => $relacion['tipo'],
            'version' => $relacion['version'],
            'fecha' => $relacion['fechaCreacion'],
            'numProductos' => count($productos),
            'productos' => $productos,
        );
    }

    return $filas;
}

function parametrosDelListado($recibidos, $nombresDeTipos)
{
    // @ Objetivo
    // Dejar los parámetros de la dirección en valores que el listado ofrece. Lo que no
    // encaja se trata como si no hubiera llegado. Se hace antes de que los lea el plugin de
    // paginación, que los pinta en sus enlaces tal como llegan.
    // @ Parametros
    //      $recibidos -> (array) los parámetros de la dirección: pagina, buscar, filtro y orden.
    //      $nombresDeTipos -> (array) los nombres de tipo por los que se puede filtrar.
    // @ Devolvemos
    //      (array) pagina (int, 1 o más), buscar, filtro y orden (string, '' si no hay).
    $parametros = array('pagina' => 1, 'buscar' => '', 'filtro' => '', 'orden' => '');

    if (isset($recibidos['pagina']) && is_string($recibidos['pagina']) && ctype_digit($recibidos['pagina'])) {
        if ($recibidos['pagina'] > 0 && strlen($recibidos['pagina']) < 7) {
            $parametros['pagina'] = (int) $recibidos['pagina'];
        }
    }
    if (isset($recibidos['buscar']) && is_string($recibidos['buscar'])) {
        // Solo letras, números, espacios y guiones.
        $limpio = preg_replace('/[^\p{L}\p{N} \-]/u', '', $recibidos['buscar']);
        if ($limpio !== null) {
            $parametros['buscar'] = trim(mb_substr($limpio, 0, 60));
        }
    }
    if (isset($recibidos['filtro']) && is_string($recibidos['filtro'])) {
        if (in_array($recibidos['filtro'], $nombresDeTipos, true)) {
            $parametros['filtro'] = $recibidos['filtro'];
        }
    }
    if (isset($recibidos['orden']) && is_string($recibidos['orden'])) {
        if (in_array($recibidos['orden'], array('numRelacion:ASC', 'numRelacion:DESC', 'fecha:ASC', 'fecha:DESC'), true)) {
            $parametros['orden'] = $recibidos['orden'];
        }
    }

    return $parametros;
}

function filtrarFilasDelListado($filas, $buscar, $tipo)
{
    // @ Objetivo
    // Quedarse con las filas que casan con lo buscado y con el tipo elegido.
    // @ Parametros
    //      $filas -> (array) las de filasDelListado().
    //      $buscar -> (string) palabras; cada una tiene que estar en el nombre de la relación
    //                 o en el de alguno de sus productos. '' no busca.
    //      $tipo -> (string) nombre del tipo de relación. '' no filtra.
    // @ Devolvemos
    //      (array) las filas que quedan, en el mismo orden.
    $palabras = array();
    foreach (explode(' ', $buscar) as $palabra) {
        if ($palabra !== '') {
            $palabras[] = $palabra;
        }
    }

    $quedan = array();
    foreach ($filas as $fila) {
        $casa = true;
        if ($tipo !== '' && $fila['tipo'] !== $tipo) {
            $casa = false;
        }
        // Se busca en un solo texto: el nombre de la relación y los de sus productos.
        $dondeBuscar = $fila['relacion'] . ' ' . implode(' ', $fila['productos']);
        foreach ($palabras as $palabra) {
            if (mb_stripos($dondeBuscar, $palabra) === false) {
                $casa = false;
            }
        }
        if ($casa) {
            $quedan[] = $fila;
        }
    }

    return $quedan;
}

function ordenarFilasDelListado($filas, $orden)
{
    // @ Objetivo
    // Ordenar las filas. Sin orden pedido, por número de relación.
    // @ Parametros
    //      $filas -> (array) las de filasDelListado().
    //      $orden -> (string) 'numRelacion:ASC', 'numRelacion:DESC', 'fecha:ASC', 'fecha:DESC' o ''.
    // @ Devolvemos
    //      (array) las mismas filas, ordenadas.
    $campo = 'numRelacion';
    $sentido = 'ASC';
    if ($orden !== '') {
        $partes = explode(':', $orden);
        $campo = $partes[0];
        $sentido = $partes[1];
    }

    // Se ordena por una clave de texto. El número va con ceros delante para que 10 quede
    // después de 9, y desempata cuando dos relaciones tienen la misma fecha.
    $claves = array();
    foreach ($filas as $posicion => $fila) {
        $numero = str_pad((string) $fila['numRelacion'], 10, '0', STR_PAD_LEFT);
        if ($campo === 'fecha') {
            $claves[$posicion] = $fila['fecha'] . '|' . $numero;
        } else {
            $claves[$posicion] = $numero;
        }
    }
    if ($sentido === 'DESC') {
        arsort($claves);
    } else {
        asort($claves);
    }

    $ordenadas = array();
    foreach ($claves as $posicion => $clave) {
        $ordenadas[] = $filas[$posicion];
    }

    return $ordenadas;
}

function ultimaPaginaDelListado($cuantasFilas, $filasPorPagina)
{
    // @ Objetivo
    // La última página que existe para ese número de filas. Sin filas, la 1.
    // @ Devolvemos
    //      (int) número de página, 1 o más.
    $ultima = (int) ceil($cuantasFilas / $filasPorPagina);
    if ($ultima < 1) {
        $ultima = 1;
    }

    return $ultima;
}

function paginaDeFilasDelListado($filas, $pagina, $filasPorPagina)
{
    // @ Objetivo
    // Las filas de una página.
    // @ Devolvemos
    //      (array) como mucho $filasPorPagina filas.
    return array_slice($filas, ($pagina - 1) * $filasPorPagina, $filasPorPagina);
}
