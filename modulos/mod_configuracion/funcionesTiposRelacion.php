<?php

include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesTipos.php';

// @ Objetivo
// El bloque «Tipos de relación» de las tablas principales: lo que muestra, su formulario y
// sus seis operaciones. Cada operación tiene su permiso, y todo lo que lee o escribe lo
// hace la clase RelacionesTipos.

function datosBloqueTiposRelacion()
{
    // @ Objetivo
    // Lo que necesita la vista para pintar el bloque.
    // @ Devolvemos
    //      (array) 'aviso', que es '' o el motivo por el que no se puede mostrar la tabla,
    //      y 'tipos', con las filas de todos los tipos, vigentes y de baja.
    $bloque = array('aviso' => '', 'tipos' => array());

    $CTipos = new RelacionesTipos();
    $estructura = $CTipos->estadoEstructura();
    if (isset($estructura['error'])) {
        $bloque['aviso'] = $estructura['error']['mensaje'];
    } elseif ($estructura['datos'] !== 'instalada') {
        $bloque['aviso'] = 'Las tablas de relaciones entre productos no están instaladas, o no son de la versión esperada.';
    } else {
        $tipos = $CTipos->listarTipos(false);
        if (isset($tipos['error'])) {
            $bloque['aviso'] = $tipos['error']['mensaje'];
        } else {
            $bloque['tipos'] = $tipos['datos'];
        }
    }

    return $bloque;
}

function nombreDeLaForma($forma)
{
    // @ Objetivo
    // El texto con que se presenta cada forma.
    $nombres = array('1:N' => 'De uno a varios', 'N:1' => 'De varios a uno', '1:1' => 'De uno a uno');
    $nombre = $forma;
    if (isset($nombres[$forma])) {
        $nombre = $nombres[$forma];
    }

    return $nombre;
}

function permisoTipoRelacion($ClasePermisos, $accion)
{
    // @ Objetivo
    // Saber si quien pide la operación tiene su permiso. Se pregunta con módulo y vista
    // porque también se llama desde tareas.php. Se compara con 1: sin fila de permiso llega ''.
    // @ Devolvemos
    //      (bool)
    $permiso = $ClasePermisos->getAccion($accion, array('modulo' => 'mod_configuracion', 'vista' => 'tablasPrincipales.php'));

    return $permiso == 1;
}

function tareaTipoRelacion($operacion, $recibido, $ClasePermisos)
{
    // @ Objetivo
    // Ejecutar una de las seis operaciones sobre un tipo, si quien la pide tiene su permiso.
    // @ Parametros
    //      $operacion -> (string) crear, renombrar, modificar, baja, reactivar o borrar.
    //      $recibido -> (array) lo enviado: id, nombre, forma y llevaCantidad, según la operación.
    //      $ClasePermisos -> los permisos del usuario de la sesión.
    // @ Devolvemos
    //      ['datos'] con el id del tipo, o ['error'] con su advertencia.
    $id = isset($recibido['id']) ? $recibido['id'] : 0;
    $nombre = isset($recibido['nombre']) ? $recibido['nombre'] : '';
    $forma = isset($recibido['forma']) ? $recibido['forma'] : '';
    $llevaCantidad = isset($recibido['llevaCantidad']) && $recibido['llevaCantidad'] == 1;

    $CTipos = new RelacionesTipos();
    if (!permisoTipoRelacion($ClasePermisos, $operacion . '_tipo_relacion')) {
        $respuesta = array('error' => array('tipo' => 'warning', 'mensaje' => 'No tienes permiso para esta operación.'));
    } elseif ($operacion === 'crear') {
        $respuesta = $CTipos->crearTipo($nombre, $forma, $llevaCantidad);
    } elseif ($operacion === 'renombrar') {
        $respuesta = $CTipos->renombrarTipo($id, $nombre);
    } elseif ($operacion === 'modificar') {
        $respuesta = $CTipos->modificarTipo($id, $forma, $llevaCantidad);
    } elseif ($operacion === 'baja') {
        $respuesta = $CTipos->darDeBajaTipo($id);
    } elseif ($operacion === 'reactivar') {
        $respuesta = $CTipos->reactivarTipo($id);
    } else {
        $respuesta = $CTipos->borrarTipo($id);
    }

    return $respuesta;
}

