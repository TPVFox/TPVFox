<?php

declare(strict_types=1);

namespace Tpvfox\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Caracterización de la búsqueda de clientes (clase Cliente) contra la BD de
 * test. Fija que la búsqueda por id y por nombre/razón social devuelve lo
 * esperado; esta funcionalidad NO debe cambiar cuando se cierre la SQLi de la
 * búsqueda parametrizando las consultas.
 *
 * Datos sembrados dentro de una transacción que se revierte en tearDown.
 */
final class BusquedaClienteTest extends TestCase
{
    private \mysqli $db;
    private int $cafeId;

    public static function setUpBeforeClass(): void
    {
        \tpvfox_load_datalayer();
        require_once TPVFOX_ROOT . '/clases/cliente.php';
    }

    protected function setUp(): void
    {
        $this->db = \Modelo::getDbo();
        $this->db->begin_transaction();

        $this->db->query(
            "INSERT INTO clientes (Nombre, razonsocial, estado) VALUES ('ZZZ Cafe Central', 'Cafe Central SL', 'activo')"
        );
        $this->cafeId = (int) $this->db->insert_id;
        $this->db->query(
            "INSERT INTO clientes (Nombre, razonsocial, estado) VALUES ('ZZZ Panaderia Sol', 'Panaderia Sol SL', 'activo')"
        );
    }

    protected function tearDown(): void
    {
        $this->db->rollback();
    }

    public function test_datos_cliente_por_id(): void
    {
        $cliente = new \Cliente($this->db);
        $row = $cliente->DatosClientePorId($this->cafeId);

        $this->assertSame('ZZZ Cafe Central', $row['Nombre']);
        $this->assertSame('Cafe Central SL', $row['razonsocial']);
    }

    public function test_buscar_por_nombre_encuentra_coincidencias(): void
    {
        $cliente = new \Cliente($this->db);
        $res = $cliente->BuscarClientePorNombre('Cafe');

        $nombres = array_column($res['datos'], 'Nombre');
        $this->assertContains('ZZZ Cafe Central', $nombres);
        $this->assertNotContains('ZZZ Panaderia Sol', $nombres);
    }

    public function test_buscar_sin_coincidencias_devuelve_vacio(): void
    {
        $cliente = new \Cliente($this->db);
        $res = $cliente->BuscarClientePorNombre('NoExisteXYZ123');

        $this->assertSame([], $res['datos']);
    }

    /**
     * SEGURIDAD (SQLi): un término con comillas/metacaracteres debe tratarse
     * como literal. Sin parametrizar, rompe el SQL (o inyecta); parametrizado,
     * simplemente no encuentra nada.
     */
    public function test_busqueda_no_es_inyectable(): void
    {
        $cliente = new \Cliente($this->db);
        $res = $cliente->BuscarClientePorNombre('nomatch" OR "1"="1');

        $this->assertSame([], $res['datos'] ?? null, 'El término debe tratarse como literal parametrizado');
    }
}
