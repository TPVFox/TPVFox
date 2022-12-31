
function AgregarFilaAlbaran(datos, dedonde){
	//@Objetivo:
	//Agregar html con el albaran seleccionado
	//@Parametros:
	//datos: datos del albaran adjunto
	//dedonde: de donde viene
	console.log("Estoy en agregar fila albaran");
	var parametros = {
		"pulsado"    : 'htmlAgregarFilaAlbaran',
		"datos" : datos,
		"dedonde":dedonde
	};
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en escribir html fila pedidos JS****************');
		},
		success    :  function (response) {
			var resultado =  $.parseJSON(response); 
			var nuevafila = resultado['html'];
			$("#tablaAlbaran").prepend(nuevafila);
			$('#numAlbaran').focus(); 
			$('#numAlbaran').val(""); 
			
		}
	});
}

function AgregarFilaAdjunto(datos, dedonde){
	//@Objetivo:
	//Agregar html con el albaran seleccionado
	//@Parametros:
	//datos: datos del albaran adjunto
	//dedonde: de donde viene
	console.log("Estoy en agregar fila albaran");
	var parametros = {
		"pulsado"    : 'htmlAgregarFilaAdjunto',
		"datos" : datos,
		"dedonde":dedonde
	};
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en escribir html fila pedidos JS****************');
		},
		success    :  function (response) {
			var resultado =  $.parseJSON(response); 
			var nuevafila = resultado['html'];
			$("#tablaAlbaran").prepend(nuevafila);
			$('#numAlbaran').focus(); 
			$('#numAlbaran').val(""); 
			
		}
	});
}



function AgregarFilaPedido(datos , dedonde=""){
	//@Objetivo:
	//Agregar html con el pedido seleccionado
	//@Parametros:
	//datos: datos del pedido adjunto
	//dedonde: de donde viene
	console.log("Estoy en agregar fila Pedido");
	var parametros = {
		"pulsado"    : 'htmlAgregarFilaPedido',
		"datos" : datos,
		"dedonde":dedonde
	};
	
	console.log(parametros);
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en escribir html fila pedidos JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de html fila pedidos');
			var resultado =  $.parseJSON(response); 
			var nuevafila = resultado['html'];
			$("#tablaPedidos").prepend(nuevafila);
			$('#numPedido').focus(); 
			$('#numPedido').val(""); 
			
		}
	});
}

function AgregarFilaProductosAl(productosAl, dedonde='', cabecera ='NO'){
	//@Objetivo:
	//Agregar la fila de productos al principio de la tabla
	console.log("Estoy en agregar fila productos para "+dedonde);
    console.log(cabecera);
	if (productosAl.length>1){
		productosAl=productosAl.reverse();
	}
	var parametros = {
		"pulsado"    : 'htmlAgregarFilasProductos',
		"productos" : productosAl,
		"dedonde": dedonde
	};
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en escribir html fila productos JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de html fila Productos');
			var resultado =  $.parseJSON(response); 
			var nuevafila = resultado['html'];
			$("#tabla").prepend(nuevafila);
			if(dedonde=="factura"){
				if(adjuntos.length>0){
				bloquearInput();
			}
			}
		}
	});
}

function abrirIncidenciasAdjuntas(id, modulo, dedonde){
    var parametros = {
            "pulsado"    : 'abrirIncidenciasAdjuntas',
            "id" : id,
            "modulo"      : modulo,
            "dedonde": dedonde
        };
    $.ajax({
        data       : parametros,
        url        : 'tareas.php',
        type       : 'post',
        beforeSend : function () {
            console.log('*********  Entre en cancelar archivos temporales  ****************');
        },
        success    :  function (response) {
            console.log('REspuesta de cancelar temporales');
            var resultado =  $.parseJSON(response);
            console.log(resultado);
            if(resultado.error){
                alert(resultado.consulta);
            }else{
                var titulo = 'Listado de incidencias ';
                abrirModal(titulo,resultado.html);
            }
        }
    });
}

