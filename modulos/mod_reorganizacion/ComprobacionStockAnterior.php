<?php

include_once './../../inicial.php';
include_once $URLCom . '/controllers/parametros.php';

if ($ClasePermisos->getAccion('admitir') == 0) {
    header('Location: ' . $HostNombre . '/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <?php include_once $URLCom . '/head.php'; ?>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_reorganizacion/comprobacion.js"></script>
</head>

<body>
    <?php include_once $URLCom . '/modulos/mod_menu/menu.php'; ?>

    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center">
                <h2>Comprobación de existencias — ejercicio anterior</h2>
            </div>
            <div class="col-md-12">
                <form id="formAdmitirComprobacionStock" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="ficheroComprobacionStock">Fichero de intercambio (XML) del ejercicio vigente</label>
                        <input class="form-control" type="file" id="ficheroComprobacionStock" name="ficheroComprobacionStock" accept=".xml" required>
                    </div>
                    <button type="button" class="btn btn-primary" id="btnComprobacionStockAnteriorAdmitir">Admitir</button>
                </form>

                <?php if ($ClasePermisos->getAccion('exportar') == 1) : ?>
                    <button id="btnComprobacionStockAnteriorExportar" type="button" class="btn btn-default" disabled>
                        <span class="glyphicon glyphicon-download-alt"></span> Descargar informe final
                    </button>
                <?php endif; ?>

                <div id="areaComprobacionStockAnterior"></div>
            </div>
        </div>
    </div>
</body>

</html>

<script type="text/javascript">
    $(function() {
        $('#btnComprobacionStockAnteriorAdmitir').on('click', admitirComprobacionStockAnterior);
        $('#btnComprobacionStockAnteriorExportar').on('click', exportarInformeComprobacionStockAnterior);
    });
</script>
