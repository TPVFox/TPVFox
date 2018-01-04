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
	// Creamos variables de los ficheros DBF que vamos añadir de forma automatizada a TPV.
	// Inicialmente se añaden tambien a BDimportar
	$nom_ficheros = array(
					'proveedo','articulo','clientes','precprov'
					);
	// [ANTES CARGAR FUNCIONES JS]
	// Montamos la variables en JAVASCRIPT de nombre_tabla que lo vamos utilizar .js
	?>
	
<script src="<?php echo $HostNombre; ?>/modulos/mod_importar_sppg/funciones.js"></script>
	<?php
	// Controlamos ( Controllers ... fuera de su sitio ... :-)
	if (isset($Usuario['estado'])){
		if ($Usuario === "Incorrecto"){
			return;	
		}
	}
	?>

</head>
<body>
<?php 
	include './../../header.php';
	include_once ("./funciones.php");
	include ("./../../controllers/Controladores.php");
	$Controler = new ControladorComun; 
	// Ahora comprobamos si tenemos tablas en Mysql que tenga Estado
	
?>



<div class="container">
	<div class="col-md-12">
		<h2>Preparamos para actualizar.</h2>
		
	</div>	
</div>
</body>
</html>
