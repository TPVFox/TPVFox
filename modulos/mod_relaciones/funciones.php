<?php

// @ Objetivo
// El trabajo del listado de productos relacionados que no es consulta: dejar en valores
// admitidos lo que llega en la dirección, antes de usarlo.

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