function addTemporal(dedonde){
	//@Objetivo;
	//Añadir un registro temporal o modificarlo
	//@Parametros: 
	//dedonde: de donde viene (factura, albaran, pedidos)
		console.log('FUNCION Añadir temporal JS-AJAX');
		if (dedonde=="pedidos"){
			var pulsado= 'anhadirPedidoTemp';
		}
		if (dedonde=="albaran"){
			var pulsado='anhadirAlbaranTemporal';
		}
		if (dedonde=="factura"){
			var pulsado='anhadirfacturaTemporal';
		}
		var parametros = {
		"pulsado"    : pulsado,
		"idTemporal":cabecera.idTemporal,
		"idUsuario":cabecera.idUsuario,
		"idTienda":cabecera.idTienda,
		"estado":cabecera.estado,
		"idReal":cabecera.idReal,
		"fecha":cabecera.fecha,
		"productos":JSON.stringify(productos),
		"idCliente":cabecera.idCliente
	};
	if (dedonde=="albaran"){
		parametros['pedidos']=pedidos;
	}
	if (dedonde=="factura"){
		parametros['albaranes']=adjuntos;
	}
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en añadir albaran temporal JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de añadir temporal'+dedonde);
			var resultado =  $.parseJSON(response); 
			var HtmlClientes=resultado.html;
			if(resultado.error){
				alert('Error de SQL: '+resultado.consulta);
			}else{
                cabecera.idTemporal=resultado.id;
				if (resultado.existe == 0){
					history.pushState(null,'','?tActual='+resultado.id);
				}
				resetearTotales();
				
				total = parseFloat(resultado['totales']['total'])
				$('.totalImporte').html(total.toFixed(2));
				$('#tabla-pie  > tbody ').html(resultado['htmlTabla']);
				var estado="Sin guardar";
				if (cabecera.idReal>0){
					var estado="Sin guardar";
					modificarEstado(dedonde, estado, cabecera.idReal);
				}
				$("#Cancelar").show();
				$("#Guardar").show();
			}
		}
	});
}

function bloquearInput(){
	//@Objetivo:
	//Blosquear la linea de insertción de inputs y los input de unidades para que no se puedan modificar
	console.log("Elementos js");
	$('#Row0').css('display', 'none');
	$('.unidad').attr("readonly","readonly");
}

function buscarAdjunto(dedonde, valor=''){
	//@ Objetivos:
	//  Buscar los pedidos de un cliente que tenga el estado guardado
    //@ Parametros
    //  loque => indicamos que vamos buscar (albaran o pedido)
    //  valor => Un numero de adjunto a buscar.
	console.log('FUNCION buscar albaran JS-AJAX');
    // Controlamos

	var parametros = {
		"pulsado"    : 'buscarAlbaran',
		"busqueda" : valor,
		"idCliente":cabecera.idCliente,
        "dedonde": dedonde
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en buscar Albaran JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de buscar albaran');
			var resultado =  $.parseJSON(response); 
			var encontrados = resultado.encontrados;
			var HtmlAdjuntos=resultado.html;   //$resultado['html'] de montaje html
			if(resultado.error){
				alert('Error de sql: '+resultado.consulta);
			}else{
				if (resultado.Nitems>1 || resultado.Nitems== 0){
                 //Si hay mas un dato o 0 entonces abrimos modal.
					var titulo = 'Listado Albaranes ';
					abrirModal(titulo, HtmlAdjuntos);
				}else{
					if (resultado.Nitems== 1){
                        // Comprobamos que no se repitan.
                        // Realmente ya no debería parecer en el listado si esta introducido.
						var bandera=0;
						for(i=0; i<adjuntos.length; i++){//recorre todo el array de arrays de pedidos
							var numeroAdjunto=adjuntos[i].NumAdjunto;
							var numeroNuevo=resultado['datos'].NumAdjunto;
							if (numeroAdjunto == numeroNuevo){
								bandera=bandera+1;
							}
						}
						if (bandera==0){
                            // Si no es repetido el adjunto.
                            // -- Añadimos fila adjunto y cambiamos estado.  --//
							resultado['datos'].nfila=parseInt(adjuntos.length)+1;;
							adjuntos.push(resultado['datos']);
                            modificarEstado("albaran", "Facturado", resultado['datos'].NumAdjunto);
							AgregarFilaAdjunto(resultado['datos'], dedonde);
                            // -- Añadimos productos y lineas de productos de ese adjunto. --//
							productosAdd=resultado.productos;
							var numFila=productos.length+1;
							for (i=0; i<productosAdd.length; i++){ //en el array de arrays de productos metemos los productos de ese pedido
								resultado.productos[i]['nfila']=numFila;
								resultado.productos[i]['importe']=resultado.productos[i]['nunidades']*resultado.productos[i]['pvpSiva'];
								productos.push(resultado.productos[i]);
								numFila++;
							}
							AgregarFilaProductosAl(resultado.productos, dedonde,cabecera);
                            addTemporal(dedonde);
						}else{
							alert("Ya has introducido ese "+dedonde);
						}
					}else{
						alert("No hay resultado");
					}
				}
			}
		}
	});
}

