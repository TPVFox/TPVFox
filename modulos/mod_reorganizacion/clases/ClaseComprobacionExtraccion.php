<?php

include_once $RutaServidor . $HostNombre . '/clases/ClaseTFModelo.php';
include_once $URLCom . '/modulos/mod_informes/clases/PosstockQueryRepository.php';
include_once $URLCom . '/modulos/mod_informes/clases/PosstockC1Detector.php';

// @ Objetivo
// Componer, para todo el catálogo, la trayectoria de existencias del producto en el
// ejercicio vigente y su estado frente al criterio: cruza el detector de existencias
// negativas y le aporta el saldo de partida ya calculado, y adjunta el marcado y las
// condiciones conocidas sin depender de que el detector haya emitido incidencia.
//
// Es la única clase del módulo que instancia el repositorio y el detector del
// informe de existencias negativas: el resto del módulo recibe de ella estructuras
// propias y no nombra ningún tipo suyo.
class ClaseComprobacionExtraccion extends TFModelo
{
}
