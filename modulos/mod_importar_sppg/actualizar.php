<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogo productos Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero
 * @Descripcion	Importar ficheros de DBF
 *  */
		// Objetivo de esta aplicacion es:
		//	- Comparar los datos de DBF y realizar las acciones que le indicamos Xml de cada tabla

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


// ||-------------------------------- Obtenemos  los arrays de Xml ----------------------------------------------||	// 
//				1.- datos_tablas:  Obtenemos datos parametros y montamos array 								 		//
// 					Ejemplo estructura:																				//
//					Array(																							//
// 						tablas = array(...)	Nombres tablas implicadas en importacion y tpv.							//
//						importar = array(...)																		//
//						tpv = array(...)																			//
// 						acciones = array (...)	Campos y acciones de BDFImportar									//
// 						comprobaciones = array (...)																//
// Esto debería se un objecto en el cual se controlara que obtenemos correctamente los datos.						//
// ||------------------------------------------------------------------------------------------------------------||	//
	$datos_tablas = array(); // Un array sencillo donde tenemos los campos los que podemos generar variables JS
							 // para reallizar la busqueda del registro la tabla importar.
	$datos_tablas['tablas']['importar'] = $tabla;
	// Recuerda que en el Xml tabla debe tener algun campo como tipo= Unico para poder identificarlo correctamente
	$parametros_importar = TpvXMLtablaImportar($parametros,$tabla); // Obtenemos parametros tabla
	foreach ($parametros_importar->campos->children() as $campo){;
		$n =(string) $campo['nombre'];
		if (isset($campo->tipo)){
			if ((string) $campo->tipo === 'Unico'){
				$datos_tablas['importar']['campos'][]=$n;
			}
		}
		// Creamos array campos que utilizamos para BuscarIgualSimilar
		$datos_tablas['acciones'][$n] = CamposAccionesImportar($campo); // Los campos (BDImport) y las acciones de la tabla
	}
	// ------ Obtenemos los parametross para inserta,modificar datos en tpv ----- //
	$datos_tablas['tpv'] =TpvXMLtablaTpv($parametros_importar);
	// --------- Obtenemos el nombre de las tablas que elementos tpv ------------ //
	// Pueden ser varias....
	$datos_tablas['tablas']['tpv'] = $Newparametros->Xpath('tpv/tabla/nombre','Valores');
	// Ahora obtenemos comprobaciones a realizar.
	$Newparametros->setRoot($parametros_importar); // // Cambio objeto XML que tengo por defecto ahora es parametros_importar.
	
	// Montamos Array parametros de comprobaciones.
	$datos_tablas['comprobaciones']['Mismo'] = $Newparametros->Xpath('comprobaciones//comprobacion[@nombre="Mismo"]');
	$datos_tablas['comprobaciones']['Similar'] = $Newparametros->Xpath('comprobaciones//comprobacion[@nombre="Similar"]');
	$datos_tablas['comprobaciones']['NoEncontrado'] = $Newparametros->Xpath('comprobaciones//comprobacion[@nombre="NoEncontrado"]');
	
	
	
// ||------------------------- Terminamos montar el array de $datos_tablas  ----------|| //
	echo '<pre>';
	print_r($datos_tablas);
	echo '</pre>';
	
	
// ---------- Obtenemos de parametros/configuracion tipos de Registros -------- //
	$tiposRegistros = array();
	foreach ($parametros->configuracion->tipos_registros as $tipos){
		foreach ($tipos as $tipo){
			$c = (string) $tipo['clase'];
			$tiposRegistros[$c]['texto']= (string) $tipo->texto;
			$tiposRegistros[$c]['consulta']= (string) $tipo->consulta;
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
	
// ---  Realizamos comprobaciones y montamos parametros para cada registro.  -------------- //
	$registros_tpv = array();
			
	$comprobaciones = array();
	foreach ($Registros_sin['importar'] as $item=>$registro){
		// Comprobamos si los registros sin tratar existe ( mismo) o similar en tpv. --- //
		$c = $datos_tablas['acciones'];
		$respuesta = BuscarIgualSimilar($BDTpv,$c,$registro);
		// Añadimos array las repuestas.
		$comprobaciones[$item]['resultado'] = $respuesta['comprobacion'];
		$resultado_b = $comprobaciones[$item]['resultado']['encontrado_tipo'];
		// Montamos botonera de opciones generales con JS
		$p = $datos_tablas['comprobaciones'];
		$comprobaciones[$item]['opt_generales'] = MontarHtmlOpcionesGenerales($p,$resultado_b,$item);
		if (isset($p[$resultado_b][0]->procesos->before->action)){
			$X = $p[$resultado_b][0]->procesos->before->action; // funciones
		} else {
			$X = array(); // funciones
		}
		if (count($X)){
			// Obtenermos funciones antes (before)
			$comprobaciones[$item]['proceso_before'] = BeforeProcesosOpcionesGeneralesComprobaciones($X,$item);
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
		$d = array();
		foreach ($datos_tablas['importar']['campos'] as $campo){
			if (isset($registro[$campo])){ 
				$d['importar'][] = array ( $campo =>$registro[$campo]);
			}
		}
		if (isset($datos_tablas['tpv']['campos'])){
			foreach ($datos_tablas['tpv']['campos'] as $campo){
				if (isset($registros_tpv[$item])){
					// Quiere decir que hemos encontrado datos..
					// Si Nitems encontrado es uno.
					if ($registros_tpv[$item]['NItems'] === 1){ 
						$d['tpv'][] = array ( $campo =>$registros_tpv[$item]['Items'][0][$campo]);
					}
				}
			}
		}
		$comprobaciones[$item]['JS'] = $d;
		
		
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
	// Mostramos resumen registros encontrado segun su estado.
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
						// Ahora mostramos los items si los hay
						if ($registros_tpv[$item]['NItems'] >0 ){
							echo '<pre>';
							print_r($registros_tpv[$item]['Items']);
							echo '</pre>';
						}
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
