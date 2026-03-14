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
            abrirModal(resultado.titulo, resultado.html);
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
                cerrarPopUp();
                // Actualizar ventana_dias en JS para que la barra y las restricciones
                // reflejen el nuevo valor sin necesidad de recargar la página.
                if (resultado.ventana_dias !== undefined) {
                    window.POSSTOCK_VENTANA_DIAS = resultado.ventana_dias;
                    // Si hay una barra de botones visible, re-pintarla con el nuevo umbral.
                    if (window.posstockTipoActivo && window.posstockPeriodoActivo) {
                        var numeroActivo = parseInt(
                            document.getElementById("posstockNumero").value, 10
                        ) || 1;
                        posstockPintarBarra(
                            window.posstockTipoActivo,
                            numeroActivo,
                            window.posstockPeriodoActivo.total_periodos
                        );
                    }
                }
                alert(resultado.mensaje);
            }
        },
    });
}

window.abrirModalConfigPosstock = abrirModalConfigPosstock;
window.guardarConfigPosstock    = guardarConfigPosstock;

// =====================================================================
//       POSSTOCK — Filtro de familias
// =====================================================================

// Globals: dos arrays independientes de idFamilia
window.posstockFamiliasIncluir = []; // si no está vacío: solo estas familias
window.posstockFamiliasExcluir = []; // si no está vacío: excluir estas familias

var _posstockFamiliasCache = null;   // caché de la lista completa

/**
 * Abre el modal de dos tablas (consultar / excluir).
 * Carga familias vía AJAX la primera vez; reutiliza caché en sucesivas.
 */
function posstockAbrirFiltroFamilias() {
    if (_posstockFamiliasCache) {
        _posstockMostrarModalFamilias();
        return;
    }
    $.ajax({
        data: { pulsado: "getFamiliasPosstock" },
        url:  "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = $.parseJSON(response);
            if (resultado.error) { alert(resultado.error); return; }
            _posstockFamiliasCache = resultado.familias;
            _posstockMostrarModalFamilias();
        },
        error: function () { alert("Error al cargar familias."); }
    });
}

/** Genera y abre el modal con las dos tablas usando los globals actuales. */
function _posstockMostrarModalFamilias() {
    var html = _htmlPanelFamilia('incluir', 'Familias a consultar',
            'Si hay familias aquí, solo se analizarán estas (y sus subfamilias).',
            'panel-success', 'btn-success', window.posstockFamiliasIncluir)
        + _htmlPanelFamilia('excluir', 'Familias excluidas',
            'Estas familias (y sus subfamilias) serán excluidas del análisis.',
            'panel-danger', 'btn-danger', window.posstockFamiliasExcluir)
        + '<div style="margin-top:8px;text-align:right;">'
        + '<button type="button" class="btn btn-primary btn-sm"'
        +   ' onclick="posstockAplicarFiltroFamilias()">Aplicar filtro</button>'
        + '</div>';

    abrirModal('Filtrar familias — POSStock', html);

    // Poblar el datalist compartido
    var dl = document.getElementById('posstockFamiliasDatalist');
    if (dl && dl.options.length === 0) {
        _posstockFamiliasCache.forEach(function (f) {
            var opt = document.createElement('option');
            opt.value = f.ruta;
            opt.dataset.id = f.id;
            dl.appendChild(opt);
        });
    }
}

