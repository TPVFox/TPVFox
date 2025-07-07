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
    protected $rutaLogs = '/logs/'; // Ruta donde se guardan los logs de la balanza
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
        $this->rutaBalanza = $ruta . '/';
        $this->rutaLogs = $ruta . '/logs/'; // Establecemos la ruta de los logs
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
        $H2 .= "H2"; // Clave de registro
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
        $H2 .= "00"; // 2 dígitos de control
        $H2 .= $this->formatearCampo($this->dataH2['precio'], 6, 'precio');
        $H2 .= "00"; // 2 dígitos de control
        $H2 .= $this->formatearCampo($this->dataH2['precioOferta'], 6, 'precioOferta');
        $H2 .= "00"; // 2 dígitos de control
        $H2 .= $this->formatearCampo($this->dataH2['precioCoste'], 6, 'precioCoste');
        $H2 .= str_repeat("0", 30); // 30 dígitos nulos (N/A)
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
        $H3 .= "H3"; // Clave de registro
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
    // Metodo para verificar si baltty se esta instalado y en el PATH del sistema
    public function verificarDriverBalanza(): ?string {
        // Verificamos si el comando baltty está disponible en el sistema
        $ruta = $this->rutaBalanza . '/baltty'; // Ruta del driver baltty
        if (is_file($ruta) && is_executable($ruta)) {
            $rutaBaltty = $ruta;
            $this->alertas[] = "El driver baltty está instalado en: {$rutaBaltty}";
            return $rutaBaltty; // El driver baltty está instalado y en el PATH
        } else {
            $this->alertas[] = "El driver baltty no está instalado o no se encuentra en el PATH del sistema.";
            $rutaBaltty = null; // No se encontró el driver baltty
            return $rutaBaltty; // El driver baltty no está instalado o no se encuentra en el PATH
        }
    }
    // Metodo para verificar si baltty se está ejectuando, desde que directorio se ejectuta
    public function verificarBalanzaEnEjecucion(): bool {
        // Verificamos si el comando baltty está en ejecución
        $output = [];
        $return_var = 0;
        $rutaBaltty = $this->verificarDriverBalanza();
        if ($rutaBaltty === null) {
            $this->alertas[] = "No se puede verificar si el driver baltty está en ejecución porque no está instalado.";
            return false; // No se puede verificar si baltty está en ejecución si no está instalado
        } else {
            exec('pgrep -af baltty', $output, $return_var);
        }
        // Verificamos el contenido de la salida del comando contega nuestra ruta de balanza
        $output = array_filter($output, function($line) use ($rutaBaltty) {
            return strpos($line, $rutaBaltty) !== false; // Filtramos las líneas que contienen la ruta del baltty
        });
        // Si output tiene un valor
        if (!empty($output)) {
            $this->alertas[] = "El driver baltty está en ejecución. (PIDs:" . implode("\n", $output) . ")" . $return_var;
            return true; // El driver baltty está en ejecución
        } else {
            $this->alertas[] = "El driver baltty no está en ejecución.";
            return false; // El driver baltty no está en ejecución
        }
    }
    // Método para ejecutar baltty desde el directorio de la balanza
    // Baltty es un comando sencillo que se ejecuta desde un directorio especifico. 
    // Se puede ejecutar como Ballty log para que gener registro en el directorio de logs en el que se ejecuta
    public function ejecutarDriverBalanza(): bool {
        $directorioBalanza = $this->rutaBalanza; // Directorio de la balanza
        // Verificamos si balty esta en instalado
        $rutaBaltty = $this->verificarDriverBalanza();
        if ($rutaBaltty === null) {
            $this->alertas[] = "No se puede ejecutar baltty porque no está instalado.";
            return false; // No se puede ejecutar baltty si no está instalado
        }
        // Verificamos si baltty no se está ejecutando
        if ($this->verificarBalanzaEnEjecucion()) {
            $this->alertas[] = "El driver baltty ya está en ejecución.";
            $this->estadoLogBalanza($rutaBaltty); // Verificamos el estado de la balanza
            return false; // No se puede ejecutar baltty si ya está en ejecución
        }
        // Verificamos si el directorio de la balanza existe
        if (!is_dir($directorioBalanza)) {
            $this->alertas[] = "El directorio de la balanza no existe: {$directorioBalanza}";
            return false;
        }
        // Cambiamos al directorio de la balanza
        chdir($directorioBalanza);
        // Ejecutamos el comando baltty
        $output = [];
        $return_var = 0;
        $cmd = $rutaBaltty . '  > /dev/null 2>&1 &'; // Ejecutamos baltty en modo log
        exec($cmd);
        // Esperamos 1 segundo para asegurarnos de que el comando se ejecute correctamente
        sleep(1); // Esperamos 1 segundo para asegurarnos de que el comando se ejecute correctamente
        // Verificamos si la comunicación de la balanza se ha establecido correctamente
        $this->estadoLogBalanza($rutaBaltty); // Verificamos el estado de la balanza
        if ($return_var === 0) {
            return true; // El comando se ejecutó correctamente
        } else {
            $this->alertas[] = "Error al ejecutar baltty: " . implode("\n", $output);
            return false; // Hubo un error al ejecutar el comando
        }
    }
    // Método para verificar si la balanza establece comunicación con el sistema
    // Desde el directorio de la balanza si se ejecuta baltty en modo log se genera un archivo en /logs/BalttyEstadoBalanzas.log
    // Este archivo puede contener "La balanza rechaza la comunicación" o "Balanza OK"
    public function verificarEstadoBalanza(): bool {
        $directorioBalanza = $this->rutaBalanza; // Directorio de la balanza
        // Verificamos si el directorio de la balanza existe
        if (!is_dir($directorioBalanza)) {
            $this->alertas[] = "El directorio de la balanza no existe: {$directorioBalanza}";
            return false;
        }
        // Dierectorio en el que se ubica el archivo de log de la balanza
        $logFile = $directorioBalanza . '/logs/BalttyEstadoBalanzas.log';
        if (!file_exists($logFile)) {
            $this->alertas[] = "El archivo de log de la balanza no existe: {$logFile}";
            return false; // El archivo de log no existe
        } else {
            // Tenemos que leer el $logFile de la balanza y pasarlo a la variable $logContent teniendo cuidado de que acentos y ñ se conserven
            $logContent = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($logContent !== false) {
                // Convertimos a UTF-8 si es necesario (por si el archivo está en otra codificación)
                foreach ($logContent as &$line) {
                    $line = mb_convert_encoding($line, 'UTF-8', mb_detect_encoding($line, 'UTF-8,ISO-8859-1,ISO-8859-15', true));
                }
                unset($line);
            }

            if ($logContent === false) {
                $this->alertas[] = "No se pudo leer el archivo de log de la balanza: {$logFile}";
                return false; // No se pudo leer el archivo de log
            }
            foreach ($logContent as $line) {
                if (strpos($line, 'La balanza rechaza la comunicación') !== false) {
                    $this->alertas[] = "La balanza rechaza la comunicación.";
                    return false; // La balanza no establece comunicación
                } elseif (strpos($line, 'Balanza OK') !== false) {
                    return true; // La balanza establece comunicación correctamente
                }
                $this->alertas[] =  $line;
            }
            $this->alertas[] = "No se pudo determinar el estado de la balanza.";
            return false; // No se pudo determinar el estado de la balanza
        }
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
            return str_pad($valorStr, $longitud, $relleno, STR_PAD_LEFT);
        } else {
            return str_pad($valorStr, $longitud, $relleno, STR_PAD_RIGHT);
        }
    }
    protected function estadoLogBalanza($rutaDriver) {
        if ($this->verificarEstadoBalanza()) {
            $this->alertas[] = "La balanza se ha ejecutado correctamente.";
        } else {
            $this->alertas[] = "La balanza no se ha ejecutado correctamente.";
            exec('pgrep -f ' . $rutaDriver, $output); // Obtenemos el PID del proceso baltty
            $pid = array_map('trim', $output); // Limpiamos los espacios en blanco de los PIDs
            foreach ($pid as $p) {
                exec('kill ' . $p); // Terminamos el proceso baltty
                $this->alertas[] = "Proceso baltty con PID {$p} terminado.";
            }
        }
    }


}
