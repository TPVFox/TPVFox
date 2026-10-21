<?php

include_once './../../inicial.php';

// Solo entra quien tiene el permiso de ver. Se compara con 1: quien no tiene fila de permiso recibe ''.
if ($ClasePermisos->getAccion('ver') != 1) {
    header('Location: ' . $HostNombre . '/index.php');
    exit;
}

include_once $URLCom . '/plugins/paginacion/ClasePaginacion.php';
include_once $URLCom . '/modulos/mod_relaciones/clases/Relaciones.php';
include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesTemporales.php';
include_once $URLCom . '/modulos/mod_relaciones/funciones.php';

// @ Objetivo
// Listado de productos relacionados: una fila por relación, vigente o de baja. Aquí se
// preparan los datos; el HTML está en template/view_lista.php.

$avisos = array();
// Lo que acaba de hacerse en la pantalla de relación, para decirlo al volver. Solo se aceptan
// números: se lee antes de que se limpien los parámetros del listado.
$avisoDeLaPantalla = null;
if (isset($_GET['activada']) && is_string($_GET['activada']) && ctype_digit($_GET['activada'])) {
    $version = '';
    if (isset($_GET['version']) && is_string($_GET['version']) && preg_match('/^[0-9]+\.[0-9]+$/', $_GET['version']) === 1) {
        $version = ', en la versión ' . $_GET['version'];
    }
    $avisoDeLaPantalla = array('tipo' => 'success', 'mensaje' => 'La relación ' . (int) $_GET['activada'] . ' ya rige con lo que se preparó' . $version . '.');
} elseif (isset($_GET['cancelada'])) {
    $avisoDeLaPantalla = array('tipo' => 'info', 'mensaje' => 'Se canceló lo que se preparaba: lo que rige no ha cambiado.');
}
$filasDeLaPagina = array();
$nombresDeTipos = array();
$CantidadRegistros = 0;
$hayRelaciones = false;
$instalado = false;
// Cada opción solo se ofrece a quien tiene su permiso. Ver solo necesita el de entrar aquí.
$puedeCambiarEstado = $ClasePermisos->getAccion('CambiarEstado') == 1;
$puedeCrear = $ClasePermisos->getAccion('Crear') == 1;
$puedeModificar = $ClasePermisos->getAccion('Modificar') == 1;
$relacionesAbiertas = array();

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
    if (isset($tipos['error'])) {
        $avisos[] = $tipos['error'];
    } else {
        $instalado = true;
        foreach ($tipos['datos'] as $tipo) {
            $nombresDeTipos[] = $tipo['nombre'];
        }
        if (count($nombresDeTipos) === 0) {
            $avisos[] = array('tipo' => 'info', 'mensaje' => 'Todavía no hay ningún tipo de relación. Se crean en Sistema, Tablas principales.');
        }
    }
}

// Lo que se está preparando, para retomarlo, como los albaranes abiertos de compras.
if ($instalado && ($puedeCrear || $puedeModificar)) {
    $CTemporales = new RelacionesTemporales();
    $abiertas = $CTemporales->listarAbiertos();
    if (isset($abiertas['error'])) {
        $avisos[] = $abiertas['error'];
    } else {
        $relacionesAbiertas = $abiertas['datos'];
    }
}

// Los parámetros se dejan en valores que el listado ofrece antes de que los lea el plugin.
$parametros = parametrosDelListado($_GET, $nombresDeTipos);
$_GET = array('pagina' => (string) $parametros['pagina'], 'buscar' => $parametros['buscar'], 'filtro' => $parametros['filtro']);
if ($parametros['orden'] !== '') {
    $_GET['orden'] = $parametros['orden'];
}
$NPaginado = new PluginClasePaginacion(__FILE__);
$filasPorPagina = $NPaginado->GetLimite();

if ($instalado) {
    // Solo se leen las filas de la página. Si la página pedida ya no existe, llega la última que hay.
    $listado = $CRelaciones->listado($parametros['buscar'], $parametros['filtro'], $parametros['orden'], $parametros['pagina'], $filasPorPagina);
    if (isset($listado['error'])) {
        $avisos[] = $listado['error'];
    } else {
        $filasDeLaPagina = $listado['datos']['filas'];
        $CantidadRegistros = $listado['datos']['total'];
        $parametros['pagina'] = $listado['datos']['pagina'];
        $hayRelaciones = $CantidadRegistros > 0 || $parametros['buscar'] !== '' || $parametros['filtro'] !== '';
    }
}

// La página puede haber cambiado: el plugin se vuelve a crear con la que se muestra.
$_GET['pagina'] = (string) $parametros['pagina'];
$NPaginado = new PluginClasePaginacion(__FILE__);
// El plugin tiene que conocer el orden pedido para conservarlo en los enlaces de sus páginas.
if ($parametros['orden'] !== '') {
    $NPaginado->SetOrderByConsulta($parametros['orden']);
}
$NPaginado->SetCantidadRegistros($CantidadRegistros);

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
$htmlOrdenarEstado = $NPaginado->htmlOrdenar('estado');

include $URLCom . '/modulos/mod_relaciones/template/view_lista.php';
