<div>
<nav class="col-sm-2" id="myScrollspy">
<a class="text-ritght" href="./">Volver Atrás</a>
</nav>

<h2>Cierres</h2>
<h3>Listado de cierres:</h3>
	<ul>
		<li style="color:red"> Paginacion, buscar por usuario y numero de encontrados. Pendiente!</li>
	</ul>
<div class="col-md-6">
<h3><strong>Cierre Caja</strong> menu lateral:</h3>
	
<li>Fechas:</li>
	<ul>
		<li><strong>Fecha Cierre:</strong> Fecha que guardaremos en Cierres.  </li>
		<li><strong>Fecha Inicial:</strong> Fecha Inicial del primer ticket cobrado sin cerrar.<strong>No se modifica esta fecha.</strong> </li>
		<li><strong>Fecha Final:</strong> Fecha del ultimo ticket cobrado sin cerrar.</li>
		<p>Comprobaciones: </p>
		<li><strong>Comparamos fecha inicial y fecha final:</strong></li>
		<ul>
			<li> Si son dias iguales: Hacemos cierre caja de 1 solo dia. Se desactiva input de Fecha Final.</li>
			<li>Si son distintas fechas:<strong> Avisamos </strong> que hay dias sin hacer cierre de caja.</li>
		</ul>
		<li><strong>Aviso tickets abiertos:</strong>Debemos cerrarlos para hacer cierre. Boton Aceptar estará deshabilitado.</li>
		<p>Si se hace de la mañana es posible que salgan 2 usuarios, y si el que hace el cierre no tiene tickets abiertos,
		<strong> pero el otro usuario dejo 1 ticket abierto? </strong></br>El usuario NO PODRA hacer cierre de caja. </br>
		 Y no puede acceder al ticket de su compañero para poder hacer cierre de caja. </br><strong>Tendra que avisar al administrador para hacer cierre caja.</strong>  </p>
	</ul>
<li>Mostramos usuarios y el numero de tickets hechos en esas fechas.</li>
<li>Mostraremos desglose de la forma de pago, por usuarios.</li>
<li>La suma de los usuarios por formas de pago. ej. tarjeta y contado, tanto.</li>
<li>Desglose de IVAS y Bases de todos los tickets de ese intervalo de fechas.</li>

</div>
<div class="col-md-6">
	<h3>Al grabar cierre:</h3>
	<ul>
		<li>Tenemos 3 tablas: <strong>cierres, cierres_ivas y cierres_usuarios_tiendas.</strong></li>
		<li>Para Aceptar / Guardar cierre:</li>
		<ul>
			<li><strong>No </strong>puede haber <strong>tickets abiertos.</strong></li>
			<li>Se envian los datos por javascript en la propia vista de cierre caja.</li>
		</ul>
	</ul>
	
	
</div>
</div>
