<?php

include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesModelo.php';
include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesTipos.php';
include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesFormas.php';

// @ Objetivo
// Las operaciones sobre las relaciones entre productos. Una relación tiene versiones y
// cada versión tiene líneas, una por producto. Toda versión nace como borrador.
class Relaciones extends RelacionesModelo
{
    public function crearBorrador($idTipo, $datos, $lineas, $idUsuario)
    {
        // @ Objetivo
        // Preparar una relación nueva, que no rige hasta que se activa.
        // @ Parametros
        //      $idTipo -> (int) id del tipo de relación.
        //      $datos -> (array) opcionales: nombre, descripcion, diasViabilidad.
        //      $lineas -> (array) una por producto: idArticulo, sentido, cantidad, unidad.
        //      $idUsuario -> (int) usuario de sesión.
        // @ Devolvemos
        //      ['datos'] con el id del borrador y su numRelacion, o ['error'].
        $motivo = '';
        if ($idUsuario <= 0) {
            $motivo = 'Hace falta un usuario identificado para guardar una relación.';
        } else {
            $tipos = new RelacionesTipos();
            $tipo = $tipos->leerTipo($idTipo);
            if (isset($tipo['error']) || $tipo['datos'] === null) {
                $motivo = 'El tipo de relación no existe.';
            } else {
                $motivo = $this->motivoPorProductos($lineas);
            }
        }

        if ($motivo !== '') {
            $respuesta = array('error' => $this->montarAdvertencia('warning', $motivo));
        } else {
            $this->iniciarEscritura();
            $respuesta = $this->consultaPreparada('SELECT COALESCE(MAX(numRelacion), 0) + 1 AS siguiente FROM relaciones');
            if (!isset($respuesta['error'])) {
                $numRelacion = $respuesta['datos'][0]['siguiente'];
                $respuesta = $this->insertarBorrador($numRelacion, $idTipo, $datos, $idUsuario);
            }
            if (!isset($respuesta['error'])) {
                $idBorrador = $respuesta['datos']['idInsertado'];
                $respuesta = $this->insertarLineas($idBorrador, $lineas, 'Activo');
            }
            if (!isset($respuesta['error'])) {
                $respuesta = array('datos' => array('id' => $idBorrador, 'numRelacion' => (int) $numRelacion));
            }
            $respuesta = $this->terminarEscritura($respuesta);
        }

        return $respuesta;
    }

    public function crearBorradorDeCambio($numRelacion, $idUsuario)
    {
        // @ Objetivo
        // Preparar un cambio sobre una relación que rige. El borrador nace como copia de
        // la versión activa, que sigue rigiendo.
        // @ Parametros
        //      $numRelacion -> (int) número de la relación.
        //      $idUsuario -> (int) usuario de sesión.
        // @ Devolvemos
        //      ['datos'] con el id del borrador y su numRelacion, o ['error'].
        $motivo = '';
        $activa = null;
        if ($idUsuario <= 0) {
            $motivo = 'Hace falta un usuario identificado para guardar una relación.';
        } else {
            $sql = "SELECT id, idTipo, nombre, descripcion, diasViabilidad, estado FROM relaciones"
                . " WHERE numRelacion = ? AND estado IN ('Activo', 'Borrador')";
            $versiones = $this->consultaPreparada($sql, array($numRelacion));
            if (isset($versiones['error'])) {
                $motivo = 'No se pudo leer la relación.';
            } else {
                $tieneBorrador = false;
                foreach ($versiones['datos'] as $version) {
                    if ($version['estado'] === 'Activo') {
                        $activa = $version;
                    } else {
                        $tieneBorrador = true;
                    }
                }
                if ($activa === null) {
                    $motivo = 'La relación no está vigente.';
                } elseif ($tieneBorrador) {
                    $motivo = 'La relación ya tiene un borrador.';
                }
            }
        }

        if ($motivo !== '') {
            $respuesta = array('error' => $this->montarAdvertencia('warning', $motivo));
        } else {
            $this->iniciarEscritura();
            $respuesta = $this->insertarBorrador($numRelacion, $activa['idTipo'], $activa, $idUsuario);
            if (!isset($respuesta['error'])) {
                $idBorrador = $respuesta['datos']['idInsertado'];
                $sql = "INSERT INTO relacionesLineas (idRelacion, idArticulo, sentido, cantidad, unidad, estado)"
                    . " SELECT ?, idArticulo, sentido, cantidad, unidad, 'Activo' FROM relacionesLineas"
                    . " WHERE idRelacion = ? AND estado = 'Activo'";
                $respuesta = $this->consultaPreparada($sql, array($idBorrador, $activa['id']));
            }
            if (!isset($respuesta['error'])) {
                $respuesta = array('datos' => array('id' => $idBorrador, 'numRelacion' => (int) $numRelacion));
            }
            $respuesta = $this->terminarEscritura($respuesta);
        }

        return $respuesta;
    }

