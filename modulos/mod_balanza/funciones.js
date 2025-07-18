function metodoClick(pulsado,adonde){
    switch(pulsado) {
        case 'AgregarBalanza':
            window.location.href = './balanza.php';
        break;
        case 'VerBalanza':
            var checkIds = TfObtenerCheck('check_balanza');
            if (!checkIds || checkIds.length === 0) {
            alert("Debe seleccionar una balanza.");
            return;
            }
            console.log(checkIds);
            window.location.href = './' + adonde + '.php?id=' + checkIds[0];
        break;
    }
}
function AgregarBalanza() {
    //@Objetivo: Agregar una nueva balanza con validaciones adicionales

    var nombreBalanza = $('#nombreBalanza').val().trim();
    var modeloBalanza = $('#modeloBalanza').val().trim();
    var secciones = $('#secciones').val();
    var grupo = $('#grupoBalanza').val().trim();
    var direccion = $('#direccionBalanza').val().trim();
    var ip = $('#ipBalanza').val().trim();

 

    // Validar grupo, dirección e IP: si alguno tiene valor, todos deben tenerlo
    var algunoGrupo = grupo || direccion || ip;
    console.log(algunoGrupo);
    if (algunoGrupo && (!grupo || !direccion || !ip)) {
        alert("Si completa Grupo, Dirección o IP, debe completar los tres campos.");
        return;
    }
    if (ip) {
        // Validar formato IP: 4 grupos de 1-3 dígitos, sin ceros a la izquierda, cada grupo <= 255
        var ipRegex = /^(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)){3}$/;
        if (!ipRegex.test(ip)) {
            alert("La IP debe tener el formato correcto (ej: 192.168.4.23), sin ceros a la izquierda y cada grupo de 1 a 3 dígitos.");
            return;
        }
    }
    // Validar que Grupo y Dirección sea un numero de no más de 2 digitios. Si lo tiene 1 digito transformalo y añadir un 0 a la izquierda
    if (grupo && !/^\d{1,2}$/.test(grupo)) {
        alert("El Grupo debe ser un número de 1 o 2 dígitos.");
        return;
    }
    if (direccion && !/^\d{1,2}$/.test(direccion)) {
        alert("La Dirección debe ser un número de 1 o 2 dígitos.");
        return;
    }

    var parametros = {
        "pulsado": "addBalanza",
        "nombreBalanza": nombreBalanza,
        "modeloBalanza": modeloBalanza,
        "secciones": secciones,
        "Grupo": grupo,
        "Direccion": direccion,
        "IP": ip,
        "soloPLUS": $('#soloPLUS').is(':checked') ? 1 : 0
    };
    console.log(parametros);
    $.ajax({
        data: parametros,
        url: 'tareas.php',
        type: 'post',
        beforeSend: function () {
            console.log('*********  enviando datos para add balanzas ****************');
        },
        success: function (response) {
            console.log('Repuesta de add balanzas');
            var resultado = $.parseJSON(response);
            console.log(resultado);
            window.location = "ListaBalanzas.php";
        }
    });
}


function htmlPlu(idBalanza){
    //@Objetivo: Generar el html con los campos para añadir un plu
    var secciones=$('#secciones').val();
    var parametros={
            "pulsado"    	: 'htmlPlu',
            'secciones'    :secciones,
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
function controladorAcciones(caja, accion, seccion){
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
    var seccion=$('#seccionPLU').val();
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
            "seccion"         : seccion,
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
                $('#seccionPlu').val("");
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
    var nombreBalanza = $('#nombreBalanza').val().trim();
    var modeloBalanza = $('#modeloBalanza').val().trim();
    var secciones = $('#secciones').val();
    var grupo = $('#grupoBalanza').val().trim();
    var direccion = $('#direccionBalanza').val().trim();
    var ip = $('#ipBalanza').val().trim();

    if(nombreBalanza === "" || modeloBalanza === ""){
        alert("Quedan campos IMPORTANTES sin cubrir!!");
        return;
    }

    // Validar grupo, dirección e IP: si alguno tiene valor, todos deben tenerlo
    var algunoGrupo = grupo || direccion || ip;
    if (algunoGrupo && (!grupo || !direccion || !ip)) {
        alert("Si completa Grupo, Dirección o IP, debe completar los tres campos.");
        return;
    }
    if (ip) {
        // Validar formato IP: 4 grupos de 1-3 dígitos, sin ceros a la izquierda, cada grupo <= 255
        var ipRegex = /^(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)){3}$/;
        if (!ipRegex.test(ip)) {
            alert("La IP debe tener el formato correcto (ej: 192.168.4.23), sin ceros a la izquierda y cada grupo de 1 a 3 dígitos.");
            return;
        }
    }
    // Validar que Grupo y Dirección sea un numero de no más de 2 dígitos. Si lo tiene 1 digito transformalo y añadir un 0 a la izquierda
    if (grupo && !/^\d{1,2}$/.test(grupo)) {
        alert("El Grupo debe ser un número de 1 o 2 dígitos.");
        return;
    }
    if (direccion && !/^\d{1,2}$/.test(direccion)) {
        alert("La Dirección debe ser un número de 1 o 2 dígitos.");
        return;
    }

    var parametros = {
        "pulsado": "modificarBalanza",
        "idBalanza": id,
        "nombreBalanza": nombreBalanza,
        "modeloBalanza": modeloBalanza,
        "secciones": secciones,
        "Grupo": grupo,
        "Direccion": direccion,
        "IP": ip,
        "soloPLUS": $('#soloPLUS').is(':checked') ? 1 : 0
    };
    $.ajax({
        data: parametros,
        url: 'tareas.php',
        type: 'post',
        beforeSend: function () {
            console.log('*********  enviando modificar datos balanzas ****************');
        },
        success: function (response) {
            console.log('Repuesta de modificar datos balanza');
            var resultado = $.parseJSON(response);
            window.location = "ListaBalanzas.php";
        }
    });
}

function CrearDirectioBalanza() {
    var id = <?php echo json_encode($id); ?>;
    var nombre = document.getElementById('nombreBalanza').value;
    var modoDirectorio = document.getElementById('modoDirectorio').value;

    $('#directorioMsg').text('Creando directorio...');
    $.ajax({
        url: 'tareas.php',
        type: 'POST',
        data: {
            id: id,
            nombre: nombre,
            modoDirectorio: modoDirectorio
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#directorioMsg').text('Directorio creado correctamente.');
            } else {
                $('#directorioMsg').text('Error: ' + response.message);
            }
        },
        error: function() {
            $('#directorioMsg').text('Error en la solicitud AJAX.');
        }
    });
}

function validarCamposBalanza(nombreBalanza, modeloBalanza, secciones) {
    //@Objetivo: Validar los campos necesarios para agregar o modificar una balanza
    // Validar campos obligatorios
    var respuesta = {};
    if (!nombreBalanza || !modeloBalanza || !secciones) {
        respuesta["Texto"] = "Debe completar Nombre Balanza, Modelo Balanza y Secciones.";
    }
    return respuesta;
}