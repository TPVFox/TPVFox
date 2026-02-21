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

function buscarFamilia(dedonde, idcaja, valor = "", popup = "") {
  // @Objetivo: Buscar y comprobar que la busqueda de familia es correcta
  // @parametros:
  //      dedonde -> De donde venimos
  //      idCaja  -> La utilizamos en tareas para comprobaciones
  //      valor   -> valor que vamos a buscar
  //      popup   -> si viene de popup cerramos la ventana modal
  console.log("FUNCION buscarFamilia JS-AJAX");
  var parametros = {
    pulsado: "buscarFamilias",
    busqueda: valor,
    dedonde: dedonde,
    idcaja: idcaja,
  };
  $.ajax({
    data: parametros,
    url: "tareas.php",
    type: "post",
    beforeSend: function () {
      console.log("******** estoy en buscar Familia JS****************");
    },
    success: function (response) {
      console.log("Llegue devuelta respuesta de buscar Familia");
      var resultado = $.parseJSON(response);
      if (resultado.error) {
        alert("Error de sql :" + resultado.consulta);
        return;
      }
      if (resultado.Nitems == 1 && resultado.html == null) {
        // Si es solo un resultado pone en la cabecera idFamilia ponemos el id devuelto
        //Desactivamos los input para que no se puede modificar y en el nombre mostramos el valor
        //Se oculta el botón del botón buscar
        var titulo = "Listado Familias ";
        cerrarPopUpConTitulo(titulo);
        cabecera.idFamilia = resultado.id;
        $("#id_familia").val(resultado.id);
        $("#Familia").val(resultado.nombre);
        $("#Familia").prop("disabled", true);
        $("#id_familia").prop("disabled", true);

        mostrarFilaFamilia(dedonde);
      } else {
        //Si no mostramos un modal con los proveedores según la busqueda
        var titulo = "Listado Familias ";
        var HtmlFamilias = resultado.html["html"];
        abrirModalConTitulo(titulo, HtmlFamilias);
        if (idcaja !== "cajaBusquedafamilia") {
          focusAlLanzarModal("cajaBusquedafamilia");
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

function catalogoFamilias() {
  var parametros = {
    pulsado: "catalogoFamilias",
    dedonde: V_JS.dedonde,
  };
  $.ajax({
    data: parametros,
    url: "tareas.php",
    type: "post",
    beforeSend: function () {
      console.log("******** estoy en catalogo Familias JS****************");
    },
    success: function (response) {
      console.log("Llegue devuelta respuesta de catalogo Familias");
      var resultado = $.parseJSON(response);
      if (resultado.error) {
        alert("Error de sql :" + resultado.consulta);
        return;
      }
      var titulo = "Catálogo Familias ";
      var HtmlFamilias = resultado.html;
      abrirModalConTitulo(titulo, HtmlFamilias);
      focusAlLanzarModal("cajaBusquedafamilia");
    },
  });
}

function agregarFamilia() {
  var id = document.getElementById("id_familia").value.trim();
  var nombre = document.getElementById("Familia").value.trim();

  if (id === "" || nombre === "") {
    alert("Debe ingresar ID y Nombre");
    return;
  }

  // Verificar duplicado
  if (
    document.querySelector(
      '#tablaFamiliasExcluidas tbody tr[data-id="' + id + '"]',
    )
  ) {
    alert("Ese ID ya existe");
    return;
  }

  var tabla = document.querySelector("#tablaFamiliasExcluidas tbody");

  var fila = document.createElement("tr");
  fila.setAttribute("data-id", id);

  fila.innerHTML =
    '<td class="v-align-middle">' +
    id +
    "</td>" +
    '<td class="v-align-middle">' +
    nombre +
    "</td>" +
    "<td>" +
    '<button type="button" class="btn btn-xs btn-link text-danger" onclick="eliminarFamilia(this)">' +
    '<i class="glyphicon glyphicon-trash"></i>' +
    "</button>" +
    "</td>";

  tabla.appendChild(fila);

  // Si está disabled lo habilitamos
  if ($("#Familia").prop("disabled")) {
    $("#Familia").prop("disabled", false);
    $("#id_familia").prop("disabled", false);
  }

  // limpiar campos
  document.getElementById("id_familia").value = "";
  document.getElementById("Familia").value = "";
}

function eliminarFamilia(boton) {
  var fila = boton.closest("tr");
  fila.remove();
}

function toggleHijos(rutaPadre) {
  // Buscamos todas las filas que "empiecen" por la ruta del padre
  // Ejemplo: si padre es "1", ocultará "1-5", "1-5-12", etc.
  const filas = document.querySelectorAll(
    '#tablaFamiliasJerarquica tr[data-ruta^="' + rutaPadre + '-"]',
  );

  filas.forEach((f) => {
    if (f.style.display === "none") {
      f.style.display = "";
    } else {
      f.style.display = "none";
    }
  });
}

function toggleHijosDirectos(rutaPadre, elemento) {
  // Si el elemento es la TR (fila), buscamos el icono dentro
  const filaBase =
    elemento.tagName === "TR" ? elemento : elemento.closest("tr");
  const icono = filaBase.querySelector(".btn-desplegar-icono");

  if (!icono) return; // Si no tiene icono, no tiene hijos, no hacemos nada

  const nivelPadre = rutaPadre.split("-").length;
  const descendientes = document.querySelectorAll(
    `#tablaFamiliasJerarquica tr[data-ruta^="${rutaPadre}-"]`,
  );

  // Determinamos si vamos a abrir o cerrar basándonos en la rotación actual
  let seVaAAbrir = !icono.classList.contains("rotar-90");

  descendientes.forEach((f) => {
    const rutaHijo = f.getAttribute("data-ruta");
    const nivelHijo = rutaHijo.split("-").length;

    if (seVaAAbrir) {
      if (nivelHijo === nivelPadre + 1) f.style.display = "";
    } else {
      f.style.display = "none";
      // Al cerrar el padre, reseteamos hijos y sus iconos
      const iconoHijo = f.querySelector(".btn-desplegar-icono");
      if (iconoHijo) {
        iconoHijo.classList.remove("rotar-90");
        if (f.classList.contains("nivel-1")) {
          iconoHijo.classList.replace(
            "glyphicon-folder-open",
            "glyphicon-folder-close",
          );
        }
      }
    }
  });

  // Animación y cambio de estado del icono
  if (seVaAAbrir) {
    icono.classList.add("rotar-90");
    icono.classList.replace("glyphicon-folder-close", "glyphicon-folder-open");
  } else {
    icono.classList.remove("rotar-90");
    icono.classList.replace("glyphicon-folder-open", "glyphicon-folder-close");
  }
}

function colapsarTodo() {
  const filas = document.querySelectorAll(
    "#tablaFamiliasJerarquica tr[data-ruta]",
  );
  filas.forEach((f) => {
    const ruta = f.getAttribute("data-ruta");
    // Si contiene un guión, no es raíz, por tanto se oculta
    if (ruta.includes("-")) {
      f.style.display = "none";
    }
    // Reset de iconos
    const btn = f.querySelector(".glyphicon");
    if (btn) {
      btn.classList.remove("rotar-90");
      btn.classList.replace("glyphicon-folder-open", "glyphicon-folder-close");
    }
  });
}

function seleccionarFamilia(idFamilia, nombreFamilia) {
  // Al seleccionar una familia del catálogo, se asigna a la cabecera y se cierra el modal
  cabecera.idFamilia = idFamilia;
  $("#id_familia").val(idFamilia);
  $("#Familia").val(nombreFamilia);
  $("#Familia").prop("disabled", true);
  $("#id_familia").prop("disabled", true);

  cerrarPopUpConTitulo("Catálogo Familias ");
}

function mostrarFilaProveedor(dedonde) {
  //@Objetivo: Mostrar la fila principal de articulos
  $("#Row0").removeAttr("style");
  console.log(dedonde);
  ponerFocus(ObtenerFocusDefectoEntradaLinea());
}

function mostrarFilaFamilia(dedonde) {
  //@Objetivo: Mostrar la fila principal de articulos
  $("#Row0").removeAttr("style");
  console.log(dedonde);
  ponerFocus(ObtenerFocusDefectoEntradaLinea());
}

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
    case "buscarFamilia":
      if (caja.darValor() == "" && caja.id_input == "id_familia") {
        // Cuando el valor no tiene datos y estamos id_input pasamos a cja Familia
        ObtenerFocus(caja);
      } else {
        buscarFamilia(
          caja.darParametro("dedonde"),
          caja.id_input,
          caja.darValor(),
        );
      }
      break;
  }
}

function ObtenerFocus(caja) {
  if (caja.darValor() !== "") {
    // Si tiene valor entonces no saltamos directamente , comprobamos que tenemos hacer segun la caja.
    SiTieneValorCajaCabecera(caja);
  }
  ponerFocus(ObtenerCajaSiguiente(caja.id_input));
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
    case "id_familia":
      buscarFamilia(
        caja.darParametro("dedonde"),
        caja.id_input,
        caja.darValor(),
      );
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
      break;
    case "Proveedor":
      d_focus = "id_proveedor";
      break;
    case "id_proveedor":
      d_focus = "Proveedor";
      break;
    case "Familia":
      d_focus = "id_familia";
      break;
    case "id_familia":
      d_focus = "Familia";
      break;
  }
  return d_focus;
}

function ObtenerFocusDefectoEntradaLinea() {
  return salto_linea;
}

function guardarConfiguracionXML(seccion) {
  // @Objetivo: Guardar la configuración del XML de cierre de stock anual
  // @parametros:
  //      Se recogen los datos del formulario y se envían por AJAX para guardar la configuración
  var datos = obternerDatosFormulario(seccion);

  var parametros = {
    pulsado: "guardarConfiguracionXML",
    seccion: seccion,
    datos: JSON.stringify(datos),
  };
  $.ajax({
    data: parametros,
    url: "tareas.php",
    type: "post",
    beforeSend: function () {
      console.log(
        "********* envio para guardar configuración XML de cierre de stock anual **************",
      );
    },
    success: function (response) {
      console.log(
        "Respuesta de guardar configuración XML de cierre de stock anual ",
      );
      var resultado = $.parseJSON(response);
      if (resultado.error) {
        alert("Error al guardar configuración: " + resultado.mensaje);
        return;
      }
      alert("Configuración guardada correctamente");
      cerrarPopUpConTitulo("Configuración XML - " + seccion);
    },
  });
}

function obternerDatosFormulario(seccion) {
  // @objetivo: dependiendo de la sección que se trate, se recogen los datos del formulario correspondiente y se devuelven en un objeto para enviar por AJAX
  switch (seccion) {
    case "cierre_stock_anual":
      return obtenerDatosFormularioCierreStockAnual();
    default:
      return {};
  }
}

function obtenerDatosFormularioCierreStockAnual() {
  // Recolectar datos del formulario
  idProveedor = $("#id_proveedor").val();
  proveedor = $("#Proveedor").val();
  reescribirAlbaran = $("#reescribir_albaran").is(":checked") ? true : false;
  numProductos = $("#num_productos").val();
  serieApertura = $("#serie_apertura").val();
  serieCierre = $("#serie_cierre").val();

  // Recolectar familias excluidas
  familiasExcluidas = [];
  $("#tablaFamiliasExcluidas tbody tr").each(function () {
    var idFamilia = $(this).find("td:first").text().trim();
    var nombreFamilia = $(this).find("td:nth-child(2)").text().trim();
    familiasExcluidas.push({ id: idFamilia, nombre: nombreFamilia });
  });

  return {
    idProveedor: idProveedor,
    proveedor: proveedor,
    reescribirAlbaran: reescribirAlbaran,
    numProductos: numProductos,
    serieApertura: serieApertura,
    serieCierre: serieCierre,
    familiasExcluidas: familiasExcluidas,
  };
}
