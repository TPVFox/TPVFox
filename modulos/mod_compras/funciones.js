// Protección contra doble envío en formulario de albarán
function onSubmitForm(form) {
    prepararAjustesParaGuardar();
    var btnGuardar = document.getElementById("bGuardar");
    if (btnGuardar) {
        btnGuardar.disabled = true;
        btnGuardar.value = "Guardando...";
    }
    return true;
}
// JS para modulo compras
// Este se carga en todas las vistas , por lo que debemos poner solo
// aquellas funciones que se utilizan en todas las vistas.
// La funciones particulares de cada vista se deben poner /js/nombredecadavista
//
// Ya existe un fichero AccionesDirectas.js que tiene acciones directa controlador teclado
// aunque están mezcladas.

function addCosteProveedor(idArticulo, valor, nfila, dedonde) {
    // @Objetivo: Añadir o modificar el coste de un producto
    // @Parametros:
    //      idArticulo: el id del articulo del producto
    //      idProveedor: el id del proveedor
    //      valor: valor nuevo
    //      dedonde: donde estamos, si en albaranes o facturas
    //      nfila: número de la fila que estamos cambiando
    console.log("Entre en addCosteProveedor");
    productos[nfila].ultimoCoste = sanitizarDecimal(valor);
    recalculoImporte(productos[nfila].nunidades, nfila);
    addTemporal(dedonde);
}

function buscarAdjunto(dedonde, valor = "") {
    //@Objetivo:
    //  Cada vez que vamos a adjuntar un pedido/albarann a un albaran/factura ejecutamos esta función que
    //  carga tanto los productos del adjunto como realiza la comprobación de si ya existe ....
    //@Parametros:
    //  dedonde:desde donde estamos ejecutando la función
    //  valor: numero de pedido o albarán que vamos a adjuntar
    console.log("Entre en buscarAdjunto");
    return new Promise((resolve, reject) => {
        var parametros = {
            pulsado: "buscarAdjunto",
            numAdjunto: valor,
            idProveedor: cabecera.idProveedor,
            dedonde: dedonde,
        };
        $.ajax({
            data: parametros,
            url: "tareas.php",
            type: "post",
            beforeSend: function () {
                console.log(
                    "******** estoy en buscar adjunto JS****************",
                );
            },
            success: function (response) {
                console.log("Llegue devuelta respuesta de buscar adjunto");
                var resultado = $.parseJSON(response);
                var HtmlAdjuntos = resultado.html;
                if (resultado.error) {
                    alert(resultado.error + "\n" + resultado.consulta);
                } else {
                    if (resultado.Nitems !== 1) {
                        // Si cja adjunto esta vacia o pulsos en lupa.
                        // Debemos mostrar el modal con los datos qumostramos el modal con los pedidos de ese proveedor
                        if (dedonde == "albaran") {
                            var titulo = "Listado Pedidos ";
                        } else {
                            var titulo = "Listado Albaranes";
                        }
                        abrirModal(titulo, HtmlAdjuntos);
                    } else {
                        // Comprobamos que el adjunto que vamos añadir, no este ya añadido en este pedido.
                        // Ya que podríamos tenerlo como marcado eliminado.
                        var bandera = 0;
                        if (dedonde == "albaran") {
                            var adjuntos = pedidos;
                        } else {
                            var adjuntos = albaranes;
                        }
                        for (i = 0; i < adjuntos.length; i++) {
                            // Recorre todo el array de arrays de pedidos que existan actualmente en pedido.
                            var num_adjunto_actual = adjuntos[i].NumAdjunto;
                            var numeroNuevo = resultado["datos"].NumAdjunto;
                            if (num_adjunto_actual == numeroNuevo) {
                                bandera = bandera + 1; // Para que no añada adjunto , ni productos.
                                alert(
                                    " Ya existe este adjunto en este " +
                                        dedonde,
                                );
                            }
                        }
                        if (bandera == 0) {
                            var datos = [];
                            datos = resultado["datos"];
                            n_item = parseInt(adjuntos.length) + 1;
                            datos.nfila = n_item;
                            if (dedonde == "albaran") {
                                pedidos.push(datos);
                            } else {
                                albaranes.push(datos);
                            }
                            productosAdd = resultado.productos;
                            var prodArray = new Array();
                            for (i = 0; i < productosAdd.length; i++) {
                                // Array de arrays de productos metemos los productos de ese pedido
                                // cargamos todos los datos en un objeto y por ultimo lo añadimos a los productos que ya tenemos
                                var prod = {
                                    articulo_name: productosAdd[i].cdetalle,
                                    codBarras: productosAdd[i].ccodbar,
                                    ref_prov: productosAdd[i].ref_prov,
                                    crefTienda: productosAdd[i].cref,
                                    idArticulo: productosAdd[i].idArticulo,
                                    iva: productosAdd[i].iva,
                                    coste: productosAdd[i].costeSiva,
                                    unidades: productosAdd[i].nunidades,
                                    estado: productosAdd[i].estadoLinea,
                                };
                                prod = new ObjProducto(prod);
                                if (dedonde == "albaran") {
                                    prod.numPedido = productosAdd[i].Numpedpro;
                                    prod.idpedpro = productosAdd[i].idpedpro;
                                } else {
                                    prod.numAlbaran = productosAdd[i].Numalbpro;
                                    prod.idalbpro = productosAdd[i].idalbpro;
                                }
                                var numAdjunto = resultado["datos"].NumAdjunto;
                                var idAdjunto = resultado["datos"].idAdjunto;
                                productos.push(prod);
                                prodArray.push(prod);
                            }

                            //  Cambiamos el estado del adjunto, para ponerlo como Facturado, para que no puedas ser añadido.
                            modificarEstado(dedonde, "Facturado", idAdjunto);
                            //Agregamos una nueva fila en adjunto con los datos principales
                            AgregarAdjunto(datos, dedonde);
                            // Agregamos filas de productos pero con la cabecera del adjunto.
                            AgregarFilasProductos(prodArray, dedonde, datos);
                            // Creamos el temporal.
                            addTemporal(dedonde);
                            //Cierro el modal aqui porque cuando selecciono un pedido del modal llamo a esta misma funcion
                            //Cuando se mete el numero del pedido de esta manera el valor de busqueda ya es un numero
                            // y no vuelve a mostrar el modal,no entra en la segunda parte del if que tenemos mas arriba
                            cerrarPopUp();
                            resolve(resultado);
                        }
                    }
                }
            },
            error: function (err) {
                reject(err);
            },
        });
    });
}

function modificarEstado(dedonde, estado, id = "") {
    //~ @Objetivo:
    // Modificar el estado según el id que llegue y de donde para poder filtrar
    //~ @Parametros : el estado se envia en la función
    console.log("Entre en modificar estado pedido");
    var parametros = {
        pulsado: "modificarEstado",
        id: id,
        estado: estado,
        dedonde: dedonde,
    };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        beforeSend: function () {
            console.log(
                "******** estoy en Modificar estado pedido js****************",
            );
        },
        success: function (response) {
            console.log("Llegue devuelta respuesta de estado pedido js");
            var resultado = $.parseJSON(response);
            if (resultado.error) {
                alert("Error de SQL" + respuesta.consulta);
            }
        },
    });
}

function modalAlbaranesCambioEstado() {
    // @ Objetivo:
    // Abre Modal para Seleccionar el nuevo estado a poner en la seleccion listado
    // @ Parametros:
    // No hay
    // @ Devuelve:
    // Modal con select de posibles estados.
    var parametros = {
        pulsado: "modalAlbaranesCambioEstado",
    };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        beforeSend: function () {
            console.log(
                "********* envio para mostrar el modal para  cambiar estado albaran **************",
            );
        },
        success: function (response) {
            console.log(
                "Respuesta de mostrar modal para cambiar estado albaran ",
            );
            var resultado = $.parseJSON(response);
            var titulo = "Cambiar estado Albaranes ";
            abrirModal(titulo, resultado.html);
        },
    });
}

function modalImportarAlbaranes() {
    var parametros = {
        pulsado: "modalImportarAlbaranes",
    };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        beforeSend: function () {
            console.log(
                "********* envio para mostrar el modal para  cambiar estado albaran **************",
            );
        },
        success: function (response) {
            console.log(
                "Respuesta de mostrar modal para cambiar estado albaran ",
            );
            var resultado = $.parseJSON(response);
            var titulo = "Cambiar estado Albaranes ";
            abrirModal(titulo, resultado.html);
        },
    });
}

