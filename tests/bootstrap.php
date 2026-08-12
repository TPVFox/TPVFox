<?php

/**
 * Bootstrap de la suite de tests de tpvfox.
 *
 * El código legacy no usa autoload PSR-4: carga clases con includes que
 * dependen de los globals $RutaServidor/$HostNombre definidos en
 * configuracion.php, y algunos includes son relativos. Aquí preparamos ese
 * entorno de forma controlada y apuntando SIEMPRE a la BD de test (contenedor
 * MariaDB dockerizado), sin tocar el código de la aplicación.
 */

declare(strict_types=1);

define('TPVFOX_ROOT', dirname(__DIR__));

// --- Forzar la BD de test para toda la suite (independiente de la de dev). ---
putenv('TPVFOX_DB_HOST=127.0.0.1');
putenv('TPVFOX_DB_NAME=tpvfox_test');
putenv('TPVFOX_DB_USER=tpvfox_test');
putenv('TPVFOX_DB_PASS=tpvfox_test');

// --- Generar configuracion.php (env-driven) si no existe. Está gitignored y
//     no contiene secretos reales: por defecto apunta a la BD de test. ---
$configPath = TPVFOX_ROOT . '/configuracion.php';
if (!file_exists($configPath)) {
    file_put_contents($configPath, <<<'PHP'
<?php
// Configuración env-driven para DESARROLLO/TEST (gitignored). Generada por
// tests/bootstrap.php. Por defecto apunta a la BD MariaDB de test dockerizada;
// sobrescribe con las variables de entorno TPVFOX_* para otra BD.
$RutaServidor = getenv('TPVFOX_RUTA_SERVIDOR') ?: dirname(__DIR__);
$HostNombre   = getenv('TPVFOX_HOST_NOMBRE')   ?: '/' . basename(__DIR__);
$RutaDatos    = getenv('TPVFOX_RUTA_DATOS')     ?: sys_get_temp_dir() . '/tpvfox_datos';

$servidorMysql = getenv('TPVFOX_DB_HOST') ?: '127.0.0.1';
$nombrebdMysql = getenv('TPVFOX_DB_NAME') ?: 'tpvfox_test';
$usuarioMysql  = getenv('TPVFOX_DB_USER') ?: 'tpvfox_test';
$passwordMysql = getenv('TPVFOX_DB_PASS') ?: 'tpvfox_test';

$rutatmp     = sys_get_temp_dir();
$ruta_upload = sys_get_temp_dir() . '/tpvfox_upload';
$ruta_segura = sys_get_temp_dir() . '/tpvfox_segura';

$CONF_campoPeso = 'no';
$email_direccion_origen = 'sinrespuesta@tpvfox.test';
$email_usuario_origen   = 'TPVFox test';
$PHPMAILER_CONF = ['host' => 'localhost', 'SMTPAuth' => false, 'Port' => 1025, 'Username' => '', 'Password' => ''];
PHP);
}

// --- Autoload de Composer (PHPUnit + clases de test namespaced). ---
require_once TPVFOX_ROOT . '/vendor/autoload.php';

// --- Cargar la config: define $RutaServidor, $HostNombre y las credenciales.
//     PHPUnit incluye este bootstrap DENTRO de su propio código, así que las
//     variables de aquí NO son globales de verdad; hay que promocionarlas a
//     $GLOBALS para que los includes del legacy (que usan `global`/ámbito
//     global) las vean. ---
require $configPath;
foreach (['RutaServidor', 'HostNombre', 'RutaDatos'] as $__g) {
    if (isset($$__g)) {
        $GLOBALS[$__g] = $$__g;
    }
}
unset($__g);

// --- Los includes relativos del legacy (p.ej. include('ClasePermisos.php'))
//     se resuelven añadiendo las carpetas de clases al include_path. ---
set_include_path(implode(PATH_SEPARATOR, [
    get_include_path(),
    TPVFOX_ROOT . '/clases',
    TPVFOX_ROOT . '/modulos',
]));

// --- Loaders. No precargamos nada en el bootstrap a propósito: la capa de datos
//     (claseModeloP) incluye ClaseConexion.php con include_once, pero
//     ClaseSession.php lo incluye con include (plano) — cargar ambos en el mismo
//     proceso da "Cannot redeclare class ClaseConexion". Por eso cada clase de
//     test carga SOLO lo suyo con estos loaders (que exponen los globals que
//     necesitan los includes del legacy), y los tests de ClaseSession corren en
//     proceso aislado. ---
function tpvfox_load_datalayer(): void
{
    global $RutaServidor, $HostNombre;
    require_once TPVFOX_ROOT . '/modulos/claseModelo.php';
}

function tpvfox_load_session(): void
{
    global $RutaServidor, $HostNombre;
    require_once TPVFOX_ROOT . '/clases/ClaseSession.php';
}

/**
 * Extrae UNA función global por su nombre de un fichero legacy y la define, sin
 * ejecutar los include/require de la cabecera de ese fichero (que arrastran
 * inicial.php, sesión, etc.). Útil para caracterizar funciones PURAS atrapadas
 * en ficheros con efectos colaterales (p.ej. recalculoTotales en
 * modulos/mod_tpv/funciones.php).
 *
 * Localiza la firma y empareja llaves. Asume que la función no contiene `{`/`}`
 * dentro de cadenas o comentarios (verificado para las que cargamos).
 */
function tpvfox_load_function(string $relFile, string $fnName): void
{
    if (function_exists($fnName)) {
        return;
    }
    $src = file_get_contents(TPVFOX_ROOT . '/' . $relFile);
    $pos = strpos($src, 'function ' . $fnName);
    if ($pos === false) {
        throw new \RuntimeException("Función '$fnName' no encontrada en $relFile");
    }
    $braceStart = strpos($src, '{', $pos);
    $depth = 0;
    $end = null;
    for ($i = $braceStart, $n = strlen($src); $i < $n; $i++) {
        if ($src[$i] === '{') {
            $depth++;
        } elseif ($src[$i] === '}') {
            $depth--;
            if ($depth === 0) {
                $end = $i;
                break;
            }
        }
    }
    eval(substr($src, $pos, $end - $pos + 1));
}

/** Conexión mysqli directa a la BD de test (para sembrar/asertar en tests). */
function tpvfox_test_mysqli(): \mysqli
{
    return new \mysqli(
        getenv('TPVFOX_DB_HOST') ?: '127.0.0.1',
        getenv('TPVFOX_DB_USER') ?: 'tpvfox_test',
        getenv('TPVFOX_DB_PASS') ?: 'tpvfox_test',
        getenv('TPVFOX_DB_NAME') ?: 'tpvfox_test'
    );
}