function modalTipoRelacion($idTipo, $ClasePermisos)
{
    // @ Objetivo
    // El formulario de un tipo, para crearlo o para cambiarlo. Solo ofrece lo que quien lo
    // abre puede hacer y lo que el tipo admite: con relaciones, ni forma ni cantidad ni borrado.
    // @ Parametros
    //      $idTipo -> (int) id del tipo, o 0 para uno nuevo.
    // @ Devolvemos
    //      ['html'] con el formulario, o ['error'].
    $tipo = array('id' => 0, 'nombre' => '', 'forma' => '1:N', 'llevaCantidad' => 1, 'estado' => 'Activo');
    $tieneRelaciones = false;
    $respuesta = array();

    if ($idTipo != 0) {
        $CTipos = new RelacionesTipos();
        $leido = $CTipos->leerTipo($idTipo);
        if (isset($leido['error'])) {
            $respuesta = $leido;
        } elseif ($leido['datos'] === null) {
            $respuesta = array('error' => array('tipo' => 'warning', 'mensaje' => 'El tipo de relación no existe.'));
        } else {
            $tipo = $leido['datos'];
            $tieneRelaciones = $CTipos->tieneRelaciones($tipo['id']);
        }
    }

    if (!isset($respuesta['error'])) {
        $esNuevo = $tipo['id'] == 0;
        $puedeNombre = permisoTipoRelacion($ClasePermisos, $esNuevo ? 'crear_tipo_relacion' : 'renombrar_tipo_relacion');
        $puedeForma = !$tieneRelaciones && permisoTipoRelacion($ClasePermisos, $esNuevo ? 'crear_tipo_relacion' : 'modificar_tipo_relacion');

        $html = '<div class="col-md-12" id="formTipoRelacion" data-id="' . (int) $tipo['id'] . '"'
            . ' data-nombre="' . htmlspecialchars($tipo['nombre']) . '" data-forma="' . htmlspecialchars($tipo['forma']) . '"'
            . ' data-cantidad="' . (int) $tipo['llevaCantidad'] . '">';
        $html .= '<div class="form-group"><label>Nombre:</label> '
            . '<input type="text" id="tipoRelacionNombre" maxlength="50" value="' . htmlspecialchars($tipo['nombre']) . '"' . ($puedeNombre ? '' : ' disabled') . '></div>';
        $html .= '<div class="form-group"><label>Forma:</label> <select id="tipoRelacionForma"' . ($puedeForma ? '' : ' disabled') . '>';
        foreach (array('1:N', 'N:1', '1:1') as $forma) {
            $html .= '<option value="' . $forma . '"' . ($tipo['forma'] === $forma ? ' selected' : '') . '>' . nombreDeLaForma($forma) . '</option>';
        }
        $html .= '</select> <label><input type="checkbox" id="tipoRelacionCantidad"' . ($tipo['llevaCantidad'] == 1 ? ' checked' : '') . ($puedeForma ? '' : ' disabled') . '> Lleva cantidad</label></div>';
        if ($tieneRelaciones) {
            $html .= '<p class="text-muted">Este tipo tiene relaciones: solo se puede renombrar o dar de baja.</p>';
        }

        $html .= '<div class="text-left">';
        if ($puedeNombre || $puedeForma) {
            $html .= '<a class="btn btn-primary" id="btnTipoRelacionGuardar" onclick="guardarTipoRelacion()">Guardar</a> ';
        }
        if (!$esNuevo && $tipo['estado'] === 'Activo' && permisoTipoRelacion($ClasePermisos, 'baja_tipo_relacion')) {
            $html .= '<a class="btn btn-default" id="btnTipoRelacionBaja" onclick="operacionTipoRelacion(\'bajaTipoRelacion\')">Dar de baja</a> ';
        }
        if (!$esNuevo && $tipo['estado'] === 'Baja' && permisoTipoRelacion($ClasePermisos, 'reactivar_tipo_relacion')) {
            $html .= '<a class="btn btn-default" id="btnTipoRelacionReactivar" onclick="operacionTipoRelacion(\'reactivarTipoRelacion\')">Reactivar</a> ';
        }
        if (!$esNuevo && !$tieneRelaciones && permisoTipoRelacion($ClasePermisos, 'borrar_tipo_relacion')) {
            $html .= '<a class="btn btn-danger" id="btnTipoRelacionBorrar" onclick="operacionTipoRelacion(\'borrarTipoRelacion\')">Borrar</a>';
        }
        $html .= '</div></div>';

        $respuesta = array('html' => $html);
    }

    return $respuesta;
}
