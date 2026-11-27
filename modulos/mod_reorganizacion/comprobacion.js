/*
 * Comprobación de existencias en el cambio de año, desde el listado de la reorganización: un
 * modal elige el ejercicio. En el vigente, un bucle por lotes con su barra, como el resto de
 * operaciones del módulo; en el anterior, la admisión del fichero del vigente.
 *
 * Aquí no se compone pantalla: la tabla llega montada desde el servidor y este
 * fichero solo pide, inserta y descarga.
 */

function ajaxComprobacionStock(parametros, callback) {
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

function campoOcultoComprobacionStock(form, nombre, valor) {
  var input = document.createElement("input");
  input.type = "hidden";
  input.name = nombre;
  input.value = valor;
  form.appendChild(input);
}

function enviarFormularioComprobacionStock(campos) {
  var form = document.createElement("form");
  form.method = "POST";
  form.action = "tareas.php";
  Object.keys(campos).forEach(function (nombre) {
    var valor = campos[nombre];
    if (Array.isArray(valor)) {
      valor.forEach(function (elemento) {
        campoOcultoComprobacionStock(form, nombre, elemento);
      });
    } else {
      campoOcultoComprobacionStock(form, nombre, valor);
    }
  });
  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);
}

// ---------------------------------------------------------------------------
// El modal del listado
// ---------------------------------------------------------------------------

// El filtro del vigente: las familias o los proveedores añadidos con las cajas del cierre de
// stock anual. Solo de un tipo; dentro del tipo, se suman.
var filtroComprobacionStock = { familia: [], proveedor: [] };

function abrirModalComprobacionStock() {
  // @ Objetivo: abrir el modal que elige el ejercicio, con el contenido que la página trae preparado
  filtroComprobacionStock = { familia: [], proveedor: [] };
  abrirModalConTitulo("Comprobar existencias", $("#plantillaModalComprobacionStock").html());
}

function escaparComprobacionStock(texto) {
  return String(texto).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}

function agregarFiltroComprobacionStock(tipo) {
  // @ Objetivo: añadir al filtro la familia o el proveedor que está en su caja
  // @ Devolvemos: false si ya hay del otro tipo; true en otro caso
  var id = tipo === "familia" ? $("#id_familia").val() : $("#id_proveedor").val();
  var nombre = tipo === "familia" ? $("#Familia").val() : $("#Proveedor").val();
  var otro = tipo === "familia" ? "proveedor" : "familia";
  if (!id) {
    return true;
  }
  if (filtroComprobacionStock[otro].length > 0) {
    $("#avisoFiltroComprobacionStock").text("Elija familias o proveedores, no los dos.");
    return false;
  }
  var repetido = filtroComprobacionStock[tipo].some(function (elegido) {
    return elegido.id === String(id);
  });
  if (!repetido) {
    filtroComprobacionStock[tipo].push({ id: String(id), nombre: nombre });
  }
  console.log("Filtro de la comprobación: " + tipo + " " + id);
  $("#avisoFiltroComprobacionStock").text("");
  // Las cajas quedan libres para añadir otro, como al añadir una familia en el cierre
  if (tipo === "familia") {
    $("#id_familia").val("");
    $("#Familia").val("");
    $("#id_familia").prop("disabled", false);
    $("#Familia").prop("disabled", false);
  } else {
    $("#id_proveedor").val("");
    $("#Proveedor").val("");
    $("#id_proveedor").prop("disabled", false);
    $("#Proveedor").prop("disabled", false);
  }
  pintarFiltroComprobacionStock();
  return true;
}

function quitarFiltroComprobacionStock(tipo, id) {
  // @ Objetivo: quitar del filtro una familia o un proveedor
  filtroComprobacionStock[tipo] = filtroComprobacionStock[tipo].filter(function (elegido) {
    return elegido.id !== String(id);
  });
  pintarFiltroComprobacionStock();
}

function pintarFiltroComprobacionStock() {
  // @ Objetivo: enseñar en el modal lo que lleva el filtro
  var html = "";
  ["familia", "proveedor"].forEach(function (tipo) {
    filtroComprobacionStock[tipo].forEach(function (elegido) {
      html += '<li><span class="label label-info">' + escaparComprobacionStock(elegido.nombre || elegido.id) + "</span> "
        + '<button type="button" class="btn btn-xs btn-link text-danger" onclick="quitarFiltroComprobacionStock(\'' + tipo + "', '" + escaparComprobacionStock(elegido.id) + "')\">"
        + '<i class="glyphicon glyphicon-trash"></i></button></li>';
    });
  });
  $("#listaFiltroComprobacionStock").html(html);
}

function idsDelFiltroComprobacionStock(tipo) {
  return filtroComprobacionStock[tipo].map(function (elegido) {
    return elegido.id;
  }).join(",");
}

