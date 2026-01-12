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

    public static function simpleXMLToArray(SimpleXMLElement $xml): array
    {
        $albaran = [];

        // Cabecera
        $cab = $xml->Cabecera;
        $albaran['Numalbpro'] = (string) $cab->Numero;
        $albaran['Su_numero'] = (string) $cab->SuNumero;
        $albaran['Fecha'] = (string) $cab->Fecha;
        $albaran['idTienda'] = (int) $cab->IdTienda;
        $albaran['idProveedor'] = (int) $cab->IdProveedor;
        $albaran['estado'] = (string) $cab->Estado;

        if (isset($cab->FechaVencimiento)) {
            $albaran['FechaVencimiento'] = (string) $cab->FechaVencimiento;
        }

        // Líneas
        $albaran['Productos'] = [];
        foreach ($xml->Lineas->Linea as $linea) {
            $producto = [];
            $producto['id'] = (int) $linea->attributes()->idInterno;
            $producto['idArticulo'] = (int) $linea->IdArticulo;
            $producto['cdetalle'] = (string) $linea->Descripcion;
            $producto['ncant'] = (float) $linea->Cantidad;
            $producto['nunidades'] = (string) $linea->Unidades;
            $producto['ultimoCoste'] = (float) $linea->PrecioUnitario;
            $producto['iva'] = (float) $linea->IVA;
            $producto['nfila'] = (int) $linea->Fila;

            if (isset($linea->CodigoBarras)) {
                $producto['ccodbar'] = (string) $linea->CodigoBarras;
            }

            if (isset($linea->ReferenciaProveedor)) {
                $producto['ref_prov'] = (string) $linea->ReferenciaProveedor;
            }

            $albaran['Productos'][] = $producto;
        }

        // Totales
        $albaran['Datostotales'] = [];
        $albaran['Datostotales']['desglose'] = [];
        foreach ($xml->Totales->DesgloseIVA as $d) {
            $tipo = (float) $d->Tipo;
            $albaran['Datostotales']['desglose'][$tipo] = [
                'base' => (float) $d->Base,
                'iva' => (float) $d->Cuota,
                'BaseYiva' => (float) $d->Total,
            ];
        }
        $albaran['Datostotales']['subivas'] = (float) $xml->Totales->TotalIVA;
        $albaran['Datostotales']['total'] = (float) $xml->Totales->TotalDocumento;
        return $albaran;
    }
}
