//Función que controla las acciones que llegan del xml

function controladorAcciones(caja,accion, tecla){
    console.log (' Controlador Acciones: ' +accion);
    switch(accion) {
		case 'buscarProveedor':
			console.log("Estoy en buscar proveedor");

            if( caja.darValor()=="" && caja.id_input=="id_proveedor"){
				// Entramos cuando venimos de id de proveedor.
				var d_focus="Proveedor";
                ponerFocus(d_focus);
            }else{
				buscarProveedor(caja.darParametro('dedonde'),caja.id_input ,caja.darValor());
			}
            
		break;
		
		case 'recalcular_totalProducto':
			console.log("entre en recalcular precio producto");
			// recuerda que lo productos empizan 0 y las filas 1
			var nfila = parseInt(caja.fila)-1;
			// Comprobamos si cambio valor , sino no hacemos nada.
			productos[nfila].nunidades = caja.darValor();
			productos[nfila].ncant = caja.darValor();
			recalculoImporte(productos[nfila].nunidades, nfila, caja.darParametro('dedonde'));
			if (caja.tipo_event !== "blur"){
				if (caja.darParametro('dedonde') == "pedidos"){
                    ponerFocus( ObtenerFocusDefectoEntradaLinea());
				}else{
					d_focus='ultimo_coste_'+parseInt(caja.fila);
					ponerSelect(d_focus);
					
				}
			}
		break;
		case  'saltar_productos':
			if (productos.length >0){
			// Debería añadir al caja N cuantos hay
				console.log ( 'Entro en saltar a producto que hay '+ productos.length);
				ponerSelect('Unidad_Fila_'+productos.length);
			} else {
			   console.log( ' No nos movemos ya que no hay productos');
			}
			break;
		case 'Saltar_hora':
			if (caja.id_input=="fecha"){
				cabecera.fecha=caja.darValor();
			}
			var d_focus = 'hora';
			if(caja.darParametro('dedonde')=='factura'){
				var d_focus = 'suNumero';
			}
			if(caja.darParametro('dedonde')=='pedidos'){
				var d_focus = 'id_proveedor';
			}
			
			ponerFocus(d_focus);
		break;
		
		case 'mover_down':
			// Controlamos si numero fila es correcto.
			var nueva_fila = 0;
			if(caja.id_input=="cajaBusquedaproveedor" || caja.id_input=="cajaBusqueda"){
				ponerFocus('N_0');
			}else{
			if ( isNaN(caja.fila) === false){
				nueva_fila = parseInt(caja.fila)+1;
			} 
			console.log('mover_down:'+nueva_fila);
			
			mover_down(nueva_fila,caja.darParametro('prefijo'));
			}
		break;
		case 'mover_up':
			console.log( 'Accion subir 1 desde fila'+caja.fila);
			var nueva_fila = 0;
			if(caja.fila=='0'){
				if(cabecera.idProveedor>0){
					ponerSelect('cajaBusqueda');
				}else{
					$("#cajaBusquedaproveedor").select();
				}
			}else{
				if ( isNaN(caja.fila) === false){
					nueva_fila = parseInt(caja.fila)-1;
				}
				mover_up(nueva_fila,caja.darParametro('prefijo'));
			}
		break;
		
        
        case 'Saltar_desde_Hora':
            cabecera.hora = dato; // Guardamos el dato, tanto tenga datos , como no.
            var d_focus = "suNumero";
            if (caja.tecla === '37'){
                // Quiere volver a fecha
                d_focus = 'fecha';
            }
            ponerFocus(d_focus);
        break;

        case 'Saltar_desde_SuNumero':
            console.log('Estoy en Saltar desde SuNumero');
            cabecera.suNumero=caja.darValor();
            var d_focus = "id_proveedor";
            // Comprobamos que si esta disabled o no .
            if ($('#id_proveedor').prop("disabled") == true) {
                // Ya ponemos focus a entrada productos ( campo por defecto)
                  d_focus = ObtenerFocusDefectoEntradaLinea();
            }
            if ( caja.tecla === '9'){
                // Quiere volver a fecha
                d_focus = 'formaVenci';
            }
            ponerFocus(d_focus);
        break;
        
        case 'Saltar_idProveedor':
            console.log('Voy a saltar a idProveedor');
            console.log('Parametros cajas.' + caja.parametros);
            var dato = caja.darValor();
            var d_focus = 'id_proveedor';
            // Tenemos que saber primero si esta disabled o no .
            if ($('#id_proveedor').prop("disabled") == true) {
                // Ya ponemos focus a entrada productos ( campo por defecto)
                d_focus = ObtenerFocusDefectoEntradaLinea();
            }
            var controlSalto = 'Si' ; // variable que utilizo para indicar si salto o no, por defecto si.
            
			if (caja.id_input=="Proveedor"){
				if ( dato.length !== 0){
					controlSalto = 'No'; // No salto
				}
			}
			ponerFocus(d_focus);
		break;

        case 'Saltar_desde_fecha':
            console.log('Saltar desde fecha');
            cabecera.fecha=caja.darValor();
            var d_focus = 'id_proveedor';
            // Comprobamos de donde ( albaran, factura, pedido )
            if (caja.darParametro('dedonde') == "albaran" ){
                d_focus = "hora";
            }
            if (caja.darParametro('dedonde') == "factura"){
                d_focus = "suNumero";
            }
			ponerFocus(d_focus);
		break;

        case 'Saltar_Proveedor':
			var dato = caja.darValor();
				if(dato==0){
					var d_focus = 'Proveedor';
					ponerFocus(d_focus);
				}
		break;

        case 'Saltar_idArticulo':
			var dato = caja.darValor();
			if(dato==0){
					var d_focus = 'idArticulo';
					ponerFocus(d_focus);
			}
		break;

        case 'Saltar_fecha':
			var dato = caja.darValor();
			var d_focus = 'fecha';
			ponerFocus(d_focus);
		break;

        case 'Saltar_Referencia':
			var dato = caja.darValor();
			if(dato==0){
				var d_focus = 'Referencia';
				ponerFocus(d_focus);
			}
			
		break;

        case 'Saltar_ReferenciaPro':
			var dato = caja.darValor();
			if(dato==0){
				var d_focus = 'ReferenciaPro';
				ponerFocus(d_focus);
			}
		break;
		case 'Saltar_CodBarras':
			var dato = caja.darValor();
			if(dato==0){
				var d_focus = 'Codbarras';
				ponerFocus(d_focus);
			}
		break;

        case 'Saltar_Descripcion':
			var dato = caja.darValor();
			if(dato==0){
				var d_focus = 'Descripcion';
				ponerFocus(d_focus);
			}
		break;

        case 'addPedidoAlbaran':
			buscarAdjunto(caja.darParametro('dedonde'), caja.darValor());
		break;
		
		case 'buscarUltimoCoste':
			var nfila = parseInt(caja.fila)-1;
			if (caja.tipo_event !== "blur"){
				var costeAnt=productos[nfila].ultimoCoste;
				var idArticulo=productos[nfila].idArticulo;
                console.log("Número de productos:"+productos.length);
                console.log("Esto en la fila:"+parseInt(caja.fila));
					if (parseFloat(costeAnt)===parseFloat(caja.darValor())){
                        if(parseInt(caja.fila)==productos.length){
                             ponerFocus( ObtenerFocusDefectoEntradaLinea());
                        }
					}else {
						if(valor=""){
                        alert("NO HAS INTRODUCIDO NINGÚN COSTE");
                        }else{
                            productos[nfila].CosteAnt=costeAnt;
                            addCosteProveedor(idArticulo, caja.darValor(), nfila, caja.darParametro('dedonde'));
                            if (caja.tipo_event !== "blur"){
                                if(parseInt(caja.fila)==productos.length){
                                    ponerFocus( ObtenerFocusDefectoEntradaLinea());
                                }
                            }
                        }
                    }
            }
		break;
		
	}
}
function addCosteProveedor(idArticulo, valor, nfila, dedonde){
	// @Objetivo: Añadir o modificar el coste de un producto
	// @Parametros: 
	//      idArticulo: el id del articulo del producto
	//      idProveedor: el id del proveedor
	//      valor: valor nuevo 
	//      dedonde: donde estamos, si en albaranes o facturas 
	//      nfila: número de la fila que estamos cambiando
	console.log("Entre en addCosteProveedor");
	productos[nfila].importe=parseFloat(valor)*productos[nfila].nunidades;
	var id = '#N'+productos[nfila].nfila+'_Importe';
	importe = productos[nfila].importe.toFixed(2);
	productos[nfila].ultimoCoste=valor;	
	$(id).html(importe);
	addTemporal(dedonde);
}

