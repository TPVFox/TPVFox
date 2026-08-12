<?php

declare(strict_types=1);

namespace Tpvfox\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Caracterización del cálculo de totales del ticket (recalculoTotales, mod_tpv).
 * Fija la aritmética y las reglas actuales: solo cuentan las líneas 'Activo',
 * el importe de línea es unidad × pvpconiva (precio CON iva), y el desglose por
 * tipo de IVA reparte base/iva.
 *
 * Es la red de seguridad para cuando dejemos de fiarnos del total que envía el
 * cliente y lo recalculemos en servidor: el resultado de este cálculo NO debe
 * cambiar.
 *
 * La función es pura pero vive en un fichero con includes de efectos colaterales
 * (inicial.php); se extrae aislada con tpvfox_load_function.
 */
final class RecalculoTotalesTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        \tpvfox_load_function('modulos/mod_tpv/funciones.php', 'recalculoTotales');
    }

    private function linea(string $estado, float $unidad, float $pvpconiva, int $ctipoiva): object
    {
        return (object) [
            'estado' => $estado,
            'unidad' => $unidad,
            'pvpconiva' => $pvpconiva,
            'ctipoiva' => $ctipoiva,
        ];
    }

    public function test_total_suma_lineas_activas(): void
    {
        $r = \recalculoTotales([
            $this->linea('Activo', 2, 10.00, 21),
            $this->linea('Activo', 1, 5.00, 10),
        ]);
        // 2*10 + 1*5 = 25.00
        $this->assertSame('25.00', $r['total']);
    }

    public function test_lineas_no_activas_se_excluyen(): void
    {
        $r = \recalculoTotales([
            $this->linea('Activo', 1, 10.00, 21),
            $this->linea('Eliminado', 5, 100.00, 21), // no debe contar
        ]);
        $this->assertSame('10.00', $r['total']);
    }

    public function test_desglose_por_tipo_de_iva(): void
    {
        $r = \recalculoTotales([
            $this->linea('Activo', 2, 10.00, 21), // BaseYiva 20 -> base 16.53, iva 3.47
            $this->linea('Activo', 1, 5.00, 10),  // BaseYiva 5  -> base 4.55,  iva 0.45
        ]);
        $this->assertSame('16.53', $r['desglose'][21]['base']);
        $this->assertSame('3.47', $r['desglose'][21]['iva']);
        $this->assertSame('4.55', $r['desglose'][10]['base']);
        $this->assertSame('0.45', $r['desglose'][10]['iva']);
    }

    public function test_ticket_vacio_da_cero(): void
    {
        $r = \recalculoTotales([]);
        $this->assertSame('0.00', $r['total']);
        $this->assertSame([], $r['desglose']);
    }
}
