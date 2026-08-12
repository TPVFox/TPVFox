<?php

declare(strict_types=1);

namespace Tpvfox\Tests\Integration;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Caracterización del ciclo CRUD a través de la capa de datos (claseModeloP)
 * contra la BD de test, sobre la tabla `clientes`. Fija que crear→leer→
 * modificar→borrar funciona; esta funcionalidad NO debe cambiar cuando se
 * parametrice el SQL para cerrar la SQLi.
 *
 * Cada test corre dentro de una transacción que se revierte en tearDown, así la
 * BD queda intacta entre tests.
 */
final class ClienteCrudTest extends TestCase
{
    private \mysqli $db;

    public static function setUpBeforeClass(): void
    {
        \tpvfox_load_datalayer();
    }

    protected function setUp(): void
    {
        $this->db = \Modelo::getDbo();
        $this->db->begin_transaction();
    }

    protected function tearDown(): void
    {
        $this->db->rollback();
    }

    /** Invoca un método protegido estático de la capa de datos (ejecutándolo). */
    private function dl(string $metodo, array $args): mixed
    {
        $m = new ReflectionMethod('Modelo', $metodo);
        $m->setAccessible(true);
        return $m->invokeArgs(null, $args);
    }

    public function test_ciclo_crud_completo(): void
    {
        // CREATE
        $id = (int) $this->dl('_insert', ['clientes', ['Nombre' => 'ZZZ Cliente CRUD', 'estado' => 'activo'], false]);
        $this->assertGreaterThan(0, $id);

        // READ
        $rows = $this->dl('_leer', ['clientes', ['idClientes=' . $id], [], [], 0, 0, false]);
        $this->assertCount(1, $rows);
        $this->assertSame('ZZZ Cliente CRUD', $rows[0]['Nombre']);

        // UPDATE
        $this->dl('_update', ['clientes', ['Nombre' => 'ZZZ Cliente MODIFICADO'], ['idClientes=' . $id], false]);
        $rows = $this->dl('_leer', ['clientes', ['idClientes=' . $id], [], [], 0, 0, false]);
        $this->assertSame('ZZZ Cliente MODIFICADO', $rows[0]['Nombre']);

        // DELETE
        $this->dl('_delete', ['clientes', ['idClientes=' . $id], false]);
        $rows = $this->dl('_leer', ['clientes', ['idClientes=' . $id], [], [], 0, 0, false]);
        $this->assertSame([], $rows);
    }
}
