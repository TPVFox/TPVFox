<?php

include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesModelo.php';

// @ Objetivo
// Lo que se prepara en la pantalla de relación y todavía no rige: una fila por relación en la
// tabla de temporales, con sus líneas en una columna, como los temporales de los documentos.
// Guardar y cancelar solo tocan esa tabla. Activar pone a regir lo preparado por el camino de
// siempre —borrador, admisión e historial— y borra el temporal en la misma escritura.
class RelacionesTemporales extends RelacionesModelo
{
    public function leer($idTemporal)
    {
        // @ Objetivo
        // Leer un temporal, con sus líneas ya como lista.
        // @ Parametros
        //      $idTemporal -> (int) id del temporal.
        // @ Devolvemos
        //      ['datos'] con el temporal y el nombre de quien lo guardó por última vez, o null si
        //      no existe; o ['error'].
        $sql = 'SELECT t.*, u.username AS usuario FROM relacionesTemporales t'
            . ' LEFT JOIN usuarios u ON u.id = t.idUsuario WHERE t.id = ?';

        return $this->unTemporal($sql, array((int) $idTemporal));
    }

    public function deRelacion($numRelacion)
    {
        // @ Objetivo
        // Leer el temporal de una relación que rige, si alguien está preparando un cambio.
        // @ Parametros
        //      $numRelacion -> (int) número de la relación.
        // @ Devolvemos
        //      ['datos'] con el temporal y el nombre de quien lo guardó por última vez, o null si
        //      no hay ninguno; o ['error'].
        $sql = 'SELECT t.*, u.username AS usuario FROM relacionesTemporales t'
            . ' LEFT JOIN usuarios u ON u.id = t.idUsuario WHERE t.numRelacion = ?';

        return $this->unTemporal($sql, array((int) $numRelacion));
    }

    public function guardar($temporal, $idUsuario)
    {
        // @ Objetivo
        // Guardar lo que se prepara, sin que rija. La primera vez crea el temporal; después lo
        // actualiza, si nadie lo guardó desde que la pantalla lo leyó. Para el primer guardado de
        // un cambio se lee la relación con bloqueo, como en la baja: si se da de baja a la vez,
        // uno espera al otro. No se exige la forma completa, pero sí líneas legibles, de
        // artículos que existan y sin repetir.
        // @ Parametros
        //      $temporal -> (array) id (0 la primera vez), numRelacion (0 si la relación es nueva),
        //                   idTipo, nombre, descripcion, diasViabilidad, origenAjuste, lineas y
        //                   fechaModificacion, la que leyó la pantalla.
        //      $idUsuario -> (int) usuario de sesión.
        // @ Devolvemos
        //      ['datos'] con el id del temporal, su fechaInicio y su fechaModificacion, o ['error'].
        $id = isset($temporal['id']) ? (int) $temporal['id'] : 0;
        $lineas = $this->lineasLegibles(isset($temporal['lineas']) ? $temporal['lineas'] : null);
        $datos = $this->datosLegibles($temporal);

        $motivo = '';
        if ($idUsuario <= 0) {
            $motivo = 'Hace falta un usuario identificado para guardar una relación: vuelve a entrar en TPVFox con el tuyo.';
        } elseif (is_string($lineas)) {
            $motivo = $lineas;
        } elseif ($datos === null) {
            $motivo = 'La vida útil tiene que ser un número entero de días: corrígela o déjala vacía.';
        } else {
            $motivo = $this->motivoPorArticulos($lineas);
        }
        if ($motivo !== '') {
            return array('error' => $this->montarAdvertencia('warning', $motivo));
        }

        $ahora = date('Y-m-d H:i:s');
        $this->iniciarEscritura();
        if ($id > 0) {
            $respuesta = $this->actualizar($id, $temporal, $datos, $lineas, $idUsuario, $ahora);
        } else {
            $respuesta = $this->crear($temporal, $datos, $lineas, $idUsuario, $ahora);
        }

        return $this->terminarEscritura($respuesta);
    }