function buscarClientes(dedonde, idcaja, valor=''){
	// @ Objetivo:
	// 	Abrir modal con lista clientes, que permitar buscar en caja modal.
	// 	Ejecutamos Ajax para obtener el html que vamos mostrar.
	// @ parametros :
	//	valor -> Sería el valor caja del propio modal
	console.log('FUNCION buscarClientes JS-AJAX');
	var parametros = {
		"pulsado"    : 'buscarClientes',
		"busqueda" : valor,
		"dedonde":dedonde,
		"idcaja":idcaja
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en buscar clientes JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de buscar clientes');
			var resultado =  $.parseJSON(response); 
			// Si el archivo de donde viene la consulta es  albaran con lo que devuelve la consulta
			//de buscarCliente se registra en los input y se bloquean posteriormente
			if (resultado.error){
					alert('ERROR DE SQL: '+resultado.consulta);
			}else{
				if (resultado.Nitems==1 && resultado.html ==null ){
					cabecera.idCliente=resultado.id;
					$('#Cliente').val(resultado.nombre);
					$('#id_cliente').val(resultado.id);
					$('#Cliente').prop('disabled', true);
					$('#id_cliente').prop('disabled', true);
					$("#buscar").css("display", "none");
					mostrarFila();
					if (dedonde=="albaran"){
						comprobarPedidosExis();
					}
					if (dedonde=="factura"){
						comprobarAdjuntosExis();
					}
					if(dedonde=="pedido"){
					$('#Referencia').focus();	
					}
					 cerrarPopUp();
				}else{
					console.log(resultado.html);
				 var titulo = 'Listado clientes ';
				 var HtmlClientes=resultado.html.html; 
				 abrirModal(titulo,HtmlClientes);
				 focusAlLanzarModal('cajaBusquedacliente');
				if(resultado.html.encontrados>0){
					ponerFocus('N_0');
				}
				 }
			}
		}
	});
}

function buscarDatosPedido(NumPedido){
	//@Objetivo:
	// Cuando seleccionamos un pedido o un albarán llamamos a la funciones correspondiente enviandole el número.
	//Estas funciones se llama en el modal tando de añadir un pedido como un albarán para no hacer mas grande la funcion 
	//lo que hacemos es llamar a la función que llamamos cuando ponemos directamente el número
	console.log("Estoy en buscarDatosPedido");
	buscarPedido("albaran", "numPedido", NumPedido);
	cerrarPopUp();
}
function buscarDatosAlbaran(NumAlbaran){
	//@Objetivo:
	//Cuando se selecciona un albaran se llama a la función buscarAlbaran con los datos principales
	console.log("Estoy en buscar datos albaran");
	buscarAlbaran("factura", "numAlbaran", NumAlbaran);
	cerrarPopUp();
}

