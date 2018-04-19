// Funciones necesarias para plugin de mod_producto de vehiculos.
function SeleccionMarca(){
	alert ( 'cambio de marca ');
	
}

	


function  SeleccionMarca(tienda_web,productos){
	// @Objetivo :
	// Ejecutar en servidor de web funcion que reste stock de productos
	// Pendiente el que no lo haga dos vez , si hace clic o intro muy rapido.
	alert ( 'cambio de marca ');
	//~ var parametros = {
		//~ "key" :  tienda_web.key_api,
		//~ "action"    : 'ObtenerMarcasVehiculos'
	//~ };
	//~ $.ajax({
		//~ data       : parametros,
		//~ url        : url_ruta,
		//~ type       : 'post',
		//~ beforeSend : function () {
		//~ console.log('*********  Solicitamos a  servidor datos de vehiculo  ****************');
		//~ },
		//~ success    :  function (response) {
				//~ console.log('Respuesta de envio de datos');
				var resultado = $.parseJSON(response);
				//~ var resultado = response;
	
				//~ if (resultado['Datos'].estado !== 'Correcto'){
					//~ // Quiere decir que algo salio mal.. por lo que debemos guardalo en registro como error.
					//~ alert(' Error, algo salio mal.');
				//~ }
				//~ // Ahora registramos en tpv ( importar_virtuemart_ticketst el resultado)
				//~ console.log(resultado['Datos']);
			//~ }
			
	//~ });
} 
