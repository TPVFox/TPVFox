<?php

include_once $RutaServidor . $HostNombre . '/clases/ClaseTFModelo.php';
include_once $URLCom . '/clases/traits/MontarAdvertenciaTrait.php';

// @ Objetivo
// Base de las clases del módulo de relaciones entre productos. Reúne lo que todas
// necesitan antes de leer o escribir: saber si las tablas del módulo están instaladas.
//
// El código del módulo y el fichero que crea sus tablas llegan a una instalación por
// caminos distintos, así que ninguna clase da por hecho que las tablas existen: lo
// pregunta aquí. Esta clase no crea ni repara tablas; eso es del fichero de instalación.
class RelacionesModelo extends TFModelo
{
    use MontarAdvertenciaTrait;

    // Las columnas que este código espera de cada tabla. Si la base tiene otras, de más o
    // de menos, las tablas son de otra versión y el módulo no opera sobre ellas.
    const ESTRUCTURA = array(
        'relacionesTipos' => array('id', 'nombre', 'forma', 'llevaCantidad', 'estado'),
        'relaciones' => array(
            'id', 'numRelacion', 'idTipo', 'nombre', 'descripcion', 'diasViabilidad', 'version',
            'tipoCambio', 'origenAjuste', 'idUsuario', 'fechaCreacion', 'estado',
        ),
        'relacionesLineas' => array('id', 'idRelacion', 'idArticulo', 'sentido', 'cantidad', 'unidad', 'estado'),
    );

    // Cuántas operaciones del módulo hay en curso dentro de la transacción abierta, y si
    // alguna ha fallado. Son estáticas porque la conexión también lo es: la transacción
    // pertenece a la conexión, no a la instancia que la abrió.
    private static $operacionesAbiertas = 0;
    private static $algunaFallo = false;

    protected function consultaPreparada($sql, $valores = array())
    {
        // @ Objetivo
        // Ejecutar una sentencia con sus valores aparte: ningún valor recibido forma parte
        // del texto de la sentencia. Toda lectura y toda escritura del módulo pasa por aquí.
        // @ Parametros
        //      $sql -> (string) la sentencia, con un ? por cada valor.
        //      $valores -> (array) los valores, en el orden de los ?.
        // @ Devolvemos
        //      array con ['datos']: las filas, si la sentencia devuelve filas; y si no,
        //          ['afectadas'] y ['idInsertado'].
        //      o con ['error'] y ['consulta'] si falló. El fallo no se lanza ni se imprime:
        //          se devuelve, y su rastro queda en el registro del servidor.
        try {
            $conexion = $this->conexionBDTPV();
            $sentencia = $conexion->prepare($sql);
            if ($sentencia === false) {
                throw new \RuntimeException($conexion->error);
            }
            if (count($valores) > 0) {
                $tipos = '';
                foreach ($valores as $valor) {
                    $tipos .= is_int($valor) ? 'i' : (is_float($valor) ? 'd' : 's');
                }
                $sentencia->bind_param($tipos, ...$valores);
            }
            if ($sentencia->execute() === false) {
                throw new \RuntimeException($sentencia->error);
            }
            $resultado = $sentencia->get_result();
            if ($resultado === false) {
                $datos = array('afectadas' => $sentencia->affected_rows, 'idInsertado' => $sentencia->insert_id);
            } else {
                $datos = $resultado->fetch_all(MYSQLI_ASSOC);
            }
            $sentencia->close();
        } catch (\Throwable $e) {
            return $this->fallo('Error en la base de datos: ' . $e->getMessage(), $sql, $e);
        }

        return array('datos' => $datos);
    }

