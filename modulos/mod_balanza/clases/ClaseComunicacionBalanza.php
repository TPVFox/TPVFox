<?php
/* Clase que se encarga de traducir los datos al formato final de la balanza dina
*
* Esta clase incluirá variables que definen la estructura de los datos
* de forma indivicual bajo el nombre cregitroH2, cregitroH3, etc.
* Ademas incluira metodos que permiten la traducción de datos enfocada a objetivos
* especificos de la balanza actualizacionArticulo, actualizacionSección, etc.
* Tambien incluirá metodos de validación de datos y de estructura así como
* variables que garanticen poder tener una estructura clara y definida
* de los datos que se envian a la balanza.
*/
class ClaseComunicacionBalanza {
    // Propiedad de la ruta de la balanza
    protected $rutaBalanza = ''; // Ruta de la balanza, por defecto es /dev/ttyUSB0
    // Propiedad ruta de logs de la balanza
    protected $rutaLogs = '/logs'; // Ruta donde se guardan los logs de la balanza
    // Propiedad ultimo estado de la balanza
    protected $ultimoEstado = ''; // Último estado de la balanza, se actualiza con el resultado de la última comunicación
    // Propiedad tiempo de espera para comunicaciones entre balanza
    protected $tiempoEspera = 1; // Tiempo de espera en segundos para las comunicaciones entre balanza y sistema
    // Propiedad PID de la balanza, se puede usar para identificar la balanza en el sistema
    protected $pidBalanza = null; // PID de la balanza, se puede usar para identificar la balanza en el sistema
    // Propiedad grupo de balanza por defecto 0. Actualmente es un valor fijo pero abria que evaluar si incluir una tabla con grupo de cada balanza.
    protected $grupo = 0;
    // Propiedad direccion de balanza por defecto 0. Actualmente es un valor fijo pero abria que evaluar si incluir una tabla con dirección de cada balanza.
    protected $direccion = 0;
    // Propiedad para definir el modo de comunicación de la balanza;
    protected $modoComunicacion = 'H'; // Modo de comunicación con balanzas que utilizan el protocolo H2/H3 tambien puede se 'L';
    // Sistema de alertas interno para la clase
    protected $alertas = [];
    // Definición la estructura de datos para el registro H2
    protected $dataH2 = [
        'codigo' => '', //Obligatorio
        'nombre' => '', //Obligatorio
        'precio' => 0, //Obligatorio
        'PLU' => '',
        'precioOferta' => 0,
        'precioCoste' => 0
    ];
    // Definición la estructura de datos para el registro H3
    protected $dataH3 = [
        'codigo' => '', //Obligatorio
        'tipoProducto' => '', //Obligatorio
        'iva' => 0, //Obligatorio
        'seccion' => 0,
        'fechaCaducidad' => '',
        'fechaConsumoPreferente' => '',
        'tara' => '',
        'formatoEtiqueta' => '',
        'formatoCodigoBarras' => '',
        'codigoSmiley' => '',
        'codigoEAN13' => '',
        'oferta' => '',
        'fechaCongelacion' => '',
    ];
    // Definición de la estructura de datos para el registro DS
    protected $dataDS = [
        'seccion' => 0, //Obligatorio
        'nombre' => '', //Obligatorio
        'PLU' => '',
    ];
    public function setRutaBalanza(string $ruta): void {
        // Establecemos la ruta de la balanza
        $this->rutaBalanza = $ruta;
        $this->rutaLogs = $ruta . '/logs'; // Establecemos la ruta de los logs
    }
    // Definimos los metodos para asignar grupo y direccion de la balanza
    public function setGrupo(int $grupo): void {
        // Establecemos el grupo de la balanza
        $this->grupo = $grupo;
    }
    public function setDireccion(int $direccion): void {
        // Establecemos la dirección de la balanza
        $this->direccion = $direccion;
    }
    // Cambiar el modo de comunicación de L a H
    public function setModoComunicacion(string $modo): void {
        // Establecemos el modo de comunicación de la balanza
        if (in_array($modo, ['H', 'L'])) {
            $this->modoComunicacion = $modo;
        } else {
            throw new InvalidArgumentException('Modo de comunicación no válido. Debe ser "H" o "L".');
        }
    }
    public function getModoComunicacion(): string {
        // Retornamos el modo de comunicación actual
        return $this->modoComunicacion;
    }

