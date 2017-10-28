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
	// Controlamos ( Controllers ... fuera de su sitio ... :-)
	if (isset($Usuario['estado'])){
		if ($Usuario === "Incorrecto"){
			return;	
		}
	}
	// [DEFINICION Y OBTENCION DE VARIABLES]
	//[Obtenemos variables, recuerda los array de inicio necesitar prefijoBD 
	// Los arrays que obtenemos son : ($tablasTemporales,$comprobaciones ,$tablas_importar)

	include_once ('./Arrays_inicio.php');

	//[Array de opciones de como generar CREF en Tpv]
	$optcrefs = array (
				'0' => array(
					'value' =>'default',
					'descripcion' => 'No generar',
					'EtiqueTitle' => 'No se crea CREF para nuevos articulos creados.',
					),
				'1' => array(
					'value' => 'cref_id',
					'descripcion' => 'Id de virtuemart como CREF',
					'EtiqueTitle' =>'Ponemos como CREF el campo virtuemart_product_id',
					),
				'2' => array(
					'value' => 'cref_SKU',
					'descripcion' => 'El SKU de virtuemart como CREF',
					'EtiqueTitle' => 'Ponemos como CREF el campo product_sku',
					)
			);
	//[COMPROBAMOS GET Y POST]
	if (isset($_GET['configuracion'])){
		// Quiere decir que obtenemos datos de $POST montar 
		foreach ($optcrefs as $key => $optcref){
			if ($_POST['optcref'] === $optcref['value']){
				$optcrefs[$key]['checked'] = 'checked';
			} else {
				$optcrefs[$key]['checked'] = '';
			}
		}
	} else {
		// Quiere decir que no pulso en configuracion por lo que pongo valor por defecto.
		$optcrefs['0']['checked'] = 'checked';

	}
	echo '<pre>';
	print_r($_POST);
	echo '</pre>';
	echo '<pre>';
	print_r($_GET);
	echo '</pre>';
	
	// [ANTES CARGAR FUNCIONES JS]
	// Montamos la variables en JAVASCRIPT de nombre_tabla que lo vamos utilizar .js
	?>
	<script type="application/javascript">
	var nombretabla = [];
	// Objeto tabla
	var tablaImpor = [];
	// Objeto tablaTemporales

	var tablasTemporales = [];
	
	var tablatemporal_actual; // GLOBAL: Variable que utilizamos para indicar en la que estamos haciendo bucle
	
	var comprobacionesTemporales = [] ; // Array GLOBAL para ejecturar funciones comprobaciones.
	
	var comprobacion_actual; // GLOBAL: Variable que utilizamos para indicar en la que estamos haciendo bucle
	<?php
	// Añadimmos a variable global JS nombretabla 
	// [PENDIENTE] -> Realmente esta variable ya no hacer falta JS 
	// porque ya la podemos obtener tablasTemporales. 
	foreach ($tablas_importar as $tabla){
		echo "nombretabla.push('".$tabla['nombre']."');";
	?>
	
	<?php
		echo "tablaImpor.push(".json_encode($tabla).");";
	}
	?>
	<?php
	// Añadimos a variable global JS tablatemporales
	foreach ($tablasTemporales as $tablaTemporal){
		echo "tablasTemporales.push(".json_encode($tablaTemporal).");";
	
	}
	?>
	<?php 
	// Añadimos a variable global JS tablatemporales
	foreach ($comprobaciones as $comprobacion){
		echo "comprobacionesTemporales.push(".json_encode($comprobacion).");";
	
	}
	?>
	
	
	
	</script>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_importar_virtuemart/funciones.js"></script>
	
	

</head>
<body>
<?php 
	include './../../header.php';
	include_once ("./funciones.php");
	include ("./../../controllers/Controladores.php");
	
	
	
	
	?>
	<?php
	// Cargamos el controlador.
	// Contamos cuantos si tienen registros las tabla BDTPV
	$Controler = new ControladorComun; 
	// Obtenemos los registros de las tablas
	$tablas_importar= ObtenerNumRegistrosVariasTablas($Controler,$BDTpv,$tablas_importar);
	// Sumamos los registros de tolas tablas.	
	$sum_Items_articulos = SumarNumRegistrrosVariasTablas($tablas_importar);
	
?>

