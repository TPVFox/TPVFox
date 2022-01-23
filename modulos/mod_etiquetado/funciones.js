function metodoClick(pulsado,adonde){
	switch(pulsado) {
		case 'Ver':
			console.log('Entro en Ver');
			VerIdSeleccionado ();
			if (checkID.length >1 || checkID.length=== 0) {
				alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			}
			// Ahora redireccionamos 
			window.location.href = './'+adonde+'.php?id='+checkID[0];
		break;
		case 'Imprimir':
			VerIdSeleccionado ();
			contarEtiquetasLote(checkID);
			//~ imprimirEtiquetas(checkID);
		break;
		case 'Agregar':
			console.log('entro en agregar lote');
			window.location.href = './etiquetaCodBarras.php';
			
		break;
	}
}
function imprimirEtiquetas(lotes){
	//@OBjetivo:
	//imprimir las etiquetas
	//@Retorno:
	//abre una nueva pestaña con el resultado
	var parametros ={
		'pulsado':'imprimirEtiquetas',
		'lotes':lotes
	};
		$.ajax({
			data       : parametros,
			url        : 'tareas.php',
			type       : 'post',
			beforeSend : function () {
				console.log('******** imprimir etiquetas JS****************');
			},
			success    :  function (response) {
				console.log('Llegue devuelta imprimir etiquetas JS');
				var resultado =  $.parseJSON(response);
				console.log(resultado);
				 window.open(resultado);
			
			}
		});
}
function modificarTipo(tipo){
	//@Objetivo: cada vez que seleccionamos en el select un tipo distinto se modifica el nombre
	//de tipo en la tabla
	console.log(tipo);
	switch(tipo){
			case '0':
				var tipoTabla='Tipo';
			break;
			case '1':
				var tipoTabla='Unidad';
			break;
			case '2':
				var tipoTabla='Peso';
			break;
			default:
				var tipoTabla='Tipo';
			break;
	}
	$('#tipoTabla').html(tipoTabla);
	cabecera.tipo=tipo;
	var bandera=1;
	if(productos.length>0){
		for (i=0;i<productos.length;i++){ 
				modificarCodigoBarras(i, bandera);
		}
	}
}
function controladorAcciones(caja, accion, tecla){
	switch(accion) {
		case 'RepetirProducto':
			console.log('Entre en repetir producto');
			if(caja.darValor()>0){
				//~ var select=$("#tipo option:selected").val();
				var select=cabecera.tipo;
				if(select==1 || select==2){
					repetirProducto(caja.darValor(), select);
				}else{
					alert('No has seleccionado TIPO');
				}
				
			}else{
				alert("No has escrito ninguna cantidad");
			}
			
		break;
		case 'BuscarProducto':
			console.log('Entre en el case de buscar producto');
			console.log(caja.darValor());
			buscarProducto(caja.darValor(), caja.id_input);
		break;
		case 'modificarNombreProducto':
			console.log('entre en modificarNombreProducto');
			var nfila=caja.fila-1
			console.log(caja.fila);
			if(nfila>=0){
				productos[nfila]['nombre']=caja.darValor();
				console.log(productos[nfila]['nombre']);
				addEtiquetadoTemporal();
				id='nombre_'+caja.fila;
				destacarCambioCaja(id);
				$( "#peso_"+caja.fila ).select();
				
			}else{
				alert("Error al seleccionar producto");
			}
		break;
		case 'modificarPesoProducto':
			console.log('entre en modificarPesoProductos');
			var nfila=caja.fila-1;
			console.log(nfila);
			var val=validarCaja(caja.darValor());
			if(val!=false){
				if(nfila>=0){
					productos[nfila]['peso']=caja.darValor();
					modificarCodigoBarras(nfila);
					
				}else{
					alert("Error al seleccionar producto");
				}
			}else{
				alert('Error en el formato del número');
				 $( "#"+caja.id_input ).select();
			}
			
		break;
		case 'modificarNumeroAlbaranProducto':
			var nfila=caja.fila-1
			if(nfila>=0){
				productos[nfila]['NumAlb']=caja.darValor();
				addEtiquetadoTemporal();
			}else{
				alert("Error al seleccionar producto");
			}
		break;
		case 'GuardarNumAlb':
			if(caja.darValor()>0){
				cabecera.numAlb=caja.darValor();
			}
			if(cabecera.idTemporal>0){
				addEtiquetadoTemporal();
			}
		break;
		case 'GuardarFechaCad':
			cabecera.fechaCad=caja.darValor();
		break;
		case  'mover_down':
			console.log("EStoy en mover dow");
			var nfila=parseInt(caja.fila)+1
			
			console.log(caja);
			id=caja.parametros.prefijo+nfila;
			console.log(id);
			$( "#"+id ).select();
		break;
		case 'mover_up':
			console.log("EStoy en mover up");
			var nfila=parseInt(caja.fila)-1
			
			console.log(caja);
			id=caja.parametros.prefijo+nfila;
			console.log(id);
			$( "#"+id ).select();
		break;
	}
}
function modificarCodigoBarras(nfila, bandera=""){
	//@OBjetivo:
	//MOdificar el código de barras
	//Si se modifican los datos del producto se tiene que modificar el código de barras de ese producto
	var parametros ={
		'pulsado':'modificarCodigoBarras',
		'tipo':cabecera.tipo,
		'producto':productos[nfila]
		
	};
		$.ajax({
			data       : parametros,
			url        : 'tareas.php',
			type       : 'post',
			beforeSend : function () {
				console.log('******** ModificarCodigoBarras JS****************');
			},
			success    :  function (response) {
				console.log('Llegue devuelta ModificarCodigoBarras JS');
				var resultado =  $.parseJSON(response); 
				console.log(resultado);
				productos[nfila]['codBarras']=resultado.codBarras;
				nfila=nfila+1;
				id='codigoBarras_'+nfila;
				$('#codigoBarras_'+nfila).html(resultado.codBarras);
				addEtiquetadoTemporal();
				if(bandera==""){
					var nfilaSig=nfila+1;
					idOrig='peso_'+nfila;
					$( "#peso_"+nfilaSig ).select();
					destacarCambioCaja(idOrig);
				}
				destacarCambioCaja(id);
					
			}
		});
}
function repetirProducto(unidades, tipo){
	//@OBjetivo: repetir el producto cuantas veces sea indicado
	//NOta: controlar si ya tiene productos introducidos
	console.log('Entre en repetir producto');
	var parametros ={
		'pulsado':'repetirProductos',
		'unidades':unidades,
		'idProducto':cabecera.idProducto,
		'idTienda': cabecera.idTienda,
		'fechaCad':cabecera.fechaCad,
		'productos':productos,
		'tipo' :	tipo
		
	};
	$.ajax({
			data       : parametros,
			url        : 'tareas.php',
			type       : 'post',
			beforeSend : function () {
				console.log('******** repetir productos JS****************');
			},
			success    :  function (response) {
				console.log('Llegue devuelta repetir productos JS');
				var resultado =  $.parseJSON(response); 
				if(resultado.error){
					alert(resultado.error);
				}else{
					var filasNuevas = resultado['html'];
					$("#tabla").append(filasNuevas);
					console.log(resultado['productos']);
					productosAdd=resultado['productos'];
					for (i=0; i<productosAdd.length; i++){
						var prod = new Object();
                        // Tengo sustituir el nombre , ya que si tiene acento o caracteres extraños no lo pone
                        var a =i+1;
                        var nombre_producto= $('<textarea />').html(productosAdd[i]['nombre']).text();
                        $('#nombre_'+a).val(nombre_producto);
						prod.nombre=nombre_producto;
						prod.peso=productosAdd[i]['peso'];
						prod.precio=productosAdd[i]['precio'];
						prod.Fecha=productosAdd[i]['Fecha'];
						prod.NumAlb=productosAdd[i]['NumAlb'];
						prod.codBarras=productosAdd[i]['codBarras'];
						prod.estado=productosAdd[i]['estado'];
						prod.Nfila=productosAdd[i]['Nfila'];
						prod.crefTienda=productosAdd[i]['crefTienda'];
						productos.push(prod);
					}
					addEtiquetadoTemporal()
				}
				
				
			}
		});
}
function addEtiquetadoTemporal(){
	//@Objetivo:
	//Añadir una etiqueta temporal
	//Generar el registro o modificarlo de una etiqueta temporal
	console.log(productos);
	var parametros ={
		'pulsado'	:'addEtiquetadoTemporal',
		'estado'	: cabecera.estado,
		'idTemporal': cabecera.idTemporal,
		'idReal'	: cabecera.idReal,
		'fechaEnv'	: cabecera.fechaEnv,
		'fechaCad'	: cabecera.fechaCad,
		'idProducto': cabecera.idProducto,
		'idUsuario'	: cabecera.idUsuario,
		'tipo'		: cabecera.tipo,
		'NumAlb'	: cabecera.numAlb,
		'productos'	: productos
	};
	$.ajax({
			data       : parametros,
			url        : 'tareas.php',
			type       : 'post',
			beforeSend : function () {
				console.log('******** addEtiquetadoTemporal JS****************');
			},
			success    :  function (response) {
				console.log('Llegue devuelta  addEtiquetado Temporal JS');
				var resultado =  $.parseJSON(response);
				if (resultado.error){
					alert(resultado.consulta);
				}else{
					if (resultado.existe == 0){
						history.pushState(null,'','?tActual='+resultado.idTemporal);
						cabecera.idTemporal=resultado.idTemporal;
					}
				}
				
			}
		});
	
}
function buscarProducto(valor="", caja=""){
	//@OBjetivo:
	//BUscar los datos del producto
	
	console.log("estoy dento de la función de buscar Producto");
	console.log(valor);
	var parametros ={
		'pulsado'	: 'buscarProducto',
		'valor'		:valor,
		'caja'		:caja,
		'idTienda'	:cabecera.idTienda
	};
	$.ajax({
			data       : parametros,
			url        : 'tareas.php',
			type       : 'post',
			beforeSend : function () {
				console.log('******** buscar producto JS****************');
			},
			success    :  function (response) {
				console.log('Llegue devuelta de buscar producto  JS');
				var resultado =  $.parseJSON(response);
				console.log(resultado);
				if(resultado.error){
					alert("Error:"+resultado.consulta);
				}else{
					if(resultado.Nitem==1){
						cerrarPopUp();
						console.log("sólo hay un resultado");
						cabecera.idProducto=resultado.datos['idArticulo'];
						$('#id_producto').val(resultado.datos['idArticulo']);
                        var nombre_producto= $('<textarea />').html(resultado.datos['articulo_name']).text();
                        console.log('combierto nombre que puede traer html a texto:'+nombre_producto);
						$('#producto').val(nombre_producto);
						$('#producto').prop('disabled', true);
						$('#id_producto').prop('disabled', true);
						$("#buscar").css("display", "none");
						$('#unidades').focus();
						
					}else{
						var titulo = 'Listado De Productos ';
						var HtmlProductos=resultado.html; 
						abrirModal(titulo,HtmlProductos);
						focusAlLanzarModal('cajaBusquedaproductos');
					}
				}
				
			}
		});
}
function after_constructor(padre_caja, event){
	console.log("entre en after_constructor");
	console.log(padre_caja.id_input.indexOf('peso_'));
	if (padre_caja.id_input.indexOf('nombre_') >=-1){
		padre_caja.id_input = event.target.id;
	}
	if (padre_caja.id_input.indexOf('peso_') >=-1){
		padre_caja.id_input = event.target.id;
	}
	if (padre_caja.id_input.indexOf('numAlb_') >=-1){
		padre_caja.id_input = event.target.id;
	}
	return padre_caja;
}
function before_constructor(caja){
	console.log("entre en before_constructor");
	if (caja.id_input.indexOf('nombre_') >-1){
		console.log(' Entro en Before de '+caja.id_input)
		caja.fila = caja.id_input.slice(7);
	}
	if (caja.id_input.indexOf('peso_') >-1){
		console.log(' Entro en Before de '+caja.id_input)
		caja.fila = caja.id_input.slice(5);
	}
	if (caja.id_input.indexOf('numAlb_') >-1){
		console.log(' Entro en 3 Before de '+caja.id_input)
		caja.fila = caja.id_input.slice(7);
	}

	return caja;
}
//~ function mover_down(fila,prefijo){
	//~ console.log('mover down'+fila);

	//~ var d_focus = prefijo+fila;
		//~ if ( document.getElementById(d_focus) ) {
			//~ ponerSelect(d_focus);
		//~ }else{
			//~ //estamos en abrir modal ponemos focus en la 1ª opc despues de buscar algo.. nos movemos con tabulador
			//~ ponerFocus(d_focus);
		//~ }