/** Genera el HTML de un panel (incluir o excluir) con su tabla y buscador. */
function _htmlPanelFamilia(lista, titulo, descripcion, panelCls, btnCls, idsActivos) {
    var filas = '';
    idsActivos.forEach(function (item) {
        filas += _htmlFilaFamilia(item.id, item.nombre);
    });

    return '<div class="panel ' + panelCls + '" style="margin-bottom:10px;">'
        + '<div class="panel-heading small"><strong>' + titulo + '</strong></div>'
        + '<div class="panel-body" style="padding:8px;">'
        + '<p class="text-muted small" style="margin:0 0 6px;">' + descripcion + '</p>'
        + '<div style="max-height:160px;overflow-y:auto;border:1px solid #ddd;">'
        + '<table class="table table-condensed table-hover" style="margin:0;"'
        +   ' id="posstockTabla_' + lista + '">'
        + '<thead><tr><th class="small">ID</th><th class="small">Familia</th><th></th></tr></thead>'
        + '<tbody>' + filas + '</tbody>'
        + '</table></div>'
        + '<div class="input-group" style="margin-top:6px;">'
        + '<input type="text" class="form-control input-sm" list="posstockFamiliasDatalist"'
        +   ' id="posstockBuscar_' + lista + '" placeholder="Buscar familia…">'
        + '<span class="input-group-btn">'
        + '<button type="button" class="btn btn-sm ' + btnCls + '"'
        +   ' onclick="posstockAgregarFamilia(\'' + lista + '\')">'
        + '<i class="glyphicon glyphicon-plus"></i> Agregar</button>'
        + '</span></div>'
        + '</div></div>'
        + '<datalist id="posstockFamiliasDatalist"></datalist>';
}

/** Genera una fila de tabla para una familia en una lista. */
function _htmlFilaFamilia(id, nombre) {
    return '<tr data-id="' + id + '">'
        + '<td>' + id + '</td>'
        + '<td>' + nombre + '</td>'
        + '<td><button type="button" class="btn btn-xs btn-link text-danger"'
        +   ' onclick="posstockEliminarFamilia(this)">'
        + '<i class="glyphicon glyphicon-trash"></i></button></td>'
        + '</tr>';
}

/**
 * Busca la familia escrita en el input, la añade a la tabla correspondiente
 * si existe en el catálogo y no está duplicada.
 */
function posstockAgregarFamilia(lista) {
    var input = document.getElementById('posstockBuscar_' + lista);
    var texto = input.value.trim();
    if (!texto) return;

    // Buscar en el cache por ruta exacta o por nombre parcial
    var encontrada = null;
    _posstockFamiliasCache.forEach(function (f) {
        if (!encontrada && (f.ruta === texto || f.nombre.trim() === texto)) {
            encontrada = f;
        }
    });
    if (!encontrada) {
        alert('Familia no encontrada. Escribe el nombre exacto de la ruta que aparece en la lista.');
        return;
    }

    // Verificar duplicado en la tabla
    var tabla = document.querySelector('#posstockTabla_' + lista + ' tbody');
    if (tabla.querySelector('tr[data-id="' + encontrada.id + '"]')) {
        alert('Esa familia ya está en la lista.');
        input.value = '';
        return;
    }

    tabla.insertAdjacentHTML('beforeend', _htmlFilaFamilia(encontrada.id, encontrada.nombre.trim()));
    input.value = '';
}

/** Elimina una fila de la tabla de una lista. */
function posstockEliminarFamilia(boton) {
    boton.closest('tr').remove();
}

/**
 * Lee las dos tablas del modal, actualiza los globals y el badge,
 * y cierra el modal.
 */
function posstockAplicarFiltroFamilias() {
    window.posstockFamiliasIncluir = _leerTablaFamilias('incluir');
    window.posstockFamiliasExcluir = _leerTablaFamilias('excluir');
    _posstockActualizarBadgeFiltro();
    cerrarPopUp();
}

/** Lee las filas de una tabla y devuelve array de {id, nombre}. */
function _leerTablaFamilias(lista) {
    var filas = document.querySelectorAll('#posstockTabla_' + lista + ' tbody tr');
    var resultado = [];
    filas.forEach(function (tr) {
        resultado.push({
            id:     parseInt(tr.dataset.id, 10),
            nombre: tr.cells[1].textContent.trim()
        });
    });
    return resultado;
}