function buscarAdjunto(dedonde, valor=""){
	//@Objetivo:
    //  Cada vez que vamos a adjuntar un pedido/albarann a un albaran/factura ejecutamos esta función que 
	//  carga tanto los productos del adjunto como realiza la comprobación de si ya existe ....
	//@Parametros: 
	//  dedonde:desde donde estamos ejecutando la función
	//  valor: numero de pedido o albarán que vamos a adjuntar
	console.log("Entre en buscarAdjunto");
	console.log(dedonde);
	var parametros ={
		'pulsado':'buscarAdjunto',
		'numReal':valor,
		'idProveedor':cabecera.idProveedor,
		'dedonde':dedonde
	};
	$.ajax({
        data       : parametros,
        url        : 'tareas.php',
        type       : 'post',
        beforeSend : function () {
            console.log('******** estoy en buscar pedido JS****************');
        },
        success    :  function (response) {
            console.log('Llegue devuelta respuesta de buscar pedido');
            var resultado =  $.parseJSON(response); 
            console.log(resultado);
            var HtmlPedidos=resultado.html;
            if (resultado.error){
                alert('Error de SQL'+respuesta.consulta);
            } else {
                if (valor==""){ // Si el valor esta vacio mostramos el modal con los pedidos de ese proveedor
                    if (dedonde=="albaran"){
                        var titulo = 'Listado Pedidos ';
                    }else{
                        var titulo= 'Listado Albaranes';
                    }
                    abrirModal(titulo, HtmlPedidos);
                    
                }else{
                    if (resultado.Nitems>0){
                        console.log("entre en resultados numero de items");
                        var bandera=0;
                        if (dedonde=="albaran"){
                            var adjuntos=pedidos;
                        }else{
                            var adjuntos=albaranes;
                        }
                        for(i=0; i<adjuntos.length; i++){//recorre todo el array de arrays de pedidos
                            console.log("entre en el for");
                            var numeroReal=adjuntos[i].NumAdjunto;
                            var numeroNuevo=resultado['datos'].NumAdjunto;
                            if (numeroReal == numeroNuevo){// Si el número del pedido introducido es igual que el número de pedido
                            //del array pedidos entonces la bandera es igual a 1
                                bandera=bandera+1;
                            }
                        }
                            if (bandera==0){
                                var datos = [];
                                datos = resultado['datos'];
                                var datos = [];
                                datos = resultado['datos'];
                                n_item=parseInt(adjuntos.length)+1;
                                datos.nfila=n_item;
                                if (dedonde=="albaran"){
                                    pedidos.push(datos);
                                }else{
                                    albaranes.push(datos);
                                }
                                productosAdd=resultado.productos;
                                console.log(productosAdd);
                                var prodArray=new Array();
                                for (i=0; i<productosAdd.length; i++){
                                    // Array de arrays de productos metemos los productos de ese pedido
                                    // cargamos todos los datos en un objeto y por ultimo lo añadimos a los productos que ya tenemos
                                    var prod = {
                                            'articulo_name' : productosAdd[i].cdetalle,
                                            'codBarras'     : productosAdd[i].ccodbar,
                                            'crefProveedor' : productosAdd[i].ref_prov,
                                            'crefTienda'    : productosAdd[i].cref,
                                            'idArticulo'    : productosAdd[i].idArticulo,
                                            'iva'           : productosAdd[i].iva,
                                            'coste'         : productosAdd[i].costeSiva,
                                            'unidades'      : productosAdd[i].nunidades,
                                            'estado'        : productosAdd[i].estadoLinea
                                    }
                                    //~ console.log(productosAdd[i].nunidades);
                                    prod = new ObjProducto(prod);
                                    console.log(prod);
                                    if (dedonde=="albaran"){
                                        prod.numPedido=productosAdd[i].Numpedpro;
                                        prod.idpedpro=productosAdd[i].idpedpro;
                                    }else{
                                        prod.numAlbaran=productosAdd[i].Numalbpro;
                                        prod.idalbpro=productosAdd[i].idalbpro;
                                    }
                                    var numAdjunto=resultado['datos'].NumAdjunto;
                                    var idAdjunto=resultado['datos'].idAdjunto;
                                    productos.push(prod);
                                    prodArray.push(prod);
                                }
                                addTemporal(dedonde);
                                modificarEstado(dedonde, "Facturado",  idAdjunto);
                                //Agregamos una nueva fila con los datos principales de pedidos
                                AgregarAdjunto(datos, dedonde);
                                    console.log(prodArray);
                                    console.log(dedonde);
                                AgregarFilasProductos(prodArray, dedonde,datos);

                                //Cierro el modal aqui por que cuando selecciono un pedido del modal llamo a esta misma funcion
                                //Pero metiendo el numero del pedido de esta manera el valor de busqueda ya es un numero y no vuelve 
                                // a mostrar el modal si no que entra en la segunda parte del if que tenemos mas arriba 
                                cerrarPopUp();
                                
                            }
                    }
                }
            }
        }
	});
}

