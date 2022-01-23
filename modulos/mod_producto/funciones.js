$(function ()
    {
        $(".boton-regularizar").on("click", function (event) {
            event.stopPropagation();
            event.preventDefault();
            var data = $(event.currentTarget).data();
            RegularizarStock(data.idarticulo);
        });
        $("#stockcolocar").keyup(function (event) {
            var stock = $("#stockactual").val();
            var colocar = $("#stockcolocar").val();
            if (!isNaN(colocar)) {
                var final = parseFloat(colocar) - parseFloat(stock);
                $("#stocksumar").val(final);
            }
        });
        $("#stocksumar").keyup(function (event) {
            event.preventDefault();
            var stock = $("#stockactual").val();
            var sumar = $("#stocksumar").val();
            if (!isNaN(sumar)) {
                var final = parseFloat(sumar) + parseFloat(stock);
                $("#stockcolocar").val(final);
            }
        });
        $('#ventanaModal').on('shown.bs.modal', function() {
              //@Objetivo: llamar a la librería autocomplete 
            $( ".familias" ).combobox({
                select : function(event, ui){ 
                    var idProducto= $( "#idProductoModal" ).val();
                      
                    var botonhtml='<button class="btn btn-primary" onclick="guardarProductoFamilia('+ui.item.value+', '+idProducto+')">Guardar</button>';
                    if(idProducto==0){
                        $('#botonEnviar2').html(botonhtml);  
                    }else{
                         $('#botonEnviar').html(botonhtml);  
                    }
                },
            });
            $( ".estados" ).combobox({
                select : function(event, ui){ 
                    var idProductos= $( "#idProductosModal" ).val();  
                    var botonhtml='<button class="btn btn-primary" onclick="modificarEstadoProductos('+"'"+ui.item.value+"'"+', '+"'"+idProductos+"'"+')">Guardar</button>';
                    $('#botonEnviarEstados').html(botonhtml);  
                },
            });
        });
        //@Objetivo: llamar a la librería autocomplete 
        if( $("select").hasClass("familiasLista")){
            $( ".familiasLista" ).combobox({
                select : function(event, ui){ 
                    //~ var idProducto= $( "#idProductoModal" ).val();
                     var botonhtml='<a class="btn btn-primary" onclick="buscarProductosFamilia('+ui.item.value+')">Buscar</a>';
                   $('#botonEnviar').html(botonhtml);   
                },
            });
            $( ".proveedoresLista" ).combobox({
                select : function(event, ui){ 
                    //~ var idProducto= $( "#idProductoModal" ).val();
                     var botonhtml='<a class="btn btn-primary" onclick="buscarProductosProveedor('+ui.item.value+')">Buscar</a>';
                   $('#botonEnviarPro').html(botonhtml);   
                },
            });
        }
        $( "#toggle" ).on( "click", function()
        {
            $( "#combobox" ).toggle();
        });
    }
);

//recogemos valor de la caja de busqueda que tenemos en Listado tickets o productos
function BuscarProducto (){
	$(document).ready(function()
	{
		// Lo ideal sería identificar palabras..
		// de momento solo una palabra..
		NuevoValorBuscar = $('input[name=buscar').val();
		NuevoValorBuscar = $.trim(NuevoValorBuscar);
		if (NuevoValorBuscar !== ''){
			BProductos= NuevoValorBuscar;
			console.log('Filtro:'+BProductos);
		} else {
			alert (' Debes poner algun texto ');
			BProductos = '';
		}
		return;
	});
}

function BuscarProveedor (dedonde,busqueda=''){
	// @ Objetivo:
	// Obtener caja de busqueda de Proveedor y abrir modal con caja de busqueda de proveedor para añadir un proveedor.
	// @ Parametro:
	// 	 dedonde -> Indicamos quien ejecuta funcion: popup , o productos ( link),
	// 	 busqueda-> Si viene de productos (link) no lo tiene valor, sino si.
	// --  Ahora obtengo un array con los idsProveedores que tiene añadido al producto, ya que hacer falta -- //
	idsProveedores = obtenerIdsProveedores();
	// -- Montamos parametros -- //
	var parametros = {
		"pulsado"    	: 'HtmlCajaBuscarProveedor',
		"dedonde"	 	: dedonde,
		"busqueda"		: busqueda,
		"idsProveedores": idsProveedores
	};
	// -- Enviamos datos por Ajax -- //
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Obteniendo html de cajabuscarproveedor  ****************');
		},
		success    :  function (response) {
			console.log('Repuesta de Obtener HTML de cajabuscarproveedor');
			var resultado =  $.parseJSON(response);
			var titulo = 'Buscar Proveedor Nuevo para este producto'
			var contenido = resultado['html'];
			abrirModal(titulo,contenido);
			focusAlLanzarModal('cajaBusquedaproveedor');
		}
	});
}


