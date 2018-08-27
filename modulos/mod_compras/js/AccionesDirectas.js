/* Motivo:
 *  El fichero funciones.js se estaba haciendo eterno, por ello creo este fichero aparte.
 *  
 * Objetivo:
 *  Poner las funciones que son AccionesDirectas
 *  Las acciones que venimos de controlador eventos (teclados.js) sin ir al controladorAcciones
 * 
 * Nota:
 *  Lo ideal seria que estuvieran clasificados los js por funciones que para cada vista, aunque ahora tal
 *  como esta organizado funciones.js , no puedo hacerlo.
 * */

 
function AccionAddProveedorProducto(caja,event){
	//@Objetivo: añadir una referencia a un proveedor articulo o cambiarla en caso de que exista
	//@Parametros;
	//  idArticulo: id del articulo
	//  nfila:Número de la fila
	//  valor: referencia que le vamos a poner 
	//  coste : coste de la referencia
	//  dedonde: de donde venimos di pedidos , albaranes o facturas
	console.log("ESTOY EN LA FUNCION AccionAddProveedor PRODUCTO");
    var nfila = caja.fila-1;
    var idArticulo = productos[nfila].idArticulo;
    var coste = productos[nfila].ultimoCoste;
    var valor = caja.darValor();
    var dedonde = caja.darParametro('dedonde');
    
	console.log(nfila);
	var parametros = {
		"pulsado"    : 'addProveedorArticulo',
		"idArticulo" : idArticulo,
		"refProveedor":valor,
		"idProveedor":cabecera.idProveedor,
		"coste":coste
	};
	console.log(parametros);
	$.ajax({
			data       : parametros,
			url        : 'tareas.php',
			type       : 'post',
			beforeSend : function () {
				console.log('******** estoy en buscar clientes JS****************');
			},
			success    :  function (response) {
				console.log('Llegue devuelta respuesta de buscar clientes');
				var resultado =  $.parseJSON(response); //Muestra el modal con el resultado html
				if (resultado.error){
					alert('ERROR DE SQL: '+resultado.error);
				}else{
					productos[nfila].crefProveedor=valor;// pone le valor en el input 
					fila=nfila+1;//sumamos uno a la fila
					var id="#Proveedor_Fila_"+fila;
					if (valor){
						$(id).prop('disabled', true);// desactivar el input para que no se pueda cambiar 
						$(id).val(valor);
					
						$('#enlaceCambio'+fila).css("display", "inline");
						var d_focus='idArticulo';
						ponerFocus(d_focus);
					}
					addTemporal(dedonde);
				}
			}
		});
}

function AccionBuscarProductos (caja,event){
	//@Objetivo: 
	//  Buscar producto es una función que llamamos desde las distintas cajas de busquedas de los productos
	//  Entra en la función de tareas de buscar productos y le envia los parametros
	//  Esta función devuelve el número de busquedas
	console.log('FUNCION AccionBuscarProductos JS- Para buscar con el campo '+caja.id_input);
    id_input = caja.name_cja;
    idcaja = caja.id_input;
    campo = caja.darParametro('campo');
    busqueda = caja.darValor();
    dedonde = caja.darParametro('dedonde');
    
   	if (busqueda !== "" || idcaja === "Descripcion"){
        // Solo ejecutamos si hay datos de busqueda.
        var parametros = {
            "pulsado"    : 'buscarProductos',
            "id_input"	 : id_input,
            "valorCampo" : busqueda,
            "campo"      : campo,
            "idcaja"	 :idcaja,
            "idProveedor": cabecera.idProveedor,
            "dedonde":dedonde
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
                if (resultado['Nitems']===2){
                        alert("El elemento buscado no está relacionado con ningún producto");
                }else{
                    if (resultado['Nitems']===1){
                        // Si recibe un solo resultado
                        // Lo añadimos a productos.
                        // Llamamos addpedidotemporeal
                        // Agremamos fila de producto.
                        console.log (' Entramos en AccionBuscarProducto->Añadir un resultado ');
                        console.log(resultado['datos'][0]);
                        if(resultado['datos'][0]['estadoTabla']=="Baja"){
                            alert("Este producto no se puede adjuntar ya que el estado del producto es BAJA");
                        }else{
                            
                        
                            var datos = new ObjProducto(resultado['datos'][0]);
                           
                            
                            if (resultado['datos'][0]['coste']<=0){
                                datos.getCoste(resultado['datos'][0]['ultimoCoste']);
                                alert("¡OJO!\nEste producto es NUEVO para este proveedor");
                                 //~ alert(resultado['datos'][0]['ultimoCoste']);
                            }
                            
                            
                            productos.push(datos);
                            addTemporal(dedonde)
                            document.getElementById(id_input).value='';
                            console.log("muestro fecha");
                            console.log(resultado['datos'][0]);
                             if(resultado['datos'][0]['fechaActualizacion']!=null){
                                 
                                fechaProducto= resultado['datos'][0]['fechaActualizacion'].split("-");
                                fechaProducto=new Date(fechaProducto[2], fechaProducto[1] - 1, fechaProducto[0]);
                                fechaCabecera= cabecera.fecha.split("-");
                                fechaCabecera=new Date(fechaCabecera[2], fechaCabecera[1] - 1, fechaCabecera[0]);
                                console.log(fechaCabecera);
                                if(fechaProducto>fechaCabecera)
                                {
                                     alert("El producto que vas a añadir tiene un coste que fue actualizado con fecha superior a la del albarán");
                                }
                            }
                             
                            //  Añado linea de producto.
                            AgregarFilasProductos(datos, dedonde);
                            // ¿¿¿ Creo que no permitimos entonces tabla para añadir albaranes... 
                            if (dedonde=="factura"){
                                $("#tablaAl").hide();
                            }
                        }
                        
                        
                    }else{
                        // Si no mandamos el resultado html a abrir el modal para poder seleccionar uno de los resultados
                        console.log('=== Entro en Estado Listado de funcion buscarProducto =====');
                    
                        var busqueda = resultado.listado; 
                        
                        var HtmlProductos=busqueda['html']; 
                        
                        console.log(HtmlProductos);
                        var titulo = 'Listado productos encontrados ';
                        abrirModal(titulo,HtmlProductos);
                        focusAlLanzarModal('cajaBusqueda');
                        console.log(id_input);
                        console.log(resultado.Nitems);
                        if (resultado.html.encontrados >0 ){
                            // Quiere decir que hay resultados por eso apuntamos al primero
                            // focus a primer producto.
                            if(id_input=="Descripcion"){
                                var d_focus = 'N_0';
                                ponerFocus(d_focus);
                            }
                            
                         }
                    }
                }
            }
        });
    } else {
        // No hay contenido en la caja por lo que ponemos focus a siguiente caja.
        console.log('Saltamos a ' + ObtenerCajaSiguiente(idcaja));
        ponerFocus(ObtenerCajaSiguiente(idcaja));
    }
}
