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
            $('#errores').html(resultado['html']);
		}
	});
    }
}


function htmlPlu(idBalanza){
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
            buscarProducto(caja.id_input, valor);
          break;
      }
}

function buscarProducto(idInput, valor){
    var parametros = {
		"pulsado"   : 'buscarProducto',
		"busqueda"  : valor,
		"idcaja"    : idInput
	};
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
                abrirModal(titulo,resultado.html);
            }
		}
	});
}
function datosEnInput(id, nombre, ref, codBarras){
    $('#idArticulo').val(id);
    $('#nombreProducto').val(nombre);
    $('#referencia').val(ref);
    $('#codBarras').val(codBarras);
}
function buscarProductosModal(id, nombre, ref, codBarras){
    datosEnInput(id, nombre, ref, codBarras);
    cerrarPopUp();
}
function addPlu(idBalanza){
    var id=$('#idArticulo').val();
    var plu=$('#plu').val();
    var tecla=$('#teclaPlu').val();
    if(id=="" || plu =="" || tecla==""){
        
    }else{
        var parametros = {
            "pulsado"   : 'addPlu',
            "idArticulo"  : id,
            "plu"    : plu,
            "tecla": tecla,
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
            if(resultado['error']){
                alert(resultado['error']);
            }
		}
	});
    
    }
}
