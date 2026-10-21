<?php

// @ Objetivo
// El trabajo de las pantallas de productos relacionados que no es consulta: dejar en valores
// admitidos lo que llega en la dirección, antes de usarlo, y atender lo que el listado y la
// pantalla de relación piden por tareas.php. Cada función comprueba su permiso antes de leer o
// escribir nada.

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
        if (in_array($recibidos['orden'], array('numRelacion:ASC', 'numRelacion:DESC', 'fecha:ASC', 'fecha:DESC', 'estado:ASC', 'estado:DESC'), true)) {
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
    // Cambiar el estado de las relaciones marcadas en el listado: darlas de baja (Baja) o
    // reactivar las que están de baja (Activo). Cada relación se cambia por separado: la que no se puede devuelve su aviso y no
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
    } elseif ($estado !== 'Baja' && $estado !== 'Activo') {
        $respuesta['error'] = array('tipo' => 'warning', 'mensaje' => 'Ese estado no se admite: las relaciones solo se pueden dar de baja o reactivar.');
    } elseif (!is_array($numRelaciones) || count($numRelaciones) === 0) {
        $respuesta['error'] = array('tipo' => 'warning', 'mensaje' => 'No tienes items seleccionados.');
    } else {
        $respuesta['datos'] = array('cambiadas' => array());
        $respuesta['avisos'] = array();
        foreach ($numRelaciones as $recibido) {
            $numRelacion = (int) $recibido;
            if ($estado === 'Activo') {
                $cambio = $CRelaciones->reactivar($numRelacion, $Usuario['id']);
            } else {
                $cambio = $CRelaciones->darDeBaja($numRelacion, $Usuario['id']);
            }
            if (isset($cambio['error'])) {
                $respuesta['avisos'][] = array('tipo' => 'warning', 'mensaje' => 'Relación ' . $numRelacion . ': ' . $cambio['error']['mensaje']);
            } else {
                $respuesta['datos']['cambiadas'][] = $numRelacion;
            }
        }
    }

    return $respuesta;
}

function permisoParaPreparar($ClasePermisos, $numRelacion)
{
    // @ Objetivo
    // Saber si el usuario puede preparar lo que pide: crear, si la relación es nueva, y
    // modificar, si es una que rige.
    // @ Parametros
    //      $ClasePermisos -> (ClasePermisos) los permisos de la sesión.
    //      $numRelacion -> (int) número de la relación; 0 si es nueva.
    // @ Devolvemos
    //      (bool) true si lo tiene.
    $accion = 'Crear';
    if ($numRelacion > 0) {
        $accion = 'Modificar';
    }

    return permisoRelacion($ClasePermisos, $accion, 'ListaRelaciones.php');
}

function puedePrepararAlgo($ClasePermisos)
{
    // @ Objetivo
    // Saber si el usuario puede preparar relaciones, nuevas o cambios. Es lo primero que se
    // mira en las peticiones de la pantalla de relación, antes de leer nada.
    // @ Devolvemos
    //      (bool) true si tiene crear o modificar.
    return permisoRelacion($ClasePermisos, 'Crear', 'ListaRelaciones.php')
        || permisoRelacion($ClasePermisos, 'Modificar', 'ListaRelaciones.php');
}

function sinPermiso()
{
    // @ Objetivo
    // La respuesta de una petición que el usuario no puede hacer.
    return array('error' => array('tipo' => 'warning', 'mensaje' => 'No tienes permiso para esta operación: pídeselo al administrador.'));
}

