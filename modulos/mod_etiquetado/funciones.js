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
		
		case 'Agregar':
			console.log('entro en agregar lote');
			window.location.href = './etiquetaCodBarras.php';
			
		break;
	}
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
}
function controladorAcciones(caja, accion, tecla){
	switch(accion) {
		case 'RepetirProducto':
			console.log('Entre en repetir producto');
			if(caja.darValor()>0){
				var select=$("#tipo option:selected").val();
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
			console.log(nfila);
			if(nfila>=0){
				productos[nfila]['nombre']=caja.darValor();
				console.log(productos[nfila]['nombre']);
				addEtiquetadoTemporal();
			}else{
				alert("Error al seleccionar producto");
			}
		break;
		case 'modificarPesoProducto':
			console.log('entre en modificarPesoProductos');
			var nfila=caja.fila-1
			if(nfila>=0){
				productos[nfila]['peso']=caja.darValor();
				modificarCodigoBarras(nfila);
			}else{
				alert("Error al seleccionar producto");
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
	}
}
function modificarCodigoBarras(nfila){
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
				console.log('******** repetir productos JS****************');
			},
			success    :  function (response) {
				console.log('Llegue devuelta repetir productos JS');
				var resultado =  $.parseJSON(response); 
				productos[nfila]['codBarras']=resultado.codBarras;
				nfila=nfila+1;
				id='#codigoBarras_'+nfila;
				console.log(id);
				$('#codigoBarras_'+nfila).html(resultado.codBarras);
				addEtiquetadoTemporal();
				
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
				var filasNuevas = resultado['html'];
				$("#tabla").append(filasNuevas);
				console.log(resultado['productos']);
				productosAdd=resultado['productos'];
				for (i=0; i<productosAdd.length; i++){
					var prod = new Object();
					prod.nombre=productosAdd[i]['nombre'];
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
		});
}
function addEtiquetadoTemporal(){
	var tipo=$("#tipo option:selected").val();
	var NumAlb=$("#numAlb").val();
	if(NumAlb==""){
		NumAlb=0;
	}
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
		'tipo'		: tipo,
		'NumAlb'	: NumAlb,
		'productos'	: productos
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
				console.log('******** repetir productos JS****************');
			},
			success    :  function (response) {
				console.log('Llegue devuelta de buscar Producto JS');
				var resultado =  $.parseJSON(response);
				console.log(resultado);
				if(resultado.error){
					alert("Error de sql:"+resultado.consulta);
				}else{
					if(resultado.Nitem==1){
						cerrarPopUp();
						console.log("sólo hay un resultado");
						cabecera.idProducto=resultado.datos['idArticulo'];
						$('#id_producto').val(resultado.datos['idArticulo']);
						$('#producto').val(resultado.datos['articulo_name']);
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
function after_constructor(padre_caja,event){
	console.log("entre en after_constructor");
	if (padre_caja.id_input.indexOf('nombre_') >=-1){
		padre_caja.id_input = event.originalTarget.id;
	}
	if (padre_caja.id_input.indexOf('peso_') >=-1){
		padre_caja.id_input = event.originalTarget.id;
	}
	if (padre_caja.id_input.indexOf('numAlb_') >=-1){
		padre_caja.id_input = event.originalTarget.id;
	}
	return padre_caja;
}
function before_constructor(caja){
	console.log("entre en before_constructor");
	if (caja.id_input.indexOf('nombre_') >=-1){
		console.log(' Entro en Before de '+caja.id_input)
		caja.fila = caja.id_input.slice(7);
	}
	if (caja.id_input.indexOf('peso_') >=-1){
		console.log(' Entro en Before de '+caja.id_input)
		caja.fila = caja.id_input.slice(5);
	}
	if (caja.id_input.indexOf('numAlb_') >=-1){
		console.log(' Entro en Before de '+caja.id_input)
		caja.fila = caja.id_input.slice(7);
	}
	return caja;
}
function mover_down(fila,prefijo){
	console.log('mover down'+fila);

	var d_focus = prefijo+fila;
		if ( document.getElementById(d_focus) ) {
			ponerSelect(d_focus);
		}else{
			//estamos en abrir modal ponemos focus en la 1ª opc despues de buscar algo.. nos movemos con tabulador
			ponerFocus(d_focus);
		}
}
function mover_up(fila,prefijo){
	console.log("entro en mover up");
	console.log(fila);
	
	var d_focus = prefijo+fila;
	
	console.log(d_focus);
	ponerSelect(d_focus);
}