function modificarEstado(dedonde, estado, id=""){
	//~ @Objetivo: Modificar el estado según el id que llegue y de donde para poder filtrar
	//~ @Parametros : el estado se envia en la función
    console.log("Entre en modificar estado pedido");
    var parametros = {
        "pulsado"   : 'modificarEstado',
        "id"        : id,
        "estado"    : estado,
        "dedonde"   : dedonde
    };
    $.ajax({
        data       : parametros,
        url        : 'tareas.php',
        type       : 'post',
        beforeSend : function () {
            console.log('******** estoy en Modificar estado pedido js****************');
        },
        success    :  function (response) {
            console.log('Llegue devuelta respuesta de estado pedido js');
            var resultado =  $.parseJSON(response); 
            if (resultado.error){
                alert('Error de SQL'+respuesta.consulta);
            }
        }
	});
}

function metodoClick(pulsado,adonde){
	console.log("Inicimos switch de control pulsar");
	switch(pulsado) {
		case 'Ver':
			console.log('Entro en Ver'+adonde);
			// Cargamos variable global ar checkID = [];
			checkID = leerChecked('check_'+ adonde);
			if (checkID.length >1 || checkID.length=== 0) {
				alert ('Que items tienes seleccionados? \n Solo puedes tener uno seleccionado');
				return
			}
			// Ahora Redirijo a  
            window.location.href = './'+adonde+'.php?id='+$('#'+checkID[0]).val();
			break;
		case 'AgregarPedido':
			console.log('entro en agregar producto');
			window.location.href = './pedido.php';
			
			break;
		case 'AgregarAlbaran':
			window.location.href = './albaran.php';
			break;
		case 'AgregarFactura':
			window.location.href = './factura.php';
			break;
		
	 }
} 
function imprimir(id, dedonde, idTienda){
	// @Objetivo: Imprimir el documento que se ha seleccionado
	// @parametros: 
		// id: id del documento
		// dedonde: de donde es para poder filtrar
		// idTienda : id de la tienda 
	var parametros = {
		"pulsado"   : 'datosImprimir',
		"dedonde"   : dedonde,
		"id"        : id,
		"idTienda"  : idTienda
	};
	$.ajax({
			data       : parametros,
			url        : 'tareas.php',
			type       : 'post',
			beforeSend : function () {
				console.log('******** estoy en datos Imprimir JS****************');
			},
			success    :  function (response) {
				 var resultado =  $.parseJSON(response); 
				 window.open(resultado);// Abre una nuvea pestaña con el documento pdf que se generó anteriormente
		}
		
	});
	
		
}
function buscarProveedor(dedonde, idcaja, valor='', popup=''){
	// @Objetivo: Buscar y comprobar que la busqueda de proveedor es correcta 
	// @parametros: 
	//      dedonde -> De donde venimos 
	//      idCaja  -> La utilizamos en tareas para comprobaciones
	//      valor   -> valor que vamos a buscar
	//      popup   -> si viene de popup cerramos la ventana modal
	console.log('FUNCION buscarProveedores JS-AJAX');
    
    var parametros = {
		"pulsado"   : 'buscarProveedor',
		"busqueda"  : valor,
		"dedonde"   : dedonde,
		"idcaja"    : idcaja
	};
    $.ajax({
        data       : parametros,
        url        : 'tareas.php',
        type       : 'post',
        beforeSend : function () {
            console.log('******** estoy en buscar Proveedor JS****************');
        },
        success    :  function (response) {
            console.log('Llegue devuelta respuesta de buscar Proveedor');
            var resultado =  $.parseJSON(response); 
            if (resultado.error){
                alert('Error de sql :'+resultado.consulta);
                return;
            }

            if (resultado.Nitems==2){
                alert("El id del proveedor no existe");
                document.getElementById(idcaja).value='';
            }
                if (resultado.Nitems==1){
                    // Si es solo un resultado pone en la cabecera idProveedor ponemos el id devuelto
                    //Desactivamos los input para que no se puede modificar y en el nombre mostramos el valor
                    //Se oculta el botón del botón buscar
                    cerrarPopUp();
                    cabecera.idProveedor=resultado.id;
                    $('#id_proveedor').val(resultado.id);
                    $('#Proveedor').val(resultado.nombre);
                    $('#Proveedor').prop('disabled', true);
                    $('#id_proveedor').prop('disabled', true);
                    $("#buscar").css("display", "none");
                    
                    //Dendiendo de donde venga realizamos unas funciones u otras
                    if (dedonde=="albaran"){
                        comprobarAdjunto(dedonde);
                    }
                    if (dedonde=="factura"){
                        comprobarAdjunto(dedonde);
                    }
                    if (dedonde=="pedidos"){
                        // Si viene de pedido ponemos el foco en idArticulo ya que pedidos no tiene que comprobar nada 
                        //Para poder empezar a meter articulos
                        ponerFocus("idArticulo");
                    }
                    mostrarFila();
                    
                }else{
                    //Si no mostramos un modal con los proveedores según la busqueda
                    var titulo = 'Listado Proveedores ';
                    var HtmlProveedores=resultado.html['html']; 
                    abrirModal(titulo,HtmlProveedores);
                    focusAlLanzarModal('cajaBusquedaproveedor');
                    if(resultado.html['encontrados']){
                        ponerFocus('N_0');
                    }
                }
            

        }
	});
	
}
function comprobarAdjunto(dedonde){
	//@Objetivo: comprobar si el proveedor tiene algun pedido o albaran Guardado que se pueda adjuntar tanto a la factura como al albaran
	console.log("Entre en adjunto proveedor");
	var parametros = {
		"pulsado"       :'comprobarAdjunto',
		"idProveedor"   : cabecera.idProveedor,
		"dedonde"       : dedonde
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
				if (resultado.error){
					alert(resultado.error);
				}else{
					if (resultado.bandera == 1){
						console.log("entre en las opciones");
						$('#tablaAl').css("display", "block");
						$('#numPedidoT').css("display", "block");
						$('#numPedido').css("display", "block");
						$('#buscarPedido').css("display", "block");
						$('#tablaPedidos').css("display", "block");
						ponerFocus('numPedido');
					}else{
                        ponerFocus( ObtenerFocusDefectoEntradaLinea());
					}
				}
				
	
		}
	});
}