function iniciarComprobacionStockDesdeModal() {
  // @ Objetivo: lanzar la comprobación del ejercicio elegido en el modal
  var modoEstricto = $("#chkComprobacionStockModoEstricto").is(":checked") ? "1" : "0";
  if ($("#radioComprobacionAnterior").is(":checked")) {
    admitirComprobacionStockAnterior();
  } else {
    // Lo que quede en las cajas sin añadir también cuenta; con familias y proveedores a la vez, no empieza
    if (!agregarFiltroComprobacionStock("familia") || !agregarFiltroComprobacionStock("proveedor")) {
      return;
    }
    comprobarExistenciasVigente(modoEstricto, idsDelFiltroComprobacionStock("familia"), idsDelFiltroComprobacionStock("proveedor"));
  }
  cerrarPopUpConTitulo("Comprobar existencias");
}

// ---------------------------------------------------------------------------
// El panel del resultado, apartado de las operaciones del listado
// ---------------------------------------------------------------------------

function abrirPanelComprobacionStock(ejercicio) {
  // @ Objetivo: enseñar el panel del resultado con el ejercicio que se comprueba y su descarga
  $("#tituloComprobacionStock").text(ejercicio === "anterior" ? "ejercicio anterior" : "ejercicio vigente");
  $("#btnComprobacionStockExportar").prop("disabled", true);
  $("#btnComprobacionStockAnteriorExportar").prop("disabled", true);
  if (ejercicio === "anterior") {
    $("#btnComprobacionStockExportar").hide();
    $("#btnComprobacionStockAnteriorExportar").show();
  } else {
    $("#btnComprobacionStockAnteriorExportar").hide();
    $("#btnComprobacionStockExportar").show();
  }
  $("#panelComprobacionStock").show();
}

function cerrarPanelComprobacionStock() {
  // @ Objetivo: quitar el resultado de la vista
  $("#areaComprobacionStock").html("");
  $("#panelComprobacionStock").hide();
}

// ---------------------------------------------------------------------------
// Ejercicio vigente: el bucle por lotes
// ---------------------------------------------------------------------------

var comprobacionStockVigente = null;

function avisoComprobacionStock(mensaje) {
  return '<div class="alert alert-danger">' + mensaje + "</div>";
}

function comprobarExistenciasVigente(modoEstricto, idsFamiliaFiltro, idsProveedorFiltro) {
  // @ Objetivo: empezar la comprobación del vigente; el servidor fija el contexto, con el filtro si lo hay, y reparte los lotes
  comprobacionStockVigente = { modoEstricto: modoEstricto === "1" ? "1" : "0", lotesRecibidos: [] };
  abrirPanelComprobacionStock("vigente");
  $("#areaComprobacionStock").html("Comprobando…");
  console.log("Comprobación del vigente: empezar, familias " + (idsFamiliaFiltro || "todas") + ", proveedores " + (idsProveedorFiltro || "todos"));

  $.ajax({
    data: {
      pulsado: "iniciarComprobacionStockVigente",
      idsFamiliaFiltro: idsFamiliaFiltro || "",
      idsProveedorFiltro: idsProveedorFiltro || "",
    },
    url: "./tareas.php",
    type: "post",
    success: function (respuesta) {
      var obj = JSON.parse(respuesta);
      if (!obj.ok) {
        $("#areaComprobacionStock").html(obj.html);
        return;
      }
      comprobacionStockVigente.contexto = obj.contexto;
      comprobacionStockVigente.resumen = obj.resumen;
      comprobacionStockVigente.lotes = obj.lotes;
      loteComprobacionStockVigente(0);
    },
    error: function () {
      $("#areaComprobacionStock").html(avisoComprobacionStock("La comprobación no terminó: no se pudo empezar."));
    },
  });
}

function loteComprobacionStockVigente(indice) {
  // @ Objetivo: pedir un lote; al terminar el último, componer
  var total = comprobacionStockVigente.lotes.length;
  if (indice >= total) {
    componerComprobacionStockVigente();
    return;
  }
  BarraProceso(indice, total, "-comprobacion-stock");
  console.log("Comprobación del vigente: lote " + (indice + 1) + " de " + total);

  $.ajax({
    data: {
      pulsado: "loteComprobacionStockVigente",
      contexto: JSON.stringify(comprobacionStockVigente.contexto),
      resumen: comprobacionStockVigente.resumen,
      ids: comprobacionStockVigente.lotes[indice].join(","),
      modoEstricto: comprobacionStockVigente.modoEstricto,
    },
    url: "./tareas.php",
    type: "post",
    success: function (respuesta) {
      var obj = JSON.parse(respuesta);
      if (!obj.ok) {
        // Un lote rechazado para el bucle: no se compone nada a medias.
        $("#areaComprobacionStock").html(obj.html);
        return;
      }
      comprobacionStockVigente.lotesRecibidos.push({ filas: obj.filas, resumen: obj.resumen });
      loteComprobacionStockVigente(indice + 1);
    },
    error: function () {
      $("#areaComprobacionStock").html(avisoComprobacionStock("La comprobación no terminó: falló el lote " + (indice + 1) + " de " + total + "."));
    },
  });
}

