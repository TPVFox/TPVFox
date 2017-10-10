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
	// Creamos variables de los ficheros para poder automatizar el añadir articulos y otros
	
	// Array $tablasTemporales;
	// @ Parametros de array
	//		nombre_tabla_temporal	-> Nombre de la tabla temporal de Virtuemart
	//		campo_id				-> Nombre del campo autonumerico que creamos automaticamentes.
	//		select					-> Consulta que realizamos para crear tabal temporal
	$tablasTemporales = array(
						'1' => array(
									'nombre_tabla_temporal' =>'tmp_articulosCompleta',
									'campo_id' 	=>'idArticulo',
									'select'	=>'SELECT 1 as idTienda,
													CAST( c.virtuemart_product_id as CHAR(18))as crefTienda,
													cr.product_name as articulo_name,
													coalesce((
														select calc_value from '.$prefijoBD.'_virtuemart_calcs as e 
														WHERE e.virtuemart_calc_id = d.product_tax_id),0) as iva,
													c.product_gtin as codbarras,
													0 as beneficio,
													0 as costepromedio,
													case c.published
														when 0 then "NoPublicado"
														when 1 then "Activo"
														end as estado,
													d.product_price as pvpCiva,
													d.product_price as pvpSiva,
													0 as idProveedor,
													c.created_on as fecha_creado,
													c.modified_on as fecha_modificado
													from '.$prefijoBD.'_virtuemart_products as c
													left join '.$prefijoBD.'_virtuemart_products_es_es as cr
														on c.virtuemart_product_id = cr.virtuemart_product_id
													left join '.$prefijoBD.'_virtuemart_product_prices as d
														on c.virtuemart_product_id = d.virtuemart_product_id'
									),
							'2' => array(
									'nombre_tabla_temporal' => 'tmp_familias',
									'campo_id' 	=> 'idFamilia',
									'select' 	=> 'SELECT c.`virtuemart_category_id` as ref_familia_tienda ,
										 c.`category_name` as familiaNombre ,
										 cr.`category_parent_id` as familiaPadre FROM `'.$prefijoBD.'_virtuemart_categories_es_es` AS c LEFT JOIN `'.$prefijoBD.'_virtuemart_category_categories` AS cr ON c.`virtuemart_category_id` = cr.`category_child_id` '
									),
							'3' => array(
									'nombre_tabla_temporal' => 'tmp_productos_img',
									'campo_id'	=> 'id',
									'select'	=>'SELECT completa.idArticulo AS idArticulo,
										 CAST( pro_img.`virtuemart_product_id` AS CHAR( 18 ) ) AS cref,
										 pro_img.`virtuemart_media_id` , pro_img.`ordering` ,
										 img.file_url
										 FROM `'.$prefijoBD.'_virtuemart_product_medias` AS pro_img
										 LEFT JOIN '.$prefijoBD.'_virtuemart_medias AS img 
										 ON pro_img.virtuemart_media_id = img.virtuemart_media_id
										 LEFT JOIN tmp_articulosCompleta AS completa 
										 ON pro_img.`virtuemart_product_id` = completa.crefTienda'
									),
							'4' => array(
									'nombre_tabla_temporal' => 'tmp_cruce_familias',
									'campo_id' 	=> 'id',
									'select'	=>'SELECT completa.idArticulo AS idArticulo, `virtuemart_category_id` AS idFamilia
											FROM '.$prefijoBD.'_virtuemart_product_categories AS cr 
											LEFT JOIN tmp_articulosCompleta AS completa ON cr.`virtuemart_product_id` = completa.crefTienda'
									)
							);
	
	
	// Array $tablas_articulos;
	// @ Parametros de array $tablas
	//		nombre		-> Nombre de la tabla tpv
	//		obligatorio	-> Campos que tiene contener datos obligatoriamente
	//		campos->	Los campos que obtenemos
	$tablas_articulos = 		array(
						'0' => array(
								'nombre'		=>'articulos',
								'obligatorio'	=> array(),
								'campos'		=>array('idArticulo','iva','idProveedor','articulo_name', 'beneficio','costepromedio', 'estado', 'fecha_creado', 'fecha_modificado'),
								'origen' 		=>'tmp_articulosCompleta'
								),
						'1' => array(
								'nombre'		=>'articulosCodigoBarras',
								'obligatorio'	=> array('codBarras'),
								'campos'		=> array('idArticulo', 'codBarras'),
								'origen' 		=>'tmp_articulosCompleta'
								),
						'2' => array(
								'nombre'		=>'articulosPrecios',
								'obligatorio'	=> array(),
								'campos'		=> array('idArticulo','pvpCiva', 'pvpSiva', 'idTienda'),
								'origen' 		=>'tmp_articulosCompleta'
								),
						'3' => array(
								'nombre'		=>'articulosTiendas',
								'obligatorio'	=>array('crefTienda'),
								'campos'		=>array('idArticulo','idTienda','crefTienda'),
								'origen' 		=>'tmp_articulosCompleta'
								),
						'4' => array(
								'nombre'		=>'articulosFamilias',
								'obligatorio'	=>array(),
								'campos'		=>array('idArticulo','idFamilia'),
								'origen' 		=>'tmp_cruce_familias'
								),
						'5' => array(
								'nombre'		=>'familias',
								'obligatorio'	=>array(),
								'campos'		=>array('idFamilia','familiaNombre','familiaPadre'),
								'origen' 		=>'tmp_familias'
								),
						'6' => array(
								'nombre'		=>'articulosImagenes',
								'obligatorio'	=>array(),
								'campos'		=>array('idArticulo','cref','virtuemart_media_id','file_url'),
								'origen' 		=>'tmp_productos_img'
								)
						);

	// [ANTES CARGAR FUNCIONES JS]
	// Montamos la variables en JAVASCRIPT de nombre_tabla que lo vamos utilizar .js
	?>
	<script type="application/javascript">
	var nombretabla = [];
	// Objeto tabla
	var tablaImpor = [];

	<?php
	foreach ($tablas_articulos as $tabla){
		// Llenamos array javascript con los nombres ficheros
		echo "nombretabla.push('".$tabla['nombre']."');";
		echo "tablaImpor.push(".json_encode($tabla).");";
	}
	?>
	</script>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_importar_virtuemart/funciones.js"></script>
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
	// Cargamos el controlador.
	// Contamos cuantos si tienen registros las tabla BDTPV
	$Controler = new ControladorComun; 
	$Items_tabla = array();
	$sum_Items_articulos = 0;
	foreach ( $tablas_articulos as $tabla ) {
		$n_tabla = $tabla['nombre'];
		$Items_tabla[$n_tabla] = $Controler->contarRegistro($BDTpv,$n_tabla);
		$sum_Items_articulos += (int)$Items_tabla[$n_tabla] ;
	}
	// Ahora creamos la tablas temporales 
	$temporalesArticulos = array();
	foreach ($tablasTemporales as $tablaTemporal){
		$temporalesArticulos[] = prepararTablaTempArticulosComp($BDVirtuemart,$tablaTemporal);
	}
	
	
	// Ahora comprobamos tmp_articulosCompleta
	$comprobarArticulosCompleta = ComprobarTablaTempArticulosCompleta ($BDVirtuemart);

	
	if (isset($comprobarArticulosCompleta['error'])) {
		$arrayErrores = $comprobarArticulosCompleta['error'];
		// Quiere decir que hubo un error al crear la tabla temporal.
		echo '<pre>';
		if (count($arrayErrores['ComprobarCodbarras']['Codbarras_repetidos']) >0){
			// Hay codbarras repetirdos con distintos articulos.
			// Buscamos id y nombre 
			$where = array();
			foreach ($arrayErrores['ComprobarCodbarras']['Codbarras_repetidos'] as $codbarrasRepetido){
				$where[] = 'codbarras="'.$codbarrasRepetido.'"';
			}
			$stringwhere = implode(' OR ',$where);
			$sql = "SELECT `idArticulo`,`crefTienda`,`articulo_name`,`codbarras` FROM `tmp_articulosCompleta` WHERE ".$stringwhere;
			if ($registros = $BDVirtuemart->query($sql)) {
				echo ' Registros que tiene duplicados los codbarras:<br/>';
				while ($fila = $registros->fetch_assoc()) {
					 printf ("%s	%s (%s)\n",$fila['codbarras'],$fila['crefTienda'],$fila['articulo_name']);
					echo '<br/>';
				}
			}else {
				print_r( 'Error en consulta:'.$sql.'<br/>');
				echo $BDVirtuemart->error;
			}
		
		}
		
		print_r($temporalesArticulos);
		echo '</pre>';
		exit(); // No continuamos
	}
	// Para DEBUG
	// Ahora añadimos datos a la tabla tempora creada en BDtpv
	//~ $InsertTablas= prepararInsertArticulosTpv($BDVirtuemart,$BDTpv,$prefijoBD,$tablas_articulos);
	//~ echo '<pre>';
		//~ foreach ($InsertTablas['tabla'] as $key =>$inserttabla){
		//~ echo $key.'<br/>';
		//~ print_r($inserttabla);
		//~ echo '<br/>';
		//~ }
	//~ echo '</pre>';
	
	