function metodoClick(pulsado, adonde = "") {
    // @ Objetivo:
    // Metodo para saber que pulso y ver item tenemos seleccionado y saber que hacer.
    // @ Parametro:
    // Son string los dos parametros (pulsado,adonde)
    // adonde si no viene esta vacio.
    VerIdSeleccionado(); // Cargamos array de id seleccionados ;

    console.log("Inicimos switch de control tras pulsar:" + pulsado);
    switch (pulsado) {
        case "Ver":
        case "Modificar":
            // ver la ayuda https://developer.mozilla.org/es/docs/Web/JavaScript/Referencia/Sentencias/switch
            // para entender case;
            if (checkID.length > 1 || checkID.length === 0) {
                alert(
                    "Que items tienes seleccionados? \n Solo puedes tener uno seleccionado",
                );
                return;
            }
            var accion = "";

            if (pulsado == "Ver") {
                accion = "&accion=ver";
            } else {
                accion = "&accion=editar";
            }
            // Ahora Redirijo al id de adonde conla accion que indiquemos
            window.location.href =
                "./" + adonde + ".php?id=" + checkID[0] + accion;
            break;
        case "cambiarEstado":
            if (checkID.length === 0) {
                alert("No tienes items seleccionados");
                return;
            }
            // Ahora deberíamos controlar a donde..
            if (adonde == "albaranes") {
                console.log("Entro en cambio estado albaran");
                modalAlbaranesCambioEstado(checkID);
            }
            break;
    }
}

function imprimir(id, dedonde, idTienda) {
    // @Objetivo: Imprimir el documento que se ha seleccionado
    // @parametros:
    // id: id del documento
    // dedonde: de donde es para poder filtrar
    // idTienda : id de la tienda
    var parametros = {
        pulsado: "datosImprimir",
        dedonde: dedonde,
        id: id,
        idTienda: idTienda,
    };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        beforeSend: function () {
            console.log("******** estoy en datos Imprimir JS****************");
        },
        success: function (response) {
            var resultado = $.parseJSON(response);
            window.open(resultado); // Abre una nuvea pestaña con el documento pdf que se generó anteriormente
        },
    });
}

function exportarXML(id, dedonde, idTienda) {
    // @Objetivo: Exportar el documento que se ha seleccionado en formato XML
    var parametros = {
        pulsado: "datosExportarXML",
        dedonde: dedonde,
        id: id,
        idTienda: idTienda,
    };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        beforeSend: function () {
            console.log(
                "******** estoy en datos Exportar XML JS****************",
            );
        },
        success: function (response) {
            var resultado = $.parseJSON(response); // ruta del XML en el servidor

            // Preguntar al usuario si quiere descargar
            if (confirm("El XML está listo. ¿Deseas descargarlo?")) {
                // Crear un enlace dinámico para forzar la descarga
                var a = document.createElement("a");
                a.href = resultado; // ruta del XML
                a.download = dedonde + id + ".xml"; // nombre sugerido
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
            location.reload();
        },
        error: function (err) {
            console.error("Error al generar el XML", err);
        },
    });
}

function ampliarInformacionImportar(opcion) {
    var parametros = {
        pulsado: "modal" + opcion,
    };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        beforeSend: function () {
            console.log(
                "********* envio para mostrar el modal para  importar albaranes **************",
            );
        },
        success: function (response) {
            console.log("Respuesta de mostrar modal para importar albaranes ");
            // la respuesta se pone dentro del id areaImportacionAlbaranes
            response = $.parseJSON(response);
            document.getElementById("areaImportacionAlbaranes").innerHTML =
                response.html;
        },
    });
}

function importarAlbaranCierreAno() {
    var form = document.getElementById("formImportarAlbaranCierreAno");
    var formData = new FormData(form);
    formData.append("pulsado", "importarAlbaranCierreAno");

    $.ajax({
        url: "tareas.php",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        beforeSend: function () {
            console.log("Importando albarán cierre año...");
        },
        success: function (resultado) {
            if (resultado.ok) {
                $("#areaAlbaranCierreSeleccionado").html(
                    '<div class="alert alert-success">' +
                        resultado.message +
                        "</div>",
                );

                // Si quieres refrescar, hazlo con intención
                setTimeout(() => location.reload(), 1500);
            } else {
                $("#areaAlbaranCierreSeleccionado").html(
                    '<div class="alert alert-danger">' +
                        resultado.message +
                        "</div>",
                );
            }
        },
        error: function (xhr) {
            let msg = "Error inesperado al importar el albarán";

            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }

            $("#areaAlbaranCierreSeleccionado").html(
                '<div class="alert alert-danger">' + msg + "</div>",
            );
        },
    });
}

function formularioEnvioEmail(id, dedonde, idTienda, destinatario) {
    var parametros = {
        pulsado: "obtenerFormularioEmail",
        idTienda: idTienda,
        dedonde: dedonde,
        id: id,
        destinatario: destinatario,
    };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        beforeSend: function () {
            console.log(
                "******** estoy en datos obtenerFormularioEmail JS****************",
            );
        },
        success: function (response) {
            var resultado = $.parseJSON(response);
            abrirModal("Enviar por email el " + dedonde, resultado.html); // Abre una ventana y muestra el texto
        },
        error: function (request) {
            console.log(request);
        },
    });
}

function enviarCorreo(f) {
    console.log($("#FormEmail"));
    datos = $("#FormEmail").serialize();
    cerrarPopUp();
    $.post("tareas.php", datos, function (res) {
        var resultado = $.parseJSON(res);
        // cerramos modal.

        titulo = "Envio de email";
        if (
            resultado.envio_destinatario === "OK" &&
            resultado.subido_enviados == "OK"
        ) {
            contenido =
                '<div class="alert alert-info">Fue enviado correctame y subido como enviado nuestro email correctamente</div>';
            // Debemos cambiar el estado pedido y cerrar ventanama
        } else {
            contenido_inicio =
                '<div class="alert alert-danger">Hubo en error al enviarlo<br/>';
            contenido_enviado =
                " Envio destino:" + resultado.envio_destinatario + "<br/>";
            contenido_subido =
                "Subida a nuestro email:" + resultado.subido_enviados + "<br/>";
            contenido_final = "</div>";
            contenido =
                contenido_inicio +
                contenido_enviado +
                contenido_subido +
                contenido_final;
            // Hay que ver que fallo y informar del fallo.
        }
        console.log(resultado);
        respuesta_email(titulo, contenido);
    });
}

function respuesta_email(titulo, contenido) {
    abrirModal(titulo, contenido);
}

function buscarProveedor(dedonde, idcaja, valor = "", popup = "") {
    // @Objetivo: Buscar y comprobar que la busqueda de proveedor es correcta
    // @parametros:
    //      dedonde -> De donde venimos
    //      idCaja  -> La utilizamos en tareas para comprobaciones
    //      valor   -> valor que vamos a buscar
    //      popup   -> si viene de popup cerramos la ventana modal
    console.log("FUNCION buscarProveedores JS-AJAX");
    var parametros = {
        pulsado: "buscarProveedor",
        busqueda: valor,
        dedonde: dedonde,
        idcaja: idcaja,
    };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        beforeSend: function () {
            console.log(
                "******** estoy en buscar Proveedor JS****************",
            );
        },
        success: function (response) {
            console.log("Llegue devuelta respuesta de buscar Proveedor");
            var resultado = $.parseJSON(response);
            if (resultado.error) {
                alert("Error de sql :" + resultado.consulta);
                return;
            }
            if (resultado.Nitems == 1 && resultado.html == null) {
                // Si es solo un resultado pone en la cabecera idProveedor ponemos el id devuelto
                //Desactivamos los input para que no se puede modificar y en el nombre mostramos el valor
                //Se oculta el botón del botón buscar
                cerrarPopUp();
                cabecera.idProveedor = resultado.id;
                $("#id_proveedor").val(resultado.id);
                $("#Proveedor").val(resultado.nombre);
                $("#Proveedor").prop("disabled", true);
                $("#id_proveedor").prop("disabled", true);
                $("#buscar").css("display", "none");

                //Dendiendo de donde venga realizamos unas funciones u otras
                if (dedonde == "albaran" || dedonde == "factura") {
                    comprobarAdjunto(dedonde);
                }
                if (dedonde == "pedido") {
                    // Si viene de pedido ponemos el foco en idArticulo ya que pedidos no tiene que comprobar nada
                    //Para poder empezar a meter articulos
                    ponerFocus("idArticulo");
                }
                mostrarFilaProveedor(dedonde);
            } else {
                //Si no mostramos un modal con los proveedores según la busqueda
                var titulo = "Listado Proveedores ";
                var HtmlProveedores = resultado.html["html"];
                abrirModal(titulo, HtmlProveedores);
                if (idcaja !== "cajaBusquedaproveedor") {
                    focusAlLanzarModal("cajaBusquedaproveedor");
                } else {
                    // Vine modal , por lo que debemos saber si tiene resultado y poner focus en el primero
                    if (resultado.Nitems > 0) {
                        ponerFocus("N_0");
                    }
                }
            }
        },
    });
}

