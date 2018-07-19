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
	this.tipo_event ;
	this.tecla;
	this.acciones = input.acciones;
	this.id_input = input.id_input;
	this.parametros = input.parametros;
	this.before_constructor = input.before_constructor;
	this.darValor = function() {
						var valor = $('#'+this.id_input).val()
						return valor;
					};
	this.darAccion = function(tecla = 0) {
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
    var nombre_objeto = event.target.dataset.obj;
	// Creamos obj que indicamos en data-objecto con string
	var padre_caja = window[nombre_objeto];
	// Comprobamos si es un evento pulsar. ( falta alguno mas... que se pued añadir)
    if (event.type === 'keydown'){
        var tecla = event.keyCode;
		console.log('tecla:'+tecla);
	}
	if (typeof padre_caja === 'object'){
		// Comprobamos si tenemos hacer algo antes de crear objeto caja
		if (typeof padre_caja.after_constructor !== 'undefined'){
		// Antes de constructor si hay lo ejecutamos.
			padre_caja = after_constructor(padre_caja,event);
		}
        var caja = new cajainput(padre_caja);
            // Buscamos la accion para esa tecla
            // Solo realizo si hay accion para esa tecla.
            caja.name_cja =event.target.name;
            caja.tipo_event = event.type;
            caja.tecla = event.keyCode;
            // Comprobamos si tenemos que hacer algo mas (before_constructor)
            if (typeof caja.before_constructor !== 'undefined') {
                caja = before_constructor(caja);
            }

		
	} else {
		var accion = 'error';
		alert( ' Objeto a crear no reconocido ');
	}

    if (event.type === 'blur' || event.type==='click'){
		// Blur -> Evento al perder un foco de objeto
        // click -> Evento al hacer click en objeto.
		// [RECUERDA] 
        //      -> Que el evento Blur se ejecuta siempre cuando un objeto pierde focus.
        //      -> Que el Click luego tambien pierde el focos, por lo que puede ejecutarse dos veces.
        //      -> Al hacer click nosotros aquí hacemos lo mismo que haciendo click.
		console.log('Ejecutamos lo mismo que intro al pulsar click');
		tecla = '13'; // Hacemos la misma accion que intro.
        if (typeof caja.darParametro('pulsado_intro') !== 'undefined'){
			if (caja.darParametro('pulsado_intro') === 'Si'){
				// Si tenemos parametro control intro en la caja, y este esta como Si, no repetimos la accion.
				console.log('Tipo event:'+ event.type + '   No hacemos nada ');
                var accion = 'error';
			} else {
				caja.parametros.pulsado_intro = 'Si';
			}
		}
        
	}
    
	if (accion !== 'error' ){
    	var accion = caja.darAccion(tecla);
        console.log (' Accion que vamos realizar:'+ accion);
		// Ejecutamos funcion (accion)
        if (accion.indexOf('Accion') >-1){
            // Quiere decir queremos que vaya directamente a esa funcion
            if(typeof accion.toString === 'function'){
                // Existe la funcion vamos ejecutarla
                console.log('Voy ejecutar funcion '+accion)
                accion = accion+'(caja,tecla)';
                eval(accion);
            } else {
                console.log('Error no existe la funcion ' +accion);
            }
        } else {
            controladorAcciones(caja,accion, tecla);
        }
	}
}




