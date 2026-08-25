<?php

include_once './../../inicial.php';
include_once $URLCom . '/controllers/parametros.php';

if ($ClasePermisos->getAccion('ver') == 0) {
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
                <h2>Comprobación de existencias — ejercicio vigente</h2>
            </div>
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="chkComprobacionStockVigenteModoEstricto">
                        Modo estricto (trunca a cero el saldo de apertura de cada producto)
                    </label>
                </div>

                <?php if ($ClasePermisos->getAccion('exportar') == 1) : ?>
                    <button id="btnComprobacionStockVigenteExportar" type="button" class="btn btn-default" disabled>
                        <span class="glyphicon glyphicon-download-alt"></span> Descargar fichero de intercambio
                    </button>
                <?php endif; ?>

                <div id="areaComprobacionStockVigente">Cargando…</div>
            </div>
        </div>
    </div>
</body>

</html>

<script type="text/javascript">
    $(function() {
        cargarComprobacionStockVigente();
        $('#chkComprobacionStockVigenteModoEstricto').on('change', cargarComprobacionStockVigente);
        $('#btnComprobacionStockVigenteExportar').on('click', exportarComprobacionStockVigenteXML);
    });
</script>
