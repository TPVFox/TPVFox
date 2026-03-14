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

// =====================================================================
//       POSSTOCK — Selectores de periodo y barra de navegación
// =====================================================================

var MESES = ["Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic"];

/**
 * Rellena el selector de número de periodo según el tipo elegido.
 * También habilita/deshabilita el botón Generar.
 */
function posstockActualizarNumero() {
    var tipo  = document.getElementById("posstockTipo").value;
    var anio  = parseInt(document.getElementById("posstockAnio").value, 10);
    var sel   = document.getElementById("posstockNumero");
    sel.innerHTML = "";
    sel.disabled  = true;
    document.getElementById("posstockBtnGenerar").disabled = true;
    document.getElementById("posstockAvisoVentana").style.display = "none";

    if (!tipo || !anio) return;

    var opciones = [];

    if (tipo === "semana") {
        // Total semanas ISO del año: 52 o 53 (dic-28 siempre está en la última)
        var dec28 = new Date(anio, 11, 28);
        var totalSem = getISOWeek(dec28);
        for (var s = 1; s <= totalSem; s++) opciones.push({ v: s, t: "Semana " + s });

    } else if (tipo === "quincena") {
        for (var q = 1; q <= 24; q++) {
            var mes   = Math.ceil(q / 2);
            var mitad = (q % 2 === 1) ? "1ª" : "2ª";
            opciones.push({ v: q, t: mitad + " quincena " + MESES[mes - 1] });
        }

    } else if (tipo === "mes") {
        for (var m = 1; m <= 12; m++) opciones.push({ v: m, t: MESES[m - 1] + " " + anio });

    } else if (tipo === "trimestre") {
        opciones = [
            { v: 1, t: "T1 (Ene–Mar)" },
            { v: 2, t: "T2 (Abr–Jun)" },
            { v: 3, t: "T3 (Jul–Sep)" },
            { v: 4, t: "T4 (Oct–Dic)" },
        ];
    }

    sel.innerHTML = '<option value="">— seleccionar —</option>';
    opciones.forEach(function (o) {
        sel.innerHTML += '<option value="' + o.v + '">' + o.t + "</option>";
    });
    sel.disabled = false;
}

/**
 * Calcula el número de semana ISO de una fecha.
 */
function getISOWeek(date) {
    var d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    var dayNum = d.getUTCDay() || 7;
    d.setUTCDate(d.getUTCDate() + 4 - dayNum);
    var yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
}

/**
 * Llama a calcularPeriodoPosstock (AJAX), comprueba la ventana de consolidación
 * y si es válido llama a cargarDatosPosstock() y pinta la barra de botones.
 */
function posstockGenerar() {
    var tipo   = document.getElementById("posstockTipo").value;
    var numero = document.getElementById("posstockNumero").value;
    var anio   = document.getElementById("posstockAnio").value;

    if (!tipo || !numero || !anio) return;

    $.ajax({
        data: { pulsado: "calcularPeriodoPosstock", tipo: tipo, numero: numero, anio: anio },
        url:  "tareas.php",
        type: "post",
        success: function (response) {
            var periodo = $.parseJSON(response);
            if (periodo.error) {
                alert("Error al calcular periodo: " + periodo.error);
                return;
            }

            // ── Restricción ventana_dias ──────────────────────────────
            var hoy        = new Date();
            hoy.setHours(0, 0, 0, 0);
            var limite     = new Date(hoy);
            limite.setDate(limite.getDate() - (window.POSSTOCK_VENTANA_DIAS || 7));
            var ffMov      = new Date(periodo.fecha_fin_movimientos);

            if (ffMov >= limite) {
                document.getElementById("posstockAvisoVentana").style.display = "";
                return;
            }
            document.getElementById("posstockAvisoVentana").style.display = "none";

            // Guardar periodo activo y cargar datos
            window.posstockPeriodoActivo = periodo;
            window.posstockTipoActivo    = tipo;
            window.posstockAnioActivo    = parseInt(anio, 10);

            cargarDatosPosstock(periodo);
            posstockPintarBarra(tipo, parseInt(numero, 10), periodo.total_periodos);
        },
        error: function () { alert("Error de comunicación al calcular el periodo."); }
    });
}

/**
 * Pinta la barra de botones numerados bajo los selectores.
 * El botón del periodo activo queda resaltado (btn-primary).
 */
/**
 * Calcula la fecha_fin_movimientos de un periodo directamente en JS,
 * sin AJAX, para poder marcar botones fuera de ventana.
 */
