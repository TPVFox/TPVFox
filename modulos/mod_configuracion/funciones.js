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
function modificarTabla(dedonde){
	var datos = [];
	datos[0]=($("#id").val());
	datos[1]=($("#descripcion").val());
	if(dedonde=="iva"){
		datos[2]=($("#iva").val());
		datos[3]=($("#recargo").val());
	}
	var parametros = {
		"pulsado"    : 'ModificarTabla',
		"dedonde" : dedonde,
		"datos":datos
	};
	
}
