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
	// Creamos variables de los ficheros para poder automatizar el añadir ficheros.
	$nom_ficheros = array();
	$nom_ficheros[] ='proveedo';// El que vamos utilizar al crear tb la tabla en BDimport
	$nom_ficheros[] ='albprot';
	$nom_ficheros[] ='albprol';
	$nom_ficheros[] ='articulo';
	$nom_ficheros[] ='clientes';
	// [PENDIENTE]
	// La idea es hacer un JSON que luego en funciones js, lo obtenga, para eliminar la variables globales que tenemos al principio
	// del fichero funciones.js (nombretabla) , así se añadimos algun fichero, solo tengamos que hacer aquí.
	
?>
<script src="<?php echo $HostNombre; ?>/modulos/mod_importar/funciones.js"></script>
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
	// Ahora comprobamos si tenemos tablas en Mysql que tenga Estado
	$actualizar = array();
	foreach ($nom_ficheros as $nombreTabla){
		$campos =ObtenerEstructuraTablaMysq($BDImportDbf,$nombreTabla,'no');
		foreach ($campos as $campo){
			// Ahora comprobamos que si existe campo estado
			if ($campo === 'estado'){
				// ahora debería comprobar si estan todos cubiertos o no..
				// de momento no lo hago..
				$actualizar[$nombreTabla] = 'Existe Estado';
			}
		}
	}
	// Ahora tenemos un array con los campos de la tablas .
	
	echo '<pre>';
	print_r($actualizar);
	echo '</pre>';
?>

<?php
	// Variables de template ( vista) 
	$clases_td = array();
	$clases_td[] ="CEstruct";
	$clases_td[] ="CBorrar";
	$clases_td[] ="CCrear";
	$clases_td[] ="CVaciar";
	$clases_td[] ="CImportar";
	//~ $clases_td[] ="CActualizar"> // Esto no lo puedo montar... hay parametroc control en fichero.
	// Montamos htmlClass , ya que siempre es el mismo... 
	$html_clasetd = '';
	foreach ($clases_td as $clasetd){
		$html_clasetd .= '<td class="'.$clasetd.'"></td>';
	}
	
?>

<div class="container">
	<div class="col-md-6">
		<h2>Importación de datos a DBF de TPV.</h2>
		<p> La importación de DBF de SPPGTpv consiste en dos faxes:</p>
		<h3>1.-Importacion de DBF a MYSQL</h3>
		<p>Esta faxe <b>inicia automaticamente</b> al entrar en esta pagina, consiste es añadir los datos DBF a BDImport de Mysql.<br/>Las tablas de DBF las obtenemos en configuracion (homer/solucion40/www/superoliva/datos/DBF71)<p>
		<p><b>[PENDIENTE]</b> Crear un proceso para copiarlas automatizado o indicar donde optenerlar. <a title="De momento lo hacemos manual, ya que no podemos indicar fuera www porque generar un error">(*)</a></p>
		<h4>Procesos que realizamos en importar</h4>
		<ol>
			<li> Comprobamos podemos obtener estructura de BDF.
				<ol>
					<li>NO-> Pasamos al siguiente fichero .</li>
					<li>SI-> Pasamos al siguiente punto.</li>
				</ol>
			</li>
			<li> Si la estructura es igual a la que tenemos en tablas mysql <a title="Si es distinta puede suceder que ya hubieramos actualizado">(*)</a>
				<ol>
					<li>NO-> Creamos nuevamente tabla .</li>
					<li>SI-> Eliminamos tabla y añadimos contenido DBF</li>
				</ol>
			</li>
		</ol>
		<p><strong>Nota:</strong>Si falla añadiendo datos, por lo entonces ... esa tabla puede estar corrupta.</p>	
		<h3>2.-Actualizar BDImport con BDTpv</h3>
		<p>La actualizacion es una sincronización en la que comprobamos y añadimos articulos, proveedores, clientes</p>
		<h4>Procesos que realizamos en actualizar</h4>
		<ol>
			<li> <b>[PENDIENTE]</b> Comprobar si ya empezamos con actualizacion.</li>
			<li> Añadimos campos a tablas que necesitamos para poder importar.</li>
		</ol>
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
					<a title="Si borro la tabla BDimportar">
						<span class="glyphicon glyphicon-trash"></span>
					</a>
				</th>
				<th><!-- Creada -->
					<a title="Se creo la tabla en BDimportar">
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
			
			<?php 
			foreach ($nom_ficheros as $nom_fichero){
				echo '<tr id="id'.$nom_fichero.'">';
				echo '		<th>'.$nom_fichero.'.dbf</th>';
				echo $html_clasetd;
				if (isset($actualizar[$nom_fichero])){
					echo '<td class="CActualizar">Existe</td>';
				} else {
					echo '<td class="CActualizar"></td>';

				}
				echo '  </tr>';
			}
			?>
			</tbody>
		 </table>		
		</div>		
		<?php
			//creo boton para crear tabla en mysql, 1º comprobar que no existe tabla, 2º conseguir estructura 
			//recibircsv.php?subida=0
		?>
		<div class="btn-actualizar" style="display:none;">
			
				<div class="form-group">
					<label>Sincronizar tablas importadas con las que ya tenemos:</label>
					<input onclick="ActualizarInicio()" type="submit" value="Sincronizar" />
				</div>
			
		</div>

	</div>	
</div>
<script>
	//Iniciamos importacion.
	ImportInicio('import_inicio')
</script>
</body>
</html>