function metodoClick(pulsado,adonde){
	// @ Objetivo:
	//  Controlas los click en listadoproductos.
	// @ parametros:
	//     adonde : a donde quiero ir o donde quiero permanecer: ListaTickets, ListaProductos.. 
	console.log("Inicimos switch de control pulsar");
	switch(pulsado) {
		case 'VerProducto':
			// Cargamos variable global ar checkID = [];
			VerIdSeleccionado ();
			if (checkID.length >1 || checkID.length=== 0) {
				alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			}
			// Ahora redireccionamos 
			window.location.href = './'+adonde+'.php?id='+checkID[0];			
        break;
        
		case 'EtiquetasCodBarras':
			// Cargamos variable global ar checkID = [];
			 VerIdSeleccionado ();
			if (checkID.length >1 || checkID.length=== 0) {
				alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			}
			// Ahora redireccionamos 
			window.location.href = './../mod_etiquetado/'+adonde+'.php?idProducto='+checkID[0];			
        break;
		
		case 'AgregarProducto':
			console.log('entro en agregar producto');
			window.location.href = './producto.php';
        break;
		
		case 'NuevaBusqueda':
			// Obtenemos puesto en input de Buscar
			BuscarProducto ();
			// Ahora redireccionamos 
			if (BProductos !== ''){
				window.location.href = './'+adonde+'.php?buscar='+BProductos;
			} else {
				// volvemos sin mas..
				return;
			}
        break;
	 }
} 

function agregoCodBarrasVacio(contNuevo){
	//ajax
	// @ Objetivo
	//agrego campo codigo barras vacio en html
	var tablaC=document.getElementById("tcodigo");
	var cont=tablaC.childElementCount;
	
	var parametros = {
		"pulsado"    : 'HtmlCodigoBarrasVacio',
		"filas": cont
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Obteniendo html de codBarras vacio  ****************');
		},
		success    :  function (response) {
			console.log('Repuesta de Obtener HTML linea de FUNCION -> agregoCodBarrasVacio');
			var resultado =  $.parseJSON(response);
			var nuevafila = resultado['html'];
			$("#tcodigo").prepend(nuevafila);
		}
	});
}

function controlCodBarras(caja){
	// Objetivo
	// Controlar si el codigo de barras es correcto.
	// De momento solo controlo que si existe hace una advertencia.
	validarEntradaNombre(caja); // Limpiamos codigo de "
	var codb = caja.darValor();
	// Ahora debería comprobar si existe este codigo barras en este producto.
	 $('#tcodigo').find(':input').each(function (id){
		var stringId='codBarras_'+id; 
		if ( stringId !== caja.id_input){
			// Evitamos que no repita el mismo codigo barras en el mismo producto.
			if ($('#codBarras_'+id).val() === codb){
				console.log($('#codBarras_'+id).val());
				console.log(codb);
				alert ('No puedes repetir el mismo codbarras en el mismo producto');
				$('#'+caja.id_input).val('');
			}
		}
	});
	var parametros = {
		"pulsado"    : 'ComprobarSiExisteCodbarras',
		"codBarras": codb
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Comprobamos si existe ese codbarras en algún producto  ****************');
		},
		success    :  function (response) {
			console.log('Respuesta de comprobación si existe ese codbarras');
			var resultado =  $.parseJSON(response);
			var msj='';
			resultado.Items.forEach(function (item){
				if (item.idArticulo !== producto.idArticulo){
					msj = 'Existe este codbarras en ';
				}
			});
			if  (msj !==''){
				alert(msj+resultado.NItems+ " productos. \n Estas segura que quiere añadirlo.")	;
			}
		}
	});	
}

function eliminarFamiliaProducto(e){
    var padre=e.parentNode; 
	var abuelo=padre.parentNode; 
	var bisa=abuelo.parentNode; 
	bisa.removeChild(abuelo);
}

function anular(e) {
    // Objetivo:
    // Evitar recargar el formulario al pulsar intro, ya que sino lo recarga.
    // [NO COMPRENDO]
    // No se porque pero lo hace...
    tecla = (document.all) ? e.keyCode : e.which;
    return (tecla != 13);
}

 function eliminarCodBarras(e){
    // @ Objetivo :
    // Eliminar el código de barras . Busca los elementos a eliminar mediante DOM
    // Cuando encuentra el elemento TBODY elimina el hijo que le indicamos
	var padre=e.parentNode; 
	var abuelo=padre.parentNode; 
	var bisa=abuelo.parentNode; 
	bisa.removeChild(abuelo);
 }

function recalcularPrecioSegunCosteBeneficio (caja){
	// @ Objetivo
	// Recalcular precio de PVP sin iva y con iva, segun ultimo coste y beneficio.
	// Obtenemos el iva que selecciono.
	console.log('RecalculoPreciosSegunCosteBeneficio');
	var iva = obtenerIva();
	var coste = parseFloat($( "#coste" ).val());
	var beneficio = parseFloat($( "#beneficio" ).val());
	if (beneficio >0 ){
		// No puedo dividir entre 0
		beneficio = beneficio/100;
	}
	var precioSiva = coste+(coste*beneficio);
	var precioCiva = precioSiva+(precioSiva*iva);
	// Ahora cambiamos los datos en input.
	destacarCambioCaja('pvpSiva');
	destacarCambioCaja('pvpCiva');
	$('#pvpSiva').val(precioSiva.toFixed(2));
	$('#pvpCiva').val(precioCiva.toFixed(2));
}

function destacarCambioCaja(idcaja){
	$("#"+idcaja).css("outline-style","solid");
	$("#"+idcaja).css("outline-color","coral");
	$("#"+idcaja).animate({
			"opacity": "0.3"
		 },2000);
	t = setTimeout(volverMostrar,2000,idcaja);
}

function volverMostrar(idcaja){
	console.log('Entro volver mostrar');
	$("#"+idcaja).animate({
			"opacity": "1"
		 },1000);
	$("#"+idcaja).css("outline-color","transparent")
}