    public function editarBorrador($idBorrador, $datos, $lineas)
    {
        // @ Objetivo
        // Cambiar los datos y los productos de un borrador. Solo alcanza a un borrador: lo
        // que rige o ha regido no se edita.
        // @ Parametros
        //      $idBorrador -> (int) id de la fila del borrador.
        //      $datos -> (array) opcionales: nombre, descripcion, diasViabilidad.
        //      $lineas -> (array) los productos que quedan en el borrador.
        // @ Devolvemos
        //      ['datos'] con el id del borrador, o ['error'].
        $motivo = '';
        $sql = "SELECT numRelacion FROM relaciones WHERE id = ? AND estado = 'Borrador'";
        $borrador = $this->consultaPreparada($sql, array($idBorrador));
        if (isset($borrador['error']) || count($borrador['datos']) === 0) {
            $motivo = 'Solo se puede editar un borrador.';
        } else {
            $motivo = $this->motivoPorProductos($lineas);
        }

        if ($motivo !== '') {
            $respuesta = array('error' => $this->montarAdvertencia('warning', $motivo));
        } else {
            $numRelacion = $borrador['datos'][0]['numRelacion'];
            $this->iniciarEscritura();

            $sql = "UPDATE relaciones SET nombre = ?, descripcion = ?, diasViabilidad = ?"
                . " WHERE id = ? AND estado = 'Borrador'";
            $valores = array(
                isset($datos['nombre']) ? $datos['nombre'] : null,
                isset($datos['descripcion']) ? $datos['descripcion'] : null,
                isset($datos['diasViabilidad']) ? $datos['diasViabilidad'] : null,
                $idBorrador,
            );
            $respuesta = $this->consultaPreparada($sql, $valores);

            if (!isset($respuesta['error'])) {
                $sql = "DELETE FROM relacionesLineas WHERE idRelacion = ?"
                    . " AND idRelacion IN (SELECT id FROM relaciones WHERE estado = 'Borrador')";
                $respuesta = $this->consultaPreparada($sql, array($idBorrador));
            }
            if (!isset($respuesta['error'])) {
                $respuesta = $this->insertarLineas($idBorrador, $lineas, 'Activo');
            }
            if (!isset($respuesta['error'])) {
                // Los productos de la versión que rige que este borrador ya no lleva quedan
                // como líneas eliminadas, para que conste qué se quita.
                $sql = "SELECT l.idArticulo, l.sentido FROM relacionesLineas l"
                    . " JOIN relaciones r ON r.id = l.idRelacion"
                    . " WHERE r.numRelacion = ? AND r.estado = 'Activo' AND l.estado = 'Activo'";
                $respuesta = $this->consultaPreparada($sql, array($numRelacion));
            }
            if (!isset($respuesta['error'])) {
                $articulosDelBorrador = array();
                foreach ($lineas as $linea) {
                    $articulosDelBorrador[] = $linea['idArticulo'];
                }
                $quitadas = array();
                foreach ($respuesta['datos'] as $lineaActiva) {
                    if (!in_array($lineaActiva['idArticulo'], $articulosDelBorrador)) {
                        $quitadas[] = array(
                            'idArticulo' => $lineaActiva['idArticulo'],
                            'sentido' => $lineaActiva['sentido'],
                            'cantidad' => null,
                            'unidad' => null,
                        );
                    }
                }
                $respuesta = $this->insertarLineas($idBorrador, $quitadas, 'Eliminado');
            }
            if (!isset($respuesta['error'])) {
                $respuesta = array('datos' => array('id' => $idBorrador));
            }
            $respuesta = $this->terminarEscritura($respuesta);
        }

        return $respuesta;
    }

