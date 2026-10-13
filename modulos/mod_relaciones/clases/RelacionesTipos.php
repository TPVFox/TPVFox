<?php

include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesModelo.php';
include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesFormas.php';

// @ Objetivo
// Los tipos de relación: el vocabulario que cada tienda define sobre las tres formas.
// Es la única clase que lee y escribe la tabla relacionesTipos.
class RelacionesTipos extends RelacionesModelo
{
    public function leerTipo($idTipo)
    {
        // @ Objetivo
        // Leer un tipo de relación.
        // @ Parametros
        //      $idTipo -> (int) id del tipo.
        // @ Devolvemos
        //      ['datos'] con la fila del tipo, o null si no existe.
        //      ['error'] y ['consulta'] si falla.
        $sql = 'SELECT id, nombre, forma, llevaCantidad, estado FROM relacionesTipos WHERE id = ?';
        $respuesta = $this->consultaPreparada($sql, array($idTipo));

        if (!isset($respuesta['error'])) {
            $tipo = null;
            if (count($respuesta['datos']) > 0) {
                $tipo = $respuesta['datos'][0];
            }
            $respuesta['datos'] = $tipo;
        }

        return $respuesta;
    }

    public function listarTipos($soloVigentes)
    {
        // @ Objetivo
        // Listar los tipos de relación, por nombre.
        // @ Parametros
        //      $soloVigentes -> (bool) true deja fuera los que están de baja.
        // @ Devolvemos
        //      ['datos'] con las filas, o ['error'] y ['consulta'] si falla.
        $sql = 'SELECT id, nombre, forma, llevaCantidad, estado FROM relacionesTipos';
        if ($soloVigentes) {
            $sql .= " WHERE estado = 'Activo'";
        }
        $sql .= ' ORDER BY nombre';

        return $this->consultaPreparada($sql);
    }

    public function crearTipo($nombre, $forma, $llevaCantidad)
    {
        // @ Objetivo
        // Crear un tipo de relación. Nace vigente.
        // @ Parametros
        //      $nombre -> (string) letras, números, espacios y guiones; hasta 50 caracteres.
        //      $forma -> (string) '1:N', 'N:1' o '1:1'.
        //      $llevaCantidad -> (bool) si las líneas de sus relaciones llevan cantidad.
        // @ Devolvemos
        //      ['datos'] con el id del tipo creado, o ['error'].
        $nombre = trim((string) $nombre);
        $motivo = $this->motivoPorNombre($nombre, 0);
        if ($motivo === '' && !isset(RelacionesFormas::FORMAS[$forma])) {
            $motivo = 'La forma del tipo no es ninguna de las tres admitidas.';
        }

        if ($motivo !== '') {
            $respuesta = array('error' => $this->montarAdvertencia('warning', $motivo));
        } else {
            $sql = "INSERT INTO relacionesTipos (nombre, forma, llevaCantidad, estado) VALUES (?, ?, ?, 'Activo')";
            $respuesta = $this->consultaPreparada($sql, array($nombre, $forma, $llevaCantidad ? 1 : 0));
            if (!isset($respuesta['error'])) {
                $respuesta = array('datos' => array('id' => (int) $respuesta['datos']['idInsertado']));
            }
        }

        return $respuesta;
    }

    public function renombrarTipo($idTipo, $nombre)
    {
        // @ Objetivo
        // Cambiar el nombre de un tipo. Se admite aunque tenga relaciones: todas pasan a
        // leerse con el nombre nuevo.
        // @ Devolvemos
        //      ['datos'] con el id del tipo, o ['error'].
        $nombre = trim((string) $nombre);
        $tipo = $this->leerTipo($idTipo);
        $motivo = '';
        if (isset($tipo['error'])) {
            $respuesta = $tipo;
        } elseif ($tipo['datos'] === null) {
            $motivo = 'El tipo de relación no existe.';
        } else {
            $motivo = $this->motivoPorNombre($nombre, $tipo['datos']['id']);
        }

        if ($motivo !== '') {
            $respuesta = array('error' => $this->montarAdvertencia('warning', $motivo));
        } elseif (!isset($tipo['error'])) {
            $sql = 'UPDATE relacionesTipos SET nombre = ? WHERE id = ?';
            $respuesta = $this->consultaPreparada($sql, array($nombre, $tipo['datos']['id']));
            if (!isset($respuesta['error'])) {
                $respuesta = array('datos' => array('id' => (int) $tipo['datos']['id']));
            }
        }

        return $respuesta;
    }