function recalcularPvp(dedonde){
	// @ Objetivo:
	// Recalcular precio s/iva y precio c/iva segun los datos que tengan las cjas y de donde venga.
	// @ Parametros:
	//  dedonde = (string) id_input.
	// Obtenemos iva ( deberías ser funcion)
	var iva = obtenerIva();
	if (dedonde === 'pvpSiva'){
		var precioSiva = parseFloat($('#pvpSiva').val(),2);
		var precioCiva = precioSiva+(precioSiva*iva);
		// Ahora destacamos los input que cambiamos.		
		destacarCambioCaja('pvpCiva');
	} else {
		var precioCiva = parseFloat($('#pvpCiva').val(),2);
		iva = iva +1;
		var precioSiva = precioCiva/iva;
		// Ahora destacamos los input que cambiamos		
		destacarCambioCaja('pvpSiva');
	}
	//~ // Ahora cambiamos los datos en input.
	$('#pvpSiva').val(precioSiva.toFixed(2));
	$('#pvpCiva').val(precioCiva.toFixed(2));
}

function obtenerIva(){
	// @ Objetivo
	// Obtener el iva a aplicar según el que tengamos seleccionado.
	var id_iva=$( "#idIva option:selected" ).val();
	var iva = 0;
	ivas.forEach(function(element){
		if (element.idIva === id_iva){
			iva = parseFloat(element.iva,2);
		}
	});
	if (iva >0){
		// No puedo dividir entre 0
		iva = iva/100;
	}
	return iva;
}

function obtenerIdsProveedores(){
	// Objetivo:
	// Obtener ids de los proveedores que tiene ya asignado el producto o que se añadieron ya...
	// por eso utilizamos los valores de los inputs y no los valores de variable proveedor.
	var idsProveedores= [];
	$('.idProveedor').each(function(){
		idsProveedores.push($(this).val());
	});
	return idsProveedores;
}

function AnhadirCodbarras(){
	// @ Objetivo
	// Añadir una caja de codbarras, pero solo si las que hay tiene valor, sino no añade.
	// Contamos los tr que hay body tcodigo
	var num_tr = $('#tcodigo>tbody>tr').length; 
	var vacio = 'No';
	var trComprobar;
	for (i = 0; i <= num_tr; i++) { 
		// Comprobamos que input codbarras tenga valor, sino tiene no creamos tr con input.
		trComprobar =document.getElementById("codBarras_"+i);
		if (document.body.contains(trComprobar)){ 
			var valor = $('#codBarras_'+i).val() ;
			if ( valor.length === 0){
				vacio = 'Si';
			}
		}
	}
	// Solo continuamos si vacio es No, ya que sino hay una caja codBarras vacio.
	if (vacio === 'No'){
		var parametros = {
		"pulsado"    : 'HtmlLineaCodigoBarras',
		"fila": num_tr
		};
		$.ajax({
			data       : parametros,
			url        : 'tareas.php',
			type       : 'post',
			beforeSend : function () {
				console.log('*********  Obteniendo html linea de codBarras  ****************');
			},
			success    :  function (response) {
				console.log('******  Respuesta de html lineas de codBarras *********');
				var resultado =  $.parseJSON(response);
				var nuevafila = resultado['html'];
				$("#tcodigo>tbody").prepend(nuevafila);
			}
		});
	}
}

function GuardarConfiguracion(obj){
	// Si llega aquí es porque cambio el valor de check impresion...
	// tenemos que tomar los valores configuracion para enviarlos y cambiarlos.
	if ($(obj).val() === 'Si'){
		$(obj).val('No');
	} else {
		$(obj).val('Si');
	}
	var valor= $(obj).val();
	var nombre = $(obj).attr("name");
	CambiarConfiguracionMostrarLista(valor,nombre); // Cambiamos el valor de la configuracion
	// Ahora ejecutamos el guardar la configuracion.. pero esperamos un segundo por si tarda en hacer CambiarConfiguracionMostrarListado.
	setTimeout(AjaxGuardarConfiguracion,500);
	// Recargo pagina en un 1 s.
	setTimeout(refresh,1000);
}
function AjaxGuardarConfiguracion(){
	// Objetivo:
	// Guardar configuracion de usuario y modulo.
	var parametros = {
		"pulsado"    		: 'Grabar_configuracion',
		"configuracion"		: configuracion,
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
		console.log('*********  Grabando configuracion **************');
		},
		success    :  function (response) {
				console.log('Respuesta de grabar configuracion');
				// var resultado = $.parseJSON(response);
				var resultado = response;
				return resultado ;
			}
    });
}

function CambiarConfiguracionMostrarLista(valor,nombre){
	// Ahora cambiamos el valor configuracion.
	configuracion.mostrar_lista.forEach(function(element) {
		if (element.nombre === nombre){
			element.valor=valor;
		}
	});
}

function CambiarConfiguracionBuscar_default(nombre){
	// Ahora cambiamos el valor configuracion.
	configuracion.mostrar_lista.forEach(function(element) {
		if (element.nombre === nombre){
			// Creo propiedad buscar_default.
			element.buscar_default='Si';
		} else {
			// A todos los demas elimino propiedad
			delete element.buscar_default;
		}
	});
}


