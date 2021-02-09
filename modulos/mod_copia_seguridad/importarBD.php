<?php 
/**
 * Este archivo contiene la clase Restore_Database que realiza
 * una restauración parcial o completa de cualquier base de datos MySQL dada
 * @author Daniel López Azaña <daniloaz@gmail.com>
 * @version 1.0
 */
/**
 * Definir los parámetros de la base de datos aquí
 */

define("DB_USER", 'usertpv');
define("DB_PASSWORD", 'prueba');
define("DB_NAME", 'tpv');
define("DB_HOST", 'localhost');
define("BACKUP_DIR", '../datos/backup/myphp-backup-files'); // Comenta esta línea para usar el mismo directorio de scripts ('.')

/** 
 * RECOGER NOMBRE ARCHIVO PARA IMPORTAR y poner en backup_file
 */

define("BACKUP_FILE", '20171108_200713-backup-tpv.sql'); //El script se autodetectará si el archivo de copia de seguridad está comprimido en base a la extensión .gz
define("CHARSET", 'utf8');


/**
 * La clase Restore_Database
 */
class Restore_Database {
    var $host;		//Host donde se encuentra la base de datos
    var $username;	//Nombre de usuario utilizado para conectarse a la base de datos
    var $passwd;	//Contraseña utilizada para conectarse a la base de datos
    var $dbName;	//Base de datos para respaldar
    var $charset;	//Juego de caracteres de la base de datos
    var $conn;		//Conexión a la base
    /**
     * Constructor initializes database
     */
    function __construct($host, $username, $passwd, $dbName, $charset = 'utf8') {
        $this->host       = $host;
        $this->username   = $username;
        $this->passwd     = $passwd;
        $this->dbName     = $dbName;
        $this->charset    = $charset;
        $this->conn       = $this->initializeDatabase();
        $this->backupDir  = BACKUP_DIR ? BACKUP_DIR : '.';
        $this->backupFile = BACKUP_FILE ? BACKUP_FILE : null;
    }
    protected function initializeDatabase() {
        try {
            $conn = mysqli_connect($this->host, $this->username, $this->passwd, $this->dbName);
            if (mysqli_connect_errno()) {
                throw new Exception('ERROR conectarse en base de datos: ' . mysqli_connect_error());
                die();
            }
            if (!mysqli_set_charset($conn, $this->charset)) {
                mysqli_query($conn, 'SET NAMES '.$this->charset);
            }
        } catch (Exception $e) {
            print_r($e->getMessage());
            die();
        }
        return $conn;
    }
    /**
     * Copia de seguridad de toda la base de datos o solo algunas tablas
     * Use '*' para la base de datos completa o 'table1 table2 table3 ...'
     * @param string $ tables
     */
    public function restoreDb() {
        try {
            $sql = '';
            $multiLineComment = false;
            $backupDir = $this->backupDir;
            $backupFile = $this->backupFile;
            /**
             * Archivo de Gunzip si gzipped
             */
            $backupFileIsGzipped = substr($backupFile, -3, 3) == '.gz' ? true : false;
            if ($backupFileIsGzipped) {
                if (!$backupFile = $this->gunzipBackupFile()) {
                    throw new Exception("ERROR: no podría gunzip archivo de copia de seguridad " . $backupDir . '/' . $backupFile);
                }
            }
            /**
            * Lea el archivo de respaldo línea por línea
            */
            $handle = fopen($backupDir . '/' . $backupFile, "r");
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $line = ltrim(rtrim($line));
                    if (strlen($line) > 1) { // evitar líneas en blanco 
                        $lineIsComment = false;
                        if (preg_match('/^\/\*/', $line)) {
                            $multiLineComment = true;
                            $lineIsComment = true;
                        }
                        if ($multiLineComment or preg_match('/^\/\//', $line)) {
                            $lineIsComment = true;
                        }
                        if (!$lineIsComment) {
                            $sql .= $line;
                            if (preg_match('/;$/', $line)) {
                                // execute query
                                if(mysqli_query($this->conn, $sql)) {
                                    if (preg_match('/^CREATE TABLE `([^`]+)`/i', $sql, $tableName)) {
                                        $this->obfPrint("Tabla creada con éxito: `" . $tableName[1] . "`");
                                    }
                                    $sql = '';
                                } else {
                                    throw new Exception("ERROR: Error de ejecución de SQL: " . mysqli_error($this->conn));
                                }
                            }
                        } else if (preg_match('/\*\/$/', $line)) {
                            $multiLineComment = false;
                        }
                    }
                }
                fclose($handle);
            } else {
                throw new Exception("ERROR: no se pudo abrir el archivo de copia de seguridad " . $backupDir . '/' . $backupFile);
            } 
        } catch (Exception $e) {
            print_r($e->getMessage());
            return false;
        }
        if ($backupFileIsGzipped) {
            unlink($backupDir . '/' . $backupFile);
        }
        return true;
    }
    /*
     * Archivo de copia de seguridad Gunzip
     *
     * @return string Nuevo nombre de archivo (sin .gz adjunto y sin directorio de respaldo) si es exitoso, o falso si falla la operación
     */
    protected function gunzipBackupFile() {
        // Elevar este valor puede aumentar el rendimiento
        $bufferSize = 4096; // leer 4kb a la vez
        $error = false;
        $source = $this->backupDir . '/' . $this->backupFile;
        $dest = $this->backupDir . '/' . date("Ymd_His", time()) . '_' . substr($this->backupFile, 0, -3);
        $this->obfPrint('Archivo de copia de seguridad Gunzipping' . $source . '... ', 0, 0);
        //Eliminar el archivo $dest si existe
        if (file_exists($dest)) {
            if (!unlink($dest)) {
                return false;
            }
        }
        
        // Abrir archivos comprimidos y de destino en modo binario
        if (!$srcFile = gzopen($this->backupDir . '/' . $this->backupFile, 'rb')) {
            return false;
        }
        if (!$dstFile = fopen($dest, 'wb')) {
            return false;
        }
        while (!gzeof($srcFile)) {
			// Leer bytes de tamaño de búfer
            // Tanto fwrite como gzread son binarios seguros
            if(!fwrite($dstFile, gzread($srcFile, $bufferSize))) {
                return false;
            }
        }
        fclose($dstFile);
        gzclose($srcFile);
        $this->obfPrint('OK', 0, 2);
        // Devuelve el nombre de archivo de la copia de seguridad excepto el directorio
        return str_replace($this->backupDir . '/', '', $dest);
    }
    /**
     * Imprime un mensaje que fuerza el lavado del búfer de salida
     *
     */
    public function obfPrint ($msg = '', $lineBreaksBefore = 0, $lineBreaksAfter = 1) {
        if (!$msg) {
            return false;
        }
        $output = '';
        if (php_sapi_name() != "cli") {
            $lineBreak = "<br />";
        } else {
            $lineBreak = "\n";
        }
        if ($lineBreaksBefore > 0) {
            for ($i = 1; $i <= $lineBreaksBefore; $i++) {
                $output .= $lineBreak;
            }                
        }
        $output .= $msg;
        if ($lineBreaksAfter > 0) {
            for ($i = 1; $i <= $lineBreaksAfter; $i++) {
                $output .= $lineBreak;
            }                
        }
        if (php_sapi_name() == "cli") {
            $output .= "\n";
        }
        echo $output;
        if (php_sapi_name() != "cli") {
            ob_flush();
        }
        flush();
    }
}
/**
 *  Crear una instancia de Restore_Database y realizar una copia de seguridad
 */
//Reportar todos los errores
error_reporting(E_ALL);
// Todos los errores
set_time_limit(900); // 15 minutes
if (php_sapi_name() != "cli") {
    echo '<div style="font-family: monospace;">';
}
$restoreDatabase = new Restore_Database(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$result = $restoreDatabase->restoreDb(BACKUP_DIR, BACKUP_FILE) ? 'OK' : 'KO';
$restoreDatabase->obfPrint("Resultado de la restauración: ".$result, 1);
if (php_sapi_name() != "cli") {
    echo '</div>';
}
