<?php

include_once $RutaServidor . $HostNombre . '/clases/ClaseTFModelo.php';
include_once $URLCom . '/clases/traits/MontarAdvertenciaTrait.php';

// @ Objetivo
// Base de las clases del módulo de relaciones entre productos. Reúne lo que todas
// necesitan antes de leer o escribir: saber si las tablas del módulo están instaladas.
//
// El código del módulo y el fichero que crea sus tablas llegan a una instalación por
// caminos distintos, así que ninguna clase da por hecho que las tablas existen: lo
// pregunta aquí. Esta clase no crea ni repara tablas; eso es del fichero de instalación.
class RelacionesModelo extends TFModelo
{
    use MontarAdvertenciaTrait;

    // Las columnas que este código espera de cada tabla. Si la base tiene otras, de más o
    // de menos, las tablas son de otra versión y el módulo no opera sobre ellas.
    const ESTRUCTURA = array(
        'relacionesTipos' => array('id', 'nombre', 'forma', 'llevaCantidad', 'estado'),
        'relaciones' => array(
            'id', 'numRelacion', 'idTipo', 'nombre', 'descripcion', 'diasViabilidad', 'version',
            'tipoCambio', 'origenAjuste', 'idUsuario', 'fechaCreacion', 'estado',
        ),
        'relacionesLineas' => array('id', 'idRelacion', 'idArticulo', 'sentido', 'cantidad', 'unidad', 'estado'),
    );

    public function estadoEstructura()
    {
        // @ Objetivo
        // Decir en qué estado están las tablas del módulo en esta instalación.
        // @ Devolvemos
        //      array con ['datos'], uno de estos cuatro valores:
        //          'ausente'      no existe ninguna de las tres tablas.
        //          'incompleta'   existe alguna, pero no las tres.
        //          'otraVersion'  existen las tres, con columnas distintas de las esperadas.
        //          'instalada'    existen las tres con las columnas esperadas.
        //      o con ['error'] y ['consulta'] si no se pudo leer la base.
        $sql = 'SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS'
            . ' WHERE TABLE_SCHEMA = DATABASE()'
            . " AND TABLE_NAME IN ('" . implode("', '", array_keys(self::ESTRUCTURA)) . "')";

        $columnas = array();
        try {
            $resultado = $this->conexionBDTPV()->query($sql);
            while ($fila = $resultado->fetch_assoc()) {
                $columnas[$fila['TABLE_NAME']][] = $fila['COLUMN_NAME'];
            }
        } catch (\Throwable $e) {
            error_log('Relaciones: no se pudo leer el estado de las tablas: ' . $e);
            return array(
                'error' => $this->montarAdvertencia('danger', 'No se pudo comprobar la instalación de las relaciones entre productos.'),
                'consulta' => $sql,
            );
        }

        if (count($columnas) === 0) {
            return array('datos' => 'ausente');
        }
        if (count($columnas) < count(self::ESTRUCTURA)) {
            return array('datos' => 'incompleta');
        }
        foreach (self::ESTRUCTURA as $tabla => $esperadas) {
            $presentes = $columnas[$tabla];
            sort($presentes);
            sort($esperadas);
            if ($presentes !== $esperadas) {
                return array('datos' => 'otraVersion');
            }
        }

        return array('datos' => 'instalada');
    }
}
