<?php

/**
 * Guard de autenticación para los endpoints AJAX (modulos/*\/tareas.php).
 *
 * Debe incluirse DESPUÉS de inicial.php, que arranca la sesión y fija
 * $_SESSION['estadoTpv']. Cierra el bypass por el que los tareas.php se
 * ejecutaban sin comprobar la sesión (a diferencia de las páginas, que sí pasan
 * por el gate de head.php).
 */

if (!function_exists('tpvfox_is_authenticated')) {
    function tpvfox_is_authenticated(): bool
    {
        return isset($_SESSION['estadoTpv']) && $_SESSION['estadoTpv'] === 'Correcto';
    }

    function tpvfox_require_auth(): void
    {
        if (!tpvfox_is_authenticated()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'No autenticado']);
            exit;
        }
    }
}
