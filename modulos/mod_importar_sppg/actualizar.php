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
	include ("./../../controllers/Controladores.php");
	$Controler = new ControladorComun; 
	// Cargamos parametros de XML donde tenemos parametros generales y los modulo.
	include_once ('parametros.php');
	$Newparametros = new ClaseParametros('parametros.xml');
	$parametros = $Newparametros->getRoot();
// ---------   Obtenemos la tabla que vamos gestionar y tratar   ------------ //
	if ($_GET['tabla']){
		$tabla =$_GET['tabla'];
		// Comprobamos si existe tabla.
		if (!in_array($tabla,$Conexiones['1']['tablas'])){
			print 'No existe la tabla';
			// Si no existe no continuamos.
			return;
		}
	}
// -------------- Obtenemos de parametros cajas con sus acciones ---------------  //
	$VarJS = $Controler->ObtenerCajasInputParametros($parametros);
	?>
	
	<script type="text/javascript">
		<?php echo $VarJS;?>
		var registros = { 'tpv' : [] ,'importar' : [] };
		var tabla = '<?php echo $tabla;?>'; 
		
	</script>

<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
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
	//~ include_once ("./classTabla.php");

// ---------------  Montamos array campos que obtenemos XML parametros   ------------- //
	$campos = array();
	$datos_tablas = array(); // Un array sencillo donde tenemos los campos los que podemos generar variables JS
							 // para reallizar la busqueda del registro la tabla importar.
	$datos_tablas['tablas']['importar'] = $tabla;
	
	
	//~ echo '<pre>';
	//~ print_r($parametros->tablas->tabla);
	//~ echo '</pre>';
	
	
	$tabla_importar = TpvXMLtablaImportar($parametros,$tabla);
	$objConsultas = $Newparametros->setRoot($tabla_importar);
	//~ echo '<pre>';
	//~ print_r($parametros);
	//~ echo '</pre>';
	
	$consultas = $Newparametros->Xpath('consultas//consulta[@tipo="obtener"]','Valores');
	
	
// -------- Obtenemos los campos de la tabla importar ----------- //
	foreach ($tabla_importar->campos->children() as $campo){;
		$nombre_campo =(string) $campo['nombre'];
		if (isset($campo->tipo)){
			if ((string) $campo->tipo === 'Unico'){
				$datos_tablas['importar']['campos'][]=$nombre_campo;
			}
		}
		$x = 0;
		foreach ($campo->action as $action) {
			// obtenemos las acciones para encontrar
			$campos[$nombre_campo]['acciones_buscar'][$x]['funcion'] = (string) $action['funcion'];
			$campos[$nombre_campo]['acciones_buscar'][$x]['tabla_cruce'] =(string) $action['tabla_cruce'];
			$campos[$nombre_campo]['acciones_buscar'][$x]['campo_cruce'] =(string) $action['campo_cruce'];
			$campos[$nombre_campo]['acciones_buscar'][$x]['description'] =(string) $action['description'];
			$x++;
		}
	}

// --------- Obtenemos los parametross tpv que para inserta,modificar datos en tpv --------- //
	$parametros_tpv = TpvXMLtablaTpv($tabla_importar);
	$datos_tablas['tpv'] =$parametros_tpv['tpv'];
	$datos_tablas['tablas']['tpv'] =$parametros_tpv['tablas']['tpv'];

	echo '<pre>';
		print_r($datos_tablas);
	echo '</pre>';



// ---------- Obtenemos de parametros/configuracion tipos de Registros -------- //
	$tiposRegistros = array();
	foreach ($parametros->configuracion->tipos_registros as $tipos){
		foreach ($tipos as $tipo){
			$clase = (string) $tipo['clase'];
			$tiposRegistros[$clase]['texto']= (string) $tipo->texto;
			$tiposRegistros[$clase]['consulta']= (string) $tipo->consulta;
		}
	}


// ----------- Obtenemos registros sin tratar y hacemos resumen registros por su estado -------------- //
	$Registros_sin = array();
	foreach ( $tiposRegistros as $key => $tipo){
		$resultado = $Controler->contarRegistro($BDImportDbf,$tabla,$tipo['consulta']);
		$tiposRegistros[$key]['Num_items'] = $resultado;
		if ($key ==='sin'){
			// Obtenemos los registros que vamos tratar
			if ($tiposRegistros['sin']['Num_items'] > 100){
				// Para evitar exceso de memoria... Solo obtenemos 100 primero registros que están sin tratar
				$tipo['consulta'] .= '  LIMIT 100';
			}
			$resultado = $Controler->consultaRegistro($BDImportDbf,$tabla,$tipo['consulta']);
			// Asocio array a 'imporar' por si mas adelante queremos ver los nuevos o modificados.
			$Registros_sin['importar'] = $resultado['Items'];
		}
	}