    public function descartarBorrador($idBorrador)
    {
        // @ Objetivo
        // Borrar un borrador y sus líneas. Solo alcanza a un borrador.
        // @ Parametros
        //      $idBorrador -> (int) id de la fila del borrador.
        // @ Devolvemos
        //      ['datos'] con el id descartado, o ['error'].
        $this->iniciarEscritura();

        $sql = "DELETE FROM relacionesLineas WHERE idRelacion = ?"
            . " AND idRelacion IN (SELECT id FROM relaciones WHERE estado = 'Borrador')";
        $respuesta = $this->consultaPreparada($sql, array($idBorrador));

        if (!isset($respuesta['error'])) {
            $sql = "DELETE FROM relaciones WHERE id = ? AND estado = 'Borrador'";
            $respuesta = $this->consultaPreparada($sql, array($idBorrador));
        }
        if (!isset($respuesta['error'])) {
            if ($respuesta['datos']['afectadas'] === 1) {
                $respuesta = array('datos' => array('id' => $idBorrador));
            } else {
                $respuesta = array('error' => $this->montarAdvertencia('warning', 'Solo se puede descartar un borrador.'));
            }
        }

        return $this->terminarEscritura($respuesta);
    }

    public function activarBorrador($idBorrador, $idUsuario, $origenAjuste = null)
    {
        // @ Objetivo
        // Poner a regir un borrador. Pasa la admisión, se deduce qué clase de cambio es
        // comparándolo con la versión que rige, recibe su número de versión y sustituye a
        // la anterior, todo en una sola escritura.
        // @ Parametros
        //      $idBorrador -> (int) id de la fila del borrador.
        //      $idUsuario -> (int) usuario de sesión: queda como autor de la versión.
        //      $origenAjuste -> (string) de dónde sale un ajuste de cantidades.
        // @ Devolvemos
        //      ['datos'] con id, numRelacion, version y tipoCambio, o ['error'].
        $motivo = '';
        $borrador = null;
        $activa = null;
        $lineasBorrador = array();
        $lineasActiva = array();
        $tipoCambio = '';
        $version = '';

        // El borrador y la versión que rige, con sus líneas
        if ($idUsuario <= 0) {
            $motivo = 'Hace falta un usuario identificado para activar una relación.';
        } else {
            $sql = "SELECT id, numRelacion, idTipo, nombre, descripcion, diasViabilidad FROM relaciones"
                . " WHERE id = ? AND estado = 'Borrador'";
            $leido = $this->consultaPreparada($sql, array($idBorrador));
            if (isset($leido['error']) || count($leido['datos']) === 0) {
                $motivo = 'Solo se puede activar un borrador.';
            } else {
                $borrador = $leido['datos'][0];
            }
        }
        if ($motivo === '') {
            $sql = "SELECT id, version, nombre, descripcion, diasViabilidad FROM relaciones"
                . " WHERE numRelacion = ? AND estado = 'Activo'";
            $leido = $this->consultaPreparada($sql, array($borrador['numRelacion']));
            $lineasBorrador = $this->lineasDe($borrador['id']);
            if (isset($leido['error']) || $lineasBorrador === null) {
                $motivo = 'No se pudo leer la relación.';
            } elseif (count($leido['datos']) > 0) {
                $activa = $leido['datos'][0];
                $lineasActiva = $this->lineasDe($activa['id']);
                if ($lineasActiva === null) {
                    $motivo = 'No se pudo leer la relación.';
                }
            }
        }

        // La admisión
        if ($motivo === '') {
            $tipos = new RelacionesTipos();
            $tipo = $tipos->leerTipo($borrador['idTipo']);
            $formas = new RelacionesFormas();
            $admision = $formas->admitir(isset($tipo['datos']) ? $tipo['datos'] : null, $lineasBorrador, $activa === null);
            if (isset($admision['error'])) {
                $motivo = $admision['error']['mensaje'];
            }
        }

        // Qué clase de cambio es y qué versión le corresponde
        if ($motivo === '') {
            if ($activa === null) {
                $tipoCambio = 'alta';
                $version = '1.0';
            } else {
                $tipoCambio = $this->tipoDeCambio($activa, $lineasActiva, $borrador, $lineasBorrador);
                $partes = explode('.', $activa['version']);
                if ($tipoCambio === '') {
                    $motivo = 'El borrador no cambia nada respecto a la versión que rige.';
                } elseif ($tipoCambio === 'composicion') {
                    $version = ($partes[0] + 1) . '.0';
                } else {
                    $version = $partes[0] . '.' . ($partes[1] + 1);
                }
                if ($tipoCambio === 'proporciones' && trim((string) $origenAjuste) === '') {
                    $motivo = 'Un ajuste de cantidades tiene que decir de qué origen sale.';
                }
            }
        }

        // Lo que solo se comprueba cuando cambian los productos
        if ($motivo === '' && ($tipoCambio === 'alta' || $tipoCambio === 'composicion')) {
            $motivo = $this->motivoPorCatalogo($borrador, $lineasBorrador, $lineasActiva);
        }

        if ($motivo !== '') {
            $respuesta = array('error' => $this->montarAdvertencia('warning', $motivo));
        } else {
            $this->iniciarEscritura();
            $respuesta = array('datos' => array());

            if ($activa !== null) {
                $sql = "UPDATE relaciones SET estado = 'Sustituido' WHERE id = ? AND estado = 'Activo'";
                $respuesta = $this->consultaPreparada($sql, array($activa['id']));
                if (!isset($respuesta['error']) && $respuesta['datos']['afectadas'] !== 1) {
                    $respuesta = array('error' => $this->montarAdvertencia('warning', 'La versión que regía ya no es la vigente.'));
                }
            }
            if (!isset($respuesta['error'])) {
                $sql = "UPDATE relaciones SET version = ?, tipoCambio = ?, origenAjuste = ?, idUsuario = ?,"
                    . " fechaCreacion = ?, estado = 'Activo' WHERE id = ? AND estado = 'Borrador'";
                $valores = array(
                    $version,
                    $tipoCambio,
                    $tipoCambio === 'proporciones' ? $origenAjuste : null,
                    $idUsuario,
                    date('Y-m-d H:i:s'),
                    $borrador['id'],
                );
                $respuesta = $this->consultaPreparada($sql, $valores);
                if (!isset($respuesta['error']) && $respuesta['datos']['afectadas'] !== 1) {
                    $respuesta = array('error' => $this->montarAdvertencia('warning', 'El borrador ya no se puede activar.'));
                }
            }
            if (!isset($respuesta['error'])) {
                $respuesta = array('datos' => array(
                    'id' => (int) $borrador['id'],
                    'numRelacion' => (int) $borrador['numRelacion'],
                    'version' => $version,
                    'tipoCambio' => $tipoCambio,
                ));
            }
            $respuesta = $this->terminarEscritura($respuesta);
        }

        return $respuesta;
    }

