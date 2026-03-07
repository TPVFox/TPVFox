<?php
include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_producto/funciones.php';
include_once $URLCom . '/controllers/Controladores.php';
include_once $URLCom . '/modulos/mod_producto/clases/ClaseProductos.php';
include_once $URLCom . '/plugins/paginacion/ClasePaginacion.php';
include_once $URLCom . '/controllers/parametros.php';
include_once $URLCom . '/modulos/mod_familia/clases/ClaseFamilias.php';
include_once $URLCom . '/clases/Proveedores.php';
include_once __DIR__ . '/clases/ClaseSeleccionProductos.php';

// --- Instancias ---
$CTArticulos = new ClaseProductos($BDTpv);
$CFamilia    = new ClaseFamilias($BDTpv);
$CProveedor  = new Proveedores();
$Controler   = new ControladorComun();
$Controler->loadDbtpv($BDTpv);

// --- Parametros y configuracion de usuario ---
$ClasesParametros = new ClaseParametros($URLCom . '/modulos/mod_producto/parametros.xml');
$parametros       = $ClasesParametros->getRoot();
$conf_defecto     = $ClasesParametros->ArrayElementos('configuracion');
$conf_defecto['filtro']->valor = 'No';

// --- Seleccion de productos (cache XML por usuario) ---
$botonSeleccion = 0;
$CSeleccion     = new ClaseSeleccionProductos((int) $Usuario['id'], __DIR__ . '/cache');
$ids_seleccion  = $CSeleccion->getIds();
$prod_seleccion = [
    'Items'   => $ids_seleccion,
    'NItems'  => count($ids_seleccion),
    'display' => count($ids_seleccion) === 0 ? 'style="display:none"' : '',
];

$configuracion = $Controler->obtenerConfiguracion($conf_defecto, 'mod_productos', $Usuario['id']);

if (!isset($configuracion['tipo_configuracion'])) {
    $CTArticulos->SetComprobaciones([
        'tipo'    => 'danger',
        'dato'    => 'Fichero Parametros.xml',
        'mensaje' => 'Error al cargar configuracion.',
    ]);
}

$htmlConfiguracion = HtmlListadoCheckMostrar($configuracion['mostrar_lista']);
if (isset($htmlConfiguracion['error'])) {
    $CTArticulos->SetComprobaciones([
        'tipo'    => 'danger',
        'dato'    => 'Fichero Parametros.xml',
        'mensaje' => $htmlConfiguracion['error'],
    ]);
}

// --- Filtro por estado ---
$option_sinFiltrar = '<option value="Sin Filtrar">Sin Filtrar</option>';
if (!isset($configuracion['estado_filtro'])) {
    $configuracion['estado_filtro'] = '';
    $option_sinFiltrar = '<option value="Sin Filtrar" selected>Sin Filtrar</option>';
}
$posibles_estados = $CTArticulos->posiblesEstados('articulos');
$htmlEstadosProducto = $option_sinFiltrar . htmlOptionEstados($posibles_estados, $configuracion['estado_filtro']);
$filtro_estado = $configuracion['estado_filtro'] !== '' ? 'a.estado="' . $configuracion['estado_filtro'] . '"' : '';

// --- Filtro por familia (GET ?familia=ID) ---
$filtro_familia_nombre = '';
$filtro_familia_ids    = [];
$idFamiliaFiltro = isset($_GET['familia']) ? (int) $_GET['familia'] : 0;
if ($idFamiliaFiltro !== 0) {
    if ($idFamiliaFiltro < 0) {
        // Sin familia
        $famProd = $CFamilia->buscarProductosSinFamilias();
        $filtro_familia_nombre = 'Sin familia';
    } else {
        $famProd = $CFamilia->buscarProductosFamilias($idFamiliaFiltro);
        $famNombre = $CFamilia->buscarPorId($idFamiliaFiltro);
        $filtro_familia_nombre = $famNombre['datos'][0]['familiaNombre'] ?? '';
    }
    if (isset($famProd['datos'])) {
        foreach ($famProd['datos'] as $fp) {
            $filtro_familia_ids[] = (int) $fp['idArticulo'];
        }
    }
}