function GuardarBusqueda(event){
	// @ Objetivo :
	// Guardar el campo el que se busca en la configuracion del usuario y del modulo.
	// @ Parametro:
	// 		event-> Es select....
	var campo =  event.target.value;
	CambiarConfiguracionBuscar_default(campo);
	// Ahora ejecutamos el guardar la configuracion.. pero esperamos un segundo por si tarda en hacer CambiarConfiguracionBuscar_default.
	var respuesta = setTimeout(AjaxGuardarConfiguracion,500);
	// Limpiamo la cja de busqueda, ya que cambiamos la  busqueda.
	$('input:text[name=buscar]').val("");
}

function GuardarFiltroEstado(event){
	// @ Objetivo :
	// Guardar el campo el que se busca en la configuracion del usuario y del modulo.
	// @ Parametro:
	// 		event-> Es select....
	console.log("GuardarFiltroEstado");
    if (event.target.value !== 'Sin Filtrar'){
        configuracion.estado_filtro = event.target.value;
    } else {
        delete configuracion.estado_filtro;
    }
    // Ahora creamos grabamos configuracion en usuario
    AjaxGuardarConfiguracion();
    // Redireccionamos
    setTimeout(refresh,1000);
}

function refresh() {
	// Funcion para recargar pagina.
	location.reload(true);
}

function desActivarCoste(event){
	// Objetivo:
	// Activar o Desactivar input de ultimo coste, para poder recalcular precio.
	// Cambiamo el nombre de la caja para no cambiar el coste_ultimo en post.
	console.log(event.target);
	$('#coste').removeAttr('readonly', '');
}

function desActivarCajasProveedor(obj){
	// Objetivo:
	// Activar o Desactivar cjas de input proveedores coste.
	// Obtenemos el id del proveedor.
	var idInput= obj.id;
	var id_prov = idInput.substr(15, 4);
	// Cambiamos
	$('#prov_coste_'+ id_prov).removeAttr('readonly', '');
	$('#prov_cref_'+ id_prov).removeAttr('readonly', '');
	// Añadimos funcion a input de control de datos.
	$('#prov_coste_'+ id_prov).attr('onkeydown',"controlEventos(event)");
	$('#check_pro_'+ id_prov).removeAttr('disabled', '');
	$('#check_pro_'+ id_prov).removeAttr('readonly', '');
	$('#check_pro_'+ id_prov).attr('onclick', "cambioEstadoProvPrincipal(this)");
}
function bloquearCajaProveedor(caja){
	// Objetivo es poner solo lecturar la cja input
	console.log('Poner solo lectura '+caja.name_cja);
	$('#'+ caja.name_cja).attr('readonly', "true");
}

function cambioEstadoProvPrincipal(obj){
	// Objetivo:
	// Comprobar si cambio estado check de proveedor, si lo marco , desmarca el resto proveedores.
	// Solo puede haber un proveedor principal.
	var check = $('#'+obj.id).prop('checked')
	
	if (check === true){
		// Comprobamos si hay alguno marcado , entonces lo desmarcamos.
		var checks_pro = $("input:checkbox[name=check_pro]:checkbox");
		console.log(checks_pro.length);
		for (i = 0; i < checks_pro.length; i++) { 
			if ( obj.id !== checks_pro[i].id ){
				console.log(checks_pro[i].id+$("#"+checks_pro[i].id).prop('checked'));
				$('#'+checks_pro[i].id).removeAttr('checked', '');
			}
		}
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

function comprobarNumero(valor){
	// Objetivo validar un numero decimal tanto positivo , como negativo.
	var RE = /^\-?\d*\.?\d*$/;
    if (RE.test(valor)) {
        return true;
    } else {
        return false;
    }
}

function eliminarCoste(idArticulo, dedonde, id, tipo, fila){
	
	var parametros = {
		"pulsado"    		: 'eliminarCoste',
		"idArticulo"		: idArticulo,
		"dedonde"			:dedonde,
		"id"				:id,
		"tipo"				:tipo
	};
	console.log(parametros);
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
		console.log('*********  Modificando eliminar costes  **************');
		},
		success    :  function (response) {
				console.log('Respuesta de eliminar costes ');
                var resultado = $.parseJSON(response);
				$('#Row'+ fila).addClass("tachado");
				$("#Row" + fila +"> .eliminar").html('<a onclick="retornarCoste('+idArticulo+', '+"'"+dedonde+"'"+', '+id+', '+"'"+tipo+"'"+', '+fila+');"><span class="glyphicon glyphicon-export"></span></a>');
				//return resultado;
		}	
	});
}
function retornarCoste(idArticulo, dedonde, id, tipo, fila){
	var parametros = {
		"pulsado"    		: 'retornarCoste',
		"idArticulo"		: idArticulo,
		"dedonde"			:dedonde,
		"id"				:id,
		"tipo"				:tipo
	};
	console.log(parametros);
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
		console.log('*********  Modificando eliminar costes  **************');
		},
		success    :  function (response) {
				console.log('Respuesta de eliminar costes ');
                var resultado = $.parseJSON(response);
                $("#Row" + fila).removeClass('tachado');
				$("#Row" + fila +"> .eliminar").html('<a onclick="eliminarCoste('+idArticulo+', '+"'"+dedonde+"'"+', '+id+', '+"'"+tipo+"'"+', '+fila+');"><span class="glyphicon glyphicon-trash"></span></a>');
				//return resultado;
		}	
	});
}
function mensajeImprimir(id, dedonde){
	var mensaje = confirm("¿Quieres imprimir los precios?");
	if (mensaje) {
		var bandera=1;
		imprimir(id, dedonde, bandera);
    } else {
        // Volvemos a albaranes,ya que se ejecuto desde ahi.
		location.href="../mod_compras/albaranesListado.php";
    }
}
function imprimir(id, dedonde, bandera=""){
	var parametros = {
		"pulsado"    		: 'imprimir',
		"dedonde"			:dedonde,
		"id"				:id,
		"bandera"			:bandera
		
	};
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
		console.log('*********  Modificando imprimir   **************');
		},
		success    :  function (response) {
				console.log('Respuesta de eliminar costes ');
				 var resultado = $.parseJSON(response);
				 window.open(resultado.fichero);
				 if (bandera==1){
                    // Volvemos a albaranes ya que se ejecuto desde ahi.
					location.href="../mod_compras/albaranesListado.php";
				}
				 
		}	
	});
}

