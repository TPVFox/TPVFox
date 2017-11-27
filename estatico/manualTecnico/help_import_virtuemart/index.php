<div>
<nav class="col-sm-2" id="myScrollspy">
	<a class="text-ritght" href="./">Volver Atrás</a>
</nav>
<div class="col-md-12">
<h2>Modulo de importar de virtuemart a tpv</h2>
<p>El codigo de este modulo se encuentra en <b>directorio modulos/mod_importar_virtuemart</b>, podremos importar la información de una tienda on-line que tengamos con Joomla y virtuemart a la BDTpv que la que utilizamos en esta aplicación.</p>
<p> El objetivo inicial es hacer una importación, es decir traernos a BDTPV los datos de la BD de TiendaOnline ( Virtuemart) </p>
	<div class="col-md-4">
		<h3>Fases proceso importación</h3>
		<p>Por lo complejo que es esté proceso, se realiza por fases</p>
		<ul>
			<li><b>Configuracion:</b> El usuario selecciona como quiere realizar importacion o actualizacion:</li>
			<ul>
				<li>Seleccionar web</li>
				<li>Como generamos CREF</li>
				<li>Los precios tpv</li>
			</ul>
			<li><b>Importación total:</b> Limpia las tablas BDTPV y importa todos los datos de virtuemart.</li>
			<li><b>Actualizar:</b> No elimina los datos que tengamos en BDTPV y solo obtiene los datos nuevos de virtuemart</li>
		</ul>
		<?php include $DirectorioActual.'fasesImportar.html' ;?>
	</div>
	<div class="col-md-4">
		<h3>Tablas de virtuemart</h3>
		<p>Las tablas de las que vamos utilizar tanto importar, como actualizar</p>
		<ul>
		<li>prefijo_virtuemart_products</li>
		<li>prefijo_virtuemart_products_es_es</li>
		<li>prefijo_virtuemart_product_prices</li>
		<li>prefijo_virtuemart_calcs as</li>
		<li>prefijo_virtuemart_categories_es_es</li>
		<li>prefijo_virtuemart_category_categories</li>
		<li>prefijo_virtuemart_product_medias</li>
		<li>prefijo_virtuemart_medias</li>
		<li>prefijo_virtuemart_product_categories</li>
		<li>prefijo_virtuemart_userinfos</li>
		<li>prefijo_users</li>
		</ul>

	</div>
	<div class="col-md-4">
		<h3>Tablas temporales en BD virtuemart</h3>
		<p>Las tablas temporales, se generan cada vez que iniciemos "Importar a virtuemart". Estas tablas son las indican al principio del <b>fichero "Importar_virtuemart.php"</b> en el array $tablasTemporales.</p>
		<p>Realmente no son temporales ya que si lo fueran no se borraría al cerrar la conexión.</p>
		<ul>
		<li>tmp_articulosCompleta</li>
		<li>tmp_familias</li>
		<li>tmp_productos_img</li>
		<li>tmp_cruces_familias</li>
		<li>tmp_clientes</li>
		</ul>
		<p> Esto se puede añadir facilmente en ese array.</p>
	</div>
</div>	
<div class="col-md-12">
	<h4>Diagrama flujo local -1</h4>
	<p>En el siguiente diagrama intento explicar los que hacemos antes de enviar datos al servidor</p>
	<img src="<?php echo $UrlActual;?>Diagrama2.svg" width="90%" alt="Diagrama de flujo"> 
</div>
<div class="col-md-12">
	<p> Al principio del fichero Importar_virtuemart.php creamos varios array tanto en php como javascript que son los que utilizamos tanto para crear tablas temporales, como para crear comprobaciones y insertar en las tablas tpv. </p>
	<ul>
	<li>$tablasTemporales -> Donde indicamos las que creamos en virtuemart, con la consulta. </li>
	<li>$comprobaciones -> El array que utilizamos para generar procesos de comprobaciones por cada tabla creada en virtuemart, para saber si esta bien los datos.</li>
	<li>$tablas_importar -> Son las tabla tpv que tenemos que vamos importar los datos.</li>
	</ul>
</div>
<div class="col-md-12">
	<h4>Cosas a tener en cuenta</h4>
	<p>En virtuemart la tabla que registra los compradores es <b>virtuemart_userinfos</b>.</p>
	<p>En la <b>tabla tmp_clientes</b> que acabamos creamos, hay que tener en cuenta que solo <b>obtenemos un solo registro por usuario </b>, en la tabla virtuemart_userinfos un mismo usuarios puede tener varios registros, si tiene varias direcciones.</p>
	<p>Ademas debemos tener en cuenta que puede tener usuarios que se no existan en la tabal de joomla de usuarios, los cuales obtenemos igualmente.</p>
	<ul>
	<li>Usuarios que tienen varias direcciones les aparece varios registros </li>
	<li>Los usuarios que eliminamos de usuarios de joomla,por los motivos que sean, en la tabla virtuemart_userinfos siguen existiendo.</li>
	</ul>
</div>
<div class="col-md-12">
	<?php include $DirectorioActual.'./errores.php' ;?>
	<h4>Diagrama de funciones</h4>
	<p>Intento mostrar de alguna manera todos los errores que se pueden producir en la importacion de virtuemart.</p>
	 <img src="<?php echo $UrlActual;?>Diagrama1.dia" alt="Errores"> 
</div>

</div>