// ---  Comprobamos si los registros sin tratar existe y son iguales o similares en tpv. --- //
	$registros_tpv = array();
	$comprobaciones = array();
	foreach ($Registros_sin['importar'] as $item=>$registro){
		// Montamos variable JS que enviaremos para identificar el registro importar

		// Ahora buscamos un registro similar o igual en Tpv
		$respuesta = BuscarIgualSimilar($BDTpv,$tabla,$campos,$registro);
		// Añadimos array las repuestas.
		$comprobaciones[$item] = $respuesta['comprobacion'];
		if (isset($respuesta['tpv'])){
			//Quiere decir que encontro uno igual o similares
			// Hay que tener que igual es igual en campo que consideramos que es suficientemente 
			// identificador para decir que es mismo, pero no sabemos que si se modifico algún campo.
			$registros_tpv[$item]=$respuesta['tpv'];
			// Ahora comprobamos si hay alguno igual de 
			// -- Si encontro similar o igual --
			// Comprobamos que los datos sean iguales 
			
		}
	}
	//~ echo '<pre>';
	//~ print_r($Registros_sin);
	//~ echo '</pre>';
	
?>



<div class="container">
	<div class="col-md-12">
		<h2>Preparamos para actualizar tabla <?php echo $tabla;?>.</h2>
	</div>	
	<div class="col-md-12">
		<p><strong>Resumen el estado actual BDImportar</strong></p>
	<p>
	<?php
	// Mostramos resumen en etiquetas
	foreach ($tiposRegistros as  $key => $tipo){
		echo ' <span class="label label-default">'.$tipo['texto'].' registros:'.$tipo['Num_items'].'</span> ';
		
	}
	?>
	</p>
	</div>
	<div class="col-md-12">
		<?php 
		foreach ($Registros_sin['importar'] as $item =>$registro_sin){
		?>
		<div class="row" id="fila<?php echo $item;?>" >
			<div class="col-md-12">
				<h3>Registro: <?php echo $item;?></h3>
				<div class="text-right">
					<select id="accion_general_<?php echo $item?>">
						<option value="Nuevo">Crear nuevo</option>
						<?php 
						if ($comprobaciones[$item]['encontrado_tipo'] !== 'No existe mismo-Ni similar'){?>
								<option value="Modificado">Aplicar cambios - Modificar</option>
						<?php } ?>
						<option value="Descartado" selected="true">Descartar</option>
					</select> 
					<?php 
					// Montamos variables JS para poder buscar el registro de la tabla import
					$datos = array();
					foreach ($datos_tablas['importar']['campos'] as $campo){
						if (isset($registro_sin[$campo])){ 
							$datos['importar'][] = array ( $campo =>$registro_sin[$campo]);
						}
					}
					if (isset($datos_tablas['tpv']['campos'])){
						foreach ($datos_tablas['tpv']['campos'] as $campo){
							if (isset($registros_tpv[$campo])){ 
								$datos['tpv'][] = array ( $campo =>$registro_sin[$campo]);
							}
						}
					}
					
					echo '<script>';
					// Añadimos registros datos a variable global.
					echo "registros.tpv[".$item."] = [];";
					echo "registros.importar[".$item."] = [];";
					foreach ($datos as $tipo =>$dato){
						echo "registros.".$tipo."[".$item."].push(".json_encode($dato).");";
					}
					echo '</script>';
					// Ahora controlamos si hubo campos import por lo menos, sino muestro un error
					if (count($datos['importar']>0)){
					?>
					<button id="Ejecutar_<?php echo $item?>" class="btn btn-primary" data-obj="botonEjecutar" onclick="controlEventos(event)">Ejecutar</button>
					<?php 
					} else {
						echo '<pre>Error !, no hay campos tabla importar identificativos unicos</pre>';
					}
					?>
				</div>
			</div>
			<div class="col-md-4">
				<h4>Registro de importarDBF</h4>
				<?php 	echo '<pre>';
						print_r($registro_sin);
						echo '</pre>';
				?>
			</div>
			<div class="col-md-4">
				<h4>Registro de Tpv</h4>
				<?php 	
					if (isset($registros_tpv[$item])){
						echo '<pre>';
						print_r($registros_tpv[$item]);
						echo '</pre>';
					}
				?>
				
			</div>
			<div class="col-md-4">
				<h4>Que encontramos</h4>
				
				<?php
					echo '<pre>';
						print_r($comprobaciones[$item]['encontrado_tipo']);
					echo '</pre>';
				?>
			</div>
		</div>
		<?php
		}
		?>
	</div>
</div>
</body>
</html>