function componerComprobacionStockVigente() {
  // @ Objetivo: componer los lotes recibidos, pintar el resultado y habilitar la descarga
  BarraProceso(comprobacionStockVigente.lotes.length, comprobacionStockVigente.lotes.length, "-comprobacion-stock");
  $.ajax({
    data: {
      pulsado: "componerComprobacionStockVigente",
      contexto: JSON.stringify(comprobacionStockVigente.contexto),
      resumen: comprobacionStockVigente.resumen,
      lotes: JSON.stringify(comprobacionStockVigente.lotesRecibidos),
      modoEstricto: comprobacionStockVigente.modoEstricto,
    },
    url: "./tareas.php",
    type: "post",
    success: function (respuesta) {
      var obj = JSON.parse(respuesta);
      $("#areaComprobacionStock").html(obj.html);
      if (!obj.ok) {
        return;
      }
      $("#btnComprobacionStockExportar").prop("disabled", false);
      $("#chkComprobacionStockVigenteTodos").on("change", function () {
        $(".chkComprobacionStockVigenteArticulo").prop("checked", $(this).is(":checked"));
      });
    },
    error: function () {
      $("#areaComprobacionStock").html(avisoComprobacionStock("La comprobación no terminó: no se pudo componer el resultado."));
    },
  });
}

function exportarComprobacionStockVigenteXML() {
  // @ Objetivo: descargar el fichero de intercambio con la misma composición que se pintó
  if (!comprobacionStockVigente || !comprobacionStockVigente.lotes) {
    return;
  }
  var seleccionados = $(".chkComprobacionStockVigenteArticulo:checked")
    .map(function () {
      return $(this).val();
    })
    .get();

  // La selección viaja unida en un solo campo, y aparte cuántos identificadores se
  // envían para que el servidor cuente los que le llegan y compare. Un campo por
  // producto quedaría acotado por cuántos campos admite el motor al leer la
  // petición, y por encima de ese límite descarta el resto sin decirlo.
  enviarFormularioComprobacionStock({
    pulsado: "exportarComprobacionStockXML",
    modoEstricto: comprobacionStockVigente.modoEstricto,
    contexto: JSON.stringify(comprobacionStockVigente.contexto),
    resumen: comprobacionStockVigente.resumen,
    lotes: JSON.stringify(comprobacionStockVigente.lotesRecibidos),
    filtro: seleccionados.join(","),
    filtroDeclarado: seleccionados.length,
  });
}

// ---------------------------------------------------------------------------
// Ejercicio anterior
// ---------------------------------------------------------------------------

var comprobacionStockAnteriorComposicion = null;
var comprobacionStockAnteriorResumen = null;

function admitirComprobacionStockAnterior() {
  var form = document.getElementById("formAdmitirComprobacionStock");
  var formData = new FormData(form);
  formData.append("pulsado", "admitirComprobacionStock");

  abrirPanelComprobacionStock("anterior");
  $("#areaComprobacionStock").html("Comprobando…");

  $.ajax({
    url: "tareas.php",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (resultado) {
      // Lo que se pinta llega montado, sea la tabla o el aviso de que no se pudo.
      // La composición viaja aparte porque es lo que el informe final necesita de
      // vuelta, no lo que se muestra. Con ella viene su resumen, que se guarda tal
      // como llega y se devuelve tal cual: aquí no se recalcula nada, porque un
      // resumen que calculara quien lo devuelve no comprobaría nada.
      $("#areaComprobacionStock").html(resultado.html);
      if (!resultado.ok) {
        return;
      }
      comprobacionStockAnteriorComposicion = resultado.composicion;
      comprobacionStockAnteriorResumen = resultado.resumen;
      $("#btnComprobacionStockAnteriorExportar").prop("disabled", false);
    },
    error: function () {
      $("#areaComprobacionStock").text("Error al comunicar con el servidor.");
    },
  });
}

function exportarInformeComprobacionStockAnterior() {
  if (!comprobacionStockAnteriorComposicion) {
    return;
  }
  enviarFormularioComprobacionStock({
    pulsado: "exportarInformeComprobacionStock",
    composicion: JSON.stringify(comprobacionStockAnteriorComposicion),
    resumen: comprobacionStockAnteriorResumen,
  });
}
