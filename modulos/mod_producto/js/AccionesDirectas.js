/* Motivo:
 *  El fichero funciones.js se estaba haciendo eterno, por ello creo este fichero aparte.
 *  
 * Objetivo:
 *  - La function de controladorAcciones suele ser muy grande.
 *  - Poner las funciones que son AccionesDirectas
 *  Las acciones que venimos de controlador eventos (teclados.js) sin ir al controladorAcciones
 * 
 * Nota:
 *  Lo ideal seria que estuvieran clasificados los js por funciones que para cada vista, aunque ahora tal
 *  como esta organizado funciones.js , no puedo hacerlo.
 * */



function RehacerMayor(){

	let rutaAbsoluta = String(window.location);

	let rutaSinId = rutaAbsoluta.split('idArticulo=');

	console.log("ID -> " + id);
	console.log("Fecha Inicial -> " + fechaInicio);
	console.log("Fecha Final -> " + fechaFin);

	let idnuevo = document.getElementById("idArticulo").value;
	let fechaInicialN = document.getElementById("fecha_inicial").value;
	let fechaFinalN = document.getElementById("fecha_final").value;

	console.log("***********************");
	console.log("ID value -> " + idnuevo);
	console.log("Fecha Inicial value-> " + fechaInicialN);
	console.log("Fecha Inicial value -> " + fechaFinalN);

	if(id != idnuevo){
		id = idnuevo;
	}
	if(fechaInicio != fechaInicialN){
		fechaInicio = fechaInicialN;
	}
	if(fechaFin != fechaInicialN){
		fechaFin = fechaFinalN;
	}

	location.replace(rutaSinId[0] + "idArticulo=" + id + "&fecha_inicial=" + fechaInicio + '&fecha_final=' + fechaFin);


}




function controladorAcciones(caja,accion, tecla){
	switch(accion) {
		case 'revisar_contenido':
			validarEntradaNombre(caja);
		break;

		case 'controlReferencia':
            comprobarReferencia();
		break;

		case 'salto':
			console.log("Estoy en buscar controladorAcciones-> salto + caja:");
			console.log(caja);
		break;
		
		case 'salto_recalcular':
			var re= comprobarNumero(caja.darValor());
			if ( re === true){
				recalcularPrecioSegunCosteBeneficio(caja);
			}
		break
		
		case 'recalcularPvp':
			var re= comprobarNumero(caja.darValor());
			if ( re === true){
				recalcularPvp(caja.id_input);
			}
		break
		
		case 'controlCosteProv':
			caja.id_input = caja.name_cja;
			console.log(caja.darValor());
			var re= comprobarNumero(caja.darValor());
			console.log(re);
			if ( re === false){
				alert( 'Error en el coste, fijate bien');
			} else {
				// Volvemos a ponerla solo lectura.
				bloquearCajaProveedor(caja);
			}
		break
		
		case 'controlCodBarras':
			caja.id_input = caja.name_cja;
			var codb = caja.darValor();
			if (codb.length>0){
				// No ejecuto si no hay codigo introducido.
				controlCodBarras(caja);
			}
		break;
		
		case 'buscarProveedor':
			// Solo venimos a esta accion cuando pulsamos intro cajaBusquedaproveedor
			// entonce enviamos dedonde=popup, el buscar=Valor cja... que puede ser vacio.. 
			var buscar = caja.darValor();
			var dedonde = 'popup';
			BuscarProveedor (dedonde,buscar)
		break;

		case 'mover_down':
			// Controlamos si numero fila es correcto.
			console.log(caja);
			var nueva_fila = 0;
			if ( isNaN(caja.fila) === false){
				nueva_fila = parseInt(caja.fila)+1;
			} 
			console.log('mover_down:'+nueva_fila);
			mover_down(nueva_fila,caja.darParametro('prefijo'));
		break;

		case 'mover_up':
			console.log( 'Accion subir 1 desde fila'+caja.fila);
			var nueva_fila = 0;
			
			if ( isNaN(caja.fila) === false){
				nueva_fila = parseInt(caja.fila)-1;
			}
			mover_up(nueva_fila,caja.darParametro('prefijo'));
			
		break;

        default:
            console.log( ' No hubo accion a realizar,accion pedida '+accion);
        break;
	}
}

// ---------------------------------  Funciones control de teclado ----------------------------------------------- //

function after_constructor(padre_caja,event){
	// @ Objetivo:
	// Ejecuta procesos antes construir el obj. caja. ( SI ANTES) Se fue pinza.. :-)
	// Traemos 
	//		(objeto) padre_caja -> Que es objeto el padre del objeto que vamos a crear 
	//		(objeto) event -> Es la accion que hizo, que trae todos los datos input,button , check.
	console.log("entre aqui");
	if (padre_caja.id_input.indexOf('pvpRecomendado') >-1){
		padre_caja.id_input = event.target.id;
	}
	return padre_caja;
}

function before_constructor(caja){
	// @ Objetivo :
	//  Ejecutar procesos para obtener datos despues del construtor de caja. ( SI DESPUES ) :-)
	//  Estos procesos los indicamos en parametro before_constructor, si hay
	console.log( 'Entro en before');
	if (caja.id_input.indexOf('pvpRecomendado_') >-1){
		caja.fila = caja.id_input.slice(15);
	}
    return caja;	
}


