<div>
<nav class="col-sm-2" id="myScrollspy">
	<a class="text-ritght" href="./">Volver Atrás</a>
</nav>
<h2>Controlar los eventos de teclado y raton.</h2>
<p> El objetivo es hacer un proceso sencillo para poder controlar eventos de tecla y raton. Una idea similar como <a hef="https://github.com/jkup/shortcut">shortcut.js</a>. </p>
<p> Nosotros creamos lib teclado.js, que tenemos en lib/js/, donde una forma sencilla, creo :-), controlamos los eventos de teclado y los eventos de raton</p>
<h4>¿Como empezar?</h4>
<p>Lo primero añadiendo es añadir los objetos globales en JS en el head</p>
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
<p>Luego añadir la libreria</p>
<pre>
<code> &lt;script src="&lt;?php echo $HostNombre; ?&gt;/lib/js/teclado.js"&gt;&lt;/script&gt; </code>
</pre>
<p>Poner en input,check,button o la caja permita eventos,<strong>el atributo data-obj="Nombre_objeto_global"</strong></p>
<p>Tambien poner en el evento queramos controlas llamamos a la funcion controlEventos(event).</p>
<p>Y ya solo queda currar lo queremos que haga.</p>
<p>En esta parte tienes que tener en cuenta que las funciones que necesita y utiliza son:</p>
<ul>
<li>function controladorAcciones(caja,accion)</li>
<li>function after_constructor(padre_caja,event)</li>
<li>function before_constructor(caja)</li>
</ul>

<h4>¿Que hace la funcion controladorAcciones ?</h4>
<p>Esta funcion puede ser un switch o simple if donde comprobamos si existe la accion que le tenemos objeto global.</p>
<p> Llegamos a la funcion cuando pulso una tecla o un evento que tengamos definido en el objeto global, realizar la accion que le indiquemos</p>


</div>
