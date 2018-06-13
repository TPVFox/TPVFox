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
		var bandera=1;
		alert("si");
		
	}
}





