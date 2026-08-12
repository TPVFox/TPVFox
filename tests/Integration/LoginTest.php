<?php

declare(strict_types=1);

namespace Tpvfox\Tests\Integration;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Caracterización del control de credenciales del login (ClaseSession::comprobarUser)
 * contra la BD de test (MariaDB dockerizado), con el usuario semilla admin/admin.
 *
 * Se aísla en proceso separado porque ClaseSession.php incluye ClaseConexion.php
 * con `include` plano (choca con la capa de datos si comparten proceso).
 *
 * Se instancia ClaseSession SIN constructor (reflexión) para no arrastrar
 * comprobarEstado()/ClasePermisos y probar la lógica de credenciales en aislado,
 * que es justo el punto donde vive la SQLi del login que luego corregiremos.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class LoginTest extends TestCase
{
    protected function setUp(): void
    {
        // En setUp (no setUpBeforeClass): con RunTestsInSeparateProcesses,
        // setUpBeforeClass corre en el proceso PADRE —donde la capa de datos ya
        // pudo cargar ClaseConexion— y chocaría con el `include` plano de
        // ClaseSession. setUp corre en el proceso hijo aislado y limpio.
        \tpvfox_load_session();
    }

    private function nuevaSession(): object
    {
        $sess = (new ReflectionClass('ClaseSession'))->newInstanceWithoutConstructor();
        $sess->BDTpv = \tpvfox_test_mysqli();
        return $sess;
    }

    public function test_credenciales_correctas_marcan_estado_correcto(): void
    {
        $_SESSION = [];
        $this->nuevaSession()->comprobarUser('admin', 'admin');

        $this->assertSame('Correcto', $_SESSION['estadoTpv']);
        $this->assertSame('admin', $_SESSION['usuarioTpv']['login']);
    }

    public function test_password_incorrecta_no_autentica(): void
    {
        $_SESSION = [];
        $this->nuevaSession()->comprobarUser('admin', 'password-mala');

        $this->assertNotSame('Correcto', $_SESSION['estadoTpv']);
        $this->assertArrayNotHasKey('usuarioTpv', $_SESSION);
    }

    public function test_usuario_inexistente_no_autentica(): void
    {
        $_SESSION = [];
        // El @ suprime un warning REAL del código actual: con usuario inexistente
        // fetch_assoc() devuelve null y comprobarUser accede a $pwdBD['password']
        // (clave indefinida). Queda documentado aquí; el arreglo lo eliminará.
        @$this->nuevaSession()->comprobarUser('nadie', 'loquesea');

        $this->assertNotSame('Correcto', $_SESSION['estadoTpv']);
    }

    /**
     * SEGURIDAD (hashing): un usuario cuya contraseña está guardada con
     * password_hash (bcrypt) debe poder autenticarse. El código antiguo solo
     * comparaba MD5, así que no lo reconocía.
     */
    public function test_verifica_password_bcrypt(): void
    {
        $_SESSION = [];
        $sess = $this->nuevaSession();
        $db = $sess->BDTpv;
        $db->begin_transaction();

        $user = 'bcrypt_user';
        $hash = password_hash('secreto', PASSWORD_DEFAULT);
        $ins = $db->prepare('INSERT INTO usuarios (username,password,fecha,group_id,estado,nombre) VALUES (?,?,CURDATE(),1,"activo","BC")');
        $ins->bind_param('ss', $user, $hash);
        $ins->execute();
        $uid = $db->insert_id;
        $db->query('INSERT INTO indices (idTienda,idUsuario,numticket,tempticket) VALUES (1,' . $uid . ',1,1)');

        $sess->comprobarUser($user, 'secreto');
        $db->rollback();

        $this->assertSame('Correcto', $_SESSION['estadoTpv']);
    }

    /**
     * SEGURIDAD (SQLi de login, pre-auth): un username con inyección UNION no
     * debe permitir autenticarse sin credenciales válidas. El payload fabrica
     * una fila con password = md5('x') e id = 1 (que tiene registro en indices).
     * Contra el código vulnerable esto autentica; tras parametrizar, el username
     * es un literal que no existe y NO autentica.
     */
    public function test_no_permite_bypass_por_inyeccion_union(): void
    {
        $_SESSION = [];
        $payload = 'z" UNION SELECT "' . md5('x') . '","H",1,9 -- ';
        @$this->nuevaSession()->comprobarUser($payload, 'x');

        $this->assertNotSame('Correcto', $_SESSION['estadoTpv'], 'La inyección UNION no debe autenticar');
    }
}