    protected function enTransaccion(callable $pasos)
    {
        // @ Objetivo
        // Ejecutar una escritura de varios pasos de modo que quede entera o no quede.
        // Una operación puede llamar a otra que también usa este método: solo la más
        // externa abre la transacción y decide si se confirma; las de dentro se suman a
        // ella. Si cualquiera falla, lanzando o devolviendo ['error'], se deshace todo.
        // @ Parametros
        //      $pasos -> función sin argumentos que ejecuta los pasos y devuelve su respuesta.
        // @ Devolvemos
        //      La respuesta de $pasos, o un array con ['error'] si algo falló.
        $externa = (self::$operacionesAbiertas === 0);
        try {
            $conexion = $this->conexionBDTPV();
            if ($externa) {
                self::$algunaFallo = false;
                $conexion->begin_transaction();
            }
        } catch (\Throwable $e) {
            return $this->fallo('No se pudo iniciar la escritura.', '', $e);
        }

        self::$operacionesAbiertas++;
        try {
            $respuesta = $pasos();
        } catch (\Throwable $e) {
            $respuesta = $this->fallo('La escritura no se pudo completar.', '', $e);
        }
        self::$operacionesAbiertas--;

        if (isset($respuesta['error'])) {
            self::$algunaFallo = true;
        }
        if (!$externa) {
            return $respuesta;
        }

        try {
            if (self::$algunaFallo) {
                $conexion->rollback();
                if (!isset($respuesta['error'])) {
                    $respuesta = $this->fallo('La escritura no se pudo completar: falló uno de sus pasos.', '');
                }
            } else {
                $conexion->commit();
            }
        } catch (\Throwable $e) {
            $respuesta = $this->fallo('La escritura no se pudo confirmar.', '', $e);
        }

        return $respuesta;
    }

    private function fallo($mensaje, $sql, $causa = null)
    {
        // @ Objetivo
        // Componer la respuesta de un fallo y apuntar su rastro en el registro del servidor.
        // @ Devolvemos
        //      array con ['error'] (advertencia con su tipo) y ['consulta'].
        if ($causa !== null) {
            error_log('Relaciones: ' . $mensaje . ' ' . $causa);
        }

        return array(
            'error' => $this->montarAdvertencia('danger', $mensaje),
            'consulta' => $sql,
        );
    }

    public function estadoEstructura()
    {
        // @ Objetivo
        // Decir en qué estado están las tablas del módulo en esta instalación.
        // @ Devolvemos
        //      array con ['datos'], uno de estos cuatro valores:
        //          'ausente'      no existe ninguna de las tres tablas.
        //          'incompleta'   existe alguna, pero no las tres.
        //          'otraVersion'  existen las tres, con columnas distintas de las esperadas.
        //          'instalada'    existen las tres con las columnas esperadas.
        //      o con ['error'] y ['consulta'] si no se pudo leer la base.
        $sql = 'SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS'
            . ' WHERE TABLE_SCHEMA = DATABASE()'
            . " AND TABLE_NAME IN ('" . implode("', '", array_keys(self::ESTRUCTURA)) . "')";

        $columnas = array();
        try {
            $resultado = $this->conexionBDTPV()->query($sql);
            while ($fila = $resultado->fetch_assoc()) {
                $columnas[$fila['TABLE_NAME']][] = $fila['COLUMN_NAME'];
            }
        } catch (\Throwable $e) {
            error_log('Relaciones: no se pudo leer el estado de las tablas: ' . $e);
            return array(
                'error' => $this->montarAdvertencia('danger', 'No se pudo comprobar la instalación de las relaciones entre productos.'),
                'consulta' => $sql,
            );
        }

        if (count($columnas) === 0) {
            return array('datos' => 'ausente');
        }
        if (count($columnas) < count(self::ESTRUCTURA)) {
            return array('datos' => 'incompleta');
        }
        foreach (self::ESTRUCTURA as $tabla => $esperadas) {
            $presentes = $columnas[$tabla];
            sort($presentes);
            sort($esperadas);
            if ($presentes !== $esperadas) {
                return array('datos' => 'otraVersion');
            }
        }

        return array('datos' => 'instalada');
    }
}
