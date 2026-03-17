import * as JSTpv from "./../../lib/js/tpvfox.js";

// ── Informes legacy (no POSStock) ─────────────────────────────────────────────
function metodoClick() {
    checkID = JSTpv.TfObtenerCheck("rowCheck");
    console.log(checkID);
    if (checkID.length > 1 || checkID.length === 0) {
        alert(
            "Que items tienes seleccionados? \n Solo puedes tener uno seleccionado",
        );
        return;
    }
    let opcion         = document.getElementById("opcion" + checkID[0]).value;
    let fecha_inicial  = document.getElementById("idFechaInicio").value;
    let fecha_final    = document.getElementById("idFechaFinal").value;
    if (fecha_final === "") {
        let today     = new Date();
        fecha_final   = today.getFullYear() + "-" + (today.getMonth() + 1) + "-" + today.getDate();
    }
    if (fecha_inicial === "") {
        fecha_inicial = new Date().getFullYear() + "-01-01";
    }
    if (fecha_inicial > fecha_final) {
        alert("Error:\n Fecha inicial no puede ser posterior a la fecha final\n o la fecha Final esta vacia");
        return;
    }
    AbrirModalLoading(fecha_inicial, fecha_final, opcion);
}

function AbrirModalLoading(fecha_inicial, fecha_final, opcion) {
    $.ajax({
        data: { pulsado: "obtenerLoading" },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            abrirModal("Procesando", resultado.html);
            setTimeout(function () {
                window.open(
                    "./informes.php?id=" + checkID[0]
                    + "&Finicio=" + fecha_inicial
                    + "&Ffinal="  + fecha_final
                    + "&opcion="  + opcion,
                    "_blank",
                );
            }, 5000);
        },
        error: function (request) { console.log(request); },
    });
}
window.metodoClick = metodoClick;

// =====================================================================
//       POSSTOCK — Helpers internos
// =====================================================================

/** Muestra un mensaje de error en el área de resultados. */
function _posstockMostrarError(msg) {
    $("#posstockSpinner").hide();
    $("#posstockTablaWrap")
        .html('<div class="alert alert-danger" role="alert">' + msg + "</div>")
        .show();
}

/**
 * Comparador de ordenación para el array de incidencias acumulado entre lotes.
 * El backend ya genera orden_clave para evitar duplicar la lógica de prioridad.
 */
function _posstockSortComparator(a, b) {
    if (a && b && a.orden_clave && b.orden_clave) {
        if (a.orden_clave < b.orden_clave) return -1;
        if (a.orden_clave > b.orden_clave) return  1;
        return (a.idArticulo || 0) - (b.idArticulo || 0);
    }
    return 0;
}

// =====================================================================
//       POSSTOCK — Configuración
// =====================================================================

function abrirModalConfigPosstock() {
    $.ajax({
        data: { pulsado: "abrirModalConfigPosstock" },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) {
                _posstockMostrarError("Error al cargar configuración: " + resultado.error);
                return;
            }
            abrirModal("Configuración POSStock", resultado.html);
        },
        error: function () {
            _posstockMostrarError("Error de comunicación al abrir la configuración.");
        },
    });
}

function guardarConfigPosstock() {
    var datosFormulario = $("#formConfigPosstock").serializeArray();
    var params = { pulsado: "guardarConfigPosstock" };
    datosFormulario.forEach(function (item) { params[item.name] = item.value; });

    $.ajax({
        data: params,
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) {
                var htmlError = '<div class="alert alert-danger alert-sm">' + resultado.error + '</div>';
                $("#formConfigPosstock").prepend(htmlError);
                return;
            }
            // Actualizar variables JS con los nuevos valores guardados
            if (resultado.c3b_dias_post      !== undefined) window.POSSTOCK_C3B_DIAS_POST      = resultado.c3b_dias_post;
            if (resultado.c3a_multiplicador  !== undefined) window.POSSTOCK_C3A_MULTIPLICADOR  = resultado.c3a_multiplicador;
            if (resultado.c6b_dias_historico !== undefined) window.POSSTOCK_C6B_DIAS_HISTORICO = resultado.c6b_dias_historico;
            if (resultado.ventana_dias       !== undefined) window.POSSTOCK_VENTANA_DIAS       = resultado.ventana_dias;

            // Si hay un periodo activo, regenerar con la nueva configuración
            if (resultado.ventana_dias !== undefined && window.posstockTipoActivo && window.posstockPeriodoActivo) {
                posstockActualizarNumero(true);
            }

            var htmlOk = '<div class="alert alert-success alert-sm">Configuración guardada.</div>';
            $("#posstockTablaWrap").html(htmlOk).show();
            cerrarModal();
        },
        error: function () {
            _posstockMostrarError("Error de comunicación al guardar la configuración.");
        },
    });
}

