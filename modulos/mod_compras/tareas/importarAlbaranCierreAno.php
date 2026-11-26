<?php
header('Content-Type: application/json');

try {
    require_once $URLCom . '/clases/ClaseIOXML.php';
    require_once $URLCom . '/modulos/mod_compras/clases/ClaseAlbaranCompraXML.php';

    $input = 'inputAlbaranCierreAno';
    $xsd   = $URLCom . '/modulos/mod_compras/albaran_compra_v1.xsd';

    // Solo se acepta un fichero XML; lo subido se borra al terminar, se importe o no
    $ficheroSubido = '';
    if (isset($_FILES[$input]['name'])) {
        if (strtolower(pathinfo($_FILES[$input]['name'], PATHINFO_EXTENSION)) !== 'xml') {
            throw new Exception('El fichero a importar tiene que ser un XML.');
        }
        $ficheroSubido = $RutaServidor . $rutatmp . '/' . basename($_FILES[$input]['name']);
    }

    $io = ClaseIOXML::desdeSubida(
        $input,
        $xsd,
        $RutaServidor . $rutatmp . '/'
    );

    // Cargar y validar XML
    $xml = $io->cargar();

    $albaranXML = new ClaseAlbaranCompraXML();
    $albaran = $albaranXML->simpleXMLToArrayCambioAno($xml);

    $albaran['idUsuario'] = $_SESSION['usuarioTpv']['id'];

    global $BDTpv;
    include_once $URLCom . '/modulos/mod_compras/clases/albaranesCompras.php';

    $AlbaranesCompras = new AlbaranesCompras($BDTpv);
    // Comprobar si el provedor ya tiene un albaran con SuNumero igual
    if ($AlbaranesCompras->ExisteSuNumeroProveedor($albaran['suNumero'], $albaran['idProveedor'])) {
        throw new Exception('Ya existe un albarán de apertura con el mismo número para este proveedor.');
    }else {
        $AlbaranesCompras->AddAlbaranGuardado($albaran, 0);
    }

    // El fichero subido ya no hace falta
    if ($ficheroSubido !== '' && is_file($ficheroSubido)) {
        unlink($ficheroSubido);
    }
    echo json_encode([
        'ok' => true,
        'message' => 'Albarán importado correctamente'
    ]);
    exit;

} catch (Throwable $e) {
    // El fichero subido tampoco se queda si la importación falla
    if (isset($ficheroSubido) && $ficheroSubido !== '' && is_file($ficheroSubido)) {
        unlink($ficheroSubido);
    }
    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'message' => $e->getMessage(),
        'code' => 'IMPORT_XML_ERROR'
    ]);
    exit;
}
