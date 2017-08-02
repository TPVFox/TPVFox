<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogo productos Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero
 * @Descripcion	Importar ficheros de DBF
 *  */
		// Objetivo de esta aplicacion es:
		//	- Copiar DBF y guardar en directorio de copias de seguridad.
		// 	- Importar los datos copiados a MYSQL.
		

?>

<!DOCTYPE html>
<html>
<head>
<?php
	include './../../head.php';
?>
<script src="<?php echo $HostNombre; ?>/modulos/mod_importar/funciones.js"></script>
<script src="<?php echo $HostNombre; ?>/modulos/mod_importar/calculador.js"></script>

</head>
<body>
<?php 
	include './../../header.php';

?>

</body>
</html>
