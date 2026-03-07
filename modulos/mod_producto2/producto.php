<?php
include_once './../../inicial.php';
include_once $URLCom . '/modulos/mod_producto/funciones.php';
include_once $URLCom . '/controllers/Controladores.php';
include_once $URLCom . '/modulos/mod_producto/clases/ClaseProductos.php';
include_once $URLCom . '/controllers/parametros.php';

// --- Instancias ---
$CTArticulos = new ClaseProductos($BDTpv);
$Controler   = new ControladorComun();
$Controler->loadDbtpv($BDTpv);

$ClasesParametros = new ClaseParametros($URLCom . '/modulos/mod_producto/parametros.xml');
$parametros       = $ClasesParametros->getRoot();
$conf_defecto     = $ClasesParametros->ArrayElementos('configuracion');

// --- Titulo y ID ---
$id     = 0;
$titulo = 'Productos: ';
if (isset($_GET['id'])) {
    $id      = (int) $_GET['id'];
    $titulo .= 'Modificar';
} else {
    $titulo .= 'Crear';
}

// --- Recibir POST (guardar) ---
$precioNuevo = false;
if ($_POST) {
    include_once __DIR__ . '/tareas/reciboPostProducto.php';
}

// --- Cargar datos del producto ---
$Producto = $CTArticulos->GetProducto($id);
$ivas     = $CTArticulos->getTodosIvas();
$posibles_estados_producto = $CTArticulos->posiblesEstados('articulos');
$Link_volver = $Controler->getHtmlLinkVolver('Volver');

// --- Volcar comprobaciones del POST sobre el producto ---
if (isset($preparados)) {
    foreach (['comprobaciones', 'codbarras', 'familias', 'insert_articulos'] as $clave) {
        if (isset($preparados[$clave])) {
            foreach ($preparados[$clave] as $c) {
                $CTArticulos->SetComprobaciones($c);
            }
        }
    }
}
$Producto['comprobaciones'] = $CTArticulos->GetComprobaciones();

// --- Proveedor principal ---
if (!isset($Producto['proveedores_costes'])) {
    $Producto['proveedores_costes'] = [];
} elseif (count($Producto['proveedor_principal']) > 0) {
    foreach ($Producto['proveedores_costes'] as $key => $proveedor) {
        if ($proveedor['idProveedor'] === $Producto['proveedor_principal']['idProveedor']) {
            $Producto['proveedores_costes'][$key]['principal'] = 'Si';
        }
    }
}

// --- Comprobar ultimo coste ---
$albaranes_ultimo   = $CTArticulos->getUltimoPrecioCompra($Producto['idArticulo']);
$proveedores_costes = comprobarUltimaCompraProveedor($Producto['proveedores_costes']);
$valor_actualizado  = 0.00;

if (isset($albaranes_ultimo) || isset($proveedores_costes['coste_ultimo'])) {
    $actualizado = false;

    if (isset($albaranes_ultimo) &&
        number_format($albaranes_ultimo, 2) != number_format($Producto['ultimoCoste'], 2)) {
        $Producto['comprobaciones'][] = [
            'tipo'    => 'warning',
            'mensaje' => 'Coste actualizado desde último albarán. Antes: ' . $Producto['ultimoCoste'] . ' → ' . $albaranes_ultimo,
            'dato'    => [$albaranes_ultimo, $Producto['ultimoCoste']],
        ];
        $valor_actualizado = $albaranes_ultimo;
        $actualizado = true;
    }

    if (isset($proveedores_costes['coste_ultimo']) && $actualizado &&
        number_format($proveedores_costes['coste_ultimo'], 2) != number_format($Producto['ultimoCoste'], 2)) {
        $Producto['comprobaciones'][] = [
            'tipo'    => 'warning',
            'mensaje' => 'El proveedor tiene tarifa distinta al coste actual: ' . $proveedores_costes['coste_ultimo'],
            'dato'    => [$proveedores_costes['coste_ultimo'], $Producto['ultimoCoste']],
        ];
    }

    if (isset($proveedores_costes['coste_ultimo']) && !$actualizado &&
        number_format($proveedores_costes['coste_ultimo'], 2) != number_format($Producto['ultimoCoste'], 2)) {
        $Producto['comprobaciones'][] = [
            'tipo'    => 'warning',
            'mensaje' => 'Coste actualizado desde proveedor. Antes: ' . $Producto['ultimoCoste'] . ' → ' . $proveedores_costes['coste_ultimo'],
            'dato'    => [$proveedores_costes['coste_ultimo'], $Producto['ultimoCoste']],
        ];
        $valor_actualizado = $proveedores_costes['coste_ultimo'];
    }

    if ($valor_actualizado != 0.00) {
        $Producto['ultimoCoste'] = $valor_actualizado;
    }
}