<div class="container">
			<h2 class="text-center">Importación o Actualizacion de datos de Virtuemart a TPV.</h2>

	<div class="col-md-5">
		<h4>Parametros a configurar</h4>
			<form action="Importar_virtuemart.php?configuracion=SI" method="POST">
			<div class="form-group">
			<label title="Podemos seleccionar si creamos CREF y con que campo de Virtuemart">En empresa actual : ¿Como generamos campo CREF?"</label>
				<?php
				foreach ($optcrefs as $optcref){
					echo '<label class="radio-inline">';
					echo '	<input type="radio" name="optcref" title="'.$optcref['EtiqueTitle'].'" value ="'.$optcref['value'].'" '.$optcref['checked'].'>'.$optcref['descripcion'].'</label>';
				}
				?>
			</div>
			<div class="form-group">
			<label title="El cruce con la tienda on-line es con virtuemart_id y en tabla tpv articulosTienda">Selecciona la tienda On Line con la quieres importar/actualizar datos:</label>
			</div>

			<button type="submit" class="btn btn-default">Submit</button>

			</form>
	
		
		<?php 
		if ( $sum_Items_articulos > 0){
			// Quiere decir que tiene datos BDTpv por lo que puede ser no puede ser una iniciacion.
			echo '<div class="alert alert-warning"><strong>¡¡ Actua con producencia !!</strong>
			<p>Ya que hay datos en BDTpv , puede iniciar la importacion, eliminando todo, o solo la actualización donde solo añade los datos nuevos.</p></div>';
			echo '<div class ="col-md-12">
				<h3>Importar datos de Virtuemart</h3>
				<p>Eliminamos los datos de tpv y importamos datos de virtuemart</p>
				  
				<a  href="#VaciarTablas" title="Vaciar tablas TPV" onclick="VaciarTablas();" class="btn btn-danger">
				Borrar de Tpv y Importar datos de Virtuemart
				</a>
  
				<div class="alert alert-danger">
					Al pulsar en "Importar todo", elimina las tablas indicadad de BDTPV.
				</div>
				</div>';
			// La opcion de actualizar solo mostramos cuando hay datos.
		} else {
			echo '<div class="alert alert-info"> Vas importar los datos de Virtuemart</div>';
			echo '<div>
				   <a  href="#ImportarVirtuemart" title="Importar tablas de Virtuemart" onclick="BucleTablaTemporal();" class="btn btn-primary">
				  Importar datos de Virtuemart
				  </a>
				</div>';
		}
		
		?>
	</div>
		
	<div class="col-md-7">
		<div class="col-md-12">
		<h3 class="text-center"> Control de procesos de importacion tablas</h3>
		<div>
		<div class="text-center" id="idCabeceraBarra"></div>

	    <div class="progress" style="margin:0 100px">
			<div id="barra" class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                   0 % completado
             </div>
		</div>
		</div>
		<div id="resultado" class="text-center"></div>
			<div class="col-md-7">
			<table class="table table-bordered">
				<thead>
				  <tr>
					<th colspan="4" class="text-center">
						Base datos TPV
					</th>
				  </tr>
				  <tr>
					<td></td>
					<th>NºReg
					</th>
					<th id="BDTpv_trash"><!-- Borrada -->
						<span class="glyphicon glyphicon-trash"></span>
					</th>
					<th id="PrepararInsert"><!-- Creada -->
						<span title="Preparar los insert, (N/n) (N)Inserts y (n)descartados en grupos 1000" class="glyphicon glyphicon-log-in"></span>
					</th>
				  </tr>
				</thead>
				<tbody>
					<?php 
					// Ahora obtenemos html de tr datos tablas tpv
					echo htmlBDTpvTR($tablas_importar)
					
					?>
				</tbody>
			</table>
			</div>
			<div class="col-md-5">
			<table class="table table-bordered">
				<thead>
				<tr>
					<th colspan="3" class="text-center">
						Base datos virtuemart
					</th>
				</tr>
				<tr class="info">
					<th>CREACION TABLAS</th>
					<th >
						NºReg
					</th>
					<th>
						<span class="glyphicon glyphicon-floppy-open"></span>
					</th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ($tablasTemporales as $temporal){
				?>
				<tr id="<?php echo $temporal['nombre_tabla_temporal'];?>">
					<th><?php echo $temporal['nombre_tabla_temporal'];?></th>
					<td class="num_registros"></td>
					<td class="check"></td>
				</tr>	
				<?php	
				}
				?>
				<tr class="info">
					<th>COMPROBACIONES</th>
					<th><span title="Subprocesos dentro comprobacion">Sub(*)</span> / Errores</th>
					<th></th>
				</tr>
				<?php foreach ($comprobaciones as $key =>$comprobacion){?>
				<tr id="comprobacion_<?php echo $key;?>">
					<td><h5>
						<a data-toggle="collapse" data-parent="#accordion" href="#<?php echo $comprobacion['nom_funcion'];?>" aria-expanded="false" class="collapsed">
						<?php echo $comprobacion['link_collapse'];?>
						<span style="float:right;" class="icono-collapse">+</span>
						</a>
						</h5>
						<div id="<?php echo $comprobacion['nom_funcion'];?>" class="pepe collapse" style="height: 0px;" aria-expanded="false">
							
							<?php
							$i = 1;
							foreach($comprobacion['explicacion_subprocesos'] as $explicacion){
								echo '<p><b>'.$i.'.-</b>'.$explicacion.'</p>';
								$i++;
							}
							?>
						</div>
					</td>
					<td class="errores"></td>
					<td></td>
				</tr>
				<?php	
				}
				?>
				<tr>
				</tr>
				</tbody>
			</table>
			</div>
					
		</div>		
	
</div>
		
	<div>
<?php
//~ $respuesta = prepararInsertArticulosTpv($BDVirtuemart,$BDTpv,$prefijoBD,$tablas_importar);
//~ echo '<pre>';
	//~ $respuesta = json_encode($respuesta);
	//~ print_r($respuesta);
//~ echo '</pre>';
//~ echo '<pre>';
	//~ print_r(json_decode($respuesta));
//~ echo '</pre>';
?>



	
	</div>
</body>
</html>
