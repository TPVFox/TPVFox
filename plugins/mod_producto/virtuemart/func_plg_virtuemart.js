function modificarProductoWeb(){
    console.log("entre en modificar producto web ");
    var datos={
        'estado':       $('#estadosWeb').val(),
        'referencia':   $('#referenciaWeb').val(),
        'nombre':       $('#nombreWeb').val(),
        'codBarras':    $('#codBarrasWeb').val(),
        'precioSiva':   $('#precioSivaWeb').val(),
        'iva':          $('#ivasWeb').val(),
        'id':           $('#idWeb').html()
    };
    
    console.log(datos);
     var parametros = {
		"pulsado"    	: 'modificarDatosWeb',
		"datos"	: JSON.stringify(datos)
		};
     $.ajax({
		data       : parametros,
		url        : ruta_plg_virtuemart+'tareas_virtuemart.php',
        type       : 'post',
		beforeSend : function () {
		console.log('********* Envio los datos para modificar el producto en la web  **************');
		},
		success    :  function (response) {
				console.log('Respuesta de modificar los datos de la web  ');
				var resultado = $.parseJSON(response);
                console.log(resultado);
				 
		}	
        });
}
function ModalNotificacion(numLinea){
    console.log("entre en enviar modal notificacion");
   
    var datos={
        'nombreProducto': $('#nombreWeb').val(),
        'id':             $('#idWeb').html(),
        'correo':         $('#mail_'+numLinea).html(),
        'nombreUsuario':  $('#nombre_'+numLinea).html()
        
    };
     var parametros = {
		"pulsado"    	: 'mostrarModalNotificacion',
		"datos"	: datos
		};
     $.ajax({
		data       : parametros,
		url        : ruta_plg_virtuemart+'tareas_virtuemart.php',
        type       : 'post',
		beforeSend : function () {
		console.log('********* Envio los datos para mostrar el modal de notificaciones  **************');
		},
		success    :  function (response) {
				console.log('Respuesta de mostrar el modal de notificaciones  ');
				var resultado = $.parseJSON(response);
                console.log(resultado);
                var titulo="Enviar correo de Notificacion";
                abrirModal(titulo, resultado.html);
                
				 
		}	
        });
   
    
    
}
function enviarCorreoNotificacion(){
    console.log("entre en enviar correo de notificacion");
    var datos={
        'email':$('#email').val(),
        'asunto':$('#asunto').val(),
        'mensaje':$('#mensaje').val(),
        'idProducto':$('#idProducto').html()
    };
      var parametros = {
		"pulsado"    	: 'enviarCorreoNotificacion',
		"datos"	: datos
		};
         $.ajax({
		data       : parametros,
		url        : ruta_plg_virtuemart+'tareas_virtuemart.php',
        type       : 'post',
		beforeSend : function () {
		console.log('********* Envio los datos para mostrar el modal de notificaciones  **************');
		},
		success    :  function (response) {
				console.log('Respuesta de mostrar el modal de notificaciones  ');
				var resultado = $.parseJSON(response);
                console.log(resultado);
               
                cerrarModal();
                
				 
		}	
        });
}
