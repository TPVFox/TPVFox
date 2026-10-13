<?php

include_once $RutaServidor . $HostNombre . '/clases/ClaseTFModelo.php';
include_once $URLCom . '/clases/traits/MontarAdvertenciaTrait.php';

// @ Objetivo
// Base de las clases del módulo de relaciones entre productos: comprueba que sus tablas
// están instaladas, consulta con los valores aparte de la sentencia y escribe de modo que
// una escritura de varios pasos quede entera o no quede.
class RelacionesModelo extends TFModelo
{
    use MontarAdvertenciaTrait;

    // Columnas que este código espera de cada tabla del módulo.
    const ESTRUCTURA = array(
        'relacionesTipos' => array('id', 'nombre', 'forma', 'llevaCantidad', 'estado'),
        'relaciones' => array(
            'id', 'numRelacion', 'idTipo', 'nombre', 'descripcion', 'diasViabilidad', 'version',
            'tipoCambio', 'origenAjuste', 'idUsuario', 'fechaCreacion', 'estado',
        ),
        'relacionesLineas' => array('id', 'idRelacion', 'idArticulo', 'sentido', 'cantidad', 'unidad', 'estado'),
    );

    // Son estáticas porque la transacción es de la conexión, y la conexión es una sola
    // para todas las clases del módulo.
    private static $escriturasAbiertas = 0;
    private static $algunaFallo = false;

    protected function consultaPreparada($sql, $valores = array())
    {
        // @ Objetivo
        // Ejecutar una sentencia con sus valores aparte del texto.
        // @ Parametros
        //      $sql -> (string) la sentencia, con un ? por cada valor.
        //      $valores -> (array) los valores, en el orden de los ?.
        // @ Devolvemos
        //      ['datos'] con las filas; o con ['afectadas'] e ['idInsertado'] si no devuelve filas.
        //      ['error'] y ['consulta'] si falla.
        $respuesta = array();
        try {
            $conexion = $this->conexionBDTPV();
            $sentencia = $conexion->prepare($sql);
            if ($sentencia === false) {
                throw new \Exception($conexion->error);
            }
            if (count($valores) > 0) {
                // 's' es texto: la base convierte cada valor al tipo de su columna.
                $tipos = str_repeat('s', count($valores));
                $sentencia->bind_param($tipos, ...$valores);
            }
            if ($sentencia->execute() === false) {
                throw new \Exception($sentencia->error);
            }

            $resultado = $sentencia->get_result();
            if ($resultado === false) {
                $respuesta['datos'] = array('afectadas' => $sentencia->affected_rows, 'idInsertado' => $sentencia->insert_id);
            } else {
                $respuesta['datos'] = $resultado->fetch_all(MYSQLI_ASSOC);
            }
            $sentencia->close();
        } catch (\Throwable $e) {
            // En PHP 8 la base avisa de un fallo con una excepción.
            error_log('Relaciones: ' . $e->getMessage() . ' Sentencia: ' . $sql);
            $respuesta = array(
                'error' => $this->montarAdvertencia('danger', 'Error en la base de datos: ' . $e->getMessage()),
                'consulta' => $sql,
            );
        }

        return $respuesta;
    }

    protected function iniciarEscritura()
    {
        // @ Objetivo
        // Empezar una escritura de varios pasos. Solo la primera abre la transacción; las
        // que se inician dentro de otra se suman a ella. Se cierra con terminarEscritura().
        self::$escriturasAbiertas++;
        if (self::$escriturasAbiertas === 1) {
            self::$algunaFallo = false;
            try {
                $this->conexionBDTPV()->begin_transaction();
            } catch (\Throwable $e) {
                error_log('Relaciones: no se pudo iniciar la escritura. ' . $e->getMessage());
                self::$algunaFallo = true;
            }
        }
    }

    protected function terminarEscritura($respuesta)
    {
        // @ Objetivo
        // Cerrar la escritura: la más externa confirma si ningún paso falló y deshace si
        // falló alguno.
        // @ Parametros
        //      $respuesta -> (array) la respuesta de la operación: ['datos'] o ['error'].
        // @ Devolvemos
        //      La misma respuesta, o ['error'] y ['consulta'] si hubo que deshacer.
        if (isset($respuesta['error'])) {
            self::$algunaFallo = true;
        }
        self::$escriturasAbiertas--;

        if (self::$escriturasAbiertas === 0) {
            try {
                if (self::$algunaFallo) {
                    $this->conexionBDTPV()->rollback();
                } else {
                    $this->conexionBDTPV()->commit();
                }
            } catch (\Throwable $e) {
                error_log('Relaciones: no se pudo cerrar la escritura. ' . $e->getMessage());
                self::$algunaFallo = true;
            }

            if (self::$algunaFallo && !isset($respuesta['error'])) {
                $respuesta = array(
                    'error' => $this->montarAdvertencia('danger', 'La escritura no se pudo completar.'),
                    'consulta' => '',
                );
            }
        }

        return $respuesta;
    }

    public function estadoEstructura()
    {
        // @ Objetivo
        // Decir en qué estado están las tablas del módulo en esta instalación.
        // @ Devolvemos
        //      ['datos'] con uno de estos valores:
        //          'ausente'      no existe ninguna de las tres tablas.
        //          'incompleta'   existe alguna, pero no las tres.
        //          'otraVersion'  existen las tres, con columnas distintas de las esperadas.
        //          'instalada'    existen las tres con las columnas esperadas.
        //      ['error'] y ['consulta'] si no se pudo leer la base.
        $sql = "SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS"
            . " WHERE TABLE_SCHEMA = DATABASE()"
            . " AND TABLE_NAME IN ('relacionesTipos', 'relaciones', 'relacionesLineas')";
        $respuesta = $this->consultaPreparada($sql);

        if (!isset($respuesta['error'])) {
            $columnasEnLaBase = array();
            foreach ($respuesta['datos'] as $fila) {
                $columnasEnLaBase[$fila['TABLE_NAME']][] = $fila['COLUMN_NAME'];
            }

            $tablasQueExisten = count($columnasEnLaBase);
            if ($tablasQueExisten === 0) {
                $estado = 'ausente';
            } elseif ($tablasQueExisten < 3) {
                $estado = 'incompleta';
            } else {
                $estado = 'instalada';
                // El orden de las columnas no cuenta: se comparan ordenadas.
                foreach (self::ESTRUCTURA as $tabla => $columnasEsperadas) {
                    $columnasDeLaTabla = $columnasEnLaBase[$tabla];
                    sort($columnasDeLaTabla);
                    sort($columnasEsperadas);
                    if ($columnasDeLaTabla !== $columnasEsperadas) {
                        $estado = 'otraVersion';
                    }
                }
            }
            $respuesta['datos'] = $estado;
        }

        return $respuesta;
    }
}