function AgregarFilasProductos(datos, dedonde, cabecera ='NO'){
	//@objetivo: 
	//Agregar la fila de productos
	console.log("Estoy en agregar fila productos albaran");
	if (datos.length>1){
		datos = datos.reverse();
	}
	console.log(dedonde);
	var parametros = {
		"pulsado"   : 'htmlAgregarFilasProductos',
		"productos" : datos,
		"dedonde"   : dedonde,
        "cabecera"  : cabecera
	};

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
            console.log(resultado);
            var nuevafila = resultado['html'];
            $("#tabla").prepend(nuevafila);
            if(dedonde=="factura"){
                if(albaranes.length>0){
                    bloquearInput();
                }
            }
            ponerSelect('Unidad_Fila_'+datos.nfila);

        }
	});
}
function bloquearInput(){
	console.log("Elementos js");
	$('#Row0').css('display', 'none');
	$('.unidad').attr("readonly","readonly");
}
function addTemporal(dedonde=""){
	//@Objetivo: añadir un temporal , dependiendo de donde venga se cargan unos parámetros distintos
	//@parámetros:
	//dedonde: de donde venimos , pedidos, albaran, factura
	console.log('FUNCION Añadir temporal JS-AJAX');
	if (dedonde=="pedidos"){
		var pulsado='addPedidoTemporal';
	}
	if (dedonde=="albaran"){
		var pulsado='addAlbaranTemporal';
	}
	if (dedonde=="factura"){
		var pulsado='addFacturaTemporal';
	}
	var parametros = {
		"pulsado"       : pulsado,
		"idTemporal"    : cabecera.idTemporal,
		"idUsuario"     : cabecera.idUsuario,
		"idTienda"      : cabecera.idTienda,
		"estado"        : cabecera.estado,
		"idReal"        : cabecera.idReal,
		"fecha"         : cabecera.fecha,
		"productos"     : JSON.stringify(productos),
		"idProveedor"   : cabecera.idProveedor,
		"hora"          : cabecera.hora
	};
	if (dedonde=="albaran"){
		parametros['pedidos']=pedidos;
		parametros['suNumero']=cabecera.suNumero;
	}
	if (dedonde=="factura"){
		parametros['albaranes']=albaranes;
		parametros['suNumero']=cabecera.suNumero;
	}
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		
		beforeSend : function () {
			console.log('******** estoy en añadir PEDIDO temporal JS****************');
		},
		success    :  function (response) {
			console.log('Llegue devuelta respuesta de añadir  temporal');
			var resultado =  $.parseJSON(response); 
			if (resultado.error){
				alert(resultado.consulta);
			}else{
				var HtmlClientes=resultado.html;//$resultado['html'] de montaje html
				console.log(resultado.id.id);
				if (resultado.existe == 0){
					history.pushState(null,'','?tActual='+resultado.id);
					cabecera.idTemporal=resultado.id;
				}
				// Creo funcion para restear totales.	
				resetearTotales();
				
				total = parseFloat(resultado['totales']['total'])
				$('.totalImporte').html(total.toFixed(2));
				$('#tabla-pie  > tbody ').html(resultado['htmlTabla']);
				
				if (dedonde=="factura"){
					var importe= document.getElementById("Eimporte").value;
					if (importe>0){
						insertarImporte(total);
					}
				}
				
			}
		}
	});
}