function buscarPedido(dedonde, idcaja, valor=''){
	//@Objetivo
	//Buscar los pedidos de un cliente que tenga el estado guardado
	//Parametros:
	//dedonde: archivo del que viene 
	//Idcaja : id de la caja de donde se inserto el número
	//valor: valor que se escribió en la caja
	console.log('FUNCION buscarPedido JS-AJAX');
	var parametros = {
		"pulsado"    : 'buscarPedido',
		"busqueda" : valor,
		"idCliente":cabecera.idCliente,
		"dedonde":dedonde
	};
	console.log (valor);
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en buscar Pedfidos JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de buscar pedidos');
			var resultado =  $.parseJSON(response); 
			var encontrados = resultado.encontrados;
			var HtmlPedidos=resultado.html;
			if(resultado.error){
				 alert('Error de SQL: '+resultado.error);
			}else{   
				if (valor==""){ 
					var titulo = 'Listado Pedidos ';
					abrirModal(titulo, HtmlPedidos);
				}else{
					if (resultado.Nitems>0){
						var bandera=0;
						for(i=0; i<pedidos.length; i++){
							var numeroPedido=pedidos[i].Numpedcli;
							var numeroNuevo=resultado['datos'].Numpedcli;
							if (numeroPedido == numeroNuevo){
								bandera=bandera+1;
							}
						}
						if (bandera==0){// si no hay repetidos
							var datos = [];
							datos = resultado['datos'];
							n_item=parseInt(pedidos.length)+1;
							datos.nfila=n_item;
							pedidos.push(datos);// En el array de arrays  de pedidos de la cabecera metemos el array de pedido nuevo 
							productosAdd=resultado.productos;
							var numFila=productos.length+1;
							for (i=0; i<productosAdd.length; i++){ //en el array de arrays de productos metemos los productos de ese pedido
								resultado.productos[i]['nfila']=numFila;
								resultado.productos[i]['importe']=resultado.productos[i]['nunidades']*resultado.productos[i]['pvpSiva'];
								productos.push(resultado.productos[i]);
								numFila++;
							}
							console.log(dedonde);
							addTemporal(dedonde)
							modificarEstado("pedidos", "Facturado",resultado['datos'].idPedCli );
							AgregarFilaPedido(datos, "albaran");
							AgregarFilaProductosAl(resultado.productos, dedonde);
						}else{
							alert("Ya has introducido ese pedido");
						}
					}else{
						alert("No hay resultado");
					}
				}
			}
		}
	});
}

