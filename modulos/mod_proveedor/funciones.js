
function metodoClick(pulsado){
	console.log("Inicimos switch de control pulsar");
	switch(pulsado) {
		case 'VerProveedor':
			console.log('Entro en proveedor ver');
			// Cargamos variable global ar checkID = [];
			VerUsuariosSeleccionado ();
			if (checkID.length >1 || checkID.length=== 0) {
				alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			}
			// Ahora redireccionamos 
			// recambi.php?id=id
				
			window.location.href = './proveedor.php?id='+checkID[0];
			
			
			break;
		
		case 'AgregarProveedor':
			console.log('entro en agregar proveedor');
			window.location.href = './proveedor.php';
			
			break;
		
		
		
	 }
} 





