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
	// [ANTES CARGAR FUNCIONES JS]
	// Montamos la variables en JAVASCRIPT de nombre_tabla que lo vamos utilizar .js
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
	include_once ("./classTabla.php");
	include ("./../../controllers/Controladores.php");
	$Controler = new ControladorComun; 
	// Ahora obtenemos nombre tabla
	if ($_GET['tabla']){
		$tabla =$_GET['tabla'];
		// Comprobamos si existe tabla.
		if (!in_array($tabla,$Conexiones['1']['tablas'])){
			print 'No existe la tabla';
			// Si no existe no continuamos.
			return;
		}
	}
	
	// ---------------  Montamos array campos que obtenemos XML parametros   ------------- //
	// Cargamos XML donde tenemos parametros de las tabla
	// https://diego.com.es/tutorial-de-simplexml
	$tablas_importar = simplexml_load_file('parametros.xml');
	//~ $campos = array();
	foreach ($tablas_importar as $tabla_importar){
		echo $tabla_importar->nombre.'=='.$tabla;
		if (htmlentities((string)$tabla_importar->nombre) === $tabla){
			// Solo obtenemos los datos de tabla que estamos
			foreach ($tabla_importar->campos->children() as $campo){;
				$nombre_campo =(string) $campo['nombre'];
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
		}
	}
	echo '<pre>';
	print_r($campos);
	echo '</pre>';
	
	// Realizamos resumen registros por el estado la tabla.
	
	$tiposRegistros = array('todos'	=>array('consulta' => '',
										'texto' => 'Todos'
									),
					'sin'		=>array('consulta' => 'WHERE estado is null',
										'texto' => 'Sin tratar'
									),
					'nuevo'		=>array('consulta' => 'WHERE estado= "Nuevo"',
										'texto' => 'Nuevos'
									),
					'modificado'=>array('consulta' => ' WHERE estado="Modificado"',
										'texto' => 'Modificados'
									),
					'descartado'=>array('consulta' => ' WHERE estado="Descartado"',
										'texto' => 'Descartados'
										)
					);
	// ------------- Obtenemos registros sin tratar. ------------------ //
	$Registros_sin = array();
	foreach ( $tiposRegistros as $key => $tipo){
		$resultado = $Controler->contarRegistro($BDImportDbf,$tabla,$tipo['consulta']);
		$tiposRegistros[$key]['Num_items'] = $resultado;
		if ($key ==='sin'){
			// Obtenemos los registros que vamos tratar
			if ($tiposRegistros['sin']['Num_items'] < 500){
				// Para evitar exceso de memoria... Solo obtenemos 100 primero registros que están sin tratar
				$tipo['consulta'] .= '  LIMIT 100';
				$resultado = $Controler->consultaRegistro($BDImportDbf,$tabla,$tipo['consulta']);
				// Asocio array a 'imporar' por si mas adelante queremos ver los nuevos o modificados.
				$Registros_sin['importar'] = $resultado['Items'];
			}
			
		}
	}
	// ---  Comprobamos si los registros sin tratar existe y son iguales o similares en tpv. --- //
	$registros_tpv = array();
	$comprobaciones = array();
	foreach ($Registros_sin['importar'] as $item=>$registro){
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
		//~ print_r($comprobaciones);
	//~ echo '</pre>';
	
?>



<div class="container">
	<div class="col-md-12">
		<h2>Preparamos para actualizar tabla <?php echo $tabla;?>.</h2>
	</div>	
	<div class="col-md-12">
		<p><strong>Resumen</strong></p>
	<p>
	<?php
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
		<div class="row">
			<div class="col-md-12">
				<h3>Registro: <?php echo $item;?></h3>
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
				<h4>Accion a realizar</h4>
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
