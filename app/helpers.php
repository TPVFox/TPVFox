<?php

if (!function_exists('dd')) { // dump and die
    function dd($what_to_dump)
    {
        echo '<pre>';
        print_r($what_to_dump);
        echo '</pre>';
        die();
    }
}

if (!function_exists('dump')) {
    function dump($what_to_dump)
    {
        echo '<pre>';
        print_r($what_to_dump);
        echo '</pre>';
    }
}

if (!function_exists('mostrarError')) {
    function mostrarError($respuesta)
    {
        return '<pre>' . print_r($respuesta) . '</pre>';
    }
}

if (!function_exists('entreComillas')) {
    function entreComillas($cadena, $comillas = '"')
    {
        return $comillas . $cadena . $comillas;
    }
}

if (!function_exists('e')) {
    /**
     * Escapa una cadena para insertarla de forma segura en HTML (anti-XSS).
     * Usar SIEMPRE al imprimir datos de usuario o de la BD: echo e($valor).
     */
    function e($valor): string
    {
        return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
