function metodoClick(pulsado,adonde){
    switch(pulsado) {
        case 'AgregarBalanza':
            window.location.href = './balanza.php';
        break;
        case 'VerBalanza':
        checkID = leerChecked('check_'+ adonde);
			if (checkID.length >1 || checkID.length=== 0) {
				alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			}
			  window.location.href = './'+adonde+'.php?id='+$('#'+checkID[0]).val();
        break;
    }
}
function AgregarBalanza(){
    //@Objetivo: Agregar una nueva balanza
    //@Proceso: 
    //  1- Recoger los datos de los imput y el select 
    //  2- Mandar los datos por ajax 
    //  3-Cuando envia la respuesta del ajax redirige al listado
    var nombreBalanza=$('#nombreBalanza').val();
    var modeloBalanza=$('#modeloBalanza').val();
    if(nombreBalanza=="" || modeloBalanza==""){
        alert("Quedan campos IMPORTANTES sin cubrir!!");
    }else{
        var teclas=$('#teclas').val();
        var parametros={
            "pulsado"    	: 'addBalanza',
            'nombreBalanza' : nombreBalanza,
            'modeloBalanza' : modeloBalanza,
            'teclas'         :teclas
        }
        console.log(parametros);
        $.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  enviando datos para add balanzas ****************');
		},
		success    :  function (response) {
			console.log('Repuesta de add balanzas');
			var resultado =  $.parseJSON(response);
			console.log(resultado);
            window.location="ListaBalanzas.php";
		}
	});
    }
}


function htmlPlu(idBalanza){
    //@Objetivo: Generar el html con los campos para añadir un plu
    var teclas=$('#teclas').val();
    var parametros={
            "pulsado"    	: 'htmlPlu',
            'teclas'    :teclas,
            'idBalanza':idBalanza
        }
    $.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  enviando datos para html balanzas ****************');
		},
		success    :  function (response) {
			console.log('Repuesta de html balanzas');
			var resultado =  $.parseJSON(response);
			console.log(resultado);
           $('#addPlu').html(resultado['html']);
		}
	});
}
function controladorAcciones(caja, accion, tecla){
     console.log (' Controlador Acciones: ' +accion);
      switch(accion) {
          case 'BuscarProducto':
            console.log("entre en buscar producto");
            console.log(caja);
            var valor=$('#'+caja.id_input).val();
            buscarProducto(caja, valor);
          break;
      }
}

function buscarProducto(caja, valor){
    //@Objetivo: buscar producto
    idcaja = caja.id_input;
    campo = caja.darParametro('campo');
    console.log(caja);
    var parametros = {
		"pulsado"   : 'buscarProducto',
		"busqueda"  : valor,
		"idcaja"    : idcaja,
        "campo"     :campo
	};
    console.log(parametros);
    $.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  enviando datos para buscar productos ****************');
		},
		success    :  function (response) {
			console.log('Repuesta de buscar productos');
			var resultado =  $.parseJSON(response);
            if(resultado['datos']){
                datosEnInput(resultado['datos']['idArticulo'], resultado['datos']['nombre'], resultado['datos']['referencia'], resultado['datos']['codBarras']);
            }else{
                var titulo = 'Listado Productos ';
               
                abrirModal(titulo,resultado['html']);
            }
		}
	});
}
function datosEnInput(id, nombre, ref, codBarras,precioCiva){
    //@Objetivo: cuando se tengan los datos del producto se introducen en los respectivos inputs
    $('#idArticulo').val(id);
    $('#nombreProducto').val(nombre);
    $('#referencia').val(ref);
    $('#codBarras').val(codBarras);
    $('#precioConIva').val(precioCiva);

}
function seleccionProductoModal(id, nombre, ref, codBarras,precioCiva){
    //@OBjetivo:  cuando se obtienen los datos del producto desde el modal se introducen en los respectivos inputs
    datosEnInput(id, nombre, ref, codBarras,precioCiva);
    cerrarPopUp();
}
function addPlu(idBalanza){
    //@OBjetivo: añadir plu
    var id=$('#idArticulo').val();
    var plu=$('#plu').val();
    var cref=$('#referencia').val();
    var articulo_name=$('#nombreProducto').val();
    var tecla=$('#teclaPlu').val();
    var pvpCiva = $('#precioConIva').val();
    if(id=="" || plu =="" ){
        alert("Quedan campos sin cubrir");
    }else{
        var parametros = {
            "pulsado"       : 'addPlu',
            "idArticulo"    : id,
            "plu"           : plu,
            "cref"          : cref,
            "articulo_name" : articulo_name,
            "tecla"         : tecla,
            "idBalanza"     : idBalanza,
            "pvpCiva"       : pvpCiva
        };
         $.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  enviando add plu ****************');
		},
		success    :  function (response) {
			console.log('Repuesta de add plu');
			var resultado =  $.parseJSON(response);
            if(resultado['error']){
                alert(resultado['error']);
            }else{
                //Si no hay error se borrar los campos y se añade la nueva linea
                $('#tPlus tr:last').after(resultado['html']);
                $('#idArticulo').val("");
                $('#nombreProducto').val("");
                $('#referencia').val("");
                $('#codBarras').val("");
                $('#plu').val("");
                $('#teclaPlu').val("");
                $('#precioConIva').val("");
            }
		}
	});
    
    }
}
function eliminarPlu(plu, idBalanza){
    //@Objetivo_eliminar plu
    var parametros = {
            "pulsado"   : 'eliminarPlu',
            "plu"    : plu,
            "idBalanza": idBalanza
        };
    $.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  enviando add plu ****************');
		},
		success    :  function (response) {
			console.log('Repuesta de add plu');
			var resultado =  $.parseJSON(response);
            $('#plu_'+plu).remove();
            console.log('#plu_'+plu);
		}
	});
       
}
function mostrarDatosBalanza(idBalanza){
    //@Objetivo: MOstrar los datos de un balanza(todos los plu y datos del articulo)
     
     if($('#filtroBalanza').val()){
         var filtro=$('#filtroBalanza').val();
     }else{
         var filtro='a.plu';
     }
     console.log(filtro);
    var parametros = {
            "pulsado"   : 'mostrarDatosBalanza',
            "idBalanza": idBalanza,
            "filtro"    :filtro
        };
    $.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  enviando mostrar datos balanza ****************');
		},
		success    :  function (response) {
			console.log('Repuesta de mostrar datos balanza');
			var resultado =  $.parseJSON(response);
            $('.tablaPrincipal tbody tr').remove();
            $('#infoBalanza').html(resultado['htmlDatosBalanza']);
            $( "#infoBalanza" ).addClass( "bg-success" )
            $('.tablaPrincipal tbody').append(resultado['html']);
		}
	});
}


function ModificarBalanza(id){
    //@Objetivo: Modificar los datos de una balanza
      var nombreBalanza=$('#nombreBalanza').val();
    var modeloBalanza=$('#modeloBalanza').val();
    if(nombreBalanza=="" || modeloBalanza==""){
        alert("Quedan campos IMPORTANTES sin cubrir!!");
    }else{
        var teclas=$('#teclas').val();
        var parametros = {
            "pulsado"   : 'modificarBalanza',
            "idBalanza": id,
            "nombre":nombreBalanza,
            "modelo":modeloBalanza,
            'tecla':teclas
        };
         $.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  enviando modificar datos balanzas ****************');
		},
		success    :  function (response) {
			console.log('Repuesta de modificar datos balanza');
			var resultado =  $.parseJSON(response);
            window.location="ListaBalanzas.php";
		}
	});
    }
}

