<?php

include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_relaciones/funciones.php';

// @ Objetivo
// La pantalla de relación: crear una relación de cualquiera de las tres formas, preparar un
// cambio de una que rige viendo la versión vigente, y ver una relación. Se abre como el
// albarán: sin parámetros, para añadir; con id y accion, ver o editar; o con tActual, el
// temporal que se retoma. Aquí se preparan los datos; el HTML está en template/view_relacion.php.

// Solo se conservan los parámetros de la pantalla, ya convertidos: el enlace de volver escribe
// los de la dirección tal como llegan.
$accion = '';
if (isset($_GET['accion']) && in_array($_GET['accion'], array('ver', 'editar'), true)) {
    $accion = $_GET['accion'];
}
$numRelacion = isset($_GET['id']) && is_string($_GET['id']) ? (int) $_GET['id'] : 0;
$idTemporal = isset($_GET['tActual']) && is_string($_GET['tActual']) ? (int) $_GET['tActual'] : 0;
$_GET = array();
if ($idTemporal > 0) {
    $accion = 'editar';
    $numRelacion = 0;
    $_GET['tActual'] = (string) $idTemporal;
} elseif ($numRelacion > 0) {
    if ($accion === '') {
        $accion = 'ver';
    }
    $_GET['id'] = (string) $numRelacion;
    $_GET['accion'] = $accion;
} else {
    $accion = 'crear';
}

// El permiso de lo pedido se comprueba antes de consultar nada. Los de la pantalla están en la
// vista del listado. Un temporal solo se sabe si es de una relación nueva al leerlo: antes, basta
// con poder preparar algo.
$listado = $HostNombre . '/modulos/mod_relaciones/ListaRelaciones.php';
if ($accion === 'ver') {
    $permitido = permisoRelacion($ClasePermisos, 'ver', 'ListaRelaciones.php');
} elseif ($accion === 'crear' || $numRelacion > 0) {
    $permitido = permisoParaPreparar($ClasePermisos, $numRelacion);
} else {
    $permitido = puedePrepararAlgo($ClasePermisos);
}
if (!$permitido) {
    header('Location: ' . $listado);
    exit;
}

include_once $URLCom . '/controllers/Controladores.php';
include_once $URLCom . '/controllers/parametros.php';
include_once $URLCom . '/modulos/mod_relaciones/clases/Relaciones.php';
include_once $URLCom . '/modulos/mod_relaciones/clases/RelacionesTemporales.php';

$Controler = new ControladorComun;
$Controler->loadDbtpv($BDTpv);
$CRelaciones = new Relaciones();
$CTemporales = new RelacionesTemporales();
$CTipos = new RelacionesTipos();

$avisos = array();
$instalado = false;
$vigente = null;
$temporal = null;
$tipos = array();
$editable = false;
$puedeActivar = permisoRelacion($ClasePermisos, 'Activar', 'ListaRelaciones.php');

$estructura = $CRelaciones->estadoEstructura();
if (isset($estructura['error'])) {
    $avisos[] = $estructura['error'];
} elseif ($estructura['datos'] !== 'instalada') {
    $avisos[] = array('tipo' => 'danger', 'mensaje' => 'Las tablas de relaciones no están instaladas, o no son las que este módulo espera: avisa al administrador para que aplique la actualización de la base de datos.');
} else {
    $instalado = true;
}

if ($instalado && $idTemporal > 0) {
    $leido = $CTemporales->leer($idTemporal);
    if (isset($leido['error'])) {
        $avisos[] = $leido['error'];
    } elseif ($leido['datos'] === null) {
        $avisos[] = array('tipo' => 'warning', 'mensaje' => 'El cambio en preparación ya no existe: alguien lo canceló o lo activó. Vuelve al listado para ver cómo está la relación.');
    } else {
        $temporal = $leido['datos'];
        $numRelacion = (int) $temporal['numRelacion'];
        if (!permisoParaPreparar($ClasePermisos, $numRelacion)) {
            header('Location: ' . $listado);
            exit;
        }
        $editable = true;
    }
} elseif ($instalado && $accion === 'editar') {
    // Modificar una relación que ya tiene un cambio en preparación abre ese mismo.
    $leido = $CTemporales->deRelacion($numRelacion);
    if (isset($leido['error'])) {
        $avisos[] = $leido['error'];
    } else {
        $temporal = $leido['datos'];
        $editable = true;
    }
} elseif ($instalado && $accion === 'crear') {
    $editable = true;
}

