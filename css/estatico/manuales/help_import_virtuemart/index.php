<div>
<nav class="col-sm-2" id="myScrollspy">
	<a class="text-ritght" href="./">Volver Atrás</a>
</nav>
<div class="col-md-12">
<h2>Importar de virtuemart a tpv</h2>
<p>En esta opción prodremos importar o actualizar los datos que tenemos en una Web con los de tpv. Hay conocer bien la diferencia en lo que llamamos importar y lo que llamamos actualizar.</p>
<p>Diferencias entre actualizar y importar:</p>
<ul>
	<li>Importar:
		<ul>
			<li><b>Borra datos de tpv:</b>Las tablas que indicamos en el listado son eliminadas.</li>
			<li>Crear nuevo articulos empezando id ->1</li>
			<li>Comprueba si existe varias productos con el mismo codbarras, y lo registra en log, pero si los añade tpv</li>
		</ul>
	</li>
	<li> Actualizar:
		<ul>
		<li>No elimina datos de tpv</li>
		<li>Busca las diferencias que hay entre la WEB - tpv y viceversa, ya puede haber mas producto en ambast tablas.</li>
		<li>NO muestra differencias de codigo barras, aunque comprueba si existe varias productos con el mismo codbarras de Web, y lo registra en log, no hace nada mas</li>
		<li>Te indica esas diferencias y te da las opciones que quieres que haga.</li>
		</ul>
	</li>
</ul>
<h3>Pantallazos.</h3>
<h3>Diferencias encontradas.</h3>
<p>Cuando actualizamos y nos muestras las diferencias tenemos que tener claro que porque son esas diferencias.</p>
<h4>Diferencia por cambio estado</h4>
<p> Si en la web despublicamos un producto, podemos realizarlo de dos formas:</p>
<p> Desde listado de productos de virtuemart o desde dentro del propio producto, pues el resultado de diferencia es distinto:</p>
<div class="col-md-12">
	<h5>Cuando es cambiado estado desde check listado de productos.</h5>
	<div class="col-md-4">
		<h3>Web</h3>
		<pre>Array
(
    [idVirtuemart] =&gt; 2738
    [articulo_name] =&gt; Productos 1 
    [iva] =&gt; 21.00
    [estado] =&gt; NoPublicado
    [pvpCiva] =&gt; 13.899996
    [pvpSiva] =&gt; 11.487600
    [fecha_creado] =&gt; 2017-04-05 15:14:45
    [fecha_modificado] =&gt; 2017-09-20 15:03:51
)
</pre>
	</div>
	<div class="col-md-4">
		<h3>tpv</h3>
		<pre>Array
(
    [idArticulo] =&gt; 1694
    [idVirtuemart] =&gt; 2738
    [articulo_name] =&gt; Productos 1
    [iva] =&gt; 21.00
    [estado] =&gt; Activo
    [pvpCiva] =&gt; 13.899996
    [pvpSiva] =&gt; 11.487600
    [fecha_creado] =&gt; 2017-04-05 15:14:45
    [fecha_modificado] =&gt; 2017-09-20 15:03:51
)
</pre>
	</div>
	<div class="col-md-4">
		<h3>Differencia</h3>
	<pre>Array
(
    [estado] =&gt; Activo
)
</pre>
	</div>
</div>
<div class="col-md-12">
	<h5>Cuando es cambiado el estado desde dentro producto.</h5>

	<div class="col-md-4"><h3>Web</h3><pre>Array
(
    [idVirtuemart] =&gt; 1813
    [articulo_name] =&gt; Productos 2
    [iva] =&gt; 21.00
    [estado] =&gt; NoPublicado
    [pvpCiva] =&gt; 12.500002
    [pvpSiva] =&gt; 10.330580
    [fecha_creado] =&gt; 2016-10-27 09:00:13
    [fecha_modificado] =&gt; 2017-11-23 16:40:53
)
</pre></div><div class="col-md-4"><h3>tpv</h3><pre>Array
(
    [idArticulo] =&gt; 856
    [idVirtuemart] =&gt; 1813
    [articulo_name] =&gt; Productos 2
    [iva] =&gt; 21.00
    [estado] =&gt; Activo
    [pvpCiva] =&gt; 12.500002
    [pvpSiva] =&gt; 10.330580
    [fecha_creado] =&gt; 2016-10-27 09:00:13
    [fecha_modificado] =&gt; 2017-11-17 09:40:53
)
</pre></div><div class="col-md-4"><h3>Differencia</h3><pre>Array
(
    [estado] =&gt; Activo
    [fecha_modificado] =&gt; 2017-11-17 09:40:53
)
</pre></div></div>



</div>