    public function tipoDeCambio($datosQueRigen, $lineasQueRigen, $datosNuevos, $lineasNuevas)
    {
        // @ Objetivo
        // Deducir qué clase de cambio hay entre la versión que rige y la nueva. Lo decide el
        // sistema, no quien pide el cambio. Solo cuentan las líneas activas.
        // @ Devolvemos
        //      (string) 'composicion' si cambian los productos o su sentido; si no,
        //      'proporciones' si cambian cantidades o unidades; si no, 'datos' si cambian
        //      el nombre, la descripción o la vida útil; y '' si no cambia nada.
        $cambio = '';

        if ($this->paresDe($lineasQueRigen) !== $this->paresDe($lineasNuevas)) {
            $cambio = 'composicion';
        }

        if ($cambio === '') {
            $cantidadesQueRigen = array();
            foreach ($lineasQueRigen as $linea) {
                if ($linea['estado'] === 'Activo') {
                    $cantidadesQueRigen[$linea['idArticulo']] = $linea;
                }
            }
            foreach ($lineasNuevas as $linea) {
                if ($linea['estado'] === 'Activo') {
                    $anterior = $cantidadesQueRigen[$linea['idArticulo']];
                    $otraCantidad = abs((float) $anterior['cantidad'] - (float) $linea['cantidad']) > 0.0000001;
                    $otraUnidad = (string) $anterior['unidad'] !== (string) $linea['unidad'];
                    if ($otraCantidad || $otraUnidad) {
                        $cambio = 'proporciones';
                    }
                }
            }
        }

        if ($cambio === '') {
            foreach (array('nombre', 'descripcion', 'diasViabilidad') as $campo) {
                if ((string) $datosQueRigen[$campo] !== (string) $datosNuevos[$campo]) {
                    $cambio = 'datos';
                }
            }
        }

        return $cambio;
    }

