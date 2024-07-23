<?php

trait DesglosaDatosPorNombreTrait
{
    public function desglosaDatosPorNombre(array $datos, array $columnas): array
    {
        $resultado = [];
        foreach ($columnas as $columna) {
            if (isset($datos[$columna])) {
                $resultado[$columna] = $datos[$columna];
            }
        }
        return $resultado;
    }

    public function desglosaNoDatosPorNombre(array $datos, array $columnas): array
    {
        $resultado = [];
        foreach ($datos as $indice =>$dato) {
            if (!in_array($indice, $columnas)) {
                $resultado[$indice] = $dato;
            }
        }
        return $resultado;
    }

}
