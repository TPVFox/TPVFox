<?php

/**
 * Capa de acceso a datos segura.
 *
 * ÚNICA puerta por la que debe pasar el acceso a la base de datos. Todo va por
 * sentencias preparadas: los VALORES nunca se concatenan en el SQL, así que la
 * inyección SQL es imposible por construcción.
 *
 * - Consultas a medida (con JOINs, etc.): usar select()/execute() con `?` y un
 *   array de parámetros.
 * - Casos simples: insert()/update()/delete()/selectWhere() con arrays.
 * - Identificadores (tabla/columna, que NO se pueden parametrizar): se validan
 *   con ident(); si vienen de fuera (ORDER BY, campo de búsqueda), con
 *   identWhitelist() contra una lista blanca.
 */
class DB
{
    /** @var mysqli */
    private $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /** SELECT parametrizado. Devuelve un array de filas asociativas. */
    public function select(string $sql, array $params = []): array
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params === [] ? null : array_values($params));
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Consulta parametrizada que devuelve el mysqli_result (o false).
     * Reemplazo directo (drop-in) de `$conn->query($sqlConcatenado)`: se cambia
     * por `$db->pquery($sqlCon?, [$valores])` y el código que consume el
     * resultado (fetch_assoc, num_rows, fetch_object...) sigue igual.
     */
    public function pquery(string $sql, array $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params === [] ? null : array_values($params));
        return $stmt->get_result();
    }

    /** INSERT/UPDATE/DELETE parametrizado. Devuelve el nº de filas afectadas. */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params === [] ? null : array_values($params));
        return $stmt->affected_rows;
    }

    /** INSERT desde un array columna=>valor. Devuelve el insert_id. */
    public function insert(string $tabla, array $datos): int
    {
        $cols = array_map([self::class, 'ident'], array_keys($datos));
        $placeholders = implode(', ', array_fill(0, count($datos), '?'));
        $sql = 'INSERT INTO ' . self::ident($tabla)
            . ' (' . implode(', ', $cols) . ') VALUES (' . $placeholders . ')';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(array_values($datos));
        return (int) $this->conn->insert_id;
    }

    /** UPDATE con datos y condiciones como arrays columna=>valor. */
    public function update(string $tabla, array $datos, array $condiciones): int
    {
        $set = array_map(function ($c) {
            return self::ident($c) . ' = ?';
        }, array_keys($datos));
        [$where, $wparams] = self::buildWhere($condiciones);
        $sql = 'UPDATE ' . self::ident($tabla) . ' SET ' . implode(', ', $set)
            . ($where !== '' ? ' WHERE ' . $where : '');
        return $this->execute($sql, array_merge(array_values($datos), $wparams));
    }

    /** DELETE con condiciones como array columna=>valor. */
    public function delete(string $tabla, array $condiciones): int
    {
        [$where, $wparams] = self::buildWhere($condiciones);
        $sql = 'DELETE FROM ' . self::ident($tabla)
            . ($where !== '' ? ' WHERE ' . $where : '');
        return $this->execute($sql, $wparams);
    }

    /** SELECT * FROM tabla WHERE (condiciones AND ...). */
    public function selectWhere(string $tabla, array $condiciones = []): array
    {
        [$where, $wparams] = self::buildWhere($condiciones);
        $sql = 'SELECT * FROM ' . self::ident($tabla)
            . ($where !== '' ? ' WHERE ' . $where : '');
        return $this->select($sql, $wparams);
    }

    /**
     * Valida y entrecomilla un identificador (tabla o columna). Los
     * identificadores no se pueden parametrizar, así que se restringen a
     * `nombre` o `nombre` con backticks; cualquier otra cosa es un error.
     */
    public static function ident(string $nombre): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $nombre)) {
            throw new InvalidArgumentException("Identificador no válido: $nombre");
        }
        return '`' . $nombre . '`';
    }

    /**
     * Identificador cuyo valor viene de fuera (ORDER BY, campo de búsqueda):
     * debe estar en la lista blanca de permitidos.
     */
    public static function identWhitelist(string $nombre, array $permitidos): string
    {
        if (!in_array($nombre, $permitidos, true)) {
            throw new InvalidArgumentException("Identificador no permitido: $nombre");
        }
        return self::ident($nombre);
    }

    /** Construye "col1 = ? AND col2 = ?" + [valores] desde un array asociativo. */
    private static function buildWhere(array $condiciones): array
    {
        if ($condiciones === []) {
            return ['', []];
        }
        $parts = [];
        $params = [];
        foreach ($condiciones as $col => $val) {
            $parts[] = self::ident((string) $col) . ' = ?';
            $params[] = $val;
        }
        return [implode(' AND ', $parts), $params];
    }
}
