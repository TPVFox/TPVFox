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

function modalCambioAno()
{
    $html = '<p>Con este metodo importaras un albaran de cierre del año anterior al actual y se creara un albaran de apertura nuevo en este año.</p>

            <form id="formImportarAlbaranCierreAno" name="formImportarAlbaranCierreAno" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="inputAlbaranCierreAno" class="form-label">Albaran Cierre Año Anterior (XML)</label>
                    <input class="form-control" type="file" id="inputAlbaranCierreAno" name="inputAlbaranCierreAno" accept=".xml" required>
                </div>
                <button type="button" class="btn btn-primary" id="btnImportarAlbaranCierreAno" onclick="importarAlbaranCierreAno()">Importar Albaran Cierre Año Anterior</button>
            </form>
            <hr>
            <div id="areaAlbaranCierreSeleccionado"></div>';
    return $html;
}
