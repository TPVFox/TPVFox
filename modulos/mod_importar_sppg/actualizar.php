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

// ---------------  Montamos array campos que obtenemos XML parametros   ------------- //
	$campos = array();
	$datos_tablas = array(); // Un array sencillo donde tenemos los campos los que podemos generar variables JS
							 // para reallizar la busqueda del registro la tabla importar.
	$datos_tablas['tablas']['importar'] = $tabla;
	
	
	//~ echo '<pre>';
	//~ print_r($parametros->tablas->tabla);
	//~ echo '</pre>';
	$parametros_importar = TpvXMLtablaImportar($parametros,$tabla);
	$objConsultas = $Newparametros->setRoot($parametros_importar);
	//~ echo '<pre>';
	//~ print_r($parametros);
	//~ echo '</pre>';
	// Montamos Array parametros de comprobaciones.
	$parametros__comprobaciones = array();
	$parametros_comprobaciones['Mismo'] = $Newparametros->Xpath('comprobaciones//comprobacion[@nombre="Mismo"]');
	$parametros_comprobaciones['Similar'] = $Newparametros->Xpath('comprobaciones//comprobacion[@nombre="Similar"]');
	$parametros_comprobaciones['NoEncontrado'] = $Newparametros->Xpath('comprobaciones//comprobacion[@nombre="NoEncontrado"]');

	//~ $parametros_comprobaciones['Mismo'] = $Xml_comprobaciones
	
// -------- Obtenemos los campos de la tabla importar ----------- //
// Recuerda que en el Xml tabla debe tener algun campo como tipo= Unico para poder identificarlo correctamente
	$campos = array();
	foreach ($parametros_importar->campos->children() as $campo){;
		$nombre_campo =(string) $campo['nombre'];
		if (isset($campo->tipo)){
			if ((string) $campo->tipo === 'Unico'){
				$datos_tablas['importar']['campos'][]=$nombre_campo;
			}
		}
		// Creamos array campos que utilizamos para BuscarIgualSimilar
		$campos[$nombre_campo] = CamposAccionesImportar($campo);
	}
	//~ echo '<pre>';
		//~ print_r($campos);
	//~ echo '</pre>';
// --------- Obtenemos los parametross tpv que para inserta,modificar datos en tpv --------- //

	$parametros_tpv = TpvXMLtablaTpv($parametros_importar);
	$datos_tablas['tpv'] =$parametros_tpv['tpv'];
// -----------  Obtenemos el nombre de las tablas que tenemos en elemento tpv ------------ //
// Pueden ser varias....
	$datos_tablas['tablas']['tpv'] = $Newparametros->Xpath('tpv/tabla/nombre','Valores');

	//~ echo '<pre>';
		//~ print_r($datos_tablas['tpv']);
	//~ echo '</pre>';

// ---------- Obtenemos de parametros/configuracion tipos de Registros -------- //
	$tiposRegistros = array();
	foreach ($parametros->configuracion->tipos_registros as $tipos){
		foreach ($tipos as $tipo){
			$clase = (string) $tipo['clase'];
			$tiposRegistros[$clase]['texto']= (string) $tipo->texto;
			$tiposRegistros[$clase]['consulta']= (string) $tipo->consulta;
		}
	}
	
	
// ----------- Obtenemos registros sin tratar y hacemos resumen resto de registros por su estado -------------- //
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
// ---  Obtenemos los parametros de comprobaciones de cada fichero --- //
	
