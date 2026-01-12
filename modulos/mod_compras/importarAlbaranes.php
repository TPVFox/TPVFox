<?php


function modalImportarAlbaranes()
{
    $html = '<p>Seleccione el metodo para importar los albaranes de proveedor:</p>
            <div class="d-grid gap-2">
                <button class="btn btn-default" type="button" id="btnImportarXMLCambioAno" onclick="ampliarInformacionImportar(\'CambioAno\')">Cambio de Año</button>
                <button class="btn btn-default" type="button" id="btnImportarXMLProveedor" onclick="ampliarInformacionImportar(\'ImportarAlbaran\')">Importar Albaranes XML</button>
                <button class="btn btn-default" type="button" id="btnImportarCSVProveedor" onclick="ampliarInformacionImportar(\'ImportarCSV\')">Importar Albaranes CSV</button>
            </div>
            <hr>
            <div id="areaImportacionAlbaranes"></div>';
    return $html;
}
