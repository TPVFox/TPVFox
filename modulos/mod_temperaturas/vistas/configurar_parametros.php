<?php
include_once("./../../../inicial.php");
include_once("./../../../configuracion.php");
include_once($URLCom . '/controllers/parametros.php');
include_once $URLCom . '/controllers/Controladores.php';

$ClasesParametros = new ClaseParametros('../parametros.xml');
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
<!DOCTYPE html>
<html>

<head>
    <?php include_once $URLCom . '/head.php'; ?>
    <style>
        .config-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        .config-section {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .config-section h3 {
            margin-top: 0;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group small {
            color: #888;
            font-size: 12px;
        }
        .registro-row {
            display: grid;
            grid-template-columns: 1fr 100px 150px 2fr;
            gap: 10px;
            align-items: center;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .registro-row label {
            font-weight: normal;
            margin: 0;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .btn-guardar {
            background: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        .btn-guardar:hover {
            background: #0056b3;
        }
        .alerta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
    </style>
</head>

<body>
    <?php include_once $URLCom . '/modulos/mod_menu/menu.php'; ?>
    
    <div class="config-container">
        <h2><i class="fas fa-cog"></i> Configuración de Parámetros - Temperaturas</h2>
        
        <?php if (isset($mensaje)): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <!-- Registros Diarios -->
            <div class="config-section">
                <h3><i class="fas fa-clock"></i> Registros Diarios</h3>
                
                <div class="form-group">
                    <label for="registros_diarios">
                        <?php echo (string)$config->registros_diarios['descripcion']; ?>
                    </label>
                    <input type="number" 
                           id="registros_diarios" 
                           name="registros_diarios" 
                           value="<?php echo (string)$config->registros_diarios; ?>" 
                           min="1" 
                           max="4">
                </div>
                
                <h4>Configuración de Registros</h4>
                <?php for ($i = 1; $i <= 4; $i++): 
                    $registro = $config->{"registro{$i}"};
                ?>
                <div class="registro-row">
                    <div>
                        <label>Registro <?php echo $i; ?></label>
                    </div>
                    <div>
                        <input type="time" 
                               name="registro<?php echo $i; ?>_hora" 
                               value="<?php echo (string)$registro['hora']; ?>"
                               placeholder="Hora">
                    </div>
                    <div>
                        <select name="registro<?php echo $i; ?>_forzar">
                            <option value="Si" <?php echo ((string)$registro['forzar'] == 'Si') ? 'selected' : ''; ?>>Forzar: Sí</option>
                            <option value="No" <?php echo ((string)$registro['forzar'] == 'No') ? 'selected' : ''; ?>>Forzar: No</option>
                        </select>
                    </div>
                    <div>
                        <input type="text" 
                               name="registro<?php echo $i; ?>_descripcion" 
                               value="<?php echo (string)$registro['descripcion']; ?>"
                               placeholder="Descripción">
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            
            <!-- Alertas de Temperatura -->
            <div class="config-section">
                <h3><i class="fas fa-thermometer-half"></i> Alertas de Temperatura</h3>
                
                <div class="alerta-grid">
                    <div class="form-group">
                        <label for="alerta_maxima">
                            <i class="fas fa-temperature-high" style="color: red;"></i>
                            <?php echo (string)$config->alerta_maxima['descripcion']; ?>
                        </label>
                        <input type="number" 
                               id="alerta_maxima" 
                               name="alerta_maxima" 
                               value="<?php echo (string)$config->alerta_maxima['valor']; ?>"
                               step="0.1">
                        <small>Temperatura en °C</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="alerta_minima">
                            <i class="fas fa-temperature-low" style="color: blue;"></i>
                            <?php echo (string)$config->alerta_minima['descripcion']; ?>
                        </label>
                        <input type="number" 
                               id="alerta_minima" 
                               name="alerta_minima" 
                               value="<?php echo (string)$config->alerta_minima['valor']; ?>"
                               step="0.1">
                        <small>Temperatura en °C</small>
                    </div>
                </div>
            </div>
            
            <!-- Otras Configuraciones -->
            <div class="config-section">
                <h3><i class="fas fa-sliders-h"></i> Otras Configuraciones</h3>
                
                <div class="form-group">
                    <label for="dias_historico">
                        <?php echo (string)$config->dias_historico['descripcion']; ?>
                    </label>
                    <input type="number" 
                           id="dias_historico" 
                           name="dias_historico" 
                           value="<?php echo (string)$config->dias_historico['valor']; ?>"
                           min="1" 
                           max="365">
                </div>
                
                <div class="form-group">
                    <label for="auto_guardar">
                        <?php echo (string)$config->auto_guardar['descripcion']; ?>
                    </label>
                    <select id="auto_guardar" name="auto_guardar">
                        <option value="Si" <?php echo ((string)$config->auto_guardar['valor'] == 'Si') ? 'selected' : ''; ?>>Sí</option>
                        <option value="No" <?php echo ((string)$config->auto_guardar['valor'] == 'No') ? 'selected' : ''; ?>>No</option>
                    </select>
                </div>
            </div>
            
            <div style="text-align: center;">
                <button type="submit" class="btn-guardar">
                    <i class="fas fa-save"></i> Guardar Configuración
                </button>
            </div>
        </form>
    </div>
</body>
</html>