    // Definimos el metodo setH2Data para establecer los datos del registro H2
    public function setH2Data(array $data): void {
        // Validamos que los datos necesarios esten presentes
        if (isset($data['codigo'], $data['nombre'], $data['precio'])) {
            // Asignamos los datos al registro H2
            $this->dataH2['codigo'] = $data['codigo'];
            $this->dataH2['nombre'] = $data['nombre'];
            // El precio debe ser un entero en centavos
            if (is_float($data['precio'])) {
                $data['precio'] = round($data['precio'], 2);
            }
            $this->dataH2['precio'] = intval($data['precio'] * 100);
            // Asignamos el PLU si esta presente
            if (isset($data['PLU'])) {
                $this->dataH2['PLU'] = $data['PLU'];
            } else {
                $this->dataH2['PLU'] = '';  // Si no se proporciona, lo dejamos vacío
            }
            // Asignamos el precio de oferta si esta presente
            if (isset($data['precioOferta'])) {
                if (is_float($data['precioOferta'])) {
                    $data['precioOferta'] = round($data['precioOferta'], 2);
                }
                $this->dataH2['precioOferta'] = intval($data['precioOferta'] * 100);
            } else {
                $this->dataH2['precioOferta'] = 0; // Si no se proporciona, lo dejamos en 0
            }
            // Asignamos el precio de coste si esta presente
            if (isset($data['precioCoste'])) {
                if (is_float($data['precioCoste'])) {
                    $data['precioCoste'] = round($data['precioCoste'], 2);
                }
                $this->dataH2['precioCoste'] = intval($data['precioCoste'] * 100);
            } else {
                $this->dataH2['precioCoste'] = 0; // Si no se proporciona, lo dejamos en 0
            }
        } else {
            throw new InvalidArgumentException('Faltan datos obligatorios para el registro H2.');
        }
    }
    // Definimos el metodo setH3Data para establecer los datos del registro H3
    public function setH3Data(array $data): void {
        // Validamos que los datos necesarios esten presentes
        if (isset($data['codigo'], $data['tipoProducto'], $data['iva'])) {
            // Asignamos los datos al registro H3
            $this->dataH3['codigo'] = $data['codigo'];
            $this->dataH3['tipoProducto'] = $data['tipoProducto'];
            // El IVA debe ser un entero, lo convertimos a centavos
            $this->dataH3['iva'] = intval($data['iva'] * 100);
            // Asignamos la sección si esta presente
            if (isset($data['seccion'])) {
                $this->dataH3['seccion'] = intval($data['seccion']);
            } else {
                $this->dataH3['seccion'] = 0; // Si no se proporciona, lo dejamos en 0
            }
            // Asignamos la fecha de caducidad si esta presente
            if (isset($data['fechaCaducidad'])) {
                $this->dataH3['fechaCaducidad'] = date('dmy', strtotime($data['fechaCaducidad']));
            } else {
                $this->dataH3['fechaCaducidad'] = '000000'; // Si no se proporciona, lo dejamos en 000000
            }
            // Asignamos la fecha de consumo preferente si esta presente
            if (isset($data['fechaConsumoPreferente'])) {
                $this->dataH3['fechaConsumoPreferente'] = date('dmy', strtotime($data['fechaConsumoPreferente']));
            } else {
                $this->dataH3['fechaConsumoPreferente'] = '000000'; // Si no se proporciona, lo dejamos en 000000
            }
            // Asignamos la tara si esta presente
            if (isset($data['tara'])) {
                $this->dataH3['tara'] = str_pad($data['tara'], 4, '0', STR_PAD_LEFT);
            } else {
                $this->dataH3['tara'] = '0000'; // Si no se proporciona, lo dejamos en 0000
            }
            // Asignamos el formato de etiqueta si esta presente
            if (isset($data['formatoEtiqueta'])) {
                $this->dataH3['formatoEtiqueta'] = str_pad($data['formatoEtiqueta'], 2, '0', STR_PAD_LEFT);
            } else {
                $this->dataH3['formatoEtiqueta'] = '02'; // Si no se proporciona, lo dejamos en 02
            }
            // Asignamos el formato de código de barras si esta presente
            if (isset($data['formatoCodigoBarras'])) {
                $this->dataH3['formatoCodigoBarras'] = str_pad($data['formatoCodigoBarras'], 2, '0', STR_PAD_LEFT);
            } else {
                $this->dataH3['formatoCodigoBarras'] = '00'; // Si no se proporciona, lo dejamos en 00
            }
            // Asignamos el código Smiley si esta presente
            if (isset($data['codigoSmiley'])) {
                $this->dataH3['codigoSmiley'] = str_pad($data['codigoSmiley'], 2, '0', STR_PAD_LEFT);
            } else {
                $this->dataH3['codigoSmiley'] = '00'; // Si no se proporciona, lo dejamos en 00
            }
            // Asignamos el código EAN13 si esta presente
            if (isset($data['codigoEAN13'])) {
                $this->dataH3['codigoEAN13'] = str_pad($data['codigoEAN13'], 13, ' ', STR_PAD_RIGHT);
            }
            // Asignamos la oferta si esta presente
            if (isset($data['oferta'])) {
                $this->dataH3['oferta'] = str_pad($data['oferta'], 4, '0', STR_PAD_LEFT);
            } else {
                $this->dataH3['oferta'] = '0000'; // Si no se proporciona, lo dejamos en 0000
            }
            // Asignamos la fecha de congelación si esta presente
            if (isset($data['fechaCongelacion'])) {
                $this->dataH3['fechaCongelacion'] = date('dmy', strtotime($data['fechaCongelacion']));
            } else {
                $this->dataH3['fechaCongelacion'] = '000000'; // Si no se proporciona, lo dejamos en 000000
            }
        } else {
            throw new InvalidArgumentException('Faltan datos obligatorios para el registro H3.');
        }
    }
    // Definimos el metodo setDSData para establecer los datos del registro DS
    public function setDSData(array $data): void {
        // Validamos que los datos necesarios esten presentes
        if (isset($data['seccion'], $data['nombre'])) {
            // Asignamos los datos al registro DS
            $this->dataDS['seccion'] = $data['seccion'];
            $this->dataDS['nombre'] = $data['nombre'];
            // Asignamos el PLU si esta presente. Acceso rapido a la sección desde la balanza.
            if (isset($data['PLU'])) {
                $this->dataDS['PLU'] = $data['PLU'];
            } else {
                $this->dataDS['PLU'] = '';  // Si no se proporciona, lo dejamos vacío
            }
        } else {
            throw new InvalidArgumentException('Faltan datos obligatorios para el registro DS.');
        }
    }
    // Definimos el método que se encarga de traducir los datos al formato de etiqueta H2
    public function traducirH2(): string {
        // Validamos que los datos necesarios esten presentes
        if (empty($this->dataH2['codigo']) || empty($this->dataH2['nombre']) || empty($this->dataH2['precio'])) {
            throw new InvalidArgumentException('Datos incompletos para el registro H2.');
        }
        // Construimos la etiqueta H2
        $H2 = $this->formatearCampo($this->grupo, 2, 'grupo');
        $H2 .= $this->modoComunicacion . "2"; // Clave de registro
        $H2 .= $this->formatearCampo($this->direccion, 2, 'direccion');
        $H2 .= "A" . $this->formatearCampo($this->dataH2['codigo'], 6, 'codigo');
        $H2 .= $this->formatearCampo($this->dataH2['PLU'], 3, 'PLU');
        
        // Limpiamos el nombre del producto
        $nombre = $this->limpiarNombre($this->dataH2['nombre']);
        if (mb_strlen($nombre, 'UTF-8') > 60) {
            $this->alertas[] = "El campo 'nombre' excede la longitud máxima de 60 caracteres y será recortado. [" . __FUNCTION__ . "]";
            $nombre = mb_substr($nombre, 0, 60, 'UTF-8');
            // verificar logitud del nombre mostrando una alerta
        }
        // Rellenar a 60 caracteres multibyte correctamente
        $nombre_padded = $nombre . str_repeat(' ', 60 - mb_strlen($nombre, 'UTF-8'));
        $H2 .= $nombre_padded; // Nombre del producto (60 caracteres)
        // Si es modelo L se añaden 12 espacios más
        if ($this->modoComunicacion === 'L') {
            $H2 .= str_repeat(' ', 12); // Añadimos 12 espacios adicionales
        }
        $H2 .= "00"; // 2 dígitos de control
        $H2 .= $this->formatearCampo($this->dataH2['precio'], 6, 'precio');
        $H2 .= "00"; // 2 dígitos de control
        $H2 .= $this->formatearCampo($this->dataH2['precioOferta'], 6, 'precioOferta');
        $H2 .= "00"; // 2 dígitos de control
        $H2 .= $this->formatearCampo($this->dataH2['precioCoste'], 6, 'precioCoste');
        //si es modelo M se añaden 30 digitos nutlo el modelo L solo 18
        if ($this->modoComunicacion === 'M') {
            $H2 .= str_repeat("0", 30); // 30 dígitos nulos (N/A)
        } else {
            $H2 .= str_repeat("0", 18); // 18 dígitos nulos (N/A)
        }
        $H2 .= str_repeat("-", 20); // 20 dígitos no transmitibles
        return $H2 . "\n"; // Retornamos la etiqueta H2 con un salto de línea
    }
    // Definimos el método que se encarga de traducir los datos al formato de etiqueta H3
    public function traducirH3(): string {
        // Validamos que los datos necesarios esten presentes
        if (empty($this->dataH3['codigo']) || empty($this->dataH3['tipoProducto']) || empty($this->dataH3['iva'])) {
            throw new InvalidArgumentException('Datos incompletos para el registro H3.');
        }
        // Construimos la etiqueta H3
        $H3 = $this->formatearCampo($this->grupo, 2, 'grupo');
        $H3 .= $this->modoComunicacion . "3"; // Clave de registro
        $H3 .= $this->formatearCampo($this->direccion, 2, 'direccion');
        $H3 .= $this->formatearCampo($this->dataH3['codigo'], 6, 'codigo');
        // Tipo de producto (0: Pesado, 1: Unidad, etc.)
        $tipoProducto = $this->dataH3['tipoProducto'];
        switch ($tipoProducto) {
            case 'peso':
                $H3 .= '0';
                break;
            case 'unidad':
                $H3 .= '1';
                break;
            default:
                $H3 .= '0';
                break;
        }
        $H3 .= '0'; // Tipo de producto adicional (según formato original)
        $H3 .= $this->formatearCampo($this->dataH3['fechaCaducidad'], 6, 'fechaCaducidad');
        $H3 .= $this->formatearCampo($this->dataH3['fechaConsumoPreferente'], 6, 'fechaConsumoPreferente');
        $H3 .= str_repeat("0", 7); // 7 dígitos que no se usan o no hay información
        $H3 .= $this->formatearCampo($this->dataH3['tara'], 4, 'tara');
        $H3 .= "00"; // Dígitos que no se usan o no hay información
        $H3 .= $this->formatearCampo($this->dataH3['formatoEtiqueta'], 2, 'formatoEtiqueta');
        $H3 .= "00"; // Dígitos de **
        $H3 .= "0000"; // Dígitos que no se usan o no hay información
        $H3 .= $this->formatearCampo($this->dataH3['seccion'], 2, 'seccion');
        // 2 digitos de tipo de iva (00 para IVA 000, 01 para IVA 400, 02 para IVA 1000, 03 para IVA 2100)
        $iva = $this->dataH3['iva'];
        switch ($iva) {
            case 0:
                $H3 .= '00'; // IVA 000
                break;
            case 400:
                $H3 .= '01'; // IVA 400
                break;
            case 1000:
                $H3 .= '02'; // IVA 1000
                break;
            case 2100:
                $H3 .= '03'; // IVA 2100
                break;
            default:
                $H3 .= '03'; // Valor por defecto si no coincide con los anteriores
                break;
        }
        $H3 .= $this->formatearCampo($this->dataH3['formatoCodigoBarras'], 2, 'formatoCodigoBarras');
        $H3 .= str_repeat("0", 5); // 5 dígitos que no se usan o no hay información
        $H3 .= $this->formatearCampo($this->dataH3['codigoSmiley'], 2, 'codigoSmiley');
        $H3 .= "0000"; // Dígitos que no se usan o no hay información
        // Código EAN13 personalizado (13 espacios)
        if (isset($this->dataH3['codigoEAN13']) && strlen($this->dataH3['codigoEAN13']) === 13) {
            $H3 .= $this->dataH3['codigoEAN13'];
        } else {
            $H3 .= str_repeat(" ", 13);
        }
        $H3 .= str_repeat("0", 11); // 11 dígitos que no se usan o no hay información
        $H3 .= $this->formatearCampo($this->dataH3['oferta'], 4, 'oferta');
        $H3 .= str_repeat("0", 6); // 6 dígitos que no se usan o no hay información
        $H3 .= $this->formatearCampo($this->dataH3['fechaCongelacion'], 6, 'fechaCongelacion');
        $H3 .= "00"; // 2 dígitos de control
        $H3 .= str_repeat("0", 24); // 24 dígitos nulos (N/A)
        $H3 .= str_repeat("-", 20); // 20 dígitos no transmitibles
        return $H3 . "\n"; // Retornamos la etiqueta H3
    }
    // Definimos el método que se encarga de traducir los datos al formato de etiqueta DS
    public function traducirDS(): string {
    // Definimos un metodo para limpiar los datos en los nombres que se envian a la balanza
        // Validamos que los datos necesarios esten presentes
        if (empty($this->dataDS['seccion']) || empty($this->dataDS['nombre'])) {
            throw new InvalidArgumentException('Datos incompletos para el registro DS.');
        }
        // Construimos la etiqueta DS
        $DS = $this->formatearCampo($this->grupo, 2, 'grupo');
        $DS .= "DS"; // Clave de registro
        $DS .= $this->formatearCampo($this->direccion, 2, 'direccion');
        $DS .= $this->formatearCampo($this->dataDS['seccion'], 2, 'seccion');
        $nombre = $this->limpiarNombre($this->dataDS['nombre']);
        if (mb_strlen($nombre, 'UTF-8') > 20) {
            $this->alertas[] = "El campo 'nombre' excede la longitud máxima de 20 caracteres y será recortado. [" . __FUNCTION__ . "]";
            $nombre = mb_substr($nombre, 0, 20, 'UTF-8');
            // verificar logitud del nombre mostrando una alerta
        }
        // Rellenar a 20 caracteres multibyte correctamente
        $nombre_padded = $nombre . str_repeat(' ', 20 - mb_strlen($nombre, 'UTF-8'));
        $DS .= $nombre_padded; // Nombre del producto
        $DS .= str_repeat("0",9);
        $DS .= " ";
        $DS .= str_repeat("0",4);
        $DS .= $this->formatearCampo($this->dataDS['PLU'], 2, 'PLU');
        $DS .= "0";
        $DS .= str_repeat(" ", 85); // 24 digitos nulos (N/A): 0
        $DS .= str_repeat("-", 20); // 20 digitos no transmitribles: -
        return $DS . "\n"; // Retornamos la etiqueta DS con un salto de línea
    }
    // Metodo para verificar si baltty está instalado y es ejecutable en el directorio de la balanza
    public function verificarDriverBalanza(): ?string {
        $rutaBaltty = $this->rutaBalanza . '/baltty';
        if (is_file($rutaBaltty) && is_executable($rutaBaltty)) {
            $this->alertas[] = "El driver baltty está instalado en: {$rutaBaltty}";
            return $rutaBaltty;
        } else {
            $this->alertas[] = "El driver baltty no está instalado o no es ejecutable en: {$rutaBaltty}";
            error_log("ERROR: El driver baltty no está instalado o no es ejecutable en: {$rutaBaltty} [" . date('Y-m-d H:i:s') . "]");
            return null;
        }
    }
    // Metodo para reiniciar baltty si se está ejecutando: se paran los procesos de baltty en ejecución
    public function reiniciarBalanzaEnEjecucion(): bool {
        $output = [];
        $rutaBaltty = $this->verificarDriverBalanza();
        if ($rutaBaltty === null) {
            $this->alertas[] = "No se puede verificar si baltty está en ejecución porque no está instalado.";
            error_log("ERROR: No se puede verificar si baltty está en ejecución porque no está instalado. [" . date('Y-m-d H:i:s') . "]");
            return false;
        }
        $cmd = "pgrep -f " . escapeshellarg($rutaBaltty);
        exec($cmd, $output);
        $found = false;
        foreach ($output as $p) {
            $p = trim($p);
            if (!empty($p) && ctype_digit($p)) {
                exec("kill " . escapeshellarg($p));
                $this->alertas[] = "Proceso baltty con PID {$p} terminado.";
                error_log("INFO: Proceso baltty con PID {$p} terminado forzosamente. [" . date('Y-m-d H:i:s') . "]");
                $found = true;
            }
        }
        return $found;
    }
    // Método para ejecutar baltty desde el directorio de la balanza
    // Baltty es un comando sencillo que se ejecuta desde un directorio específico.
    // Se puede ejecutar como baltty log para que genere registro en el directorio de logs en el que se ejecuta
    public function ejecutarDriverBalanza(): bool {
        $directorioBalanza = $this->rutaBalanza;
        // Verificamos si el directorio de la balanza existe
        if (!is_dir($directorioBalanza)) {
            $mensaje = "El directorio de la balanza no existe: {$directorioBalanza}";
            $this->alertas[] = $mensaje;
            error_log("ERROR: {$mensaje} [" . date('Y-m-d H:i:s') . "]");
            return false;
        }
        // Verificamos si baltty está instalado
        $rutaBaltty = $this->verificarDriverBalanza();
        if ($rutaBaltty === null) {
            $mensaje = "No se puede ejecutar baltty porque no está instalado.";
            $this->alertas[] = $mensaje;
            error_log("ERROR: {$mensaje} Ruta buscada: {$directorioBalanza}/baltty [" . date('Y-m-d H:i:s') . "]");
            return false;
        }
        // Intentamos reiniciar cualquier proceso baltty en ejecución
        $this->reiniciarBalanzaEnEjecucion();
        // Cambiamos al directorio de la balanza
        if (!@chdir($directorioBalanza)) {
            $mensaje = "No se pudo cambiar al directorio de la balanza: {$directorioBalanza}";
            $this->alertas[] = $mensaje;
            error_log("ERROR: {$mensaje} [" . date('Y-m-d H:i:s') . "]");
            return false;
        }
        // Verificamos el directorio de logs
        if (!is_dir($this->rutaLogs)) {
            $mensaje = "El directorio de logs no existe: {$this->rutaLogs}. Por favor, créalo antes de ejecutar baltty.";
            $this->alertas[] = $mensaje;
            error_log("ERROR: {$mensaje} [" . date('Y-m-d H:i:s') . "]");
            return false;
        }
        // Limpiamos o creamos el archivo de log
        $logFile = $this->rutaLogs . '/BalttyEstadoBalanzas.log';
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        } else {
            touch($logFile);
        }
        // Ejecutamos baltty en modo log
        $cmd = escapeshellcmd($rutaBaltty) . ' log > /dev/null 2>&1 &';
        exec($cmd);
        // Esperamos un segundo para que baltty inicie y genere el log
        sleep(1);
        // Verificamos el estado de la balanza
        $this->estadoLogBalanza($rutaBaltty);
        // Comprobamos si baltty está corriendo correctamente
        if ($this->verificarEstadoBalanza()) {
            return true;
         } else {
            $mensaje = "Error al ejecutar baltty o la balanza no respondió correctamente.";
            $this->alertas[] = $mensaje;
            error_log("ERROR: {$mensaje} [" . date('Y-m-d H:i:s') . "]");
            return false;
        }
    }
    // Método para verificar si la balanza establece comunicación con el sistema
    // Desde el directorio de la balanza si se ejecuta baltty en modo log se genera un archivo en /logs/BalttyEstadoBalanzas.log
    // Este archivo puede contener "La balanza rechaza la comunicación" o "Balanza OK"
    public function verificarEstadoBalanza(): bool {
        $directorioBalanza = $this->rutaBalanza;
        if (!is_dir($directorioBalanza)) {
            $this->alertas[] = "El directorio de la balanza no existe: {$directorioBalanza}";
            error_log("ERROR: El directorio de la balanza no existe: {$directorioBalanza} [" . date('Y-m-d H:i:s') . "]");
            return false;
        }
        $logFile = $this->rutaLogs . '/BalttyEstadoBalanzas.log';
        if (!file_exists($logFile)) {
            $this->alertas[] = "El archivo de log de la balanza no existe: {$logFile}";
            error_log("ERROR: El archivo de log de la balanza no existe: {$logFile} [" . date('Y-m-d H:i:s') . "]");
            return false;
        }

        $logContent = @file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($logContent === false) {
            $this->alertas[] = "No se pudo leer el archivo de log de la balanza: {$logFile}";
            error_log("ERROR: No se pudo leer el archivo de log de la balanza: {$logFile} [" . date('Y-m-d H:i:s') . "]");
            return false;
        }

        // Convertimos cada línea a UTF-8 si es necesario
        foreach ($logContent as &$line) {
            $encoding = mb_detect_encoding($line, ['UTF-8', 'ISO-8859-1', 'ISO-8859-15'], true);
            if ($encoding !== 'UTF-8') {
                $line = mb_convert_encoding($line, 'UTF-8', $encoding);
            }
        }
        unset($line);

        foreach ($logContent as $line) {
            if (strpos($line, 'La balanza rechaza la comunicación') !== false) {
                $this->alertas[] = "La balanza rechaza la comunicación.";
                error_log("ERROR: La balanza rechaza la comunicación. Línea: {$line} [" . date('Y-m-d H:i:s') . "]");
                return false;
            }
            if (strpos($line, 'Balanza OK') !== false) {
                return true;
            }
        }

        $this->alertas[] = "No se pudo determinar el estado de la balanza.";
        error_log("ERROR: No se pudo determinar el estado de la balanza. Archivo: {$logFile} [" . date('Y-m-d H:i:s') . "]");
        return false;
    }
    

   // Método para obtener las alertas generadas
    public function getAlertas(): array {
        return $this->alertas;
    }

    // Método para limpiar nombres, reutilizable para H2 y DS
    protected function limpiarNombre($nombre): string {
        // Eliminar saltos de línea, tabulaciones y comillas
        $nombre = str_replace(array("\n", "\r", "\t", '"'), ' ', $nombre);
        // Eliminar símbolos excepto letras, números, espacios y la Ñ/ñ
        $nombre = preg_replace('/[^\p{L}\p{N}\s]/u', '', $nombre);
        // Sustituir Ñ/ñ por NH
        $nombre = str_replace(['Ñ', 'ñ'], 'NH', $nombre);
        // Sustituir letras acentuadas por la misma letra sin acento
        $nombre = strtr($nombre, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Ü' => 'U', 'ü' => 'u'
        ]);
        // Eliminar dobles espacios
        $nombre = preg_replace('/\s+/', ' ', $nombre);
        // Eliminar espacios al comienzo y al final
        $nombre = trim($nombre);
        return $nombre;
    }

    // Método auxiliar para formatear y recortar valores, generando alerta si es necesario
    protected function formatearCampo($valor, int $longitud, string $campo, $relleno = "0", $tipo = 'izquierda', $origen = null): string {
        $valorStr = (string)$valor;
        if (strlen($valorStr) > $longitud) {
            $func = $origen ?? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
            $this->alertas[] = "El campo '{$campo}' excede la longitud máxima de {$longitud} caracteres y será recortado. [{$func}] ";
            $valorStr = substr($valorStr, 0, $longitud);
        }
        if ($tipo === 'izquierda') {
            $valorStr = str_pad($valorStr, $longitud, $relleno, STR_PAD_LEFT);
        } else {
            $valorStr = str_pad($valorStr, $longitud, $relleno, STR_PAD_RIGHT);
        }
        return $valorStr;
        
    }
    // Metodo para verificar el estado de la balanza leyendo el log de baltty
    // Si la balanza no se ha ejecutado correctamente, se terminan los procesos de baltty en ejecución
    // y se genera una alerta con el estado de la balanza.
    protected function estadoLogBalanza($rutaDriver) {
        if ($this->verificarEstadoBalanza()) {
            $this->alertas[] = "La balanza se ha ejecutado correctamente.";
        } else {
            $this->alertas[] = "La balanza no se ha ejecutado correctamente.";
            error_log("ERROR: La balanza no se ha ejecutado correctamente. [" . date('Y-m-d H:i:s') . "]");
            $output = [];
            exec('pgrep -f ' . escapeshellarg($rutaDriver), $output); // Obtenemos el PID del proceso baltty
            $pid = array_map('trim', $output); // Limpiamos los espacios en blanco de los PIDs
            foreach ($pid as $p) {
                if (!empty($p) && ctype_digit($p)) {
                    exec('kill ' . escapeshellarg($p)); // Terminamos el proceso baltty
                    $this->alertas[] = "Proceso baltty con PID {$p} terminado.";
                    error_log("ERROR: Proceso baltty con PID {$p} terminado forzosamente. [" . date('Y-m-d H:i:s') . "]");
                }
            }
            if (empty($pid)) {
                error_log("ERROR: No se encontraron procesos baltty para terminar. [" . date('Y-m-d H:i:s') . "]");
            }
        }
    }
    // Metodo para crear el directorio de la balanza si no existe
    public function crearDirectorioBalanza($datos) {
        $formato = $datos['modoDirectorio'] ?? 'Balctrol';
        switch ($formato) {
            case 'Balctrol':
                $this->comprobarCrearDirectorio($this->rutaBalanza);
                $fileBalctrol = $this->rutaBalanza . '/balctrol';
                $this->comprobarCrearArchivo($fileBalctrol);
                // Escribimo los nuevos datos en el archivo balctrol
                $this->crearFicheroConfiguracionDibal($fileBalctrol, $datos);
                $fileTx = $this->rutaBalanza . '/filetx';
                $this->comprobarCrearArchivo($fileTx);
                $this->comprobarCrearDirectorio($this->rutaLogs);
                $logFile = $this->rutaLogs . '/BalttyEstadoBalanzas.log';
                $this->comprobarCrearArchivo($logFile);
                return ['mensaje' => 'No hay cambios para actualizar'];
            break;
        }
    }
    // metodo privado pra comrpobar si existen directorio y si no existe crearlo. Devolver un aviso
    private function comprobarCrearDirectorio(string $directorio): void {
        if (!is_dir($directorio)) {
            if (mkdir($directorio, 0755, true)) {
                $this->alertas[] = "Directorio creado: {$directorio}";
            } else {
                $this->alertas[] = "Error al crear el directorio: {$directorio}";
                error_log("ERROR: Error al crear el directorio: {$directorio} [" . date('Y-m-d H:i:s') . "]");
            }
        } else {
            $this->alertas[] = "El directorio ya existe: {$directorio}";
        }
    }
    // Método para combrobar si existe un archivo y si no existe crearlo
    private function comprobarCrearArchivo(string $archivo): void {
        if (!file_exists($archivo)) {
            if (touch($archivo)) {
                $this->alertas[] = "Archivo creado: {$archivo}";
            } else {
                $this->alertas[] = "Error al crear el archivo: {$archivo}";
                error_log("ERROR: Error al crear el archivo: {$archivo} [" . date('Y-m-d H:i:s') . "]");
            }
        } else {
            $this->alertas[] = "El archivo ya existe: {$archivo}";
        }
    }

    private function crearFicheroConfiguracionDibal(string $rutaArchivo, array $config): bool {
        $contenido = <<<EOT
        DT = {$this->rutaBalanza}/
        TC = 1
        DI = {$config['ipPc']}
        PR = 3001
        BD = {$config['direccionBalanza']} {$config['ipBalanza']} 3000
        TX = {$this->rutaBalanza}/filetx 141
        RX = {$this->rutaBalanza}/filerx 151
        BL = {$config['grupoBalanza']} {$config['serieH']}
        BH = {$config['grupoBalanza']} {$config['serieTipo']}
        PM = 3
        EB = 0
        SL = 0
        SI = 0
        EOT;

        if (file_put_contents($rutaArchivo, $contenido) !== false) {
            $this->alertas[] = "Fichero de configuración creado: {$rutaArchivo}";
            return true;
        } else {
            $this->alertas[] = "Error al crear el fichero de configuración: {$rutaArchivo}";
            error_log("ERROR: Error al crear el fichero de configuración: {$rutaArchivo} [" . date('Y-m-d H:i:s') . "]");
            return false;
        }
    }
}