/** Muestra/oculta la descripción del modelo estadístico en el modal de config. */
function modalToggleModeloEstadistico() {
    var v     = document.getElementById("modelo_estadistico");
    var bDesc = document.getElementById("modeloEstadisticoDesc");
    if (!v || !bDesc) return;
    if (window._modeloDescPosstock && window._modeloDescPosstock[v.value]) {
        bDesc.textContent = window._modeloDescPosstock[v.value];
    }
}

window.modalToggleModeloEstadistico = modalToggleModeloEstadistico;
window.modalTogglePoissonConfianza  = modalToggleModeloEstadistico;
window.abrirModalConfigPosstock     = abrirModalConfigPosstock;
window.guardarConfigPosstock        = guardarConfigPosstock;

// =====================================================================
//       POSSTOCK — Filtro de familias
// =====================================================================

window.posstockFamiliasIncluir = [];
window.posstockFamiliasExcluir = [];
var _posstockFamiliasCache     = null;

function posstockAbrirFiltroFamilias() {
    $.ajax({
        data: {
            pulsado:               "getFamiliasPosstock",
            familias_incluir_json: JSON.stringify(window.posstockFamiliasIncluir || []),
            familias_excluir_json: JSON.stringify(window.posstockFamiliasExcluir || []),
        },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) {
                _posstockMostrarError("Error al cargar familias: " + resultado.error);
                return;
            }
            _posstockFamiliasCache = resultado.familias;
            abrirModal("Filtrar familias — POSStock", resultado.html);
        },
        error: function () {
            _posstockMostrarError("Error de comunicación al cargar familias.");
        },
    });
}

/** Agrega una familia a la tabla incluir/excluir del modal. */
function posstockAgregarFamilia(lista) {
    var input     = document.getElementById("posstockBuscar_" + lista);
    var val       = input ? input.value.trim() : "";
    var encontrado = null;

    (_posstockFamiliasCache || []).forEach(function (f) {
        if (f.nombre.toLowerCase() === val.toLowerCase()) encontrado = f;
    });
    if (!encontrado) {
        (_posstockFamiliasCache || []).forEach(function (f) {
            if (!encontrado && f.nombre.toLowerCase().includes(val.toLowerCase())) encontrado = f;
        });
    }

    var wrapErr = document.getElementById("posstockFamiliaError_" + lista);
    if (!encontrado) {
        if (wrapErr) wrapErr.textContent = "Familia no encontrada: " + val;
        return;
    }
    if (wrapErr) wrapErr.textContent = "";

    var tabla = document.querySelector("#posstockTabla_" + lista + " tbody");
    var rows  = tabla ? Array.from(tabla.querySelectorAll("tr")) : [];
    if (rows.some(function (r) { return parseInt(r.dataset.id) === encontrado.id; })) {
        if (wrapErr) wrapErr.textContent = "Ya está en la lista.";
        return;
    }
    if (wrapErr) wrapErr.textContent = "";
    if (tabla) tabla.insertAdjacentHTML("beforeend", _htmlFilaFamilia(encontrado.id, encontrado.nombre));
    if (input) input.value = "";
}

/** Elimina una fila de familia del modal. */
function posstockEliminarFamilia(boton) {
    boton.closest("tr").remove();
}

/** Lee las tablas del modal y aplica el filtro a las variables globales. */
function posstockAplicarFiltroFamilias() {
    window.posstockFamiliasIncluir = _leerTablaFamilias("incluir");
    window.posstockFamiliasExcluir = _leerTablaFamilias("excluir");
    _posstockActualizarBadgeFiltro();
    cerrarModal();
}

function _leerTablaFamilias(lista) {
    var rows = document.querySelectorAll("#posstockTabla_" + lista + " tbody tr");
    return Array.from(rows).map(function (r) {
        return { id: parseInt(r.dataset.id), nombre: r.dataset.nombre || "" };
    });
}

