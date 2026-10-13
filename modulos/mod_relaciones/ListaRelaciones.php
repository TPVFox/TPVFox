<?php

include_once './../../inicial.php';

// Solo entra quien tiene el permiso de ver. Se compara con 1: quien no tiene fila de permiso recibe ''.
if ($ClasePermisos->getAccion('ver') != 1) {
    header('Location: ' . $HostNombre . '/index.php');
    exit;
}

include_once $URLCom . '/plugins/paginacion/ClasePaginacion.php';
include_once $URLCom . '/modulos/mod_relaciones/clases/Relaciones.php';
include_once $URLCom . '/modulos/mod_relaciones/funciones.php';

// @ Objetivo
// Listado de productos relacionados: una fila por relación que rige. Aquí se
// preparan los datos; el HTML está en template/view_lista.php.

$avisos = array();
$filas = array();
$nombresDeTipos = array();

$CRelaciones = new Relaciones();
$estructura = $CRelaciones->estadoEstructura();
if (isset($estructura['error'])) {
    $avisos[] = $estructura['error'];
} elseif ($estructura['datos'] !== 'instalada') {
    $motivos = array(
        'ausente' => 'Las tablas de relaciones no están instaladas en esta base de datos.',
        'incompleta' => 'La instalación de las tablas de relaciones está incompleta.',
        'otraVersion' => 'Las tablas de relaciones no son de la versión que este módulo espera.',
    );
    $avisos[] = array('tipo' => 'warning', 'mensaje' => $motivos[$estructura['datos']]);
} else {
    $CTipos = new RelacionesTipos();
    $tipos = $CTipos->listarTipos(false);
    $vigentes = $CRelaciones->vigentes();
    if (isset($tipos['error'])) {
        $avisos[] = $tipos['error'];
    } elseif (isset($vigentes['error'])) {
        $avisos[] = $vigentes['error'];
    } else {
        foreach ($tipos['datos'] as $tipo) {
            $nombresDeTipos[] = $tipo['nombre'];
        }
        if (count($nombresDeTipos) === 0) {
            $avisos[] = array('tipo' => 'info', 'mensaje' => 'Todavía no hay ningún tipo de relación. Se crean en Sistema, Tablas principales.');
        }
        $filas = filasDelListado($vigentes['datos']);
    }
}
$hayRelaciones = count($filas) > 0;

// Los parámetros se dejan en valores que el listado ofrece antes de que los lea el plugin.
$parametros = parametrosDelListado($_GET, $nombresDeTipos);
$_GET = array('pagina' => (string) $parametros['pagina'], 'buscar' => $parametros['buscar'], 'filtro' => $parametros['filtro']);
if ($parametros['orden'] !== '') {
    $_GET['orden'] = $parametros['orden'];
}

$filas = filtrarFilasDelListado($filas, $parametros['buscar'], $parametros['filtro']);
$filas = ordenarFilasDelListado($filas, $parametros['orden']);
$CantidadRegistros = count($filas);

// Una página que ya no existe pasa a ser la última que hay.
$NPaginado = new PluginClasePaginacion(__FILE__);
$filasPorPagina = $NPaginado->GetLimite();
$ultimaPagina = ultimaPaginaDelListado($CantidadRegistros, $filasPorPagina);
if ($parametros['pagina'] > $ultimaPagina) {
    $parametros['pagina'] = $ultimaPagina;
    $_GET['pagina'] = (string) $ultimaPagina;
    $NPaginado = new PluginClasePaginacion(__FILE__);
}
// El plugin tiene que conocer el orden pedido para conservarlo en los enlaces de sus páginas.
if ($parametros['orden'] !== '') {
    $NPaginado->SetOrderByConsulta($parametros['orden']);
}
$NPaginado->SetCantidadRegistros($CantidadRegistros);
$filasDeLaPagina = paginaDeFilasDelListado($filas, $parametros['pagina'], $filasPorPagina);

if (count($avisos) === 0 && !$hayRelaciones) {
    $avisos[] = array('tipo' => 'info', 'mensaje' => 'No hay ninguna relación entre productos.');
} elseif (count($avisos) === 0 && $CantidadRegistros === 0) {
    $avisos[] = array('tipo' => 'warning', 'mensaje' => 'Ninguna relación casa con lo buscado.');
}

$htmlPG = $NPaginado->htmlPaginado();
$htmlBuscar = $NPaginado->htmlBuscar();
$htmlFiltrar = $NPaginado->htmlFiltrar($nombresDeTipos, 'Filtrar por tipo');
$htmlOrdenarNumero = $NPaginado->htmlOrdenar('numRelacion');
$htmlOrdenarFecha = $NPaginado->htmlOrdenar('fecha');

include $URLCom . '/modulos/mod_relaciones/template/view_lista.php';
