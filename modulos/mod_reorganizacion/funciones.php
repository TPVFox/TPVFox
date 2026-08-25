<?php
// Archivo de funciones para el modulo de reorganizacion
// modulos/mod_reorganizacion/funciones.php


// Obtener datos de productos para albaran de cierre
function obtenerDatosProductoAlbaranCierre($arrayIdsArticulos, $idFamilia = null)
{
    global $CReorganizar;
    $productos = array();
    foreach ($arrayIdsArticulos as $key => $idArticulo) {
        $resultado = $CReorganizar->articulosAlbaranCierre($idArticulo['idArticulo']);
        if (empty($resultado)) {
            continue;
        }
        if ($idFamilia !== null) {
            // Maximo 100 caracters
            $resultado['cdetalle'] = substr($resultado['cdetalle'], 0, 80);
            $resultado['cdetalle'] .= ' CIERRE ID: ' . $idFamilia;
        }
        $datosProducto = [
            'idArticulo' => $idArticulo['idArticulo'],
            'ncant' => -$idArticulo['stockOn'],
            'nunidades' => -$idArticulo['stockOn'],
            'nfila' => $key,
            'ccodbar' => $resultado['ccodbar'],
            'cdetalle' => $resultado['cdetalle'],
            'ultimoCoste' => $resultado['costSiva'],
            'iva' => $resultado['iva'],
            'estado' => 'Activo',
            'cref' => '',
            'ref_prov' => '',
            'idPedido' => 0
        ];
        $productos[] = $datosProducto;
    }
    return $productos;
}

function generarCierreAlbaran($productos, $familia_id = null, $idProveedor = 1, $serieAlbaranCierre = 'C')
{
    $idTienda = $_SESSION['tiendaTpv']['idTienda'];
    $ano = $_SESSION['tiendaTpv']['ano'];
    $idUsuario = $_SESSION['usuarioTpv']['id'];

    $fechaCierre = $ano . '-12-31 00:00:00';

    $totalSinIva = 0;
    $totalIva = 0;
    $basesYivas = array();
    foreach ($productos as $producto) {
        $iva = $producto['iva'];
        $precioSinIva = $producto['ultimoCoste'] * $producto['ncant'];
        $ivaProducto = $precioSinIva * ($producto['iva'] / 100);
        $totalSinIva += $precioSinIva;
        $totalIva += $ivaProducto;
        if (!isset($basesYivas[$iva])) {
            $basesYivas[$iva] = array('base' => 0, 'iva' => 0);
        }
        $basesYivas[$iva]['base'] += $precioSinIva;
        $basesYivas[$iva]['iva'] += $ivaProducto;
    }

    $datosAlbaran = array(
        'Numalpro' => '',
        'fecha' => $fechaCierre,
        'idTienda' => $idTienda,
        'idUsuario' => $idUsuario,
        'idProveedor' => $idProveedor,
        'estado' => 'Guardado',
        'total_siniva' => $totalSinIva,
        'total' => $totalSinIva + $totalIva,
        'suNumero' => $familia_id !== null ? 'ID-' . $familia_id . '#' . $serieAlbaranCierre : 'SINID#' . $serieAlbaranCierre,
        'formaPago' => '',
        'fechaVenci' => ''
    );

    $datosAlbaran['productos'] = json_encode($productos);
    $datosAlbaran['DatosTotales']['desglose'] = $basesYivas;
    global $BDTpv;
    include_once '../mod_compras/clases/albaranesCompras.php';
    $AlbaranesCompras = new AlbaranesCompras($BDTpv);
    $AlbaranesCompras->AddAlbaranGuardado($datosAlbaran, 0);
}


