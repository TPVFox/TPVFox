<?php

declare(strict_types=1);

namespace Tpvfox\Tests\Integration;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Caracterización + seguridad de la búsqueda de productos de compras
 * (BuscarProductos, mod_compras). Concatenaba el campo y las palabras de
 * búsqueda ($_POST) dentro del WHERE. Ahora el campo se valida contra lista
 * blanca (columnas de parametros.xml) y las palabras + idProveedor van
 * ligadas como parámetros.
 *
 * Proceso aislado: `BuscarProductos` es un nombre de función GLOBAL compartido
 * con las variantes de mod_tpv (4 args) y mod_venta (5 args); sin aislar, la
 * primera que cargue gana y las demás llamarían a la función equivocada.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class BuscarProductosComprasTest extends TestCase
{
    private \mysqli $conn;

    protected function setUp(): void
    {
        // En el proceso hijo aislado: cargar la función correcta de este módulo.
        require_once TPVFOX_ROOT . '/clases/DB.php';
        \tpvfox_load_function('modulos/mod_compras/funciones.php', 'BuscarProductos');
        $this->conn = \tpvfox_test_mysqli();
        $this->conn->begin_transaction();
        $this->conn->query(
            "INSERT INTO articulos (idArticulo, articulo_name, estado, fecha_creado, ultimoCoste)"
            . " VALUES (900003, 'ZZZ Te Verde Compras', 'activo', NOW(), 0)"
        );
    }

    protected function tearDown(): void
    {
        $this->conn->rollback();
    }

    public function test_busca_por_nombre(): void
    {
        $r = \BuscarProductos('ReferenciaPro', 'a.articulo_name', 'cajaBusqueda', 'Te Verde Compras', $this->conn, 1);
        $nombres = array_column($r['datos'] ?? [], 'articulo_name');
        $this->assertContains('ZZZ Te Verde Compras', $nombres);
    }

    public function test_campo_no_permitido_se_rechaza(): void
    {
        $r = \BuscarProductos('ReferenciaPro', 'a.articulo_name; DROP TABLE articulos', 'cajaBusqueda', 'Te', $this->conn, 1);
        $this->assertSame('CampoNoValido', $r['error'] ?? null);
    }

    public function test_palabra_inyectable_es_literal(): void
    {
        $r = \BuscarProductos('ReferenciaPro', 'a.articulo_name', 'cajaBusqueda', 'NoExisteXYZ" OR "1"="1', $this->conn, 1);
        $this->assertSame([], $r['datos'] ?? []);
    }
}
