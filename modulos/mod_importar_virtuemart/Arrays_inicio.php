<?php
// Recuerda que tienes que tener estas VARIABLES ANTES CARGAR ESTE FICHERO:
//  $prefijoBD -> prefijo de la base de datos virtuemart.
//  $idTienda -> Tienda principal ( actual).
// 	$idTienda_export -> Id de tienda virtuemart donde exportamos.


//[Array $opyicrefs ] Opciones de como generar CREF en Tpv
// @ Array indexado y asociativo
//			value= Nombre option
//			descripcion = label que se muestra usuario
// 			EtiqueTitle = Descripcion que muestra poner encima label.
//			checked  	= 'checked' -> Indicamos cual es por defecto, se cubre según el id en proceso toma datos.
$optcrefs = array (
			'0' => array(
				'value' =>'No_cref',
				'descripcion' => 'No generar',
				'EtiqueTitle' => 'No se crea CREF para nuevos articulos creados.',
				'checked'	  => ''
				),
			'1' => array(
				'value' => 'cref_id',
				'descripcion' => 'Id de virtuemart como CREF',
				'EtiqueTitle' =>'Ponemos como CREF el campo virtuemart_product_id',
				'checked'	  => ''
				),
			'2' => array(
				'value' => 'cref_SKU',
				'descripcion' => 'El SKU de virtuemart como CREF',
				'EtiqueTitle' => 'Ponemos como CREF el campo product_sku',
				'checked'	  => ''
				)
		);





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
									'select'	=>'SELECT '.$idTienda.' as idTienda,'
													.$idTienda_export.' as idTienda_export,
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
									'select'	=>'SELECT c.`virtuemart_user_id` AS idVirtuemart, 
									CONCAT( c.`first_name` , " ", c.`middle_name` , " ", 
									c.`last_name` ) AS Nombre, c.`company` AS razonsocial, 
									c.DNICIF AS nif, CONCAT( c.`address_1`," ", c.`address_2` , " ", c.`city` ) AS direccion, 
									c.`zip` AS codpostal, c.`phone_1` AS telefono, c.`phone_2` AS movil, 
									c.`fax` AS fax, u.`email` AS email, "activo" AS `estado`, 
									count( * ) AS NumDirecciones 
									FROM '.$prefijoBD.'_users AS u 
									INNER JOIN '.$prefijoBD.'_virtuemart_userinfos AS c ON u.id = c.virtuemart_user_id 
									GROUP BY c.virtuemart_user_id 
									HAVING COUNT( * ) ' 
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
								'link_collapse'				=>'Comprobar Tabla Temporal Articulos Completa',
								'subprocesos'				=>array('RecalculoPrecioConIva','CodbarrasRepetidos'),
								'explicacion_subprocesos'	=>array(
															'Ponemos en tabla_tmp precion con Iva ya que estaba sin el (Recalculamos)','Comprobamos que no haya ningún codbarras repetetido en tmp_temporal ya que vemos advertir si hay codbarras repetidos.') 
								),
							'1' => array(
								'nom_funcion'		=>'ComprobarTablaTempClientes',
								'link_collapse'	=>'Comprobar tabla de Clientes temporal',
								'subprocesos'				=>array('AnhadirIdCliente1'),
								'explicacion_subprocesos'	=>array('Añadimos cliente con id 1 que es Sin determinar') 
								)
							);
	
	
	
	
	//[ Array $tablas_importars ] Tablas de BDTvp que vamos importar o actualizar.
	// @ Array indexado y asociativo
	//		nombre		-> Nombre de la tabla tpv
	//		obligatorio	-> Campos que tiene contener datos obligatoriamente, el nombre campo destino.
	//		campos_origen->	Los campos que obtenemos
	// 		campos_destino-> Campos de la tabla destino BDTPV
	// 		origen-> 'Tabla de donde ejecutamos select para obtener datos.
	// 		NRegistros-> No se poner, pero recuerda va existir ya que esto se rellena al inicio o con javascript
	$tablas_importar = 		array(
						'0' => array(
								'nombre'		=>'articulos',
								'obligatorio'	=> array(),
								'campos_origen'		=>array('idArticulo','iva','idProveedor','articulo_name', 'beneficio','costepromedio', 'estado', 'fecha_creado', 'fecha_modificado'),
								'campos_destino'	=>array('idArticulo','iva','idProveedor','articulo_name', 'beneficio','costepromedio', 'estado', 'fecha_creado', 'fecha_modificado'),

								'origen' 		=>'tmp_articulosCompleta',
								'NumRegistros'	=> '?' // No le pongo valor ya lo obtenemos...
								),
						'1' => array(
								'nombre'		=>'articulosCodigoBarras',
								'obligatorio'	=> array('codBarras'),
								'campos_origen'		=> array('idArticulo', 'codBarras'),
								'campos_destino'	=> array('idArticulo', 'codBarras'),

								'origen' 		=>'tmp_articulosCompleta',
								'NumRegistros'	=> '?' // No le pongo valor ya lo obtenemos...
								),
						'2' => array(
								'nombre'		=>'articulosPrecios',
								'obligatorio'	=> array(),
								'campos_origen'		=> array('idArticulo','pvpCiva', 'pvpSiva', 'idTienda_export'),
								'campos_destino'	=> array('idArticulo','pvpCiva', 'pvpSiva', 'idTienda'),
								'origen' 		=>'tmp_articulosCompleta',
								'NumRegistros'	=> '?' // No le pongo valor ya lo obtenemos...
								),
						'3' => array(
								'nombre'		=>'articulosTiendas',
								'obligatorio'	=>array('crefTienda'),
								'campos_origen'		=>array('idArticulo','idTienda_export','crefTienda','estado'),
								'campos_destino'	=>array('idArticulo','idTienda','crefTienda','estado'),
								'origen' 		=>'tmp_articulosCompleta',
								'NumRegistros'	=> '?' // No le pongo valor ya lo obtenemos...
								),
						'4' => array(
								'nombre'		=>'articulosFamilias',
								'obligatorio'	=>array(),
								'campos_origen'		=>array('idArticulo','idFamilia'),
								'campos_destino'	=>array('idArticulo','idFamilia'),
								'origen' 		=>'tmp_cruce_familias',
								'NumRegistros'	=> '?' // No le pongo valor ya lo obtenemos...
								),
						'5' => array(
								'nombre'		=>'familias',
								'obligatorio'	=>array(),
								'campos_origen'		=>array('idFamilia','familiaNombre','familiaPadre'),
								'campos_destino'	=>array('idFamilia','familiaNombre','familiaPadre'),
								'origen' 		=>'tmp_familias',
								'NumRegistros'	=> '?' // No le pongo valor ya lo obtenemos...
								),
						'6' => array(
								'nombre'		=>'articulosImagenes',
								'obligatorio'	=>array('file_url'),
								'campos_origen'		=>array('idArticulo','cref','virtuemart_media_id','file_url'),
								'campos_destino'	=>array('idArticulo','cref','virtuemart_media_id','file_url'),
								'origen' 		=>'tmp_productos_img',
								'NumRegistros'	=> '?' // No le pongo valor ya lo obtenemos...
								),
						'7' => array(
								'nombre'		=>'clientes',
								'obligatorio'	=>array(),
								'campos_origen'		=>array('idClientes', 'Nombre', 'razonsocial', 'nif', 'direccion', 'codpostal','telefono', 'movil', 'fax', 'email', 'estado'),
								'campos_destino'	=>array('idClientes', 'Nombre', 'razonsocial', 'nif', 'direccion', 'codpostal','telefono', 'movil', 'fax', 'email', 'estado'),
								'origen' 		=>'tmp_clientes',
								'NumRegistros'	=> '?' // No le pongo valor ya lo obtenemos...
								)
						);
						
						

?>