    private function paresDe($lineas)
    {
        // @ Objetivo
        // La composición de una versión: sus productos activos, cada uno con su sentido.
        // @ Devolvemos
        //      (array) textos 'idArticulo|sentido', ordenados para poder compararlos.
        $pares = array();
        foreach ($lineas as $linea) {
            if ($linea['estado'] === 'Activo') {
                $pares[] = $linea['idArticulo'] . '|' . $linea['sentido'];
            }
        }
        sort($pares);

        return $pares;
    }

    private function lineasDe($idRelacion)
    {
        // @ Objetivo
        // Leer las líneas de una versión.
        // @ Devolvemos
        //      (array) las líneas, o null si no se pudieron leer.
        $sql = 'SELECT idArticulo, sentido, cantidad, unidad, estado FROM relacionesLineas WHERE idRelacion = ? ORDER BY id';
        $leido = $this->consultaPreparada($sql, array($idRelacion));
        $lineas = null;
        if (!isset($leido['error'])) {
            $lineas = $leido['datos'];
        }

        return $lineas;
    }

    private function motivoPorCatalogo($borrador, $lineasBorrador, $lineasActiva)
    {
        // @ Objetivo
        // Las dos comprobaciones que se hacen cuando cambian los productos: que ninguno de
        // los que se incorporan esté de baja, y que no haya ya otra relación vigente del
        // mismo tipo con los mismos productos y sentidos.
        // @ Devolvemos
        //      (string) el motivo del rechazo, o '' si se admite.
        $motivo = '';

        $articulosQueYaEstaban = array();
        foreach ($lineasActiva as $linea) {
            if ($linea['estado'] === 'Activo') {
                $articulosQueYaEstaban[] = $linea['idArticulo'];
            }
        }
        foreach ($lineasBorrador as $linea) {
            if ($motivo === '' && $linea['estado'] === 'Activo' && !in_array($linea['idArticulo'], $articulosQueYaEstaban)) {
                $sql = 'SELECT estado FROM articulos WHERE idArticulo = ?';
                $articulo = $this->consultaPreparada($sql, array($linea['idArticulo']));
                if (isset($articulo['error']) || count($articulo['datos']) === 0) {
                    $motivo = 'Uno de los productos no existe en el catálogo.';
                } elseif ($articulo['datos'][0]['estado'] === 'Baja') {
                    $motivo = 'Uno de los productos que se incorporan está de baja en el catálogo.';
                }
            }
        }

        if ($motivo === '') {
            $sql = "SELECT r.id, l.idArticulo, l.sentido, l.estado FROM relaciones r"
                . " JOIN relacionesLineas l ON l.idRelacion = r.id"
                . " WHERE r.estado = 'Activo' AND r.idTipo = ? AND r.numRelacion <> ?";
            $otras = $this->consultaPreparada($sql, array($borrador['idTipo'], $borrador['numRelacion']));
            if (isset($otras['error'])) {
                $motivo = 'No se pudo comprobar si la relación ya existe.';
            } else {
                $lineasPorRelacion = array();
                foreach ($otras['datos'] as $linea) {
                    $lineasPorRelacion[$linea['id']][] = $linea;
                }
                $paresDelBorrador = $this->paresDe($lineasBorrador);
                foreach ($lineasPorRelacion as $lineasDeOtra) {
                    if ($this->paresDe($lineasDeOtra) === $paresDelBorrador) {
                        $motivo = 'Ya existe una relación vigente de ese tipo con los mismos productos.';
                    }
                }
            }
        }

        return $motivo;
    }