function buscarProductos(id_input,campo, idcaja, busqueda,dedonde){
	// @ Objetivo:
	//  Buscar productos donde el dato exista en el campo que se busca...
	// @ Parametros:
	// 		nombreinput = id caja de donde viene
	//		campo =  campo a buscar
	// 		busqueda = valor del input que corresponde.
	// 		
	// @ Respuesta:
	//  1.- Un producto unico.
	//  2.- Un listado de productos.
	//  3.- O nada un error.
	console.log('FUNCION buscarProductos JS- Para buscar con el campo');
    if (busqueda !== "" || idcaja === "Descripcion"){
        var parametros = {
            "pulsado"    : 'buscarProductos',
            "cajaInput"	 : id_input,
            "valorCampo" : busqueda,
            "campo"      : campo,
            "idcaja"	 :idcaja,
            'dedonde'	:dedonde,
            'idCliente'	:cabecera.idCliente
        };
        $.ajax({
            data       : parametros,
            url        : 'tareas.php',
            type       : 'post',
            beforeSend : function () {
                console.log('*********  Envio datos para Buscar Producto  ****************');
            },
            success    :  function (response) {
                console.log('Repuesta de FUNCION -> buscarProducto');
                var resultado =  $.parseJSON(response);
                 if (resultado['Nitems']===1){
                    var datos = new Object();
                    datos.Numalbcli=0;
                    datos.Numpedcli=0;
                    datos.ccodbar=resultado['datos'][0]['codBarras'];
                    datos.cdetalle=resultado['datos'][0]['articulo_name'];
                    datos.cref=resultado['datos'][0]['crefTienda'];
                    datos.estadoLinea="Activo";
                    datos.idArticulo=resultado['datos'][0]['idArticulo'];
                    datos.idpedcli=0;
                    datos.iva=resultado['datos'][0]['iva'];
                    datos.ncant=1;
                    datos.nfila=productos.length+1;
                    datos.nunidades=1;
                    var importe =resultado['datos'][0]['pvpSiva']*1;
                    datos.importe=importe.toFixed(2);
                    var pvpCiva= parseFloat(resultado['datos'][0]['pvpCiva']);
                    datos.precioCiva=pvpCiva.toFixed(2);
                    var pvpSiva= parseFloat(resultado['datos'][0]['pvpSiva']);
                    datos.pvpSiva=pvpSiva.toFixed(2);
                    n_item=parseInt(productos.length)+1;
                    var campo='Unidad_Fila_'+n_item;
                    productos.push(datos);
                    addTemporal(dedonde);
                    AgregarFilaProductosAl(datos, dedonde);
                    ponerSelect(campo);
                    resetCampo(id_input);
                    if (dedonde=="factura"){
                        $("#tablaAl").hide();
                    }
                     cerrarPopUp();
                }else{
                    console.log('=== Entro en Estado Listado de funcion buscarProducto =====');
        
                    var busqueda = resultado.listado;   
                    var HtmlProductos=busqueda.html;   
                    var titulo = 'Listado productos encontrados ';
                    abrirModal(titulo,HtmlProductos);
                    focusAlLanzarModal('cajaBusqueda');
                    if (resultado.listado['encontrados'] >0 ){
                        // Quiere decir que hay resultados por eso apuntamos al primero
                        // focus a primer producto.
                        var d_focus = 'N_0';
                         ponerFocus(d_focus);
                    }
                }
            }
        });
    }else{
        console.log('Saltamos a ' + ObtenerCajaSiguiente(idcaja));
        ponerFocus(ObtenerCajaSiguiente(idcaja));
    }
}

function cambiarEstadoProductosAdjunto(dedonde,estado,numRegistro){
    // @ Objetivo:
    // Cambiar el estado de los productos de un adjunto
    // @ Parametros:
    // dedonde -> Si estoy en albaranes o facturas
    // estado -> Que queremos poner a los productos de ese adjunto. (Activo o Eliminado)
    // numRegistro -> Numero de del adjunto.
    console.log('Entro en cambiarEstadoProductosAdjuntos:'+numRegistro);
    for(i=0;i<productos.length; i++){
        if (dedonde=="albaran"){
            var numAdjunto_Producto=productos[i].Numpedcli;
        }else{
            var numAdjunto_Producto=productos[i].NumalbCli;
        }
        if (numRegistro == numAdjunto_Producto){
            productos[i].estadoLinea= estado;
            cambioEstadoFila(productos[i],dedonde);
        }
    }
}

function cambioEstadoFila(producto,dedonde=""){
    // @Objetivo
    // Cambiamos el estado fila a eliminado (tachado) o no una fila.
    // @Parametros
    //    producto -> objeto del producto queremos cambiar ( YA viene cambiado el estado solo pintamos. )
    //    dedonde  -> indicando si es pedido, albaran o factura.
    console.log('Entro en cambiar Estado Fila');
    line = "#Row" + producto.nfila;
    if (producto.estadoLinea === 'Eliminado'){
        $(line).addClass('tachado');
        $(line + "> .eliminar").html('<a onclick="retornarFila('+producto.nfila+', '+"'"+dedonde+"'"+');"><span class="glyphicon glyphicon-export"></span></a>');
       	$("#N" +producto.nfila + "_Unidad").prop("disabled", true);

    } else {
        $(line).removeClass('tachado');
        $(line + "> .eliminar").html('<a onclick="eliminarFila('+producto.nfila+' , '+"'"+dedonde+"'"+');"><span class="glyphicon glyphicon-trash"></span></a>');
        $("#Unidad_Fila_" + producto.nfila).prop("disabled", false);
        $("#N" + producto.nfila + "_Unidad").prop("disabled", false);
        $("#N" + producto.nfila + "_Unidad").val(producto.nunidades);
    }
}