function buscarProductosRelacion($caja, $texto, $CRelaciones, $ClasePermisos)
{
    // @ Objetivo
    // Buscar los artículos que se escriben en una de las cajas de la pantalla de relación.
    // @ Parametros
    //      $caja -> (string) la caja: idArticulo, Referencia, Codbarras o Descripcion.
    //      $texto -> (string) lo escrito.
    //      $CRelaciones -> (Relaciones) la clase del módulo.
    //      $ClasePermisos -> (ClasePermisos) los permisos de la sesión.
    // @ Devolvemos
    //      ['datos'] con 'productos' y, si hay más de uno, el 'html' de la lista para elegir;
    //      o ['error'].
    if (!puedePrepararAlgo($ClasePermisos)) {
        return sinPermiso();
    }
    if (!is_string($caja) || !is_string($texto)) {
        return array('error' => array('tipo' => 'warning', 'mensaje' => 'No se puede buscar por ese dato: usa la caja del id, la referencia, el código de barras o el nombre.'));
    }
    $respuesta = $CRelaciones->buscarArticulos($caja, $texto);
    if (!isset($respuesta['error'])) {
        $html = '';
        if (count($respuesta['datos']) > 1) {
            $html = htmlListadoArticulos($respuesta['datos']);
        }
        $respuesta = array('datos' => array('productos' => $respuesta['datos'], 'html' => $html));
    }

    return $respuesta;
}

function htmlLineasRelacion($numRelacion, $idTipo, $lineas, $datos, $CRelaciones, $ClasePermisos)
{
    // @ Objetivo
    // Componer las tablas de Entrada y de Salida de lo que se prepara, con cada línea marcada
    // frente a la versión que rige y el tipo de cambio que constará al activar. No escribe nada.
    // @ Parametros
    //      $numRelacion -> (int) número de la relación; 0 si es nueva.
    //      $idTipo -> (int) tipo de la relación: dice si las líneas llevan cantidad.
    //      $lineas -> (array) las líneas que hay en la pantalla.
    //      $datos -> (array) nombre, descripcion y diasViabilidad, si la pantalla los manda.
    //      $CRelaciones -> (Relaciones) la clase del módulo.
    //      $ClasePermisos -> (ClasePermisos) los permisos de la sesión.
    // @ Devolvemos
    //      ['datos'] con 'lineas' marcadas, el html de 'entrada' y de 'salida', y 'tipoCambio';
    //      o ['error'].
    if (!puedePrepararAlgo($ClasePermisos)) {
        return sinPermiso();
    }
    $numRelacion = (int) $numRelacion;
    $nuevas = array();
    if (is_array($lineas)) {
        foreach ($lineas as $linea) {
            if (is_array($linea) && isset($linea['idArticulo']) && isset($linea['sentido'])) {
                $nuevas[] = array(
                    'idArticulo' => (int) $linea['idArticulo'],
                    'sentido' => (string) $linea['sentido'],
                    'cantidad' => isset($linea['cantidad']) && $linea['cantidad'] !== '' ? (string) $linea['cantidad'] : null,
                    'unidad' => isset($linea['unidad']) && $linea['unidad'] !== '' ? (string) $linea['unidad'] : null,
                    'estado' => 'Activo',
                );
            }
        }
    }

    $vigente = null;
    if ($numRelacion > 0) {
        $leida = $CRelaciones->vigente($numRelacion);
        if (isset($leida['error'])) {
            return $leida;
        }
        $vigente = $leida['datos'];
    }

    $tipoCambio = 'alta';
    $queRigen = array();
    if ($vigente !== null) {
        $queRigen = $vigente['lineas'];
        $datosNuevos = array();
        foreach (array('nombre', 'descripcion', 'diasViabilidad') as $campo) {
            $datosNuevos[$campo] = $vigente[$campo];
            if (is_array($datos) && array_key_exists($campo, $datos)) {
                $datosNuevos[$campo] = trim((string) $datos[$campo]) === '' ? null : trim((string) $datos[$campo]);
            }
        }
        $tipoCambio = $CRelaciones->tipoDeCambio($vigente, $queRigen, $datosNuevos, $nuevas);
    }
    $marcadas = $CRelaciones->diferencias($queRigen, $nuevas);

    $articulos = $CRelaciones->datosDeArticulos(array_column($marcadas, 'idArticulo'));
    if (isset($articulos['error'])) {
        return $articulos;
    }
    foreach ($marcadas as $i => $linea) {
        $articulo = isset($articulos['datos'][$linea['idArticulo']]) ? $articulos['datos'][$linea['idArticulo']] : null;
        $marcadas[$i]['articulo_name'] = $articulo === null ? '' : $articulo['articulo_name'];
        $marcadas[$i]['crefTienda'] = $articulo === null ? null : $articulo['crefTienda'];
        $marcadas[$i]['codBarras'] = $articulo === null ? null : $articulo['codBarras'];
        $marcadas[$i]['catalogo'] = '';
        if ($articulo === null) {
            $marcadas[$i]['catalogo'] = 'noExiste';
        } elseif ($articulo['estado'] === 'Baja') {
            $marcadas[$i]['catalogo'] = 'baja';
        }
    }

    $llevaCantidad = true;
    $CTipos = new RelacionesTipos();
    $tipo = $CTipos->leerTipo((int) $idTipo);
    if (isset($tipo['datos']) && $tipo['datos'] !== null) {
        $llevaCantidad = $tipo['datos']['llevaCantidad'] == 1;
    }

    return array('datos' => array(
        'lineas' => $marcadas,
        'entrada' => htmlLineasEditables($marcadas, 'entrada', $llevaCantidad),
        'salida' => htmlLineasEditables($marcadas, 'salida', $llevaCantidad),
        'tipoCambio' => $tipoCambio,
    ));
}

