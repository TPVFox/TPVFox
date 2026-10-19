<?php
// @ Objetivo
// Pintar la pantalla de relación. Todo lo que muestra llega ya preparado desde relacion.php:
// aquí no se calcula ni se consulta nada.

// Las columnas de cantidad y unidad solo se ven si el tipo las lleva.
$conCantidad = true;
if (isset($tipos[$relacion['idTipo']])) {
    $conCantidad = $tipos[$relacion['idTipo']]['llevaCantidad'] == 1;
}
$ocultarCantidad = $conCantidad ? '' : ' style="display:none"';
?>
<!DOCTYPE html>
<html>

<head>
    <?php include_once $URLCom . '/head.php'; ?>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script>
    <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_relaciones/funciones.js?v=<?php echo filemtime($URLCom . '/modulos/mod_relaciones/funciones.js'); ?>"></script>
    <?php if ($editable) { ?>
        <script type="text/javascript">
            // Lo que se prepara, y lo que el navegador necesita para aplicar el límite de cada forma.
            var relacion = <?php echo json_encode($relacion); ?>;
            var ultimasLineas = <?php echo json_encode($lineasMarcadas); ?>;
            var tipos = <?php echo json_encode((object) $tiposParaJS); ?>;
            var formas = <?php echo json_encode(RelacionesFormas::FORMAS); ?>;
            var textosTipoCambio = <?php echo json_encode($tiposTexto); ?>;
            var usuarioDeLaSesion = <?php echo json_encode((string) $Usuario['login']); ?>;
            <?php echo $VarJS; ?>
            $(document).ready(function () {
                prepararPantallaRelacion();
            });
        </script>
    <?php } ?>
</head>

