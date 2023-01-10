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

function controladorAcciones(caja,accion, tecla){
	// @ Objetivo es obtener datos si fuera necesario y ejecutar accion despues de pulsar una tecla.
	//  Es Controlador de acciones a pulsar una tecla que llamamos desde teclado.js
	// @ Parametros:
	//  	caja -> Objeto que aparte de los datos que le ponemos en variables globales de cada input
	//				tiene funciones que podemos necesitar como:
	//						darValor -> donde obtiene el valor input
	switch(accion) {
		case 'buscarClientes':
			// Esta funcion necesita el valor.
			console.log("Estoy en buscarClientes");
			console.log(caja);
			if( caja.darValor()=="" && caja.id_input=="id_cliente"){
				// Entramos cuando venimos de id de proveedor.
				var d_focus="Cliente";
                ponerFocus(d_focus);
            }else{
				buscarClientes(caja.darParametro('dedonde'),caja.id_input ,caja.darValor());
			}
		break;

        case 'desde_fecha':
            console.log('Estoy Accion/case desde_fecha');
            // Controlamos si es temporal, ya que si no es temporal, tenemos que generar si cambio de valor.(claro)
            console.log(cabecera);
            if (cabecera.fecha !== caja.darValor()){
                // Solo creamos temporal si la accion es editar
                if (cabecera.accion == 'editar'){
                    cabecera.fecha = caja.darValor();
                    // Deberíamos recalcular  fecha vencimiento, ante de generar temporal
                    addTemporal(caja.darParametro('dedonde'));
                }
            }
            var d_focus = 'Cliente';
            ponerFocus(d_focus)

        break;

		case 'saltar_nombreCliente':
		console.log('Entro en acciones saltar_nombreCliente');
		var dato = caja.darValor();
			if ( dato.length === 0){
				var d_focus = 'Cliente';
				ponerFocus(d_focus);
			}
			break;
			case 'saltar_nombreClienteArticulo':
		console.log('Entro en acciones saltar_nombreCliente');
		var dato = caja.darValor();
				var d_focus = 'Cliente';
				ponerFocus(d_focus);
			
        break;
        
		case 'saltar_Fecha':
		console.log('Entro en acciones saltar_fecha');
			var d_focus = 'fecha';
			ponerFocus(d_focus);
        break
        
		case 'saltar_idArticulo':
		console.log('Entro en acciones saltar_idArticulo');
            console.log(caja.tecla);
			var dato = caja.darValor();
			if(dato.length === 0){
				var d_focus = 'idArticulo';
				ponerFocus(d_focus);
			}
			
			break
		case 'buscarProductos':
			console.log('Entro en acciones buscar Productos');
			buscarProductos(caja.name_cja,caja.darParametro('campo'),caja.id_input , caja.darValor(),caja.darParametro('dedonde'));
			break;
			
			
		case 'saltar_CodBarras':
		console.log('Entro en acciones codigo de barras');
			var dato = caja.darValor();
			if ( dato.length === 0){
				// Si esta vacio, sino permitimos saltar.
				var d_focus = 'Codbarras';
				ponerFocus(d_focus);
			}
		break;
        
		case 'recalcular_totalProducto':
		console.log("entre en recalcular precio producto");
			// recuerda que lo productos empizan 0 y las filas 1
			var nfila = parseInt(caja.fila)-1;
			// Comprobamos si cambio valor , sino no hacemos nada.
			productos[nfila].nunidades = caja.darValor();
			productos[nfila].ncant=caja.darValor();
			recalculoImporte(productos[nfila].nunidades,nfila, caja.darParametro('dedonde'));
			if (caja.tipo_event !== "blur"){
                ponerFocus( ObtenerFocusDefectoEntradaLinea());
			}
			
			
		break;
        
		case 'mover_down':
			// Controlamos si numero fila es correcto.
			var nueva_fila = 0;
			if(caja.id_input=="cajaBusquedacliente" || caja.id_input=="cajaBusqueda"){
				ponerFocus('N_0');
				
			}else{
			if ( isNaN(caja.fila) === false){
				nueva_fila = parseInt(caja.fila)+1;
			} 
			console.log('mover_down:'+nueva_fila);
			
			mover_down(nueva_fila,caja.darParametro('prefijo'));
			}
		break;
        
		case 'mover_up':
			console.log( 'Accion subir 1 desde fila'+caja.fila);
			var nueva_fila = 0;
			if(caja.fila=='0'){
				if(cabecera.idCliente>0){
					ponerSelect('cajaBusqueda');
				}else{
					$("#cajaBusquedacliente").select();
				}
			}else{
				if ( isNaN(caja.fila) === false){
					nueva_fila = parseInt(caja.fila)-1;
				}
				mover_up(nueva_fila,caja.darParametro('prefijo'));
			}
		break;
			
		case 'saltar_Referencia':
			var dato = caja.darValor();
			if ( dato.length === 0){
				// Si esta vacio, sino permitimos saltar.
				var d_focus = 'Referencia';
				ponerFocus(d_focus);
			}
		break;
        
		case 'saltar_Descripcion':
			var dato = caja.darValor();
			if ( dato.length === 0){
				// Si esta vacio, sino permitimos saltar.
				var d_focus = 'Descripcion';
				ponerFocus(d_focus);
			}
		break;
        
		case 'saltar_CodBarras':
			var dato = caja.darValor();
			if ( dato.length === 0){
				// Si esta vacio, sino permitimos saltar.
				var d_focus = 'Codbarras';
				
				ponerFocus(d_focus);
			}
		break;
        
		case  'saltar_productos':
			if (productos.length >0){
			// Debería añadir al caja N cuantos hay
				console.log ( 'Entro en saltar a producto que hay '+ productos.length);
				ponerSelect('Unidad_Fila_'+productos.length);
			} else {
			   console.log( ' No nos movemos ya que no hay productos');
			}
		break;
	
		case 'saltarNumPedido':
				console.log("Ente en fecha Al");
				var dato = caja.darValor();
				cabecera.fecha=dato;
				if  ( $('#numPedido').css('display') == 'none' ) {
						var d_focus='id_clienteAl';
				}else{
					
					var d_focus = 'numPedido';
				}
                ponerFocus(d_focus);

		break;
        
		case 'buscarAdjunto':
		console.log("Entre en buscar adjunto");
		buscarAdjunto(caja.darParametro('dedonde'),caja.darValor());
		break;

        case 'selectFormas':
		console.log("Entre en la funcion select formas");
		selectFormas();
		break;

        case 'buscarClientesAlbaran':
		console.log("Entre en buscarCliente albaran");
		buscarClienteAl(caja.darParametro('dedonde'),caja.id_input ,caja.darValor());
		break;
	} 
}


