function metodoClick(pulsado, adonde) {
    switch (pulsado) {
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
        case 'EliminarBalanza':
            var checkIds = TfObtenerCheck('check_balanza');
            if (!checkIds || checkIds.length === 0) {
                alert("Debe seleccionar al menos una balanza.");
                return;
            }
            verificarYEliminarBalanza(checkIds[0]);
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
    var seccion=$('#seccion').val();
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
                $('#seccion').val("");
                $('#precioConIva').val("");
            }
		}
	});
    
    }
}
function eliminarPlu(plu, idBalanza){
    //@Objetivo_eliminar plu
    var parametros = {
        "pulsado": 'eliminarPlu',
        "plu": plu,
        "idBalanza": idBalanza
    };
    $.ajax({
        data: parametros,
        url: 'tareas.php',
        type: 'post',
        beforeSend: function () {
            console.log('*********  enviando eliminar plu ****************');
        },
        success: function (response) {
            console.log('Repuesta de eliminar plu');
            var resultado = $.parseJSON(response);
            $('#plu_' + plu).remove();
            console.log('#plu_' + plu);
            location.reload();
        }
    });
}

function mostrarDatosBalanza(idBalanza){
    // Marcar el checkbox correspondiente
    $('.check_balanza').prop('checked', false); // Desmarcar todos
    $('.check_balanza[value="' + idBalanza + '"]').prop('checked', true);

    var filtro = $('#filtroBalanza').val() ? $('#filtroBalanza').val() : 'a.plu';
    var parametros = {
        "pulsado": 'mostrarDatosBalanza',
        "idBalanza": idBalanza,
        "filtro": filtro
    };
    $.ajax({
        data: parametros,
        url: 'tareas.php',
        type: 'post',
        beforeSend: function () {
            console.log('*********  enviando mostrar datos balanza ****************');
        },
        success: function (response) {
            var resultado = $.parseJSON(response);
            // Limpiar antes de insertar para evitar duplicados
            $('#infoBalanza').empty();
            $('.tablaPrincipal thead').empty();
            $('.tablaPrincipal tbody').empty();

            $('#infoBalanza').html(resultado['htmlDatosBalanza']);
            $("#infoBalanza").addClass("bg-success");

            // Si el backend devuelve una tabla completa, inserta en un div, no en tbody
            // Si solo devuelve filas, usa tbody
            // Aquí asumimos que devuelve filas:
            $('.tablaPrincipal tbody').append(resultado['html']);
        }
    });
}


function ModificarBalanza(id) {
    //@Objetivo: Modificar solo los datos principales de una balanza
    var nombreBalanza = $('#nombreBalanza').val().trim();
    var modeloBalanza = $('#modeloBalanza').val().trim();
    var secciones = $('#secciones').val();

    // Validar campos obligatorios
    var mensajes = [];
    var respuesta = validarCamposBalanza(nombreBalanza, modeloBalanza, secciones);
    if (respuesta["Texto"]) mensajes.push(respuesta["Texto"]);

    if (mensajes.length === 0) {
        var parametros = {
            "pulsado": "modificarBalanza",
            "idBalanza": id,
            "nombreBalanza": nombreBalanza,
            "modeloBalanza": modeloBalanza,
            "secciones": secciones
        };
        $.ajax({
            data: parametros,
            url: 'tareas.php',
            type: 'post',
            beforeSend: function () {
                console.log('*********  enviando modificar datos principales balanza ****************');
            },
            success: function (response) {
                console.log('Respuesta de modificar datos principales balanza');
                var resultado = $.parseJSON(response);
                console.log(resultado);
                window.location = "ListaBalanzas.php";
            }
        });
    } else {
        alert(mensajes.join("\n"));
    }
}