function imprimirEtiquetas(dedonde){
	var idProductos =TfObtenerCheck('checkSelect'); // funcion de lib/js/tpvfox.js
    var tamano=$("#tamanhos option:selected").val();
    var inputs_cantidades = TfObtenerObjetos('cantidadEtiquetas');
    var productos = [];
    // Ahora mostamos productos con id y valores de cantidad de etiquetas.
    inputs_cantidades.each(function(){
        var idArticulo = this.dataset.idarticulo;
        var index = idProductos.findIndex(id => id === idArticulo);
        if (index >=0 ){
            // Es que existe ese idArticulo en idProductos (esta seleccionado), por lo que montamos array
            console.log(idArticulo+' '+this.value);
            var producto = new Object();
            producto.idArticulo = idArticulo;
            producto.numEtiquetas =this.value;
            productos.push(producto);
        }
    });
    console.log(productos);

	var parametros = {
		"pulsado"    		: 'imprimirEtiquetas',
		"dedonde"			:dedonde,
		"tamano"			:tamano,
		"productos"			:JSON.stringify(productos)
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
		console.log('*********  Modificando eliminar costes  **************');
		},
		success    :  function (response) {
				console.log('Respuesta de eliminar costes ');
				 var resultado = $.parseJSON(response);
				 console.log(resultado);
				 window.open(resultado['fichero']);
				 
		}	
	});
}

function validarEntradaNombre(caja){
	// Objetivo:
	// Eliminar caracteres extraños para evitar errores
	cadena = caja.darValor();
	cadena = cadena.replace('"', '');
	$('#'+caja.id_input).val(cadena);
}

function seleccionProveedor(dedonde,idproveedor){
	console.log('Estoy en seleccionar nuevo proveedor para producto:'+producto.idArticulo);
	var parametros = {
		"pulsado" 		: 'obtenerCostesProveedor',
		"idProveedor"	: idproveedor,
		"idProducto"	: producto.idArticulo
	}
	$.ajax({
		data 		: parametros,
		url 		: 'tareas.php',
		type 		: 'post',
		beforeSend	:function () {
		console.log('*********  Obtener datos de proveedor y coste  **************');
		},
		success    :  function (response) {
				console.log('Respuesta de eObtener datos de proveedor y coste ');
				// Cerramos modal..
				cerrarPopUp();
				var resultado = $.parseJSON(response);
				if (resultado.error){
					alert (' Hubo un error al obtener los datos del proveedor ');
				} else {
					var nuevo_proveedor = resultado.htmlFilaProveedor;
					$("#tproveedor").prepend(nuevo_proveedor);
				} 
		}	
	});
}

function selecionarItemProducto(id, dedonde="", seleccionar=""){
	// @ Objetivo:
	// 		Al seleccionar un check comprueba si existes productos_seleccionado de session.
	//	si existe lo elimina y si no lo añade.
	//  [NOTA] : Se utiliza en varias vistas (listadoproductos y listado etiquetas
	// @ Parametros:
	// 		id -> (int) Id producto
	//		dedonde-> la vista Listaproductos o Lista etiquetas.
	// @ Devuelve:
	// 		El numero de productos que tiene seleccionados
	// En la vista LISTAETIQUETAS sino quedan productos seleccionado , lleva LISTAPRODUCTOS.
	var parametros = {
		"pulsado"    	: 'productosSesion',
		"id"			:id,
        "seleccionar": seleccionar
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
		console.log('********* Añado o Elimino de producto_seleccionado a session  **************');
		},
		success    :  function (response) {
				console.log('Respuesta de añadir o eliminad productos seleccionados');
				var resultado = $.parseJSON(response);
				if(resultado.Nitems===0){
					if(dedonde=="listaProductos"){
						$(".productos_seleccionados").css("display", "none");
						location.href="ListaProductos.php";
					}
                }else{
					if(dedonde=="listaProductos"){
						$(".productos_seleccionados").css("display", "block");
						$(".textoCantidad").html(resultado.Nitems);
					}else{
                        if(seleccionar==""){
                            location.href=dedonde+".php";
                        }
					}
				}
                $("#checkSeleccion").prop( "checked", false );
        }
	});
}

function eliminarSeleccionProductos(){
	// @ Objetivo :
	// Eliminar todos los productos seleccionados. ( al pulsar ELiminar productos).
	console.log(configuracion);
		var parametros = {
		"pulsado"    	: 'eliminarSeleccion'
		};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
		console.log('*********  Eliminar seleccion de productos  **************');
		},
		success    :  function (response) {
				console.log('Respuesta de eliminar seleccion de productos ');
				// La configuracion la cambiamos ya que si esta como si filtrar , ya no .
				configuracion.filtro.valor='No';
				AjaxGuardarConfiguracion();
				location.href="ListaProductos.php";
        }
	});
}