if ($instalado && $numRelacion > 0) {
    $leida = $CRelaciones->vigente($numRelacion);
    if (isset($leida['error'])) {
        $avisos[] = $leida['error'];
        $editable = false;
    } elseif ($leida['datos'] === null) {
        $avisos[] = array('tipo' => 'warning', 'mensaje' => 'La relación ' . $numRelacion . ' no está vigente: está de baja o no existe. Vuelve al listado para elegir otra.');
        $editable = false;
    } else {
        $vigente = $leida['datos'];
    }
}

if ($instalado && $accion === 'ver' && $vigente !== null) {
    // Ver no enseña lo que se prepara: solo avisa de que existe.
    $abierto = $CTemporales->deRelacion($numRelacion);
    if (isset($abierto['datos']) && $abierto['datos'] !== null) {
        $avisos[] = array('tipo' => 'info', 'mensaje' => 'Hay un cambio en preparación de esta relación, guardado por '
            . $abierto['datos']['usuario'] . ' el ' . $abierto['datos']['fechaModificacion'] . ': lo que ves es lo que rige hasta que se active.');
    }
}

if ($instalado) {
    $leidos = $CTipos->listarTipos(false);
    if (isset($leidos['error'])) {
        $avisos[] = $leidos['error'];
    } else {
        foreach ($leidos['datos'] as $tipo) {
            $tipos[$tipo['id']] = $tipo;
        }
    }
}

// Lo que se prepara: lo guardado en el temporal; si no hay, una copia de la versión que rige;
// y en una relación nueva, nada.
$relacion = array(
    'idTemporal' => 0,
    'numRelacion' => $numRelacion,
    'idTipo' => 0,
    'nombre' => '',
    'descripcion' => '',
    'diasViabilidad' => '',
    'origenAjuste' => '',
    'fechaModificacion' => '',
    'lineas' => array(),
);
if ($temporal !== null) {
    $relacion['idTemporal'] = (int) $temporal['id'];
    $relacion['idTipo'] = (int) $temporal['idTipo'];
    foreach (array('nombre', 'descripcion', 'diasViabilidad', 'origenAjuste', 'fechaModificacion') as $campo) {
        $relacion[$campo] = (string) $temporal[$campo];
    }
    $relacion['lineas'] = $temporal['lineas'];
} elseif ($vigente !== null) {
    $relacion['idTipo'] = (int) $vigente['idTipo'];
    foreach (array('nombre', 'descripcion', 'diasViabilidad') as $campo) {
        $relacion[$campo] = (string) $vigente[$campo];
    }
    foreach ($vigente['lineas'] as $linea) {
        $relacion['lineas'][] = array(
            'idArticulo' => (int) $linea['idArticulo'],
            'sentido' => $linea['sentido'],
            'cantidad' => $linea['cantidad'] === null ? null : cantidadLegible($linea['cantidad']),
            'unidad' => $linea['unidad'],
        );
    }
}

// Los datos de lo que se prepara, a la vista para localizarlo en la base, como el temporal del
// albarán.
$cabecera = array(
    'relacion' => $relacion['numRelacion'] > 0 ? (string) $relacion['numRelacion'] : 'Nueva',
    'version' => $vigente === null ? '' : $vigente['version'] . ' (fila ' . (int) $vigente['id'] . ')',
    'temporal' => $temporal === null ? 'Sin guardar' : (string) (int) $temporal['id'],
    'inicio' => $temporal === null ? '' : $temporal['fechaInicio'],
    'guardado' => $temporal === null ? '' : $temporal['usuario'] . ', ' . $temporal['fechaModificacion'],
);

