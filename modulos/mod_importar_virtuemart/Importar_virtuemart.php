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
									),
							'5' => array(
									'nombre_tabla_temporal' => 'tmp_clientes',
									'campo_id' 	=> 'idClientes',
									'select'	=>'SELECT c.`virtuemart_user_id` as idVirtuemart,
									 CONCAT(c.`first_name`," ",c.`middle_name`," ", c.`last_name`) as Nombre,
									 c.`company` as razonsocial ,
									 c.DNICIF as nif, 
									 CONCAT( c.`address_1`," ",c.`address_2`," ",c.`city`) as direccion,
									 c.`zip` as codpostal,
									 c.`phone_1` as telefono, c.`phone_2` as movil ,
									 c.`fax` as fax ,u.`email` as email,"activo" as `estado`
									 FROM  '.$prefijoBD.'_virtuemart_userinfos AS c 
									 LEFT JOIN '.$prefijoBD.'_users AS u ON c.virtuemart_user_id=u.id'
									)
							);
	
	// Array $comprobaciones
	// @ Parametros de array $comprobaciones.
	// 		funcion						=> (String)Nombre funcion
	// 		'link_collapse'				=> (String)El html LINK COLLAPSE para poder extender y encojer
	// 		'subprocesos'				=> (Array)En una comprobación poder quere realizar varias cosas.
	// 		'explicacion_subprocesos	=> (Array)Explicación de cada subproceso que se haga - Aunque se solo uno un proceso es array()
	// 		'respuesta'					=> (Array)Para recojer el resultado de cada subproceso de la comprobación.
	$comprobaciones  = 		array(
							'0' => array(
								'nom_funcion'				=>'ComprobarTablaTempArticulosCompleta',
								'link_collapse'				=>'<a data-toggle="collapse" data-parent="#accordion" href="#ComprobarTablaTempArticulosCompleta" aria-expanded="false" class="collapsed">
								Comprobar Tabla Temporal Articulos Completa
								<span style="float:right;" class="icono-collapse">+</span>
								</a>',
								'subprocesos'				=>array('RecalculoPrecioConIva','ComprobarCodbarras'),
								'explicacion_subprocesos'	=>array('Ponemos en tabla_tmp precion con Iva ya que estaba sin el (Recalculamos)','Comprobamos que no haya ningún codbarras repetetido en tmp_temporal ya que no tiene sentido añadirlo.[DUDAMOS EN AFIRMACION]') 
								)//~ ),
							//~ '1' => array(
								//~ 'nom_funcion'		=>'CrearIdSinDeterminarClientes',
								//~ 'titulo_html'	=>'<a title="Creamos el registro de 0 de clientes como cliente sin desterminar">Crear id 0 Clientes</a>'
								//~ )
							);
	
	
	
	
	// Array $tablas_importars;
	// @ Parametros de array $tablas
	//		nombre		-> Nombre de la tabla tpv
	//		obligatorio	-> Campos que tiene contener datos obligatoriamente
	//		campos->	Los campos que obtenemos
	$tablas_importar = 		array(
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
								),
						'7' => array(
								'nombre'		=>'clientes',
								'obligatorio'	=>array(),
								'campos'		=>array('idClientes', 'Nombre', 'razonsocial', 'nif', 'direccion', 'codpostal','telefono', 'movil', 'fax', 'email', 'estado'),
								'origen' 		=>'tmp_clientes'
								)
						);

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
	?>
	
	<?php
	}
	?>
	<?php 
	// Añadimos a variable global JS tablatemporales
	foreach ($comprobaciones as $comprobacion){
		echo "comprobacionesTemporales.push(".json_encode($comprobacion).");";
	?>
	
	<?php
	}
	?>
	
	
	
	</script>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_importar_virtuemart/funciones.js"></script>
	<script type="application/javascript">
	// Ejecutamos inicio creación tablas
	BucleTablaTemporal();
	</script>
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
	foreach ( $tablas_importar as $tabla ) {
		$n_tabla = $tabla['nombre'];
		$Items_tabla[$n_tabla] = $Controler->contarRegistro($BDTpv,$n_tabla);
		$sum_Items_articulos += (int)$Items_tabla[$n_tabla] ;
	}
	
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
			<div id="barra" class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                   0 % completado
             </div>
		</div>
		</div>
		<div id="resultado" class="text-center"></div>

		<div class="col-md-12">
		<h3 class="text-center"> Control de procesos de importacion tablas</h3>
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
					foreach ( $tablas_importar as $tabla ) {
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
					<th></th>
					<th></th>
				</tr>
				<?php foreach ($comprobaciones as $comprobacion){?>
				<tr>
					<td><h5>
						<?php echo $comprobacion['link_collapse'];?>
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
					<td></td>
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
			
	<div>
	
	</div>
</div>
</body>
</html>
