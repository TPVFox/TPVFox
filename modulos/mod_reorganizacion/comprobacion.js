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
  var seleccionados = $(".chkComprobacionStockVigenteArticulo:checked")
    .map(function () {
      return $(this).val();
    })
    .get();

  // La selección viaja unida en un solo campo, y aparte cuántos identificadores se
  // envían para que el servidor cuente los que le llegan y compare. Un campo por
  // producto quedaría acotado por cuántos campos admite el motor al leer la
  // petición, y por encima de ese límite descarta el resto sin decirlo.
  //
  // Aquí no se decide nada sobre el conjunto: ni si está vacío, ni si es completo.
  // Eso lo resuelve quien conoce el conjunto de verdad, que es el servidor al
  // componerlo; lo de aquí es solo lo que hay marcado en pantalla.
  enviarFormularioComprobacionStock({
    pulsado: "exportarComprobacionStockXML",
    modoEstricto: modoEstricto,
    filtro: seleccionados.join(","),
    filtroDeclarado: seleccionados.length,
  });
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
