<?php

declare(strict_types=1);

namespace Tpvfox\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Guard de autenticación de los endpoints AJAX. Fija que solo una sesión con
 * estadoTpv === 'Correcto' se considera autenticada.
 */
final class AuthGuardTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once TPVFOX_ROOT . '/clases/auth_guard.php';
    }

    public function test_sin_sesion_no_autenticado(): void
    {
        $_SESSION = [];
        $this->assertFalse(\tpvfox_is_authenticated());
    }

    public function test_estado_no_correcto_no_autenticado(): void
    {
        $_SESSION = ['estadoTpv' => 'SinActivar'];
        $this->assertFalse(\tpvfox_is_authenticated());
    }

    public function test_estado_correcto_autenticado(): void
    {
        $_SESSION = ['estadoTpv' => 'Correcto'];
        $this->assertTrue(\tpvfox_is_authenticated());
    }
}