/** Actualiza el badge del botón con el resumen activo. */
function _posstockActualizarBadgeFiltro() {
    var badge = document.getElementById('posstockFiltroLabel');
    if (!badge) return;
    var nInc = (window.posstockFamiliasIncluir || []).length;
    var nExc = (window.posstockFamiliasExcluir || []).length;
    if (nInc === 0 && nExc === 0) {
        badge.textContent = 'Todas';
        badge.className   = 'label label-default';
    } else {
        var partes = [];
        if (nInc > 0) partes.push('Consultar: ' + nInc);
        if (nExc > 0) partes.push('Excluir: ' + nExc);
        badge.textContent = partes.join(' · ');
        badge.className   = 'label label-warning';
    }
}

window.posstockAbrirFiltroFamilias   = posstockAbrirFiltroFamilias;
window.posstockAgregarFamilia        = posstockAgregarFamilia;
window.posstockEliminarFamilia       = posstockEliminarFamilia;
window.posstockAplicarFiltroFamilias = posstockAplicarFiltroFamilias;

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
        familias_incluir:           (window.posstockFamiliasIncluir || []).map(function(f){return f.id;}).join(','),
        familias_excluir:           (window.posstockFamiliasExcluir || []).map(function(f){return f.id;}).join(','),
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

    var periodo   = window.posstockPeriodoActivo || {};
    var anio      = window.posstockAnioActivo   || new Date().getFullYear();
    var ffMov     = periodo.fecha_fin_movimientos || "";
    var fiInicio  = anio + "-01-01";

    var html = '<table class="table table-condensed table-hover table-bordered small" id="posstockTabla">';
    html += "<thead><tr>"
        + "<th>Artículo</th>"
        + "<th>Nombre</th>"
        + "<th>Tipo incidencia</th>"
        + "<th>Severidad</th>"
        + "<th>Detalle</th>"
        + "<th>Posible causa</th>"
        + "<th>Listado mayor</th>"
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

        var urlMayor = "../../modulos/mod_producto/DetalleMayor.php"
            + "?idArticulo=" + f.idArticulo
            + "&fecha_inicial=" + fiInicio
            + "&fecha_final="   + ffMov;

        html += "<tr>"
            + "<td>" + f.idArticulo + "</td>"
            + "<td>" + (f.nombre || "—") + "</td>"
            + "<td>" + f.tipo + "</td>"
            + "<td>" + (badgeSev[f.severidad] || f.severidad) + "</td>"
            + "<td>" + detalle + "</td>"
            + "<td class='text-muted'>" + (f.posible_causa || "") + "</td>"
            + "<td><a href='" + urlMayor + "' target='_blank'>"
            + "<i class='glyphicon glyphicon-list-alt'></i> Ver mayor"
            + "</a></td>"
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
        // Semanas ancladas al 01-Ene (misma lógica que PHP).
        var totalSem = _posstockTotalSemanas(anio);
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
 * Devuelve el Date correspondiente al fin de la semana 1 del año
 * (semana 1 = 01-Ene → primer domingo del año).
 * Si el 01-Ene ya es domingo, la semana 1 termina el propio 01-Ene.
 */
function _posstockFinSem1(anio) {
    var jan1 = new Date(anio, 0, 1);
    var dow  = jan1.getDay(); // 0=dom, 1=lun … 6=sab
    if (dow === 0) return jan1;
    return new Date(anio, 0, 1 + (7 - dow));
}

/**
 * Calcula el número total de semanas del año con el sistema anclado al 01-Ene.
 * Semana 1 = 01-Ene → primer domingo. Semanas 2+ = lun→dom.
 */
function _posstockTotalSemanas(anio) {
    var finSem1  = _posstockFinSem1(anio);
    var dec31    = new Date(anio, 11, 31);
    var diasRest = Math.round((dec31 - finSem1) / 86400000);
    return 1 + Math.ceil(diasRest / 7);
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
        // Semanas ancladas al 01-Ene (igual que PHP)
        var finSem1 = _posstockFinSem1(anio);
        if (n === 1) return finSem1;
        var dec31  = new Date(anio, 11, 31);
        var inicio = new Date(finSem1);
        inicio.setDate(finSem1.getDate() + (n - 1) * 7 - 6);
        var fin = new Date(inicio);
        fin.setDate(inicio.getDate() + 6);
        return fin > dec31 ? dec31 : fin;
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