function UnProductoClick(id){
	// @ Objetivo:
	// Hizo click en id o Nombre de producto, por lo que lo mostramos.
	window.location.href = './producto.php?id='+id;
}

function filtrarSeleccionProductos(){
	// @Objetivo:
	// Hizo click en filtrar productos seleccionados por lo que 
	configuracion.filtro.valor='Si';
	AjaxGuardarConfiguracion();
	location.href="ListaProductos.php";
}

function mover_up(fila,prefijo){
	var d_focus = prefijo+fila;
	ponerSelect(d_focus);
}

function mover_down(fila,prefijo){
	var d_focus = prefijo+fila;
	ponerSelect(d_focus);
}

function ponerSelect (destino_focus){
	// @ Objetivo:
	// 	Poner focus a donde nos indique el parametro, que debe ser id queremos apuntar.
	setTimeout(function() {   //pongo un tiempo de focus ya que sino no funciona correctamente
		jQuery('#'+destino_focus.toString()).select(); 
	}, 50); 
}

function comprobarReferencia(){
		var referencia=$("#referencia").val();
		var parametros = {
		"pulsado"    	: 'comprobarReferencia',
		"referencia"	: referencia
		};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
        type       : 'post',
		beforeSend : function () {
		console.log('*********  Envio para comprobar la referencia escrita en el producto  **************');
		},
		success    :  function (response) {
				console.log('Respuesta de comprobar la referencia escrita en el producto ');
				var resultado = $.parseJSON(response);
				if(resultado.error){
					alert("Error de SQL: "+resultado.error+" "+resultado.consulta);
				}else{
					if(resultado!=""){
						alert("Ojo Esa referencia de producto ya está registrada");
                    }
				}
		}	
	});
}

function RegularizarStock(idarticulo) {
    var parametros = {
		"pulsado"    	: 'datosRegularizar',
		"idarticulo"	: idarticulo
		};
    $.ajax({
		data       : parametros,
		url        : 'tareas.php',
        type       : 'post',
		beforeSend : function () {
		console.log('*********  Envio para obtener html para modal de regularizacion  **************');
		},
        success    :  function (response) {
				console.log('Respuesta de obtner html formulario de regularizacion ');
				var resultado = JSON.parse(response);
				if(resultado.error){
					alert("Error fue imposible obtener html para mostral modal");
				}else{
                    var titulo = 'Regularizacion de Stock ';
                    abrirModal(titulo,resultado.html);
				}
		}	
    });
}

function grabarRegularizacion() {
    // Cerrar modal
    var parametros = {
		"pulsado"    	: 'grabarRegularizacion',
		"idarticulo"	: $('#articuloid').val(),
        "stockReal"     : $('#stockcolocar').val()
		};
    $.ajax({
		data       : parametros,
		url        : 'tareas.php',
        type       : 'post',
		beforeSend : function () {
            console.log('*********  Envio para grabar regularizacion  **************');
		},
        success    :  function (response) {
			console.log('Respuesta de comprobar la referencia escrita en el producto ');
			var resultado = JSON.parse(response);
			if(resultado.error){
				alert("Error al grabar regularizacion");
			}else{
                var titulo = 'Respuesta grabar regularizacion';
                abrirModal(titulo,resultado.html);
                // Al cerrar ventana, recargamos.
                $('#ventanaModal').on('hidden.bs.modal', function (e) {
                      location.reload();
                    })
			}
		}
    });
}

function modalFamiliaProducto(idProducto=""){
    // @ Objetivo:
    // Abril modal con combox autocomplete para seleccionar familia y luego poder guardar esa familia
    // en producto.
    // @ parametros:
    // Si viene vacio, luego al pulsar guardar, va intentar añadir la familia a todos los producto que tengamos
    // seleccionado en Session.
    var parametros = {
        pulsado: 'modalFamiliaProducto',
        idProducto: idProducto
    }
    $.ajax({
		data       : parametros,
		url        : 'tareas.php',
        type       : 'post',
		beforeSend : function () {
		console.log('********* envio para mostrar el modal de añadir familia **************');
		},
		success    :  function (response) {
				console.log('Respuesta de mostrar modal de añadir producto a familia ');
				var resultado = $.parseJSON(response);
				var titulo = 'Añadir familia '+idProducto;
                abrirModal(titulo,resultado.html);
				setTimeout(function(){
                        $( ".custom-combobox-input" ).focus();
                       
                },3000);
		}	
	});
}
function modalEstadoProductos(){
     var parametros = {
        pulsado: 'modalEstadoProductos'
    }
      $.ajax({
		data       : parametros,
		url        : 'tareas.php',
        type       : 'post',
		beforeSend : function () {
		console.log('********* envio para mostrar el modal de modificar estado productos **************');
		},
		success    :  function (response) {
				console.log('Respuesta de mostrar modal de modificar estado productos ');
				var resultado = $.parseJSON(response);
				var titulo = 'Modificar Producto ';
                abrirModal(titulo,resultado.html);
                $( ".custom-combobox-input" ).focus();
		}	
	});
}
  
