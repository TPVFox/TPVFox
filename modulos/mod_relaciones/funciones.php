<?php

// @ Objetivo
// El trabajo del listado de productos relacionados que no es consulta: dejar en valores
// admitidos lo que llega en la dirección, antes de usarlo, y atender lo que el listado pide
// por tareas.php.

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

function permisoRelacion($ClasePermisos, $accion, $vista)
{
    // @ Objetivo
    // Saber si el usuario de la sesión tiene el permiso de una acción del módulo, en la vista
    // desde la que se hace. Se pregunta con el módulo y la vista porque quien llama es
    // tareas.php, no esa vista. Se compara con 1: quien no tiene fila de permiso recibe ''.
    // @ Parametros
    //      $ClasePermisos -> (ClasePermisos) los permisos de la sesión.
    //      $accion -> (string) nombre de la acción en acces.xml.
    //      $vista -> (string) vista de acces.xml en la que está declarada.
    // @ Devolvemos
    //      (bool) true si la tiene.
    return $ClasePermisos->getAccion($accion, array('modulo' => 'mod_relaciones', 'vista' => $vista)) == 1;
}

function cambiarEstadoRelaciones($numRelaciones, $estado, $CRelaciones, $ClasePermisos, $Usuario)
{
    // @ Objetivo
    // Cambiar el estado de las relaciones marcadas en el listado. Hoy solo se admite darlas
    // de baja. Cada relación se cambia por separado: la que no se puede devuelve su aviso y no
    // impide las demás.
    // @ Parametros
    //      $numRelaciones -> (array) los números marcados, tal como llegan.
    //      $estado -> (string) el estado al que pasan.
    //      $CRelaciones -> (Relaciones) la clase del módulo.
    //      $ClasePermisos -> (ClasePermisos) los permisos de la sesión.
    //      $Usuario -> (array) el usuario de la sesión.
    // @ Devolvemos
    //      ['datos']['cambiadas'] con los números cambiados y ['avisos'] con uno por cada número
    //      que no se pudo; o ['error'] sin permiso, con un estado que no se admite o sin números.
    $respuesta = array();
    if (!permisoRelacion($ClasePermisos, 'CambiarEstado', 'ListaRelaciones.php')) {
        $respuesta['error'] = array('tipo' => 'warning', 'mensaje' => 'No tienes permiso para esta operación.');
    } elseif ($estado !== 'Baja') {
        $respuesta['error'] = array('tipo' => 'warning', 'mensaje' => 'Ese estado no se admite: las relaciones solo se pueden dar de baja.');
    } elseif (!is_array($numRelaciones) || count($numRelaciones) === 0) {
        $respuesta['error'] = array('tipo' => 'warning', 'mensaje' => 'No tienes items seleccionados.');
    } else {
        $respuesta['datos'] = array('cambiadas' => array());
        $respuesta['avisos'] = array();
        foreach ($numRelaciones as $recibido) {
            $numRelacion = (int) $recibido;
            $baja = $CRelaciones->darDeBaja($numRelacion, $Usuario['id']);
            if (isset($baja['error'])) {
                $respuesta['avisos'][] = array('tipo' => 'warning', 'mensaje' => 'Relación ' . $numRelacion . ': ' . $baja['error']['mensaje']);
            } else {
                $respuesta['datos']['cambiadas'][] = $numRelacion;
            }
        }
    }

    return $respuesta;
}