    private function insertarBorrador($numRelacion, $idTipo, $datos, $idUsuario)
    {
        // @ Objetivo
        // Insertar la fila de un borrador: versión 0.0, que es la reservada, y sin tipo de
        // cambio. La fecha la pone el servidor.
        // @ Devolvemos
        //      La respuesta de la inserción.
        $sql = "INSERT INTO relaciones (numRelacion, idTipo, nombre, descripcion, diasViabilidad,"
            . " version, idUsuario, fechaCreacion, estado) VALUES (?, ?, ?, ?, ?, '0.0', ?, ?, 'Borrador')";
        $valores = array(
            $numRelacion,
            $idTipo,
            isset($datos['nombre']) ? $datos['nombre'] : null,
            isset($datos['descripcion']) ? $datos['descripcion'] : null,
            isset($datos['diasViabilidad']) ? $datos['diasViabilidad'] : null,
            $idUsuario,
            date('Y-m-d H:i:s'),
        );

        return $this->consultaPreparada($sql, $valores);
    }

    private function insertarLineas($idRelacion, $lineas, $estado)
    {
        // @ Objetivo
        // Insertar las líneas de una versión, todas con el mismo estado.
        // @ Devolvemos
        //      ['datos'] si se insertaron todas, o el ['error'] de la primera que falla.
        $respuesta = array('datos' => array());
        $sql = 'INSERT INTO relacionesLineas (idRelacion, idArticulo, sentido, cantidad, unidad, estado)'
            . ' VALUES (?, ?, ?, ?, ?, ?)';
        foreach ($lineas as $linea) {
            if (!isset($respuesta['error'])) {
                $valores = array(
                    $idRelacion,
                    $linea['idArticulo'],
                    $linea['sentido'],
                    isset($linea['cantidad']) ? $linea['cantidad'] : null,
                    isset($linea['unidad']) ? $linea['unidad'] : null,
                    $estado,
                );
                $respuesta = $this->consultaPreparada($sql, $valores);
            }
        }

        return $respuesta;
    }

    private function motivoPorProductos($lineas)
    {
        // @ Objetivo
        // Comprobar que los productos de un borrador existen y no se repiten. A un borrador
        // no se le exige más: la forma de su tipo se comprueba al activarlo.
        // @ Devolvemos
        //      (string) el motivo del rechazo, o '' si son correctos.
        $motivo = '';
        $articulosVistos = array();
        foreach ($lineas as $linea) {
            if ($motivo === '') {
                if (in_array($linea['idArticulo'], $articulosVistos)) {
                    $motivo = 'Hay un producto repetido en la relación.';
                } else {
                    $articulosVistos[] = $linea['idArticulo'];
                    $sql = 'SELECT COUNT(*) AS cuantos FROM articulos WHERE idArticulo = ?';
                    $existe = $this->consultaPreparada($sql, array($linea['idArticulo']));
                    if (isset($existe['error']) || $existe['datos'][0]['cuantos'] == 0) {
                        $motivo = 'Uno de los productos no existe en el catálogo.';
                    }
                }
            }
        }

        return $motivo;
    }
}
