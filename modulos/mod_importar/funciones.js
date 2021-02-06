
//recogemos valor de la caja de busqueda que tenemos en Listado tickets o productos
function metodoClick(accion,valor=0){
	if (accion === 'EliminarTabla'){
        alert('Vamos eliminar tabla');
        eliminarTabla();
    }
    if (accion === 'EliminarFichero'){
        alert('Vamos eliminar fichero');
    }
    if (accion === 'EliminarUltimoRegistro'){
        alert('Vamos eliminar ultimo registro');
        eliminarUltimoRegistro(valor);
    }
}

function eliminarTabla(tabla){
    var parametros = {
        "pulsado"    : 'borrar_tabla'
	};
	// -- Enviamos datos por Ajax -- //
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Borramos tabla  ****************');
		},
		success    :  function (response) {
			console.log('Repuesta de borrar tabla');
			var resultado =  $.parseJSON(response);
			console_log(resultado);
		}
	});
}

function eliminarUltimoRegistro(id){
    var parametros = {
        "pulsado"    : 'EliminarUltimoRegistro',
        "id_ultimo_registro" : id
	};
	// -- Enviamos datos por Ajax -- //
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Borramos tabla  ****************');
		},
		success    :  function (response) {
			console.log('Repuesta de borrar tabla');
			var resultado =  $.parseJSON(response);
			console_log(resultado);
		}
	});
}