    public function modificarTipo($idTipo, $forma, $llevaCantidad)
    {
        // @ Objetivo
        // Cambiar la forma de un tipo y si sus líneas llevan cantidad. Solo si no tiene
        // ninguna relación, en ningún estado: es lo que se comprobó al admitirlas.
        // @ Devolvemos
        //      ['datos'] con el id del tipo, o ['error'].
        $tipo = $this->leerTipo($idTipo);
        $motivo = '';
        if (isset($tipo['error'])) {
            $respuesta = $tipo;
        } elseif ($tipo['datos'] === null) {
            $motivo = 'El tipo de relación no existe.';
        } elseif (!isset(RelacionesFormas::FORMAS[$forma])) {
            $motivo = 'La forma del tipo no es ninguna de las tres admitidas.';
        } else {
            $motivo = $this->motivoPorRelaciones($tipo['datos']['id']);
        }

        if ($motivo !== '') {
            $respuesta = array('error' => $this->montarAdvertencia('warning', $motivo));
        } elseif (!isset($tipo['error'])) {
            $sql = 'UPDATE relacionesTipos SET forma = ?, llevaCantidad = ? WHERE id = ?';
            $respuesta = $this->consultaPreparada($sql, array($forma, $llevaCantidad ? 1 : 0, $tipo['datos']['id']));
            if (!isset($respuesta['error'])) {
                $respuesta = array('datos' => array('id' => (int) $tipo['datos']['id']));
            }
        }

        return $respuesta;
    }

    public function darDeBajaTipo($idTipo)
    {
        // @ Objetivo
        // Dar de baja un tipo: deja de ofrecerse para relaciones nuevas. Las que ya lo
        // tienen se siguen leyendo y modificando.
        // @ Devolvemos
        //      ['datos'] con el id del tipo, o ['error'].
        $tipo = $this->leerTipo($idTipo);
        $motivo = '';
        if (isset($tipo['error'])) {
            $respuesta = $tipo;
        } elseif ($tipo['datos'] === null) {
            $motivo = 'El tipo de relación no existe.';
        } elseif ($tipo['datos']['estado'] === 'Baja') {
            $motivo = 'El tipo de relación ya está de baja.';
        }

        if ($motivo !== '') {
            $respuesta = array('error' => $this->montarAdvertencia('warning', $motivo));
        } elseif (!isset($tipo['error'])) {
            $sql = "UPDATE relacionesTipos SET estado = 'Baja' WHERE id = ?";
            $respuesta = $this->consultaPreparada($sql, array($tipo['datos']['id']));
            if (!isset($respuesta['error'])) {
                $respuesta = array('datos' => array('id' => (int) $tipo['datos']['id']));
            }
        }

        return $respuesta;
    }

    public function reactivarTipo($idTipo)
    {
        // @ Objetivo
        // Volver a poner vigente un tipo dado de baja, si no hay otro vigente con su nombre.
        // @ Devolvemos
        //      ['datos'] con el id del tipo, o ['error'].
        $tipo = $this->leerTipo($idTipo);
        $motivo = '';
        if (isset($tipo['error'])) {
            $respuesta = $tipo;
        } elseif ($tipo['datos'] === null) {
            $motivo = 'El tipo de relación no existe.';
        } elseif ($tipo['datos']['estado'] !== 'Baja') {
            $motivo = 'El tipo de relación no está de baja.';
        } else {
            $motivo = $this->motivoPorNombre($tipo['datos']['nombre'], $tipo['datos']['id']);
        }

        if ($motivo !== '') {
            $respuesta = array('error' => $this->montarAdvertencia('warning', $motivo));
        } elseif (!isset($tipo['error'])) {
            $sql = "UPDATE relacionesTipos SET estado = 'Activo' WHERE id = ?";
            $respuesta = $this->consultaPreparada($sql, array($tipo['datos']['id']));
            if (!isset($respuesta['error'])) {
                $respuesta = array('datos' => array('id' => (int) $tipo['datos']['id']));
            }
        }

        return $respuesta;
    }

