
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
			VerIdSeleccionado ();
			if (checkID.length >1 || checkID.length=== 0) {
				alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			}
			// Ahora redireccionamos 
			window.location.href = './'+adonde+'.php?id='+checkID[0];
			//window.location.href = './producto.php?id='+checkID[0];
			
			
			break;
		
		case 'AgregarProducto':
			console.log('entro en agregar producto');
			window.location.href = './producto.php';
			
			break;
		
		case 'NuevaBusqueda':
			// Obtenemos puesto en input de Buscar
			BuscarProducto ();
			// Ahora redireccionamos 
			if (BProductos !== ''){
				window.location.href = './'+adonde+'.php?buscar='+BProductos;
			} else {
				// volvemos sin mas..
				return;
			}
			console.log('Resultado Buscar:'+BProductos);
			break;
		
		
	 }
} 

function agregoCodBarrasVacio(contNuevo){
	//ajax
	// @ Objetivo
	//agrego campo codigo barras vacio en html
	var tablaC=document.getElementById("tcodigo");
	var cont=tablaC.childElementCount;
	
	var parametros = {
		"pulsado"    : 'HtmlCodigoBarrasVacio',
		"filas": cont
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Obteniendo html de codBarras vacio  ****************');
		},
		success    :  function (response) {
			console.log('Repuesta de Obtener HTML linea de FUNCION -> agregoCodBarrasVacio');
			
			var resultado =  $.parseJSON(response);
			var nuevafila = resultado['html'];
			console.log(nuevafila);
			
			$("#tcodigo").prepend(nuevafila);
			
		}
	});
	
}
//Función para controlar que no genere un nuevo input de codigo de barras mientras tengamos uno vacio 
function comprobarVacio(valor){
				var value=$.trim($("#codBarras").val());
				if(value.length>0 || value=="")
				{
					agregoCodBarrasVacio(valor);
				} 
			}
			
			
//Función para anular el enter en el formulario 
//Se puso para cuando se lea un código de barras que al hacer enter no cargue todo el formulario
function anular(e) {
  tecla = (document.all) ? e.keyCode : e.which;
   return (tecla != 13);
}
//Función para eliminar el código de barras . Busca los elementos a eliminar mediante DOM
//Cuando encuentra el elemento TBODY elimina el hijo que le indicamos
     function eliminarCodBarras(e){
	var padre=e.parentNode; 
	var abuelo=padre.parentNode; 
	var bisa=abuelo.parentNode; 
	bisa.removeChild(abuelo);
	 }
	 
	//Función que modifica el campo del precio con IVA , dependiendo del precio sin iva o el select de iva . 
	 function modifPrecioCiva(){
		 //Busca la opcion del select iva seleccionado
				var iva=parseFloat($( "#iva option:selected" ).val());
				//Recoge el precio sin iva del input 
				var SinIva=parseFloat($( "#pvpSiva" ).val());
				//Calculo de iva 
				var res=(iva*SinIva)/100;
				var total=SinIva+res;
				// Lo redondea a dos decimales
				total=parseFloat(total).toFixed(2);
				//Se modifica en el input de precio con iva
				document.getElementById('pvpCiva').value = total;
			}
			//Función que modifica el precio sin IVA , dependiendo del precio sin iva y el iva seleccionado lo modifica.
			function modifPrecioSiva(){
				//Recoge la opción del select iva seleccionada
				var iva=parseFloat($( "#iva option:selected" ).val());
				//Recoge le valor del input de la caja del precio con IVA 
				var ConIva=parseFloat($( "#pvpCiva" ).val());
				//Realiza los calculos necesarios
				ivaT=(iva/100)+1;
				var total=ConIva/ivaT;
				//Redondea el resultado a dos decimales 
				total=parseFloat(total).toFixed(2);
				//Lo muestra en la caja del input del precio sin IVA
				document.getElementById('pvpSiva').value = total;
			}


