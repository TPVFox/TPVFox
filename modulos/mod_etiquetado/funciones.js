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
}
