<div>
<nav class="col-sm-2" id="myScrollspy">
<a class="text-ritght" href="./">Volver Atrás</a>
</nav>

<h2>Testeo Tickets:</h2>
<p>Comprobar que funcione correctamente:</p>
<h3>Modulo de Tpv Tickets:</h3>
	<ul>
		<li>Focus por defecto en Codigo barras</li>
		<li>En input codigo de barras al pulsar ENTER, moverse por las cajas REF, DESCRIPCION, CODIGO BARRAS, siempre que este vacio. </li> 
		<li>En inputs principales de buscar. Con datos pulsar ENTER: se vera ventana modal de productos filtrados por lo escrito.</li>
		
	</ul>
<h3>NUEVO TICKET : testeo</h3>
	<ul>
		<li>Focus codigo de barras.</li>
		<li><strong>Estado: Nuevo</strong></li>
		<li>Agregamos producto en codigo de barras:</li>
			<ul>
				<li>Cambia <strong>Estado: Actual.</strong></li>
				<li>Añadimos en <strong>URL: ?tActual=numTicket.</strong></li>
				<li>Focus tiene que volver a codigo de barras</li>
				<li>Datos del producto añadido, las bases, ivas y total.</li>
				<li>Si cajas input estan vacias moverse entre ellas,pulsando enter. O flecha hacia abajo, para<strong> modificar cantidad.</strong></li>			
				<li>Si cambiamos cantidad:<strong> Recalculo de Importe </strong>al abandonar el focus de cantidad.</li>	
				<li>Pinchar en icono eliminar, tacha linea o la vuelve a mostrar bien.</li>
				<li><strong>Al agregar Cliente:</strong></li>
					<ul>
						<li>Vuelve focus al codigo de barras.</li>
						<li>Se agregan los datos del cliente seleccionado.</li>
					</ul>
				<li>Agregar <strong>medio </strong>codigo barras:</li>
					<ul>
						<li>Se abre ventana modal, listado de productos con ese codigo barras.</li>
						<li>Focus en caja de busqueda.</li>
						<li>Moverse con flechas por lista y pinchar con enter o raton.</li>
						<li>Volver <strong> focus al codigo de barras.</strong></li>
					</ul>
				<li>Agregar Referencia:</li>
					<ul>
						<li>Se abre ventana modal, listado productos.</li>
						<li>Focus en caja de busqueda.</li>
						<li>Moverse con flechas por lista y pinchar con enter o raton.</li>
						<li style="color:red">Volver <strong> focus a la referencia o no?.</strong>Ahora vuelve a codigo de barras.</li>
					</ul>
			</ul>
	</ul>
<h3>Estados de tickets:</h3>
	<ul>
		<li>estado = Cobrado (ticketst, ticketstemporales)</li>
		<li>estado = Abierto (ticketstemporales)</li>
		<li>estado = Cerrado  (una vez hecho el cierre, en ticketst cambiar estado)</li>
	</ul>
	
</div>