function comprobarAdjunto(dedonde) {
    //@Objetivo:
    // Comprobamos si el proveedor seleccionado tiene algun pedido o albaran, en estado Guardado que se pueda adjuntar.
    console.log("Entre en adjunto proveedor");
    var parametros = {
        pulsado: "comprobarAdjunto",
        idProveedor: cabecera.idProveedor,
        dedonde: dedonde,
    };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        beforeSend: function () {
            console.log("******** Voy acomprobarAdjunto ****************");
        },
        success: function (response) {
            console.log("Llegue de comprobar adjunto");
            var resultado = $.parseJSON(response);
            if (resultado.error) {
                alert(resultado.error);
            } else {
                if (resultado.bandera == 1) {
                    // Ponemos focus en entrada adjunto.
                    mostrarDivAdjunto();
                    if (dedonde == "factura") {
                        ponerFocus("numPedido");
                    } else {
                        ponerFocus("suNumero");
                    }
                }
            }
        },
    });
}

function comprobarFecha(caja, event) {
    //@Objetivo
    // Comprobar que la fecha que contiene la cja no es superior la de hoy
    var hoy = new Date();
    hoy.setHours(0, 0, 0, 0); // para poner 0 la hora
    var dia_caja = caja.darValor().split("-"); // Obtengo array (dia,mes,anho)
    var fecha_caja = new Date(
        dia_caja[2] + "-" + dia_caja[1] + "-" + dia_caja[0],
    );
    fecha_caja.setHours(0, 0, 0, 0); // al crearlo me pone la 1 , no se porque
    if (isNaN(fecha_caja.getTime())) {
        alert("La fecha no es correcta");
    } else {
        if (fecha_caja > hoy) {
            alert("La fecha es superior a hoy");
        } else {
            if (productos.length == 0) {
                saltarHora(caja);
            } else {
                ponerFocus(salto_linea);
            }
            cabecera.fecha = caja.darValor();
        }
    }
}

function comprobarDecimalNumber(value) {
    // Comprobamos que sea un numero o decimal.
    // Sanitizamos coma decimal -> punto antes de evaluar.
    valor = sanitizarDecimal(value) * 1;
    if (isNaN(valor)) {
        valor = false;
    }
    return valor;
}

/**
 * Convierte coma decimal a punto y elimina separadores de miles.
 * Ej: "1.000,50" → "1000.50", "3,5" → "3.5", "3.5" → "3.5"
 */
function sanitizarDecimal(valor) {
    if (typeof valor !== "string") {
        valor = String(valor);
    }
    // Si tiene coma y punto, asumimos formato europeo: 1.000,50
    if (valor.indexOf(".") !== -1 && valor.indexOf(",") !== -1) {
        // Quitar puntos de miles, coma -> punto decimal
        valor = valor.replace(/\./g, "").replace(",", ".");
    } else if (valor.indexOf(",") !== -1) {
        // Solo coma: reemplazar por punto
        valor = valor.replace(",", ".");
    }
    return valor;
}

function AntesAgregarFilaProducto(
    datos,
    dedonde,
    fecha_actualizacion,
    coste_tabla_articulo,
) {
    // @ Objetivo
    // Comprobamos:
    //  - Si un producto nuevo para ese proveedor.
    //  - Si el coste se cambio con fecha posterior a la fecha del albaran.
    // Luego agregamos linea  o no.
    var opcion = true;
    if (datos.ultimoCoste == 0 || datos.ultimoCoste == null) {
        datos.getCoste(coste_tabla_articulo);
        // Si contesta NO, no lo añade al dedonde
        var nlen = dedonde.length - 1; // le quito la ultima letra, para que no ponga (s)
        var txtDonde = dedonde.substring(0, nlen);
        opcion = confirm(
            "¡OJO!\nEste producto es NUEVO para este proveedor \n Si (cancelas) no lo añade al " +
                txtDonde,
        );
    }
    if (opcion === true) {
        productos.push(datos);
        addTemporal(dedonde);
        document.getElementById(id_input).value = "";

        if (fecha_actualizacion != null) {
            fechaProducto = fecha_actualizacion.split("-");
            fechaProducto = new Date(
                fechaProducto[2],
                fechaProducto[1] - 1,
                fechaProducto[0],
            );
            fechaCabecera = cabecera.fecha.split("-");
            fechaCabecera = new Date(
                fechaCabecera[2],
                fechaCabecera[1] - 1,
                fechaCabecera[0],
            );
            console.log("fechaCabecera" + fechaCabecera);
            console.log("fechaProducto" + fechaProducto);

            if (fechaProducto > fechaCabecera) {
                alert(
                    "El producto que vas a añadir tiene un coste que fue actualizado con fecha superior a la del albarán",
                );
            }
        }
        //  Añado linea de producto.
        AgregarFilasProductos(datos, dedonde);
        // ¿¿¿ Creo que no permitimos entonces tabla para añadir albaranes...
        if (dedonde == "factura") {
            $("#tablaAl").hide();
        }
    }
}

function AgregarFilasProductos(datos, dedonde, cabecera = "NO") {
    //@objetivo:
    //Agregar la fila de productos
    console.log("Estoy en agregar fila productos albaran");
    if (datos.length > 1) {
        datos = datos.reverse();
    }
    var parametros = {
        pulsado: "htmlAgregarFilasProductos",
        productos: JSON.stringify(datos),
        dedonde: dedonde,
        cabecera: cabecera,
    };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        beforeSend: function () {
            console.log(
                "******** estoy en escribir html fila pedidos JS****************",
            );
        },
        success: function (response) {
            console.log("Llegue devuelta respuesta de html fila pedidos");
            console.log(datos.campo);
            var resultado = $.parseJSON(response);
            var nuevafila = resultado["html"];
            $("#tabla").prepend(nuevafila);
            if (dedonde == "factura") {
                if (albaranes.length > 0) {
                    bloquearInput();
                }
            }
            ponerSelect("Unidad_Fila_" + datos.nfila);
        },
    });
}

function bloquearInput() {
    $("#Row0").css("display", "none");
    $(".unidad").attr("readonly", "readonly");
}

function addTemporal(dedonde = "") {
    //@Objetivo: añadir un temporal , dependiendo de donde venga se cargan unos parámetros distintos
    //@parámetros:
    //dedonde: de donde venimos , pedidos, albaran, factura
    // Guard: en modo visualización NO se permite crear/modificar temporales
    if (window.modoVisualizacion === true) {
        console.warn("Modo visualización activo — addTemporal() bloqueado.");
        return;
    }
    console.log("FUNCION Añadir temporal JS-AJAX");
    if (dedonde == "pedido") {
        var pulsado = "addPedidoTemporal";
    }
    if (dedonde == "albaran") {
        var pulsado = "addAlbaranTemporal";
    }
    if (dedonde == "factura") {
        var pulsado = "addFacturaTemporal";
    }

    var parametros = {
        pulsado: pulsado,
        idTemporal: cabecera.idTemporal,
        idUsuario: cabecera.idUsuario,
        idTienda: cabecera.idTienda,
        estado: cabecera.estado,
        idReal: cabecera.idReal,
        fecha: cabecera.fecha,
        productos: JSON.stringify(productos),
        idProveedor: cabecera.idProveedor,
        hora: cabecera.hora,
    };
    // Enviar ajustes de céntimos si existen
    var ajustesJSON = getAjustesCentimosJSON();
    if (ajustesJSON) {
        parametros["ajustesCentimos"] = ajustesJSON;
    }
    if (dedonde == "albaran") {
        parametros["pedidos"] = pedidos;
        parametros["suNumero"] = cabecera.suNumero;
    }
    if (dedonde == "factura") {
        parametros["albaranes"] = albaranes;
        parametros["suNumero"] = cabecera.suNumero;
    }
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        beforeSend: function () {
            console.log(
                "******** Estoy funciones.js y voy añadir PEDIDO temporal JS****************",
            );
        },
        success: function (response) {
            console.log("== Respuesta de añadir temporal ==");
            var resultado = $.parseJSON(response);
            if (resultado.error) {
                // Error puede ser array
                var errores = resultado.error;
                errores.forEach(function (error) {
                    console.log(error.mensaje);
                });
                alert(JSON.stringify(resultado.error));
            } else {
                if (resultado.id > 0) {
                    history.pushState(null, "", "?tActual=" + resultado.id);
                    $("input[name='idTemporal']").val(resultado.id);
                    if (cabecera.idTemporal == 0) {
                        // En estado Nuevo de pedido, hay que quitar el style atributo btn-guardar.
                        $("#bGuardar").removeAttr("style");
                    }
                    cabecera.idTemporal = resultado.id;
                    if (cabecera.estado === "Guardado") {
                        // En estado Guardado, cambiamos el estado a "Sin Guardar", tanto en variable como en caja.
                        cabecera.estado = "Sin Guardar";
                        document.getElementById("estado").value = "Sin Guardar";
                    }
                }
                // Creo funcion para restear totales.
                total = parseFloat(resultado["totales"]["total"]);
                $("#tabla-pie  > tbody ").html(resultado["htmlTabla"]);
                // Cargar ajustes devueltos por el servidor
                if (resultado["ajustesCentimos"]) {
                    cargarAjustesCentimos(resultado["ajustesCentimos"]);
                }
            }
        },
    });
}

