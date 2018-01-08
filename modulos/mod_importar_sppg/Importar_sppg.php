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
					'proveedo','articulo','clientes','precprov','pedclil','pedclit','albclil','albclit','facclit','facclil'
					);
	// [ANTES CARGAR FUNCIONES JS]
	// Montamos la variables en JAVASCRIPT de nombre_tabla que lo vamos utilizar .js
	?>
	<script type="application/javascript">
	var LimiteActual = 0;
	var LimiteFinal = 0;
	var iconoCargar = '<span><img src="../../css/img/ajax-loader.gif"/></span>';
	var iconoCorrecto = '<span class="glyphicon glyphicon-ok-sign"></span>';
	var iconoIncorrecto = '<span class="glyphicon glyphicon-remove-sign"></span>';
	var campos = [];
	var ficheroActual = '';
	var estadoImportacion = [];
	var nombretabla = [];
	<?php
	foreach ($nom_ficheros as $n_fichero){
		// Llenamos array javascript con los nombres ficheros
		echo "nombretabla.push('".$n_fichero."');";
	}
	?>
	</script>
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
	$actualizar = array();
	foreach ($nom_ficheros as $nombreTabla){
		$campos =ObtenerEstructuraTablaMysq($BDImportDbf,$nombreTabla,'no');
		foreach ($campos as $campo){
			// Ahora comprobamos que si existe campo estado
			if ($campo === 'estado'){
				// Ahora contamos cuantos tienen el campo estado y no tienen cubierto nada.
				$filtro = ' WHERE estado is null';
				$num_reg_sin_cubrir = $Controler->contarRegistro($BDImportDbf,$nombreTabla,$filtro);
				$actualizar[$nombreTabla]['Sin_Cubrir_Estado'] = $num_reg_sin_cubrir;
				$actualizar[$nombreTabla]['Estado'] = 'Existe Estado';
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
	$clases_td[] ="CImportar";
	//~ $clases_td[] ="CActualizar"> // Esto no lo puedo montar... hay parametroc control en fichero.
	// Montamos htmlClass , ya que siempre es el mismo... 
	$html_clasetd = '';
	foreach ($clases_td as $clasetd){
		$html_clasetd .= '<td class="'.$clasetd.'"></td>';
	}
	
?>

<div class="container">
	<div class="col-md-5">
		<h2>Importación de datos a DBF de TPV.</h2>
		<p>La importación de DBF de SPPGTpv consiste en dos faxes:</p>
		<h3>1.-Importacion de DBF a MYSQL</h3>
		<p> El objetivo es crear las tablas en Msql con los datos de las tablas BDF, que esta en la ruta que indicamos en configuracion en <b>$RutaServidor.$RutaDatos.'/'.'DBF71'.'/'</b>, que utilizamos en tareas.php</p>
		<p>Al pulsar en <b>botton de importar</b> hace:</p>
		<ol>
			<li><span class="glyphicon glyphicon-th"></span> Obtenemos estructura de BDF y Msyql de la tabla. <br/>
			 <ul>
				 <li>Si no puede obtener estructura de fichero dbf , lo pone en rojo y pasa el siguiente fichero</li>
				 <li>Si la estructura es igual que la que hay en Mysql, borra directamente los datos...</li>
			 </ul>
			</li>
			<li> Al obtener la estructura de DBF la comparamos con MYSQL.
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
		
	<div class="col-md-7">
		<div class="barra-proceso">
			<div class="text-center" id="idCabeceraBarra"></div>

			<div class="progress" style="margin:0 100px">
				<div id="bar" class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
					   0 % completado
				 </div>
			</div>
		</div>
		<div id="resultado"></div>

		<div>
		<h3 class="text-center"> Procesos para importar a BDImportar</h3>
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
				<th>
						<span title="Existe tabla en importarDBF con datos" class="glyphicon glyphicon-repeat"></span>
				</th>
				
				
				<th>Num.Registros <br/>pendientes preparar<br/> para Actualizar</th>
				<th>Continuar</th>
			  </tr>
			</thead>
			
			<?php 
			foreach ($nom_ficheros as $nom_fichero){
				echo '<tr id="id'.$nom_fichero.'">';
				echo '		<th>'.$nom_fichero.'.dbf</th>';
				echo $html_clasetd;
				if (isset($actualizar[$nom_fichero]['Estado'])){
					// Mostramos cuanto registros faltan por preparar para actualizar.
					echo '<td class="CActualizar">'.$actualizar[$nom_fichero]['Sin_Cubrir_Estado'].'</td>';
					echo '<td class="LinkActualizar"><a href="actualizar.php?tabla='.$nom_fichero.'"><span class="glyphicon glyphicon-eye-open"></span></a></td>';

				} else {
					echo '<td class="CActualizar"></td>';
					echo '<td class="LinkActualizar"></td>';
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
		<div class="btn-Importarr">
			<div class="form-group">
				<label>Importar tablas de DBF a Mysql (BDImportar):</label>
				<?php 
				if (count($actualizar)>0){
					// Quiere decir que realmente ya se pulso actualizar , por lo que si pulsa importar perdera lo preparado.
					echo '<div class="alert alert-warning">
							<strong>Warning!</strong> Te encuenta que si pulsas Importar, lo que tengas preparado para actualizar lo pierdes.
						</div>';
				}
				?>
				<input onclick="ControlPulsado('import_inicio')" type="submit" value="1.- Importar" />
			</div>
		</div>
		<div class="btn-actualizar" style="display:none;">
			
				<div class="form-group">
					<label>Sincronizar tablas importadas con las que ya tenemos:</label>
					<input onclick="ActualizarInicio()" type="submit" value="Sincronizar" />
				</div>
			
		</div>

	</div>	
</div>
</body>
</html>
