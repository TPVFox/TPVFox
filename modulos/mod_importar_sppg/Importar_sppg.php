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
	
		
	// Obtenemos parametros con los nombres de las tablas que vamos importar
	// [RECUERDA]
	// El orden carga de las tablas es importatne ya algunas depende de otras, por ello deben tener 
	// la tabla que independiente primero y luego las otras, para una correcta importacion.
	include_once ('parametros.php');
	$ClasesParametros = new ClaseParametros('parametros.xml');
	$parametros = $ClasesParametros->getRoot();
	$nom_ficheros = $ClasesParametros->Xpath("tablas/tabla/nombre",'Valores');
	// ---------- Obtenemos de parametros/configuracion tipos de Registros -------- //
	$tiposRegistros = array();
	foreach ($parametros->configuracion->tipos_registros as $tipos){
		foreach ($tipos as $tipo){
			$clase = (string) $tipo['clase'];
			$tiposRegistros[$clase]['texto']= (string) $tipo->texto;
			$tiposRegistros[$clase]['consulta']= (string) $tipo->consulta;
		}
	}
	// -- Incluimos funciones y controlador general. --//
	include_once ("./funciones.php");
	include ("./../../controllers/Controladores.php");
	$Controler = new ControladorComun; 
	// ----------- Creamos objeto fichero -----------------//
	// 1.- Comprobamos que las tablas obtenidad si tienen ya el campo Estado, por lo que ya actualizamos
	// 2.- Creamos html fila del fichero.
	// [RECUERDA]
	// Que si tiene estado ya se trato,por lo que deberíamos indicalos .
	$clases_td = array("CEstruct","CBorrar","CCrear","CImportar");
	$ficheros = array();
	$cont_tablas_estado = 0; // Contador que utilizo para saber y las tablas que vamos analizar ya pulsaron actualizar.
	foreach ($nom_ficheros as $nombreTabla){
		$ficheros[$nombreTabla] = $Controler->InfoTabla($BDImportDbf,$nombreTabla,'no');
		// Montamos html para crear la fila de la tabla de ese fichero.
		$html_tr = '<tr id="id'.$nombreTabla.'"><th>'.$nombreTabla.'.dbf</th>';
		foreach ($clases_td as $clasetd){
			$html_tr .= '<td class="'.$clasetd.'"></td>';
		};
		$html_registros = '';
		if (in_array('estado',$ficheros[$nombreTabla]['campos'])){
			$cont_tablas_estado++;
			foreach ($tiposRegistros as $key=>$tipo){
					echo 'Entro';
					$contar_registro_tipo = $Controler->contarRegistro($BDImportDbf,$nombreTabla,$tipo['consulta']);
					$tiposRegistros[$key]['Num_items'] = $contar_registro_tipo;
					// Montamos el html queremos mostrar en columna de registros
					
				}
				$html_registros =$tiposRegistros['todos']['Num_items'].'/'.$tiposRegistros['sin']['Num_items'];
				$ficheros[$nombreTabla]['Estado']['tipos'] = $tiposRegistros;
		}
		if ($html_registros !== ''){
			$html_tr .= '<td class="CActualizar">'.$html_registros.
						'</td><td class="LinkActualizar"><a href="actualizar.php?tabla='.
						$nombreTabla.'"><span class="glyphicon glyphicon-eye-open"></span></a></td>';
		} else {
			$html_tr .= '<td class="CActualizar"></td><td class="LinkActualizar"></td>';
		}
		$ficheros[$nombreTabla]['html_tr'] = $html_tr.'</tr>';
	}	
	$ficheros['_tablas_actualizadas']= $cont_tablas_estado;	

	

	// Control de GET ya que:
	// Si existen datos en la tabla importar, hay que indicar que empresa selecciono en su caso.
	if (count($_GET)>0){
		// Quiere decir hubo enviod de datos...
	}
	
	
	

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
	
	// Ahora tenemos un array con los campos de la tablas .
	
	echo '<pre>';
	print_r($ficheros);
	echo '</pre>';
?>



<div class="container">
	<div class="col-md-12">
	<h2>Importación de datos a DBF de TPV.</h2>
	</div>
	<div class="col-md-5">
		<div class="col-md-12">
			<div class="col-md-6">
				<!--Selector de empresa -->
				<label for="sel1" title="El cruce con la tienda on-line es con virtuemart_id y en tabla tpv articulosTienda">Selecciona la tienda:</label>
				<small>Recuerda que debe informar donde tiene los datos en parametros.xml</small>
				<select <?php echo $disable_conf;?>  class="form-control" name="tiendaOnLine" id="sel1">
					<option value="0">Sin selecciona tienda fisica</option>
					<?php
					$porDefecto = ''; 
					foreach ($tiendasOnLine['items'] as $tiendaOnLine){
						if ($tienda_on_line_seleccionada===$tiendaOnLine['idTienda']){
							$porDefecto = 'selected';
						}
					?>
					<option <?php echo $porDefecto;?> value="<?php echo $tiendaOnLine['idTienda'];?>" >
					<?php echo $tiendaOnLine['idTienda'].'-'.$tiendaOnLine['dominio'];?>
					</option>
					<?php
					}
					?>
				</select>
			</div>
			<div class="col-md-6">
				<!-- Caja muestra ruta de parametro para esa empresas -->
				<!-- Se debe carja con JSON al cambiar valor de select-->
				<input type="text" name="directorioRuta" value="<?php echo $rutaEmpresa ;?>">
			</div>
		</div>
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
				<th>
					<span title="Numero Registos que BDImportar (total/Sin tratar)" class="glyphicon glyphicon-th-list"></span>
				</th>
				<th>Continuar</th>
			  </tr>
			</thead>
			
			<?php 
			foreach ($ficheros as $nom_fichero =>$fichero){
				echo $fichero['html_tr'];
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
				if (count($ficheros['_tablas_actualizadas'])>0){
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