function ponerFocus(destino_focus) {
    // @ Objetivo:
    //  Poner focus a donde nos indique el parametro, que debe ser id queremos apuntar.
    setTimeout(function () {
        //pongo un tiempo de focus ya que sino no funciona correctamente
        jQuery("#" + destino_focus.toString()).focus();
    }, 50);
}

function ponerSelect(destino_focus) {
    // @ Objetivo:
    //  Poner focus a donde nos indique el parametro, que debe ser id queremos apuntar.
    setTimeout(function () {
        //pongo un tiempo de focus ya que sino no funciona correctamente
        jQuery("#" + destino_focus.toString()).select();
    }, 50);
}

function saltarHora(caja) {
    if (caja.id_input == "fecha") {
        cabecera.fecha = caja.darValor();
    }
    var d_focus = "hora";
    if (caja.darParametro("dedonde") !== "albaran") {
        var d_focus = "Proveedor";
    }
    ponerFocus(d_focus);
}

function escribirProductoSeleccionado(
    campo,
    cref,
    cdetalle,
    ctipoIva,
    ccodebar,
    ultimoCoste,
    id,
    dedonde,
    ref_prov,
    coste,
    fecha_actualizacion,
) {
    //@ Objetivo:
    // Escribir y añadir el producto seleccionado en el modal
    //@ Parametos:
    // Recibimos los campos del producto que hemos seleccionado, luego cremoas el objeto producto
    // añadiendo los campos que faltan como importe que tenemos cacularlo.
    var objDatos = {
        codBarras: ccodebar.toString(),
        articulo_name: cdetalle.toString(),
        crefTienda: cref.toString(),
        idArticulo: id.toString(),
        iva: ctipoIva.toString(),
        ref_prov: ref_prov.toString(),
        coste: coste.toString(),
        ultimoCoste: ultimoCoste,
    };
    var datos = new ObjProducto(objDatos);
    cerrarPopUp();
    // La fecha actualizacion viene en formato AAAA-MM-DD , tengo cambiarlo porque la funcion siguiente
    // lo gestion al reves.
    if (fecha_actualizacion != "") {
        f = fecha_actualizacion.split("-");
        fecha_actualizacion = f[2] + "-" + f[1] + "-" + f[0];
    }
    AntesAgregarFilaProducto(datos, dedonde, fecha_actualizacion, ultimoCoste);
}

function cambioEstadoFila(producto, dedonde = "") {
    // @Objetivo
    // Cambiamos el estado fila a eliminado (tachado) o no una fila.
    // @Parametros
    //    producto -> objeto del producto queremos cambiar ( YA viene cambiado el estado solo pintamos. )
    //    dedonde  -> indicando si es pedido, albaran o factura.
    console.log("Entro en cambiar Estado Fila");
    line = "#Row" + producto.nfila;
    if (producto.estado === "Eliminado") {
        $(line).addClass("tachado");
        $(line + "> .eliminar").html(
            '<a onclick="retornarFila(' +
                producto.nfila +
                ", " +
                "'" +
                dedonde +
                "'" +
                ');"><span class="glyphicon glyphicon-export"></span></a>',
        );
        $("#N" + producto.nfila + "_Unidad").prop("disabled", true);
    } else {
        $(line).removeClass("tachado");
        $(line + "> .eliminar").html(
            '<a onclick="eliminarFila(' +
                producto.nfila +
                " , " +
                "'" +
                dedonde +
                "'" +
                ');"><span class="glyphicon glyphicon-trash"></span></a>',
        );
        $("#Unidad_Fila_" + producto.nfila).prop("disabled", false);
        $("#N" + producto.nfila + "_Unidad").prop("disabled", false);
        $("#N" + producto.nfila + "_Unidad").val(producto.nunidades);
    }
}

function eliminarFila(num_item, dedonde = "") {
    //@Objetivo:
    //Función para cambiar el estado del producto , deja en estado Eliminado el producto
    console.log("entre en eliminar Fila Producto");
    var num = num_item - 1;
    productos[num].estado = "Eliminado";
    cambioEstadoFila(productos[num], dedonde);
    addTemporal(dedonde);
}

function eliminarAdjunto(numRegistro, dedonde, nfila) {
    //@ Objetivo:
    // Function cuando pulsamos en eliminar un pedido o albaran adjunto en albaranes o facturas respectimente.
    // Marca como elimimanos los adjunto y pone tambien los productos que fueron añadidos con ese adjunto.
    //@ Parámetros:
    //  numRegistro: número del adjunto (pedido o albaran)
    //  dedonde: de donde venimos  albaran o factura
    //  nfila: número de la fila del adjunto.
    console.log("entre en eliminar Fila");
    var num = nfila - 1;
    if (dedonde == "factura") {
        var num_fila = albaranes[num].nfila;
        albaranes[num].estado = "Eliminado";
        var idAdjunto = albaranes[num].idAlbaran;
    }
    if (dedonde == "albaran") {
        var num_fila = pedidos[num].nfila;
        pedidos[num].estado = "Eliminado";
        var idAdjunto = pedidos[num].idPedido;
    }
    var line = "#lineaP" + num_fila;
    $(line).addClass("tachado");
    $(line + "> .eliminar").html(
        '<a onclick="retornarAdjunto(' +
            numRegistro +
            ", " +
            "'" +
            dedonde +
            "'," +
            nfila +
            ');"><span class="glyphicon glyphicon-export"></span></a>',
    );
    // Ahora cambiamos estado poniendo 'Eliminando' de todos los productos de ese adjunto.
    cambiarEstadoProductosAdjunto(dedonde, "Eliminado", numRegistro);
    // Ahora cambiamos estado de adjunto a Guardado, ya que debería tener como facturado.
    modificarEstado(dedonde, "Guardado", numRegistro, idAdjunto);
    // Creamos temporal para quede guardado
    addTemporal(dedonde);
}

function eliminarTemporal(id_temporal, dedonde) {
    // @ Objetivo:
    // Eliminarle temporal que indicamos, llega de lista de pedido,albaranes o facturas.
    var parametros = {
        pulsado: "eliminarTemporal",
        id_temporal: id_temporal,
        dedonde: dedonde,
    };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        beforeSend: function () {
            console.log("******** Estoy en eliminar temporal****************");
        },
        success: function (response) {
            console.log("Llegue devuelta de eliminar temporal");
            var resultado = $.parseJSON(response);
            if (resultado.valores_insert) {
                alert("Fue eliminado correctamente");
                // Funcion para recargar pagina.
                location.reload(true);
            } else {
                alert("Ocurrio un error:" + response);
            }
        },
    });
}

function retornarFila(num_item, dedonde = "") {
    // @Objetivo :
    // Es pasar un producto eliminado a activo.
    console.log("entre en retornar fila producto");
    var num = num_item - 1;
    // Nueva Objeto de productos.
    productos[num].estado = "Activo";
    if (productos[num].nunidades == 0) {
        // Nueva Objeto de productos.
        productos[num].nunidades = 1;
    }
    cambioEstadoFila(productos[num], dedonde);
    addTemporal(dedonde);
}

function retornarAdjunto(numRegistro, dedonde, nfila) {
    //@ Objetivo:
    //  Vuelve activar adjunto (pedido o albaran) que tenemos en adjuntos como estado eliminado, en un albaran o factura.
    //@ Parámetros:
    // nunRegitro: número de adjunto para activar.
    // dedonde: Indica donde estamos en albaran o factura
    // nfila: número de la fila del adjunto.
    //@ Nota informativa:
    // Lo correcto sería comprobar antes que el estado de ese adjunto es correcto, ya que puede ser que ya se haya
    // adjuntado.(Evitamos posibles errores).
    console.log("entre en retornar fila adjunto");
    var num = nfila - 1;
    if (dedonde == "factura") {
        var num_fila = albaranes[num].nfila;
        albaranes[num].estado = "activo";
        var idAdjunto = albaranes[num].idAlbaran;
    }
    if (dedonde == "albaran") {
        var num_fila = pedidos[num].nfila;
        pedidos[num].estado = "activo";
        var idAdjunto = pedidos[num].idPedido;
    }
    var line = "#lineaP" + num_fila;
    $(line).removeClass("tachado");
    $(line + "> .eliminar").html(
        '<a onclick="eliminarAdjunto(' +
            numRegistro +
            " , " +
            "'" +
            dedonde +
            "', " +
            nfila +
            ');"><span class="glyphicon glyphicon-trash"></span></a>',
    );
    // Ahora cambiamos el estado de todos los productos del adjunto
    cambiarEstadoProductosAdjunto(dedonde, "Activo", numRegistro);
    modificarEstado(dedonde, "Facturado", numRegistro, idAdjunto);
    addTemporal(dedonde);
}

