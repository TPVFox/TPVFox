<?php

declare(strict_types=1);

namespace Tpvfox\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Caracterización + seguridad de la búsqueda de productos del TPV
 * (BuscarProductos, mod_tpv). El campo de búsqueda ($campoAbuscar) venía de
 * $_POST y se concatenaba: SQLi. Debe restringirse a las columnas permitidas
 * (ac.codBarras / at.crefTienda / a.articulo_name) y parametrizar las palabras.
 *
 * La función vive en un fichero con includes de efectos colaterales; se extrae
 * aislada con tpvfox_load_function.
 */
final class BuscarProductosTpvTest extends TestCase
{
    private \mysqli $conn;

    public static function setUpBeforeClass(): void
    {
        require_once TPVFOX_ROOT . '/clases/DB.php';
        \tpvfox_load_function('modulos/mod_tpv/funciones.php', 'BuscarProductos');
    }

    protected function setUp(): void
    {
        $this->conn = \tpvfox_test_mysqli();
        $this->conn->begin_transaction();
        $this->conn->query(
            "INSERT INTO articulos (idArticulo, articulo_name, estado, fecha_creado, ultimoCoste)"
            . " VALUES (900001, 'ZZZ Cafe Molido', 'activo', NOW(), 0)"
        );
    }

    protected function tearDown(): void
    {
        $this->conn->rollback();
    }

    public function test_busca_por_nombre(): void
    {
        $r = \BuscarProductos(0, 'a.articulo_name', 'Cafe', $this->conn);
        $nombres = array_column($r['datos'] ?? [], 'articulo_name');
        $this->assertContains('ZZZ Cafe Molido', $nombres);
    }

    public function test_campo_no_permitido_se_rechaza(): void
    {
        $r = \BuscarProductos(0, 'a.articulo_name ; DROP TABLE x', 'Cafe', $this->conn);
        $this->assertSame('CampoNoValido', $r['Estado'] ?? null);
    }

    public function test_palabra_inyectable_es_literal(): void
    {
        $r = \BuscarProductos(0, 'a.articulo_name', 'NoExisteXYZ" OR "1"="1', $this->conn);
        $this->assertSame([], $r['datos'] ?? []);
    }
}
