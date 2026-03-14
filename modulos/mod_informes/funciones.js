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

// =====================================================================
//       POSSTOCK — Carga de datos e incidencias
// =====================================================================

/**
 * Llama al endpoint getPOSStockData con las fechas del periodo actual,
 * pinta la tabla de incidencias y muestra la barra de botones de navegación.
 *
 * @param {Object} periodo  Objeto con las 4 fechas y los labels:
 *   { fecha_inicio_movimientos, fecha_fin_movimientos,
 *     fecha_inicio_stock, fecha_fin_stock,
 *     label_movimientos, label_stock }
 */
function cargarDatosPosstock(periodo) {
    var parametros = {
        pulsado:                    "getPOSStockData",
        fecha_inicio_movimientos:   periodo.fecha_inicio_movimientos,
        fecha_fin_movimientos:      periodo.fecha_fin_movimientos,
        fecha_inicio_stock:         periodo.fecha_inicio_stock,
        fecha_fin_stock:            periodo.fecha_fin_stock,
    };

    // Spinner visible, tabla y barra ocultas mientras se carga
    $("#posstockSpinner").show();
    $("#posstockTablaWrap").hide();
    $("#posstockNavegacion").hide();
    $("#posstockBotonesWrap").hide();

    $.ajax({
        data:    parametros,
        url:     "tareas.php",
        type:    "post",
        success: function (response) {
            var resultado = $.parseJSON(response);

            $("#posstockSpinner").hide();

            if (resultado.error) {
                $("#posstockTablaWrap").html(
                    '<div class="alert alert-danger">' + resultado.error + "</div>"
                ).show();
                return;
            }

            // Actualizar labels de periodo
            $("#posstockLabelMovimientos").text(periodo.label_movimientos || "");
            $("#posstockLabelStock").text(periodo.label_stock || "");

            // Pintar tabla
            pintarTablaIncidencias(resultado.filas);

            // Mostrar barra de navegación y tabla
            $("#posstockNavegacion").show();
            $("#posstockBotonesWrap").show();
            $("#posstockTablaWrap").show();

            // Botones exportar/imprimir visibles solo si hay filas
            if (resultado.periodo.total_incidencias > 0) {
                $("#posstockBtnExportar, #posstockBtnImprimir").show();
            } else {
                $("#posstockBtnExportar, #posstockBtnImprimir").hide();
            }
        },
        error: function (request) {
            $("#posstockSpinner").hide();
            $("#posstockTablaWrap").html(
                '<div class="alert alert-danger">Error de comunicación con el servidor.</div>'
            ).show();
            console.error("getPOSStockData error", request);
        },
    });
}

/**
 * Genera el HTML de la tabla de incidencias y lo inyecta en #posstockTablaWrap.
 *
 * @param {Array} filas  Array de incidencias devuelto por getIncidencias().
 */
function pintarTablaIncidencias(filas) {
    var badgeSev = {
        CRITICA: '<span class="label label-danger">Crítica</span>',
        MEDIA:   '<span class="label label-warning">Media</span>',
        BAJA:    '<span class="label label-info">Baja</span>',
    };

    if (!filas || filas.length === 0) {
        $("#posstockTablaWrap").html(
            '<div class="alert alert-success">Sin incidencias detectadas para este periodo.</div>'
        );
        return;
    }

    var html = '<table class="table table-condensed table-hover table-bordered small" id="posstockTabla">';
    html += "<thead><tr>"
        + "<th>Artículo</th>"
        + "<th>Tipo incidencia</th>"
        + "<th>Severidad</th>"
        + "<th>Detalle</th>"
        + "<th>Posible causa</th>"
        + "</tr></thead><tbody>";

    filas.forEach(function (f) {
        var detalle = "";
        if (f.tipo === "Error crítico de stock") {
            detalle = "Stock actual: " + (f.stock_actual !== undefined ? parseFloat(f.stock_actual).toFixed(2) : "—");
        } else if (f.tipo === "Entrada con stock alto") {
            detalle = "Stock previo: " + parseFloat(f.stock_previo).toFixed(2)
                    + " | Entrada: " + parseFloat(f.ncant).toFixed(2)
                    + " | Fecha: " + (f.fecha || "—");
        } else if (f.tipo === "Riesgo de caducidad teórica") {
            detalle = "Últ. venta: " + (f.ultima_venta || "—")
                    + " | " + (f.semanas_sin_venta || "—") + " sem.";
        } else if (f.tipo === "Entrada sin rotación previa") {
            detalle = f.ultima_salida
                ? "Últ. salida: " + f.ultima_salida + " | " + f.semanas_sin_rotacion + " sem."
                : "Sin salidas en el año";
        } else if (f.tipo === "Stock sin entrada anual") {
            detalle = "Stock actual: " + (f.stock_actual !== undefined ? parseFloat(f.stock_actual).toFixed(2) : "—");
        }

        html += "<tr>"
            + "<td>" + f.idArticulo + "</td>"
            + "<td>" + f.tipo + "</td>"
            + "<td>" + (badgeSev[f.severidad] || f.severidad) + "</td>"
            + "<td>" + detalle + "</td>"
            + "<td class='text-muted'>" + (f.posible_causa || "") + "</td>"
            + "</tr>";
    });

    html += "</tbody></table>";
    $("#posstockTablaWrap").html(html);
}

window.cargarDatosPosstock   = cargarDatosPosstock;
window.pintarTablaIncidencias = pintarTablaIncidencias;
