<!DOCTYPE html>
<html>

<head>
    <?php
    include_once './../../inicial.php';
    include_once $URLCom . '/head.php';
    include_once $URLCom . '/modulos/mod_balanza/clases/ClaseBalanza.php';
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
    if (isset($_GET['id'])) {
        $titulo = "Modificar Balanza";
        $id = $_GET['id'];
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
        }
        // Asignar los PLUs si existen
        $buscarPlus = $CBalanza->pluDeBalanza($id, 'a.plu');
        if (isset($buscarPlus['datos'])) {
            $plus = $buscarPlus['datos'];
        }
    }
    $htmlplus = htmlTablaPlus($plus, $id);
    ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_balanza/funciones.js"></script>
    <script type="text/javascript">
        <?php echo $VarJS; ?>
    </script>
    <style>
        body {
            background: #f8f9fa;
        }

        .container {
            margin-top: 30px;
            margin-bottom: 30px;
            padding-left: 2rem;
            padding-right: 2rem;
        }

        #configCol,
        #plusCol {
            transition: all 0.3s;
        }

        .card.card-body {
            background: #f4f6f8;
        }

        .form-group label {
            margin-bottom: 0.4rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .panel-group {
            margin-top: 1rem;
        }
    </style>
</head>

<body>
    <?php include_once $URLCom . '/modulos/mod_menu/menu.php'; ?>
    <div class="container px-4">
        <h2 class="text-center mb-4"><?php echo $titulo; ?></h2>

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
            <a href="./ListaBalanzas.php" class="btn btn-link mb-2 mb-md-0">Volver Atrás</a>
            <input type="submit" value="<?php echo $id > 0 ? 'Modificar' : 'Guardar'; ?>"
                class="btn btn-primary mb-2 mb-md-0"
                onclick="<?php echo $id > 0 ? 'ModificarBalanza(' . $id . ')' : 'AgregarBalanza()'; ?>">
            <button
                class="btn btn-outline-info mb-2 mb-md-0"
                type="button"
                data-toggle-panel="configCol"
                data-other-panel="infoCol"
                data-expand-class="col-12 col-lg-2 mb-2"
                data-collapse-class="col-12"
                data-other-expand-class="col-12 col-lg-10"
                data-other-collapse-class="col-12"
                data-text-show="Mostrar configuración"
                data-text-hide="Ocultar configuración">
                Ocultar configuración
            </button>
        </div>

        <div class="row gx-4 gy-4">
            <!-- Col 1: Formulario -->
            <div class="col-12 col-lg-2 mb-2" id="configCol">
                <div class="p-3 bg-white rounded shadow-sm h-100">
                    <h5 class="mb-3">Datos de la balanza con ID: <?php echo $id ?></h5>
                    <form>
                        <div class="form-group">
                            <label for="nombreBalanza">Nombre de la balanza</label>
                            <input type="text" class="form-control" name="nombreBalanza" id="nombreBalanza" value="<?php echo $nombreBalanza; ?>">
                        </div>
                        <div class="form-group">
                            <label for="modeloBalanza">Modelo de la balanza</label>
                            <input type="text" class="form-control" name="modeloBalanza" id="modeloBalanza" value="<?php echo $modeloBalanza; ?>">
                        </div>
                        <div class="form-group">
                            <label for="secciones">Teclas en la balanza</label>
                            <select class="form-control" id="secciones" name="secciones">
                                <?php echo $htmlTecla; ?>
                            </select>
                        </div>
                        <?php
                        $mod_vista = array('vista' => 'ListaBalanzas.php', 'modulo' => 'mod_balanza');
                        if (isset($ClasePermisos) && $ClasePermisos->getAccion("crear", $mod_vista)):
                        ?>
                            <button class="btn btn-outline-secondary btn-sm mb-3" type="button" id="btnAbrirConfigAvanzada">
                                Añadir configuración de comunicación
                            </button>
                            <!-- Panel superpuesto de configuración avanzada -->
                            <div id="panelConfigAvanzada" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.35); z-index:1050;">
                                <div style="background:#fff; max-width:600px; margin:60px auto; border-radius:8px; box-shadow:0 2px 16px rgba(0,0,0,0.2); padding:32px 24px 24px 24px; position:relative;">
                                    <button type="button" class="close" aria-label="Cerrar" style="position:absolute; top:12px; right:16px; font-size:2rem; background:none; border:none;" onclick="cerrarConfigAvanzada()">&times;</button>
                                    <h5 class="mb-3">Configuración avanzada de comunicación</h5>
                                    <div class="card-body p-0">
                                        <div class="form-group">
                                            <label for="ipBalanza">IP de la balanza</label>
                                            <input type="text" class="form-control" name="ipBalanza" id="ipBalanza" value="<?php echo $ipBalanza ?? ''; ?>">
                                        </div>

                                        <!-- Grupo y Dirección -->
                                         <div class="form-row mb-3">
                                            <div class="form-group col-md-6">
                                                <label for="grupoBalanza">Grupo (2 dígitos)</label>
                                                <input type="text" maxlength="2" class="form-control" name="grupoBalanza" id="grupoBalanza" value="<?php echo $grupoBalanza ?? ''; ?>">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="direccionBalanza">Dirección (2 dígitos)</label>
                                                <input type="text" maxlength="2" class="form-control" name="direccionBalanza" id="direccionBalanza" value="<?php echo $direccionBalanza ?? ''; ?>">
                                            </div>
                                        </div>

                                        <div class="form-group form-check mb-2">
                                            <input type="checkbox" class="form-check-input" name="soloPLUS" id="soloPLUS" value="1" <?php echo (!empty($soloPLUS)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="soloPLUS">Permitir solo PLUs (solo enviar datos de peso que estén entre sus PLU)</label>
                                        </div>

                                        <div class="form-group mt-3">
                                            <label for="modoDirectorio">Modo de creación de directorio</label>
                                            <select class="form-control" id="modoDirectorio" name="modoDirectorio" onchange="toggleIpPcInput()">
                                                <option value="Balctrol">Balctrol</option>
                                                <option value="automatico">automatico</option>
                                            </select>
                                        </div>

                                        <div class="form-group mt-3 ipPcGroup" style="display: block;">
                                            <label for="ipPc">IP del PC</label>
                                            <input type="text" class="form-control ipPcGroup" id="ipPc" name="ipPc" placeholder="Ej: 192.168.1.100">
                                        </div>

                                        <!-- Serie H y Serie Tipo -->
                                         <div class="form-row mb-3">
                                            <div class="form-group col-md-6">
                                                <label for="serieH">Serie H</label>
                                                <select class="form-control" id="serieH" name="serieH">
                                                    <option value="">Seleccione...</option>
                                                    <option value="0" <?php echo (isset($balanza['serieH']) && $balanza['serieH'] == 'si') ? 'selected' : ''; ?>>Sí</option>
                                                    <option value="1" <?php echo (isset($balanza['serieH']) && $balanza['serieH'] == 'no') ? 'selected' : ''; ?>>No</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="serieTipo">Serie A, L o L tickets</label>
                                                <select class="form-control" id="serieTipo" name="serieTipo">
                                                    <option value="">Seleccione...</option>
                                                    <option value="0" <?php echo (isset($balanza['serieTipo']) && $balanza['serieTipo'] == 'A') ? 'selected' : ''; ?>>A</option>
                                                    <option value="1" <?php echo (isset($balanza['serieTipo']) && $balanza['serieTipo'] == 'L') ? 'selected' : ''; ?>>L</option>
                                                    <option value="2 tickets" <?php echo (isset($balanza['serieTipo']) && $balanza['serieTipo'] == 'L tickets') ? 'selected' : ''; ?>>L tickets</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <button type="button" class="btn btn-success" id="crearDirectorioBtn" onclick="CrearDirectorioBalanza(<?php echo $id ?>)">Crear directorio de balanza</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <!-- Col 2: PLUs Section -->
            <div class="col-12 col-lg-10" id="infoCol">
                <div class="p-3 bg-white rounded shadow-sm h-100">
                    <div class="panel-group">
                        <?php echo htmlPanelDesplegable(1, 'PLUs', $htmlplus, $id); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?php echo $HostNombre; ?>/controllers/ui-helper.js"></script>
    <?php
    echo '<script src="' . $HostNombre . '/plugins/modal/func_modal.js"></script>';
    include $RutaServidor . '/' . $HostNombre . '/plugins/modal/ventanaModal.php';
    ?>
</body>

</html>