function posstockFechaFinPeriodo(tipo, n, anio) {
    if (tipo === "mes") {
        return new Date(anio, n, 0); // día 0 del mes siguiente = último día del mes n
    }
    if (tipo === "trimestre") {
        return new Date(anio, n * 3, 0); // último día del mes 3n
    }
    if (tipo === "quincena") {
        var mes = Math.ceil(n / 2);
        if (n % 2 === 1) {
            return new Date(anio, mes - 1, 15); // 1ª quincena → día 15
        } else {
            return new Date(anio, mes, 0);      // 2ª quincena → último día del mes
        }
    }
    if (tipo === "semana") {
        // Lunes de la semana ISO 1: lunes de la semana que contiene el 4-ene
        var jan4     = new Date(anio, 0, 4);
        var dow      = jan4.getDay() || 7; // 1=lun … 7=dom
        var week1Mon = new Date(jan4);
        week1Mon.setDate(jan4.getDate() - (dow - 1));
        var sunday = new Date(week1Mon);
        sunday.setDate(week1Mon.getDate() + (n - 1) * 7 + 6);
        return sunday;
    }
    return null;
}

function posstockPintarBarra(tipo, numeroActivo, totalPeriodos) {
    var wrap = document.getElementById("posstockBotonesPeriodo");
    wrap.innerHTML = "";

    var hoy   = new Date(); hoy.setHours(0, 0, 0, 0);
    var limite = new Date(hoy);
    limite.setDate(limite.getDate() - (window.POSSTOCK_VENTANA_DIAS || 7));
    var anio  = window.posstockAnioActivo || new Date().getFullYear();

    for (var n = 1; n <= totalPeriodos; n++) {
        var etiqueta  = posstockEtiquetaBoton(tipo, n);
        var ffPeriodo = posstockFechaFinPeriodo(tipo, n, anio);
        var enVentana = ffPeriodo && ffPeriodo >= limite;

        var cls, extras;
        if (enVentana) {
            cls    = "btn btn-default btn-xs disabled";
            extras = 'disabled title="Dentro de la ventana de consolidación (' + (window.POSSTOCK_VENTANA_DIAS || 7) + ' días)"';
        } else if (n === numeroActivo) {
            cls    = "btn btn-primary btn-xs";
            extras = '';
        } else {
            cls    = "btn btn-default btn-xs";
            extras = '';
        }

        wrap.innerHTML += '<button type="button" class="' + cls + '" '
            + extras + ' '
            + 'onclick="posstockNavegar(' + n + ')" '
            + 'id="posstockBtn_' + n + '">'
            + etiqueta + "</button> ";
    }

    document.getElementById("posstockBarraBotones").style.display = "";
}

/** Devuelve la etiqueta de un botón de la barra según tipo y número. */
function posstockEtiquetaBoton(tipo, n) {
    if (tipo === "semana")    return "S" + n;
    if (tipo === "mes")       return MESES[n - 1];
    if (tipo === "trimestre") return "T" + n;
    if (tipo === "quincena") {
        var mes   = Math.ceil(n / 2);
        var mitad = (n % 2 === 1) ? "a" : "b";
        return MESES[mes - 1] + mitad;
    }
    return n;
}

/**
 * Navega a otro periodo de la barra sin necesidad de pulsar "Generar".
 * Actualiza el resaltado del botón activo.
 */
function posstockNavegar(numero) {
    var tipo = window.posstockTipoActivo;
    var anio = window.posstockAnioActivo;
    if (!tipo || !anio) return;

    // Ignorar botones dentro de la ventana de consolidación
    var btn = document.getElementById("posstockBtn_" + numero);
    if (btn && btn.disabled) return;

    // Actualizar resaltado de botones (solo los no desactivados)
    var botonesActuales = document.querySelectorAll("[id^='posstockBtn_']");
    botonesActuales.forEach(function (b) {
        if (!b.disabled) b.className = "btn btn-default btn-xs";
    });
    var btnActivo = document.getElementById("posstockBtn_" + numero);
    if (btnActivo) btnActivo.className = "btn btn-primary btn-xs";

    $.ajax({
        data: { pulsado: "calcularPeriodoPosstock", tipo: tipo, numero: numero, anio: anio },
        url:  "tareas.php",
        type: "post",
        success: function (response) {
            var periodo = $.parseJSON(response);
            if (periodo.error) { alert(periodo.error); return; }
            window.posstockPeriodoActivo = periodo;
            cargarDatosPosstock(periodo);
        }
    });
}

// Habilitar botón Generar cuando se elige un número de periodo
document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("posstockNumero").addEventListener("change", function () {
        document.getElementById("posstockBtnGenerar").disabled = (this.value === "");
    });
});

window.posstockActualizarNumero = posstockActualizarNumero;
window.posstockGenerar          = posstockGenerar;
window.posstockNavegar          = posstockNavegar;