function _posstockActualizarBadgeFiltro() {
    var badge = document.getElementById("posstockFiltroLabel");
    if (!badge) return;
    var nInc = (window.posstockFamiliasIncluir || []).length;
    var nExc = (window.posstockFamiliasExcluir || []).length;
    if (nInc > 0) {
        badge.className   = "label label-success";
        badge.textContent = nInc + " incluidas";
    } else if (nExc > 0) {
        badge.className   = "label label-warning";
        badge.textContent = nExc + " excluidas";
    } else {
        badge.className   = "label label-default";
        badge.textContent = "Todas";
    }
}

/** Genera <tr> mínima para una familia (usada al agregar desde el modal). */
function _htmlFilaFamilia(id, nombre) {
    return '<tr data-id="' + id + '" data-nombre="' + nombre.replace(/"/g, "&quot;") + '">'
        + '<td>' + nombre + '</td>'
        + '<td><button type="button" class="btn btn-xs btn-danger" onclick="posstockEliminarFamilia(this)">'
        + '<i class="glyphicon glyphicon-remove"></i></button></td></tr>';
}

window.posstockAbrirFiltroFamilias   = posstockAbrirFiltroFamilias;
window.posstockAgregarFamilia        = posstockAgregarFamilia;
window.posstockEliminarFamilia       = posstockEliminarFamilia;
window.posstockAplicarFiltroFamilias = posstockAplicarFiltroFamilias;

// =====================================================================
//       POSSTOCK — Filtro de proveedores
// =====================================================================

window.posstockProveedoresIncluir      = [];
window.posstockProveedorTodosProductos = false;
var _posstockProveedoresCache          = null;

function posstockAbrirFiltroProveedores() {
    $.ajax({
        data: {
            pulsado:                   "getProveedoresList",
            proveedores_incluir_json:  JSON.stringify(window.posstockProveedoresIncluir || []),
            proveedor_todos_productos: window.posstockProveedorTodosProductos ? "1" : "0",
        },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) {
                _posstockMostrarError("Error al cargar proveedores: " + resultado.error);
                return;
            }
            _posstockProveedoresCache = resultado.proveedores;
            abrirModal("Filtrar proveedor — POSStock", resultado.html);
        },
        error: function () {
            _posstockMostrarError("Error de comunicación al cargar proveedores.");
        },
    });
}

function posstockAgregarProveedor() {
    var input      = document.getElementById("posstockBuscarProveedor");
    var val        = input ? input.value.trim() : "";
    var encontrado = null;

    (_posstockProveedoresCache || []).forEach(function (p) {
        if (p.nombre.toLowerCase() === val.toLowerCase()) encontrado = p;
    });
    if (!encontrado) {
        (_posstockProveedoresCache || []).forEach(function (p) {
            if (!encontrado && p.nombre.toLowerCase().includes(val.toLowerCase())) encontrado = p;
        });
    }

    var wrapErr = document.getElementById("posstockProveedorError");
    if (!encontrado) {
        if (wrapErr) wrapErr.textContent = "Proveedor no encontrado: " + val;
        return;
    }
    if (wrapErr) wrapErr.textContent = "";

    var tbody = document.querySelector("#posstockTablaProveedores tbody");
    var rows  = tbody ? Array.from(tbody.querySelectorAll("tr")) : [];
    if (rows.some(function (r) { return parseInt(r.dataset.id) === encontrado.id; })) {
        if (wrapErr) wrapErr.textContent = "Ya está en la lista.";
        return;
    }

    if (tbody) tbody.insertAdjacentHTML("beforeend", _htmlFilaProveedor(encontrado.id, encontrado.nombre));
    if (input) input.value = "";
}

function posstockEliminarProveedor(boton) {
    boton.closest("tr").remove();
}

function posstockAplicarFiltroProveedores() {
    var tbody = document.querySelector("#posstockTablaProveedores tbody");
    window.posstockProveedoresIncluir = tbody
        ? Array.from(tbody.querySelectorAll("tr")).map(function (r) {
              return { id: parseInt(r.dataset.id), nombre: r.dataset.nombre || "" };
          })
        : [];
    var chk = document.getElementById("posstockChkTodosProductos");
    window.posstockProveedorTodosProductos = chk ? chk.checked : false;
    _posstockActualizarBadgeProveedores();
    cerrarModal();
}

