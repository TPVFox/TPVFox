<!DOCTYPE html>
<html>

<head>
    <?php
    include_once './../../inicial.php';
    include_once $URLCom . '/head.php';
    include_once $URLCom . '/modulos/mod_balanza/clases/ClaseBalanza.php';
    include_once $URLCom . '/modulos/mod_balanza/clases/ClaseComunicacionBalanza.php';
    include_once $URLCom . '/modulos/mod_balanza/funciones.php';
    include_once($URLCom . '/controllers/parametros.php');
    include_once $URLCom . '/controllers/Controladores.php';
    $ClasesParametros = new ClaseParametros('parametros.xml');
    $Controler = new ControladorComun;
    $Controler->loadDbtpv($BDTpv);
    $CBalanza = new ClaseBalanza($BDTpv);
    $titulo = "Crear Balanza";
    $id = 0;
    $nombreBalanza = "";
    $modeloBalanza = "";
    $plus = array();
    $parametros = $ClasesParametros->getRoot();
    $VarJS = $Controler->ObtenerCajasInputParametros($parametros);
    $puls = array();
    $htmlTecla = htmlTecla("si");
    $ipBalanza = '';
    $grupoBalanza = '';
    $direccionBalanza = '';
    $soloPLUS = 0;
    $balanza = [];
    // La IP del PC y las series se guardan en el fichero de configuración del driver
    $configDriver = ['ipPc' => '', 'serieTipo' => '', 'serieH' => ''];
    // Para ver o para editar; sin decirlo, para editar, como siempre
    $soloVer = (isset($_GET['accion']) && $_GET['accion'] === 'ver');
    $input_disabled = '';
    if ($soloVer) {
        $input_disabled = 'disabled';
    }
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
    }
    if ($id > 0) {
        $titulo = "Modificar Balanza";
        if ($soloVer) {
            $titulo = "Ver Balanza";
        }
        $datosBalanza = $CBalanza->datosBalanza($id);

        // Asignar todos los datos de la balanza si existen
        if (!empty($datosBalanza['datos'][0])) {
            $balanza = $datosBalanza['datos'][0];
            $nombreBalanza = $balanza['nombreBalanza'] ?? "";
            $modeloBalanza = $balanza['modelo'] ?? "";
            $htmlTecla = htmlTecla($balanza['conSeccion'] ?? "si");
            // Asignar configuración avanzada si existe
            $ipBalanza = $balanza['IP'] ?? '';
            $grupoBalanza = $balanza['Grupo'] ?? '';
            $direccionBalanza = $balanza['Dirección'] ?? '';
            $soloPLUS = !empty($balanza['soloPLUS']) ? $balanza['soloPLUS'] : 0;
            $CComunicacion = new ClaseComunicacionBalanza();
            $CComunicacion->setRutaBalanza($RutaServidor . $rutatmp . nombreCarpetaBalanza($nombreBalanza, $id));
            $configDriver = $CComunicacion->leerConfiguracionDibal();
        }
        // Asignar los PLUs si existen
        $buscarPlus = $CBalanza->pluDeBalanza($id, 'a.plu');
        if (isset($buscarPlus['datos'])) {
            $plus = $buscarPlus['datos'];
            $VarJS = 'var plusBalanza = ' . json_encode($plus) . ';';
        }
    }
    $htmlplus = htmlTablaPlus($plus, $id, $soloVer);

    // Lo que se ofrece, con los permisos del listado
    $mod_vista = array('vista' => 'ListaBalanzas.php', 'modulo' => 'mod_balanza');
    $permisoCrear = (isset($ClasePermisos) && $ClasePermisos->getAccion("crear", $mod_vista) == 1);
    $permisoModificar = (isset($ClasePermisos) && $ClasePermisos->getAccion("modificar", $mod_vista) == 1);
    $ofrecerGuardar = false;
    if (!$soloVer) {
        if ($id > 0) {
            $ofrecerGuardar = $permisoModificar;
        } else {
            $ofrecerGuardar = $permisoCrear;
        }
    }
    $ofrecerEliminar = (!$soloVer && $id > 0 && $permisoCrear && empty($plus));
    $ofrecerComunicacion = $permisoCrear;
    ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_balanza/funciones.js?v=<?php echo filemtime(__DIR__ . '/funciones.js'); ?>"></script>
    <script type="text/javascript">
        <?php echo $VarJS; ?>
    </script>
</head>

