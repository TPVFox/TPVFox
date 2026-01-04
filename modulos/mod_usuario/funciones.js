function metodoClick(pulsado){
	console.log("Inicimos switch de control pulsar");
	switch(pulsado) {
		case 'VerUsuario':
			console.log('Entro en VerUsuario');
			// Cargamos variable global ar checkID = [];
			VerIdSeleccionado ();
			if (checkID.length >1 || checkID.length=== 0) {
				alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			}
			// Ahora redireccionamos 
			// recambi.php?id=id
				
			window.location.href = './usuario.php?id='+checkID[0];
			break;
		
		case 'AgregarUsuario':
			console.log('entro en agregarUsuario');
			window.location.href = './usuario.php';
			
			break;
		case 'AccionesMultiples':
			console.log('entro en AccionesMultiples');
			//abrir modal mediante el la función AbrirModal
			VerIdSeleccionado ();
			var parametros = {
				"pulsado"    		: 'accionesMultiplesUsuarios',
				"idsSeleccionados"	: checkID
			};
			$.ajax({
				data	   : parametros,
				type	   : 'post',
				url 	   : 'tareas.php',
				beforeSend : function () {
					console.log('*********  Acciones Múltiples Usuarios   **************');
				},
				success    :  function (response) {
					var resultado =  $.parseJSON(response);
					console.log('Respuesta Acciones Múltiples Usuarios ');
					abrirModal('Acciones Múltiples Usuarios',resultado.html);
				}	
			});
			break;
	 }
} 
function eliminarConfiguracionModulo(idUsuario, modulo){
	var mensaje = confirm("¿Estás seguro que quieres eliminar la configuración del usuario?");
	if (mensaje) {
			var parametros = {
		"pulsado"    		: 'eliminarConfigModulo',
		"idUsuario"			:idUsuario,
		"modulo"				:modulo
		
		};
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
		console.log('*********  Eliminar configuración del modulo   **************');
		},
		success    :  function (response) {
				console.log('Respuesta Eliminar configuración del modulo ');
				 var resultado = $.parseJSON(response);
				 if(resultado.error){
					 alert(resultado.consulta);
				 }else{
					location.reload(true);
				 }
				 
				 
		}	
	});
		
		
	}
}

function copiarPermisosUsuario(){
 
    
    usuarioNuevo= $('#usuario').val();
   
    var parametros = {
		"pulsado"    		: 'copiarPermisosUsuario',
		"usuarioNuevo"			:usuarioNuevo
		
		
		};
    $.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
		console.log('********* Copiar los permisos de un usuario a otro  **************');
		},
		success    :  function (response) {
				console.log('Respuesta Copiar los permisos de un usuario a otro ');
				 var resultado = $.parseJSON(response);
				
                for(i=0;i<resultado.permisosUsuario.resultado.length;i++){
                  
                     permiso=resultado.permisosUsuario.resultado[i]['permiso'];
                   
                     if(permiso==1){
                         $('.permiso_'+i).prop( "checked", true );
                     }else{
                         $('.permiso_'+i).prop( "checked", false );
                     }
                }
				 
		}	
	});
}
/**
 * Funciones para submenuAccionesMultiplesUsuarios
 * - cambiarAnoUsuarios
 * - inactivarUsuarios
 * - eliminarUsuarios
 * - asignarGrupoUsuarios
 */
