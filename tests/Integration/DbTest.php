<?php

declare(strict_types=1);

namespace Tpvfox\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Capa de acceso a datos segura (clase DB): todo pasa por sentencias
 * preparadas. Los valores nunca se concatenan, así que la inyección SQL es
 * imposible por construcción. Tests contra la BD de test (transacción revertida).
 */
final class DbTest extends TestCase
{
    private \mysqli $conn;
    private \DB $db;

    public static function setUpBeforeClass(): void
    {
        require_once TPVFOX_ROOT . '/clases/DB.php';
    }

    protected function setUp(): void
    {
        $this->conn = \tpvfox_test_mysqli();
        $this->conn->begin_transaction();
        $this->db = new \DB($this->conn);
    }

    protected function tearDown(): void
    {
        $this->conn->rollback();
    }

    public function test_insert_devuelve_id_y_selectWhere_lo_lee(): void
    {
        $id = $this->db->insert('clientes', ['Nombre' => 'ZZZ DB', 'estado' => 'activo']);
        $this->assertGreaterThan(0, $id);

        $rows = $this->db->selectWhere('clientes', ['idClientes' => $id]);
        $this->assertCount(1, $rows);
        $this->assertSame('ZZZ DB', $rows[0]['Nombre']);
    }

    public function test_update_devuelve_afectados_y_cambia_el_dato(): void
    {
        $id = $this->db->insert('clientes', ['Nombre' => 'A', 'estado' => 'activo']);
        $afectados = $this->db->update('clientes', ['Nombre' => 'B'], ['idClientes' => $id]);
        $this->assertSame(1, $afectados);
        $this->assertSame('B', $this->db->selectWhere('clientes', ['idClientes' => $id])[0]['Nombre']);
    }

    public function test_delete_borra(): void
    {
        $id = $this->db->insert('clientes', ['Nombre' => 'A', 'estado' => 'activo']);
        $this->db->delete('clientes', ['idClientes' => $id]);
        $this->assertSame([], $this->db->selectWhere('clientes', ['idClientes' => $id]));
    }

    public function test_select_con_parametros_trata_el_valor_como_literal(): void
    {
        $this->db->insert('clientes', ['Nombre' => 'ZZZ Uno', 'estado' => 'activo']);
        $this->db->insert('clientes', ['Nombre' => 'ZZZ Dos', 'estado' => 'activo']);

        // Payload de inyección como VALOR: no debe devolver todo, solo el literal (nada).
        $rows = $this->db->select(
            'SELECT * FROM clientes WHERE Nombre = ?',
            ['ZZZ Uno" OR "1"="1']
        );
        $this->assertSame([], $rows);
    }

    public function test_condiciones_con_valor_malicioso_no_inyectan(): void
    {
        $this->db->insert('clientes', ['Nombre' => 'ZZZ Cond', 'estado' => 'activo']);
        $rows = $this->db->selectWhere('clientes', ['Nombre' => 'x" OR "1"="1']);
        $this->assertSame([], $rows);
    }

    public function test_pquery_devuelve_mysqli_result_usable(): void
    {
        $this->db->insert('clientes', ['Nombre' => 'ZZZ PQ', 'estado' => 'activo']);
        $res = $this->db->pquery('SELECT * FROM clientes WHERE Nombre = ?', ['ZZZ PQ']);
        // Drop-in: el llamante sigue usando la interfaz de mysqli_result.
        $this->assertInstanceOf(\mysqli_result::class, $res);
        $this->assertSame(1, $res->num_rows);
        $this->assertSame('ZZZ PQ', $res->fetch_assoc()['Nombre']);
    }

    public function test_pquery_parametro_malicioso_es_literal(): void
    {
        $this->db->insert('clientes', ['Nombre' => 'ZZZ A', 'estado' => 'activo']);
        $this->db->insert('clientes', ['Nombre' => 'ZZZ B', 'estado' => 'activo']);
        $res = $this->db->pquery('SELECT * FROM clientes WHERE Nombre = ?', ['ZZZ A" OR "1"="1']);
        $this->assertSame(0, $res->num_rows);
    }
}
