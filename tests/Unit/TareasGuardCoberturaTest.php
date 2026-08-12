<?php

declare(strict_types=1);

namespace Tpvfox\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Cobertura del guard: TODOS los dispatchers AJAX (modulos/*\/tareas.php) deben
 * exigir autenticación (llamar a tpvfox_require_auth), salvo mod_tareas_cron,
 * que es un ejecutor de cron/CLI, no un endpoint web.
 *
 * Es la red que impide que un módulo nuevo (o uno existente) quede sin gate.
 */
final class TareasGuardCoberturaTest extends TestCase
{
    private const EXCLUIDOS = ['mod_tareas_cron'];

    public static function tareasFiles(): array
    {
        $ficheros = glob(TPVFOX_ROOT . '/modulos/*/tareas.php');
        $casos = [];
        foreach ($ficheros as $f) {
            $modulo = basename(dirname($f));
            if (in_array($modulo, self::EXCLUIDOS, true)) {
                continue;
            }
            $casos[$modulo] = [$f];
        }
        return $casos;
    }

    /**
     * @dataProvider tareasFiles
     */
    public function test_tareas_exige_autenticacion(string $fichero): void
    {
        $src = file_get_contents($fichero);
        $this->assertStringContainsString(
            'tpvfox_require_auth',
            $src,
            "El dispatcher $fichero no exige autenticación (falta el guard)."
        );
    }
}
