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

if (!function_exists('escapar')) {
    function escapar($valor)
    {
        // @ Objetivo:
        // Preparar un dato para escribirlo en el HTML como texto: sus marcas y comillas no se interpretan.
        // @ Parametros:
        // $valor -> El dato. Si es null se escribe vacío.
        // @ Devolvemos:
        // (string) El dato listo para el HTML.
        $texto = '';
        if ($valor !== null) {
            $texto = (string) $valor;
        }
        return htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
