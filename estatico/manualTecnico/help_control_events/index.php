<div>
<nav class="col-sm-2" id="myScrollspy">
	<a class="text-ritght" href="./">Volver Atrás</a>
</nav>
<h2>Controlar los eventos de teclado y raton.</h2>
<p> El objetivo es hacer un proceso sencillo para poder controlar eventos de tecla y raton. Una idea similar como <a hef="https://github.com/jkup/shortcut">shortcut.js</a>. </p>

<h4>¿Como controlar un input ?</h4>
<p> Lo que hacemos inicialmente es crear el objeto del input</p>
<pre>
<code>
var idInput = {
	id_input : 'idInput', // Este se añade ante construir ya que el id input es Unidad_Fila_1
	acciones : {
		 13 : 'accion_realizar_pulsar_intro', // Pulso intro
		 40 : 'accion_realizar_pulsar_abajo', // Pulso abajo. 
		 38 : 'accion_realizar_pulsar_arriba', // Pulso arriba pero va para abajo.
			},
	parametros : {
		dedonde : 'nombre_pantalla'
		// Los parametros que podemos necesitar
		}
}

</code>
</pre>
<p>Luego lo que hacemos es en construtor que esta teclado.js crear le obj. </p>
<p> Luego solo tienes que crear directorio con el contenido, que quieras mostrar.</p>
<?php include $DirectorioActual.'./rutas.php' ;?>
</div>