<body>
    <?php
    include_once $URLCom . '/modulos/mod_menu/menu.php';
    ?>
    <div class="container">
        <?php
        foreach ($avisos as $aviso) {
            echo '<div class="alert alert-' . htmlspecialchars($aviso['tipo']) . '">' . htmlspecialchars($aviso['mensaje']) . '</div>';
        }
        ?>
        <h2 class="text-center"><?php echo htmlspecialchars($titulo); ?></h2>
        <div class="col-md-12">
            <div class="col-md-8">
                <?php echo $htmlVolver; ?>
                <?php if ($editable) { ?>
                    <button type="button" class="btn btn-primary" id="botonGuardar" onclick="guardarRelacion()">Guardar</button>
                    <?php if ($puedeActivar) { ?>
                        <button type="button" class="btn btn-success" id="botonActivar" onclick="activarRelacion()">Activar</button>
                    <?php } ?>
                <?php } ?>
            </div>
            <div class="col-md-4 text-right">
                <?php if ($editable) { ?>
                    <button type="button" class="btn btn-danger" id="botonCancelar" onclick="cancelarRelacion()">Cancelar</button>
                <?php } ?>
            </div>
        </div>
        <div class="col-md-12" id="avisosRelacion"></div>
        <?php if ($editable) { ?>
            <div class="col-md-12" id="datosTemporal">
                <?php
                // Los datos de lo que se prepara, de solo lectura, como el número de temporal del albarán.
                $campos = array(
                    'datoRelacion' => array('Relación', 'relacion', 6),
                    'datoVersion' => array('Versión que rige', 'version', 14),
                    'datoTemporal' => array('Temporal', 'temporal', 10),
                    'datoInicio' => array('Empezado', 'inicio', 18),
                    'datoGuardado' => array('Último guardado', 'guardado', 30),
                );
                foreach ($campos as $id => $campo) {
                    echo '<div class="col-md-2"><strong>' . $campo[0] . ':</strong><br>'
                        . '<input type="text" id="' . $id . '" size="' . $campo[2] . '" readonly value="'
                        . htmlspecialchars($cabecera[$campo[1]]) . '"></div>';
                }
                ?>
            </div>
        <?php } ?>

        <div class="col-md-12" id="datosRelacion">
            <?php if ($editable) { ?>
                <div class="col-md-3">
                    <label for="idTipo">Tipo</label>
                    <?php if ($relacion['numRelacion'] > 0) { ?>
                        <p class="form-control-static"><?php echo htmlspecialchars($tipos[$relacion['idTipo']]['nombre']); ?></p>
                    <?php } else { ?>
                        <select class="form-control" id="idTipo" onchange="cambiarTipo(this)">
                            <option value="0">Elige el tipo</option>
                            <?php
                            foreach ($tipos as $tipo) {
                                if ($tipo['estado'] === 'Activo') {
                                    $elegido = (int) $tipo['id'] === (int) $relacion['idTipo'] ? ' selected' : '';
                                    echo '<option value="' . (int) $tipo['id'] . '"' . $elegido . '>' . htmlspecialchars($tipo['nombre']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    <?php } ?>
                </div>
                <div class="col-md-3">
                    <label for="nombre">Nombre</label>
                    <input class="form-control" type="text" id="nombre" maxlength="100" value="<?php echo htmlspecialchars($relacion['nombre']); ?>" onchange="cambiarDatos()">
                </div>
                <div class="col-md-2">
                    <label for="diasViabilidad">Vida útil (días)</label>
                    <input class="form-control" type="text" id="diasViabilidad" size="5" value="<?php echo htmlspecialchars($relacion['diasViabilidad']); ?>" onchange="cambiarDatos()">
                </div>
                <?php if ($relacion['numRelacion'] > 0) { ?>
                    <div class="col-md-4">
                        <label for="origenAjuste">Origen del ajuste</label>
                        <input class="form-control" type="text" id="origenAjuste" maxlength="255" value="<?php echo htmlspecialchars($relacion['origenAjuste']); ?>" onchange="cambiarDatos()">
                    </div>
                <?php } ?>
                <div class="col-md-12">
                    <label for="descripcion">Descripción</label>
                    <textarea class="form-control" id="descripcion" rows="2" onchange="cambiarDatos()"><?php echo htmlspecialchars($relacion['descripcion']); ?></textarea>
                </div>
                <p class="col-md-12">Al activar constará como <strong id="tipoCambio"><?php echo htmlspecialchars($tiposTexto[$tipoCambio]); ?></strong>.</p>
            <?php } elseif ($vigente !== null) { ?>
                <p>
                    <strong>Tipo:</strong> <?php echo htmlspecialchars($vigente['tipo']); ?>.
                    <strong>Nombre:</strong> <?php echo htmlspecialchars((string) $vigente['nombre']); ?>.
                    <strong>Vida útil:</strong> <?php echo htmlspecialchars((string) $vigente['diasViabilidad']); ?> días.
                </p>
                <p><strong>Descripción:</strong> <?php echo htmlspecialchars((string) $vigente['descripcion']); ?></p>
                <p>
                    <strong>Versión:</strong> <?php echo htmlspecialchars($vigente['version']); ?>
                    (fila <?php echo (int) $vigente['id']; ?>), del <?php echo htmlspecialchars($vigente['fechaCreacion']); ?>.
                </p>
            <?php } ?>
        </div>

        <?php if ($editable) { ?>
            <div class="col-md-12" id="zonaEdicion">
                <table class="table" id="tablaBusqueda">
                    <thead>
                        <tr>
                            <th>Lado</th>
                            <th>Id artículo</th>
                            <th>Referencia</th>
                            <th>Cód. barras</th>
                            <th>Nombre</th>
                        </tr>
                        <tr id="Row0">
                            <td>
                                <select id="lado">
                                    <option value="entrada">Entrada</option>
                                    <option value="salida">Salida</option>
                                </select>
                            </td>
                            <td><input id="idArticulo" type="text" name="idArticulo" placeholder="idArticulo" data-obj="cajaidArticulo" size="6" value="" onkeydown="controlEventos(event)"></td>
                            <td><input id="Referencia" type="text" name="Referencia" placeholder="Referencia" data-obj="cajaReferencia" size="13" value="" onkeydown="controlEventos(event)"></td>
                            <td><input id="Codbarras" type="text" name="Codbarras" placeholder="Codbarras" data-obj="cajaCodBarras" size="12" value="" onkeydown="controlEventos(event)"></td>
                            <td><input id="Descripcion" type="text" name="Descripcion" placeholder="Descripcion" data-obj="cajaDescripcion" size="17" value="" onkeydown="controlEventos(event)"></td>
                        </tr>
                    </thead>
                </table>
                <?php foreach (array('Entrada' => $htmlEntrada, 'Salida' => $htmlSalida) as $lado => $filas) { ?>
                    <h3><?php echo $lado; ?></h3>
                    <table class="table table-striped" id="tabla<?php echo $lado; ?>">
                        <thead>
                            <tr>
                                <th>L</th>
                                <th>Id artículo</th>
                                <th>Referencia</th>
                                <th>Cód. barras</th>
                                <th>Nombre</th>
                                <th class="columnaCantidad" <?php echo $ocultarCantidad; ?>>Cantidad</th>
                                <th class="columnaCantidad" <?php echo $ocultarCantidad; ?>>Unidad</th>
                                <th>Cambio</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody><?php echo $filas; ?></tbody>
                    </table>
                <?php } ?>
            </div>
        <?php } ?>

        <?php if ($vigente !== null) { ?>
            <div class="col-md-12" id="zonaVigente">
                <h3>Versión que rige: <?php echo htmlspecialchars($vigente['version']); ?>, del <?php echo htmlspecialchars(substr($vigente['fechaCreacion'], 0, 10)); ?></h3>
                <?php foreach (array('Entrada' => $htmlVigenteEntrada, 'Salida' => $htmlVigenteSalida) as $lado => $filas) { ?>
                    <h4><?php echo $lado; ?></h4>
                    <table class="table table-bordered" id="vigente<?php echo $lado; ?>">
                        <thead>
                            <tr>
                                <th>L</th>
                                <th>Id artículo</th>
                                <th>Referencia</th>
                                <th>Cód. barras</th>
                                <th>Nombre</th>
                                <?php if ($vigenteLlevaCantidad) { ?>
                                    <th>Cantidad</th>
                                    <th>Unidad</th>
                                <?php } ?>
                            </tr>
                        </thead>
                        <tbody><?php echo $filas; ?></tbody>
                    </table>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
    <?php
    if ($editable) {
        echo '<script src="' . $HostNombre . '/plugins/modal/func_modal.js"></script>';
        include $RutaServidor . '/' . $HostNombre . '/plugins/modal/ventanaModal.php';
    }
    ?>
</body>

</html>