function _posstockActualizarBadgeProveedores() {
    var badge = document.getElementById("posstockFiltroProveedorLabel");
    if (!badge) return;
    var n = (window.posstockProveedoresIncluir || []).length;
    if (n > 0) {
        badge.className   = "label label-success";
        badge.textContent = n + " " + (n === 1 ? "proveedor" : "proveedores");
    } else {
        badge.className   = "label label-default";
        badge.textContent = "Todos";
    }
}

function _htmlFilaProveedor(id, nombre) {
    return '<tr data-id="' + id + '" data-nombre="' + nombre.replace(/"/g, "&quot;") + '">'
        + '<td>' + nombre + '</td>'
        + '<td><button type="button" class="btn btn-xs btn-danger" onclick="posstockEliminarProveedor(this)">'
        + '<i class="glyphicon glyphicon-remove"></i></button></td></tr>';
}

window.posstockAbrirFiltroProveedores   = posstockAbrirFiltroProveedores;
window.posstockAgregarProveedor         = posstockAgregarProveedor;
window.posstockEliminarProveedor        = posstockEliminarProveedor;
window.posstockAplicarFiltroProveedores = posstockAplicarFiltroProveedores;

// =====================================================================
//       POSSTOCK — Carga de datos (AJAX batch)
// =====================================================================

function cargarDatosPosstock(periodo, tipoIncidencia) {
    $("#posstockSpinner").show();
    $("#posstockTablaWrap").hide();
    $("#posstockNavegacion").hide();
    $("#posstockBotonesWrap").hide();
    $("#posstockProgreso").text("Calculando incidencias…");

    if (window.posstockTipoActivo === "anual") {
        $("#posstockFilaCasos").hide();
    }

    _posstockCargaLote(0, [], periodo, tipoIncidencia || "");
}

/**
 * Carga incidencias en lotes de forma recursiva.
 * Cuando termina el último lote, envía los datos acumulados a PHP para renderizar.
 */
function _posstockCargaLote(inicial, acumuladas, periodo, tipoIncidencia) {
    var parametros = {
        pulsado:                  "getPOSStockBatch",
        fecha_inicio_movimientos: periodo.fecha_inicio_movimientos,
        fecha_fin_movimientos:    periodo.fecha_fin_movimientos,
        fecha_inicio_stock:       periodo.fecha_inicio_stock,
        fecha_fin_stock:          periodo.fecha_fin_stock,
        casos_incluir:            _posstockGetCasosIncluir(tipoIncidencia),
        tipo_periodo:             window.posstockTipoActivo || "",
        min_ventas_c5:            (periodo && periodo.min_ventas_c5) || 3,
        inicial:                  inicial,
        pagina:                   500,
        familias_incluir:         (window.posstockFamiliasIncluir  || []).map(function (f) { return f.id; }).join(","),
        familias_excluir:         (window.posstockFamiliasExcluir  || []).map(function (f) { return f.id; }).join(","),
        proveedores_incluir:      (window.posstockProveedoresIncluir || []).map(function (p) { return p.id; }).join(","),
        proveedor_todos_productos: window.posstockProveedorTodosProductos ? "1" : "0",
    };

    $.ajax({
        data: parametros,
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) {
                _posstockMostrarError(resultado.error);
                return;
            }

            var acum     = acumuladas.concat(resultado.filas || []);
            var actual   = resultado.actual;
            var elementos = resultado.elementos;

            acum.sort(_posstockSortComparator);

            if (elementos >= 150) {
                $("#posstockProgreso").text("Analizando artículos: " + actual + " procesados…");
                _posstockCargaLote(actual, acum, periodo, tipoIncidencia);
            } else {
                // Último lote: enviar a PHP para renderizar la tabla
                acum.sort(_posstockSortComparator);
                $("#posstockProgreso").text("Generando tabla…");
                _posstockRenderizarTabla(acum, periodo);

                $("#posstockLabelMovimientos").text(periodo.label_movimientos || "");
                $("#posstockLabelStock").text(periodo.label_stock || "");
                $("#posstockNavegacion").show();
                $("#posstockBotonesWrap").show();
            }
        },
        error: function () {
            _posstockMostrarError("Error de comunicación con el servidor.");
        },
    });
}