    public function cancelar($idTemporal)
    {
        // @ Objetivo
        // Borrar lo que se preparaba. Lo que rige no cambia.
        // @ Parametros
        //      $idTemporal -> (int) id del temporal.
        // @ Devolvemos
        //      ['datos'] con el id borrado, o ['error'] si ya no existe.
        $this->iniciarEscritura();
        $respuesta = $this->consultaPreparada('DELETE FROM relacionesTemporales WHERE id = ?', array((int) $idTemporal));
        if (!isset($respuesta['error'])) {
            if ($respuesta['datos']['afectadas'] !== 1) {
                $respuesta = array('error' => $this->montarAdvertencia('warning', 'El cambio en preparación ya no existe: alguien lo canceló o lo activó. Vuelve al listado para ver cómo está la relación.'));
            } else {
                $respuesta = array('datos' => array('id' => (int) $idTemporal));
            }
        }

        return $this->terminarEscritura($respuesta);
    }

    public function activar($idTemporal, $idUsuario, $relaciones)
    {
        // @ Objetivo
        // Poner a regir lo preparado y borrar el temporal, en una sola escritura: o rige y el
        // temporal desaparece, o no cambia nada y el temporal sigue. El temporal se lee
        // bloqueado: otra activación del mismo espera y después ya no lo encuentra. Un cambio
        // solo se activa si la versión de la que partió sigue rigiendo.
        // @ Parametros
        //      $idTemporal -> (int) id del temporal.
        //      $idUsuario -> (int) usuario de sesión: queda como autor de la versión.
        //      $relaciones -> (Relaciones) la clase que escribe las relaciones.
        // @ Devolvemos
        //      ['datos'] con numRelacion, version y tipoCambio, o ['error'].
        if ($idUsuario <= 0) {
            return array('error' => $this->montarAdvertencia('warning', 'Hace falta un usuario identificado para activar una relación: vuelve a entrar en TPVFox con el tuyo.'));
        }

        $this->iniciarEscritura();
        $respuesta = $this->unTemporal('SELECT * FROM relacionesTemporales WHERE id = ? FOR UPDATE', array((int) $idTemporal));
        if (!isset($respuesta['error']) && $respuesta['datos'] === null) {
            $respuesta = array('error' => $this->montarAdvertencia('warning', 'El cambio en preparación ya no existe: alguien lo canceló o lo activó. Vuelve al listado para ver cómo está la relación.'));
        }

        if (!isset($respuesta['error'])) {
            $temporal = $respuesta['datos'];
            $datos = array(
                'nombre' => $temporal['nombre'],
                'descripcion' => $temporal['descripcion'],
                'diasViabilidad' => $temporal['diasViabilidad'],
            );
            if ($temporal['numRelacion'] === null) {
                $respuesta = $relaciones->alta($temporal['idTipo'], $datos, $temporal['lineas'], $idUsuario);
            } else {
                $respuesta = $this->comprobarPartida($temporal);
                if (!isset($respuesta['error'])) {
                    $respuesta = $relaciones->modificar($temporal['numRelacion'], $datos, $temporal['lineas'], $idUsuario, $temporal['origenAjuste']);
                }
            }
        }

        if (!isset($respuesta['error'])) {
            $activada = $respuesta['datos'];
            $respuesta = $this->consultaPreparada('DELETE FROM relacionesTemporales WHERE id = ?', array((int) $idTemporal));
            if (!isset($respuesta['error']) && $respuesta['datos']['afectadas'] !== 1) {
                $respuesta = array('error' => $this->montarAdvertencia('warning', 'El cambio en preparación ya no existe: alguien lo canceló o lo activó. Vuelve al listado para ver cómo está la relación.'));
            }
            if (!isset($respuesta['error'])) {
                $respuesta = array('datos' => array(
                    'numRelacion' => (int) $activada['numRelacion'],
                    'version' => $activada['version'],
                    'tipoCambio' => $activada['tipoCambio'],
                ));
            }
        }

        return $this->terminarEscritura($respuesta);
    }