function htmlFamilias($busqueda, $dedonde, $idcaja, $familias = array())
{
    // @ Objetivo:
    // Montar el hmtl para mostrar con los proveeodr si los hubiera.
    // @ parametros:
    //      $busqueda -> El valor a buscar,aunque puede venir vacio..
    //      $dedonde  -> Nos indica de donde viene. ()
    $resultado = array();
    $resultado['encontrados'] = count($familias);
    $resultado['html'] = '<label>Busqueda Familia en ' . $dedonde . '</label>'
        . '<input id="cajaBusquedafamilia" name="valorfamilia" placeholder="Buscar"'
        . 'size="13" data-obj="cajaBusquedafamilia" value="' . $busqueda
        . '" onkeydown="controlEventos(event)" type="text">';

    if (count($familias) > 10) {
        $resultado['html'] .= '<span> Se muestra 10 familias de ' . count($familias) . '</span>';
    }
    $resultado['html'] .= '<table class="table table-striped"><thead>'
        . ' <th></th> <th>Id</th><th>Nombre</th><th>Familia Padre</th><th>Beneficio</th></thead><tbody>';
    if (count($familias) > 0) {
        foreach ($familias as $key => $familia) {
            $resultado['html'] .= '<tr id="Fila_' . $key
                . '" class="FilaModal" onclick="buscarFamilia(' . "'" . $dedonde . "'" . ' , '
                . "'id_familia'" . ', ' . $familia['idFamilia'] . ', ' . "'popup'" . ');" >'
                . '<td id="C' . $key . '_Lin" >'
                . '<input id="N_'
                . $key . '" name="filafamilia" '
                . 'data-obj="idN" onkeydown="controlEventos(event)" type="image"  alt="">'
                . '<span  class="glyphicon glyphicon-plus-sign agregar"></span></td>'
                . '<td>' . htmlspecialchars($familia['idFamilia'], ENT_QUOTES) . '</td>'
                . '<td>' . htmlentities($familia['familiaNombre'], ENT_QUOTES) . '</td>'
                . '<td>' . $familia['familiaPadre'] . '</td>'
                . '<td>' . $familia['beneficiomedio'] . '</td>'
                . '</tr>';
            if ($key === 10) {
                // Solo mostramos 10 como máximo.
                break;
            }
        }
    } else {
        // No se encontro nada con esa busqueda.
        $resultado['html'] .= ' <div class="alert alert-warning">No se encontro ninguna familia, para esa busqueda</div> ';
    }
    $resultado['html'] .= '</tbody></table>';
    // Ahora generamos objetos de filas.
    // Objetos queremos controlar.
    return $resultado;
}

function guardarConfiguracionXML($xml, $datos, $seccion)
{
    switch ($seccion) {
        case 'cierre_stock_anual':
            validarXMLCierrreStockAnual($xml, $datos, $seccion);
            break;
        default:
            return false; // Sección no reconocida
    }
}

function validarXMLCierrreStockAnual($xml, $datos, $seccion)
{
    // Validamos que el XML tenga la estructura esperada.
    if (!isset($xml->ajustes_globales)) {
        throw new Exception("El XML no tiene la sección 'ajustes_globales'");
    }
    if (!isset($xml->ajustes_globales->proveedor)) {
        throw new Exception("El XML no tiene la sección 'proveedor' dentro de 'ajustes_globales'");
    }
    if (!isset($xml->ajustes_globales->num_productos)) {
        throw new Exception("El XML no tiene la sección 'num_productos' dentro de 'ajustes_globales'");
    }
    if (!isset($xml->ajustes_globales->reescribir_albaran)) {
        throw new Exception("El XML no tiene la sección 'reescribir_albaran' dentro de 'ajustes_globales'");
    }
    if (!isset($xml->ajustes_globales->serie_albaran)) {
        throw new Exception("El XML no tiene la sección 'serie_albaran' dentro de 'ajustes_globales'");
    }
    if (!isset($xml->ajustes_globales->serie_albaran->apertura) || !isset($xml->ajustes_globales->serie_albaran->cierre)) {
        throw new Exception("El XML no tiene las secciones 'apertura' y 'cierre' dentro de 'serie_albaran'");
    }
    if (!isset($xml->familias_excluidas)) {
        // Si no existe el nodo de familias_excluidas, lo creamos para evitar errores posteriores.
        $xml->addChild('familias_excluidas');
    }

    // Validamos que los datos recibidos tengan la estructura esperada.
    if (!isset($datos['idProveedor']) || !isset($datos['proveedor'])) {
        throw new Exception("Los datos de proveedor deben incluir 'idProveedor' y 'proveedor'");
    }
    if (!isset($datos['reescribirAlbaran'])) {
        throw new Exception("Los datos deben incluir 'reescribirAlbaran'");
    }
    if (!isset($datos['serieApertura']) || !isset($datos['serieCierre'])) {
        throw new Exception("Los datos de serie_albaran deben incluir 'apertura' y 'cierre'");
    }
    if (!isset($datos['numProductos'])) {
        throw new Exception("Los datos deben incluir 'numProductos'");
    }
    if (!isset($datos['familiasExcluidas']) || !is_array($datos['familiasExcluidas'])) {
        throw new Exception("Los datos deben incluir 'familiasExcluidas' como un array");
    }

    // Si todo es correcto, procedemos a guardar la configuración.
    guardarConfiguracionXMLCierreStockAnual($xml, $datos);
}

