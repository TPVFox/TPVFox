// Funciones modulo de recambios vista lista recambios.
function VerUsuariosSeleccionado (){
	$(document).ready(function()
	{
		// Array para meter lo id de los checks
		
		// Contamos check están activos.... 
		checkID = [] ; // Reiniciamos varible global.
		var i= 0;
		// Con la funcion each hace bucle todos los que encuentra..
		$(".rowUsuario").each(function(){ 
			i++;
			//todos los que sean de la clase row1
			if($('input[name=checkUsu'+i+']').is(':checked')){
				// cant cuenta los que está seleccionado.
				valor = '0';
				valor = $('input[name=checkUsu'+i+']').val();
				checkID.push( valor );
				// Ahora tengo hacer array con id...
			}
			
		});
		console.log('ID de Usuarios seleccionado:'+checkID);
		return;
	});


}
	
//~ function BuscarRecambio (){
	//~ $(document).ready(function()
	//~ {
		//~ // Lo ideal sería identificar palabras..
		//~ // de momento solo una palabra..
		//~ NuevoValorBuscar = $('input[name=Buscar').val();
		//~ NuevoValorBuscar = $.trim(NuevoValorBuscar);
		//~ if (NuevoValorBuscar !== ''){
			//~ BRecambios= NuevoValorBuscar;
			//~ console.log('Filtro:'+BRecambios);
		//~ } else {
			//~ alert (' Debes poner algun texto ');
			//~ BRecambios = '';
		//~ }
		//~ return;
	//~ });
 //}



function metodoClick(pulsado){
	console.log("Inicimos switch de control pulsar");
	switch(pulsado) {
		case 'VerUsuario':
			console.log('Entro en VerUsuario');
			// Cargamos variable global ar checkID = [];
			VerUsuariosSeleccionado ();
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





