<?php

declare(strict_types=1);

namespace Tpvfox\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Caracterización de la capa de datos (claseModeloP). Fija el SQL que genera
 * HOY, para detectar cambios inesperados al parametrizar/escapar.
 *
 * Se usa reflexión (los constructores de SQL son `protected static`) con
 * `$soloSQL = true`, que devuelve el SQL SIN ejecutarlo: no toca la BD.
 *
 * NOTA: algunos tests documentan a propósito el comportamiento VULNERABLE
 * actual (interpolación sin escapado). Cuando se corrija la SQLi se actualizarán
 * para exigir el comportamiento seguro — sirven de red de seguridad y de test
 * rojo del arreglo.
 */
final class ModeloSqlTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        \tpvfox_load_datalayer();
    }

    /** Invoca un método protegido estático de la capa de datos con soloSQL=true. */
    private function sql(string $metodo, array $args): string
    {
        $m = new ReflectionMethod('Modelo', $metodo);
        $m->setAccessible(true);
        $m->invokeArgs(null, [...$args, true]); // último arg: $soloSQL = true
        return \Modelo::getSQLConsulta();
    }

    public function test_insert_genera_sql_por_concatenacion(): void
    {
        $sql = $this->sql('_insert', ['productos', ['articulo_name' => 'Cafe', 'precio' => '1.50']]);
        $this->assertSame(
            "INSERT productos SET articulo_name = 'Cafe', precio = '1.50'",
            $sql
        );
    }

    public function test_update_genera_set_y_where(): void
    {
        $sql = $this->sql('_update', ['productos', ['precio' => '2.00'], ['id=5']]);
        $this->assertSame("UPDATE productos SET precio = '2.00' WHERE id=5", $sql);
    }

    public function test_delete_genera_where(): void
    {
        $sql = $this->sql('_delete', ['productos', ['id=5']]);
        $this->assertSame('DELETE FROM productos WHERE id=5', $sql);
    }

    /**
     * SEGURIDAD (SQLi): los valores interpolados deben ir ESCAPADOS, de modo que
     * una comilla en el valor no pueda cerrar el literal e inyectar SQL.
     */
    public function test_insert_escapa_comillas_en_valores(): void
    {
        $sql = $this->sql('_insert', ['usuarios', ['username' => "a' OR '1'='1"]]);

        // La comilla del valor va escapada (\'), así que no cierra el literal.
        $this->assertStringContainsString("\\'", $sql, 'El valor debe ir escapado');
        // Y NO aparece la forma sin escapar que rompería la consulta.
        $this->assertStringNotContainsString("username = 'a' OR '1'='1'", $sql);
    }
}