function cambiarEstadoProductosAdjunto(dedonde, estado, numRegistro) {
    // @ Objetivo:
    // Cambiar el estado de los productos de un adjunto de un albaran o de una factura, que puede ser un pedido o albaran
    // @ Parametros:
    // dedonde -> Si estoy en albaranes o facturas
    // estado -> Que queremos poner a los productos de ese adjunto. (Activo o Eliminado)
    // numRegistro -> Numero de del adjunto.
    console.log("Entro en cambiarEstadoProductosAdjuntos:" + numRegistro);
    for (i = 0; i < productos.length; i++) {
        if (dedonde == "albaran") {
            var numAdjunto_Producto = productos[i].idpedpro;
        } else {
            var numAdjunto_Producto = productos[i].idalbpro;
        }
        if (numRegistro == numAdjunto_Producto) {
            productos[i].estado = estado;
            cambioEstadoFila(productos[i], dedonde);
        }
    }
}
function cambiarEstadoVariosAlbaranes() {
    // @ Objetivo:
    // Cambiar estado de varios albaranes de Listados.
    // @ Parametros :
    // Array con uno o varios ids de albaranes a cambiar estado.
    console.log("Entro en cambiar estado de varios albaranes");
    var estado = $("select[id=Nuevo_estado_albaranes]").val();
    VerIdSeleccionado(); // Cargamos array de id seleccionados ;
    var parametros = {
        pulsado: "cambiarEstadoVariosAlbaranes",
        ids: checkID,
        estado: estado,
        dedonde: "Albaranes",
    };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        beforeSend: function () {
            console.log(
                "******** Mando ids de albaranes para cambiar estado****************",
            );
        },
        success: function (response) {
            console.log("Respuesta despues de cambio estado de albaranes");
            var resultado = $.parseJSON(response);
            console.log(" Si devuelve nulos es que fue ok");
            cerrarPopUp();
            location.reload(); // recargo pagina.
        },
    });
}

function recalculoImporte(cantidad, num_item, dedonde = "") {
    // @ Objetivo:
    // Recalcular el importe de la fila, si la cantidad cambia.
    // @ Parametros:
    //  cantidad -> Valor ( numerico) de input unidades.
    //  num_item -> El numero que indica el producto que modificamos.
    console.log("Entre recalculoImporte:" + cantidad, num_item);

    // Sanitizar valores: coma → punto para evitar problemas de locale
    cantidad = parseFloat(sanitizarDecimal(cantidad)) || 0;
    var coste =
        parseFloat(sanitizarDecimal(productos[num_item].ultimoCoste)) || 0;

    productos[num_item].nunidades = cantidad;
    productos[num_item].importe = coste * cantidad;
    var N_fila = "#N" + productos[num_item].nfila;
    $(N_fila + "_Importe").html(productos[num_item].importe.toFixed(2));

    var iva = productos[num_item].importe * (productos[num_item].iva / 100);
    var importeIva = productos[num_item].importe + parseFloat(iva);
    $(N_fila + "_ImporteIva").html(importeIva.toFixed(2));

    // Comprobamos que cantidad y nunidades para saber si activamos o desactivamos linea
    if (productos[num_item].nunidades == 0 && cantidad != 0) {
        retornarFila(num_item + 1, dedonde);
    } else if (cantidad == 0) {
        eliminarFila(num_item + 1, dedonde);
    }
}

function after_constructor(padre_caja, event) {
    console.log(padre_caja);
    // @ Objetivo:
    // Ejecuta procesos antes construir el obj. caja.
    // Traemos
    //      (objeto) padre_caja -> Que es objeto el padre del objeto que vamos a crear
    //      (objeto) event -> Es la accion que hizo, que trae todos los datos input,button , check.
    if (padre_caja.parametros.prefijo) {
        // Si tiene prefijo quiere decir que es una lista, obtenemos id.
        var id = ObtenerIdString(
            padre_caja.parametros.prefijo,
            padre_caja.id_input,
        );
        if (id > -1) {
            //padre_caja.id_input = event.originalTarget.id; // Solo funciona en Mozilla
            padre_caja.id_input = event.target.id;
        } else {
            console.log(
                "ERROR_After: No se encontro el prefijo:" +
                    padre_caja.parametros.prefijo,
            );
            console.log("en el id:" + padre_caja.id_input);
        }
    }
    return padre_caja;
}

function before_constructor(caja) {
    // @ Objetivo :
    //  Ejecutar procesos para obtener datos despues del construtor de caja.
    //  Estos procesos los indicamos en parametro before_constructor, si hay
    if (caja.parametros.prefijo) {
        // Si tiene prefijo quiere decir que es una lista, obtenemos id.
        var id = ObtenerIdString(caja.parametros.prefijo, caja.id_input);
        if (id > -1) {
            caja.fila = id;
        } else {
            console.log(
                "ERROR_Before: No se encontro el prefijo:" +
                    caja.parametros.prefijo,
            );
            console.log("en el id:" + caja.id_input);
        }
    }
    if (caja.id_input === "cajaBusqueda") {
        if (caja.name_cja === "Codbarras") {
            caja.parametros.campo = cajaCodBarras.parametros.campo;
        }
        if (caja.name_cja === "Referencia") {
            caja.parametros.campo = cajaReferencia.parametros.campo;
        }
        if (caja.name_cja === "Descripcion") {
            caja.parametros.campo = cajaDescripcion.parametros.campo;
        }
    }
    if (caja.id_input.indexOf("ultimo_coste") > -1) {
        console.log("entro en ultimo_coste_");
        //~ No entiendo muy bien porque hace esto.
        caja.parametros.item_max = productos.length;
    }
    return caja;
}

function permitirModificarReferenciaProveedor(idinput) {
    //@Objetivo:
    // modificar el input del id para que se pueda modificar la referencia del proveedor articulo
    console.log("Entre en permitirModificarReferenciaProveedor");
    $("#" + idinput).removeAttr("disabled");
}

function AgregarAdjunto(datos, dedonde) {
    //@ Objetivo:
    //Esta función la utilizamos desde albarán o desde factura
    //Desde albaran es para agregar la fila del pedido seleccionado y desde factura para agregar el albaran
    console.log("Estoy en agregar fila Pedido");
    var parametros = {
        pulsado: "htmlAgregarFilaAdjunto",
        datos: datos,
        dedonde: dedonde,
    };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        beforeSend: function () {
            console.log(
                "******** estoy en escribir html fila pedidos JS****************",
            );
        },
        success: function (response) {
            console.log("Llegue devuelta respuesta de html fila pedidos");
            var resultado = $.parseJSON(response);
            var nuevafila = resultado["html"];
            $("#tablaPedidos").prepend(nuevafila);
            $("#numPedido").focus();
            $("#numPedido").val("");
        },
    });
}

function mostrarFilaProveedor(dedonde) {
    //@Objetivo: Mostrar la fila principal de articulos
    $("#Row0").removeAttr("style");
    console.log(dedonde);
    if (dedonde == "albaran") {
        ponerFocus("suNumero");
    } else {
        ponerFocus(ObtenerFocusDefectoEntradaLinea());
    }
}

function mostrarDivAdjunto() {
    $(".div_adjunto").removeAttr("style");
}

function mover_up(fila, prefijo) {
    var d_focus = prefijo + fila;
    ponerFocus(d_focus);
}

function mover_down(fila, prefijo) {
    var d_focus = prefijo + fila;
    ponerFocus(d_focus);
}

function mensajeCancelar(idTemporal, dedonde) {
    var mensaje = confirm("Estas  seguro que quieres cancelar");
    if (mensaje) {
        if (idTemporal == "0") {
            alert("No puedes BORRAR TEMPORAL si aun no existe, pulsa volver");
        } else {
            var parametros = {
                pulsado: "cancelarTemporal",
                dedonde: dedonde,
                idTemporal: idTemporal,
            };
            $.ajax({
                data: parametros,
                url: "tareas.php",
                type: "post",
                beforeSend: function () {
                    console.log(
                        "*********  Entre en cancelar archivos temporales  ****************",
                    );
                },
                success: function (response) {
                    console.log("REspuesta de cancelar temporales");
                    var resultado = $.parseJSON(response);
                    console.log(resultado);
                    if (resultado.mensaje) {
                        alert(resultado.mensaje + ": " + resultado.dato);
                    } else {
                        switch (dedonde) {
                            case "pedido":
                                location.href = "pedidosListado.php";
                                break;
                            case "albaran":
                                location.href = "albaranesListado.php";
                                break;
                            case "factura":
                                location.href = "facturasListado.php";
                                break;
                        }
                    }
                },
            });
        }
    }
}

