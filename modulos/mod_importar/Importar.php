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
        //  - Poner estado importado si es un producto nuevo.
        //  - Poner estado actualizaco si cambio alguno de los campos siguiente
        //              - Precio
        //              - Nombre
        //              - Stock

     
    include_once './../../inicial.php';

    
  
?>
<!DOCTYPE html>
<html>
<head>
<?php
    include_once $URLCom.'/head.php';
	
?>

</head>
<body>
<?php 
     include_once $URLCom.'/modulos/mod_menu/menu.php';
	
?>



<div class="container">
	<div class="col-md-12">
	<h2 class="center">Importación de datos a TPVFOX.</h2>
	</div>
	<div>
    <?php
        echo '<pre>';
        print_r($thisTpv);
        echo '</pre>';
    ?>

	</div>	
</div>
</body>
</html>