function guardarConfiguracionXMLCierreStockAnual($xml, $datos)
{
    // Comprobamos si el valor de proveedor ha cambiado, si es así actualizamos el XML.
    if ((string)$xml->ajustes_globales->proveedor['id'] !== (string)$datos['idProveedor']) {
        $xml->ajustes_globales->proveedor['id'] = $datos['idProveedor'];
    }
    if ((string)$xml->ajustes_globales->proveedor !== (string)$datos['proveedor']) {
        $xml->ajustes_globales->proveedor = $datos['proveedor'];
    }
    // Transformar el varlor bool rescribirAlbaran a true o false en el XML para evitar confusiones.
    $reescribirAlbaranValor = $datos['reescribirAlbaran'] ? 'true' : 'false';
    if ((string)$xml->ajustes_globales->reescribir_albaran !== $reescribirAlbaranValor) {
        $xml->ajustes_globales->reescribir_albaran = $reescribirAlbaranValor;
    }
    if ((string)$xml->ajustes_globales->serie_albaran->apertura !== (string)$datos['serieApertura']) {
        $xml->ajustes_globales->serie_albaran->apertura = $datos['serieApertura'];
    }
    if ((string)$xml->ajustes_globales->serie_albaran->cierre !== (string)$datos['serieCierre']) {
        $xml->ajustes_globales->serie_albaran->cierre = $datos['serieCierre'];
    }
    if ((string)$xml->ajustes_globales->num_productos !== (string)$datos['numProductos']) {
        $xml->ajustes_globales->num_productos = $datos['numProductos'];
    }
    // Para facilitar el proceso de familias excluidas hacemos unset y creamos el nodo de nuevo con los valores actualizados.
    unset($xml->familias_excluidas);
    $familiasExcluidas = $xml->addChild('familias_excluidas');
    foreach ($datos['familiasExcluidas'] as $familia) {
        $familiaNode = $familiasExcluidas->addChild('familia', $familia['nombre']);
        $familiaNode->addAttribute('id', $familia['id']);
    }
}