function SiTieneValorCajaCabecera(caja) {
    console.log(
        "Estoy en funciones SiTieneValorCaja:Tiene valor cja " + caja.id_input,
    );

    switch (caja.id_input) {
        case "id_proveedor":
            buscarProveedor(
                caja.darParametro("dedonde"),
                caja.id_input,
                caja.darValor(),
            );
            break;
    }
}
function ObtenerCajaSiguiente(idCaja) {
    // @ Objetivo
    //  Obtener cual es la caja siguiente salto
    // @ Parametro
    //   idcaja -> la caja actual.
    // @ Devolvemos
    //   d_focus -> string con id caja siguiente.
    var d_focus = "";
    switch (idCaja) {
        case "idArticulo":
            d_focus = "Referencia";
            break;

        case "Referencia":
            d_focus = "ReferenciaPro";
            break;

        case "ReferenciaPro":
            d_focus = "Codbarras";
            break;

        case "Codbarras":
            d_focus = "Descripcion";
            break;

        case "hora":
            if (productos.length > 0) {
                // Deberia saltar a linea entrada producto por defecto
                d_focus = salto_linea;
            } else {
                d_focus = "Proveedor";
            }
    }
    return d_focus;
}
function cambiarValorSaltoLinea(campo) {
    switch (campo) {
        case "a.idArticulo":
            d_focus = "idArticulo";
            break;
        case "at.crefTienda":
            d_focus = "Referencia";
            break;
        case "p.crefProveedor":
            d_focus = "ReferenciaPro";
            break;
        case "ac.codBarras":
            d_focus = "Codbarras";
            break;
        case "a.articulo_name":
            d_focus = "Descripcion";
            break;
    }
    return d_focus;
}

function ObtenerFocusDefectoEntradaLinea() {
    return salto_linea;
}

function abrirIncidenciasAdjuntas(id, modulo, dedonde) {
    var parametros = {
        pulsado: "abrirIncidenciasAdjuntas",
        id: id,
        modulo: modulo,
        dedonde: dedonde,
    };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        beforeSend: function () {
            console.log(
                "*********  Entre en cancelar archivos temporales  ****************",
            );
        },
        success: function (response) {
            console.log("REspuesta de cancelar temporales");
            var resultado = $.parseJSON(response);
            console.log(resultado);
            if (resultado.error) {
                alert(resultado.consulta);
            } else {
                var titulo = "Listado de incidencias ";
                abrirModal(titulo, resultado.html);
            }
        },
    });
}

async function agregarAlbaranesSecuencial(albaranes) {
    for (let i = 0; i < albaranes.length; i++) {
        await buscarAdjunto("factura", albaranes[i]);
        // Opcional: esperar 0.5s entre cada uno
        await new Promise((res) => setTimeout(res, 500));
    }
}
// =========================== OBJETOS  ===================================
function ObjProducto(datos) {
    // @ Objetivo
    // Crear un obj de las propiedades de un producto
    console.log("Estoy creando objeto producto");
    this.idArticulo = datos.idArticulo;
    this.cref = datos.crefTienda;
    this.ref_prov = datos.ref_prov;
    this.ccodbar = datos.codBarras;
    this.cdetalle = datos.articulo_name;
    this.iva = datos.iva;
    if (datos.unidades === undefined) {
        this.nunidades = "1.00"; // Valor por defecto.
        this.ncant = "1.00"; // Valor por defecto.
    } else {
        this.nunidades = datos.unidades;
        this.ncant = datos.unidades; // Valor por defecto.
    }
    if (datos.estado === undefined) {
        this.estado = "Activo"; // Valor por defecto.
    } else {
        this.estado = datos.estado;
    }
    if (datos.nfila === undefined) {
        // Si no enviamos nfila ,cuanta los productos que existe y añade una fila.
        this.nfila = productos.length + 1;
    } else {
        this.nfila = datos.nfila;
    }
    this.ultimoCoste = sanitizarDecimal(datos.coste);
    // Lógica para CosteAnt:
    // Si el producto es nuevo y no tiene coste anterior, CosteAnt = ultimoCoste
    // Si tiene historial, CosteAnt = datos.CosteAnt (último coste registrado)
    if (typeof datos.CosteAnt !== "undefined" && datos.CosteAnt !== null) {
        this.CosteAnt = sanitizarDecimal(datos.CosteAnt);
    } else {
        // Producto nuevo: CosteAnt igual a ultimoCoste
        this.CosteAnt = this.ultimoCoste;
    }
    var importe = parseFloat(this.ultimoCoste) * parseFloat(this.nunidades);
    this.importe = importe.toFixed(2);
    this.getCoste = function (nuevoCoste) {
        // Metodo para cambiar Coste y ademas importe del producto.
        this.ultimoCoste = sanitizarDecimal(datos.ultimoCoste);
        // Si cambia el coste, actualizamos CosteAnt solo si no existe
        if (typeof this.CosteAnt === "undefined" || this.CosteAnt === null) {
            this.CosteAnt = this.ultimoCoste;
        }
        importe = parseFloat(this.ultimoCoste) * parseFloat(this.nunidades);
        this.importe = importe.toFixed(2);
    };
}

function abrirModalConfig() {
    var parametros = {
        pulsado: "abrirModalConfig",
        titulo: "Configuración de compras",
    };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        beforeSend: function () {
            console.log(
                "*********  Entre en abrir modal de configuración  ****************",
            );
        },
        success: function (response) {
            console.log("REspuesta de abrir modal de configuración");
            var resultado = $.parseJSON(response);
            console.log(resultado);
            if (resultado.error) {
                // Si hay error usamos el sistema de Advertencias para mostrarlo. <div class="alert alert-danger" role="alert">...</div>
                var htmlError =
                    '<div class="alert alert-danger" role="alert">' +
                    resultado.error +
                    "</div>";
                $("body").prepend(htmlError);
            } else {
                abrirModal(resultado.titulo, resultado.html);
            }
        },
    });
}

function guardarConfiguracionCompras() {
    // @ Objetivo:
    // Guardar la configuración del formulario de configuración de compras.
    var datosFormulario = $("#formConfiguracionCompras").serializeArray();
    var parametros = {
        pulsado: "guardarConfiguracionCompras",
        datosFormulario: JSON.stringify(datosFormulario),
    };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        beforeSend: function () {
            console.log(
                "*********  Entre en guardar configuración de compras  ****************",
            );
        },
        success: function (response) {
            console.log("REspuesta de guardar configuración de compras");
            var resultado = $.parseJSON(response);
            console.log(resultado);
            if (resultado.error) {
                // Si hay error usamos el sistema de Advertencias para mostrarlo. <div class="alert alert-danger" role="alert">...</div>
                var htmlError =
                    '<div class="alert alert-danger" role="alert">' +
                    resultado.error +
                    "</div>";
                $("body").prepend(htmlError);
            } else {
                alert(resultado.mensaje);
                cerrarModal();
                location.reload();
            }
        },
    });
}
// =====================================================================
//       AJUSTE DE CÉNTIMOS — Funciones para ajuste fino
// =====================================================================
// Presupuesto acumulativo: la suma de |ajuste - original| de TODOS los
// inputs (céntimos) no puede superar max_ajuste_centimos.
// Cada input individual nunca puede alejarse más de ±(max_ajuste_centimos/100)€ del original.
//
// Dos modos según configuración:
//  - ajuste_centimos_desglose = true  → edita bases/IVAs, total computado
//  - ajuste_centimos = true (desglose false) → edita solo el total
// =====================================================================

var ajustesCentimos = {
    activo: false,
    total: null, // valor ajustado del total (manual o modo sin desglose)
    totalManual: false, // true si el usuario activó edición manual del total en modo desglose
    desglose: {}, // { tipoIva: { base: valor, iva: valor } }
};

/**
 * Activa el modo ajuste o aplica los cambios si ya estaba activo.
 */
function toggleAjusteCentimos() {
    if (!ajustesCentimos.activo) {
        activarModoAjuste();
    } else {
        aplicarAjusteCentimos();
    }
}

/**
 * Determina si estamos en modo desglose (editar bases/IVAs) o modo total.
 */
function esModoDesglose() {
    return (
        typeof window.ajusteCentimosDesglose !== "undefined" &&
        window.ajusteCentimosDesglose === true
    );
}

/**
 * Entra en modo edición: según configuración muestra inputs desglose o total.
 */
