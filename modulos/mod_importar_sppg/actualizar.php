<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogo productos Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero
 * @Descripcion	Importar ficheros de DBF
 *  */
		// Objetivo de esta aplicacion es:
		//	- Comparar los datos de DBF que tenemos importado BD importarDBF con las que tenemos BD tpv
		//  - Realizar las acciones que le indicamos Xml de cada tabla

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
	
// ---------   Mosmandamos Arrya datos_tablas con el obejto ParametrsoTabl -------------------  //
	include_once('parametrostablas.php');
	$NewParametrosTabla = new ClaseArrayParametrosTabla($tabla,'parametros.xml');
	$parametros = $NewParametrosTabla->getParametros();
	$datos_tablas = array(); 
	$datos_tablas['tablas']['importar'] = $tabla;
	$datos_tablas['importar'] = $NewParametrosTabla->getCamposImportar();
	$datos_tablas['acciones']=$NewParametrosTabla->getAccionesImportar();
	$datos_tablas['tablas']['tpv'] = $NewParametrosTabla->getTablas('tpv');
	// Ahora si hay tablas tpv , obtenemos los campos unicos de esas tablas.
	$datos_tablas['tpv'] = $NewParametrosTabla->getCamposTpv();
	$datos_tablas['comprobaciones'] = $NewParametrosTabla->getComprobaciones();
	$datos_tablas['parametros_tabla'] = $NewParametrosTabla->getParametrosTabla();
	echo '<pre>';
		print_r($datos_tablas['tpv']);
		//~ // print_r($NewParametrosTabla->getConsultas());
	echo '</pre>';
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
	//~ include './../../header.php';
     include_once $URLCom.'/modulos/mod_menu/menu.php';
	include_once ("./funciones.php");



	
	
// ---------- Obtenemos de parametros/configuracion tipos de Registros -------- //
// Tipos de registros , les llamo a los registros de BDImporta según el estado que tenga 
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
		$e_tipo = $comprobaciones[$item]['resultado']['encontrado_tipo'];
		// Montamos botonera de opciones generales con JS
		$p = $datos_tablas['comprobaciones'];
		$comprobaciones[$item]['opt_generales'] = MontarHtmlOpcionesGenerales($p,$e_tipo,$item);
		if (isset($p[$e_tipo][0]->procesos->before->action)){
			$X = $p[$e_tipo][0]->procesos->before->action; // funciones
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
				// En caso de haya mas de un resgistro , entonces  tipo es Similar
				if ($comprobaciones[$item]['resultado']['encontrado_tipo'] === "Mismo" && $respuesta['tpv']['NItems'] > 1){
					// Cambiamos dato a similar y marcamos registro comprobaciones como error.
					$comprobaciones[$item]['resultado']['encontrado_tipo'] ="Similar";
					$comprobaciones[$item]['estado'] = 'Error - Cambio tipo encontrado Similar';
				}
			}
			
			// Analizamos los campos que son iguales y vemos que campos son significativos para considerar que el mismo registros.
			// Tb faltaría comprobar que si se modifico algún campo.
			// -- De momento no comprobamos si hay modificaciones. -- //
			$procesos = 'Si' ; 
			if ($comprobaciones[$item]['resultado']['encontrado_tipo']=== "Mismo"){
				// Debería:
				//  - procesos de comprobaciones = Mismo
				$procesos = 'Si' ; // Mientras no hago las diferencias.
			}
			//~ echo '<pre>';
			//~ echo $item.'<br/>';
			//~ print_r($respuesta['tpv']);
			//~ echo '</pre>';
			if ($procesos === 'Si'){
				$registros_tpv[$item]=$respuesta['tpv'];
			}
			//~ echo '<pre>';
			//~ print_r($respuesta);
			//~ echo '</pre>';
		}
		// ------------- Montamos Variables JS para cada Item ------------------------------ //
		$d = array();
		// Variables JS de importar.
		foreach ($datos_tablas['importar']['campos'] as $campo){
			if (isset($registro[$campo])){ 
				$d['importar'][] = array ( $campo =>$registro[$campo]);
				
			}
		}
		// Variables JS de tpv ( ten encuenta que puede haber varias tablas en tpv... no una sola)
		// Si la consulta 
		if (isset($registros_tpv[$item])){
			// Recorremos array de nombres de tablas de tpv
			foreach ($datos_tablas['tablas']['tpv'] as $n_tabla){
				// Recorremos array tablas con los campos unico de tpv
				foreach ($datos_tablas['tpv'] as $campos_tabla){
					// Comprobamos que el nombre tabla tpv se mismo array campos unico..  
					if (isset($campos_tabla[$n_tabla])){
						foreach ($campos_tabla[$n_tabla] as $campo){
							// Si Nitems encontrado es uno.
							if ($registros_tpv[$item]['NItems'] === 1){ 
								$d['tpv'][] = array ( $campo =>$registros_tpv[$item]['Items'][0][$campo]);
							}
						}
					}
				}
			}
		}
		
		$comprobaciones[$item]['JS'] = $d;
		
		
	}
	
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
