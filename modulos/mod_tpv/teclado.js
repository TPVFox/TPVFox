// @ Autor: Ricardo Carpintero
// @ Ayuda : info@solucionesvigo.es
// @ Objetivo 
	// La intencion es crear librería de control de teclado y eventos de raton.
	// Obtenemos la tecla pulsada según input y dato, y poder realizar las acciones que indiquemos.
	// Informacion de event
	// http://librosweb.es/libro/javascript/capitulo_6/obteniendo_informacion_del_evento_objeto_event.html
// @ parametros que recibimos.
	// 	event -> Objeto, donde podemos obtener el numero tecla, typo de evento ( mouse o key).
	// 	id_input -> Nombre del id donde se ejecuto la funcion.
 
// RECUERDA :
//   - Tenemos un fichero controlador.js que es donde :
// 		 - Tenemos objeto por cada id , con las acciones y teclas, con parametros si fueran necesarios.
//		 - Tenemos la funciones:
// 	 	 		- Funcion controladorAcciones(objetoCaja,accion) -> Esta en fichero funciones js
//	   			es la encargada realizar las acciones que indica la tecla.
//				- Funcion construtor(input)-> Es la que crea el objeto caja al pulsar.
//				- Funcion construtor(input)
// 	 -

// Esto se debería hacer con clases, pero bueno... el deconocimeinto es grande... :-)
var nuevo_evento = {};
function cajainput(input){
	this.acciones = input.acciones;
	this.id_input = input.id_input;
	this.parametros = input.parametros;
	this.darValor = function() {
						var valor = $('#'+this.id_input).val()
						return valor;
					};
	this.darAccion = function(tecla) {
					    var arrayteclas = Object.keys(this.acciones);
						if ( arrayteclas.indexOf(tecla.toString()) >= 0){
							return this.acciones[tecla.toString()];
						} else {
							return 'error';
						}
					};
	this.darParametro = function(parametro) {
					    var arrayParametros = Object.keys(this.parametros);
						if ( arrayParametros.indexOf(parametro) >= 0){
							return this.parametros[parametro];
						} 
					};

}

function construtor(input){
	console.log('Input en constructor:'+input);
	
	switch(true) {
		// Creamos objeto caja
		case (input ==='cajaBusqueda'):
			// Llegamos aquí despues pulsar una tecla en cajaBusqueda
			// pero le añadimos name el nombre id_input
			// Antes de crear obj caja debemos saber cual vamos añadir.
			obj = new cajainput(cajaBusquedaproductos);
			var name = $('#'+input).attr("name");
			var pantalla = 'popup';
			obj.name_cja =$('#'+input).attr("name");
			obj.parametros.dedonde = pantalla;
			// Añadimos parametro campo que necesitamos y el nombre cja que pertenece para cada caso.
			if (name ==='Codbarras'){
				obj.parametros.campo = cajaCodBarras.parametros.campo;
				obj.name_cja ='Codbarras';

			}
			if (name ==='Referencia'){
				obj.parametros.campo = cajaReferencia.parametros.campo;
				obj.name_cja ='Referencia';
			}
			if (name ==='Descripcion'){
				obj.parametros.campo = cajaDescripcion.parametros.campo;
				obj.name_cja ='Descripcion';
			}
			return obj;	
			break;

		case (input ==='cajaBusquedacliente') :
			obj = new cajainput(cajaBusquedacliente);
			return obj;
			break;
		
		case (input.indexOf('N_') >-1) :
			console.log('Construtor en N_ ');
			obj = new cajainput(idN);
			obj.id_input = input; // El id es este.
			//Ahora añadimos a objeto numero fila
			obj.fila = input.slice(2);
			console.log(obj.fila);
			return obj;
			break;
	
		case (input.indexOf('Unidad_Fila') >-1):
			console.log('Construtor en Unidad fila');
			Unidad_Fila.id_input = input;
			obj = new cajainput(Unidad_Fila);
			obj.parametros.item_max = productos.length;
			obj.fila = input.slice(12);
			return obj;
			break;
		case (input ==='Codbarras'):
			obj = new cajainput(cajaCodBarras);
			obj.name_cja =$('#'+input).attr("name");
			return obj;	
			break;
		
		case (input ==='Referencia'):
			obj = new cajainput(cajaReferencia);
			obj.name_cja =$('#'+input).attr("name");
			return obj;	
		
		case (input ==='Descripcion'):
			obj = new cajainput(cajaDescripcion);
			obj.name_cja =$('#'+input).attr("name");
			return obj;	
			break;
		case (input ==='entrega'):
			obj = new cajainput(entrega);
			obj.name_cja =$('#'+input).attr("name");
			return obj;	
			break;
		case (input ==='CobrarAceptar'):
			obj = new cajainput(CobrarAceptar);
			obj.name_cja =$('#'+input).attr("name");
			return obj;	
			break;
			
		default:
			console.log( 'No hay accion para esa caja:'+input);
		
	}
}
	

function controlEventos(event){
	console.log('===== ENTRE TECLAPULSADA ======');
	// Comprobamos si la tecla pulsada tiene algún tipo accion para ese id_input
	nuevo_evento = event;
	id_input = event.originalTarget.id;
	tecla = event.keyCode

	console.log(event);
	var caja = new construtor(id_input);
	if (event.type === 'blur' || event.type==='click'){
		// Es un evento de raton, ponemos como si pulsara tecla 13 (intro)
		tecla = '13';
		if (caja.darParametro('pulsado_intro') === 'Si'){
			// En este caso es que ya pulso intro,
			// Y como se ejecuta click despues de pulsar intro, entonces permitimos repetir.
			var accion = 'error';
		}
	}
	if (accion !== 'error' ){
		var accion = caja.darAccion(tecla);
		// Ejecutamos funcion (accion)
		controladorAcciones(caja,accion);
	}
}



// ejemplo de obj input que enviamos al constructor.
//~ var cajaBusquedaproductos = {
	//~ id_input : 'cajaBusqueda',
	//~ acciones : { 
		//~ 13 : 'buscarProductos', // pulso intro
		//~ 40 : 'mover_down', // pulso abajo
		 //~ 9 : 'mover_down', // tabulador
		//~ },
	//~ parametros  : {
		//~ dedonde : 'popup',
		//~ campo   :'' // Este campo tendremos llenarlo al cargar el modal
		//~ }
//~ }

