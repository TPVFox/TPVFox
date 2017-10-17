<div>
<nav class="col-sm-2" id="myScrollspy">
	<a class="text-ritght" href="./">Volver Atrás</a>
</nav>
<h2>Modulo de importar de virtuemart a tpv</h2>
<p>Con este modulo que se encuentra en directorio modulos/mod_importar_virtuemart, podremos importar la información de una tienda on-line que tengamos con Joomla y virtuemart.</p>
<p> El objetivo inicial es hacer una importación, es decir traernos para tpv los datos de las siguientes tablas de virtuemart.</p>
<ul>
<li>prefijo_virtuemart_products</li>
<li>prefijo_virtuemart_products_es_es</li>
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
<p> Al principio del fichero Importar_virtuemart.php creamos varios array tanto en php como javascript que son los que utilizamos tanto para crear tablas temporales, como para crear comprobaciones y insertar en las tablas tpv. </p>
<ul>
<li>$tablasTemporales -> Donde indicamos las que creamos en virtuemart, con la consulta. </li>
<li>$comprobaciones -> El array que utilizamos para generar procesos de comprobaciones por cada tabla creada en virtuemart, para saber si esta bien los datos.</li>
<li>$tablas_importar -> Son las tabla tpv que tenemos que vamos importar los datos.</li>
</ul>
<h4>Cosas a tener en cuenta</h4>
<p>En virtuemart la tabla que registra los compradores es virtuemart_userinfos, por lo que es la que obtenemos los datos.</p>
<p>En la <b>tabla tmp_clientes</b> que acabamos creamos, hay que tener en cuenta que solo <b>obtenemos un solo registro por usuario </b>, ya que en la tabla virtuemart_userinfos puede contener varios registros por cada uno e incluso contener usuarios que no existen en tabla virtuemart_user, estos ultimos pienso que si los obtenemos en la tabla tmp_clientes, los motivos de esto:</p>
<ul>
<li>Usuarios que tienen varias direcciones les aparece varios registros </li>
<li>Los usuarios que eliminamos de usuarios de joomla,por los motivos que sean, en la tabla virtuemart_userinfos siguen existiendo.</li>
</ul>
<?php include $DirectorioActual.'./errores.php' ;?>
<h4>Diagrama de funciones</h4>
<p>Intento mostrar de alguna manera todos los errores que se pueden producir en la importacion de virtuemart.</p>
 <img src="<?php echo $UrlActual;?>Diagrama1.dia" alt="Errores"> 
</div>

</div>
