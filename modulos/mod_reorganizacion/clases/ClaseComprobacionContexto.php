<?php

include_once $RutaServidor . $HostNombre . '/clases/ClaseTFModelo.php';

// @ Objetivo
// Fijar ejercicio y tienda desde la sesión activa, comprobar que el esquema y los
// parámetros de los que depende el cálculo están presentes, y abrir el bloque de
// solo lectura que envuelve toda la ejecución. Si algo falta, detiene la ejecución
// con el motivo; nunca deja pasar un resultado vacío.
class ClaseComprobacionContexto extends TFModelo
{
}