    public function listarAbiertos()
    {
        // @ Objetivo
        // Los cambios en preparación, para retomarlos desde el listado: de qué relación son,
        // quién los guardó por última vez y cuándo. Los más recientes, primero.
        // @ Devolvemos
        //      ['datos'] con id, numRelacion (null si la relación es nueva), nombre, tipo,
        //      usuario y fechaModificacion de cada uno; o ['error'].
        $sql = 'SELECT t.id, t.numRelacion, t.nombre, ti.nombre AS tipo, u.username AS usuario, t.fechaModificacion'
            . ' FROM relacionesTemporales t'
            . ' LEFT JOIN relacionesTipos ti ON ti.id = t.idTipo'
            . ' LEFT JOIN usuarios u ON u.id = t.idUsuario'
            . ' ORDER BY t.fechaModificacion DESC, t.id DESC';

        return $this->consultaPreparada($sql);
    }

    private function crear($temporal, $datos, $lineas, $idUsuario, $ahora)
    {
        // @ Objetivo
        // El primer guardado. De una relación nueva, sin número; de un cambio, con el número y
        // la versión que rige, leída con la relación bloqueada.
        // @ Devolvemos
        //      ['datos'] con id y fechaModificacion, o ['error'].
        $numRelacion = isset($temporal['numRelacion']) ? (int) $temporal['numRelacion'] : 0;
        $idTipo = isset($temporal['idTipo']) ? (int) $temporal['idTipo'] : 0;
        $idRelacion = null;
        $respuesta = array('datos' => array());

        if ($numRelacion > 0) {
            $sql = 'SELECT id, idTipo, estado FROM relaciones WHERE numRelacion = ? FOR UPDATE';
            $respuesta = $this->consultaPreparada($sql, array($numRelacion));
            if (!isset($respuesta['error'])) {
                foreach ($respuesta['datos'] as $version) {
                    if ($version['estado'] === 'Activo') {
                        $idRelacion = (int) $version['id'];
                        $idTipo = (int) $version['idTipo'];
                    }
                }
                if ($idRelacion === null) {
                    $respuesta = array('error' => $this->montarAdvertencia('warning', 'La relación no está vigente: está de baja o se sustituyó, y ya no se puede cambiar.'));
                }
            }
            if (!isset($respuesta['error'])) {
                $respuesta = $this->avisoSiYaHayTemporal($numRelacion);
            }
        } else {
            $numRelacion = null;
            if ($idTipo <= 0) {
                $respuesta = array('error' => $this->montarAdvertencia('warning', 'Elige el tipo de relación antes de guardar.'));
            }
        }

        if (!isset($respuesta['error'])) {
            $sql = 'INSERT INTO relacionesTemporales (numRelacion, idRelacion, idTipo, nombre, descripcion, diasViabilidad,'
                . ' origenAjuste, lineas, idUsuario, fechaInicio, fechaModificacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $valores = array(
                $numRelacion,
                $idRelacion,
                $idTipo,
                $datos['nombre'],
                $datos['descripcion'],
                $datos['diasViabilidad'],
                $datos['origenAjuste'],
                json_encode($lineas),
                $idUsuario,
                $ahora,
                $ahora,
            );
            $respuesta = $this->consultaPreparada($sql, $valores);
            if (!isset($respuesta['error'])) {
                $respuesta = array('datos' => array('id' => (int) $respuesta['datos']['idInsertado'], 'fechaInicio' => $ahora, 'fechaModificacion' => $ahora));
            }
        }

