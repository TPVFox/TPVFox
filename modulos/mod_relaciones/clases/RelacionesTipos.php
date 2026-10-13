<?php

include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesModelo.php';

// @ Objetivo
// Los tipos de relación: el vocabulario que cada tienda define sobre las tres formas.
// Es la única clase que lee y escribe la tabla relacionesTipos.
class RelacionesTipos extends RelacionesModelo
{
    public function leerTipo($idTipo)
    {
        // @ Objetivo
        // Leer un tipo de relación.
        // @ Parametros
        //      $idTipo -> (int) id del tipo.
        // @ Devolvemos
        //      ['datos'] con la fila del tipo, o null si no existe.
        //      ['error'] y ['consulta'] si falla.
        $sql = 'SELECT id, nombre, forma, llevaCantidad, estado FROM relacionesTipos WHERE id = ?';
        $respuesta = $this->consultaPreparada($sql, array($idTipo));

        if (!isset($respuesta['error'])) {
            $tipo = null;
            if (count($respuesta['datos']) > 0) {
                $tipo = $respuesta['datos'][0];
            }
            $respuesta['datos'] = $tipo;
        }

        return $respuesta;
    }

    public function listarTipos($soloVigentes)
    {
        // @ Objetivo
        // Listar los tipos de relación, por nombre.
        // @ Parametros
        //      $soloVigentes -> (bool) true deja fuera los que están de baja.
        // @ Devolvemos
        //      ['datos'] con las filas, o ['error'] y ['consulta'] si falla.
        $sql = 'SELECT id, nombre, forma, llevaCantidad, estado FROM relacionesTipos';
        if ($soloVigentes) {
            $sql .= " WHERE estado = 'Activo'";
        }
        $sql .= ' ORDER BY nombre';

        return $this->consultaPreparada($sql);
    }
}
