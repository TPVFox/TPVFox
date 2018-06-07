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
	
	var id=($("#id").val());
	var descripcion=($("#descripcion").val());
	if(dedonde=="iva"){
		var iva=($("#iva").val());
		var recargo=($("#recargo").val());
	}else{
		var iva="";
		var recargo="";
	}
	var parametros = {
		"pulsado"    : 'ModificarTabla',
		"dedonde" : dedonde,
		"iva":iva,
		"recargo":recargo,
		"id":id,
		"descripcion":descripcion
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  MOdificar tablas de configuración   ***********');
		},
		success    :  function (response) {
			console.log('Respuesta  MOdificar tablas de configuración');
			var resultado =  $.parseJSON(response);
			console.log(resultado);
			if(resultado.error==0){
				//~ cerrarPopUp();
				location.reload(true);
			}else{
				alert(resultado.consulta);
			}
			
		}
	});
	
}
