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

    // Validar todos los campos con las funciones de validación
    var mensajes = [];
    var respuesta;

    respuesta = validarCamposBalanza(nombreBalanza, modeloBalanza, secciones);
    if (respuesta["Texto"]) mensajes.push(respuesta["Texto"]);

    respuesta = validarGrupoDireccionIp(grupo, direccion, ip);
    if (respuesta["Texto"]) mensajes.push(respuesta["Texto"]);

    respuesta = validarFormatoIp(ip);
    if (respuesta["Texto"]) mensajes.push(respuesta["Texto"]);

    respuesta = validarNumeroDosDigitos(grupo, "Grupo");
    if (respuesta["Texto"]) mensajes.push(respuesta["Texto"]);

    respuesta = validarNumeroDosDigitos(direccion, "Dirección");
    if (respuesta["Texto"]) mensajes.push(respuesta["Texto"]);

    if (mensajes.length == 0) {

        // Si todos los campos son válidos, enviar los datos al servidor
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
    } else {
        // Si hay mensajes de error, mostrarlos al usuario
        alert(mensajes.join("\n"));
    }
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


function ModificarBalanza(id) {
    //@Objetivo: Modificar los datos de una balanza con validaciones adicionales
    var nombreBalanza = $('#nombreBalanza').val().trim();
    var modeloBalanza = $('#modeloBalanza').val().trim();
    var secciones = $('#secciones').val();
    var grupo = $('#grupoBalanza').val().trim();
    var direccion = $('#direccionBalanza').val().trim();
    var ip = $('#ipBalanza').val().trim();

    // Validar todos los campos con las funciones de validación
    var mensajes = [];
    var respuesta;

    respuesta = validarCamposBalanza(nombreBalanza, modeloBalanza, secciones);
    if (respuesta["Texto"]) mensajes.push(respuesta["Texto"]);

    respuesta = validarGrupoDireccionIp(grupo, direccion, ip);
    if (respuesta["Texto"]) mensajes.push(respuesta["Texto"]);

    respuesta = validarFormatoIp(ip);
    if (respuesta["Texto"]) mensajes.push(respuesta["Texto"]);

    respuesta = validarNumeroDosDigitos(grupo, "Grupo");
    if (respuesta["Texto"]) mensajes.push(respuesta["Texto"]);

    respuesta = validarNumeroDosDigitos(direccion, "Dirección");
    if (respuesta["Texto"]) mensajes.push(respuesta["Texto"]);

    if (mensajes.length == 0) {
        // Si todos los campos son válidos, enviar los datos al servidor
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
                console.log(resultado);
                window.location = "ListaBalanzas.php";
            }
        });
    } else {
        // Si hay mensajes de error, mostrarlos al usuario
        alert(mensajes.join("\n"));
    }
}

function CrearDirectorioBalanza(idBalanza) {
    //@Objetivo: Crear el directorio de una balanza
    var nombre = $('#nombreBalanza').val();
    var modoDirectorio = $('#modoDirectorio').val();

    var parametros = {
        "pulsado": "crearDirectorioBalanza",
        "idBalanza": idBalanza,
        "nombre": nombre,
        "modoDirectorio": modoDirectorio
    };

    $('#directorioMsg').text('Creando directorio...');
    $.ajax({
        data: parametros,
        url: 'tareas.php',
        type: 'post',
        beforeSend: function () {
            console.log('*********  enviando crear directorio balanza ****************');
        },
        success: function (response) {
            console.log('Respuesta de crear directorio balanza');
            var resultado = $.parseJSON(response);
            if (resultado.success) {
                $('#directorioMsg').text('Directorio creado correctamente.');
            } else {
                $('#directorioMsg').text('Error: ' + resultado.message);
            }
        },
        error: function () {
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

function validarGrupoDireccionIp(grupo, direccion, ip) {
    //@Objetivo: Validar que si se completa Grupo, Dirección o IP, se completen los tres campos
    // Validar que si alguno de los campos Grupo, Dirección o IP tiene valor, todos deben tenerlo
    var respuesta = {};
    if ((grupo || direccion || ip) && (!grupo || !direccion || !ip)) {
        respuesta["Texto"] = "Si completa Grupo, Dirección o IP, debe completar los tres campos.";
    }
    return respuesta;
}

function validarFormatoIp(ip) {
    // @Objetivo: Validar el formato de la IP
    // La IP debe tener el formato correcto: 4 grupos de 1-3 dígitos, sin ceros a la izquierda, cada grupo <= 255
    var respuesta = {};
    if (ip) {
        var ipRegex = /^(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)){3}$/;
        if (!ipRegex.test(ip)) {
            respuesta["Texto"] = "La IP debe tener el formato correcto (ej: 192.168.4.23), sin ceros a la izquierda y cada grupo de 1 a 3 dígitos.";
        }
    }
    return respuesta;
}

function validarNumeroDosDigitos(valor, campoNombre) {
    // @Objetivo: Validar que el valor sea un número de 1 o 2 dígitos
    var respuesta = {};
    if (valor && !/^\d{1,2}$/.test(valor)) {
        respuesta["Texto"] = "El " + campoNombre + " debe ser un número de 1 o 2 dígitos.";
    }
    return respuesta;
}
