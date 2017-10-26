<?php
// Recuerda que tienes que tener la variable $prefijoBD ya definida.

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
	
	
	/*
	
	SELECT c.`virtuemart_user_id` as idVirtuemart,
									 CONCAT(c.`first_name`," ",c.`middle_name`," ", c.`last_name`) as Nombre,
									 c.`company` as razonsocial ,
									 c.DNICIF as nif, 
									 CONCAT( c.`address_1`," ",c.`address_2`," ",c.`city`) as direccion,
									 c.`zip` as codpostal,
									 c.`phone_1` as telefono, c.`phone_2` as movil ,
									 c.`fax` as fax ,u.`email` as email,"activo" as `estado`
									  FROM  '.$prefijoBD.'_users AS u 
									 INNER JOIN '.$prefijoBD.'_virtuemart_userinfos AS c ON u.id=c.virtuemart_user_id
	
	
	*/
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
	
	
	
	
	// Array $tablas_importars;
	// @ Parametros de array $tablas
	//		nombre		-> Nombre de la tabla tpv
	//		obligatorio	-> Campos que tiene contener datos obligatoriamente
	//		campos->	Los campos que obtenemos
	// 		NRegistros-> No se poner, pero recuerda va existir ya que esto se rellena al inicio o con javascript
	$tablas_importar = 		array(
						'0' => array(
								'nombre'		=>'articulos',
								'obligatorio'	=> array(),
								'campos'		=>array('idArticulo','iva','idProveedor','articulo_name', 'beneficio','costepromedio', 'estado', 'fecha_creado', 'fecha_modificado'),
								'origen' 		=>'tmp_articulosCompleta',
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
								'obligatorio'	=>array('file_url'),
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
?>
