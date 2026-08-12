<?php

declare(strict_types=1);

namespace Tpvfox\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Identificadores (tabla/columna) en la capa de datos segura. No se pueden
 * parametrizar, así que se validan: como identificador simple, o contra una
 * lista blanca cuando el nombre viene de fuera (ORDER BY, campo de búsqueda).
 */
final class DbIdentTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once TPVFOX_ROOT . '/clases/DB.php';
    }

    public function test_identificador_valido_se_entrecomilla(): void
    {
        $this->assertSame('`clientes`', \DB::ident('clientes'));
        $this->assertSame('`articulo_name`', \DB::ident('articulo_name'));
    }

    public function test_identificador_con_inyeccion_lanza(): void
    {
        $this->expectException(InvalidArgumentException::class);
        \DB::ident('clientes` ; DROP TABLE usuarios -- ');
    }

    public function test_whitelist_acepta_permitido(): void
    {
        $this->assertSame('`Nombre`', \DB::identWhitelist('Nombre', ['Nombre', 'razonsocial']));
    }

    public function test_whitelist_rechaza_no_permitido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        \DB::identWhitelist('password', ['Nombre', 'razonsocial']);
    }
}