function activarModoAjuste() {
    ajustesCentimos.activo = true;
    var $btn = $("#btnAjusteCentimos");

    // Límite por campo en euros derivado de max_ajuste_centimos
    var maxCentimos =
        typeof window.maxAjusteCentimos !== "undefined"
            ? window.maxAjusteCentimos
            : 1;
    var maxDiffEuros = maxCentimos / 100; // ej: 1 → 0.01, 2 → 0.02

    if (esModoDesglose()) {
        // --- Modo desglose: editar bases e IVAs, total computado ---
        var $inputs = $(".input-ajuste-base, .input-ajuste-iva");
        var $spans = $(".valor-base, .valor-iva");

        // Poner min/max según max_ajuste_centimos respecto al valor original
        $inputs.each(function () {
            var $td = $(this).closest("td");
            var original = parseFloat($td.data("original"));
            if (!isNaN(original)) {
                $(this).attr("min", (original - maxDiffEuros).toFixed(2));
                $(this).attr("max", (original + maxDiffEuros).toFixed(2));
                $(this).attr("step", "0.01");
            }
        });

        $inputs.off("input.ajuste").on("input.ajuste", recalcularTotalesAjuste);
        $spans.hide();
        $inputs.show();

        // Botón "Ajustar Total" para editar manualmente el total en casos extremos
        if ($("#btnAjusteTotalManual").length === 0) {
            $(".totalImporte").append(
                ' <button type="button" id="btnAjusteTotalManual" ' +
                    'class="btn btn-info btn-xs" onclick="toggleAjusteTotalManual()" ' +
                    'style="font-size:0.3em;vertical-align:middle;" title="Ajustar total manualmente">' +
                    '<i class="glyphicon glyphicon-pencil"></i> Ajustar Total</button>',
            );
        } else {
            $("#btnAjusteTotalManual").show();
        }
    } else {
        // --- Modo solo total: editar únicamente el total ---
        var $inputTotal = $(".input-ajuste-total");
        var $spanTotal = $(".totalImporte .valor-total");
        var originalTotal = parseFloat(
            $(".totalImporte").data("original") || 0,
        );

        $inputTotal.attr("min", (originalTotal - maxDiffEuros).toFixed(2));
        $inputTotal.attr("max", (originalTotal + maxDiffEuros).toFixed(2));
        $inputTotal.attr("step", "0.01");
        $inputTotal.val($spanTotal.text());

        $inputTotal.off("input.ajuste").on("input.ajuste", function () {
            var nuevoTotal = parseFloat($(this).val()) || 0;
            var maxCentimos =
                typeof window.maxAjusteCentimos !== "undefined"
                    ? window.maxAjusteCentimos
                    : 1;
            var diff = Math.abs(nuevoTotal - originalTotal);
            if (diff > maxCentimos / 100 + 0.001) {
                $(this).css("color", "red");
            } else {
                $(this).css("color", "");
            }
        });

        $spanTotal.hide();
        $inputTotal.show();
    }

    $btn.removeClass("btn-default")
        .addClass("btn-warning")
        .text("Aplicar ajuste");

    // Mostrar botón Cancelar
    if ($("#btnCancelarAjuste").length === 0) {
        $btn.after(
            ' <button type="button" id="btnCancelarAjuste" ' +
                'class="btn btn-danger btn-sm" onclick="cancelarAjusteCentimos()">' +
                '<i class="glyphicon glyphicon-remove"></i> Cancelar</button>',
        );
    } else {
        $("#btnCancelarAjuste").show();
    }
}

/**
 * Activa/desactiva la edición manual del total en modo desglose.
 * Al activar, muestra el input de total y detiene su auto-recálculo.
 * Al desactivar, vuelve al total computado desde bases+IVAs.
 */
function toggleAjusteTotalManual() {
    var $inputTotal = $(".input-ajuste-total");
    var $spanTotal = $(".totalImporte .valor-total");
    var originalTotal = parseFloat($(".totalImporte").data("original") || 0);

    if (!ajustesCentimos.totalManual) {
        // Activar edición manual del total
        ajustesCentimos.totalManual = true;
        var maxCentimos =
            typeof window.maxAjusteCentimos !== "undefined"
                ? window.maxAjusteCentimos
                : 1;
        var maxDiffEuros = maxCentimos / 100;
        $("#btnAjusteTotalManual")
            .removeClass("btn-info")
            .addClass("btn-success")
            .html('<i class="glyphicon glyphicon-ok"></i> Auto Total');

        $inputTotal.attr("min", (originalTotal - maxDiffEuros).toFixed(2));
        $inputTotal.attr("max", (originalTotal + maxDiffEuros).toFixed(2));
        $inputTotal.attr("step", "0.01");
        $inputTotal.val($spanTotal.text());

        $inputTotal
            .off("input.ajusteTotal")
            .on("input.ajusteTotal", function () {
                var nuevoTotal = parseFloat($(this).val()) || 0;
                var maxCentimos =
                    typeof window.maxAjusteCentimos !== "undefined"
                        ? window.maxAjusteCentimos
                        : 1;
                var diff = Math.abs(nuevoTotal - originalTotal);
                if (diff > maxCentimos / 100 + 0.001) {
                    $(this).css("color", "red");
                } else {
                    $(this).css("color", "");
                }
            });

        $spanTotal.hide();
        $inputTotal.show();
    } else {
        // Desactivar edición manual → volver a total automático
        ajustesCentimos.totalManual = false;
        ajustesCentimos.total = null;
        $("#btnAjusteTotalManual")
            .removeClass("btn-success")
            .addClass("btn-info")
            .html('<i class="glyphicon glyphicon-pencil"></i> Ajustar Total');

        $inputTotal.off("input.ajusteTotal").hide().css("color", "");
        $spanTotal.show();
        // Recalcular total desde desglose
        recalcularTotalesAjuste();
    }
}

/**
 * Calcula el presupuesto acumulativo de céntimos usados.
 * Suma |valor - original| de todos los inputs de desglose, en céntimos.
 */
function contarCentimosUsados() {
    var totalCentimos = 0;
    $(".input-ajuste-base, .input-ajuste-iva").each(function () {
        var valor = parseFloat($(this).val()) || 0;
        var original = parseFloat($(this).closest("td").data("original")) || 0;
        totalCentimos += Math.round(Math.abs(valor - original) * 100);
    });
    return totalCentimos;
}

/**
 * Recalcula totalBases, totalIvas y Total cada vez que un input cambia.
 * Muestra indicador visual del presupuesto de céntimos usado.
 */
function recalcularTotalesAjuste() {
    var sumBase = 0,
        sumIva = 0;

    $(".input-ajuste-base").each(function () {
        sumBase += parseFloat($(this).val()) || 0;
    });
    $(".input-ajuste-iva").each(function () {
        sumIva += parseFloat($(this).val()) || 0;
    });

    var nuevoTotal = sumBase + sumIva;
    var maxCentimos =
        typeof window.maxAjusteCentimos !== "undefined"
            ? window.maxAjusteCentimos
            : 1;
    var centimosUsados = contarCentimosUsados();

    // Actualizar celdas de totales
    $("#totalBases").text(sumBase.toFixed(2));
    $("#totalIvas").text(sumIva.toFixed(2));

    // Solo actualizar el total si NO está en modo manual
    if (!ajustesCentimos.totalManual) {
        $(".totalImporte .valor-total").text(nuevoTotal.toFixed(2));
    }

    // Indicador visual: rojo si presupuesto excedido
    if (centimosUsados > maxCentimos) {
        $(".totalImporte .valor-total").css("color", "red");
    } else if (!ajustesCentimos.totalManual) {
        $(".totalImporte .valor-total").css("color", "");
    }
}

/**
 * Valida y aplica el ajuste.
 * En modo desglose: presupuesto acumulativo = sum |campo - original| ≤ max.
 * En modo total: |total_nuevo - total_original| ≤ max.
 */