/** Llama a PHP para renderizar la tabla HTML y la inyecta en #posstockTablaWrap. */
function _posstockRenderizarTabla(filas, periodo) {
    $.ajax({
        data: {
            pulsado:                 "renderizarTablaPosstock",
            filas_json:              JSON.stringify(filas),
            fecha_fin_movimientos:   periodo.fecha_fin_movimientos  || "",
            fecha_inicio_stock:      periodo.fecha_inicio_stock     || "",
            anio:                    window.posstockAnioActivo       || new Date().getFullYear(),
            c3b_dias_post:           window.POSSTOCK_C3B_DIAS_POST   || 14,
            c6b_dias_historico:      window.POSSTOCK_C6B_DIAS_HISTORICO || 90,
        },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            $("#posstockSpinner").hide();
            if (resultado.error) {
                _posstockMostrarError(resultado.error);
                return;
            }
            $("#posstockTablaWrap").html(resultado.html).show();
            if (filas.length > 0) {
                $("#posstockBtnExportar, #posstockBtnImprimir").show();
            } else {
                $("#posstockBtnExportar, #posstockBtnImprimir").hide();
            }
        },
        error: function () {
            _posstockMostrarError("Error al renderizar la tabla de incidencias.");
        },
    });
}

window.cargarDatosPosstock = cargarDatosPosstock;

// =====================================================================
//       POSSTOCK — Exportar / Imprimir
// =====================================================================