function cancelarTemporal(idTemporal, dedonde){
	var mensaje = confirm("Estas  seguro que quieres eliminar el temporal "+idTemporal+'?');
	if (mensaje) {
		if (idTemporal=="0"){
			alert("No puedes cancelar si está guardado");
		}else{
			var parametros = {
				"pulsado"    : 'cancelarTemporal',
				"dedonde" : dedonde,
				"idTemporal"      : idTemporal
			};
			$.ajax({
				data       : parametros,
				url        : 'tareas.php',
				type       : 'post',
				beforeSend : function () {
					console.log('*********  Entre en cancelar archivos temporales  ****************');
				},
				success    :  function (response) {
					console.log('REspuesta de cancelar temporales');
					var resultado =  $.parseJSON(response);
					console.log(resultado);
						if(resultado.mensaje){
							alert(resultado.mensaje+": "+resultado.dato);
						}else{
							switch(dedonde){
								case 'pedidos':
									location.href="pedidosListado.php";
								break;
								case 'albaran':
									location.href="albaranesListado.php";
								break;
								case 'factura':
									location.href="facturasListado.php";
								break;
							}
						}
					}
			});
		}
	}
}

function comprobarAdjuntosExis(){
	//@Objetivo:
	//Buscar los albaranes de el cliente seleccionado 
	//Si la respuesta es positiva muestra la tabla oculta
	console.log('FUNCION comprobar pedidos existentes  JS-AJAX');
	var parametros = {
		"pulsado"    : 'comprobarAlbaran',
		"idCliente" : cabecera.idCliente
	};
	console.log(parametros);
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en comprobar pedidos existentes JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de comprobar pedidos');
			var resultado =  $.parseJSON(response); 
			if (resultado.error){
				alert('Error de SQL: '+resultado.consulta);
			}else{
				if (resultado.alb==1){
					$("#numAlbaranT").show();
					$("#numAlbaran").show();
					$("#buscarAlbaran").show();
					$("#tablaAlbaran").show();
				}
			}
		}
	});
}

function comprobarPedidosExis(){
	//@Objetivo:
	//comprobar que un cliente tiene pedidos con estado guardado
	//Si la respuesta es positiva muestra la entrada de pedidos
	console.log('FUNCION comprobar pedidos existentes  JS-AJAX');
	var parametros = {
		"pulsado"    : 'comprobarPedidos',
		"idCliente" : cabecera.idCliente
		
	};
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en comprobar pedidos existentes JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de comprobar pedidos');
			var resultado =  $.parseJSON(response); 
			if (resultado.ped==1){
				$("#numPedidoT").show();
				$("#numPedido").show();
				$("#buscarPedido").show();
				$("#tablaPedidos").show();
				$("#numPedido").focus();
			}else{
				$('#idArticulo').focus();
			}
		}
	});
}

function eliminarAdjunto(numRegistro, dedonde, nfila){
	//@Objetivo:
	//Eliminar tanto un pedido o albaran adjunto en una factura o albaran , elimina por lo tanto los productos
	//de ese adjunto y le modifica el estado a guardado
	console.log("entre en eliminar Fila");
	var line;
	num=nfila-1;
	if (dedonde=="factura"){
		line = "#lineaP" + adjuntos[num].nfila;
		adjuntos[num].estado= 'Eliminado';
	}
	if (dedonde=="albaran"){
		line = "#lineaP" + pedidos[num].nfila;
		pedidos[num].estado= 'Eliminado';
	}
	$(line).addClass('tachado');
	$(line + "> .eliminar").html('<a onclick="retornarAdjunto('+numRegistro+', '+"'"+dedonde+"'," + nfila+');"><span class="glyphicon glyphicon-export"></span></a>');
    // Ahora cambiamos estado poniendo 'Eliminando' de todos los productos de ese adjunto.
    cambiarEstadoProductosAdjunto(dedonde,'Eliminado',numRegistro);
    // Ahora cambiamos estado de adjunto a Guardado, ya que debería tener como facturado.
    if (dedonde=="albaran"){
        modificarEstado("pedidos", "Guardado", pedidos[num].idPedido);
    }
    if (dedonde=="factura"){
        modificarEstado("albaran", "Guardado", adjuntos[num].NumAdjunto);
    }
    // Creamos temporal para quede guardado
    alert( 'Fijate que cambiado todas las lineas antes de continuar');
    addTemporal(dedonde);
}

