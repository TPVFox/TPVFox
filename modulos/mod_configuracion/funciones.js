function abrirmodal(id, dedonde){
	var parametros = {
		"pulsado"    : 'abrirModalModificar',
		"dedonde" : dedonde,
		"id":id
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Abrir el modal para modificar una tabla   ***********');
		},
		success    :  function (response) {
			console.log('Respuesta de abrir el modal de modificar');
			var resultado =  $.parseJSON(response);
			console.log(resultado);
			titulo="Modificar Elemento de tabla "+dedonde;
			html=resultado.html;
			abrirModal(titulo, html);
		}
	});
}