// --- Plugin Virtuemart (opcional) ---
$idVirtuemart        = 0;
$datosWebCompletos   = [];
$ObjVirtuemart       = null;
$tiendaWeb           = null;

if (isset($Producto['ref_tiendas'])) {
    foreach ($Producto['ref_tiendas'] as $ref) {
        if ($ref['idVirtuemart'] > 0) {
            $idVirtuemart = $ref['idVirtuemart'];
        }
    }
}
if ($CTArticulos->SetPlugin('ClaseVirtuemart') !== false && $ClasePermisos->getModulo('mod_virtuemart') == 1) {
    $ObjVirtuemart = $CTArticulos->SetPlugin('ClaseVirtuemart');
    $ClasesParametrosVirtuemart = new ClaseParametros($RutaServidor . $HostNombre . '/plugins/mod_producto/virtuemart/parametros.xml');
    $parametrosVirtuemart = $ClasesParametrosVirtuemart->getRoot();
    $OtrosVarJS = $Controler->ObtenerCajasInputParametros($parametrosVirtuemart);
    $tiendaWeb  = $ObjVirtuemart->getTiendaWeb();
    if (count($tiendaWeb) > 0 && $Producto['idArticulo'] > 0) {
        $datosWebCompletos = $ObjVirtuemart->datosCompletosTiendaWeb($idVirtuemart, $Producto['iva'], $Producto['idArticulo'], $tiendaWeb['idTienda']);
        if (isset($datosWebCompletos['errores'])) {
            $Producto['comprobaciones'][] = $datosWebCompletos['errores'];
        } elseif ($idVirtuemart > 0) {
            $CTArticulos->modificarEstadoWeb($id, $datosWebCompletos['datosWeb']['estado'], $tiendaWeb['idTienda']);
        }
    }
}

// --- Comunicacion con balanza (solo si precio cambio) ---
$ComunicacionBalanza = ['Comprobaciones' => []];
if ($precioNuevo && $Producto['tipo'] === 'peso') {
    include __DIR__ . '/tareas/comunicarBalanza.php';
}

// --- Tablas HTML de paneles ---
$relacion_balanza = [];
if ($ClasePermisos->getModulo('mod_balanza') == 1) {
    $relacion_balanza = $CTArticulos->obtenerTeclaBalanzas($id);
}

if ($id == 0) {
    $Producto['iva'] = $conf_defecto['iva_predeterminado'];
}

$htmlIvas            = htmlOptionIvas($ivas, $Producto['iva']);
$htmlTipo            = htmlTipoProducto($Producto['tipo']);
$htmlEstadosProducto = htmlOptionEstados($posibles_estados_producto, $Producto['estado']);
$borrar_ref_prov     = $ClasePermisos->getAccion('eliminarRefProveedores') == 1 ? 'Ok' : 'KO';

$htmltabla = [];
if (!isset($relacion_balanza['error'])) {
    $htmltabla[] = ['titulo' => 'Plu y Teclas en balanzas', 'html' => htmlTablaBalanza($relacion_balanza)];
}
$htmltabla[] = ['titulo' => 'Códigos de Barras',      'html' => htmlTablaCodBarras($Producto['codBarras'])];
$htmltabla[] = ['titulo' => 'Proveedores - Costes',   'html' => htmlTablaProveedoresCostes($proveedores_costes['proveedores'], $borrar_ref_prov)];
$htmltabla[] = ['titulo' => 'Albaranes de Compra',    'html' => htmlTablaAlbaranes($Producto['albaranes'])];
$htmltabla[] = ['titulo' => 'Pedidos de Compra',      'html' => htmlTablaPedidos($Producto['pedidos'])];
$htmltabla[] = ['titulo' => 'Familias',               'html' => htmlTablaFamilias($Producto['familias'], $id)];

$linkVirtuemart = 'Error al obtener datos';
if (isset($datosWebCompletos['datosWeb']) && !isset($datosWebCompletos['errores'])) {
    $linkVirtuemart = $datosWebCompletos['datosWeb']['estado'] == 0
        ? 'No está publicado'
        : $datosWebCompletos['htmlsLinksVirtuemart']['html_frontEnd'];
}
$htmltabla[] = ['titulo' => 'Productos en otras tiendas.', 'html' => htmlTablaRefTiendas($Producto['ref_tiendas'], $linkVirtuemart, $ClasePermisos->getAccion('eliminarRefWebDeProducto'))];
$htmltabla[] = ['titulo' => 'Histórico Precios.<span class="glyphicon glyphicon-info-sign" title="Últimos 15 cambios"></span>', 'html' => htmlTablaHistoricoPrecios($Producto['productos_historico'])];

// --- Variables JS ---
$OtrosVarJS = isset($OtrosVarJS) ? $OtrosVarJS : '';
$VarJS = $Controler->ObtenerCajasInputParametros($parametros) . $OtrosVarJS;

// --- Render vista ---
include __DIR__ . '/template/view_producto.php';