function modificarEstadoProductos(estado, productos){
    var parametros = {
        pulsado: 'cambiarEstadoProductos',
        estado: estado,
        productos:productos
    }
    $.ajax({
		data       : parametros,
		url        : 'tareas.php',
        type       : 'post',
		beforeSend : function () {
		console.log('********* envio para cambiar el estado a los productos de sesion **************');
		},
		success    :  function (response) {
				console.log('Respuesta de cambiar el estado a los productos de sesion ');
				var resultado = $.parseJSON(response);
				console.log(resultado);
                if(resultado['consulta']['Consulta']['error']){
                    alert(resultado['consulta']['Consulta']['consulta']);
                }else{
                    cerrarPopUp();
                    location.reload(true);
                }
		}	
	});
}

function guardarProductoFamilia(idfamilia, idProducto){
    var parametros = {
        pulsado: 'guardarFamiliaProductos',
        idfamilia:idfamilia,
        idProducto:idProducto
    }
    $.ajax({
		data       : parametros,
		url        : 'tareas.php',
        type       : 'post',
		beforeSend : function () {
		console.log('********* envio para guardar el registro de productos familia **************');
		},
		success    :  function (response) {
				console.log('Respuesta de guardar el registro de productos familia');
                var resultado = $.parseJSON(response);
                if(idProducto==0){
                    if(resultado.productosEnFamilia.length>0){
                       alert("Producto que YA ESTABAN : "+JSON.stringify(resultado.productosEnFamilia));
                    }
                    if(resultado.error){
                        alert(resultado.error);
                    }
                    alert("Productos guardados en familia: "+resultado.contadorProductos );
                    cerrarPopUp();
                    // No hace falta que utilicemos evento modal para recargar.
                    location.reload();
                }else{
                    if(resultado.error==1){
                        alert("No puedes añadir esa familia al producto ya que ya está añadida");
                    }else{
                          cerrarPopUp();
                          var nuevafila = resultado['html'];
                        $("#tfamilias").prepend(nuevafila);
                    }
                }
		}	
	});
}
function buscarProductosFamilia(idFamilia){
   
        var parametros = {
            pulsado: 'buscarProductosDeFamilia',
            idfamilia:idFamilia
        }
        $.ajax({
            data       : parametros,
            url        : 'tareas.php',
            type       : 'post',
            beforeSend : function () {
            console.log('********* envio para buscar los productos de las familias **************');
            },
            success    :  function (response) {
                    console.log('Respuesta de buscar productos de la familia');
                    var resultado = $.parseJSON(response);
                    productos=resultado['Productos'];
                       for(i=0;i<productos.length; i++){
                           selecionarItemProducto(productos[i], "listaProductos");
                           $("#botonEnviar").hide();
                       }
            }	
        });
}

function buscarProductosProveedor(idProveedor){
      var parametros = {
            pulsado: 'buscarProductosProveedor',
            idProveedor:idProveedor
        }
        $.ajax({
            data       : parametros,
            url        : 'tareas.php',
            type       : 'post',
            beforeSend : function () {
            console.log('********* envio para buscar los productos de un proveedor **************');
            },
            success    :  function (response) {
                    console.log('Respuesta de buscar productos de un proveedor');
                    var resultado = $.parseJSON(response);
                    productos=resultado['Productos'];
                       for(i=0;i<productos.length; i++){
                           selecionarItemProducto(productos[i], "listaProductos");
                       }
            }
        });
}

function EliminarHistorico(idHistorico, e){
   var mensaje = confirm("¿Estás seguro que quieres eliminar este registro de historico?");
	if (mensaje) {
        var parametros = {
            pulsado: 'eliminarHistorico',
            idHistorico:idHistorico
        }
        $.ajax({
                data       : parametros,
                url        : 'tareas.php',
                type       : 'post',
                beforeSend : function () {
                   console.log('********* eliminar registro indicado de historico precio **************');
                },
                success    :  function (response)
                {
                   console.log('Respuesta de eliminar historico precio');
                   
                   var resultado = $.parseJSON(response);
                   console.log (resultado);
                   //QUEDA ELIMINAR LINEA
                   if(resultado.error==0){
                       alert("Error de sql: "+resultado.consulta);
                   }else{
                        // Codigo repetido que se puede poner en una funcion
                        var padre=e.parentNode; 
                        var abuelo=padre.parentNode; 
                        var bisa=abuelo.parentNode; 
                        bisa.removeChild(abuelo);
                   }
                }
            });
    }
}
function EliminarRefProveedor(e){
    // Informamos de los que vamos hacer    
    var opcion =confirm("Vas eliminar la referencia del proveedor "+e.id.substring(16)+" del producto con id:"+producto.idArticulo);
    if (opcion == true) {
        // Solo ejecutamos si el usuario pulsa aceptar
        var parametros = {
            pulsado: 'eliminarRefProveedor',
            idProveedor:e.id.substring(16),
            idArticulo:producto.idArticulo
        }
        $.ajax({
            data       : parametros,
            url        : 'tareas.php',
            type       : 'post',
            beforeSend : function () {
            console.log('********* Eliminar referencia de un proveedor en la tabla articulosProveedores **************');
            },
            success    :  function (response) {
               console.log('Respuesta de eliminar referencia proveedor de un articulo');
               var resultado = $.parseJSON(response);
               console.log (resultado);
               //QUEDA ELIMINAR LINEA
               if(resultado.error==0){
                   alert("Error de sql: "+resultado.consulta);
               }else{
                    // Codigo repetido que es puede poner en una funcion
                    var padre=e.parentNode; 
                    var abuelo=padre.parentNode; 
                    var bisa=abuelo.parentNode; 
                    bisa.removeChild(abuelo);
               }
            }
        });
	} 
}

