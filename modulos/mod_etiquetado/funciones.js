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
}
function controladorAcciones(caja, accion, tecla){
	switch(accion) {
		case 'RepetirProducto':
			console.log('Entre en repetir producto');
			repetirProducto(caja.darValor());
		break;
		case 'BuscarProducto':
			console.log('Entre en el case de buscar producto');
			console.log(caja.darValor());
			buscarProducto(caja.darValor(), caja.id_input);
		break;
	}
}

function repetirProducto(unidades){
	//@OBjetivo: repetir el producto cuantas veces sea indicado
	//NOta: controlar si ya tiene productos introducidos
	console.log('Entre en repetir producto');
	var parametros ={
		'pulsado':'repetirProductos',
		'unidades':unidades,
		'idProducto':cabecera.idProducto,
		'idTienda': cabecera.idTienda,
		'fechaCad':cabecera.fechaCad,
		'productos':productos
		
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
				
				
			}
		});
	
}
function buscarProducto(valor, caja){
	
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
				
			}
		});
}
