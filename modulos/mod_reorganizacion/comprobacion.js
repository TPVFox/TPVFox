/*
 * Comprobación de existencias en el cambio de año — pantallas del ejercicio vigente
 * y del ejercicio anterior. Las dos pantallas no conviven nunca en el mismo
 * despliegue, pero sí en el mismo repositorio, así que comparten este fichero.
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
// Ejercicio vigente
// ---------------------------------------------------------------------------

function cargarComprobacionStockVigente() {
  var modoEstricto = $("#chkComprobacionStockVigenteModoEstricto").is(":checked") ? "1" : "0";
  $("#btnComprobacionStockVigenteExportar").prop("disabled", true);
  $("#areaComprobacionStockVigente").html("Cargando…");

  ajaxComprobacionStock({ pulsado: "obtenerComprobacionStockVigente", modoEstricto: modoEstricto }, function (respuesta) {
    var obj = JSON.parse(respuesta);
    $("#areaComprobacionStockVigente").html(obj.html);
    if (!obj.ok) {
      return;
    }
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
      // Lo que se pinta llega montado, sea la tabla o el aviso de que no se pudo.
      // La composición viaja aparte porque es lo que el informe final necesita de
      // vuelta, no lo que se muestra.
      $("#areaComprobacionStockAnterior").html(resultado.html);
      if (!resultado.ok) {
        return;
      }
      comprobacionStockAnteriorComposicion = resultado.composicion;
      $("#btnComprobacionStockAnteriorExportar").prop("disabled", false);
    },
    error: function () {
      $("#areaComprobacionStockAnterior").text("Error al comunicar con el servidor.");
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
  });
}