function ObtenerCajaSiguiente(idCaja){
    // @ Objetivo
    //  Obtener cual es la caja siguiente salto 
    // @ Parametro
    //   idcaja -> la caja actual.
    // @ Devolvemos
    //   d_focus -> string con id caja siguiente.
    var d_focus = '';
    switch(idCaja){
        case 'idArticulo':
            d_focus = 'Referencia';
        break;
        
        case 'Referencia':
            d_focus = 'Codbarras';
        break;

        case 'Codbarras':
            d_focus = 'Descripcion';
        break;
    }
    return d_focus;
}

function after_constructor(padre_caja,event){
	// @ Objetivo:
	// Ejecuta procesos antes construir el obj. caja.
	// Traemos 
	//		(objeto) padre_caja -> Que es objeto el padre del objeto que vamos a crear 
	//		(objeto) event -> Es la accion que hizo, que trae todos los datos input,button , check.
	if (padre_caja.id_input.indexOf('N_') >-1){
		padre_caja.id_input = event.target.id;;
	}
	if (padre_caja.id_input.indexOf('Unidad_Fila') >-1){
		padre_caja.id_input = event.target.id;;
	}
    return padre_caja;
}

function before_constructor(caja){
	// @ Objetivo :
	//  Ejecutar procesos para obtener datos despues del construtor de caja.
	//  Estos procesos los indicamos en parametro before_constructor, si hay
	console.log( 'Entro en before');
	if (caja.id_input ==='cajaBusqueda'){
		caja.parametros.dedonde = 'popup';
		if (caja.name_cja ==='Codbarras'){
			caja.parametros.campo = cajaCodBarras.parametros.campo;
		}
		if (caja.name_cja ==='Referencia'){
			caja.parametros.campo = cajaReferencia.parametros.campo;
		}
		if (caja.name_cja ==='Descripcion'){
			caja.parametros.campo = cajaDescripcion.parametros.campo;
		}
	}
	if (caja.id_input.indexOf('N_') >-1){
		console.log(' Entro en Before de '+ caja.id_input)
		caja.fila = caja.id_input.slice(2);
		if(caja.tecla==13){
			if(cabecera.idCliente>0){
				console.log(caja.parametros.dedonde);
				if(caja.parametros.dedonde=='albaran'){
                    console.log(caja);
                    console.log(caja.parametros.dedonde);
                    buscarProductos('idArticulo', 'a.idArticulo', 'idArticulo', caja.darValor(), caja.parametros.dedonde);
                }
			}else{
				if(caja.parametros.dedonde!="factura"){
					 buscarClientes(caja.parametros.dedonde, "id_cliente", caja.darValor());
				}
			}
		}
	}
	if (caja.id_input.indexOf('Unidad_Fila') >-1){
		console.log("input de caja");
		caja.parametros.item_max = productos.length;
		caja.fila = caja.id_input.slice(12);
	}
	return caja;	
}