//~ }
//~ function mover_up(fila,prefijo){
	//~ console.log("entro en mover up");
	//~ console.log(fila);
	
	//~ var d_focus = prefijo+fila;
	
	//~ console.log(d_focus);
	//~ ponerSelect(d_focus);
//~ }
function eliminarFila(linea, dedonde){
	//@Objetivo :
	//Cambiar a estado eliminado , para dar la sensación de que el producto está eliminado
	console.log("Entro en eliminar Filas");
	line = "#Row" + linea;
    var num = findWithAttr(productos, 'Nfila', linea);
    console.log(num);
	productos[num].estado= 'Eliminado';
	$(line).addClass('tachado');
	$(line + "> .eliminar").html('<a onclick="retornarFila('+linea+', '+"'"+dedonde+"'"+');"><span class="glyphicon glyphicon-export"></span></a>');
	 $('#nombre_'+linea).prop("disabled", true);
	 $('#peso_'+linea).prop("disabled", true);
	 $('#numAlb_'+linea).prop("disabled", true);
	 addEtiquetadoTemporal();
}
function retornarFila(linea, dedonde){
	//@Objetivo: cambiar el estado de eliminado a activo
	console.log("Entro en retornar Filas");
	line = "#Row" + linea;
	var num = findWithAttr(productos, 'Nfila', linea);
    console.log(num);
	productos[num].estado= 'Activo';
	$(line).removeClass('tachado');
	$(line + "> .eliminar").html('<a onclick="eliminarFila('+linea+' , '+"'"+dedonde+"'"+');"><span class="glyphicon glyphicon-trash"></span></a>');
	$('#nombre_'+linea).prop("disabled", false);
	 $('#peso_'+linea).prop("disabled", false);
	 $('#numAlb_'+linea).prop("disabled", false);
	 addEtiquetadoTemporal();
}
function validarCaja(valor){
	sep = valor.split(".");
	entero=sep[0];
	decimal=sep[1];
	canEntero=entero.length;
	if(decimal){
		canDecimal=decimal.length;
	}else{
		canDecimal=0;
	}
	
	console.log(canEntero);
	console.log(canDecimal);
	//Si es unidades
	if(cabecera.tipo==1){
		if(canEntero<=2 & canDecimal<=2){
			var validar=true;
		}else{
			var validar=false;
		}
		
	}
	//si es peso
	if(cabecera.tipo==2){
		if(canEntero<=1 & canDecimal<=3){
			var validar=true;
		}else{
			var validar=false;
		}
		
	}
	return validar;
}
function contarEtiquetasLote(lotes){
	//Objetivo:
	//contar cuantas etiquetas se van a imprimir para avisar al usuario en caso de que no rellene una hoja entera mostrar 
	//un alert
	var parametros ={
		'pulsado'	: 'contarEtiquetas',
		'lotes'		:lotes
	};
	$.ajax({
			data       : parametros,
			url        : 'tareas.php',
			type       : 'post',
			beforeSend : function () {
				console.log('******** contar etiquetas JS****************');
			},
			success    :  function (response) {
				console.log('Llegue contar etiquetas JS');
				var resultado =  $.parseJSON(response);
				console.log(resultado);
				if(resultado.etiquetas>16){
					 var opcion = confirm("Te has sobrepasado de las etiquetas por hoja. El formato de impresión puede que no coincida");
				}
				else if(resultado.etiquetas==16){
					 var opcion = confirm("Has seleccionado justo las etiquetas a una página");
				}
				else if(resultado.etiquetas<16){
					var faltan=16-resultado.etiquetas;
					 var opcion = confirm("Te faltan etiquetas "+faltan+" para llegar a 16 (hoja entera)");
				}
				else{
					 var opcion = confirm("No tienes lotes seleccionado");
				}
				if (opcion == true) {
						imprimirEtiquetas(lotes);
				}
			}
		});
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

function findWithAttr(array, attr, value) {
    for(var i = 0; i < (array.length); i += 1) {
        console.log('atributo:'+array[i][attr]);
        console.log(value);
        
        if(array[i][attr] == value) {
             console.log('Entro');
            return i;
        }
    }
    return -1;
}

