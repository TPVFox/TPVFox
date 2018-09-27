<div>
<nav class="col-sm-2" id="myScrollspy">
	<a class="text-ritght" href="./">Volver Atrás</a>
</nav>
<div class="col-md-12">
    <h2>Modulo de virtuemart en tpvfox</h2>
    <p>El codigo de este modulo se encuentra en <b>directorio modulos/mod_virtuemart</b>, donde podremos obtener los datos de la tienda on-line que tengamos con Joomla y virtuemart , para poder actualizar en TpvFox.</p>
    <p> El objetivo inicial es podre comprobar que productos cambiaron en la web y ademas podamos añadirlos a tpvfox.s </p>
    <h3> Las 3 fases del flujo de usuario para la actualizacion y modificacion de productos de web en tpv</h3>
    <div class="col-md-12">
    	<h4>1.-Configuracion de opciones</h4>
		<p>Lo primero seleccionamos la tienda web queremos importar.Se podría tener varias webs</p>
        <p>Luego escogemos y asignamos:</p>
        <ul><li>Acciones
                <ul>
                    <li><b>Importamos codbarras:</b>
                        En la web el campo que aparece en la ficha del producto en la web -GTIN (EAN,ISBN) es el codbarras, para meter en la WEB varios codbarras los separamos por ";".<br/>
                        Las opciones tiene:
                        <br/>-Añadir codbarras:
                        <br/>-Ignorar: No se tiene encuenta tanto para añadir ,como para modificar.. y  tampoco se tiene en cuenta a la hora comprobar diferencias.</li>
                    <li><b>Referencia de producto:</b> Las referencias de los productos se anotan <b>tabla articulosTienda</b>.<br/>
                        Las opciones que tienes:<br/>
                        -Copiar solo en el registro de tienda Web con refencia<br/>
                        -Copiar copiar la referencia en las dos tiendas (Web y principal).<br/>
                        -Ignorar: Donde no se copia , ni se tiene en cuanta a la hora hacer comprobaciones.</li>
                    <li><b>Estado cuando es nuevo:</b> Que estado quieres ponerle cuando creamos un producto nuevo en Tpv que ya existe en la Web</li>
                    <li><b>Estado cuando es Modificado:</b>Que estado quieres ponerle cuando modifiamos un producto y ponemos los datos de la Web</li>
                    <li><b>Ultimo coste:</b>Nos permite calcular o no el coste y ponerlo.</li>
                </ul>
            </li>
            <li><b>Beneficio por defecto:</b>El beneficio que ponemos por defecto a productos nuevos. Ha producto modificados ?????<br/>
            Debería poder permitir una cantidad.</li>
            <li><b>Coste promedio</b>????</li>
		</ul>
	</div>
    <div class="col-md-6">
		<h4>2.-Obtenemos los productos de la web de 100 en 100</h4>
		<p>Este proceso lo que hacer obtener los productos de 100 en 100 de la web, para luego comprobar si son Nuevos o Modificados.
        </p>
        <p>Una vez se compruebe, se pintan para poder luego dar la opción al usuario a realizar la opcion.</p>
        <p>El terminar pintar nuevos y modificados de esos 100, va buscar los 100 siguientes.</p>
	</div>
	<div class="col-md-6">
		<h4>3.-El usuario decide</h4>
		<p>Una vez termine de comprobar todos los productos de la web, permite al usuario poder importar tpv de uno en uno.</p>
	</div>	
    <div class="col-md-12">
        <h3>Diagrama general de flujo</h3>
        <p>Un diagrama general de flujo a la hora actualizar y modificar producto en tpv desde la web</p>
        <img src="<?php echo $UrlActual;?>Diagrama2.svg" style= "max-width=400px " alt="Diagrama de flujo"/>
        <p>El flujo es de modulo virtuemart a plugin y de plugin a APISV y luego de vuelta...</p>
    </div>

</div>