?>

<div class="container">
	<div class="col-md-5">
		<h2>Importación de datos de Virtuemart a TPV.</h2>
		<?php 
		if ( $sum_Items_articulos > 0){
			// Quiere decir que no puede ser una iniciacion.
			echo '<div class="alert alert-danger">Hay datos BDTpv , ten cuidado por se pueden eliminar el contenido (vaciar) los datos tpv.<br/>
				 Si quieres actualizar no pulses en borra.
				 </div>';
		} else {
			echo '<div class="alert alert-info"> No tienes datos en BDTpv puedes importar</div>';
		}
		?>
		<h3>Las FAXES para la importación de Virtuemart a BDTPV:</h3>
		<?php include_once 'faxesImportar.html';?>
		<script>
			// Cambien id por clase, pero no funciona correctamente
			// ya que pone - a todos cuando uno esta desplegado.
			$(document).ready(function(){
			  $(".collapse.pepe").on("hide.bs.collapse", function(){
				$(".icono-collapse").html('+');
			  });
			  $(".collapse.pepe").on("show.bs.collapse", function(){
				$(".icono-collapse").html('-');
			  });
			});
		</script>
		
	</div>
		
	<div class="col-md-7">
		<div>
		<div class="text-center" id="idCabeceraBarra"></div>

	    <div class="progress" style="margin:0 100px">
			<div id="bar" class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                   0 % completado
             </div>
		</div>
		</div>
		<div id="resultado"></div>

		<div class="col-md-12">
		<h3 class="text-center"> Control de procesos de importacion productos</h3>
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
					<th><!-- Borrada -->
						 <?php // Si no tiene articulos en tpv no ponemos link.
						 if ($sum_Items_articulos >0){ ?>
						<a  href="#VaciarTablas" title="Vaciar tablas TPV" onclick="ControlPulsado('vaciar_tablas');">
						<?php } ?>
							<span class="glyphicon glyphicon-trash"></span>
						<?php
						if ($sum_Items_articulos >0){ ?>
						</a>
						<?php } ?>
					</th>
					<th id="PrepararInsert"><!-- Creada -->
						<a href="#PrepararInsert" title="Preparar los insert, (N/n) (N)Inserts y (n)descartados en grupos 1000" onclick="ControlPulsado('preparar_insert');">
							<span class="glyphicon glyphicon-log-in"></span>
						</a>
					</th>
				  </tr>
				</thead>
				<tbody>
					<?php 
					foreach ( $tablas_articulos as $tabla ) {
						$n_tabla = $tabla['nombre'];
						echo '<tr id="'.$n_tabla.'">';
						echo '<th>'.$n_tabla.'</th>';
						echo '<td>'.$Items_tabla[$n_tabla] .'</td>';
						echo '<td>'.'</td>';
						echo '<td class="inserts">'.'</td>';
						echo '</tr>';
					}
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
				<tr>
					<th>TABLAS TEMPORALES</th>
					<th >
						NºReg
					</th>
					<th>
					</th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ($temporalesArticulos as $temporal){
						foreach ($temporal as $key => $tablat){
				?>
				<tr>
					<th><?php echo $key;?></th>
					<td><?php echo $tablat['Num_articulos'];?></td>
					<td><span class="glyphicon glyphicon-ok"></span></td>
				</tr>	
				<?php	
					}
				}
				?>
				</tbody>
			</table>
			</div>
					
		</div>		
		<div class="col-md-12">
		<h3 class="text-center"> Control de procesos de importacion Clientes</h3>
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
					<th><!-- Borrada -->
						 <?php // Si no tiene articulos en tpv no ponemos link.
						 if ($sum_Items >0){ ?>
						<a  href="#VaciarTablas" title="Vaciar tablas TPV">
						<?php } ?>
							<span class="glyphicon glyphicon-trash"></span>
						<?php
						if ($sum_Items >0){ ?>
						</a>
						<?php } ?>
					</th>
					<th id="PrepararInsert"><!-- Creada -->
						<a href="#PrepararInsert" title="Preparar los insert, (N/n) (N)Inserts y (n)descartados en grupos 1000" onclick="ControlPulsado('preparar_insert');">
							<span class="glyphicon glyphicon-log-in"></span>
						</a>
					</th>
				  </tr>
				</thead>
				<tbody>
				
				</tbody>
			</table>
			</div>
	
	</div>	
	<div>
	
	</div>
</div>
</body>
</html>
