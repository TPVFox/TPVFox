<?php

trait MontarAdvertenciaTrait
{
    public function montarAdvertencia($tipo, $mensaje, $html = 'KO')
    {
        // @ Objetivo:
        // Montar array para error/advertencia , tb podemos devolver el html
        // @ Parametros
        //  $tipo -> (string) Indica tipo error/advertencia puede ser : danger,warning,success y info
        //  $mensaje -> puede ser string o array. Este ultimos es comodo por ejemplo en las cosultas.
        //  $html -> (string) Indicamos si queremos que devuelva html en vez del array.
        // @ Devolvemos
        //  Array ( tipo, mensaje ) o html con advertencia o error.
        $advertencia = array('tipo' => $tipo,
            'mensaje' => $mensaje,
        );
        if ($html === 'OK') {
            $advertencia = '<div class="alert alert-' . $tipo . '">'
                . '<strong>' . $tipo . ' </strong><br/> ';
            if (is_array($mensaje)) {
                $p = print_r($mensaje, true);
                $advertencia .= '<pre>' . $p . '</pre>';
            } else {
                $advertencia .= $mensaje;
            }
            $advertencia .= '</div>';

        }

        return $advertencia;
    }
}
