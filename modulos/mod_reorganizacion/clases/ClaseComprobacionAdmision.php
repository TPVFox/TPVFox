<?php

include_once $RutaServidor . $HostNombre . '/clases/ClaseTFModelo.php';

// @ Objetivo
// Admitir el resultado que llega del otro ejercicio: validar su origen e integridad,
// y emparejar cada fila con el catálogo por la misma identidad con la que se
// reconoce el traspaso. Si el fichero entero no es válido lo rechaza entero; si solo
// una fila falla, esa fila queda marcada y el resto sigue.
class ClaseComprobacionAdmision extends TFModelo
{
}
