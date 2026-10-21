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
            $motivo = 'Hace falta un usuario identificado para guardar una relación: vuelve a entrar en TPVFox con el tuyo.';
        } else {
            $tipos = new RelacionesTipos();
            $tipo = $tipos->leerTipo($idTipo);
            if (isset($tipo['error']) || $tipo['datos'] === null) {
                $motivo = 'El tipo de relación no existe: elige uno de la lista.';
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
        if ($idUsuario <= 0) {
            $respuesta = array('error' => $this->montarAdvertencia('warning', 'Hace falta un usuario identificado para guardar una relación: vuelve a entrar en TPVFox con el tuyo.'));
        } else {
            // Las versiones se leen bloqueadas, dentro de la escritura: una baja de la misma
            // relación que llegue a la vez espera a que esta termine, y después ve el borrador.
            $this->iniciarEscritura();
            $activa = null;
            $tieneBorrador = false;
            $deBaja = false;
            $sql = "SELECT id, idTipo, nombre, descripcion, diasViabilidad, estado FROM relaciones"
                . " WHERE numRelacion = ? AND estado IN ('Activo', 'Borrador', 'Baja') FOR UPDATE";
            $respuesta = $this->consultaPreparada($sql, array($numRelacion));
            if (isset($respuesta['error'])) {
                $respuesta = array('error' => $this->montarAdvertencia('danger', 'No se pudo leer la relación: vuelve a intentarlo y, si se repite, avisa al administrador.'));
            } else {
                foreach ($respuesta['datos'] as $version) {
                    if ($version['estado'] === 'Activo') {
                        $activa = $version;
                    } elseif ($version['estado'] === 'Baja') {
                        $deBaja = true;
                    } else {
                        $tieneBorrador = true;
                    }
                }
                if ($deBaja) {
                    $respuesta = array('error' => $this->montarAdvertencia('warning', 'La relación no está vigente: está de baja, reactívala desde el listado para poder cambiarla.'));
                } elseif ($activa === null) {
                    $respuesta = array('error' => $this->montarAdvertencia('warning', 'La relación no está vigente: se sustituyó o no existe, y ya no se puede cambiar.'));
                } elseif ($tieneBorrador) {
                    $respuesta = array('error' => $this->montarAdvertencia('warning', 'La relación ya tiene un borrador pendiente: descártalo antes de preparar otro cambio.'));
                }
            }

            if (!isset($respuesta['error'])) {
                $respuesta = $this->insertarBorrador($numRelacion, $activa['idTipo'], $activa, $idUsuario);
            }
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
        // Lo que el usuario puede corregir es un aviso; que no se pueda leer la base, un fallo.
        $gravedad = 'warning';

        // El borrador y la versión que rige, con sus líneas
        if ($idUsuario <= 0) {
            $motivo = 'Hace falta un usuario identificado para activar una relación: vuelve a entrar en TPVFox con el tuyo.';
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
                $motivo = 'No se pudo leer la relación: vuelve a intentarlo y, si se repite, avisa al administrador.';
                $gravedad = 'danger';
            } elseif (count($leido['datos']) > 0) {
                $activa = $leido['datos'][0];
                $lineasActiva = $this->lineasDe($activa['id']);
                if ($lineasActiva === null) {
                    $motivo = 'No se pudo leer la relación: vuelve a intentarlo y, si se repite, avisa al administrador.';
                    $gravedad = 'danger';
                }
            }            if ($motivo === '' && $activa === null) {
                // Un borrador de una relación que está de baja no rige: antes hay que reactivarla.
                $sql = "SELECT COUNT(*) AS deBaja FROM relaciones WHERE numRelacion = ? AND estado = 'Baja'";
                $deBaja = $this->consultaPreparada($sql, array($borrador['numRelacion']));
                if (isset($deBaja['error'])) {
                    $motivo = 'No se pudo leer la relación: vuelve a intentarlo y, si se repite, avisa al administrador.';
                    $gravedad = 'danger';
                } elseif ($deBaja['datos'][0]['deBaja'] > 0) {
                    $motivo = 'La relación no está vigente: está de baja, reactívala desde el listado para poder cambiarla.';
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
                    $motivo = 'Lo preparado no cambia nada respecto a la versión que rige: cambia algo o cancélalo.';
                } elseif ($tipoCambio === 'composicion') {
                    $version = ($partes[0] + 1) . '.0';
                } else {
                    $version = $partes[0] . '.' . ($partes[1] + 1);
                }
                if ($tipoCambio === 'proporciones' && trim((string) $origenAjuste) === '') {
                    $motivo = 'Es un ajuste de cantidades y falta su origen: escribe en «Origen del ajuste» de dónde salen las nuevas, por ejemplo la pesada del día.';
                }
            }
        }

        // Lo que solo se comprueba cuando cambian los productos
        if ($motivo === '' && ($tipoCambio === 'alta' || $tipoCambio === 'composicion')) {
            $motivo = $this->motivoPorCatalogo($borrador, $lineasBorrador, $lineasActiva);
        }

        if ($motivo !== '') {
            $respuesta = array('error' => $this->montarAdvertencia($gravedad, $motivo));
        } else {
            $this->iniciarEscritura();
            $respuesta = array('datos' => array());

            if ($activa !== null) {
                $sql = "UPDATE relaciones SET estado = 'Sustituido' WHERE id = ? AND estado = 'Activo'";
                $respuesta = $this->consultaPreparada($sql, array($activa['id']));
                if (!isset($respuesta['error']) && $respuesta['datos']['afectadas'] !== 1) {
                    $respuesta = array('error' => $this->montarAdvertencia('warning', 'La versión que regía ya no es la vigente: vuelve a abrir la relación para ver la que rige.'));
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

    public function alta($idTipo, $datos, $lineas, $idUsuario)
    {
        // @ Objetivo
        // Dar de alta una relación y ponerla a regir en una sola operación: es crear su
        // borrador y activarlo. Si la activación se rechaza, no queda ni el borrador.
        // @ Parametros
        //      Los de crearBorrador().
        // @ Devolvemos
        //      ['datos'] con id, numRelacion, version y tipoCambio, o ['error'].
        $this->iniciarEscritura();
        $respuesta = $this->crearBorrador($idTipo, $datos, $lineas, $idUsuario);
        if (!isset($respuesta['error'])) {
            $respuesta = $this->activarBorrador($respuesta['datos']['id'], $idUsuario);
        }

        return $this->terminarEscritura($respuesta);
    }

    public function modificar($numRelacion, $datos, $lineas, $idUsuario, $origenAjuste = null)
    {
        // @ Objetivo
        // Modificar una relación que rige en una sola operación: es crear el borrador del
        // cambio, editarlo y activarlo. Si la relación ya tiene un borrador pendiente se
        // rechaza, y si la activación se rechaza no queda ningún borrador.
        // @ Parametros
        //      $numRelacion -> (int) número de la relación.
        //      $datos, $lineas -> como en editarBorrador().
        //      $idUsuario -> (int) usuario de sesión.
        //      $origenAjuste -> (string) de dónde sale un ajuste de cantidades.
        // @ Devolvemos
        //      ['datos'] con id, numRelacion, version y tipoCambio, o ['error'].
        $this->iniciarEscritura();
        $respuesta = $this->crearBorradorDeCambio($numRelacion, $idUsuario);
        if (!isset($respuesta['error'])) {
            $idBorrador = $respuesta['datos']['id'];
            $respuesta = $this->editarBorrador($idBorrador, $datos, $lineas);
        }
        if (!isset($respuesta['error'])) {
            $respuesta = $this->activarBorrador($idBorrador, $idUsuario, $origenAjuste);
        }

        return $this->terminarEscritura($respuesta);
    }

    public function darDeBaja($numRelacion, $idUsuario)
    {
        // @ Objetivo
        // Dar de baja una relación que rige: su versión pasa de Activo a Baja. No se crea
        // ninguna versión ni cambia nada más, y la baja no deja autor ni momento. Si la
        // relación tiene un borrador pendiente, o un cambio en preparación en la pantalla de
        // relación, se rechaza: la baja no descarta lo que alguien está preparando.
        // @ Parametros
        //      $numRelacion -> (int) número de la relación.
        //      $idUsuario -> (int) usuario de sesión: hace falta uno identificado, aunque no se guarda.
        // @ Devolvemos
        //      ['datos'] con el id de la versión y su numRelacion, o ['error'].
        if ($idUsuario <= 0) {
            $respuesta = array('error' => $this->montarAdvertencia('warning', 'Hace falta un usuario identificado para dar de baja una relación.'));
        } else {
            // Las versiones se leen bloqueadas, dentro de la escritura: un borrador de la misma
            // relación que se cree a la vez espera a que la baja termine, y después ya no rige.
            $this->iniciarEscritura();
            $activa = null;
            $tieneBorrador = false;
            $sql = "SELECT id, estado FROM relaciones WHERE numRelacion = ? FOR UPDATE";
            $respuesta = $this->consultaPreparada($sql, array($numRelacion));
            if (!isset($respuesta['error'])) {
                foreach ($respuesta['datos'] as $version) {
                    if ($version['estado'] === 'Activo') {
                        $activa = $version;
                    } elseif ($version['estado'] === 'Borrador') {
                        $tieneBorrador = true;
                    }
                }
                if ($activa === null) {
                    $respuesta = array('error' => $this->montarAdvertencia('warning', 'La relación no está vigente.'));
                } elseif ($tieneBorrador) {
                    $respuesta = array('error' => $this->montarAdvertencia('warning', 'La relación tiene un borrador pendiente: descártalo para darla de baja.'));
                } else {
                    // El cambio en preparación se lee después del bloqueo y sin leer nada antes:
                    // si alguien guardó el primero mientras tanto, ya está confirmado y se ve.
                    $sql = 'SELECT COUNT(*) AS temporales FROM relacionesTemporales WHERE numRelacion = ?';
                    $temporales = $this->consultaPreparada($sql, array($numRelacion));
                    if (isset($temporales['error'])) {
                        $respuesta = $temporales;
                    } elseif ($temporales['datos'][0]['temporales'] > 0) {
                        $respuesta = array('error' => $this->montarAdvertencia('warning', 'La relación tiene un cambio en preparación: cancélalo para darla de baja.'));
                    }
                }
            }

            if (!isset($respuesta['error'])) {
                $sql = "UPDATE relaciones SET estado = 'Baja' WHERE id = ? AND estado = 'Activo'";
                $respuesta = $this->consultaPreparada($sql, array($activa['id']));
                if (!isset($respuesta['error']) && $respuesta['datos']['afectadas'] !== 1) {
                    $respuesta = array('error' => $this->montarAdvertencia('warning', 'La versión que regía ya no es la vigente: vuelve a abrir la relación para ver la que rige.'));
                }
            }
            if (!isset($respuesta['error'])) {
                $respuesta = array('datos' => array('id' => (int) $activa['id'], 'numRelacion' => (int) $numRelacion));
            }
            $respuesta = $this->terminarEscritura($respuesta);
        }

        return $respuesta;
    }

    public function reactivar($numRelacion, $idUsuario)
    {
        // @ Objetivo
        // Reactivar una relación de baja: su versión pasa de Baja a Activo, la inversa de dar
        // de baja. No se crea ninguna versión ni cambia nada más. Se rechaza si algún producto
        // de sus líneas está de baja en el catálogo, o si ya hay otra relación vigente del
        // mismo tipo con los mismos productos, que pudo darse de alta mientras estaba de baja.
        // @ Parametros
        //      $numRelacion -> (int) número de la relación.
        //      $idUsuario -> (int) usuario de sesión: hace falta uno identificado, aunque no se guarda.
        // @ Devolvemos
        //      ['datos'] con el id de la versión y su numRelacion, o ['error'].
        if ($idUsuario <= 0) {
            $respuesta = array('error' => $this->montarAdvertencia('warning', 'Hace falta un usuario identificado para reactivar una relación.'));
        } else {
            // Las versiones se leen bloqueadas, dentro de la escritura: una baja o un cambio de la
            // misma relación que llegue a la vez espera a que esta termine.
            $this->iniciarEscritura();
            $deBaja = null;
            $sql = "SELECT id, idTipo, estado FROM relaciones WHERE numRelacion = ? FOR UPDATE";
            $respuesta = $this->consultaPreparada($sql, array($numRelacion));
            if (!isset($respuesta['error'])) {
                foreach ($respuesta['datos'] as $version) {
                    if ($version['estado'] === 'Baja') {
                        $deBaja = $version;
                    }
                }
                if ($deBaja === null) {
                    $respuesta = array('error' => $this->montarAdvertencia('warning', 'La relación no está de baja.'));
                }
            }

            if (!isset($respuesta['error'])) {
                $lineas = $this->lineasDe($deBaja['id']);
                if ($lineas === null) {
                    $respuesta = array('error' => $this->montarAdvertencia('danger', 'No se pudo leer la relación: vuelve a intentarlo y, si se repite, avisa al administrador.'));
                } else {
                    $motivo = '';
                    foreach ($lineas as $linea) {
                        if ($motivo === '' && $linea['estado'] === 'Activo') {
                            $sql = 'SELECT estado, articulo_name FROM articulos WHERE idArticulo = ?';
                            $articulo = $this->consultaPreparada($sql, array($linea['idArticulo']));
                            if (isset($articulo['error']) || count($articulo['datos']) === 0) {
                                $motivo = 'El artículo ' . $linea['idArticulo'] . ' no existe en el catálogo: no se puede reactivar la relación.';
                            } elseif ($articulo['datos'][0]['estado'] === 'Baja') {
                                $motivo = 'El artículo «' . $articulo['datos'][0]['articulo_name'] . '» está de baja en el catálogo: no se puede reactivar la relación.';
                            }
                        }
                    }
                    if ($motivo === '') {
                        $motivo = $this->motivoPorRepetida(array('idTipo' => $deBaja['idTipo'], 'numRelacion' => $numRelacion), $lineas, 'descártala o dala de baja antes de reactivar esta');
                    }
                    if ($motivo !== '') {
                        $respuesta = array('error' => $this->montarAdvertencia('warning', $motivo));
                    }
                }
            }

            if (!isset($respuesta['error'])) {
                $sql = "UPDATE relaciones SET estado = 'Activo' WHERE id = ? AND estado = 'Baja'";
                $respuesta = $this->consultaPreparada($sql, array($deBaja['id']));
                if (!isset($respuesta['error']) && $respuesta['datos']['afectadas'] !== 1) {
                    $respuesta = array('error' => $this->montarAdvertencia('warning', 'La relación ya no está de baja: vuelve a abrir el listado para ver cómo está.'));
                }
            }
            if (!isset($respuesta['error'])) {
                $respuesta = array('datos' => array('id' => (int) $deBaja['id'], 'numRelacion' => (int) $numRelacion));
            }
            $respuesta = $this->terminarEscritura($respuesta);
        }

        return $respuesta;
    }

    public function vigentes($idArticulo = 0)
    {
        // @ Objetivo
        // Las relaciones que rigen ahora, cada una con su tipo y sus líneas. Es la única
        // lectura de «qué rige»: de ella parten el listado y la comprobación de repetidas.
        // @ Parametros
        //      $idArticulo -> (int) opcional: solo las relaciones en las que ese producto
        //                     entra o sale. Sin él, todas.
        // @ Devolvemos
        //      ['datos'] con una relación por elemento y sus líneas en 'lineas', o ['error'].
        $condicion = "r.estado = 'Activo'";
        $valores = array();
        // No se convierte a número: un valor que no sea un id no debe leerse como «todas».
        if ($idArticulo != 0) {
            $condicion .= " AND r.id IN (SELECT idRelacion FROM relacionesLineas"
                . " WHERE idArticulo = ? AND estado = 'Activo')";
            $valores[] = $idArticulo;
        }
        $respuesta = $this->versionesConLineas($condicion, $valores);

        return $respuesta;
    }

    public function vigente($numRelacion)
    {
        // @ Objetivo
        // La versión que rige de una relación, con su tipo y sus líneas. Sale de la misma
        // lectura que las vigentes de todas.
        // @ Parametros
        //      $numRelacion -> (int) número de la relación.
        // @ Devolvemos
        //      ['datos'] con la versión, o null si no rige ninguna; o ['error'].
        $respuesta = $this->versionesConLineas("r.estado = 'Activo' AND r.numRelacion = ?", array($numRelacion));
        if (!isset($respuesta['error'])) {
            $version = null;
            if (count($respuesta['datos']) > 0) {
                $version = $respuesta['datos'][0];
            }
            $respuesta = array('datos' => $version);
        }

        return $respuesta;
    }

    public function listado($buscar, $nombreTipo, $orden, $pagina, $filasPorPagina)
    {
        // @ Objetivo
        // Una página del listado de relaciones, las que rigen y las dadas de baja: solo se
        // leen las filas de esa página. De cada una sale su versión en estado Activo o Baja,
        // con sus líneas activas. Un borrador o una versión sustituida no cuentan.
        // @ Parametros
        //      $buscar -> (string) palabras; cada una tiene que estar en el nombre de la relación
        //                 o en el de alguno de sus productos. '' no busca.
        //      $nombreTipo -> (string) nombre del tipo de relación. '' no filtra.
        //      $orden -> (string) 'numRelacion:ASC', 'numRelacion:DESC', 'fecha:ASC', 'fecha:DESC',
        //                'estado:ASC' o 'estado:DESC'.
        //                Cualquier otro valor ordena por número de relación.
        //      $pagina -> (int) página pedida. Si ya no existe se devuelve la última que hay.
        //      $filasPorPagina -> (int)
        // @ Devolvemos
        //      ['datos'] con 'total' (relaciones que casan), 'pagina' (la devuelta) y 'filas',
        //      cada una con numRelacion, relacion, tipo, version, estado, fecha y numProductos. O ['error'].
        $condicion = "r.estado IN ('Activo', 'Baja')";
        $valores = array();
        if ($nombreTipo !== '') {
            $condicion .= ' AND t.nombre = ?';
            $valores[] = $nombreTipo;
        }
        foreach (explode(' ', $buscar) as $palabra) {
            if ($palabra !== '') {
                // % y _ se buscan como texto, no como comodines.
                $patron = '%' . addcslashes($palabra, '%_\\') . '%';
                $condicion .= ' AND (r.nombre LIKE ? OR EXISTS (SELECT 1 FROM relacionesLineas lb'
                    . ' JOIN articulos ab ON ab.idArticulo = lb.idArticulo'
                    . " WHERE lb.idRelacion = r.id AND lb.estado = 'Activo' AND ab.articulo_name LIKE ?))";
                $valores[] = $patron;
                $valores[] = $patron;
            }
        }
        $ordenes = array(
            'numRelacion:ASC' => 'r.numRelacion ASC',
            'numRelacion:DESC' => 'r.numRelacion DESC',
            'fecha:ASC' => 'r.fechaCreacion ASC, r.numRelacion ASC',
            'fecha:DESC' => 'r.fechaCreacion DESC, r.numRelacion DESC',
            'estado:ASC' => 'r.estado ASC, r.numRelacion ASC',
            'estado:DESC' => 'r.estado DESC, r.numRelacion ASC',
        );
        $ordenarPor = $ordenes['numRelacion:ASC'];
        if (isset($ordenes[$orden])) {
            $ordenarPor = $ordenes[$orden];
        }
        $filasPorPagina = (int) $filasPorPagina;
        $pagina = (int) $pagina;

        $desdeDonde = ' FROM relaciones r JOIN relacionesTipos t ON t.id = r.idTipo WHERE ' . $condicion;
        $respuesta = $this->consultaPreparada('SELECT COUNT(*) AS total' . $desdeDonde, $valores);

        if (!isset($respuesta['error'])) {
            $total = (int) $respuesta['datos'][0]['total'];
            $ultimaPagina = (int) ceil($total / $filasPorPagina);
            if ($pagina > $ultimaPagina) {
                $pagina = $ultimaPagina;
            }
            if ($pagina < 1) {
                $pagina = 1;
            }

            $sql = 'SELECT r.id, r.numRelacion, r.nombre, t.nombre AS tipo, t.forma, r.version, r.estado, r.fechaCreacion AS fecha'
                . $desdeDonde
                . ' ORDER BY ' . $ordenarPor
                . ' LIMIT ' . $filasPorPagina . ' OFFSET ' . (($pagina - 1) * $filasPorPagina);
            $respuesta = $this->consultaPreparada($sql, $valores);
        }
        if (!isset($respuesta['error'])) {
            $filas = array();
            $posicion = array();
            foreach ($respuesta['datos'] as $fila) {
                $posicion[$fila['id']] = count($filas);
                $filas[] = array(
                    'numRelacion' => (int) $fila['numRelacion'],
                    'relacion' => (string) $fila['nombre'],
                    'tipo' => $fila['tipo'],
                    'version' => $fila['version'],
                    'estado' => $fila['estado'],
                    'fecha' => $fila['fecha'],
                    'numProductos' => 0,
                    // Si la relación no tiene nombre, se llama como el producto que está solo en su lado.
                    'ladoQueDaNombre' => $fila['forma'] === 'N:1' ? 'salida' : 'entrada',
                );
            }
            if (count($filas) > 0) {
                // Las líneas se leen solo para las relaciones de la página.
                $sql = 'SELECT l.idRelacion, l.sentido, a.articulo_name AS articulo FROM relacionesLineas l'
                    . ' JOIN articulos a ON a.idArticulo = l.idArticulo'
                    . " WHERE l.estado = 'Activo' AND l.idRelacion IN (" . implode(', ', array_fill(0, count($posicion), '?')) . ')'
                    . ' ORDER BY l.id';
                $respuesta = $this->consultaPreparada($sql, array_keys($posicion));
            }
        }
        if (!isset($respuesta['error'])) {
            if (count($filas) > 0) {
                foreach ($respuesta['datos'] as $linea) {
                    $cual = $posicion[$linea['idRelacion']];
                    $filas[$cual]['numProductos']++;
                    if ($filas[$cual]['relacion'] === '' && $linea['sentido'] === $filas[$cual]['ladoQueDaNombre']) {
                        $filas[$cual]['relacion'] = $linea['articulo'];
                    }
                }
            }
            foreach ($filas as $cual => $fila) {
                unset($filas[$cual]['ladoQueDaNombre']);
            }
            $respuesta = array('datos' => array('total' => $total, 'pagina' => $pagina, 'filas' => $filas));
        }

        return $respuesta;
    }

    public function estadoEnFecha($numRelacion, $fecha)
    {
        // @ Objetivo
        // Qué versión de una relación regía en una fecha. Un borrador no cuenta.
        // @ Parametros
        //      $numRelacion -> (int) número de la relación.
        //      $fecha -> (string) 'AAAA-MM-DD' o 'AAAA-MM-DD HH:MM:SS'. Con solo el día se
        //                responde lo que regía al terminar ese día.
        // @ Devolvemos
        //      ['datos'] con 'situacion' ('regia' o 'noExistia') y 'version', que es la versión
        //      con sus líneas y su estado actual, o null. O ['error']. La baja no deja fecha: de
        //      una relación de baja se devuelve la última versión anterior a la fecha, en Baja.
        $situacion = 'noExistia';
        $version = null;
        if (strlen($fecha) === 10) {
            $fecha .= ' 23:59:59';
        }

        // Entre dos versiones con la misma fecha, el id mayor es la que se activó después.
        $sql = "SELECT id, estado FROM relaciones"
            . " WHERE numRelacion = ? AND estado <> 'Borrador' AND fechaCreacion <= ?"
            . " ORDER BY fechaCreacion DESC, id DESC LIMIT 1";
        $respuesta = $this->consultaPreparada($sql, array($numRelacion, $fecha));

        if (!isset($respuesta['error']) && count($respuesta['datos']) === 1) {
            $estado = $respuesta['datos'][0]['estado'];
            $respuesta = $this->versionesConLineas('r.id = ?', array($respuesta['datos'][0]['id']));
            if (!isset($respuesta['error']) && count($respuesta['datos']) === 1) {
                $situacion = 'regia';
                $version = $respuesta['datos'][0];
                $version['estado'] = $estado;
            }
        }
        if (!isset($respuesta['error'])) {
            $respuesta = array('datos' => array('situacion' => $situacion, 'version' => $version));
        }

        return $respuesta;
    }

    public function comprobarVersiones()
    {
        // @ Objetivo
        // Buscar relaciones en un estado que las operaciones del módulo no dejan nunca: con
        // más de una versión entre la que rige y la de baja, con más de un borrador, de baja y
        // con un borrador o con un cambio en preparación, o sin versión que rija, sin borrador
        // y sin baja. Si todo está bien no devuelve ninguna.
        // @ Devolvemos
        //      ['datos'] con una fila por relación afectada ('numRelacion' y 'motivo'), o ['error'].
        $sql = "SELECT r.numRelacion, SUM(r.estado = 'Activo') AS activas,"
            . " SUM(r.estado = 'Borrador') AS borradores, SUM(r.estado = 'Baja') AS bajas,"
            . " (SELECT COUNT(*) FROM relacionesTemporales t WHERE t.numRelacion = r.numRelacion) AS temporales"
            . " FROM relaciones r GROUP BY r.numRelacion"
            . " HAVING activas + bajas > 1 OR borradores > 1 OR (bajas > 0 AND borradores > 0)"
            . " OR (bajas > 0 AND temporales > 0)"
            . " OR (activas = 0 AND borradores = 0 AND bajas = 0)"
            . " ORDER BY r.numRelacion";
        $respuesta = $this->consultaPreparada($sql);

        if (!isset($respuesta['error'])) {
            $afectadas = array();
            foreach ($respuesta['datos'] as $fila) {
                if ($fila['activas'] > 1) {
                    $motivo = 'Tiene más de una versión rigiendo.';
                } elseif ($fila['activas'] + $fila['bajas'] > 1) {
                    $motivo = 'Tiene más de una versión entre la que rige y la de baja.';
                } elseif ($fila['borradores'] > 1) {
                    $motivo = 'Tiene más de un borrador.';
                } elseif ($fila['bajas'] > 0 && $fila['borradores'] > 0) {
                    $motivo = 'Está de baja y tiene un borrador.';
                } elseif ($fila['bajas'] > 0 && $fila['temporales'] > 0) {
                    $motivo = 'Está de baja y tiene un cambio en preparación.';
                } else {
                    $motivo = 'No tiene ninguna versión que rija, ni borrador, ni baja.';
                }
                $afectadas[] = array('numRelacion' => $fila['numRelacion'], 'motivo' => $motivo);
            }
            $respuesta = array('datos' => $afectadas);
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

    public function diferencias($lineasQueRigen, $lineasNuevas)
    {
        // @ Objetivo
        // Marcar, línea a línea, lo que difiere de la versión que rige. Compara igual que
        // tipoDeCambio(): por producto y sentido, y la cantidad con la misma tolerancia. Así lo
        // que la pantalla marca es lo que constará en el historial.
        // @ Parametros
        //      $lineasQueRigen -> (array) las de la versión que rige; vacío si no rige ninguna.
        //      $lineasNuevas -> (array) las que se preparan.
        // @ Devolvemos
        //      (array) las líneas nuevas, cada una con 'marca': 'nueva' si su producto con su
        //      sentido no estaba; 'cambiada' si cambia su cantidad o su unidad, con
        //      'cantidadQueRige' y 'unidadQueRige'; o '' si no cambia. Detrás, las que rigen y ya
        //      no están, con la marca 'quitada'. Sin versión que rija no se marca nada.
        $queRigen = array();
        foreach ($lineasQueRigen as $linea) {
            if ($linea['estado'] === 'Activo') {
                $queRigen[$linea['idArticulo'] . '|' . $linea['sentido']] = $linea;
            }
        }

        $marcadas = array();
        $siguen = array();
        foreach ($lineasNuevas as $linea) {
            if ($linea['estado'] === 'Activo') {
                $par = $linea['idArticulo'] . '|' . $linea['sentido'];
                $linea['marca'] = '';
                if (count($queRigen) > 0) {
                    if (!isset($queRigen[$par])) {
                        $linea['marca'] = 'nueva';
                    } else {
                        $siguen[] = $par;
                        $anterior = $queRigen[$par];
                        $otraCantidad = abs((float) $anterior['cantidad'] - (float) $linea['cantidad']) > 0.0000001;
                        $otraUnidad = (string) $anterior['unidad'] !== (string) $linea['unidad'];
                        if ($otraCantidad || $otraUnidad) {
                            $linea['marca'] = 'cambiada';
                            $linea['cantidadQueRige'] = $anterior['cantidad'];
                            $linea['unidadQueRige'] = $anterior['unidad'];
                        }
                    }
                }
                $marcadas[] = $linea;
            }
        }
        foreach ($queRigen as $par => $linea) {
            if (!in_array($par, $siguen)) {
                $linea['marca'] = 'quitada';
                $marcadas[] = $linea;
            }
        }

        return $marcadas;
    }

    public function buscarArticulos($caja, $texto)
    {
        // @ Objetivo
        // Buscar artículos para añadirlos a una relación, como en el albarán: primero el valor
        // idéntico en el campo de la caja y, si no hay ninguno, por palabras. No se ofrecen los
        // artículos de baja, que la admisión rechazaría. La referencia es la de la tienda
        // principal, como en la balanza.
        // @ Parametros
        //      $caja -> (string) la caja en que se escribió: idArticulo, Referencia, Codbarras
        //               o Descripcion.
        //      $texto -> (string) lo escrito.
        // @ Devolvemos
        //      ['datos'] con hasta 30 artículos: idArticulo, articulo_name, crefTienda, codBarras
        //      y estado; o ['error'] si la caja no es una de las cuatro o falla la consulta.
        $campos = array(
            'idArticulo' => 'a.idArticulo',
            'Referencia' => 'at.crefTienda',
            'Codbarras' => 'ac.codBarras',
            'Descripcion' => 'a.articulo_name',
        );
        $texto = trim((string) $texto);
        if (!is_string($caja) || !isset($campos[$caja])) {
            return array('error' => $this->montarAdvertencia('warning', 'No se puede buscar por ese dato: usa la caja del id, la referencia, el código de barras o el nombre.'));
        }
        if ($texto === '') {
            return array('datos' => array());
        }

        // El campo sale de la lista de arriba, nunca de lo recibido; lo escrito va aparte.
        $campo = $campos[$caja];
        $consulta = "SELECT a.idArticulo, a.articulo_name, MIN(at.crefTienda) AS crefTienda,"
            . " MIN(ac.codBarras) AS codBarras, a.estado"
            . " FROM articulos a"
            . " LEFT JOIN articulosTiendas at ON at.idArticulo = a.idArticulo"
            . " AND at.idTienda = (SELECT idTienda FROM tiendas WHERE tipoTienda = 'principal' LIMIT 1)"
            . " LEFT JOIN articulosCodigoBarras ac ON ac.idArticulo = a.idArticulo"
            . " WHERE a.estado <> 'Baja' AND %s"
            . " GROUP BY a.idArticulo, a.articulo_name, a.estado"
            . " ORDER BY a.articulo_name LIMIT 30";

        $respuesta = $this->consultaPreparada(sprintf($consulta, $campo . ' = ?'), array($texto));
        if (!isset($respuesta['error']) && count($respuesta['datos']) === 0) {
            // Cada palabra tiene que estar; un % o un _ escrito se busca tal cual, no como comodín.
            $condiciones = array();
            $valores = array();
            foreach (explode(' ', $texto) as $palabra) {
                if ($palabra !== '') {
                    $condiciones[] = $campo . ' LIKE ?';
                    $valores[] = '%' . addcslashes($palabra, '%_\\') . '%';
                }
            }
            $respuesta = $this->consultaPreparada(sprintf($consulta, implode(' AND ', $condiciones)), $valores);
        }

        return $respuesta;
    }

    public function datosDeArticulos($idArticulos)
    {
        // @ Objetivo
        // Lo que se enseña de cada artículo de unas líneas: nombre, referencia de la tienda
        // principal, código de barras y estado en el catálogo. Se lee al presentar: las líneas
        // solo guardan el identificador.
        // @ Parametros
        //      $idArticulos -> (array) los identificadores.
        // @ Devolvemos
        //      ['datos'] con los artículos que existen, cada uno por su identificador; o ['error'].
        $ids = array();
        foreach ($idArticulos as $idArticulo) {
            $ids[] = (int) $idArticulo;
        }
        if (count($ids) === 0) {
            return array('datos' => array());
        }

        $sql = "SELECT a.idArticulo, a.articulo_name, MIN(at.crefTienda) AS crefTienda,"
            . " MIN(ac.codBarras) AS codBarras, a.estado"
            . " FROM articulos a"
            . " LEFT JOIN articulosTiendas at ON at.idArticulo = a.idArticulo"
            . " AND at.idTienda = (SELECT idTienda FROM tiendas WHERE tipoTienda = 'principal' LIMIT 1)"
            . " LEFT JOIN articulosCodigoBarras ac ON ac.idArticulo = a.idArticulo"
            . " WHERE a.idArticulo IN (" . implode(', ', array_fill(0, count($ids), '?')) . ")"
            . " GROUP BY a.idArticulo, a.articulo_name, a.estado";
        $respuesta = $this->consultaPreparada($sql, $ids);
        if (!isset($respuesta['error'])) {
            $articulos = array();
            foreach ($respuesta['datos'] as $articulo) {
                $articulos[$articulo['idArticulo']] = $articulo;
            }
            $respuesta = array('datos' => $articulos);
        }

        return $respuesta;
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

    private function versionesConLineas($condicion, $valores)
    {
        // @ Objetivo
        // Leer versiones con su tipo y sus líneas activas, y el nombre que cada producto tiene
        // ahora en el catálogo. Las filas de la consulta se agrupan por versión.
        // @ Parametros
        //      $condicion -> (string) el WHERE, escrito por quien llama y nunca con datos recibidos.
        //      $valores -> (array) los valores de los ? de esa condición.
        // @ Devolvemos
        //      ['datos'] con una versión por elemento y sus líneas en 'lineas', o ['error'].
        $sql = "SELECT r.id, r.numRelacion, r.idTipo, t.nombre AS tipo, t.forma, r.nombre, r.descripcion,"
            . " r.diasViabilidad, r.version, r.fechaCreacion,"
            . " l.idArticulo, a.articulo_name AS articulo, l.sentido, l.cantidad, l.unidad"
            . " FROM relaciones r"
            . " JOIN relacionesTipos t ON t.id = r.idTipo"
            . " JOIN relacionesLineas l ON l.idRelacion = r.id AND l.estado = 'Activo'"
            . " JOIN articulos a ON a.idArticulo = l.idArticulo"
            . " WHERE " . $condicion
            . " ORDER BY r.numRelacion, r.id, l.id";
        $respuesta = $this->consultaPreparada($sql, $valores);

        if (!isset($respuesta['error'])) {
            $versiones = array();
            $posicion = array();
            foreach ($respuesta['datos'] as $fila) {
                if (!isset($posicion[$fila['id']])) {
                    $posicion[$fila['id']] = count($versiones);
                    $versiones[] = array(
                        'id' => $fila['id'],
                        'numRelacion' => $fila['numRelacion'],
                        'idTipo' => $fila['idTipo'],
                        'tipo' => $fila['tipo'],
                        'forma' => $fila['forma'],
                        'nombre' => $fila['nombre'],
                        'descripcion' => $fila['descripcion'],
                        'diasViabilidad' => $fila['diasViabilidad'],
                        'version' => $fila['version'],
                        'fechaCreacion' => $fila['fechaCreacion'],
                        'lineas' => array(),
                    );
                }
                $versiones[$posicion[$fila['id']]]['lineas'][] = array(
                    'idArticulo' => $fila['idArticulo'],
                    'articulo' => $fila['articulo'],
                    'sentido' => $fila['sentido'],
                    'cantidad' => $fila['cantidad'],
                    'unidad' => $fila['unidad'],
                    'estado' => 'Activo',
                );
            }
            $respuesta = array('datos' => $versiones);
        }

        return $respuesta;
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
                $sql = 'SELECT estado, articulo_name FROM articulos WHERE idArticulo = ?';
                $articulo = $this->consultaPreparada($sql, array($linea['idArticulo']));
                if (isset($articulo['error']) || count($articulo['datos']) === 0) {
                    $motivo = 'El artículo ' . $linea['idArticulo'] . ' no existe en el catálogo: quítalo de la relación.';
                } elseif ($articulo['datos'][0]['estado'] === 'Baja') {
                    $motivo = 'El artículo «' . $articulo['datos'][0]['articulo_name'] . '» está de baja en el catálogo: quítalo de la relación.';
                }
            }
        }

        if ($motivo === '') {
            $motivo = $this->motivoPorRepetida($borrador, $lineasBorrador, 'modifica esa en lugar de crear otra');
        }

        return $motivo;
    }

    private function motivoPorRepetida($relacion, $lineas, $quehacer)
    {
        // @ Objetivo
        // Comprobar que no haya ya otra relación vigente del mismo tipo con los mismos
        // productos y sentidos.
        // @ Parametros
        //      $relacion -> (array) idTipo y numRelacion de la que se comprueba.
        //      $lineas -> (array) sus líneas.
        //      $quehacer -> (string) lo que se aconseja hacer, al final del motivo.
        // @ Devolvemos
        //      (string) el motivo del rechazo, o '' si no hay otra igual.
        $motivo = '';
        $vigentes = $this->vigentes();
        if (isset($vigentes['error'])) {
            $motivo = 'No se pudo comprobar si la relación ya existe: vuelve a intentarlo y, si se repite, avisa al administrador.';
        } else {
            $paresDeLaRelacion = $this->paresDe($lineas);
            foreach ($vigentes['datos'] as $otra) {
                if ($otra['idTipo'] == $relacion['idTipo'] && $otra['numRelacion'] != $relacion['numRelacion']) {
                    if ($this->paresDe($otra['lineas']) === $paresDeLaRelacion) {
                        $motivo = 'Ya existe una relación vigente de ese tipo con los mismos productos, la ' . $otra['numRelacion'] . ': ' . $quehacer . '.';
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
                    $motivo = 'El artículo ' . $linea['idArticulo'] . ' está repetido en la relación: quita una de sus líneas.';
                } else {
                    $articulosVistos[] = $linea['idArticulo'];
                    $sql = 'SELECT COUNT(*) AS cuantos FROM articulos WHERE idArticulo = ?';
                    $existe = $this->consultaPreparada($sql, array($linea['idArticulo']));
                    if (isset($existe['error']) || $existe['datos'][0]['cuantos'] == 0) {
                        $motivo = 'El artículo ' . $linea['idArticulo'] . ' no existe en el catálogo: quítalo de la relación.';
                    }
                }
            }
        }

        return $motivo;
    }
}
