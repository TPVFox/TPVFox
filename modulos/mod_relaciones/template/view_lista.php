<?php
// @ Objetivo
// Pintar el listado de productos relacionados. Todo lo que muestra llega ya preparado
// desde ListaRelaciones.php: aquí no se calcula ni se consulta nada.
?>
<!DOCTYPE html>
<html>

<head>
    <?php include_once $URLCom . '/head.php'; ?>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_relaciones/funciones.js?v=<?php echo filemtime($URLCom . '/modulos/mod_relaciones/funciones.js'); ?>"></script>
</head>

<body>
    <?php
    include_once $URLCom . '/modulos/mod_menu/menu.php';
    if ($avisoDeLaPantalla !== null) {
        echo '<div class="alert alert-' . $avisoDeLaPantalla['tipo'] . '">' . htmlspecialchars($avisoDeLaPantalla['mensaje']) . '</div>';
    }
    foreach ($avisos as $aviso) {
        echo '<div class="alert alert-' . htmlspecialchars($aviso['tipo']) . '">' . htmlspecialchars($aviso['mensaje']) . '</div>';
    }
    ?>
    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center">
                <h2>Productos relacionados</h2>
            </div>
            <div class="col-sm-3">
                <h4> Opciones generales</h4>
                <?php
                if ($puedeCrear) {
                    echo '<a class="btn btn-default" href="./relacion.php">Añadir</a>';
                }
                echo '<button class="btn btn-default" onclick="metodoClick(' . "'" . 'Ver' . "'" . ')">Ver</button>';
                if ($puedeModificar) {
                    echo '<button class="btn btn-default" onclick="metodoClick(' . "'" . 'Modificar' . "'" . ')">Modificar</button>';
                }
                if ($puedeCambiarEstado) {
                    // El estado al que lleva depende de lo marcado: las vigentes pasan a Baja y las de baja a Activo.
                    echo '<button class="btn btn-default" onclick="metodoClick(' . "'" . 'cambiarEstado' . "'" . ')">Cambiar estado</button>';
                }
                ?>
                <?php if ($puedeCrear || $puedeModificar) { ?>
                    <h4>Relaciones abiertas</h4>
                    <table class="table table-striped" id="relacionesAbiertas">
                        <thead>
                            <tr>
                                <th>Nº</th>
                                <th>Relación</th>
                                <th>Quién</th>
                                <th>Cuándo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($relacionesAbiertas as $abierta) {
                                $url = 'relacion.php?tActual=' . (int) $abierta['id'];
                                $td = '<td style="cursor:pointer" onclick="redireccionA(' . "'" . $url . "'" . ')">';
                                $numero = $abierta['numRelacion'] === null ? 'nueva' : (int) $abierta['numRelacion'];
                                $nombre = $abierta['nombre'] !== null && $abierta['nombre'] !== '' ? $abierta['nombre'] : $abierta['tipo'];
                                echo '<tr>' . $td . $numero . '</td>'
                                    . $td . htmlspecialchars((string) $nombre) . '</td>'
                                    . $td . htmlspecialchars((string) $abierta['usuario']) . '</td>'
                                    . $td . htmlspecialchars(substr($abierta['fechaModificacion'], 0, 16)) . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                <?php } ?>
            </div>
            <div class="col-md-9">
                <p>
                    -Relaciones encontradas:
                    <span id="relacionesEncontradas"><?php echo $CantidadRegistros; ?></span>
                </p>
                <?php echo $htmlPG; ?>
                <div class="row">
                    <div class="col-md-6">
                        <?php echo $htmlBuscar; ?>
                    </div>
                    <div class="col-md-6 text-right">
                        <?php echo $htmlFiltrar; ?>
                    </div>
                </div>
                <div>
                    <table class="table table-bordered table-hover" id="tablaRelaciones">
                        <thead>
                            <tr>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th>Nº <?php echo $htmlOrdenarNumero; ?></th>
                                <th>RELACIÓN</th>
                                <th>TIPO</th>
                                <th>VERSIÓN</th>
                                <th>ESTADO <?php echo $htmlOrdenarEstado; ?></th>
                                <th>FECHA <?php echo $htmlOrdenarFecha; ?></th>
                                <th>PRODUCTOS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($filasDeLaPagina as $fila) {
                            ?>
                                <tr>
                                    <td>
                                        <input class="Check" type="checkbox" value="<?php echo (int) $fila['numRelacion']; ?>" data-nombre="<?php echo htmlspecialchars($fila['relacion']); ?>" data-estado="<?php echo htmlspecialchars($fila['estado']); ?>">
                                    </td>
                                    <td>
                                        <?php if ($puedeModificar && $fila['estado'] === 'Activo') { ?>
                                            <a title="Modificar" class="glyphicon glyphicon-pencil" href="./relacion.php?id=<?php echo (int) $fila['numRelacion']; ?>&amp;accion=editar"></a>
                                        <?php } ?>
                                    </td>
                                    <td><a title="Ver" class="glyphicon glyphicon-eye-open" href="./relacion.php?id=<?php echo (int) $fila['numRelacion']; ?>&amp;accion=ver"></a></td>
                                    <td><?php echo (int) $fila['numRelacion']; ?></td>
                                    <td><?php echo htmlspecialchars($fila['relacion']); ?></td>
                                    <td><?php echo htmlspecialchars($fila['tipo']); ?></td>
                                    <td><?php echo htmlspecialchars($fila['version']); ?></td>
                                    <td><?php echo htmlspecialchars($fila['estado']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($fila['fecha'], 0, 10)); ?></td>
                                    <td><?php echo (int) $fila['numProductos']; ?></td>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