function aplicarAjusteCentimos() {
    var errores = [];
    var maxCentimos =
        typeof window.maxAjusteCentimos !== "undefined"
            ? window.maxAjusteCentimos
            : 1;

    var maxDiffEuros = maxCentimos / 100; // ej: 1 → 0.01, 2 → 0.02

    if (esModoDesglose()) {
        // --- Modo desglose ---
        var desglose = {};

        // Recoger valores y validar cada input individual
        $(".input-ajuste-base, .input-ajuste-iva").each(function () {
            var tipo = $(this).data("tipo");
            var campo = $(this).data("campo");
            var valor = parseFloat($(this).val());
            var original = parseFloat($(this).closest("td").data("original"));

            if (!desglose[tipo]) {
                desglose[tipo] = {};
            }
            desglose[tipo][campo] = valor;

            if (Math.abs(valor - original) > maxDiffEuros + 0.001) {
                errores.push(
                    (campo === "base" ? "Base" : "IVA") +
                        " " +
                        tipo +
                        "%: máximo ±" +
                        maxDiffEuros.toFixed(2) +
                        "€ desde " +
                        original.toFixed(2) +
                        ".",
                );
            }
        });

        // Validar presupuesto acumulativo global
        var centimosUsados = contarCentimosUsados();
        if (centimosUsados > maxCentimos) {
            errores.push(
                "Presupuesto de ajuste: " +
                    centimosUsados +
                    " céntimo(s) usados, máximo permitido: " +
                    maxCentimos +
                    ".",
            );
        }

        if (errores.length > 0) {
            alert(errores.join("\n"));
            return;
        }

        // Si total manual activo, validar también el total
        if (ajustesCentimos.totalManual) {
            var $inputTotal = $(".input-ajuste-total");
            var nuevoTotal = parseFloat($inputTotal.val());
            var originalTotal = parseFloat(
                $(".totalImporte").data("original") || 0,
            );
            var diffTotalCent = Math.round(
                Math.abs(nuevoTotal - originalTotal) * 100,
            );

            if (diffTotalCent > maxCentimos) {
                errores.push(
                    "Total manual: diferencia de " +
                        diffTotalCent +
                        " céntimo(s) excede máximo de " +
                        maxCentimos +
                        ".",
                );
            }
            if (Math.abs(nuevoTotal - originalTotal) > maxDiffEuros + 0.001) {
                errores.push(
                    "Total manual: máximo ±" +
                        maxDiffEuros.toFixed(2) +
                        "€ desde " +
                        originalTotal.toFixed(2) +
                        ".",
                );
            }

            if (errores.length > 0) {
                alert(errores.join("\n"));
                return;
            }
            ajustesCentimos.total = nuevoTotal;
            // Actualizar el span visible con el total manual
            $(".totalImporte .valor-total").text(nuevoTotal.toFixed(2));
        } else {
            ajustesCentimos.total = null;
        }

        // Guardar y cerrar modo edición
        ajustesCentimos.desglose = desglose;
        ajustesCentimos.activo = false;
        ajustesCentimos.totalManual = false;

        for (var tipo in desglose) {
            if (desglose[tipo].base !== undefined) {
                $("#base" + tipo + " .valor-base").text(
                    desglose[tipo].base.toFixed(2),
                );
            }
            if (desglose[tipo].iva !== undefined) {
                $("#iva" + tipo + " .valor-iva").text(
                    desglose[tipo].iva.toFixed(2),
                );
            }
        }

        $(".input-ajuste-base, .input-ajuste-iva").off("input.ajuste").hide();
        $(".valor-base, .valor-iva").show();
        // Cerrar input total manual y botón
        $(".input-ajuste-total")
            .off("input.ajusteTotal")
            .hide()
            .css("color", "");
        $(".totalImporte .valor-total").show();
        $("#btnAjusteTotalManual").hide();
    } else {
        // --- Modo solo total ---
        var $inputTotal = $(".input-ajuste-total");
        var nuevoTotal = parseFloat($inputTotal.val());
        var originalTotal = parseFloat(
            $(".totalImporte").data("original") || 0,
        );
        var diffCentimos = Math.round(
            Math.abs(nuevoTotal - originalTotal) * 100,
        );

        if (diffCentimos > maxCentimos) {
            errores.push(
                "Total: diferencia de " +
                    diffCentimos +
                    " céntimo(s) excede máximo de " +
                    maxCentimos +
                    ".",
            );
        }
        if (Math.abs(nuevoTotal - originalTotal) > maxDiffEuros + 0.001) {
            errores.push(
                "Total: máximo ±" +
                    maxDiffEuros.toFixed(2) +
                    "€ desde " +
                    originalTotal.toFixed(2) +
                    ".",
            );
        }

        if (errores.length > 0) {
            alert(errores.join("\n"));
            return;
        }

        ajustesCentimos.total = nuevoTotal;
        ajustesCentimos.desglose = {};
        ajustesCentimos.activo = false;

        $(".totalImporte .valor-total")
            .text(nuevoTotal.toFixed(2))
            .css("color", "");
        $inputTotal.off("input.ajuste").hide();
        $(".totalImporte .valor-total").show();
    }

    $("#btnAjusteCentimos")
        .removeClass("btn-warning")
        .addClass("btn-default")
        .text("Ajuste céntimos");
    $("#btnCancelarAjuste").hide();

    // Persistir los ajustes al temporal inmediatamente
    if (typeof window.dedonde !== "undefined") {
        addTemporal(window.dedonde);
    }
}

/**
 * Cancela el ajuste: restaura todos los inputs y cierra modo.
 */
function cancelarAjusteCentimos() {
    if (esModoDesglose()) {
        $(".input-ajuste-base").each(function () {
            $(this).val(
                parseFloat($(this).closest("td").data("original")).toFixed(2),
            );
        });
        $(".input-ajuste-iva").each(function () {
            $(this).val(
                parseFloat($(this).closest("td").data("original")).toFixed(2),
            );
        });
        ajustesCentimos.totalManual = false;
        recalcularTotalesAjuste();
        $(".input-ajuste-base, .input-ajuste-iva").off("input.ajuste").hide();
        $(".valor-base, .valor-iva").show();
        // Restaurar total manual
        var origTot = parseFloat($(".totalImporte").data("original") || 0);
        $(".input-ajuste-total")
            .val(origTot.toFixed(2))
            .off("input.ajusteTotal")
            .hide()
            .css("color", "");
        $(".totalImporte .valor-total").show();
        $("#btnAjusteTotalManual").hide();
    } else {
        var originalTotal = parseFloat(
            $(".totalImporte").data("original") || 0,
        );
        $(".input-ajuste-total")
            .val(originalTotal.toFixed(2))
            .off("input.ajuste")
            .hide();
        $(".totalImporte .valor-total")
            .text(originalTotal.toFixed(2))
            .css("color", "")
            .show();
    }

    ajustesCentimos.activo = false;
    ajustesCentimos.total = null;
    ajustesCentimos.totalManual = false;
    ajustesCentimos.desglose = {};
    $(".totalImporte .valor-total").css("color", "");

    $("#btnAjusteCentimos")
        .removeClass("btn-warning")
        .addClass("btn-default")
        .text("Ajuste céntimos");
    $("#btnCancelarAjuste").hide();
}

/**
 * Devuelve los ajustes como JSON string para enviar al backend.
 * Devuelve null si no hay ningún ajuste.
 */
function getAjustesCentimosJSON() {
    var hayAjustes = false;

    // Comprobar ajuste de total directo (modo sin desglose)
    if (ajustesCentimos.total !== null) {
        var origTotal = parseFloat($(".totalImporte").data("original") || 0);
        if (Math.abs(ajustesCentimos.total - origTotal) > 0.001) {
            hayAjustes = true;
        }
    }

    // Comprobar ajustes de desglose
    for (var tipo in ajustesCentimos.desglose) {
        if (ajustesCentimos.desglose.hasOwnProperty(tipo)) {
            var vals = ajustesCentimos.desglose[tipo];
            var origBase = parseFloat($("#base" + tipo).data("original") || 0);
            var origIva = parseFloat($("#iva" + tipo).data("original") || 0);
            if (
                Math.abs((vals.base || 0) - origBase) > 0.001 ||
                Math.abs((vals.iva || 0) - origIva) > 0.001
            ) {
                hayAjustes = true;
            }
        }
    }

    if (!hayAjustes) {
        return null;
    }

    // Total: si hay override manual, usarlo; si hay desglose, sumar; si no, usar total directo
    var total = ajustesCentimos.total;
    if (total === null && Object.keys(ajustesCentimos.desglose).length > 0) {
        total = 0;
        for (var t in ajustesCentimos.desglose) {
            total +=
                (ajustesCentimos.desglose[t].base || 0) +
                (ajustesCentimos.desglose[t].iva || 0);
        }
        total = parseFloat(total.toFixed(2));
    }

    return JSON.stringify({
        total: total,
        desglose: ajustesCentimos.desglose,
    });
}

/**
 * Popula el hidden input de ajustesCentimos antes de enviar el formulario Guardar.
 */
function prepararAjustesParaGuardar() {
    var hidden = document.getElementById("ajustesCentimosHidden");
    // Solo enviar ajustesCentimos si el modo ajuste está activo
    if (hidden) {
        if (ajustesCentimos && ajustesCentimos.activo) {
            var ajustesJSON = getAjustesCentimosJSON();
            hidden.value = ajustesJSON !== null ? ajustesJSON : "";
        } else {
            hidden.value = "";
        }
    }
}

/**
 * Carga ajustes guardados desde el servidor al cargar un temporal.
 * @param {Object|string|null} datos
 */
function cargarAjustesCentimos(datos) {
    if (!datos) {
        ajustesCentimos = { activo: false, total: null, desglose: {} };
        return;
    }
    if (typeof datos === "string") {
        try {
            datos = JSON.parse(datos);
        } catch (e) {
            ajustesCentimos = { activo: false, total: null, desglose: {} };
            return;
        }
    }
    ajustesCentimos.activo = false;
    ajustesCentimos.totalManual = false;
    ajustesCentimos.desglose = datos.desglose || {};
    // Si hay total Y desglose, es un override manual; si solo total sin desglose, es modo total directo
    ajustesCentimos.total = datos.total !== undefined ? datos.total : null;
}