// --- Paginacion ---
$NPaginado = new PluginClasePaginacion(__FILE__);
$NPaginado->SetCamposControler([$htmlConfiguracion['campo_defecto']]);

$filtro = $NPaginado->GetFiltroWhere();
$CantidadRegistros = 0;

// Añadir filtro de familia al WHERE si aplica
$cond_familia = '';
if (!empty($filtro_familia_ids)) {
    $ids_str      = implode(',', $filtro_familia_ids);
    $cond_familia = "(a.idArticulo IN ($ids_str))";
} elseif ($idFamiliaFiltro !== 0) {
    // Familia sin productos → forzar 0 resultados
    $cond_familia = '(1=0)';
}

// Combinar todos los filtros
$condiciones = array_filter([$filtro_estado, $cond_familia]);
if (trim($filtro) !== '') {
    if (!empty($condiciones)) {
        $filtro .= ' AND ' . implode(' AND ', $condiciones);
    }
    $CantidadRegistros = count($CTArticulos->obtenerProductos($htmlConfiguracion['campo_defecto'], compact('filtro')));
} elseif (!empty($condiciones)) {
    $filtro = 'WHERE ' . implode(' AND ', $condiciones);
    $CantidadRegistros = count($CTArticulos->obtenerProductos($htmlConfiguracion['campo_defecto'], compact('filtro')));
} else {
    $CantidadRegistros = $CTArticulos->GetNumRows();
}

if ($prod_seleccion['NItems'] > 0 && $configuracion['filtro']->valor === 'Si') {
    $NPaginado->SetCantidadRegistros($prod_seleccion['NItems']);
} else {
    $NPaginado->SetCantidadRegistros($CantidadRegistros);
}

$htmlPG   = '';
$productos = [];

if ($CantidadRegistros > 0 || $prod_seleccion['NItems'] > 0) {
    $htmlPG = $NPaginado->htmlPaginado();
    if ($configuracion['filtro']->valor === 'Si' && $prod_seleccion['NItems'] > 0) {
        $botonSeleccion = 1;
        $ids_sel = implode(',', $prod_seleccion['Items']);
        $filtro .= (trim($filtro) !== '') ? " AND (a.idArticulo IN ($ids_sel))" : " WHERE (a.idArticulo IN ($ids_sel))";
    }
    $limite   = $NPaginado->GetLimitConsulta();
    $productos = $CTArticulos->obtenerProductos($htmlConfiguracion['campo_defecto'], compact('filtro', 'limite'));
}

if (isset($productos['error'])) {
    $CTArticulos->SetComprobaciones([
        'tipo'    => 'danger',
        'dato'    => $productos['error'],
        'mensaje' => $productos['consulta'],
    ]);
    $productos = [];
}

// --- Proveedores para filtro ---
$todosProveedores = $CProveedor->todosProveedores();
if (isset($todosProveedores['error'])) {
    $CTArticulos->SetComprobaciones([
        'tipo'    => 'warning',
        'dato'    => $todosProveedores['error'],
        'mensaje' => $todosProveedores['error'],
    ]);
    $todosProveedores = [];
}

// --- Plugin Virtuemart (opcional) ---
$script_ObjVirtuemart = '';
$tiendaWeb = null;
$ObjVirtuemart = null;
if ($CTArticulos->SetPlugin('ClaseVirtuemart') !== false) {
    $ObjVirtuemart        = $CTArticulos->SetPlugin('ClaseVirtuemart');
    $script_ObjVirtuemart = $ObjVirtuemart->htmlJava();
    $tiendaWeb            = $ObjVirtuemart->getTiendaWeb();
}

// --- Variables JS de parametros ---
$VarJS = $Controler->ObtenerCajasInputParametros($parametros);
$id_tienda_principal = $Tienda['idTienda'];

// --- Render de la vista ---
include __DIR__ . '/template/view_lista.php';