function cambiarAnoUsuarios(){
	limpiarSubmenuAccionesMultiplesUsuarios();
	var parametros = {
		"pulsado"    		: 'cambiarAnoUsuarios',
		"idsSeleccionados"	: checkID
	};
	$.ajax({
		data	   : parametros,
		type	   : 'post',
		url 	   : 'tareas.php',
		beforeSend : function () {
			console.log('*********  Cambiar Año Usuarios   **************');
		},
		success    :  function (response) {
			var resultado =  $.parseJSON(response);
			console.log('Respuesta Cambiar Año Usuarios ');
			$('#submenuAccionesMultiplesUsuarios').html(resultado.html);
		}	
	});
}
function confirmarCambiarAnoUsuarios(){
	var nuevaContrasena = $('#nuevaContrasena').val();
	if (nuevaContrasena.trim() === '') {
		alert('Por favor, introduce una nueva contraseña para los usuarios seleccionados.');
		return;
	}
	// si checkID no tiene id 1 (Administrador), añadirlo al inicio del array
	if (!checkID.includes(1)) {
		checkID.unshift(1);
	}
	var parametros = {
		"pulsado"    		: 'confirmarCambiarAnoUsuarios',
		"idsSeleccionados"	: checkID,
		"nuevaContrasena"	: nuevaContrasena
	};
	$.ajax({
		data	   : parametros,
		type	   : 'post',
		url 	   : 'tareas.php',
		beforeSend : function () {
			console.log('*********  Confirmar Cambiar Año Usuarios   **************');
		},
		success    :  function (response) {
			var resultado =  $.parseJSON(response);
			console.log('Respuesta Confirmar Cambiar Año Usuarios ');
			alert(resultado.mensaje);
			location.reload();
		}	
	});
}

function inactivarUsuarios(){
	limpiarSubmenuAccionesMultiplesUsuarios();
	var parametros = {
		"pulsado"    		: 'inactivarUsuarios',
		"idsSeleccionados"	: checkID
	};
	$.ajax({
		data	   : parametros,
		type	   : 'post',
		url 	   : 'tareas.php',
		beforeSend : function () {
			console.log('*********  Inactivar Usuarios   **************');
		},
		success    :  function (response) {
			var resultado =  $.parseJSON(response);
			console.log('Respuesta Inactivar Usuarios ');
			$('#submenuAccionesMultiplesUsuarios').html(resultado.html);
		}	
	});
}
function confirmarInactivarUsuarios(){
	var parametros = {
		"pulsado"    		: 'confirmarInactivarUsuarios',
		"idsSeleccionados"	: checkID
	};
	$.ajax({
		data	   : parametros,
		type	   : 'post',
		url 	   : 'tareas.php',
		beforeSend : function () {
			console.log('*********  Confirmar Inactivar Usuarios   **************');
		},
		success    :  function (response) {
			var resultado =  $.parseJSON(response);
			console.log('Respuesta Confirmar Inactivar Usuarios ');
			alert(resultado.mensaje);
			location.reload();
		}	
	});
}
function eliminarUsuarios(){
	limpiarSubmenuAccionesMultiplesUsuarios();
	var parametros = {
		"pulsado"    		: 'eliminarUsuarios',
		"idsSeleccionados"	: checkID
	};
	$.ajax({
		data	   : parametros,
		type	   : 'post',
		url 	   : 'tareas.php',
		beforeSend : function () {
			console.log('*********  Eliminar Usuarios   **************');
		},
		success    :  function (response) {
			var resultado =  $.parseJSON(response);
			console.log('Respuesta Eliminar Usuarios ');
			$('#submenuAccionesMultiplesUsuarios').html(resultado.html);
		}	
	});
}
function confirmarEliminarUsuarios(){
	var parametros = {
		"pulsado"    		: 'confirmarEliminarUsuarios',
		"idsSeleccionados"	: checkID
	};
	$.ajax({
		data	   : parametros,
		type	   : 'post',
		url 	   : 'tareas.php',
		beforeSend : function () {
			console.log('*********  Confirmar Eliminar Usuarios   **************');
		},
		success    :  function (response) {
			var resultado =  $.parseJSON(response);
			console.log('Respuesta Confirmar Eliminar Usuarios ');
			alert(resultado.mensaje);
			location.reload();
		}	
	});
}
function activarUsuarios(){
	limpiarSubmenuAccionesMultiplesUsuarios();
	var parametros = {
		"pulsado"    		: 'activarUsuarios',
		"idsSeleccionados"	: checkID
	};
	$.ajax({
		data	   : parametros,
		type	   : 'post',
		url 	   : 'tareas.php',
		beforeSend : function () {
			console.log('*********  Activar Usuarios   **************');
		},
		success    :  function (response) {
			var resultado =  $.parseJSON(response);
			console.log('Respuesta Activar Usuarios ');
			$('#submenuAccionesMultiplesUsuarios').html(resultado.html);
		}	
	});
}

function limpiarSubmenuAccionesMultiplesUsuarios(){
	$('#submenuAccionesMultiplesUsuarios').html('');
}