function ponerFocus (destino_focus){
	// @ Objetivo:
	// 	Poner focus a donde nos indique el parametro, que debe ser id queremos apuntar.
	console.log('Pongo focus a:'+destino_focus);
	setTimeout(function() {   //pongo un tiempo de focus ya que sino no funciona correctamente
		jQuery('#'+destino_focus.toString()).focus(); 
	}, 50); 

}
function ponerSelect (destino_focus){
	// @ Objetivo:
	// 	Poner focus a donde nos indique el parametro, que debe ser id queremos apuntar.
	console.log('Pongo select a :'+destino_focus);
	setTimeout(function() {   //pongo un tiempo de focus ya que sino no funciona correctamente
		jQuery('#'+destino_focus.toString()).select(); 
	}, 50); 

}

function escribirProductoSeleccionado(campo,cref,cdetalle,ctipoIva,ccodebar,ultimoCoste,id , dedonde, crefProveedor, coste){
	//@Objetivo:
	//Función para escribir el producto seleccionado del modal
	//LO que hacemos en la función es que recibimos los campos del producto que hemos seleccionado y creamos un objeto
	//En el que vamos metiendo los campos (algunos como importe hay que calcularlos)
	//Y dependiendo de donde venga el modal llamamos a una función u otra de esta manera utilizamos esta función estemos donde estemo
	
    var objDatos = {
        'codBarras'     : ccodebar.toString(),
        'articulo_name' : cdetalle.toString(),
        'crefTienda'    : cref.toString(),
        'idArticulo'    : id.toString(),
        'iva'           : ctipoIva.toString(),
        'crefProveedor' : crefProveedor.toString(),
        'coste'         : ultimoCoste.toString()
    };
    var datos = new ObjProducto(objDatos);
    console.log ('Ultimo coste desde listado:'+ultimoCoste);
    if(coste <= 0){
        alert("¡OJO!\nEste producto es NUEVO para este proveedor");
    }
    
    // Falta controlar si tiene coste ese proveedor o no , es decir si es nuevo para ese proveedor.
    productos.push(datos);
    addTemporal(dedonde);
    AgregarFilasProductos(datos, dedonde);
    document.getElementById(campo).value='';


    cerrarPopUp();
		
}
function eliminarFila(num_item, valor=""){
	//@Objetivo:
	//Función para cambiar el estado del producto , deja en estado Eliminado el producto
	console.log("entre en eliminar Fila Producto");
	var line;
	num=num_item-1;
	line = "#Row" + productos[num].nfila;
	productos[num].estado= 'Eliminado';
	$(line).addClass('tachado');
	$(line + "> .eliminar").html('<a onclick="retornarFila('+num_item+', '+"'"+valor+"'"+');"><span class="glyphicon glyphicon-export"></span></a>');
	$("#N" +productos[num].nfila + "_Unidad").prop("disabled", true);
	addTemporal(valor);
	
}
function eliminarAdjunto(numRegistro, dedonde, nfila){
	//@Objetivo: esta acción se ejecuta cuando eleiminamos un pedio o albaran de albaranes o facturas 
	//pone la fila de los datos del pedido y albaran como eliminada y todos sus productos
	//@parámetros:
	//numRegistro: número tanto del pedido como del alabarán
	//dedonde: de donde venimos , pedidos , albaran o factura
	//nfila: número de la fila del pedido o albarán
	console.log("entre en eliminar Fila");
	var line;
	num=nfila-1;
	if (dedonde=="factura"){
		line = "#lineaP" + albaranes[num].nfila;
		albaranes[num].estado= 'Eliminado';
		var idAdjunto=albaranes[num].idAlbaran;
	}
	if (dedonde=="albaran"){
		line = "#lineaP" + pedidos[num].nfila;
		pedidos[num].estado= 'Eliminado';
		
		var idAdjunto=pedidos[num].idPedido;
	}
	$(line).addClass('tachado');
	$(line + "> .eliminar").html('<a onclick="retornarAdjunto('+numRegistro+', '+"'"+dedonde+"'," + nfila+');"><span class="glyphicon glyphicon-export"></span></a>');
		for(i=0;i<productos.length; i++){
			if (dedonde=="albaran"){
				var numProducto=productos[i].numPedido;
				
			}else{
				var numProducto=productos[i].numAlbaran;
			}
			if (numRegistro == numProducto){
					eliminarFila(productos[i].nfila, "bandera");
			}
		}
		
		modificarEstado(dedonde, "Guardado", numRegistro, idAdjunto);
		addTemporal(dedonde);
}
function retornarFila(num_item, valor=""){
	// @Objetivo :
	// Es pasar un producto eliminado a activo.
	console.log("entre en retornar fila producto");
	var line;
	num=num_item-1;
	line = "#Row" +productos[num].nfila;
	// Nueva Objeto de productos.
	productos[num].estado= 'Activo';
	$(line).removeClass('tachado');
	$(line + "> .eliminar").html('<a onclick="eliminarFila('+num_item+' , '+"'"+valor+"'"+');"><span class="glyphicon glyphicon-trash"></span></a>');

    if (productos[num].nunidades == 0) {
        // Nueva Objeto de productos.
        // Antiguo array productos.
        productos[num].nunidades = 1;
    }
    $("#Unidad_Fila_" + productos[num].nfila).prop("disabled", false);
    $("#N" + productos[num].nfila + "_Unidad").prop("disabled", false);
    $("#N" + productos[num].nfila + "_Unidad").val(productos[num].nunidades);
    addTemporal(valor);
}