// Las líneas de lo que se prepara, ya marcadas, las compone la misma función que atiende la
// pantalla cuando cambian.
$htmlEntrada = '';
$htmlSalida = '';
$tipoCambio = '';
$lineasMarcadas = array();
if ($editable) {
    $datos = array('nombre' => $relacion['nombre'], 'descripcion' => $relacion['descripcion'], 'diasViabilidad' => $relacion['diasViabilidad']);
    $compuestas = htmlLineasRelacion($relacion['numRelacion'], $relacion['idTipo'], $relacion['lineas'], $datos, $CRelaciones, $ClasePermisos);
    if (isset($compuestas['error'])) {
        $avisos[] = $compuestas['error'];
    } else {
        $htmlEntrada = $compuestas['datos']['entrada'];
        $htmlSalida = $compuestas['datos']['salida'];
        $tipoCambio = $compuestas['datos']['tipoCambio'];
        $lineasMarcadas = $compuestas['datos']['lineas'];
    }
}

// La versión que rige, solo de lectura, con los datos de cada artículo.
$htmlVigenteEntrada = '';
$htmlVigenteSalida = '';
$vigenteLlevaCantidad = true;
if ($vigente !== null) {
    $articulos = $CRelaciones->datosDeArticulos(array_column($vigente['lineas'], 'idArticulo'));
    $lineasVigentes = array();
    foreach ($vigente['lineas'] as $linea) {
        $articulo = isset($articulos['datos'][$linea['idArticulo']]) ? $articulos['datos'][$linea['idArticulo']] : null;
        $linea['articulo_name'] = $linea['articulo'];
        $linea['crefTienda'] = $articulo === null ? null : $articulo['crefTienda'];
        $linea['codBarras'] = $articulo === null ? null : $articulo['codBarras'];
        $lineasVigentes[] = $linea;
    }
    if (isset($tipos[$vigente['idTipo']])) {
        $vigenteLlevaCantidad = $tipos[$vigente['idTipo']]['llevaCantidad'] == 1;
    }
    $htmlVigenteEntrada = htmlLineasVigentes($lineasVigentes, 'entrada', $vigenteLlevaCantidad);
    $htmlVigenteSalida = htmlLineasVigentes($lineasVigentes, 'salida', $vigenteLlevaCantidad);
}

// Las cajas de búsqueda y sus teclas, como las del albarán.
$VarJS = '';
if ($editable) {
    $ClasesParametros = new ClaseParametros(__DIR__ . '/parametros.xml');
    $VarJS = $Controler->ObtenerCajasInputParametros($ClasesParametros->getRoot());
}

// Para el navegador: los tipos que se pueden elegir y la forma de cada uno, con su límite.
$tiposParaJS = array();
foreach ($tipos as $tipo) {
    $tiposParaJS[$tipo['id']] = array(
        'nombre' => $tipo['nombre'],
        'forma' => $tipo['forma'],
        'llevaCantidad' => $tipo['llevaCantidad'] == 1,
    );
}
$tiposTexto = array(
    'alta' => 'una relación nueva',
    'composicion' => 'un cambio de composición',
    'proporciones' => 'un ajuste de proporciones',
    'datos' => 'un cambio de datos',
    '' => 'nada: no cambia nada',
);

if ($accion === 'ver') {
    $titulo = 'Relación ' . $numRelacion;
} elseif ($relacion['numRelacion'] > 0) {
    $titulo = 'Relación ' . $relacion['numRelacion'] . ': cambio en preparación';
} else {
    $titulo = 'Relación nueva';
}
$htmlVolver = $Controler->getHtmlLinkVolver('Volver');

include $URLCom . '/modulos/mod_relaciones/template/view_relacion.php';