function guardarTemporalRelacion($temporal, $CTemporales, $ClasePermisos, $Usuario)
{
    // @ Objetivo
    // Guardar lo que se prepara en la pantalla de relación, sin que rija. De un temporal ya
    // guardado, si es de una relación nueva o de una que rige lo dice la base, no la petición.
    // @ Parametros
    //      $temporal -> (array) lo que manda la pantalla: id, numRelacion, idTipo, nombre,
    //                   descripcion, diasViabilidad, origenAjuste, lineas y fechaModificacion.
    //      $CTemporales -> (RelacionesTemporales) la clase de los temporales.
    //      $ClasePermisos -> (ClasePermisos) los permisos de la sesión.
    //      $Usuario -> (array) el usuario de la sesión.
    // @ Devolvemos
    //      ['datos'] con el id del temporal y su fechaModificacion, o ['error'].
    if (!puedePrepararAlgo($ClasePermisos)) {
        return sinPermiso();
    }
    $id = (int) $temporal['id'];
    $numRelacion = (int) $temporal['numRelacion'];
    if ($id > 0) {
        $leido = $CTemporales->leer($id);
        if (isset($leido['error'])) {
            return $leido;
        }
        if ($leido['datos'] === null) {
            return array('error' => array('tipo' => 'warning', 'mensaje' => 'El cambio en preparación ya no existe: alguien lo canceló o lo activó. Vuelve al listado para ver cómo está la relación.'));
        }
        $numRelacion = (int) $leido['datos']['numRelacion'];
    }
    if (!permisoParaPreparar($ClasePermisos, $numRelacion)) {
        return sinPermiso();
    }
    $temporal['id'] = $id;
    $temporal['numRelacion'] = $numRelacion;

    return $CTemporales->guardar($temporal, $Usuario['id']);
}

function cancelarTemporalRelacion($idTemporal, $CTemporales, $ClasePermisos)
{
    // @ Objetivo
    // Borrar lo que se preparaba. Exige el mismo permiso que guardarlo.
    // @ Parametros
    //      $idTemporal -> (int) id del temporal, tal como llega.
    //      $CTemporales -> (RelacionesTemporales) la clase de los temporales.
    //      $ClasePermisos -> (ClasePermisos) los permisos de la sesión.
    // @ Devolvemos
    //      ['datos'] con el id borrado, o ['error'].
    if (!puedePrepararAlgo($ClasePermisos)) {
        return sinPermiso();
    }
    $id = (int) $idTemporal;
    $leido = $CTemporales->leer($id);
    if (isset($leido['error'])) {
        return $leido;
    }
    if ($leido['datos'] === null) {
        return array('error' => array('tipo' => 'warning', 'mensaje' => 'El cambio en preparación ya no existe: alguien lo canceló o lo activó. Vuelve al listado para ver cómo está la relación.'));
    }
    if (!permisoParaPreparar($ClasePermisos, (int) $leido['datos']['numRelacion'])) {
        return sinPermiso();
    }

    return $CTemporales->cancelar($id);
}