function validarDatosXML($xml, $seccion)
{
    // @ Objetivo:
    // Validar que los datos que puedan ser inconsistente del XML sigan vigentes y que no se hayan eliminado o modificado por error.
    // @ Retorno:
    // Un array con el resultado de la validación indicando si es correcto o no y un mensaje descriptivo en caso de que no sea correcto.
    $respuesta = ['error' => false, 'mensaje' => ''];
    switch ($seccion) {
        case 'cierre_stock_anual':
            // Validamos que el idProveedro exista en nuestra base de datos de proveedores.
            $proveedorId = (string)$xml->ajustes_globales->proveedor['id'];
            $proveedorNombre = (string)$xml->ajustes_globales->proveedor;
            $proveedorValido = validarProveedor($proveedorId, $proveedorNombre);
            if (!$proveedorValido) {
                $respuesta['error'] = true;
                $respuesta['mensaje'] .= "El proveedor con ID: {$proveedorId} y Nombre: {$proveedorNombre} no es válido. Por favor revise la configuración. \n";
            }
            // Validamos que las familias excluidas existan en nuestra base de datos de familias.
            $familiasExcluidas = [];
            foreach ($xml->familias_excluidas->familia as $familia) {
                $familiasExcluidas[] = [
                    'id' => (string)$familia['id'],
                    'nombre' => (string)$familia
                ];
            }
            foreach ($familiasExcluidas as $familia) {
                $familiaValida = validarFamilia($familia['id'], $familia['nombre']);
                if (!$familiaValida) {
                    $respuesta['error'] = true;
                    $respuesta['mensaje'] .= "La familia excluida con ID: {$familia['id']} y Nombre: {$familia['nombre']} no es válida. Por favor revise la configuración. \n";
                }
            }
            // Si todo es correcto, retornamos que la validación es exitosa.
            if (!isset($respuesta['error']) || $respuesta['error'] === false) {
                $respuesta['error'] = false;
                $respuesta['mensaje'] .= "La configuración XML es válida.";
            }
            return $respuesta;
        default:
            $respuesta['error'] = true;
            $respuesta['mensaje'] .= "Sección no reconocida para validación.";
            return $respuesta;
    }
}

function validarDatosFormulario($datos, $seccion)
{
    // @ Objetivo:
    // Validar que los datos recibidos del formulario sean correctos antes de generar el XML.
    // @ Retorno:
    // Un array con el resultado de la validación indicando si es correcto o no y un mensaje descriptivo en caso de que no sea correcto.
    $respuesta = ['error' => false, 'mensaje' => ''];
    switch ($seccion) {
        case 'cierre_stock_anual':
            // Validamos que el proveedor seleccionado sea válido. $datosi['idProveedor'] y $datos['proveedor'].
            $proveedorId = (string)$datos['idProveedor'];
            $proveedorNombre = (string)$datos['proveedor'];
            $proveedorValido = validarProveedor($proveedorId, $proveedorNombre);
            if (!$proveedorValido) {
                $respuesta['error'] = true;
                $respuesta['mensaje'] .= "El proveedor seleccionado con ID: {$proveedorId} y Nombre: {$proveedorNombre} no es válido. Por favor revise la selección. \n";
            }
            // Validamos que la familia seleccionada sea valida. $datos['idFamilia'] y $datos['familia'].
            $familiaId = (string)$datos['idFamilia'];
            $familiaNombre = (string)$datos['familia'];
            if ($familiaId == '' && $familiaNombre == '') {
                // Si no se ha seleccionado ninguna familia es un error no se puede continuar
                $respuesta['error'] = true;
                $respuesta['mensaje'] .= "No se ha seleccionado ninguna familia. Por favor seleccione una familia para continuar. \n";
            }
            if (!empty($familiaId) && !empty($familiaNombre)) {
                $familiaValida = validarFamilia($familiaId, $familiaNombre);
                if (!$familiaValida) {
                    $respuesta['error'] = true;
                    $respuesta['mensaje'] .= "La familia seleccionada con ID: {$familiaId} y Nombre: {$familiaNombre} no es válida. Por favor revise la selección. \n";
                }
            }
            // Si todo es correcto, retornamos que la validación es exitosa.
            if (!isset($respuesta['error']) || $respuesta['error'] === false) {
                $respuesta['error'] = false;
                $respuesta['mensaje'] .= "Los datos del formulario son válidos.";
            }
            return $respuesta;
        default:
            $respuesta['error'] = true;
            $respuesta['mensaje'] .= "Sección no reconocida para validación.";
            return $respuesta;
    }
}

