// @ Autor: Ricardo Carpintero
// @ Ayuda : info@solucionesvigo.es
// @ Objetivo 
	// La intencion es crear librería de control de teclado y eventos de raton.
	// Obtenemos la tecla pulsada según input y dato, y poder realizar las acciones que indiquemos.
	// Informacion de event
	// http://librosweb.es/libro/javascript/capitulo_6/obteniendo_informacion_del_evento_objeto_event.html
// @ parametros que recibimos.
	// 	event -> Objeto, donde podemos obtener el numero tecla, typo de evento ( mouse o key).
 
// RECUERDA :
//   - En nuestro javascript tenemos que tener las funciones.
// 	 	 		- Funcion controladorAcciones(objetoCaja,accion) -> Esta en fichero funciones js
//	   			es la encargada realizar las acciones que indica la tecla.
//				- Funcion after_constructor-> Si queremo hacer antes construir objeto de caja (event)
//				- Funcion before_constructor -> Lo queremos hacer despues pulsar o evento.
// 	 -

// Esto se debería hacer con clases, pero bueno... el deconocimeinto es grande... :-)
function cajainput(input){
	this.acciones = input.acciones;
	this.id_input = input.id_input;
	this.parametros = input.parametros;
	this.before_constructor = input.before_constructor;
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
						// Si no tiene parametros la tecla no entra.
						if (typeof this.parametros !== 'undefined'){
							var arrayParametros = Object.keys(this.parametros);
							if ( arrayParametros.indexOf(parametro) >= 0){
								return this.parametros[parametro];
							} 
						}
					};
	

}


function controlEventos(event){
	console.log('===== ENTRE CONTROLEVENTOS LIB/TECLADO ======');
	// Obtenemos el nombre del objeto que tenemos crear
	var nombre_objeto = event.originalTarget.dataset.obj;
	// Creamos obj que indicamos en data-objecto con string
	var padre_caja = window[nombre_objeto];
	// Comprobamos si es un evento pulsar. ( falta alguno mas... que se pued añadir)
	if (event.type === 'keydown'){
		var tecla = event.keyCode
	}
	if (typeof padre_caja === 'object'){
		// Comprobamos si tenemos hacer algo antes de crear objeto caja
		if (typeof padre_caja.after_constructor !== 'undefined'){
		// Antes de constructor si hay lo ejecutamos.
			padre_caja = after_constructor(padre_caja,event);
		}
		var caja = new cajainput(padre_caja);
		caja.name_cja =event.originalTarget.name;
		// Comprobamos si tenemos que hacer algo mas (before_constructor)
		if (typeof caja.before_constructor !== 'undefined') {
			caja = before_constructor(caja);
		}
		
	} else {
		
		alert( ' Objeto crear no reconocido ');
	}

	if (event.type === 'blur' || event.type==='click'){
		// Es un evento de raton
		// Me ejecuta dos veces porque al hacer click, ejecuta como pulsará intro, y luego ejecuta los que tengamos definido.
		// por eso si queremos controlarlo debemos tener parametro_intro, para evitarlo.
		// ponemos como si pulsara tecla 13 (intro)
		tecla = '13';
		if (typeof caja.darParametro('pulsado_intro') !== 'undefined'){
			if (caja.darParametro('pulsado_intro') === 'Si'){
				// En este caso es que ya pulso intro,
				// Y como se ejecuta click despues de pulsar intro, entonces permitimos repetir.
				var accion = 'error';
			}
		}
	}
	if (accion !== 'error' ){
		var accion = caja.darAccion(tecla);
		// Ejecutamos funcion (accion)
		controladorAcciones(caja,accion);
	}
}