function eliminarFila(num_item, dedonde=""){
	//@Objetivo
	//Función para cambiar el estado del producto
	console.log("entre en eliminar Fila");
	var line;
	num=num_item-1;
	line = "#Row" + productos[num].nfila;
	productos[num].estadoLinea='Eliminado';
	$(line).addClass('tachado');
	$(line + "> .eliminar").html('<a onclick="retornarFila('+num_item+', '+"'"+dedonde+"'"+');"><span class="glyphicon glyphicon-export"></span></a>');
	$("#N" +productos[num].nfila + "_Unidad").prop("disabled", true);
	addTemporal(dedonde);
}

function imprimir(id, dedonde, tienda){
	//@Objetivo:
	//imprimir en pdf tanto una factura, albaran o pedido de una tienda determinada
	var parametros = {
		"pulsado"    : 'datosImprimir',
		"dedonde":dedonde,
		"id":id,
		"tienda":tienda
	};
	console.log(parametros);
	$.ajax({
			data       : parametros,
			url        : 'tareas.php',
			type       : 'post',
			beforeSend : function () {
				console.log('******** estoy en datos Imprimir JS****************');
			},
			success    :  function (response) {
				 var resultado =  $.parseJSON(response); 
				 window.open(resultado);
		}		
	});
}


function metodoClick(pulsado,dedonde){
	console.log("Inicimos switch de control pulsar");
    console.log('Adonde:'+dedonde);
    switch(pulsado) {
		case 'editar':
			console.log('Entro en Ver pedido');
			// Cargamos variable de global.js que array objetos            
            var checkID = leerChecked('Check');
			if (checkID.length >1 || checkID.length=== 0) {
				alert ('Que ver items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			} else {
                window.location.href = './'+dedonde+'.php?id='+checkID[0].value;
            }
			break;
		case 'AgregarPedido':
			console.log('entro en agregar producto');
			window.location.href = './pedido.php';
			
			break;
		case 'AgregarAlbaran':
			console.log('entro en agregar producto');
			window.location.href = './albaran.php';
			break;
		case 'AgregarFactura':
			console.log('entro en agregar producto');
			window.location.href = './factura.php';
			break;
		
	 }
} 

function  modificarEstado(dedonde, estado, idModificar){
	//@Objetivo:
	//Modificar el estado dependiendo de donde venga 
	//Paramtros: 
	//Dedonde: de donde llamamos a la función
	//Estado: estado que vamos a asignarle al registro
	//IdModificar: id del registro que se va a modificar
	if (dedonde=="pedidos"){
		var pulsado='modificarEstadoPedido';
	}
	if (dedonde=="albaran"){
		var pulsado= 'modificarEstadoAlbaran';
		
	}
	if (dedonde=="factura"){
		var pulsado='modificarEstadoFactura';
	}
	var parametros = {
			"pulsado":pulsado,
			"idModificar":idModificar,
			"estado":estado
		};
		console.log(parametros);
		$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('******** estoy en Modificar estado factura js****************');
		},
		success    :  function (response) {
				var resultado =  $.parseJSON(response); 
				if (resultado.error){
					alert('Error de SQL: '+resultado.consulta);
				}
			}
	});
}
function mostrarFila(){
	//@Objetivo; 
	//Mostrar la fila de inputs para añadir nuevos productos
	console.log("mostrar fila");
	$("#Row0").removeAttr("style") ;
    ponerFocus( ObtenerFocusDefectoEntradaLinea());
}

