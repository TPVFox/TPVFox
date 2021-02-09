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





