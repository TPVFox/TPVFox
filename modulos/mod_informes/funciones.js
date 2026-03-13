import * as JSTpv from "./../../lib/js/tpvfox.js";
function metodoClick(pulsado) {
  checkID = JSTpv.TfObtenerCheck("rowCheck");
  console.log(checkID);
  if (checkID.length > 1 || checkID.length === 0) {
    alert(
      "Que items tienes seleccionados? \n Solo puedes tener uno seleccionado"
    );
    return;
  }
  // Ahora obtenemos el valor de la opcion seleccionada.
  let opcion = document.getElementById("opcion" + checkID[0]).value;
  // Ahora montamos rango fecha
  let fecha_inicial = document.getElementById("idFechaInicio").value;
  let fecha_final = document.getElementById("idFechaFinal").value;
  if (fecha_final == "") {
    // Si la fecha inicio esta vacia
    let today = new Date();
    let day = today.getDate();
    let month = today.getMonth() + 1;
    let year = today.getFullYear();
    fecha_final = year + "-" + month + "-" + day;
  }
  if (fecha_inicial == "") {
    // Si la fecha inicio esta vacia
    let today = new Date();

    let year = today.getFullYear();
    fecha_inicial = year + "-01-01";
  }
  if (fecha_inicial > fecha_final) {
    alert(
      "Error:\n Fecha inicial no puede ser posterior a la fecha final\n o la fecha Final esta vacia"
    );
    return;
  }
  AbrirModalLoading(fecha_inicial, fecha_final, opcion);
}
function AbrirModalLoading(fecha_inicial, fecha_final, opcion) {
  var parametros = {
    pulsado: "obtenerLoading",
  };
  $.ajax({
    data: parametros,
    url: "tareas.php",
    type: "post",
    beforeSend: function () {
      console.log("******** Obteniendo Loading****************");
    },
    success: function (response) {
      var resultado = $.parseJSON(response);
      abrirModal("Procesando", resultado.html); // Abre una ventana y muestra el texto
      // Ahora montamos link y redirecionamos
      setTimeout(function () {
        window.open(
          "./informes.php?id=" +
            checkID[0] +
            "&Finicio=" +
            fecha_inicial +
            "&Ffinal=" +
            fecha_final +
            "&opcion=" +
            opcion,
          "_blank"
        );
      }, 5000);
    },
    error: function (request) {
      console.log(request);
    },
  });
}
window.metodoClick = metodoClick;

// =====================================================================
//       POSSTOCK — Configuración
// =====================================================================

function abrirModalConfigPosstock() {
    var parametros = { pulsado: "abrirModalConfigPosstock" };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = $.parseJSON(response);
            ventanaModal(resultado.titulo, resultado.html);
        },
    });
}

function guardarConfigPosstock() {
    var datosFormulario = $("#formConfigPosstock").serializeArray();
    var parametros = {
        pulsado: "guardarConfigPosstock",
        datosFormulario: JSON.stringify(datosFormulario),
    };
    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = $.parseJSON(response);
            if (resultado.error) {
                var htmlError = '<div class="alert alert-danger" role="alert">' + resultado.error + "</div>";
                $("#formConfigPosstock").prepend(htmlError);
            } else {
                alert(resultado.mensaje);
                cerrarModal();
            }
        },
    });
}

window.abrirModalConfigPosstock = abrirModalConfigPosstock;
window.guardarConfigPosstock    = guardarConfigPosstock;