<body>
    <?php include_once $URLCom . '/modulos/mod_menu/menu.php'; ?>
    <div id="divAlertas"></div>
    <div class="container">
        <h2 class="text-center mb-4"><?php echo $titulo; ?></h2>

        <a href="./ListaBalanzas.php">Volver Atrás</a>
        <?php if ($ofrecerGuardar) { ?>
            <input type="submit" value="<?php echo $id > 0 ? 'Modificar' : 'Guardar'; ?>" class="btn btn-primary"
                onclick="<?php echo $id > 0 ? 'ModificarBalanza(' . $id . ')' : 'AgregarBalanza()'; ?>">
        <?php } ?>
        <?php if ($ofrecerEliminar) { ?>
            <button class="btn btn-danger" type="button" onclick="confirmarYEliminarBalanza(<?php echo $id; ?>)">Eliminar balanza</button>
        <?php } ?>

        <div class="row">
            <!-- Datos de la balanza -->
            <div class="col-md-6">
                <h4>Datos de la balanza</h4>
                <p>ID: <?php echo $id; ?></p>
                <div class="form-group">
                    <label for="nombreBalanza">Nombre de la balanza</label>
                    <input type="text" class="form-control" name="nombreBalanza" id="nombreBalanza" value="<?php echo escapar($nombreBalanza); ?>" <?php echo $input_disabled; ?>>
                </div>
                <div class="form-group">
                    <label for="modeloBalanza">Modelo de la balanza</label>
                    <input type="text" class="form-control" name="modeloBalanza" id="modeloBalanza" value="<?php echo escapar($modeloBalanza); ?>" <?php echo $input_disabled; ?>>
                </div>
                <div class="form-group">
                    <label for="secciones">Teclas en la balanza</label>
                    <select class="form-control" id="secciones" name="secciones" <?php echo $input_disabled; ?>>
                        <?php echo $htmlTecla; ?>
                    </select>
                </div>
            </div>
            <!-- Comunicación con la balanza -->
            <?php if ($ofrecerComunicacion) { ?>
                <div class="col-md-6">
                    <h4>Comunicación</h4>
                    <div class="form-group">
                        <label for="ipBalanza">IP de la balanza</label>
                        <input type="text" class="form-control" name="ipBalanza" id="ipBalanza" value="<?php echo escapar($ipBalanza); ?>" <?php echo $input_disabled; ?>>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="grupoBalanza">Grupo (2 dígitos)</label>
                            <input type="text" maxlength="2" class="form-control" name="grupoBalanza" id="grupoBalanza" value="<?php echo escapar($grupoBalanza); ?>" <?php echo $input_disabled; ?>>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="direccionBalanza">Dirección (2 dígitos)</label>
                            <input type="text" maxlength="2" class="form-control" name="direccionBalanza" id="direccionBalanza" value="<?php echo escapar($direccionBalanza); ?>" <?php echo $input_disabled; ?>>
                        </div>
                    </div>
                    <div class="checkbox">
                        <label><input type="checkbox" name="soloPLUS" id="soloPLUS" value="1" <?php echo (!empty($soloPLUS)) ? 'checked' : ''; ?> <?php echo $input_disabled; ?>>
                            Permitir solo PLUs (solo enviar datos de peso que estén entre sus PLU)</label>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="modoDirectorio">Modo de creación de directorio</label>
                            <select class="form-control" id="modoDirectorio" name="modoDirectorio" onchange="toggleIpPcInput()" <?php echo $input_disabled; ?>>
                                <option value="Balctrol">Balctrol</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6 ipPcGroup">
                            <label for="ipPc">IP del PC</label>
                            <input type="text" class="form-control ipPcGroup" id="ipPc" name="ipPc" placeholder="Ej: 192.168.1.100" value="<?php echo escapar($configDriver['ipPc']); ?>" <?php echo $input_disabled; ?>>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="serieH">Serie H</label>
                            <select class="form-control" id="serieH" name="serieH" <?php echo $input_disabled; ?>>
                                <option value="">Seleccione...</option>
                                <option value="1" <?php echo ($configDriver['serieH'] === '1') ? 'selected' : ''; ?>>Sí</option>
                                <option value="0" <?php echo ($configDriver['serieH'] === '0') ? 'selected' : ''; ?>>No</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="serieTipo">Serie A, L o L tickets</label>
                            <select class="form-control" id="serieTipo" name="serieTipo" <?php echo $input_disabled; ?>>
                                <option value="">Seleccione...</option>
                                <option value="0" <?php echo ($configDriver['serieTipo'] === '0') ? 'selected' : ''; ?>>A</option>
                                <option value="1" <?php echo ($configDriver['serieTipo'] === '1') ? 'selected' : ''; ?>>L</option>
                                <option value="2" <?php echo ($configDriver['serieTipo'] === '2') ? 'selected' : ''; ?>>L tickets</option>
                            </select>
                        </div>
                    </div>
                    <?php if (!$soloVer) { ?>
                        <button type="button" class="btn btn-success" id="crearDirectorioBtn" onclick="CrearDirectorioBalanza(<?php echo $id ?>)">Crear directorio de balanza</button>
                        <button type="button" class="btn btn-primary" id="guardarConfigAvanzadaBtn" onclick="guardarConfigAvanzada(<?php echo $id ?>)">Guardar configuración</button>
                        <button type="button" class="btn btn-warning" id="volcadoCompletoBtn" onclick="volcadoCompleto(<?php echo $id ?>)">Volcado completo de la balanza</button>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>

        <!-- Las teclas, añadir una y los productos al peso sin tecla -->
        <div class="panel-group">
            <?php
            echo htmlPanelDesplegable(1, 'Teclas', $htmlplus, true);
            if (!$soloVer && $id > 0) {
                $htmlAnadirTecla = '<button id="agregar" type="button" class="btn btn-success btn-xs" onclick="mostrarTablaPluAdd(' . $id . ')">'
                    . '<span class="glyphicon glyphicon-plus"></span> Añadir</button>'
                    . '<div id="addPlu"></div>';
                echo htmlPanelDesplegable(2, 'Añadir tecla', $htmlAnadirTecla);
                $htmlArticulosPeso = '<button id="agregarArtPeso" type="button" class="btn btn-info btn-xs" onclick="mostrarTablaArtPesoAdd(' . $id . ')">'
                    . '<span class="glyphicon glyphicon-plus"></span> Ver los productos al peso sin tecla</button>'
                    . '<div id="addArticuloPeso"></div>';
                echo htmlPanelDesplegable(3, 'Productos al peso sin tecla', $htmlArticulosPeso);
            }
            ?>
        </div>
    </div>
    <?php
    echo '<script src="' . $HostNombre . '/plugins/modal/func_modal.js"></script>';
    include $RutaServidor . '/' . $HostNombre . '/plugins/modal/ventanaModal.php';
    ?>
</body>

</html>
