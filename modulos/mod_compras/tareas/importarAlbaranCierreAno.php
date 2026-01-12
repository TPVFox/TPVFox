<?php
header('Content-Type: application/json');

try {
    require_once $URLCom . '/clases/ClaseIOXML.php';
    require_once $URLCom . '/modulos/mod_compras/clases/ClaseAlbaranCompraXML.php';

    $input = 'inputAlbaranCierreAno';
    $xsd   = $URLCom . '/modulos/mod_compras/albaran_compra_v1.xsd';

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

    echo json_encode([
        'ok' => true,
        'message' => 'Albarán importado correctamente'
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'message' => $e->getMessage(),
        'code' => 'IMPORT_XML_ERROR'
    ]);
    exit;
}
