<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogo productos Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero
 * @Descripcion	Importar ficheros de web con Virtuemart
 * */	

?>

<!DOCTYPE html>
<html>
<head>
<?php
	include './../../head.php';
	// Creamos variables de los ficheros para poder automatizar el añadir ficheros.
	
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
	// Ahora creamos la tablas temporales 
	$resp = crearTablaTempArticulosComp ($BDVirtuemart,$BDTpv)
?>

<div class="container">
	<div class="col-md-6">
		<h2>Importación de datos de Virtuemart a TPV.</h2>
		<p> Recuerda que esto es un importación desde 0, todas las tpv tienen que estar vacias y los campos autoincremental en 0.La importación de Virtuemart en varias faxes:</p>
		<h3>1.-Crear una tabla temporal `tmp_articulosCompleta` tanto en BDTpv compo BDVirtuemart</h3>
		<p>Creamos una tabla temporal, la cual al cerrar la sessión ya no existe esa tabla.<p>
		<h4>Campos de esta tabla</h4>
		<ul>
			<li>idArticulo int(11) AUTO_INCREMENT PRIMARY KEY</li>
			<li>crefTienda VHARCHAR(18);</li>
			<li>idTienda int(11),</li>
			<li>articulo_name VARCHAR(100),</li>
			<li>iva DECIMAL(4,2),</li>
			<li>codbarras VARCHAR(18),</li>
			<li>beneficio DECIMAL(5,2),</li>
			<li>costepromedio DECIMAL(17,6),</li>
			<li>estado VARCHAR(12),</li>
			<li>pvpCiva DECIMAL(17,6),</li>
			<li>pvpSiva DECIMAL(17,6),</li>
			<li>idProveedor int(11),</li>
			<li>fecha_creado DATETIME,</li>
			<li>fecha_modificado DATETIME</li>
		</ul>
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
				<th><!-- Estruct -->
					<a title="Indica dos cosas distintas, que puede obbtener estructura ( sino NO obtenter pasa al siguiente) y tambien comprueba que la estrucutra es la misma msyql,(sino continua borrando y creandola de nuevo)">
						<span class="glyphicon glyphicon-th"></span>
					</a>
				</th>
				<th><!-- Borrada -->
					<a title="Si borro la tabla temporal">
						<span class="glyphicon glyphicon-trash"></span>
					</a>
				</th>
				<th><!-- Creada -->
					<a title="Se creo la tabla temporal">
						<span class="glyphicon glyphicon-log-in"></span>
					</a>
				</th>
				<th><!-- Vaciar -->
					<a title="Se limpio la tabla, vacio">
						<span class="glyphicon glyphicon-repeat"></span>
					</a>
				</th>
				
				
				<th>Importar</th>
				<th>Actualizar</th>
			  </tr>
			</thead>
			
			</tbody>
		 </table>		
		</div>		
	
	</div>	
	<div>
	
	</div>
</div>
</body>
</html>