function exportarPOSStockCSV() {
    var periodo = window.posstockPeriodoActivo;
    if (!periodo) return;

    var params = {
        pulsado:                  "exportarPOSStockCSV",
        fecha_inicio_movimientos: periodo.fecha_inicio_movimientos,
        fecha_fin_movimientos:    periodo.fecha_fin_movimientos,
        fecha_inicio_stock:       periodo.fecha_inicio_stock,
        fecha_fin_stock:          periodo.fecha_fin_stock,
        casos_incluir:            _posstockGetCasosIncluir(window.posstockTipoIncidenciaActivo || ""),
        tipo_periodo:             window.posstockTipoActivo || "",
        min_ventas_c5:            (periodo && periodo.min_ventas_c5) || 3,
        familias_incluir:         (window.posstockFamiliasIncluir  || []).map(function (f) { return f.id; }).join(","),
        familias_excluir:         (window.posstockFamiliasExcluir  || []).map(function (f) { return f.id; }).join(","),
        proveedores_incluir:      (window.posstockProveedoresIncluir || []).map(function (p) { return p.id; }).join(","),
        proveedor_todos_productos: window.posstockProveedorTodosProductos ? "1" : "0",
    };

    var form = document.createElement("form");
    form.method = "POST";
    form.action = "tareas.php";
    Object.keys(params).forEach(function (key) {
        var input   = document.createElement("input");
        input.type  = "hidden";
        input.name  = key;
        input.value = params[key];
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function imprimirPOSStockPDF() {
    var periodo = window.posstockPeriodoActivo;
    if (!periodo) return;

    var btn = document.getElementById("posstockBtnImprimir");
    if (btn) { btn.disabled = true; btn.textContent = "Generando PDF…"; }

    $.ajax({
        data: {
            pulsado:                  "imprimirPOSStockPDF",
            fecha_inicio_movimientos: periodo.fecha_inicio_movimientos,
            fecha_fin_movimientos:    periodo.fecha_fin_movimientos,
            fecha_inicio_stock:       periodo.fecha_inicio_stock,
            fecha_fin_stock:          periodo.fecha_fin_stock,
            casos_incluir:            _posstockGetCasosIncluir(window.posstockTipoIncidenciaActivo || ""),
            tipo_periodo:             window.posstockTipoActivo || "",
            min_ventas_c5:            (periodo && periodo.min_ventas_c5) || 3,
            familias_incluir:         (window.posstockFamiliasIncluir  || []).map(function (f) { return f.id; }).join(","),
            familias_excluir:         (window.posstockFamiliasExcluir  || []).map(function (f) { return f.id; }).join(","),
            proveedores_incluir:      (window.posstockProveedoresIncluir || []).map(function (p) { return p.id; }).join(","),
            proveedor_todos_productos: window.posstockProveedorTodosProductos ? "1" : "0",
        },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) {
                _posstockMostrarError("Error al generar PDF: " + resultado.error);
            } else {
                window.open(resultado.url, "_blank");
            }
        },
        error: function () {
            _posstockMostrarError("Error de comunicación al generar el PDF.");
        },
        complete: function () {
            if (btn) {
                btn.disabled  = false;
                btn.innerHTML = '<i class="glyphicon glyphicon-print"></i> Imprimir PDF';
            }
        },
    });
}

window.exportarPOSStockCSV = exportarPOSStockCSV;
window.imprimirPOSStockPDF = imprimirPOSStockPDF;

// =====================================================================
//       POSSTOCK — Filtro de tipos de incidencia (checkboxes)
// =====================================================================

/**
 * Devuelve el valor de casos_incluir para enviar al backend.
 * Lee los checkboxes renderizados por PHP en #posstockFilaCasos.
 */
function _posstockGetCasosIncluir(tipoIncidenciaAnual) {
    if (tipoIncidenciaAnual) return tipoIncidenciaAnual;
    var seleccionados = [];
    document.querySelectorAll("#posstockFilaCasos input[type=checkbox]").forEach(function (chk) {
        if (chk.checked) seleccionados.push(chk.value);
    });
    // Si no hay checkboxes visibles (modo anual) devolver cadena vacía = todos
    return seleccionados.length > 0 ? seleccionados.join(",") : "";
}

/** Relanza la consulta con los casos actualmente seleccionados. */
function posstockRecargarPorCasos() {
    if (!window.posstockPeriodoActivo) return;
    cargarDatosPosstock(window.posstockPeriodoActivo, window.posstockTipoIncidenciaActivo || "");
}

window.posstockRecargarPorCasos = posstockRecargarPorCasos;

// =====================================================================
//       POSSTOCK — Selectores de periodo y barra de navegación
// =====================================================================

/**
 * Rellena el selector de número de periodo llamando a PHP (getOpcionesPeriodo).
 * También habilita/deshabilita el botón Generar.
 */
function posstockActualizarNumero(mantenerNumero) {
    var tipo = document.getElementById("posstockTipo").value;
    var anio = parseInt(document.getElementById("posstockAnio").value, 10);
    var sel  = document.getElementById("posstockNumero");

    var prevNumero = mantenerNumero && sel.value ? sel.value : null;

    sel.innerHTML = "";
    sel.disabled  = true;
    document.getElementById("posstockBtnGenerar").disabled = true;
    document.getElementById("posstockAvisoVentana").style.display = "none";

    // Ocultar barra al cambiar tipo
    var barra = document.getElementById("posstockBarraBotones");
    if (barra) barra.style.display = "none";
    var wrapBotones = document.getElementById("posstockBotonesPeriodo");
    if (wrapBotones) wrapBotones.innerHTML = "";

    // Cambiar label según contexto
    var labelEl = document.getElementById("posstockLabelNumero");
    if (labelEl) labelEl.textContent = tipo === "anual" ? "Tipo de análisis" : "Periodo";

    // Ocultar/mostrar filtro de casos
    var filaCasos = document.getElementById("posstockFilaCasos");
    if (filaCasos) filaCasos.style.display = tipo === "anual" ? "none" : "";

    if (!tipo || !anio) return;

    $.ajax({
        data: {
            pulsado:                  "getOpcionesPeriodo",
            tipo:                     tipo,
            anio:                     anio,
            ventana_dias:             window.POSSTOCK_VENTANA_DIAS || 0,
            incluir_stock_inactivo:   window.POSSTOCK_INCLUIR_STOCK_INACTIVO ? "1" : "0",
        },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) return;
            sel.innerHTML = '<option value="">— seleccionar —</option>' + (resultado.html || "");
            sel.disabled  = false;

            // Intentar restaurar número previo
            if (prevNumero) {
                for (var i = 0; i < sel.options.length; i++) {
                    if (String(sel.options[i].value) === String(prevNumero) && !sel.options[i].disabled) {
                        sel.value = prevNumero;
                        document.getElementById("posstockBtnGenerar").disabled = false;
                        posstockGenerar();
                        return;
                    }
                }
            }
        },
    });
}

/**
 * Llama a calcularPeriodoPosstock (AJAX), comprueba la ventana de consolidación
 * y si es válido carga los datos y pinta la barra de botones.
 */
