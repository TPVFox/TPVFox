<?php
class Backup_Database {
    var $host; 		// Host donde se encuentra la base de datos
    var $username; 	//Nombre de usuario utilizado para conectarse a la base de datos  
    var $passwd; 	//Contraseña utilizada para conectarse a la base de datos
    var $dbName;	//Base de datos para respaldar
    var $charset;	//Juego de caracteres de la base de datos
    var $conn;		// Conexión a la base
    var $backupDir;	//Directorio de respaldo donde se almacenan los archivos de respaldo 
    var $backupFile; //Archivo de respaldo de salida
    var $gzipBackupFile;	//Use la compresión gzip en el archivo de copia de seguridad
    /**
     * Constructor inicializa la base de datos
     */
    public function __construct($host, $username, $passwd, $dbName, $charset = 'utf8') {
        $this->host            = $host;
        $this->username        = $username;
        $this->passwd          = $passwd;
        $this->dbName          = $dbName;
        $this->charset         = $charset;
        $this->conn            = $this->initializeDatabase();
        $this->backupDir       = BACKUP_DIR ? BACKUP_DIR : '.';
        $this->backupFile      = date("Ymd_His", time()).'-backup-'.$this->dbName.'.sql';
        $this->gzipBackupFile  = defined('GZIP_BACKUP_FILE') ? GZIP_BACKUP_FILE : true; //si esta como true crea .gzip
    }
    protected function initializeDatabase() {
        try {
            $conn = mysqli_connect($this->host, $this->username, $this->passwd, $this->dbName);
            if (mysqli_connect_errno()) {
                throw new Exception('ERROR conectando bases de datos: ' . mysqli_connect_error());
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
    public function backupTables($tables = '*') {
        try {
            /**
            * Tablas para exportar
            */
            if($tables == '*') {
                $tables = array();
                $result = mysqli_query($this->conn, 'SHOW TABLES');
                while($row = mysqli_fetch_row($result)) {
                    $tables[] = $row[0];
                }
            } else {
                $tables = is_array($tables) ? $tables : explode(',',$tables);
				
            }
            $sql = 'CREATE DATABASE IF NOT EXISTS `'.$this->dbName."`;\n\n";
            $sql .= 'USE '.$this->dbName.";\n\n";
            /**
            * Iterate tables
            */
            foreach($tables as $table) {
                $this->obfPrint("Backing up `".$table."` table...".str_repeat('.', 50-strlen($table)), 0, 0);
                /**
                 * CREATE TABLE
                 */
                $sql .= 'DROP TABLE IF EXISTS `'.$table.'`;';
                $row = mysqli_fetch_row(mysqli_query($this->conn, 'SHOW CREATE TABLE `'.$table.'`'));
                $sql .= "\n\n".$row[1].";\n\n";
                /**
                 * INSERT INTO
                 */
                $row = mysqli_fetch_row(mysqli_query($this->conn, 'SELECT COUNT(*) FROM `'.$table.'`'));
                $numRows = $row[0];
                // Dividir la tabla en lotes para no agotar la memoria del sistema
                $batchSize = 1000; // Número de filas por lote
                $numBatches = intval($numRows / $batchSize) + 1; // Número de llamadas while-loop para realizar
                for ($b = 1; $b <= $numBatches; $b++) {
                    
                    $query = 'SELECT * FROM `'.$table.'` LIMIT '.($b*$batchSize-$batchSize).','.$batchSize;
                    $result = mysqli_query($this->conn, $query);
                    $numFields = mysqli_num_fields($result);
                    $values = array();
                    //~ for ($i = 0; $i < $numFields; $i++) {
                        $rowCount = 0;                        
                        while($row = mysqli_fetch_row($result)) {
                           // $sql .= 'INSERT INTO `'.$table.'` VALUES(';
                            $interiorValue = '';
                            for($j=0; $j<$numFields; $j++) {
                                if (isset($row[$j])) {
                                    $row[$j] = addslashes($row[$j]);
                                    $row[$j] = str_replace("\n","\\n",$row[$j]);
                                    $interiorValue .= '"'.$row[$j].'"' ;
                                } else {
                                    $interiorValue.= 'NULL';
                                }
                                if ($j < ($numFields-1)) {
                                    $interiorValue .= ',';
                                } 
                     
                            }
                            $values[$rowCount] ='('.$interiorValue.')';
							$rowCount++;							
							
                        }
                    //~ }
                    //~ $sql.=" );\n";
                    $grupoValues = array_chunk($values,500,true);
                //  $sql .= 'INSERT INTO `'.$table.'` VALUES ';

                    foreach ($grupoValues as $value ){
						$sql .= 'INSERT INTO `'.$table.'` VALUES '.implode(' ,',$value).";\n";

					}
                    $sql .=$valuesString;
                    $this->saveFile($sql);
                    $sql = '';
                }
                $sql.="\n\n\n";
                $this->obfPrint(" OK");
            }
            if ($this->gzipBackupFile) {
                $this->gzipBackupFile();
            } else {
                $this->obfPrint('El archivo de copia de seguridad se guardó correctamente en ' . $this->backupDir.'/'.$this->backupFile, 1, 1);
            }
        } catch (Exception $e) {
            print_r($e->getMessage());
            return false;
        }
        return true;
    }
    /**
     * Guardar SQL en archivo
     * @param string $sql
     */
    protected function saveFile(&$sql) {
        if (!$sql) return false;
        try {
            if (!file_exists($this->backupDir)) {
                mkdir($this->backupDir, 0777, true); //crea backupDir
            }
            file_put_contents($this->backupDir.'/'.$this->backupFile, $sql, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            print_r($e->getMessage());
            return false;
        }
        return true;
    }
    /*
     * Archivo de copia de seguridad Gzip
     *
     * @param integer $ level GZIP nivel de compresión (predeterminado: 9)
     * @return stringNuevo nombre de archivo (con .gz adjunto) si es correcto o falso si la operación falla
     */
    protected function gzipBackupFile($level = 9) {
        if (!$this->gzipBackupFile) {
            return true;
        }
        $source = $this->backupDir . '/' . $this->backupFile;
        $dest =  $source . '.gz';
        $this->obfPrint('Gzipping backup file to ' . $dest . '... ', 1, 0);
        $mode = 'wb' . $level;
        if ($fpOut = gzopen($dest, $mode)) {
            if ($fpIn = fopen($source,'rb')) {
                while (!feof($fpIn)) {
                    gzwrite($fpOut, fread($fpIn, 1024 * 256));
                }
                fclose($fpIn);
            } else {
                return false;
            }
            gzclose($fpOut);
            if(!unlink($source)) {
                return false;
            }
        } else {
            return false;
        }
        
        $this->obfPrint('OK');
        return $dest;
    }
    /**
     * Imprime el mensaje forzando el flujo del búfer de salida
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
        echo $output;
        if (php_sapi_name() != "cli") {
            ob_flush();
        }
        flush();
    }
}
?>