    public function borrarTipo($idTipo)
    {
        // @ Objetivo
        // Borrar un tipo. Solo si no tiene ninguna relación, en ningún estado.
        // @ Devolvemos
        //      ['datos'] con el id del tipo borrado, o ['error'].
        $tipo = $this->leerTipo($idTipo);
        $motivo = '';
        if (isset($tipo['error'])) {
            $respuesta = $tipo;
        } elseif ($tipo['datos'] === null) {
            $motivo = 'El tipo de relación no existe.';
        } else {
            $motivo = $this->motivoPorRelaciones($tipo['datos']['id']);
        }

        if ($motivo !== '') {
            $respuesta = array('error' => $this->montarAdvertencia('warning', $motivo));
        } elseif (!isset($tipo['error'])) {
            $sql = 'DELETE FROM relacionesTipos WHERE id = ?';
            $respuesta = $this->consultaPreparada($sql, array($tipo['datos']['id']));
            if (!isset($respuesta['error'])) {
                $respuesta = array('datos' => array('id' => (int) $tipo['datos']['id']));
            }
        }

        return $respuesta;
    }

    public function tieneRelaciones($idTipo)
    {
        // @ Objetivo
        // Saber si un tipo tiene alguna relación, en cualquier estado. Si no se puede
        // comprobar se responde que sí: es lo que impide cambiar su forma o borrarlo.
        // @ Devolvemos
        //      (bool)
        return $this->motivoPorRelaciones($idTipo) !== '';
    }

    private function motivoPorNombre($nombre, $idTipoQueNoCuenta)
    {
        // @ Objetivo
        // Comprobar que un nombre de tipo se admite: sus caracteres, su longitud y que no
        // lo tenga ya otro tipo vigente.
        // @ Parametros
        //      $nombre -> (string) el nombre, ya sin espacios en los extremos.
        //      $idTipoQueNoCuenta -> (int) el propio tipo al renombrar o reactivar; 0 al crear.
        // @ Devolvemos
        //      (string) el motivo del rechazo, o '' si se admite.
        $motivo = '';
        if ($nombre === '') {
            $motivo = 'El tipo de relación necesita un nombre.';
        } elseif (mb_strlen($nombre) > 50) {
            $motivo = 'El nombre del tipo no puede tener más de 50 caracteres.';
        } elseif (preg_match('/^[\p{L}\p{N} \-]+$/u', $nombre) !== 1) {
            $motivo = 'El nombre del tipo solo admite letras, números, espacios y guiones.';
        } else {
            $sql = "SELECT COUNT(*) AS cuantos FROM relacionesTipos WHERE nombre = ? AND estado = 'Activo' AND id <> ?";
            $repetido = $this->consultaPreparada($sql, array($nombre, $idTipoQueNoCuenta));
            if (isset($repetido['error'])) {
                $motivo = 'No se pudo comprobar si el nombre ya existe.';
            } elseif ($repetido['datos'][0]['cuantos'] > 0) {
                $motivo = 'Ya hay un tipo vigente con ese nombre.';
            }
        }

        return $motivo;
    }

    private function motivoPorRelaciones($idTipo)
    {
        // @ Objetivo
        // Comprobar que un tipo no tiene ninguna relación, cuente o no como vigente.
        // @ Devolvemos
        //      (string) el motivo del rechazo, o '' si no tiene ninguna.
        $motivo = '';
        $sql = 'SELECT COUNT(*) AS cuantas FROM relaciones WHERE idTipo = ?';
        $relaciones = $this->consultaPreparada($sql, array($idTipo));
        if (isset($relaciones['error'])) {
            $motivo = 'No se pudo comprobar si el tipo tiene relaciones.';
        } elseif ($relaciones['datos'][0]['cuantas'] > 0) {
            $motivo = 'El tipo tiene relaciones: solo se puede renombrar o dar de baja.';
        }

        return $motivo;
    }
}
