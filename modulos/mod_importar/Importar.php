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
</head>
<body>
<?php 
	include './../../header.php';
	include_once ("./funciones.php");

?>

<?php

	// Este código va para funciones...
	// Ruta completa fichero : /home/solucion40/www/superoliva/datos/DBF71/albprol.dbf
	
	//~ ($fichero,$numFinal,$numInic,$campos)
	//~ $fichero = $RutaServidor.$CopiaDBF.'/albprol.dbf';
	
	//~ $respuesta = LeerDbf($fichero);
	//~ $respuesta = LeerDbf($fichero,$numFinal,$numInic,$campos);
	//~ $respuesta = LeerEstructuraDbf($fichero);
	//~ echo '<pre>';
	//~ print_r($respuesta);
	//~ echo '</pre>';
	
	
?>
<style type="text/css">
.listanumerada ol { counter-reset: item }
.listanumerada li{ display: block }
.listanumerada li:before { content: counters(item, ".") " "; counter-increment: item }
</style>
<div class="container">
	<div class="col-md-6">
		<h2>Importación de datos a DBF de TPV.</h2>
		<p> La importación de DBF de SPPGTpv consiste en dos faxes:</p>
		<div class="listanumerada">
		<ol>
			<li> Copia DBF:<span class="label label-danger">Manual</span><br/>
				 de :bart/tpv<br/>
				 en :homer/solucion40/www/superoliva/datos/DBF71.<br/>
				 Ya que fuera de www no me deja hacerlo... por lo menos por defecto.
			</li>
			<li> Realizar backup en Diario de copias.
			</li>
			<li> Luego se empieza importar Datos a Mysql
				<ol>
					<li>Articulos</li>
					<li>Proveedores</li>
					<li>Albaranes de Proveedores:
						<ol>
						<li>Albprovl.dbf</li>
						<li>Albprovt.dbf</li>
						</ol>
					</li>
				</ol>
			</li>
		</ol>
		</div>
	<p><strong>Importar:</strong>Nos referimos a actualizar o sobreescribir</p>	
	</div>
		
	<div class="col-md-6">
		<div>
		<div class="text-center" id="idCabeceraBarra"></div>

	    <div class="progress" style="margin:0 100px">
			<div id="bar" class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                   0 % completado
             </div>
		</div>
		</div>
		<div id="resultado"></div>

		<div>
		<h3 class="text-center"> Control de procesos de importacion</h3>
		<table class="table table-bordered">
			<thead>
			  <tr>
				<th></th>
				<th>LeerEstructura</th>
				<th>LeerDbf</th>
				<th>Importar Mysql</th>
			  </tr>
			</thead>
			<tbody>
			  <tr id="idproveedor">
				<th>proveedo.dbf</th>
				<td class="CLeerEstructura"></td>
				<td class="CLeerDbf"></td>
				<td class="CInsertMsyq"></td>
			  </tr>
			  <tr id="idalbprot">
				<th>albprot.dbf</th>
				<td class="CLeerEstructura"></td>
				<td class="CLeerDbf"></td>
				<td class="CInsertMsyq"></td>
			  </tr>
			   <tr id="idalbprol">
				<th>albprol.dbf</th>
				<td class="CLeerEstructura"></td>
				<td class="CLeerDbf"></td>
				<td class="CInsertMsyq"></td>
			  </tr>
			 <tr id="idarticulo">
				<th>articulo.dbf</th>
				<td class="CLeerEstructura"></td>
				<td class="CLeerDbf"></td>
				<td class="CInsertMsyq"></td>
			  </tr>
			</tbody>
		 </table>		
		</div>		
		<?php
			//creo boton para crear tabla en mysql, 1º comprobar que no existe tabla, 2º conseguir estructura 
			//recibircsv.php?subida=0
		?>
		<form role="form" enctype="multipart/form-data" action="Importar.php" method="POST">
			<div class="form-group">
				<label>Crear tablas:</label>
				<input type="submit" value="Crear tabla" />
			</div>
		</form>
	</div>	
</div>
<script>
	Inicio('pulso_inicio')
</script>
</body>
</html>
