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
function controladorAcciones(caja,accion, tecla){
	switch(accion) {
		case 'RepetirProducto':
			console.log('Entre en repetir producto');
			repetirProducto(caja.darValor());
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
				var resultado =  $.parseJSON(response); //Muestra el modal con el resultado html
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
				
			}
		});
}
