<?php

declare(strict_types=1);

namespace Tpvfox\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Caracterización + seguridad de la búsqueda de productos de ventas
 * (BuscarProductos, mod_venta). Igual que la del TPV pero además concatenaba
 * $idCliente en el JOIN. Campo restringido a la lista blanca; palabras e
 * idCliente parametrizados.
 */
final class BuscarProductosVentaTest extends TestCase
{
    private \mysqli $conn;

    public static function setUpBeforeClass(): void
    {
        require_once TPVFOX_ROOT . '/clases/DB.php';
        \tpvfox_load_function('modulos/mod_venta/funciones.php', 'BuscarProductos');
    }

    protected function setUp(): void
    {
        $this->conn = \tpvfox_test_mysqli();
        $this->conn->begin_transaction();
        $this->conn->query(
            "INSERT INTO articulos (idArticulo, articulo_name, estado, fecha_creado, ultimoCoste)"
            . " VALUES (900002, 'ZZZ Te Verde', 'activo', NOW(), 0)"
        );
    }

    protected function tearDown(): void
    {
        $this->conn->rollback();
    }

    public function test_busca_por_nombre(): void
    {
        $r = \BuscarProductos('cajaBusqueda', 'a.articulo_name', 'Te Verde', $this->conn, 0);
        $nombres = array_column($r['datos'] ?? [], 'articulo_name');
        $this->assertContains('ZZZ Te Verde', $nombres);
    }

    public function test_campo_no_permitido_se_rechaza(): void
    {
        $r = \BuscarProductos('cajaBusqueda', 'x ; DROP TABLE y', 'Te', $this->conn, 0);
        $this->assertSame('CampoNoValido', $r['Estado'] ?? null);
    }

    public function test_palabra_inyectable_es_literal(): void
    {
        $r = \BuscarProductos('cajaBusqueda', 'a.articulo_name', 'NoExisteXYZ" OR "1"="1', $this->conn, 0);
        $this->assertSame([], $r['datos'] ?? []);
    }
}