// ---  Realizamos comprobaciones y montamos parametros para cada registro.  -------------- //
	$registros_tpv = array();
	
	//~ echo '<pre>';
	//~ print_r($parametros_comprobaciones['Mismo'][0]->procesos->before->action);
	//~ echo '</pre>';
		
	$comprobaciones = array();
	foreach ($Registros_sin['importar'] as $item=>$registro){
		// Comprobamos si los registros sin tratar existe ( mismo) o similar en tpv. --- //
		$respuesta = BuscarIgualSimilar($BDTpv,$campos,$registro);
		// Añadimos array las repuestas.
		$comprobaciones[$item]['resultado'] = $respuesta['comprobacion'];
		$resultado_b = $comprobaciones[$item]['resultado']['encontrado_tipo'];
		// Montamos botonera de opciones generales con JS
		$comprobaciones[$item]['opt_generales'] = MontarHtmlOpcionesGenerales($parametros_comprobaciones,$resultado_b,$item);
		if (isset($parametros_comprobaciones[$resultado_b][0]->procesos->before->action)){
			$Xmlfunciones = $parametros_comprobaciones[$resultado_b][0]->procesos->before->action;
		} else {
			$Xmlfunciones = array();
		}
		if (count($Xmlfunciones)){
			$comprobaciones[$item]['proceso_before'] = BeforeProcesosOpcionesGeneralesComprobaciones($Xmlfunciones,$item);
		}
		if (isset($respuesta['tpv'])){
			//Quiere decir que encontro uno igual o similares
			// Comprobamos que solo tengamos una respuesta ya que sino será similar.
			if ($respuesta['tpv']['NItems'] >0){
				if ($comprobaciones[$item]['resultado']['encontrado_tipo'] === "Mismo" && $respuesta['tpv']['NItems'] > 1){
					// Cambiamos dato a similar y marcamos registro comprobaciones como error.
					$comprobaciones[$item]['resultado']['encontrado_tipo'] ="Similar";
					$comprobaciones[$item]['estado'] = 'Error - Cambio tipo encontrado Similar';
				}
			}
			
			// Hay que tener que igual es igual en campo que consideramos que es suficientemente 
			// identificador para decir que es el mismo, pero no sabemos que si se modifico algún campo.
			$procesos = 'Si' ; // De momento entiendo que siempre 
			if ($comprobaciones[$item]['resultado']['encontrado_tipo']=== "Mismo"){
				// Debería:
				//  - procesos de comprobaciones = Mismo
				
				$procesos = 'Si' ; // Mientras no hago las diferencias.
			}
			if ($procesos === 'Si'){
				$registros_tpv[$item]=$respuesta['tpv'];
			}
		}
		// Montamos Variables JS para cada Item
		$datos = array();
		foreach ($datos_tablas['importar']['campos'] as $campo){
			if (isset($registro[$campo])){ 
				$datos['importar'][] = array ( $campo =>$registro[$campo]);
			}
		}
		if (isset($datos_tablas['tpv']['campos'])){
			foreach ($datos_tablas['tpv']['campos'] as $campo){
				if (isset($registros_tpv[$item])){
					// Quiere decir que hemos encontrado datos..
					// Si Nitems encontrado es uno.
					if ($registros_tpv[$item]['NItems'] === 1){ 
						$datos['tpv'][] = array ( $campo =>$registros_tpv[$item]['Items'][0][$campo]);
					}
				}
			}
		}
		$comprobaciones[$item]['JS'] = $datos;
		
		
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
					<?php 
					echo $comprobaciones[$item]['opt_generales'];
					// Montamos variables JS para poder buscar el registro de la tabla import
					echo '<script type="text/javascript">';
					// Añadimos registros datos a variable global.
					echo "registros.tpv[".$item."] = [];";
					echo "registros.importar[".$item."] = [];";
					foreach ($comprobaciones[$item]['JS'] as $tipo=>$dato){
						echo "registros.".$tipo."[".$item."].push(".json_encode($dato).");";
					}
					echo '</script>';
					// Ahora controlamos si hubo campos import por lo menos, sino muestro un error
					if (count($comprobaciones[$item]['JS']['importar']>0)){
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
						echo 'Numero Items encontrados:'.$registros_tpv[$item]['NItems'];
						echo '<pre>';
						print_r($registros_tpv[$item]['Items']);
						echo '</pre>';
					}
				?>
				
			</div>
			<div class="col-md-4">
				<h4>Resultado Busqueda</h4>
				
				<?php
					echo '<pre>';
						print_r($comprobaciones[$item]['resultado']['encontrado_tipo']);
					echo '</pre>';
					if (isset($comprobaciones[$item]['proceso_before'])){
						foreach ( $comprobaciones[$item]['proceso_before'] as $htmlBefore){
							echo $htmlBefore;
						}
					}
					//~ echo '<pre>';
						//~ print_r($comprobaciones[$item]);
					//~ echo '</pre>';
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
