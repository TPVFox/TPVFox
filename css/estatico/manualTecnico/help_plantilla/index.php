<div>
<nav class="col-sm-2" id="myScrollspy">
	<a class="text-ritght" href="./">Volver Atrás</a>
</nav>
<h2>Plantilla para crear ayudas.</h2>
<p> La idea es tener un metodo cómodo en la que podamos crear ayudas para nosotros, en este caso es para manual técnico, pero perfectamente valdría para otras cosas como:</p>
<ul>
<li> Manual usuario</li>
<li> Documentacion de empresa, empleados y de mas.</li>
<li>etc...</li>
</ul>
<h4>¿Como añador un cuadrado de ayuda?</h4>
<p>En fichero /estatico/SECCION/index.php añadimos al array se ayudas un item más, este array es: </p>
<pre>
<code>
'1' => array(
		'titulo_cuadro'	=> 'Generar Ayudas',
		'introduccion'	=> 'Texto de introduccion queremos poner en cuadro de ayudas.',
		'ruta'			=> '/directorio/' directorio que ponemos nuevo para la meter nuestros ficheros y datos.
		'fichero'		=> 'index.php' -> fichero donde quieras que empiece.... ejecutar.
		),
</code>
</pre>
<p> Luego solo tienes que crear directorio con el contenido, que quieras mostrar.</p>
<?php include $DirectorioActual.'./rutas.php' ;?>
</div>
