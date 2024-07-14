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
    function entreComillas($cadena, $comillas='"')
    {
        return $comillas . $cadena . $comillas;
    }
}
