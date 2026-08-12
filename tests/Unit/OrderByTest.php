<?php

declare(strict_types=1);

namespace Tpvfox\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Seguridad del ORDER BY de la paginación (usado por todos los listados). El
 * campo de ordenación viene de $_GET['orden'] y no debe poder inyectar SQL.
 */
final class OrderByTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once TPVFOX_ROOT . '/plugins/paginacion/ClasePaginacion.php';
    }

    public function test_campo_valido_se_aplica(): void
    {
        $pag = new \PluginClasePaginacion('listado.php');
        $pag->SetOrderByConsulta('nombre:ASC');
        $this->assertStringContainsString('ORDER BY nombre ASC', $pag->filtroOrd);
    }

    public function test_campo_con_prefijo_de_tabla_valido(): void
    {
        $pag = new \PluginClasePaginacion('listado.php');
        $pag->SetOrderByConsulta('c.nombre:DESC');
        $this->assertStringContainsString('ORDER BY c.nombre DESC', $pag->filtroOrd);
    }

    /**
     * SEGURIDAD (SQLi): un campo de ordenación con inyección no debe llegar al
     * ORDER BY. Solo se admiten nombres de columna (con prefijo de tabla opcional).
     */
    public function test_campo_inyectable_se_rechaza(): void
    {
        $pag = new \PluginClasePaginacion('listado.php');
        $pag->SetOrderByConsulta('(SELECT password FROM usuarios LIMIT 1):ASC');
        $this->assertStringNotContainsString('SELECT', $pag->filtroOrd);
        $this->assertStringNotContainsString('usuarios', $pag->filtroOrd);
    }
}
