/*
 * @Copyright 2018, Alagoro Software.
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción
 */

function contarProductosWeb() {
  var callback = function (respuesta) {
    var obj = JSON.parse(respuesta);
    if (obj.totalProductos > 0) {
      var totalProductos = obj.totalProductos;
      $("#bar1").show();
      $("#boton_subir_stock").prop("disabled", true);
      var cantidad = 900;
      if (obj.totalProductos <= 900) {
        cantidad = obj.totalProductos;
      }
      SubirStockWeb(0, cantidad, totalProductos, "1");
    }
  };
  var parametros = {
    pulsado: "contarproductos",
    tipo: "web",
  };

  ajaxStock(parametros, callback);
}

function contarProductosEstoqueables(callback) {
  var parametros = {
    pulsado: "contarproductos",
    tipo: "tpv",
  };
  ajaxStock(parametros, callback);
}

function contarFamiliasProductos(callback) {
  var parametros = {
    pulsado: "contarfamilias",
  };
  ajaxStock(parametros, callback);
}

function RegenerarStock(inicio, pagina, total, idBar) {
  var parametros = {
    pulsado: "generastock",
    inicial: parseInt(inicio),
    pagina: pagina,
    totalProductos: total,
  };

  BarraProceso(inicio, total, idBar);
  ajaxStock(parametros, function (response) {
    var obj = JSON.parse(response);
    if (obj) {
      elementos = obj.elementos;
      actual = obj.actual;
      totalProductos = obj.totalProductos;
      pagina = obj.pagina;

      console.log(obj.stocks);

      if (elementos > 0) {
        RegenerarStock(actual, pagina, totalProductos, idBar);
      } else {
        $("#boton-stock").prop("disabled", false);
      }
    }
  });
}

function SubirStockWeb(inicio, cantidad, total, idBar) {
  var parametros = {
    pulsado: "subirStockYPrecio",
    inicial: parseInt(inicio),
    cantidad: cantidad,
    totalProductos: total,
  };

  BarraProceso(inicio, total, idBar);
  ajaxStock(parametros, function (response) {
    var obj = JSON.parse(response);
    if (obj) {
      elementos = obj.elementos;
      actual = obj.actual;
      totalProductos = obj.totalProductos;
      if (actual < totalProductos) {
        cantidad = totalProductos - actual;
        if (cantidad > 900) {
          cantidad = 900;
        }
        console.log(
          "elementos:" +
            elementos.length +
            " Cantidad:" +
            cantidad +
            " Actual:" +
            actual,
        );
        if (elementos.length > 0) {
          SubirStockWeb(actual, cantidad, totalProductos, idBar);
        }
      } else {
        // fin
        BarraProceso(total, total, idBar);
      }
    }
  });
}

function reorganizarPermisosModulos(inicial, total) {
  var parametros = {
    pulsado: "reorganizarPermisosModulos",
    inicial: inicial,
    total: total,
  };
  console.log("inicial:" + inicial);
  $("#boton_limpiar_permisos").prop("disabled", false);
  BarraProceso(inicial, total, 2);
  ajaxStock(parametros, function (response) {
    var obj = JSON.parse(response);
    console.log(obj);
    inicial = inicial + 1;
    if (inicial < total) {
      reorganizarPermisosModulos(inicial, total);
    } else {
      BarraProceso(inicial, total, 2);
    }
  });
}

function CerrarStockAnoActual(inicio, pagina, familias, idBar, idProveedor) {
  //inicio es el indice de array de familias en el que empezamos
  //pagina es la cantidad de familias a procesar en cada llamada
  //familias es el array con los ids de las familias a procesar
  //idBar es el id de la barra de progreso

  var parametros = {
    pulsado: "cerrarStockAnoActual",
    inicial: parseInt(inicio),
    pagina: pagina,
    familias: JSON.stringify(familias),
    idProveedor: idProveedor,
  };

  BarraProceso(inicio, familias.length, idBar);
  ajaxStock(parametros, function (response) {
    var obj = JSON.parse(response);
    if (obj) {
      elementos = obj.elementos;
      actual = obj.actual;
      totalFamilias = obj.totalFamilias;
      pagina = obj.pagina;
      idProveedor = obj.idProveedor;

      console.log(totalFamilias);

      if (actual < familias.length) {
        CerrarStockAnoActual(actual, pagina, familias, idBar, idProveedor);
      } else {
        BarraProceso(actual, familias.length, idBar);
        $("#boton-cerrar-stock").prop("disabled", false);
      }
    }
  });
}

function ajaxStock(parametros, callback) {
  $.ajax({
    data: parametros,
    url: "./tareas.php",
    type: "post",
    success: callback,
    error: function (request, textStatus, error) {
      console.log(textStatus);
    },
  });
}

// Modal para cerrar stock anual

function modalCerrarStock() {
  var parametros = {
    pulsado: "modalCerrarStock",
    titulo: "Cerrar Stock Anual",
    dedonde: V_JS.dedonde,
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
      console.log("Respuesta de mostrar modal para cerrar stock anual ");
      var resultado = $.parseJSON(response);
      var titulo = "Cerrar Stock Anual";
      abrirModalConTitulo(titulo, resultado.html);
    },
  });
}

// Modal configuracion XML
function abrirConfiguracionXML(seccion, dedonde) {
  var parametros = {
    pulsado: "abrirConfiguracionXML",
    dedonde: V_JS.dedonde,
    seccion: seccion,
  };
  $.ajax({
    data: parametros,
    url: "tareas.php",
    type: "post",
    beforeSend: function () {
      console.log(
        "********* envio para mostrar el modal para configurar XML de cierre de stock anual **************",
      );
    },
    success: function (response) {
      console.log(
        "Respuesta de mostrar modal para configurar XML de cierre de stock anual ",
      );
      cerrarPopUpConTitulo(dedonde);
      var resultado = $.parseJSON(response);
      var titulo = "Configuración XML - " + seccion;
      abrirModalConTitulo(titulo, resultado.html);
    },
  });
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
    url: "../mod_compras/tareas.php",
    type: "post",
    beforeSend: function () {
      console.log("******** estoy en buscar Proveedor JS****************");
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
        var titulo = "Listado Proveedores ";
        cerrarPopUpConTitulo(titulo);
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
        abrirModalConTitulo(titulo, HtmlProveedores);
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

// -- Funciones para modal
function controladorAcciones(caja, accion, tecla) {
  console.log(" Controlador Acciones: " + accion);
  switch (accion) {
    case "buscarProveedor":
      if (caja.darValor() == "" && caja.id_input == "id_proveedor") {
        // Cuando el valor no tiene datos y estamos id_input pasamos a cja Proveedor
        ObtenerFocus(caja);
      } else {
        buscarProveedor(
          caja.darParametro("dedonde"),
          caja.id_input,
          caja.darValor(),
        );
      }
      break;
  }
}

function ponerFocus(destino_focus) {
  // @ Objetivo:
  //  Poner focus a donde nos indique el parametro, que debe ser id queremos apuntar.
  setTimeout(function () {
    //pongo un tiempo de focus ya que sino no funciona correctamente
    jQuery("#" + destino_focus.toString()).focus();
  }, 50);
}