function recalculoImporte(cantidad,num_item, dedonde=""){
	// @ Objetivo:
	// Recalcular el importe de la fila, si la cantidad cambia.
	// @ Parametros:
	//	cantidad -> Valor ( numerico) de input unidades.
	//	num_item -> El numero que indica el producto que modificamos.
	console.log('Estoy en recalculoImporte');
		if (productos[num_item].ncant == 0 && cantidad != 0) {
			retornarFila(num_item+1, dedonde);
		} else if (cantidad == 0 ) {
			eliminarFila(num_item+1, dedonde);
		}
		productos[num_item].nunidades = cantidad;
		productos[num_item].ncant = cantidad;
		var importe = cantidad*productos[num_item].pvpSiva;
		var id = '#N'+productos[num_item].nfila+'_Importe';
		importe = importe.toFixed(2);
		productos[num_item].importe= importe;
		$(id).html(importe);
        addTemporal(dedonde);
}

function resetCampo(campo){
	//@Objetivo: borrar los campos input
	console.log('Entro en resetCampo '+campo);
	document.getElementById(campo).value='';
	return;
}

function resetearTotales(){
	// Funcion para resetear totales.
	$('#tipo4').html('');
	$('#tipo10').html('');
	$('#tipo21').html('');
	$('#base4').html('');
	$('#base10').html('');
	$('#base21').html('');
	$('#iva4').html('');
	$('#iva10').html('');
	$('#iva21').html('');
	$('.totalImporte').html('');
}

function retornarAdjunto(numRegistro, dedonde, nfila){
	//@Objetivo:
	//retornar un adjunto eliminado , modifica el estado del adjunto a facturado y añade los productos de ese adjunto
	console.log("entre en retornar fila adjunto");
    alert ('NumRegistro:'+numRegistro);
	var estado="Guardado";
	var line;
    // Recuerda que el nfila empieza 1 y num de array 0
    var num = nfila -1;
	if (dedonde=="factura"){
		line = "#lineaP" + adjuntos[num].nfila;
        alert ('line:'+num);
		adjuntos[num].estado= 'Activo';
	}
	if (dedonde=="albaran"){
		line = "#lineaP" + pedidos[num].nfila;
		pedidos[num].estado= 'Activo';
	}
	$(line).removeClass('tachado');
	$(line + "> .eliminar").html('<a onclick="eliminarAdjunto('+numRegistro+' , '+"'"+dedonde+"', "+nfila+');"><span class="glyphicon glyphicon-trash"></span></a>');
    // Ahora cambiamos el estado de todos los productos del adjunto
    cambiarEstadoProductosAdjunto(dedonde,'Activo',numRegistro);
	if (dedonde=="albaran"){
        modificarEstado("pedidos", "Facturado", pedidos[num].idPedido);
	}
	if (dedonde=="factura"){
        modificarEstado("albaran", "Facturado", adjuntos[num].NumAdjunto);
	}
    addTemporal(dedonde);
}

function retornarFila(num_item, valor=""){
	// @Objetivo :
	// Es pasar un producto eliminado a activo.
	console.log("entre en retornar fila");
	var line;
	num=num_item-1;
	line = "#Row" +productos[num].nfila;
	productos[num].estadoLinea= 'Activo';
	$(line).removeClass('tachado');
	$(line + "> .eliminar").html('<a onclick="eliminarFila('+num_item+' , '+"'"+valor+"'"+');"><span class="glyphicon glyphicon-trash"></span></a>');
	if (productos[num].nunidades == 0) {
		productos[num].nunidades = 1;
	}
	$("#Unidad_Fila_" + productos[num].nfila).prop("disabled", false);
	$("#N" + productos[num].nfila + "_Unidad").prop("disabled", false);
	$("#N" + productos[num].nfila + "_Unidad").val(productos[num].nunidades);	
	addTemporal(valor);
}