function validarProveedor($idProveedor, $nombreProveedor)
{
    global $URLCom, $BDTpv;
    // Comprobar en la base de datos que el proveedor con ese ID existe y que su nombre coincide con el configurado en el XML.
    include_once $URLCom . '/clases/Proveedores.php';
    // Buscamos el proveedor por ID
    $Proveedores = new Proveedores($BDTpv);
    $proveedor = $Proveedores->buscarProveedorId($idProveedor);
    if (isset($proveedor['error']) || empty($proveedor)) {
        return false;
    }
    // Comprobamos que el nombre del proveedor coincide
    if ($proveedor['nombrecomercial'] !== $nombreProveedor) {
        return false;
    }
    return true;
}

function validarFamilia($idFamilia, $nombreFamilia)
{
    // Comprobar en la base de datos que la familia con ese ID existe y que su nombre coincide con el configurado en el XML.
    global $URLCom, $BDTpv;
    include_once $URLCom . '/modulos/mod_reorganizacion/clases/ClaseReorganizar.php';
    $CReorganizar = new ClaseReorganizar($BDTpv);
    $familia = $CReorganizar->buscarFamiliaId($idFamilia);
    if (isset($familia['error']) || empty($familia)) {
        return false;
    }
    // Comprobamos que el nombre de la familia coincide
    if ($familia['familiaNombre'] !== $nombreFamilia) {
        return false;
    }
    return true;
}


function normalizarProductos($idsProductos)
{
    $idsProductosUnicos = [];

    foreach ($idsProductos as $idsProducto) {
        if (!isset($idsProductosUnicos[$idsProducto['idArticulo']])) {
            $idsProductosUnicos[$idsProducto['idArticulo']] = $idsProducto;
        }
    }
    return array_values($idsProductosUnicos);
}

function htmlAlertaComprobacionStock($mensaje)
{
    // @ Objetivo
    // Dar el aviso de que la comprobación no se pudo hacer, ya montado. Se compone
    // aquí y no en el navegador porque el motivo puede venir de un fichero que sube
    // el operador, y ahí tiene que escaparse antes de llegar a la pantalla.
    // @ Parametros
    //      $mensaje -> string, el motivo tal cual lo da quien no pudo continuar.
    // @ Devolvemos
    //      string, el aviso montado.
    return '<div class="alert alert-danger">' . htmlspecialchars($mensaje) . '</div>';
}

function htmlTablaComprobacionStock($composicion, $rama)
{
    // @ Objetivo
    // Dar la tabla de resultados de la comprobación a quien la pida. La plantilla
    // es la misma para los dos ejercicios y devuelve lo que monta; esta función
    // solo la localiza, para que las tareas no dependan de dónde esté ni de desde
    // qué directorio se las llame.
    // @ Parametros
    //      $composicion -> array, la salida de ClaseComprobacionStockEmision::componer().
    //      $rama -> string, 'vigente' o 'anterior'.
    // @ Devolvemos
    //      string, la tabla montada.
    return include __DIR__ . '/template/view_comprobacion_stock.php';
}

function generarAlbaranesConLimite(array $productos, string $identificador, int $limite, int $idProveedor, string $serieAlbaranCierre)
{
    $total = count($productos);
    if ($total > $limite) {
        $partes = ceil($total / $limite);

        // Calcular tamaño aproximado de cada parte para distribuir los productos de manera uniforme
        $tamanoBase = floor($total / $partes);
        $resto = $total % $partes;

        $offset = 0;
        for ($i = 0; $i < $partes; $i++) {
            // Distribuimos el resto 1 a 1 en los primeros albaranes
            $tamanoActual = $tamanoBase + ($i < $resto ? 1 : 0);
            $productosParte = array_slice($productos, $offset, $tamanoActual);
            $idParte = $identificador . '-P' . ($i + 1);
            generarCierreAlbaran($productosParte, $idParte, $idProveedor, $serieAlbaranCierre);
            $offset += $tamanoActual;
        }
    } else {
        generarCierreAlbaran($productos, $identificador, $idProveedor, $serieAlbaranCierre);
    }
}