        return $respuesta;
    }

    private function actualizar($id, $temporal, $datos, $lineas, $idUsuario, $ahora)
    {
        // @ Objetivo
        // Un guardado sobre un temporal que ya existe, si nadie lo guardó desde que la pantalla
        // lo leyó. El tipo solo cambia en una relación nueva: el de una que rige es el suyo.
        // @ Devolvemos
        //      ['datos'] con id y fechaModificacion, o ['error'].
        $sql = 'SELECT id, fechaInicio, fechaModificacion FROM relacionesTemporales WHERE id = ? FOR UPDATE';
        $respuesta = $this->consultaPreparada($sql, array($id));
        $fechaInicio = '';
        if (!isset($respuesta['error'])) {
            $leido = isset($temporal['fechaModificacion']) ? (string) $temporal['fechaModificacion'] : '';
            if (count($respuesta['datos']) === 0) {
                $respuesta = array('error' => $this->montarAdvertencia('warning', 'El cambio en preparación ya no existe: alguien lo canceló o lo activó. Vuelve al listado para ver cómo está la relación.'));
            } elseif ($respuesta['datos'][0]['fechaModificacion'] !== $leido) {
                $respuesta = array('error' => $this->montarAdvertencia('warning', 'Otra persona guardó este cambio después de que lo abrieras: vuelve a abrirlo para ver lo que guardó.'));
            } else {
                $fechaInicio = $respuesta['datos'][0]['fechaInicio'];
            }
        }

        if (!isset($respuesta['error'])) {
            $sql = 'UPDATE relacionesTemporales SET idTipo = IF(numRelacion IS NULL, ?, idTipo), nombre = ?, descripcion = ?,'
                . ' diasViabilidad = ?, origenAjuste = ?, lineas = ?, idUsuario = ?, fechaModificacion = ? WHERE id = ?';
            $valores = array(
                isset($temporal['idTipo']) ? (int) $temporal['idTipo'] : 0,
                $datos['nombre'],
                $datos['descripcion'],
                $datos['diasViabilidad'],
                $datos['origenAjuste'],
                json_encode($lineas),
                $idUsuario,
                $ahora,
                $id,
            );
            $respuesta = $this->consultaPreparada($sql, $valores);
            if (!isset($respuesta['error'])) {
                $respuesta = array('datos' => array('id' => $id, 'fechaInicio' => $fechaInicio, 'fechaModificacion' => $ahora));
            }
        }

        return $respuesta;
    }

    private function avisoSiYaHayTemporal($numRelacion)
    {
        // @ Objetivo
        // Una relación tiene como mucho un cambio en preparación: si ya lo tiene, se dice de
        // quién es y cuándo se guardó, para que se retome ese.
        // @ Devolvemos
        //      ['datos'] si no hay ninguno, o ['error'] con el aviso.
        $respuesta = $this->deRelacion($numRelacion);
        if (!isset($respuesta['error']) && $respuesta['datos'] !== null) {
            $abierto = $respuesta['datos'];
            $respuesta = array('error' => $this->montarAdvertencia('warning', 'La relación ya tiene un cambio en preparación, guardado por '
                . $abierto['usuario'] . ' el ' . $abierto['fechaModificacion'] . ': ábrelo desde las relaciones abiertas.'));
        }

        return $respuesta;
    }

    private function comprobarPartida($temporal)
    {
        // @ Objetivo
        // Un cambio se preparó sobre una versión: si ya no rige, o ya no existe, activarlo
        // registraría un cambio que nadie hizo así. La relación se lee bloqueada.
        // @ Devolvemos
        //      ['datos'] si sigue rigiendo, o ['error'].
        $sql = 'SELECT id, estado FROM relaciones WHERE numRelacion = ? FOR UPDATE';
        $respuesta = $this->consultaPreparada($sql, array($temporal['numRelacion']));
        if (!isset($respuesta['error'])) {
            $sigueRigiendo = false;
            foreach ($respuesta['datos'] as $version) {
                if ((int) $version['id'] === (int) $temporal['idRelacion'] && $version['estado'] === 'Activo') {
                    $sigueRigiendo = true;
                }
            }
            if (!$sigueRigiendo) {
                $respuesta = array('error' => $this->montarAdvertencia('warning', 'La relación cambió desde que se preparó este cambio: cancélalo y vuelve a prepararlo.'));
            }
        }

        return $respuesta;
    }

    private function unTemporal($sql, $valores)
    {
        // @ Objetivo
        // Leer una fila de temporal y dejar sus líneas como lista.
        // @ Devolvemos
        //      ['datos'] con el temporal, o null si no hay; o ['error'].
        $respuesta = $this->consultaPreparada($sql, $valores);
        if (!isset($respuesta['error'])) {
            $temporal = null;
            if (count($respuesta['datos']) > 0) {
                $temporal = $respuesta['datos'][0];
                $temporal['lineas'] = json_decode($temporal['lineas'], true);
            }
            $respuesta = array('datos' => $temporal);
        }

        return $respuesta;
    }

    private function lineasLegibles($lineas)
    {
        // @ Objetivo
        // Dejar las líneas recibidas en la forma en que se guardan: artículo, sentido, cantidad
        // como texto tal como se escribió, y unidad. Lo vacío pasa a null.
        // @ Devolvemos
        //      (array) las líneas; o (string) el motivo, que nombra la línea que no se puede leer
        //      por su lado y su número, como la pantalla, y dice cómo corregirla.
        if (!is_array($lineas)) {
            return 'Las líneas no llegaron bien: vuelve a abrir la relación y repite el cambio.';
        }
        $legibles = array();
        $posicion = 0;
        $numeroEnSuLado = array('entrada' => 0, 'salida' => 0);
        foreach ($lineas as $linea) {
            $posicion++;
            if (!is_array($linea) || !isset($linea['idArticulo']) || !isset($linea['sentido'])
                || !in_array($linea['sentido'], array('entrada', 'salida'), true)) {
                return 'La línea ' . $posicion . ' no dice si entra o sale: quítala y vuelve a añadir el producto en su lado.';
            }
            $numeroEnSuLado[$linea['sentido']]++;
            $nombre = 'la ' . $linea['sentido'] . ' ' . $numeroEnSuLado[$linea['sentido']];
            $cantidad = isset($linea['cantidad']) ? trim((string) $linea['cantidad']) : '';
            $unidad = isset($linea['unidad']) ? (string) $linea['unidad'] : '';
            if ((string) $linea['idArticulo'] === '' || !ctype_digit((string) $linea['idArticulo'])) {
                return 'En ' . $nombre . ' no hay un artículo válido: quítala y vuelve a buscar el producto.';
            }
            if ($cantidad !== '' && preg_match('/^[0-9]+(\.[0-9]+)?$/', $cantidad) !== 1) {
                return 'La cantidad de ' . $nombre . ' no es un número: escríbela con cifras, y punto o coma para los decimales.';
            }
            if (!in_array($unidad, array('', 'kg', 'ud'), true)) {
                return 'La unidad de ' . $nombre . ' no es kg ni ud: elígela en su línea.';
            }
            $legibles[] = array(
                'idArticulo' => (int) $linea['idArticulo'],
                'sentido' => $linea['sentido'],
                'cantidad' => $cantidad === '' ? null : $cantidad,
                'unidad' => $unidad === '' ? null : $unidad,
            );
        }

        return $legibles;
    }

    private function datosLegibles($temporal)
    {
        // @ Objetivo
        // Los datos de la relación tal como se guardan: lo vacío pasa a null, y la vida útil es
        // un número de días.
        // @ Devolvemos
        //      (array) nombre, descripcion, diasViabilidad y origenAjuste; o null si la vida útil
        //      no es un número.
        $datos = array();
        foreach (array('nombre', 'descripcion', 'diasViabilidad', 'origenAjuste') as $campo) {
            $valor = isset($temporal[$campo]) ? trim((string) $temporal[$campo]) : '';
            $datos[$campo] = $valor === '' ? null : $valor;
        }
        if ($datos['diasViabilidad'] !== null && !ctype_digit($datos['diasViabilidad'])) {
            return null;
        }

        return $datos;
    }

    private function motivoPorArticulos($lineas)
    {
        // @ Objetivo
        // Que cada artículo de las líneas exista y no se repita.
        // @ Devolvemos
        //      (string) el motivo del rechazo, o '' si son correctos.
        $motivo = '';
        $vistos = array();
        foreach ($lineas as $linea) {
            if ($motivo === '') {
                if (in_array($linea['idArticulo'], $vistos)) {
                    $motivo = 'El artículo ' . $linea['idArticulo'] . ' está repetido en la relación: quita una de sus líneas.';
                } else {
                    $vistos[] = $linea['idArticulo'];
                    $existe = $this->consultaPreparada('SELECT COUNT(*) AS cuantos FROM articulos WHERE idArticulo = ?', array($linea['idArticulo']));
                    if (isset($existe['error']) || $existe['datos'][0]['cuantos'] == 0) {
                        $motivo = 'El artículo ' . $linea['idArticulo'] . ' no existe en el catálogo: quítalo de la relación.';
                    }
                }
            }
        }

        return $motivo;
    }
}