function ObtenerFocusDefectoEntradaLinea(){
	var valor = $("#salto").val();
	switch(valor){
		case '0':
			d_focus='Referencia';
		break;
		case '1':
			d_focus='idArticulo';
		break;
		case '2':
			d_focus='Referencia';
		break;
		
		case '3':
			d_focus='Codbarras';
		break;
		case '4':
			d_focus='Descripcion';
		break;
		default:
			d_focus='Referencia';
		break;
		
	}
    return d_focus;
}


function mover_up(fila,prefijo){
	var d_focus = prefijo+fila;
		// Segun prefijo de la caja seleccionamos o pones focus.
	if ( prefijo === 'Unidad_Fila_'){
		// Seleccionamos
		ponerSelect(d_focus);
	} else {
		ponerFocus(d_focus);
	}
}

function mover_down(fila,prefijo){
	var d_focus = prefijo+fila;
	// Segun prefijo de la caja seleccionamos o pones focus.
	if ( prefijo === 'Unidad_Fila_'){
		// Seleccionamos
		ponerSelect(d_focus);
	} else {
		ponerFocus(d_focus);
	}
}

function ponerFocus (destino_focus){
	// @ Objetivo:
	// 	Poner focus a donde nos indique el parametro, que debe ser id queremos apuntar.
	console.log('Entro en enviar focus de :'+destino_focus);
	setTimeout(function() {   //pongo un tiempo de focus ya que sino no funciona correctamente
		jQuery('#'+destino_focus.toString()).focus(); 
	}, 50); 

}

function ponerSelect (destino_focus){
	// @ Objetivo:
	// 	Poner focus a donde nos indique el parametro, que debe ser id queremos apuntar.
	console.log('Entro en ponerselects de :'+destino_focus);
	setTimeout(function() {   //pongo un tiempo de focus ya que sino no funciona correctamente
		jQuery('#'+destino_focus.toString()).select(); 
	}, 50); 

}