function activarTemporalRelacion($idTemporal, $CTemporales, $CRelaciones, $ClasePermisos, $Usuario)
{
    // @ Objetivo
    // Poner a regir lo preparado. Exige el permiso de activar: quien prepara no tiene por qué
    // poder poner a regir.
    // @ Parametros
    //      $idTemporal -> (int) id del temporal, tal como llega.
    //      $CTemporales -> (RelacionesTemporales) la clase de los temporales.
    //      $CRelaciones -> (Relaciones) la clase que escribe las relaciones.
    //      $ClasePermisos -> (ClasePermisos) los permisos de la sesión.
    //      $Usuario -> (array) el usuario de la sesión: queda como autor.
    // @ Devolvemos
    //      ['datos'] con numRelacion, version y tipoCambio, o ['error'].
    if (!permisoRelacion($ClasePermisos, 'Activar', 'ListaRelaciones.php')) {
        return sinPermiso();
    }

    return $CTemporales->activar((int) $idTemporal, $Usuario['id'], $CRelaciones);
}

function cantidadLegible($cantidad)
{
    // @ Objetivo
    // Una cantidad como se escribe: sin los ceros que la base añade a la derecha.
    // @ Devolvemos
    //      (string) la cantidad, o '' si no hay.
    $texto = $cantidad === null ? '' : (string) $cantidad;
    if (strpos($texto, '.') !== false) {
        $texto = rtrim(rtrim($texto, '0'), '.');
    }

    return $texto;
}

function htmlListadoArticulos($productos)
{
    // @ Objetivo
    // La lista de artículos encontrados, para elegir uno en la ventana modal, como la del
    // albarán: con su id, su referencia, su código de barras y su nombre, para no confundirlos.
    // @ Parametros
    //      $productos -> (array) los artículos encontrados.
    // @ Devolvemos
    //      (string) el html de la tabla.
    $html = '<table class="table table-striped"><thead><tr>'
        . '<th>Id</th><th>Referencia</th><th>Cód. barras</th><th>Nombre</th>'
        . '</tr></thead><tbody>';
    foreach ($productos as $i => $producto) {
        $html .= '<tr class="productoBuscado" style="cursor:pointer" onclick="elegirProducto(' . (int) $i . ')">'
            . '<td>' . (int) $producto['idArticulo'] . '</td>'
            . '<td>' . htmlspecialchars((string) $producto['crefTienda']) . '</td>'
            . '<td>' . htmlspecialchars((string) $producto['codBarras']) . '</td>'
            . '<td>' . htmlspecialchars((string) $producto['articulo_name']) . '</td>'
            . '</tr>';
    }

    return $html . '</tbody></table>';
}