function EliminarReferenciaTienda(idCruce,e){
    var mensaje = confirm("¿Estás seguro que quieres eliminar este la relacion de este producto entre  esta tienda?");
	if (mensaje) {
    var parametros = {
        pulsado: 'eliminarReferenciaTienda',
        idCruce:idCruce
    }
     $.ajax({
            data       : parametros,
            url        : 'tareas.php',
            type       : 'post',
            beforeSend : function () {
            console.log('********* eliminar registro indicado de historico precio **************');
            },
            success    :  function (response) {
                    console.log('Respuesta de eliminar historico precio');
                    var resultado = $.parseJSON(response);
                   //QUEDA ELIMINAR LINEA
                   if(resultado.error==0){
                       alert("Error de sql: "+resultado.consulta);
                   }else{
                        // Codigo repetido que es puede poner en una funcion
                        var padre=e.parentNode; 
                        var abuelo=padre.parentNode; 
                        var bisa=abuelo.parentNode; 
                        bisa.removeChild(abuelo);
                   }
            }
        });
    }
}

function eliminarProductos(idTiendaWeb=0){
    // @ Objetivo:
    // Eliminar los productos seleccionados.
     var mensaje = confirm("¿Estás seguro que quieres eliminar los productos seleccionado?");
	if (mensaje) {
        // Muestra icono de mostrar rueda giratoria.
        $('.loader').show();
         var parametros = {
             idTiendaWeb: idTiendaWeb,
            pulsado: 'eliminarProductos'
        }
          $.ajax({
            data       : parametros,
            url        : 'tareas.php',
            type       : 'post',
            beforeSend : function () {
                console.log('********* eliminar productos **************');
            },
            success    :  function (response) {
                    console.log('Respuesta de eliminar productos');
                   
                    var resultado = $.parseJSON(response);
                    // Oculta icono de mostrar rueda giratoria.
                    $('.loader').hide();
                    titulo = 'Resultado de eliminar productos';
                    abrirModal(titulo,resultado.html);
                    // En https://getbootstrap.com/docs/4.0/components/modal/#events
                    // indica que este evento se activa cuando el modal ha terminado de ocultarse al usuario
                    $('#ventanaModal').on('hidden.bs.modal', function (e) {
                      location.reload();
                    })
            }
        });
    }
}

function obtenerEstadoProductoWeb(ids_productos,id_tiendaWeb){
    // Objetivo es obtener el estado de los productos que enviemos a la web.
    // @ Parametros:
    //      ids_productos = (array) ids de la los productos de tpv.
    //      id_web = (int) con el id de la tienda web.
	var parametros = {
		"pulsado"       : 'obtenerEstadoProductoWeb',
		"ids_productos" : ids_productos,
        "id_tiendaWeb"  : id_tiendaWeb
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Obteniendo Estado de productos de la web  ****************');
		},
		success    :  function (response) {
			console.log('Respuesta de Obtener Estado de productos de la web');
			var resultado =  $.parseJSON(response);
			resultado.forEach(function(producto) {
                // Los estado 0 son sin publicar.
                if (producto.estado === "0"){
                    $("#idProducto_estadoWeb_"+producto.idArticulo).addClass( "icono_web despublicado" );
                    //~ console.log(producto.idArticulo);
                }
            });
		}
	});
}

function seleccionarTodo(){
    console.log("entre en seleccionar todo");
   for (i=1;i<41;i++){
       if($("#checkUsu"+i).val()>0){
            if( $("#checkUsuTodos").prop('checked') ) {
                $("#checkUsu"+i).prop( "checked", true );
                selecionarItemProducto($("#checkUsu"+i).val(), "listaProductos", "seleccionar");
            }else{
                selecionarItemProducto($("#checkUsu"+i).val(), "listaProductos", "NoSeleccionar");
                $("#checkUsu"+i).prop( "checked", false );
            }
        }
   }
} 

function seleccionProductos(){
    if( $("#checkSeleccion").prop('checked') ) {
        filtrarSeleccionProductos();
        $("#checkSeleccion").prop( "checked", true );
    }else{
         busquedaSinCheck();
        //~ eliminarSeleccionProductos();
        $("#checkSeleccion").prop( "checked", false );
    }
}

function busquedaSinCheck(){
    configuracion.filtro.valor='No';
    AjaxGuardarConfiguracion();
    location.href="ListaProductos.php";
}

function obtenerFechas(){
    // Objetivo :
    // Obtener las fechas de inicio y final que hay en cajas de las vistas mayor
    // Devuelve fechas en array
    if ( $("#fecha_inicio").val() > $("#fecha_final").val()){
        alert("La fecha inicio no puede ser mayor que la fecha final");
        return;
    }
    Fechas = [ $("#fecha_inicio").val(), $("#fecha_final").val()];
    console.log(Fechas);
    return Fechas;

}

function redirecionarMayor(idArticulo,adonde){
    // Objetivo:
    // El objetivo es redireccionar a la vista de Mayor o generar el informe
    // Obtenemos Fechas de cajas:
    Fechas = obtenerFechas();
    if (adonde === 'DetalleMayor'){
        var href ="./DetalleMayor.php?idArticulo="+idArticulo+"&fecha_inicial="+Fechas[0]+"&fecha_final="+Fechas[1];
        window.open(href,'_blank')

    }
}
