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
			}
			
		});
		console.log('ID de Producto seleccionado:'+checkID);
		return;
	});


}
//recogemos valor de la caja de busqueda que tenemos en Listado tickets o productos
function BuscarProducto (){
	$(document).ready(function()
	{
		// Lo ideal sería identificar palabras..
		// de momento solo una palabra..
		NuevoValorBuscar = $('input[name=buscar').val();
		NuevoValorBuscar = $.trim(NuevoValorBuscar);
		if (NuevoValorBuscar !== ''){
			BProductos= NuevoValorBuscar;
			console.log('Filtro:'+BProductos);
		} else {
			alert (' Debes poner algun texto ');
			BProductos = '';
		}
		return;
	});
}
//parametros:
//adonde : a donde quiero ir o donde quiero permanecer: ListaTickets, ListaProductos.. 
function metodoClick(pulsado,adonde){
	console.log("Inicimos switch de control pulsar");
	switch(pulsado) {
		case 'VerProducto':
			console.log('Entro en Ver producto');
			// Cargamos variable global ar checkID = [];
			VerUsuariosSeleccionado ();
			if (checkID.length >1 || checkID.length=== 0) {
				alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			}
			// Ahora redireccionamos 
			// recambi.php?id=id
			window.location.href = './'+adonde+'.php?id='+checkID[0];
			//window.location.href = './producto.php?id='+checkID[0];
			
			
			break;
		
		case 'AgregarProducto':
			console.log('entro en agregar producto');
			window.location.href = './'+adonde+'.php';
			
			break;
		
		case 'NuevaBusqueda':
			// Obtenemos puesto en input de Buscar
			BuscarProducto ();
			// Ahora redireccionamos 
			// recambi.php?buscar = buquedaid=id
			if (BProductos !== ''){
				window.location.href = './'+adonde+'.php?buscar='+BProductos;
//				window.location.href = './ListaProductos.php?buscar='+BProductos;
			} else {
				// volvemos sin mas..
				return;
				//~ window.location.href = './ListaRecambios.php';	
			}
			console.log('Resultado Buscar:'+BProductos);
			break;
		
	 }
} 