function htmlLineasEditables($lineas, $sentido, $llevaCantidad)
{
    // @ Objetivo
    // Las filas de un lado —Entrada o Salida— de lo que se prepara, con su marca frente a la
    // versión que rige: nueva, con otra cantidad, o quitada. La quitada sigue a la vista,
    // tachada, y se puede reponer.
    // @ Parametros
    //      $lineas -> (array) las líneas ya marcadas y con los datos de su artículo.
    //      $sentido -> (string) entrada o salida.
    //      $llevaCantidad -> (bool) si el tipo lleva cantidad y unidad.
    // @ Devolvemos
    //      (string) el html de las filas.
    $html = '';
    $numero = 0;
    foreach ($lineas as $linea) {
        if ($linea['sentido'] === $sentido) {
            $numero++;
            $id = (int) $linea['idArticulo'];
            $clase = 'linea';
            $marca = '';
            if ($linea['marca'] === 'nueva') {
                $clase .= ' marca-nueva success';
                $marca = 'Nueva';
            } elseif ($linea['marca'] === 'cambiada') {
                $clase .= ' marca-cambiada warning';
                $marca = 'Antes: ' . htmlspecialchars(cantidadLegible($linea['cantidadQueRige']) . ' ' . $linea['unidadQueRige']);
            } elseif ($linea['marca'] === 'quitada') {
                $clase .= ' marca-quitada danger';
                $marca = 'Quitada';
            }
            $nombre = htmlspecialchars((string) $linea['articulo_name']);
            if ($linea['catalogo'] === 'baja') {
                $nombre .= ' <span class="label label-danger">de baja en el catálogo</span>';
            } elseif ($linea['catalogo'] === 'noExiste') {
                $nombre .= ' <span class="label label-danger">no existe en el catálogo</span>';
            }
            if ($linea['marca'] === 'quitada') {
                $nombre = '<s>' . $nombre . '</s>';
            }
            $html .= '<tr class="' . $clase . '" data-articulo="' . $id . '" data-sentido="' . $sentido . '">'
                . '<td>' . $numero . '</td>'
                . '<td>' . $id . '</td>'
                . '<td>' . htmlspecialchars((string) $linea['crefTienda']) . '</td>'
                . '<td>' . htmlspecialchars((string) $linea['codBarras']) . '</td>'
                . '<td>' . $nombre . '</td>';
            if ($llevaCantidad) {
                if ($linea['marca'] === 'quitada') {
                    $html .= '<td>' . htmlspecialchars(cantidadLegible($linea['cantidad'])) . '</td>'
                        . '<td>' . htmlspecialchars((string) $linea['unidad']) . '</td>';
                } else {
                    $unidades = '';
                    foreach (array('kg', 'ud') as $unidad) {
                        $elegida = $linea['unidad'] === $unidad ? ' selected' : '';
                        $unidades .= '<option value="' . $unidad . '"' . $elegida . '>' . $unidad . '</option>';
                    }
                    $html .= '<td><input class="cantidad" type="text" size="10" value="'
                        . htmlspecialchars(cantidadLegible($linea['cantidad'])) . '" oninput="recogerLinea(this)" onchange="cambiarLinea(this)"></td>'
                        . '<td><select class="unidad" onchange="cambiarLinea(this)">' . $unidades . '</select></td>';
                }
            }
            $html .= '<td>' . $marca . '</td><td>';
            if ($linea['marca'] === 'quitada') {
                $html .= '<a class="reponer glyphicon glyphicon-repeat" title="Reponer" onclick="reponerLinea(' . $id . ')"></a>';
            } else {
                $html .= '<a class="quitar glyphicon glyphicon-trash" title="Quitar" onclick="quitarLinea(' . $id . ')"></a>';
            }
            $html .= '</td></tr>';
        }
    }

    return $html;
}

function htmlLineasVigentes($lineas, $sentido, $llevaCantidad)
{
    // @ Objetivo
    // Las filas de un lado de la versión que rige, solo de lectura.
    // @ Parametros
    //      $lineas -> (array) las líneas de la versión, con los datos de su artículo.
    //      $sentido -> (string) entrada o salida.
    //      $llevaCantidad -> (bool) si el tipo lleva cantidad y unidad.
    // @ Devolvemos
    //      (string) el html de las filas.
    $html = '';
    $numero = 0;
    foreach ($lineas as $linea) {
        if ($linea['sentido'] === $sentido) {
            $numero++;
            $html .= '<tr class="linea">'
                . '<td>' . $numero . '</td>'
                . '<td>' . (int) $linea['idArticulo'] . '</td>'
                . '<td>' . htmlspecialchars((string) $linea['crefTienda']) . '</td>'
                . '<td>' . htmlspecialchars((string) $linea['codBarras']) . '</td>'
                . '<td>' . htmlspecialchars((string) $linea['articulo_name']) . '</td>';
            if ($llevaCantidad) {
                $html .= '<td>' . htmlspecialchars(cantidadLegible($linea['cantidad'])) . '</td>'
                    . '<td>' . htmlspecialchars((string) $linea['unidad']) . '</td>';
            }
            $html .= '</tr>';
        }
    }

    return $html;
}
