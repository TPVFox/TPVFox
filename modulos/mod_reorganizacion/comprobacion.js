/*
 * Comprobación de existencias en el cambio de año — pantallas del ejercicio vigente
 * y del ejercicio anterior. Las dos pantallas no conviven nunca en el mismo
 * despliegue, pero sí en el mismo repositorio, así que comparten este fichero.
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
// Ejercicio vigente
// ---------------------------------------------------------------------------

function cargarComprobacionStockVigente() {
  var modoEstricto = $("#chkComprobacionStockVigenteModoEstricto").is(":checked") ? "1" : "0";
  $("#btnComprobacionStockVigenteExportar").prop("disabled", true);
  $("#areaComprobacionStockVigente").html("Cargando…");

  ajaxComprobacionStock({ pulsado: "obtenerComprobacionStockVigente", modoEstricto: modoEstricto }, function (respuesta) {
    var obj = JSON.parse(respuesta);
    if (!obj.ok) {
      $("#areaComprobacionStockVigente").html('<div class="alert alert-danger">' + obj.motivo + "</div>");
      return;
    }
    $("#areaComprobacionStockVigente").html(obj.html);
    $("#btnComprobacionStockVigenteExportar").prop("disabled", false);
    $("#chkComprobacionStockVigenteTodos").on("change", function () {
      $(".chkComprobacionStockVigenteArticulo").prop("checked", $(this).is(":checked"));
    });
  });
}

function exportarComprobacionStockVigenteXML() {
  var modoEstricto = $("#chkComprobacionStockVigenteModoEstricto").is(":checked") ? "1" : "0";
  var totalArticulos = $(".chkComprobacionStockVigenteArticulo").length;
  var seleccionados = $(".chkComprobacionStockVigenteArticulo:checked")
    .map(function () {
      return $(this).val();
    })
    .get();

  var campos = { pulsado: "exportarComprobacionStockXML", modoEstricto: modoEstricto };
  // Si están todos seleccionados no se declara filtro: el conjunto emitido es el completo.
  if (seleccionados.length > 0 && seleccionados.length < totalArticulos) {
    campos["filtro[]"] = seleccionados;
  }

  enviarFormularioComprobacionStock(campos);
}

// ---------------------------------------------------------------------------
// Ejercicio anterior
// ---------------------------------------------------------------------------

var comprobacionStockAnteriorComposicion = null;

var ETIQUETAS_ESTADO_COMPROBACION = {
  seguro: "Seguro",
  no_seguro: "No seguro",
  dudoso: "Dudoso",
  no_comparable: "No comparable",
};

function admitirComprobacionStockAnterior() {
  var form = document.getElementById("formAdmitirComprobacionStock");
  var formData = new FormData(form);
  formData.append("pulsado", "admitirComprobacionStock");

  $("#areaComprobacionStockAnterior").html("Comprobando…");
  $("#btnComprobacionStockAnteriorExportar").prop("disabled", true);

  $.ajax({
    url: "tareas.php",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function (resultado) {
      if (!resultado.ok) {
        $("#areaComprobacionStockAnterior").html('<div class="alert alert-danger">' + resultado.message + "</div>");
        return;
      }
      comprobacionStockAnteriorComposicion = resultado.composicion;
      $("#areaComprobacionStockAnterior").html(htmlTablaComprobacionStockAnterior(comprobacionStockAnteriorComposicion));
      $("#btnComprobacionStockAnteriorExportar").prop("disabled", false);
    },
    error: function () {
      $("#areaComprobacionStockAnterior").html('<div class="alert alert-danger">Error al comunicar con el servidor.</div>');
    },
  });
}

function htmlTablaComprobacionStockAnterior(composicion) {
  // La clasificación se pinta en el mismo orden en que llegó, sin ordenar por
  // gravedad, y cada estado siempre va acompañado de los dos números que lo
  // justifican: nunca se etiqueta como error.
  var html = "<p>" + composicion.filas.length + " producto(s) admitido(s).</p>";
  html += '<table class="table table-bordered table-hover">';
  html +=
    "<thead><tr>" +
    "<th>Artículo</th><th>Estado</th><th>Marcado</th><th>Condiciones conocidas</th>" +
    "<th>Existencia exigida</th><th>Stock justificado</th>" +
    "</tr></thead><tbody>";
  composicion.filas.forEach(function (fila) {
    html +=
      "<tr>" +
      "<td>" +
      fila.idArticulo +
      "</td>" +
      '<td><span class="label label-default">' +
      (ETIQUETAS_ESTADO_COMPROBACION[fila.estado] || fila.estado) +
      "</span></td>" +
      "<td>" +
      (fila.marcado ? "Sí" : "No") +
      "</td>" +
      "<td>" +
      (fila.condicionesConocidas.join(", ") || "—") +
      "</td>" +
      "<td>" +
      fila.existenciaExigida +
      "</td>" +
      "<td>" +
      (fila.stockJustificado !== null ? fila.stockJustificado : "—") +
      "</td>" +
      "</tr>";
  });
  html += "</tbody></table>";
  return html;
}

function exportarInformeComprobacionStockAnterior() {
  if (!comprobacionStockAnteriorComposicion) {
    return;
  }
  enviarFormularioComprobacionStock({
    pulsado: "exportarInformeComprobacionStock",
    composicion: JSON.stringify(comprobacionStockAnteriorComposicion),
  });
}
