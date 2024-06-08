/* Motivo:
 *  El fichero funciones.js se estaba haciendo eterno, por ello creo este fichero aparte.
 *  
 * Objetivo:
 *  - La function de controladorAcciones suele ser muy grande.
 *  - Poner las funciones que son AccionesDirectas
 *  Las acciones que venimos de controlador eventos (teclados.js) sin ir al controladorAcciones
 * 
 * Nota:
 *  Lo ideal seria que estuvieran clasificados los js por funciones que para cada vista, aunque ahora tal
 *  como esta organizado funciones.js , no puedo hacerlo.
 * */

function controladorAcciones(caja,accion, tecla){
    console.log (' Controlador Acciones: ' +accion);
    switch(accion) {
        case 'addPedidoAlbaran':
            buscarAdjunto(caja.darParametro('dedonde'), caja.darValor());
        break;
        
        case 'buscarUltimoCoste':
            var nfila = parseInt(caja.fila)-1;
            if (caja.tipo_event !== "blur"){
                var costeAnt=productos[nfila].ultimoCoste;
                var idArticulo=productos[nfila].idArticulo;
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
        
        case 'buscarProveedor':
            if( caja.darValor()=="" && caja.id_input=="id_proveedor"){
                // Cuando el valor no tiene datos y estamos id_input pasamos a cja Proveedor
                ObtenerFocus(caja);
            }else{
                buscarProveedor(caja.darParametro('dedonde'),caja.id_input ,caja.darValor());
            }
        break;

        case 'comprobarFecha':
            comprobarFecha(caja);
        break
        
        case 'recalcular_totalProducto':
            // Compruebo que sea correcto
            if (comprobarDecimalNumber(caja.darValor())){
                // recuerda que lo productos empizan 0 y las filas 1
                var nfila = parseInt(caja.fila)-1;
                var valor_anterior = productos[nfila].nunidades;
                productos[nfila].nunidades = caja.darValor();
                
                productos[nfila].ncant = caja.darValor();
                if (valor_anterior !== productos[nfila].nunidades){
                    // Comprobamos si cambio valor , sino no hacemos nada.
                    recalculoImporte(productos[nfila].nunidades, nfila, caja.darParametro('dedonde'));
                    addTemporal(caja.darParametro('dedonde'));
                }
                if (caja.tipo_event !== "blur"){
                    if (caja.darParametro('dedonde') == "pedido"){
                        ponerFocus( ObtenerFocusDefectoEntradaLinea());
                    }else{
                        d_focus='ultimo_coste_'+parseInt(caja.fila);
                        ponerSelect(d_focus);
                    }
                }
            }  else {
                // Debería advertir que esta mal el numero de la caja
            }
            
        break;
        
        case 'cambio_descripcion':
            var nfila = parseInt(caja.fila)-1;
            productos[nfila].cdetalle=caja.darValor();
            console.log('Estoy cambio descripcion,nfila:'+nfila);
            addTemporal(caja.darParametro('dedonde'));
        break;

        case  'saltar_productos':
            if (productos.length >0){
                // Debería añadir al caja N cuantos hay
                ponerSelect('Unidad_Fila_'+productos.length);
            } else {
               console.log( ' No nos movemos ya que no hay productos');
            }
        break;
        
        case 'mover_down':
            // Controlamos si numero fila es correcto.
            var nueva_fila = 0;            
            if ( isNaN(caja.fila) === false){
                nueva_fila = parseInt(caja.fila)+1;
            } 
            mover_down(nueva_fila,caja.darParametro('prefijo'));
            
        break;

        case 'mover_up':
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

        case 'Saltar_hora':
            saltarHora(caja);
        break;

        
        case 'Saltar_idProveedor':
            var dato = caja.darValor();
            var d_focus = 'id_proveedor';
            // Tenemos que saber primero si esta disabled o no .
            if ($('#id_proveedor').prop("disabled") == true) {
                // Ya ponemos focus a entrada productos ( campo por defecto)
                d_focus = ObtenerFocusDefectoEntradaLinea();
            }
            var controlSalto = 'Si' ; // variable que utilizo para indicar si salto o no, por defecto si.
            if (caja.id_input=="Proveedor"){
                // No salto por que tiene valor caja Proveedor
                if ( dato.length !== 0){
                    controlSalto = 'No'; 
                }
            }
            ponerFocus(d_focus);
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
        case 'Saltar_SuNumero':
            var dato = caja.darValor();
            cabecera.suNumero = dato;
            ponerFocus(salto_linea);
        break

        case 'Saltar_Siguiente':
            ObtenerFocus(caja);
        break;
        
    }
}

function ObtenerFocus(caja){
    if (caja.darValor() !==""){
        // Si tiene valor entonces no saltamos directamente , comprobamos que tenemos hacer segun la caja.
        SiTieneValorCajaCabecera(caja);        
    }
   ponerFocus(ObtenerCajaSiguiente(caja.id_input));
    
}


/*  =======================   Acciones Directas   ==================================  */
 
function AccionAddProveedorProducto(caja,event){
    //@Objetivo: añadir una referencia a un proveedor articulo o cambiarla en caso de que exista
    //@Parametros;
    //  idArticulo: id del articulo
    //  nfila:Número de la fila
    //  valor: referencia que le vamos a poner 
    //  coste : coste de la referencia
    //  dedonde: de donde venimos di pedidos , albaranes o facturas
    var nfila = caja.fila-1;
    var idArticulo = productos[nfila].idArticulo;
    var coste = productos[nfila].ultimoCoste;
    var valor = caja.darValor();
    var dedonde = caja.darParametro('dedonde');
    var parametros = {
        "pulsado"    : 'addProveedorArticulo',
        "idArticulo" : idArticulo,
        "refProveedor":valor,
        "idProveedor":cabecera.idProveedor,
        "coste":coste
    };
    $.ajax({
            data       : parametros,
            url        : 'tareas.php',
            type       : 'post',
            beforeSend : function () {
                console.log('********  AccionAddProveedorProducto JS****************');
            },
            success    :  function (response) {
                console.log('*** Respuesta de addProveedorArticulo ***');
                var resultado =  $.parseJSON(response); //Muestra el modal con el resultado html
                if (resultado.error){
                    alert('ERROR DE SQL: '+resultado.error);
                }else{
                    productos[nfila].ref_prov=valor;// pone le valor en el input 
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
    id_input = caja.name_cja;
    idcaja = caja.id_input;
    campo = caja.darParametro('campo');
    busqueda = caja.darValor();
    
    salto_linea= cambiarValorSaltoLinea(campo);
    
    dedonde = caja.darParametro('dedonde');
    if (busqueda !== "" || idcaja === "Descripcion"){
        // Solo ejecutamos si hay datos de busqueda.
        var parametros = {
            "pulsado"    : 'buscarProductos',
            "id_input"   : id_input,
            "valorCampo" : busqueda,
            "campo"      : campo,
            "idcaja"     :idcaja,
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
                console.log('******** Respuesta de FUNCION -> buscarProducto *********');
                var resultado =  $.parseJSON(response);
                console.log(idcaja);
                if (resultado.Nitems ===1 && idcaja!='cajaBusqueda'){
                    // Si recibe un solo resultado
                    // Lo añadimos a productos.
                    // Llamamos addpedidotemporeal
                    // Agremamos fila de producto.
                    console.log (' Entramos en AccionBuscarProducto->Añadir un resultado ');
                    if(resultado['datos'][0]['estadoTabla']=="Baja"){
                        alert("Este producto no se puede adjuntar ya que el estado del producto es BAJA");
                    }else{
                        var datos = new ObjProducto(resultado['datos'][0]);
                        // Me mando fecha Actualizacion, ya que no lo gestiona objeto datos.
                        // Al  igual que el ultimoCoste en el objeto es el coste que obtenemos y mandamos, no ultimoCoste.
                        AntesAgregarFilaProducto(datos,dedonde,resultado['datos'][0]['fechaActualizacion'],resultado['datos'][0]['ultimoCoste']);
                    }                       
                }else{
                    // Si existe resultado.html hay que abril modal o refrescarlo.
                    if (typeof resultado.html !== 'undefined'){
                        // Si no mandamos el resultado html a abrir el modal para poder seleccionar uno de los resultados
                        console.log('= Entro en Estado Listado de funcion buscarProducto =');
                        var HtmlProductos=resultado.html; 
                        var titulo = 'Listado productos encontrados ';
                        abrirModal(titulo,HtmlProductos);
                        // Ahora tenemos que poner focus, pero si estamos en listado, mejor saltar listado
                        if ( idcaja == 'cajaBusqueda' && resultado.Nitems > 0) {
                            var d_focus = 'N_0';
                            ponerFocus(d_focus);
                        } else {
                            focusAlLanzarModal('cajaBusqueda');
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


function ocultarcolumnaImporteIva(){
    // Ocultar columna de importe con iva que mostramos en albaranes..
    if($(".ImporteIva").is(":hidden")){
        $(".ImporteIva").toggle("slow");
        $(".ocultar").toggleClass("glyphicon glyphicon-eye-close").toggleClass('glyphicon glyphicon-eye-open');;
      
     } else{
        $(".ImporteIva").toggle();
        $(".ocultar").toggleClass("glyphicon glyphicon-eye-open").toggleClass('glyphicon glyphicon-eye-close');;
     }
}
