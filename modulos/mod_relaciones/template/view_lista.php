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
                <?php // Las acciones todavía no tienen pantalla: ocupan su sitio, desactivadas. ?>
                <button class="btn btn-default" disabled title="Disponible con la pantalla de relación">Añadir</button>
                <button class="btn btn-default" disabled title="Disponible con la pantalla de relación">Ver</button>
                <button class="btn btn-default" disabled title="Disponible con la pantalla de relación">Modificar</button>
                <?php
                if ($puedeCambiarEstado) {
                    // Hoy el único estado al que se puede cambiar una relación es la baja.
                    echo '<button class="btn btn-default" onclick="metodoClick(' . "'" . 'cambiarEstado' . "','" . 'Baja' . "'" . ')">Cambiar estado</button>';
                }
                ?>
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
                                        <input class="Check" type="checkbox" value="<?php echo (int) $fila['numRelacion']; ?>" data-nombre="<?php echo htmlspecialchars($fila['relacion']); ?>">
                                    </td>
                                    <td><span title="Editar: disponible con la pantalla de relación" class="glyphicon glyphicon-pencil text-muted"></span></td>
                                    <td><span title="Ver: disponible con la pantalla de relación" class="glyphicon glyphicon-eye-open text-muted"></span></td>
                                    <td><?php echo (int) $fila['numRelacion']; ?></td>
                                    <td><?php echo htmlspecialchars($fila['relacion']); ?></td>
                                    <td><?php echo htmlspecialchars($fila['tipo']); ?></td>
                                    <td><?php echo htmlspecialchars($fila['version']); ?></td>
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