function CrearDirectorioBalanza(idBalanza) {
    //@Objetivo: Crear el directorio de una balanza
    var nombreBalanza = $('#nombreBalanza').val().trim();
    var ipBalanza = $('#ipBalanza').val();
    var grupoBalanza = $('#grupoBalanza').val();
    var direccionBalanza = $('#direccionBalanza').val();
    var ipPc = $('#ipPc').val();
    var serieH = $('#serieH').val();
    var serieTipo = $('#serieTipo').val();
    var modoDirectorio = $('#modoDirectorio').val();

    var parametros = {
        "pulsado": "crearDirectorioBalanza",
        "nombreBalanza": nombreBalanza,
        "idBalanza": idBalanza,
        "ipBalanza": ipBalanza,
        "grupoBalanza": grupoBalanza,
        "direccionBalanza": direccionBalanza,
        "ipPc": ipPc,
        "serieH": serieH,
        "serieTipo": serieTipo,
        "modoDirectorio": modoDirectorio
    };
    $.ajax({
        data: parametros,
        url: 'tareas.php',
        type: 'post',
        beforeSend: function () {
            console.log('*********  enviando crear directorio balanza ****************');
        },
        success: function (response) {
            console.log('Repuesta de crear directorio balanza');
            var resultado = $.parseJSON(response);
            console.log(resultado);
            window.location = "ListaBalanzas.php";
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


function toggleIpPcInput(revert = false) {
    var modo = document.getElementById('modoDirectorio').value;
    console.log("Modo seleccionado: " + modo);
    // Asegúrate de que los elementos tengan la clase 'ipPcGroup'
    var ipPcGroups = document.querySelectorAll('.ipPcGroup');
    ipPcGroups.forEach(function(ipPcGroup) {
            if (modo === 'Balctrol') {
                ipPcGroup.style.display = '';
            } else {
                ipPcGroup.style.display = 'none';
            }
    });
}

function modificarPlu(idArticulo, idBalanza) {
    const $pluInput = $('#editPlu_' + idArticulo);
    const $teclaInput = $('#editTecla_' + idArticulo);
    const $btnEditar = $('#modificar_' + idArticulo);
    const $btnGuardar = $('#guardar_' + idArticulo);

    // Guardar valores originales si no existen
    if ($pluInput.data('original') === undefined) {
        $pluInput.data('original', $pluInput.val());
    }
    if ($teclaInput.length && $teclaInput.data('original') === undefined) {
        $teclaInput.data('original', $teclaInput.val());
    }

    // Si ya está editable y no hay cambios, desactiva edición
    if (!$pluInput.prop('readonly') && !pluInputsModificados(idArticulo)) {
        $pluInput.prop('readonly', true);
        $teclaInput.prop('readonly', true);
        $btnEditar.show();
        $btnGuardar.hide();
        $pluInput.add($teclaInput).off('input._pluEdit keydown._pluEdit');
        return;
    }

    // Hacer editables
    $pluInput.prop('readonly', false);
    $teclaInput.prop('readonly', false);

    // Mostrar solo el botón editar
    $btnEditar.show();
    $btnGuardar.hide();

    // Evento para detectar cambios
    $pluInput.add($teclaInput)
        .off('input._pluEdit keydown._pluEdit')
        .on('input._pluEdit', function () {
            if (pluInputsModificados(idArticulo)) {
                $btnEditar.hide();
                $btnGuardar.show();
            } else {
                $btnEditar.show();
                $btnGuardar.hide();
            }
        })
        .on('keydown._pluEdit', function (e) {
            if (e.key === "Enter" && pluInputsModificados(idArticulo)) {
                guardarPlu(idArticulo, idBalanza);
            }
        });
}

// Devuelve true si algún input fue modificado respecto a su valor original
function pluInputsModificados(idArticulo) {
    const $pluInput = $('#editPlu_' + idArticulo);
    const $teclaInput = $('#editTecla_' + idArticulo);
    let modificado = false;
    if ($pluInput.val() !== $pluInput.data('original')) modificado = true;
    if ($teclaInput.length && $teclaInput.val() !== $teclaInput.data('original')) modificado = true;
    return modificado;
}

// Deshabilita la edición y restaura valores originales si no se guardó
function cancelarEdicionPlu(idArticulo) {
    const $pluInput = $('#editPlu_' + idArticulo);
    const $teclaInput = $('#editTecla_' + idArticulo);
    const $btnEditar = $('#modificar_' + idArticulo);
    const $btnGuardar = $('#guardar_' + idArticulo);

    // Restaurar valores originales
    $pluInput.val($pluInput.data('original')).prop('readonly', true);
    if ($teclaInput.length) {
        $teclaInput.val($teclaInput.data('original')).prop('readonly', true);
    }

    // Botones
    $btnEditar.show();
    $btnGuardar.hide();

    // Quitar eventos
    $pluInput.add($teclaInput).off('input._pluEdit');
}

// Habilita/deshabilita la edición de todos los PLUs
function toggleEditarTodos() {
    var editando = $('#btnModificarTodos').data('editando') || false;
    console.log('toggleEditarTodos: Estado actual editando:', editando);
    if (!editando) {
        // Habilitar edición
        console.log('toggleEditarTodos: Habilitando edición de todos los PLUs');
        $('input[id^="editPlu_"]').prop('readonly', false);
        $('input[id^="editTecla_"]').prop('readonly', false);
        $('#btnModificarTodos').hide();
        $('#btnGuardarTodos').show();
        $('#btnModificarTodos').data('editando', true);
    } else {
        // Deshabilitar edición
        console.log('toggleEditarTodos: Deshabilitando edición de todos los PLUs');
        $('input[id^="editPlu_"]').prop('readonly', true);
        $('input[id^="editTecla_"]').prop('readonly', true);
        $('#btnModificarTodos').show();
        $('#btnGuardarTodos').hide();
        $('#btnModificarTodos').data('editando', false);
    }
}

function guardarTodos(idBalanza) {
    var datos = [];
    $('tr[id^="plu_"]').each(function() {
        var idArticulo = this.id.replace('plu_', '');
        var plu = $('#editPlu_' + idArticulo).val();
        var seccion = $('#editTecla_' + idArticulo).length ? $('#editTecla_' + idArticulo).val() : null;
        datos.push({
            idArticulo: idArticulo,
            plu: plu,
            seccion: seccion
        });
    });
$.ajax({
    url: 'tareas.php',
    type: 'post',
    data: {
        pulsado: 'guardarTodosPlus',
        datos: datos, // sin JSON.stringify
        idBalanza: idBalanza
    },
    success: function(response) {
        location.reload();
    }
});
}

function guardarPlu(idArticulo, idBalanza) {
    var plu = $('#editPlu_' + idArticulo).val();
    var seccion = $('#editTecla_' + idArticulo).length ? $('#editTecla_' + idArticulo).val() : null;
    $.ajax({
        url: 'tareas.php',
        type: 'post',
        data: {
            pulsado: 'guardarPlu',
            idArticulo: idArticulo,
            plu: plu,
            seccion: seccion,
            idBalanza: idBalanza
        },
        success: function(response) {
            // Puedes refrescar la tabla o mostrar un mensaje
            location.reload();
        }
    });
}

function mostrarArticulosPeso(idBalanza) {
    console.log('mostrarArticulosPeso: solicitando artículos de peso para balanza', idBalanza);
    $.ajax({
        url: 'tareas.php',
        type: 'post',
        data: {
            pulsado: 'mostrarArticulosPeso',
            idBalanza: idBalanza
        },
        beforeSend: function() {
            console.log('mostrarArticulosPeso: enviando petición AJAX...');
        },
        success: function(response) {
            console.log('mostrarArticulosPeso: respuesta recibida');
            var resultado = $.parseJSON(response);
            console.log('mostrarArticulosPeso: resultado parseado', resultado);
            $('#addArticuloPeso').html(resultado.html); // Asegúrate de tener este <tr> en tu tabla
        },
        error: function(xhr, status, error) {
            console.error('mostrarArticulosPeso: error en la petición AJAX', status, error);
        }
    });
}

function mostrarTablaPluAdd(idBalanza) {
    htmlPlu(idBalanza); // Debe rellenar #addPlu
    $('#agregar').hide();
    $('#mostrarTablaPlus').show();
    $('#addPlu').show();
}
function toggleTablaPlus() {
    $('#addPlu').toggle();
}

// Para Artículos Peso
function mostrarTablaArtPesoAdd(idBalanza) {
    mostrarArticulosPeso(idBalanza); // Debe rellenar #addArticuloPeso
    $('#agregarArtPeso').hide();
    $('#mostrarTablaArtPeso').show();
    $('#addArticuloPeso').show();
}
function toggleTablaArtPeso() {
    $('#addArticuloPeso').toggle();
}

function addArticuloPeso(idArticulo, idBalanza) {
    console.log('addArticuloPeso: idArticulo =', idArticulo, ', idBalanza =', idBalanza);
    var plu = prompt("Introduce el PLU para este artículo:");
    if (plu) {
        console.log('addArticuloPeso: PLU introducido =', plu);
        $.ajax({
            url: 'tareas.php',
            type: 'post',
            data: {
                pulsado: 'addPlu',
                idArticulo: idArticulo,
                plu: plu,
                idBalanza: idBalanza
            },
            beforeSend: function() {
                console.log('addArticuloPeso: enviando petición AJAX para añadir PLU...');
            },
            success: function(response) {
                console.log('addArticuloPeso: respuesta recibida', response);
                mostrarDatosBalanza(idBalanza);
                mostrarArticulosPeso(idBalanza);
            },
            error: function(xhr, status, error) {
                console.error('addArticuloPeso: error en la petición AJAX', status, error);
            }
        });
    } else {
        console.log('addArticuloPeso: operación cancelada o PLU vacío');
    }
}

function cerrarConfigAvanzada() {
    var panel = document.getElementById('panelConfigAvanzada');
    if (panel) panel.style.display = 'none';
}

function abrirConfigAvanzada() {
    var panel = document.getElementById('panelConfigAvanzada');
    if (panel) panel.style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('btnAbrirConfigAvanzada');
    var panel = document.getElementById('panelConfigAvanzada');
    if (btn && panel) {
        btn.onclick = abrirConfigAvanzada;
        // Cerrar con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === "Escape") cerrarConfigAvanzada();
        });
        // Cerrar al hacer click fuera del panel
        panel.addEventListener('click', function(e) {
            if (e.target === panel) cerrarConfigAvanzada();
        });
    }
});

function mostrarBotonOcultarLista() {
    document.getElementById('btnOcultarLista').style.display = '';
}

function verificarYEliminarBalanza(idBalanza) {
    $.ajax({
        url: 'tareas.php',
        type: 'post',
        data: {
            pulsado: 'tienePlusAsociados',
            idBalanza: idBalanza
        },
        success: function(response) {
            var resultado = $.parseJSON(response);
            if (resultado.tienePlus) {
                alert(resultado.mensaje);
                if (confirm("¿Desea modificar la balanza en lugar de eliminarla?")) {
                    window.location.href = './balanza.php?id=' + idBalanza;
                }
            } else {
                confirmarYEliminarBalanza(idBalanza);
            }
        }
    });
}

function confirmarYEliminarBalanza(idBalanza) {
    if (confirm("¿Está seguro de que desea eliminar la balanza seleccionada?")) {
        $.ajax({
            url: 'tareas.php',
            type: 'post',
            data: {
                pulsado: 'eliminarBalanza',
                idBalanza: idBalanza
            },
            success: function(resp) {
                if (window.location.pathname.endsWith('ListaBalanzas.php')) {
                    window.location.reload();
                } else {
                    window.location.href = 'ListaBalanzas.php';
                }
            }
        });
    }
}


function guardarConfigAvanzada(idBalanza) {
    //@Objetivo: Guardar la configuración avanzada de la balanza con validaciones adicionales
    var ipBalanza = $('#ipBalanza').val().trim();
    var grupoBalanza = $('#grupoBalanza').val().trim();
    var direccionBalanza = $('#direccionBalanza').val().trim();
    var soloPLUS = $('#soloPLUS').is(':checked') ? 1 : 0;
    var modoDirectorio = $('#modoDirectorio').val();
    var ipPc = $('#ipPc').val().trim();
    var serieH = $('#serieH').val().trim();
    var serieTipo = $('#serieTipo').val().trim();

    // Validar campos necesarios
    var mensajes = [];
    var respuesta;

    respuesta = validarGrupoDireccionIp(grupoBalanza, direccionBalanza, ipBalanza);
    if (respuesta["Texto"]) mensajes.push(respuesta["Texto"]);

    respuesta = validarFormatoIp(ipBalanza);
    if (respuesta["Texto"]) mensajes.push(respuesta["Texto"]);

    respuesta = validarNumeroDosDigitos(grupoBalanza, "Grupo");
    if (respuesta["Texto"]) mensajes.push(respuesta["Texto"]);

    respuesta = validarNumeroDosDigitos(direccionBalanza, "Dirección");
    if (respuesta["Texto"]) mensajes.push(respuesta["Texto"]);

    if (mensajes.length === 0) {
        var parametros = {
            "pulsado": "guardarConfigAvanzada",
            "idBalanza": idBalanza,
            "ipBalanza": ipBalanza,
            "grupoBalanza": grupoBalanza,
            "direccionBalanza": direccionBalanza,
            "soloPLUS": $('#soloPLUS').is(':checked') ? 1 : 0
        };
        console.log('Guardando configuración avanzada:', parametros);
        $.ajax({
            data: parametros,
            url: 'tareas.php',
            type: 'post',
            beforeSend: function () {
                console.log('*********  enviando guardar configuración avanzada balanza ****************');
            },
            success: function (response) {
                var resultado = $.parseJSON(response);
            },
            error: function () {
                alert('Error de comunicación con el servidor');
            }
        });
    } else {
        alert(mensajes.join("\n"));
    }
}