<!DOCTYPE html>
<html>

<head>
    <?php
    include_once './../../inicial.php';
    include_once $URLCom . '/head.php';
    include_once $URLCom . '/modulos/mod_balanza/clases/ClaseBalanza.php';

    $CBalanza = new ClaseBalanza($BDTpv);
    $balanzas = $CBalanza->todasBalanzas();
    $balanzas = $balanzas['datos'];




    ?>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_balanza/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/lib/js/tpvfoxSinExport.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/ui-helper.js"></script>

</head>

<body>
    <?php
    include_once $URLCom . '/modulos/mod_menu/menu.php';
    ?>

    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center">
                <h2> Balanzas: Editar y Añadir Balanzas </h2>
            </div>
            <div style="text-align:right;">
                <button
                    class="btn btn-outline-info mb-2 mb-md-0"
                    id="btnOcultarLista"
                    style="display: none;"
                    type="button"
                    data-toggle-panel="panelListaBalanzas"
                    data-other-panel="informacionBalanza"
                    data-expand-class="col-12 col-lg-2 mb-2"
                    data-collapse-class="col-12"
                    data-other-expand-class="col-12 col-lg-10"
                    data-other-collapse-class="col-12"
                    data-text-show="Mostrar lista de balanzas"
                    data-text-hide="Ocultar lista de balanzas">
                    Ocultar lista de balanzas
                </button>
            </div>
            <div class="col-sm-2" id="panelListaBalanzas">
                <div class="nav">
                    <h4> Balanzas</h4>
                    <h5> Opciones para una selección</h5>
                    <ul class="nav nav-pills nav-stacked">
                        <?php if ($ClasePermisos->getAccion("crear")): ?>
                            <li><a href="#section2" onclick="metodoClick('AgregarBalanza');">Añadir</a></li>
                        <?php endif; ?>
                        <?php if ($ClasePermisos->getAccion("modificar") || $ClasePermisos->getAccion("crear")): ?>
                            <li><a href="#section2" onclick="metodoClick('VerBalanza', 'balanza');">Modificar</a></li>
                        <?php endif; ?>
                        <?php if ($ClasePermisos->getAccion("crear")): ?>
                            <li>
                                <a href="#section2" onclick="metodoClick('EliminarBalanza', 'balanza');">
                                    Eliminar <span title="Solo disponible si la balanza no tiene plus asociados">*</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="nav">
                    Selecciona una balanza:
                    <table class="table table-striped table-hover">
                        <tr>
                            <td>Id</td>

                            <td>Nombre</td>
                        </tr>
                        <?php
                        $check = 0;

                        foreach ($balanzas as $balanza) {
                            $check = $check + 1;
                        ?>
                            <tr>

                                <td>
                                    <?php
                                    $check_name = 'checkBalanza' . $check;
                                    echo '<input type="checkbox" id="' . $check_name . '" name="' . $check_name . '" value="' . $balanza['idBalanza'] . '" class="check_balanza">';
                                    ?>
                                </td>
                                <td onclick="mostrarDatosBalanza(<?php echo $balanza['idBalanza']; ?>); mostrarBotonOcultarLista();">
                                    <?php echo $balanza['nombreBalanza']; ?>
                                </td>
                            </tr>
                        <?php

                        }

                        ?>
                    </table>
                </div>
            </div>
            <div class="col-md-10" id="informacionBalanza">
                <div class="col-md-12" id="infoBalanza">

                </div>
                <table class="table table-bordered table-hover tablaPrincipal">
                    <thead>

                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>

</html>