<?php
class ClaseAlbaranCompraXML
{
    public static function arrayToSimpleXML(array $albaran): SimpleXMLElement
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><AlbaranCompra></AlbaranCompra>'
        );

        $xml->addAttribute('idOrigen', $albaran['Su_numero']);

        /* =====================
     * Meta
     * ===================== */
        $meta = $xml->addChild('Meta');
        $meta->addChild('Version', '1.0');
        $meta->addChild('Tipo', 'COMPRA');
        $meta->addChild('FechaExportacion', date('c'));

        /* =====================
     * Cabecera
     * ===================== */
        $cab = $xml->addChild('Cabecera');
        $cab->addChild('Numero', $albaran['Numalbpro']);
        $cab->addChild('SuNumero', $albaran['Su_numero']);
        $cab->addChild('Fecha', date('c', strtotime($albaran['Fecha'])));
        $cab->addChild('IdTienda', $albaran['idTienda']);
        $cab->addChild('IdProveedor', $albaran['idProveedor']);
        $cab->addChild('Estado', $albaran['estado']);

        if (!empty($albaran['FechaVencimiento']) && $albaran['FechaVencimiento'] !== '0000-00-00') {
            $cab->addChild('FechaVencimiento', $albaran['FechaVencimiento']);
        }

        /* =====================
     * Líneas
     * ===================== */
        $lineas = $xml->addChild('Lineas');

        foreach ($albaran['Productos'] as $producto) {
            $linea = $lineas->addChild('Linea');
            $linea->addAttribute('idInterno', $producto['id']);

            $linea->addChild('IdArticulo', $producto['idArticulo']);
            $linea->addChild('Descripcion', htmlspecialchars($producto['cdetalle']));
            $linea->addChild('Cantidad', $producto['ncant']);
            $linea->addChild('Unidades', $producto['nunidades']);
            $linea->addChild('PrecioUnitario', $producto['ultimoCoste']);
            $linea->addChild('IVA', number_format($producto['iva'], 2, '.', ''));
            $linea->addChild('Fila', $producto['nfila']);

            if (!empty($producto['ccodbar'])) {
                $linea->addChild('CodigoBarras', $producto['ccodbar']);
            }

            if (!empty($producto['ref_prov'])) {
                $linea->addChild('ReferenciaProveedor', $producto['ref_prov']);
            }
        }

        /* =====================
     * Totales
     * ===================== */
        $tot = $xml->addChild('Totales');

        foreach ($albaran['Datostotales']['desglose'] as $tipo => $datos) {
            $d = $tot->addChild('DesgloseIVA');
            $d->addChild('Tipo', number_format($tipo, 2, '.', ''));
            $d->addChild('Base', $datos['base']);
            $d->addChild('Cuota', $datos['iva']);
            $d->addChild('Total', $datos['BaseYiva']);
        }

        $tot->addChild('TotalIVA', $albaran['Datostotales']['subivas']);
        $tot->addChild('TotalDocumento', $albaran['Datostotales']['total']);

        return $xml;
    }
}
