<?php

declare(strict_types=1);

namespace Tpvfox\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Helper de escapado HTML (anti-XSS). Fija que e() neutraliza el marcado
 * peligroso al imprimir datos de usuario/BD.
 */
final class EscapeHtmlTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once TPVFOX_ROOT . '/app/helpers.php';
    }

    public function test_escapa_etiquetas_script(): void
    {
        $out = \e('<script>alert(1)</script>');
        $this->assertStringNotContainsString('<script>', $out);
        $this->assertStringContainsString('&lt;script&gt;', $out);
    }

    public function test_escapa_comillas_y_atributos(): void
    {
        $out = \e('" onerror="alert(1)');
        $this->assertStringNotContainsString('"', $out);
        $this->assertStringContainsString('&quot;', $out);
    }

    public function test_texto_normal_se_conserva(): void
    {
        $this->assertSame('Café Central', \e('Café Central'));
    }

    public function test_null_no_rompe(): void
    {
        $this->assertSame('', \e(null));
    }
}
