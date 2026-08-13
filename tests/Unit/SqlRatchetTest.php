<?php

declare(strict_types=1);

namespace Tpvfox\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Guard anti-regresión ("ratchet") de la migración a la capa de datos segura.
 *
 * Cuenta las llamadas crudas `->query(` que quedan en modulos/, clases/ y
 * controllers/ (SQL a mano, potencialmente inyectable). El número NO puede crecer: el código nuevo
 * debe usar la clase DB (parametrizada), y cada módulo migrado BAJA el contador.
 *
 * Al migrar y reducir el número real, actualiza BASELINE hacia abajo para que
 * el trinquete no se pueda volver a subir.
 */
final class SqlRatchetTest extends TestCase
{
    /** Máximo de `->query(` crudos admitidos. Solo debe DECRECER. */
    private const BASELINE = 0;

    public function test_no_aumentan_las_consultas_crudas(): void
    {
        $raiz = TPVFOX_ROOT;
        $total = 0;
        foreach (['modulos', 'clases', 'controllers'] as $dir) {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($raiz . '/' . $dir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($it as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                if (str_ends_with($file->getPathname(), 'clases/DB.php')) {
                    continue;
                }
                $total += substr_count(file_get_contents($file->getPathname()), '->query(');
            }
        }

        $this->assertLessThanOrEqual(
            self::BASELINE,
            $total,
            "Hay $total llamadas crudas ->query() (baseline " . self::BASELINE . "). "
                . 'El código nuevo debe usar la capa DB parametrizada.'
        );
    }
}
