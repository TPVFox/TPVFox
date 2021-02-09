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
	
	<h3>Parametros que envio para guardar cierre:</h3>
	<li>[tienda] => 1	</li>
    <li>[sumasIvas] => </li>
    <li>[totalFpago] => 0.00	</li>
    <li>[sumaFpago] => </li>
    <li>[idUsuarioLogin] => 1 </li>
    <li>[fechaInicio_tickets] => 21-10-2017 20:52:12 </li>
    <li>[FinicioSINhora] => 21-10-2017 *Necesario para <strong>actualizar estado</strong> de tickets</li>
    <li>[FfinalSINhora] => 26-10-2017 *Necesario para <strong>actualizar estado</strong> de tickets</li>
    <li>[fechaCierre] => 26-10-2017 </li>
    <li>[fechaFinal_tickets] => 26-10-2017 23:59:59  // si vamos al dia con los cierres la hora será real. </li> 
    <li>[fechaCreacion] => 2017-10-26 21:22:34 </li></br>
	
</div>

</div>
<div class="col-md-8">
	<h3>Trabajar con <strong>Fechas</strong> tanto en <strong>php</strong> como en <strong>mysql</strong></h3>
	<li>PHP:</li>
	<ul>
		<li><strong>Dar formato a una fecha:</strong> date('d-m-Y H:m:s', strtotime('2017-10-10'))</li>
		<li><strong>Formato fechas en php:</strong></li>
		<ul>
			<li>$fecha_dmYHora = '%d-%m-%Y %H:%M:%S'; (string)</li>
			<li>Para mostrar listado, dar formato diferente a una fecha de bbdd : 'd-m-Y H:m:s'; (string)</li>
		</ul>
		<li><strong>strftime(string,int)</strong> Formatea fecha+hora local codificada 1970. 
			<ul>
				<li><i>strftime(miformato,fecha_codificada1970),</i> entonces te mostrara la fecha como tu quieres.</li>
				<li><i>strftime(miformato,fecha_normal),</i> te mostrara 01-01-1970.</li>
			</ul>
		<li><strong>strtotime(string,int): </strong> Convierte una descripción de fecha/hora textual en Inglés a una fecha Unix, codificada 1970.</li>
		<li><strong>date(string,string):</strong>Dar formato a la fecha/hora local codificada.</li>
		<li><strong>date_parse(string): </strong> Crea array sobre la fecha, date_parse(strftime($dmY,$fechaCodificada)), para atacar a ['hour']['day']</li>
		<li><strong>gettype(variable): </strong> saber el tipo de una variable.</li>
	</ul>
	<li>Mysql:</li>
	<ul>
		<li><strong>Insertar fecha en tabla:</strong> sql tiene formato: Y-d-m y nosotros al reves m-d-Y : 
			</br> <i> STR_TO_DATE("'.$Fecha.'","'.$formato_dmYHora.'")</i> ;</li>
		<li><strong>Update, dar formato a una fecha: </strong> formateas a tu gusto para que coincida con la tabla. 
			</br><i>DATE_FORMAT(`Fecha`,"%d-%m-%Y")</i> </li>
		<li><strong>Formato fecha hora sql: </strong>$fecha_dmYHora = '%d-%m-%Y %H:%i:%s';</li>
		<li></li>
	</ul>
</div>
</div>
