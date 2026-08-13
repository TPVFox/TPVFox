<?php

declare(strict_types=1);

namespace Tpvfox\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Seguridad + caracterización del filtro del plugin de paginación
 * (PluginClasePaginacion). La búsqueda ($_GET['buscar']) y el filtro por campo
 * ($_GET['filtro']) se emiten ahora como placeholders `?` y los valores se
 * recuperan con GetFiltroParams(); el número de `?` debe coincidir con el de
 * valores, y ningún valor de usuario puede aparecer crudo en el SQL.
 *
 * El nombre de campo (columna) es un identificador: se valida por regex y se
 * descarta si no es un identificador legítimo.
 */
final class PaginacionFiltroTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once TPVFOX_ROOT . '/plugins/paginacion/ClasePaginacion.php';
    }

    protected function setUp(): void
    {
        $_GET = array();
    }

    protected function tearDown(): void
    {
        $_GET = array();
    }

    private function nuevo(array $get): \PluginClasePaginacion
    {
        $_GET = $get;
        return new \PluginClasePaginacion(__FILE__);
    }

    public function test_una_palabra_un_campo_liga_un_parametro(): void
    {
        $p = $this->nuevo(['buscar' => 'cafe']);
        $p->SetCamposControler(['a.articulo_name']);
        $where = $p->GetFiltroWhere('OR');
        $params = $p->GetFiltroParams();

        $this->assertSame(1, substr_count($where, '?'));
        $this->assertSame(['%cafe%'], $params);
        $this->assertStringContainsString('a.articulo_name LIKE ?', $where);
    }

    public function test_varias_palabras_y_campos_cuadran_placeholders_y_params(): void
    {
        $p = $this->nuevo(['buscar' => 'cafe molido']);
        $p->SetCamposControler(['a.articulo_name', 'ac.codBarras']);
        $where = $p->GetFiltroWhere('OR');
        $params = $p->GetFiltroParams();

        // 2 palabras x 2 campos = 4 placeholders y 4 valores, en el mismo orden.
        $this->assertSame(4, substr_count($where, '?'));
        $this->assertSame(count($params), substr_count($where, '?'));
        $this->assertSame(['%cafe%', '%molido%', '%cafe%', '%molido%'], $params);
    }

    public function test_busqueda_inyectable_va_ligada_no_rompe_sql(): void
    {
        $p = $this->nuevo(['buscar' => 'x" OR "1"="1']);
        $p->SetCamposControler(['a.articulo_name']);
        $where = $p->GetFiltroWhere('OR');
        $params = $p->GetFiltroParams();

        // El nº de ? coincide con el nº de valores (no hay valor "suelto").
        $this->assertSame(count($params), substr_count($where, '?'));
        // La carga de inyección no aparece cruda en el SQL: va como valor ligado.
        $this->assertStringNotContainsString('"1"="1"', $where);
        $this->assertStringNotContainsString('OR "1"', $where);
    }

    public function test_filtro_por_campo_liga_el_valor(): void
    {
        $p = $this->nuevo(['filtro' => 'Activo']);
        $p->SetCamposControler(['a.articulo_name']);
        $p->SetCampoFiltro('estado');
        $where = $p->GetFiltroWhere();
        $params = $p->GetFiltroParams();

        $this->assertStringContainsString('estado = ?', $where);
        $this->assertSame(['Activo'], $params);
    }

    public function test_campo_no_identificador_se_descarta(): void
    {
        $p = $this->nuevo(['buscar' => 'y']);
        $p->SetCamposControler(['a.articulo_name; DROP TABLE x']);
        $where = $p->GetFiltroWhere('OR');
        $params = $p->GetFiltroParams();

        // El campo malicioso no se usa (no aparece en el SQL) y no genera params.
        $this->assertStringNotContainsString('DROP TABLE', $where);
        $this->assertSame([], $params);
    }

    public function test_limit_es_numerico(): void
    {
        $p = $this->nuevo(['pagina' => '3 UNION SELECT']);
        $p->SetCantidadRegistros(1000); // fuerza CuantasPaginas -> limitConsulta
        $limit = $p->GetLimitConsulta();
        // El OFFSET deriva de (int)pagina: no hay rastro de la carga de inyección.
        $this->assertStringNotContainsString('UNION', $limit);
    }
}
