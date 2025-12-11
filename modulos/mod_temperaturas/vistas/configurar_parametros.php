<?php
include_once($URLCom . "/inicial.php");
include_once($URLCom . "/configuracion.php");
include_once($URLCom . '/controllers/parametros.php');
include_once $URLCom . '/controllers/Controladores.php';

$ClasesParametros = new ClaseParametros('parametros.xml');
$Controler = new ControladorComun;
$Controler->loadDbtpv($BDTpv);

// Procesar formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $configuracion = $ClasesParametros->getNode('configuracion');
    
    // Actualizar registros diarios
    $ClasesParametros->setNodeValue('configuracion/registros_diarios', $_POST['registros_diarios']);
    
    // Actualizar registros individuales
    for ($i = 1; $i <= 4; $i++) {
        $ClasesParametros->setNodeAttribute("configuracion/registro{$i}", 'hora', $_POST["registro{$i}_hora"] ?? '');
        $ClasesParametros->setNodeAttribute("configuracion/registro{$i}", 'forzar', $_POST["registro{$i}_forzar"] ?? 'No');
        $ClasesParametros->setNodeAttribute("configuracion/registro{$i}", 'descripcion', $_POST["registro{$i}_descripcion"] ?? '');
    }
    
    // Actualizar alertas
    $ClasesParametros->setNodeAttribute('configuracion/alerta_maxima', 'valor', $_POST['alerta_maxima']);
    $ClasesParametros->setNodeAttribute('configuracion/alerta_minima', 'valor', $_POST['alerta_minima']);
    
    // Otras configuraciones
    $ClasesParametros->setNodeAttribute('configuracion/dias_historico', 'valor', $_POST['dias_historico']);
    $ClasesParametros->setNodeAttribute('configuracion/auto_guardar', 'valor', $_POST['auto_guardar']);
    
    // Guardar cambios
    $ClasesParametros->save();
    $mensaje = "Configuración guardada correctamente";
}

$parametros = $ClasesParametros->getRoot();
$config = $parametros->configuracion;
?>
    
    <div class="container" style="margin-top:20px;">
        <div class="page-header">
            <h2><i class="fas fa-cog"></i> Configuración de Parámetros - Temperaturas</h2>
        </div>
        
        <?php if (isset($mensaje)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        
        <!-- Boton Volver (Derecha) -->
        <div style="text-align:right; margin-bottom:20px;">
            <a href="<?php echo $HostNombre . '/modulos/mod_temperaturas/temperatura.php'; ?>" class="btn btn-default">
                <i class="fas fa-arrow-left"></i> Cerrar configuración
            </a>
        </div>

        <div style="margin-bottom:20px;">
        <form method="POST" action="">
            <!-- Registros Diarios -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fas fa-clock"></i> Registros Diarios</h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label for="registros_diarios">
                            <?php echo (string)$config->registros_diarios['descripcion']; ?>
                        </label>
                        <input type="number" 
                               id="registros_diarios" 
                               name="registros_diarios" 
                               class="form-control"
                               value="<?php echo (string)$config->registros_diarios; ?>" 
                               min="1" 
                               max="4">
                    </div>
                    
                    <h4>Configuración de Registros</h4>
                    
                    <?php for ($i = 1; $i <= 4; $i++): 
                        $registro = $config->{"registro{$i}"};
                    ?>
                    <div class="row" style="margin-bottom:10px; padding:10px; background:#f8f8f8; border-radius:4px;">
                        <div class="col-sm-12">
                            <label class="control-label">Registro <?php echo $i; ?></label>
                        </div>
                        <div class="col-sm-4">
                            <input type="time" 
                                   name="registro<?php echo $i; ?>_hora" 
                                   class="form-control"
                                   value="<?php echo (string)$registro['hora']; ?>"
                                   placeholder="Hora">
                        </div>
                        <div class="col-sm-4">
                            <select name="registro<?php echo $i; ?>_forzar" class="form-control">
                                <option value="Si" <?php echo ((string)$registro['forzar'] == 'Si') ? 'selected' : ''; ?>>Forzar: Sí</option>
                                <option value="No" <?php echo ((string)$registro['forzar'] == 'No') ? 'selected' : ''; ?>>Forzar: No</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <input type="text" 
                                   name="registro<?php echo $i; ?>_descripcion" 
                                   class="form-control"
                                   value="<?php echo (string)$registro['descripcion']; ?>"
                                   placeholder="Descripción">
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <!-- Otras Configuraciones -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fas fa-sliders-h"></i> Otras Configuraciones</h3>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label for="dias_historico">
                            <?php echo (string)$config->dias_historico['descripcion']; ?>
                        </label>
                        <input type="number" 
                               id="dias_historico" 
                               name="dias_historico" 
                               class="form-control"
                               value="<?php echo (string)$config->dias_historico['valor']; ?>"
                               min="1" 
                               max="365">
                    </div>
                    
                    <div class="form-group">
                        <label for="auto_guardar">
                            <?php echo (string)$config->auto_guardar['descripcion']; ?>
                        </label>
                        <select id="auto_guardar" name="auto_guardar" class="form-control">
                            <option value="Si" <?php echo ((string)$config->auto_guardar['valor'] == 'Si') ? 'selected' : ''; ?>>Sí</option>
                            <option value="No" <?php echo ((string)$config->auto_guardar['valor'] == 'No') ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="text-center" style="margin-bottom:40px;">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Guardar Configuración
                </button>
            </div>
        </form>
    </div>

    <!-- Optional: Bootstrap JS and dependencies (jQuery) if needed -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>


        </div>