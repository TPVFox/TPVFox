<!DOCTYPE html>
<html>
    <head>
        <?php
	include './../../head.php';
	//~ include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	
	
	//~ echo '<pre>';
	//~ print_r($tickets);
	//~ echo '</pre>';

	
	?>
	
	<script>
	// Declaramos variables globales
	var checkID = [];
	</script> 
	<script src="<?php echo $HostNombre; ?>/modulos/mod_copia_seguridad/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
	
 
    </head>

<body>
        <?php
        //~ include './../../header.php';
         include_once $URLCom.'/modulos/mod_menu/menu.php';
        include './ClaseBackup.php';
		define("DB_USER", $usuarioMysql);
		define("DB_PASSWORD", $passwordMysql);
		define("DB_NAME", $nombrebdMysql);
		define("DB_HOST", 'localhost');
		//para identificar copia Parcial o Completa y modificar nombre de la copia backup 
		//seria.. BACKUP_DIR y TABLES , como tabla_parcial y tabla_completa con su nombre de copia correspondiente
		
		define("BACKUP_DIR", '../../../datos/backup/backup-archivos'); // Comenta esta línea para usar el mismo directorio de scripts ('.')
		//~ define("TABLES", '*'); // copia de seguridad completa
		define("TABLES", 'indices,usuarios,tiendas,ticketslinea,ticketst,ticketstIva,ticketstemporales,cierres,cierres_ivas,cierres_usuariosFormasPago,cierres_usuarios_tickets'); // Copia de seguridad parcial
		define("CHARSET", 'utf8');
		define("GZIP_BACKUP_FILE", false);  // Establecer en falso si quieres archivos de copia de seguridad de SQL simple (no gzip)

		/**
		 * La clase Backup_Database
		 */
		/**
		 * Crea una instancia de Backup_Database y realiza una copia de seguridad
		 */
		// Reportar todos los errores
		error_reporting(E_ALL);
		//Establecer el tiempo máximo de ejecución del script
		set_time_limit(900); // 15 minutes
		if (php_sapi_name() != "cli") {
			echo '<div style="font-family: monospace;">';
		}
		$backupDatabase = new Backup_Database(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		
		$result = $backupDatabase->backupTables(TABLES, BACKUP_DIR) ? 'OK' : 'KO';
		$backupDatabase->obfPrint('Backup result: ' . $result, 1);
		if (php_sapi_name() != "cli") {
			echo '</div>';
		}
		//php_sapi_name() --> Devuelve el tipo de interfaz que hay entre PHP y el servidor. como cadena de texto en minusculas
		//https://github.com/daniloaz/myphp-backup/blob/master/myphp-backup.php
?>
		
</body>
</html>