function retornarAdjunto(numRegistro, dedonde, nfila){
	//@Objetivo: activar pedido o albaran con estado eliminado en albaran o factura
	//@Parámetros:
	//nunRegitro: número del pedido o albarán a activar
	//dedonde: de donde venimos si de albaran o factura
	//nfila: número de la fila
	console.log("entre en retornar fila adjunto");
	var estado="Guardado";
	var line;
	num=nfila-1;
	if (dedonde=="factura"){
		line = "#lineaP" + albaranes[num].nfila;
		albaranes[num].estado= 'activo';
		var idAdjunto=albaranes[num].idAlbaran;
	}
	if (dedonde=="albaran"){
		line = "#lineaP" + pedidos[num].nfila;
		pedidos[num].estado= 'activo';
		var idAdjunto=pedidos[num].idPedido;
	}
	
	$(line).removeClass('tachado');
	$(line + "> .eliminar").html('<a onclick="eliminarAdjunto('+numRegistro+' , '+"'"+dedonde+"', "+nfila+');"><span class="glyphicon glyphicon-trash"></span></a>');
	for(i=0;i<productos.length; i++){
				if (dedonde=="albaran"){
					var numProducto=productos[i].numPedido;
				}else{
					var numProducto=productos[i].numAlbaran;
				}
				
				if (numRegistro==numProducto){
					retornarFila(productos[i].nfila, "bandera");
				}
	}
		num=nfila-1;
		modificarEstado(dedonde, "Facturado", numRegistro, idAdjunto);
		addTemporal(dedonde);
}
function recalculoImporte(cantidad, num_item, dedonde=""){
	
	// @ Objetivo:
	// Recalcular el importe de la fila, si la cantidad cambia.
	// @ Parametros:
	//	cantidad -> Valor ( numerico) de input unidades.
	//	num_item -> El numero que indica el producto que modificamos.
	console.log('Estoy en recalculoImporte');
    if (productos[num_item].nunidades == 0 && cantidad != 0) {
        retornarFila(num_item+1, dedonde);
    } else if (cantidad == 0 ) {
        eliminarFila(num_item+1, dedonde);
    }
    productos[num_item].nunidades = cantidad;
    var bandera=productos[num_item].iva/100;
    var importe=parseFloat(productos[num_item].ultimoCoste)*cantidad;
    var id = '#N'+productos[num_item].nfila+'_Importe';
    importe = importe.toFixed(2);
    productos[num_item].importe=importe;
    $(id).html(importe);
    addTemporal(dedonde);
	
}
function after_constructor(padre_caja,event){
	console.log(padre_caja);
	// @ Objetivo:
	// Ejecuta procesos antes construir el obj. caja.
	// Traemos 
	//		(objeto) padre_caja -> Que es objeto el padre del objeto que vamos a crear 
	//		(objeto) event -> Es la accion que hizo, que trae todos los datos input,button , check.
    if (padre_caja.parametros.prefijo){
        // Si tiene prefijo quiere decir que es una lista, obtenemos id.
        var id = ObtenerIdString(padre_caja.parametros.prefijo,padre_caja.id_input);
        if (id > -1){
            padre_caja.id_input = event.originalTarget.id;
        } else {
            console.log('ERROR_After: No se encontro el prefijo:'+ padre_caja.parametros.prefijo );
            console.log('en el id:'+padre_caja.id_input);
        }
    }
    

	return padre_caja;
}
function before_constructor(caja){
	// @ Objetivo :
	//  Ejecutar procesos para obtener datos despues del construtor de caja.
	//  Estos procesos los indicamos en parametro before_constructor, si hay
	console.log( 'Entro en before');
    if (caja.parametros.prefijo){
        // Si tiene prefijo quiere decir que es una lista, obtenemos id.
        var id = ObtenerIdString(caja.parametros.prefijo,caja.id_input);
        if (id > -1){
            caja.fila = id;
        } else {
            console.log('ERROR_Before: No se encontro el prefijo:'+ caja.parametros.prefijo );
            console.log('en el id:'+caja.id_input);
        }
    }
	if (caja.id_input ==='cajaBusqueda'){
		if (caja.name_cja ==='Codbarras'){
			caja.parametros.campo = cajaCodBarras.parametros.campo;
		}
		if (caja.name_cja ==='Referencia'){
			caja.parametros.campo = cajaReferencia.parametros.campo;
		}
		if (caja.name_cja ==='Descripcion'){
			caja.parametros.campo = cajaDescripcion.parametros.campo;
		}
	}
	if (caja.id_input.indexOf('ultimo_coste') >-1){
		console.log("entro en ultimo_coste_");
		//~ No entiendo muy bien porque hace esto.
		caja.parametros.item_max = productos.length;
		
	}

	console.log('Return caja');
	return caja;	
}
function permitirModificarReferenciaProveedor(idinput){
	//@Objetivo:
	// modificar el input del id para que se pueda modificar la referencia del proveedor articulo
	console.log("Entre en buscar referencia");
	$("#"+idinput).prop('disabled', false);
}
function AgregarAdjunto(datos, dedonde){
	//@	Objetivo: 
	//Esta función la utilizamos desde albarán o desde factura 
	//Desde albaran es para agregar la fila del pedido seleccionado y desde factura para agregar el albaran
	console.log("Estoy en agregar fila Pedido");
	var parametros = {
		"pulsado"   : 'htmlAgregarFilaAdjunto',
		"datos"     : datos,
		"dedonde"   : dedonde
	};
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
function mostrarFila(){
	//@Objetivo: Mostrar la fila principal de articulos
	console.log("mostrar fila");
	$("#Row0").removeAttr("style") ;
	ponerFocus( ObtenerFocusDefectoEntradaLinea());
}

function mover_up(fila,prefijo){
	var d_focus = prefijo+fila;
		// Segun prefijo de la caja seleccionamos o pones focus.
	if ( prefijo === 'Unidad_Fila_'){
		// Seleccionamos
		ponerSelect(d_focus);
	} else {
		ponerFocus(d_focus);
	}
}
function mover_down(fila,prefijo){
	var d_focus = prefijo+fila;
	// Segun prefijo de la caja seleccionamos o pones focus.
	if ( prefijo === 'Unidad_Fila_'){
		// Seleccionamos
		ponerSelect(d_focus);
	} else {
		ponerFocus(d_focus);
	}
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
function pintamosTotales (DesgloseTotal) {
	// Quiere decir que hay datos a mostrar en pie.
	total = parseFloat(DesgloseTotal['totales']['total']) // varible global.
	$('.totalImporte').html(total.toFixed(2));
	// Ahora tengo que pintar los ivas.
	var desgloseIvas = [];
	desgloseIvas.push(DesgloseTotal['totales']['desglose']);
	console.log(desgloseIvas);
	// Ahora recorremos array desglose
	desgloseIvas.forEach(function(desglose){
		console.log('Entro foreah');
		// mostramos los tipos ivas , bases y importes.
		var tipos = Object.keys(desglose);
		for (index in tipos){
			var iva = tipos[index];
			console.log(desglose[iva].base);
			console.log(parseInt(iva));
			$('#line'+parseInt(iva)).css('display','');
			$('#tipo'+parseInt(iva)).html(parseInt(iva)+'%');
			$('#base'+parseInt(iva)).html(desglose[iva].base); 
			$('#iva'+parseInt(iva)).html(desglose[iva].iva);
		}
	});
	
}
function insertarImporte(total){
	//@Objetivo: insertar importe de pago 
	//Parametros: recibe el total de la factura
	//Recogemos primero los valores de entrada , se calcula y se escribe el nuevo registro
var importe= document.getElementById("Eimporte").value;
var fecha=document.getElementById("Efecha").value;
var forma=document.getElementById("Eformas").value;
var referencia=document.getElementById("Ereferencia").value;
if (forma==0){
	alert("NO HAS SELECCIONADO UNA FORMA DE PAGO");
}else{
var parametros = {
		"pulsado"   : 'insertarImporte',
		"importe"   : importe,
		"fecha"     : fecha,
		'forma'     : forma,
		'referencia': referencia,
		'total'     : total,
		"idTemporal": cabecera.idTemporal,
		"idReal"    : cabecera.idReal
	};
	$.ajax({
		data       : parametros,
		url        : 'tareas.php',
		type       : 'post',
		beforeSend : function () {
			console.log('*********  Modificando los importes de la factura  ****************');
		},
		success    :  function (response) {
			console.log('Respuesta de la modificación de los importes');
			var resultado =  $.parseJSON(response);
			if (resultado.error){
				alert('Error de SQL '+resultado.consulta);
			}else{
				if (resultado.mensaje==1){
					//Se muestra el mensaje cuando el importe es superior al de la factura
					alert("El importe introducido no es correcto");
				}else{
					$("#tablaImporte #fila0").after(resultado.html);
					$("#tabla").find('input').attr("disabled", "disabled");
					$("#tabla").find('a').css("display", "none");
				}
			}
			
			
		}
	});
}
	
}
function mensajeCancelar(idTemporal, dedonde){
	var mensaje = confirm("Estas  seguro que quieres cancelar");
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
function ObtenerCajaSiguiente(idCaja){
    // @ Objetivo
    //  Obtener cual es la caja siguiente salto 
    // @ Parametro
    //   idcaja -> la caja actual.
    // @ Devolvemos
    //   d_focus -> string con id caja siguiente.
    var d_focus = '';
    switch(idCaja){
        case 'idArticulo':
            d_focus = 'Referencia';
        break;
        
        case 'Referencia':
            d_focus = 'ReferenciaPro';
        break;

        case 'ReferenciaPro':
            d_focus = 'Codbarras';
        break;

        case 'Codbarras':
            d_focus = 'Descripcion';
        break;
    }
    return d_focus;
}


function ObtenerFocusDefectoEntradaLinea(){
	var valor = $("#salto").val();
	switch(valor){
		case '0':
			d_focus='Referencia';
		break;
		case '1':
			d_focus='idArticulo';
		break;
		case '2':
			d_focus='Referencia';
		break;
		case '3':
			d_focus='ReferenciaPro';
		break;
		case '4':
			d_focus='Codbarras';
		break;
		case '5':
			d_focus='Descripcion';
		break;
		default:
			d_focus='Referencia';
		break;
		
	}
    return d_focus;
}
function abrirIncidenciasAdjuntas(id, modulo, dedonde){
    var parametros = {
            "pulsado"   : 'abrirIncidenciasAdjuntas',
            "id"        : id,
            "modulo"    : modulo,
            "dedonde"   : dedonde
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
// =========================== OBJETOS  ===================================
function ObjProducto(datos)
{
    // @ Objetivo
    // Crear un obj de las propiedades de un producto
    console.log('Estoy creando objeto producto');
    this.idArticulo = datos.idArticulo;
    this.cref = datos.crefTienda;
    this.crefProveedor = datos.crefProveedor;
    this.ccodbar = datos.codBarras;
    this.cdetalle = datos.articulo_name;
    this.iva = datos.iva;
    console.log(datos.unidades);
    if (datos.unidades === undefined){
		this.nunidades = '1.00'; // Valor por defecto.
   		this.ncant = '1.00'; // Valor por defecto.

    } else {
		this.nunidades = datos.unidades;
   		this.ncant = datos.unidades; // Valor por defecto.
	}
    if (datos.estado === undefined){
		this.estado= 'Activo'; // Valor por defecto.
	} else {
		this.estado = datos.estado;
	}
    if (datos.nfila === undefined){
        // Si no enviamos nfila ,cuanta los productos que existe y añade una fila.
        this.nfila = productos.length+1;
    } else {
        this.nfila = datos.nfila;
    }
    
    this.ultimoCoste = datos.coste;
    var importe = parseFloat(this.ultimoCoste) * this.nunidades;
    this.importe = importe.toFixed(2);

    this.getCoste = function(nuevoCoste){
        // Metodo para cambiar Coste y ademas importe del producto.
        this.ultimoCoste = datos.ultimoCoste;
        importe = parseFloat(this.ultimoCoste) * this.nunidades;
        this.importe = importe.toFixed(2);
    }
    
}



