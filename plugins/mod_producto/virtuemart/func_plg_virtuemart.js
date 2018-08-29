function AccionRecalcularPvpWeb(caja,tecla){
	console.log('Entro en controlador de recalcularPvpWeb');

    var re= comprobarNumero(caja.darValor());
        if ( re === true){
            console.log(re);
            recalcularPvpWeb(caja.id_input);
    }

}
function modificarProductoWeb(idProducto="", idTienda=""){
    //@Objetivo:
    //MOdificar los datos del producto en la web 
    console.log("entre en modificar producto web ");
      var mensaje = confirm("¿Estás seguro que quieres AÑADIR / MODIFICAR el producto en la web?");
     	if (mensaje) {   
    if($('#referenciaWeb').val()=="" || $('#nombreWeb').val()=="" || $('#precioSivaWeb').val()==""){
        alert("Campos necesarios vacios, Referencia, Nombre y Precio sin iva");
    }else{
    
    stock=parseInt($('#stockon').val())-parseInt($('#stockmin').val());
    //~ console.log(stock);
    var datos={
        'estado':       $('#estadosWeb').val(),
        'referencia':   $('#referenciaWeb').val(),
        'nombre':       $('#nombreWeb').val(),
        'codBarras':    $('#codBarrasWeb').val(),
        'precioSiva':   $('#precioSivaWeb').val(),
        'iva':          $('#ivasWeb').val(),
        'id':           $('#idWeb').html(),
        'alias':        $('#alias').val(),
        'stock':        stock,
        'idProducto':   idProducto, 
        'idTienda':     idTienda
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
                    if(resultado.htmlAlerta){
                        $('#alertasWeb').html(resultado.htmlAlerta);
                    }
                  
                    if(resultado.resul.Datos.idArticulo){
                        $('#idWeb').html(resultado.resul.Datos.idArticulo);
                        $('#botonWeb').val("Modificar en Web");
                    }
                    
                     
                }	
            });
    }
}
}
function ModalNotificacion(numLinea){
    //@Objetivo: mostrar el modal para enviar el correo de la notificación
    console.log("entre en enviar modal notificacion");
   
    var datos={
        'nombreProducto': $('#nombreWeb').val(),
        'id':             $('#idWeb').html(),
        'correo':         $('#mail_'+numLinea).html(),
        'nombreUsuario':  $('#nombre_'+numLinea).html(),
        'idNotificacion':  $('#idNotificacion_'+numLinea).val(), 
        'numLinea':numLinea
    };
    console.log(datos);
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
    //@Objetivo : Enviar el correo con la respuesta de la notificación
    console.log("entre en enviar correo de notificacion");
    var datos={
        'email':$('#email').val(),
        'asunto':$('#asunto').val(),
        'mensaje':$('#mensaje').val(),
        'idProducto':$('#idProducto').html(),
        'idNotificacion':  $('#idNotificacion').val(),
        'numLinea':$('#numLinea').val(),
    };
    console.log(datos);
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
                if(resultado.errorModificacion){
                     alert("No se modificó la notificación  error de SQL: "+ resultado.errorModificacion);
                     
                }
               if(resultado.mail==1){
                   //~ alert(resultado.error);
                   alert("Error no ha enviado la notificación por correo");
               }else{
                    cerrarPopUp();
                    console.log(resultado.numLinea);
                    $("#Linea_"+resultado.numLinea).remove();
                  
               }
              
                
				 
		}	
        });
        
}
function recalcularPvpWeb(dedonde){
    var iva=parseFloat($( "#ivasWeb option:selected" ).html(),2);
    iva= iva/100;
    console.log(iva);
    if (dedonde === 'precioSivaWeb'){
		var precioSiva = parseFloat($('#precioSivaWeb').val(),2);
		var precioCiva = precioSiva+(precioSiva*iva);
		// Ahora destacamos los input que cambiamos.		
		destacarCambioCaja('precioCivaWeb');
	}else{
        var precioCiva = parseFloat($('#precioCivaWeb').val(),2);
        iva = iva +1;
        var precioSiva = precioCiva/iva;
        destacarCambioCaja('precioSivaWeb');
    }
    $('#precioSivaWeb').val(precioSiva.toFixed(2));
	$('#precioCivaWeb').val(precioCiva.toFixed(2));
}
function modificarIvaWeb(){
    var iva=parseFloat($( "#ivasWeb option:selected" ).html(),2);
    console.log(iva);
    var precioSiva = parseFloat($('#precioSivaWeb').val(),2);
    iva=iva/100;
    console.log(iva);
    var precioCiva=precioSiva+(precioSiva*iva);
    console.log(precioCiva);
    destacarCambioCaja('precioCivaWeb');
    $('#precioCivaWeb').val(precioCiva.toFixed(2));
}

function ObtenerDatosProducto(){
 
    
    $('#referenciaWeb').val($('#referencia').val());
    $('#nombreWeb').val($('#nombre').val());
    $('#precioSivaWeb').val($('#pvpSiva').val());
    $('#precioCivaWeb').val($('#pvpCiva').val());
}

function subirProductosWeb(idTienda){
    
    var parametros = {
		"pulsado"    	: 'subirProductosWeb',
        "idTienda"      :idTienda
		};
        $.ajax({
		data       : parametros,
		url        :  ruta_plg_virtuemart+'tareas_virtuemart.php',
		type       : 'post',
		beforeSend : function () {
		console.log('*********  Subir conjunto de productos a la web **************');
		},
		success    :  function (response) {
				console.log('Respuesta de subir conjunto de productos a la web ');
				var resultado = $.parseJSON(response);
                console.log(resultado);
                if(resultado.productoEnWeb.length >0){
                   alert("Producto que YA ESTABAN y NO se subieron: "+JSON.stringify(resultado.productoEnWeb));
                }
                if(resultado.error){
                    alert("Error de SQL: " + resultado.error);
                }
                if(resultado.contadorProductos>0){
                    alert("Se han subido a la web :"+ resultado.contadorProductos+" Productos");
                }
				//~ location.href="ListaProductos.php";
				 
		}	
	});
}