function posstockGenerar() {
    var tipo   = document.getElementById("posstockTipo").value;
    var numero = document.getElementById("posstockNumero").value;
    var anio   = document.getElementById("posstockAnio").value;

    if (!tipo || !numero || !anio) return;

    $.ajax({
        data: { pulsado: "calcularPeriodoPosstock", tipo: tipo, numero: numero, anio: anio },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var periodo = JSON.parse(response);
            if (periodo.error) {
                _posstockMostrarError("Error al calcular periodo: " + periodo.error);
                return;
            }

            var tipoInc    = tipo === "anual" ? numero : "";
            var _enVentana = false;

            if (window.POSSTOCK_VENTANA_DIAS > 0) {
                var hoy    = new Date(); hoy.setHours(0, 0, 0, 0);
                var limite = new Date(hoy);
                limite.setDate(limite.getDate() - window.POSSTOCK_VENTANA_DIAS);
                var ffMov  = new Date(periodo.fecha_fin_movimientos);
                if (ffMov >= limite) {
                    document.getElementById("posstockAvisoVentana").style.display = "";
                    if (tipoInc !== "caso6b") return;
                    _enVentana = true;
                }
            }
            if (!_enVentana) document.getElementById("posstockAvisoVentana").style.display = "none";
            window._posstockAnualEnVentana = _enVentana;

            window.posstockPeriodoActivo          = periodo;
            window.posstockTipoActivo             = tipo;
            window.posstockAnioActivo             = parseInt(anio, 10);
            window.posstockTipoIncidenciaActivo   = tipoInc;

            cargarDatosPosstock(periodo, tipoInc);
            _posstockCargarBarraPeriodos(tipo, tipo === "anual" ? numero : parseInt(numero, 10), _enVentana);
        },
        error: function () {
            _posstockMostrarError("Error de comunicación al calcular el periodo.");
        },
    });
}

/** Solicita a PHP el HTML de la barra de botones y lo inyecta. */
function _posstockCargarBarraPeriodos(tipo, numeroActivo, enVentana) {
    $.ajax({
        data: {
            pulsado:                 "getVistaBarraPeriodos",
            tipo:                    tipo,
            anio:                    window.posstockAnioActivo || new Date().getFullYear(),
            numero_activo:           numeroActivo,
            ventana_dias:            window.POSSTOCK_VENTANA_DIAS || 0,
            en_ventana:              enVentana ? "1" : "0",
            incluir_stock_inactivo:  window.POSSTOCK_INCLUIR_STOCK_INACTIVO ? "1" : "0",
        },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) return;
            var wrap = document.getElementById("posstockBotonesPeriodo");
            if (wrap) {
                wrap.innerHTML = resultado.html || "";
                document.getElementById("posstockBarraBotones").style.display = "";
            }
        },
    });
}

/**
 * Navega a otro periodo desde la barra sin necesidad de pulsar "Generar".
 * Actualiza el resaltado del botón activo (solo cambio de clase — evento interactivo).
 */
function posstockNavegar(numero) {
    var tipo = window.posstockTipoActivo;
    var anio = window.posstockAnioActivo;
    if (!tipo || !anio) return;

    if (tipo === "anual") {
        if (window._posstockAnualEnVentana && numero !== "caso6b") return;
        window.posstockTipoIncidenciaActivo = numero;
        document.querySelectorAll("[id^='posstockBtn_']").forEach(function (b) {
            if (!b.disabled) b.className = "btn btn-default btn-xs";
        });
        var btnActivo = document.getElementById("posstockBtn_" + numero);
        if (btnActivo) btnActivo.className = "btn btn-primary btn-xs";
        cargarDatosPosstock(window.posstockPeriodoActivo, numero);
        return;
    }

    var btn = document.getElementById("posstockBtn_" + numero);
    if (btn && btn.disabled) return;

    document.querySelectorAll("[id^='posstockBtn_']").forEach(function (b) {
        if (!b.disabled) b.className = "btn btn-default btn-xs";
    });
    if (btn) btn.className = "btn btn-primary btn-xs";

    $.ajax({
        data: { pulsado: "calcularPeriodoPosstock", tipo: tipo, numero: numero, anio: anio },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var periodo = JSON.parse(response);
            if (periodo.error) { _posstockMostrarError(periodo.error); return; }
            window.posstockPeriodoActivo = periodo;
            cargarDatosPosstock(periodo);
        },
        error: function () {
            _posstockMostrarError("Error de comunicación al navegar entre periodos.");
        },
    });
}

// Habilitar botón Generar cuando se elige número de periodo
document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("posstockNumero").addEventListener("change", function () {
        document.getElementById("posstockBtnGenerar").disabled = (this.value === "");
    });
});

window.posstockActualizarNumero = posstockActualizarNumero;
window.posstockGenerar          = posstockGenerar;
window.posstockNavegar          = posstockNavegar;
