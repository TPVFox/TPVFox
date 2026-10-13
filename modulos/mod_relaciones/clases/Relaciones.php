<?php

include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesModelo.php';
include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesTipos.php';

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
