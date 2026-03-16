import * as JSTpv from "./../../lib/js/tpvfox.js";
function metodoClick() {
    checkID = JSTpv.TfObtenerCheck("rowCheck");
    console.log(checkID);
    if (checkID.length > 1 || checkID.length === 0) {
        alert(
            "Que items tienes seleccionados? \n Solo puedes tener uno seleccionado",
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
            "Error:\n Fecha inicial no puede ser posterior a la fecha final\n o la fecha Final esta vacia",
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
            var resultado = JSON.parse(response);
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
                    "_blank",
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
//       POSSTOCK — Helpers internos
// =====================================================================

/**
 * Muestra un mensaje de error en el área de resultados (Bootstrap alert).
 * Sustituye el uso de alert() para mantener la UI coherente con el resto del programa.
 */
function _posstockMostrarError(msg) {
    $("#posstockSpinner").hide();
    $("#posstockTablaWrap")
        .html('<div class="alert alert-danger" role="alert">' + msg + "</div>")
        .show();
}

/**
 * Comparador de ordenación para el array de incidencias.
 * Usa el campo orden_clave generado por el backend para evitar duplicar
 * la lógica de prioridad CRITICA→ALTA→MEDIA→BAJA en el frontend.
 */
function _posstockSortComparator(a, b) {
    if (a && b && a.orden_clave && b.orden_clave) {
        if (a.orden_clave < b.orden_clave) return -1;
        if (a.orden_clave > b.orden_clave) return 1;
        return (a.idArticulo || 0) - (b.idArticulo || 0);
    }
    // Fallback mínimo: orden por id (no debería alcanzarse si el backend es correcto)
    return (a ? a.idArticulo || 0 : 0) - (b ? b.idArticulo || 0 : 0);
}

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
            var resultado = JSON.parse(response);
            abrirModal(resultado.titulo, resultado.html);
        },
        error: function () {
            _posstockMostrarError(
                "Error de comunicación al abrir la configuración.",
            );
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
            var resultado = JSON.parse(response);
            if (resultado.error) {
                var htmlError =
                    '<div class="alert alert-danger" role="alert">' +
                    resultado.error +
                    "</div>";
                $("#formConfigPosstock").prepend(htmlError);
            } else {
                cerrarPopUp();
                // Actualizar ventana_dias en JS para que la barra y las restricciones
                // reflejen el nuevo valor sin necesidad de recargar la página.
                if (resultado.c3b_dias_post !== undefined) {
                    window.POSSTOCK_C3B_DIAS_POST = resultado.c3b_dias_post;
                }
                if (resultado.c3a_multiplicador !== undefined) {
                    window.POSSTOCK_C3A_MULTIPLICADOR =
                        resultado.c3a_multiplicador;
                }
                if (resultado.c6b_dias_historico !== undefined) {
                    window.POSSTOCK_C6B_DIAS_HISTORICO =
                        resultado.c6b_dias_historico;
                }
                if (resultado.ventana_dias !== undefined) {
                    window.POSSTOCK_VENTANA_DIAS = resultado.ventana_dias;
                    if (
                        window.posstockTipoActivo &&
                        window.posstockPeriodoActivo
                    ) {
                        var numeroActivo =
                            parseInt(
                                document.getElementById("posstockNumero").value,
                                10,
                            ) || 1;
                        posstockPintarBarra(
                            window.posstockTipoActivo,
                            numeroActivo,
                        );
                    }
                }
                var htmlOk =
                    '<div class="alert alert-success" role="alert">' +
                    (resultado.mensaje || "Configuración guardada.") +
                    "</div>";
                $("#posstockTablaWrap").html(htmlOk).show();
            }
        },
        error: function () {
            _posstockMostrarError(
                "Error de comunicación al guardar la configuración.",
            );
        },
    });
}

/**
 * Muestra/oculta los bloques secundarios del selector de modelo estadístico:
 *   - #modalSignificanciaBloque : visible para todos los modos excepto 'binomial'
 *   - #modalBinomialSigmaBloque : visible solo para 'binomial'
 *   - #modalModeloDesc          : actualiza la descripción del modo seleccionado
 */
function modalToggleModeloEstadistico() {
    var sel = document.getElementById("modalModeloEstadistico");
    var bSig = document.getElementById("modalSignificanciaBloque");
    var bSigma = document.getElementById("modalBinomialSigmaBloque");
    var bDesc = document.getElementById("modalModeloDesc");
    if (!sel) return;
    var v = sel.value;
    if (bSig) bSig.style.display = v === "binomial" ? "none" : "";
    if (bSigma) bSigma.style.display = v === "binomial" ? "" : "none";
    if (bDesc && window._modeloDescPosstock && window._modeloDescPosstock[v]) {
        bDesc.textContent = window._modeloDescPosstock[v];
    }
}
window.modalToggleModeloEstadistico = modalToggleModeloEstadistico;
// Alias de compatibilidad (por si algún HTML antiguo llama a la función previa)
window.modalTogglePoissonConfianza = modalToggleModeloEstadistico;

window.abrirModalConfigPosstock = abrirModalConfigPosstock;
window.guardarConfigPosstock = guardarConfigPosstock;

// =====================================================================
//       POSSTOCK — Filtro de familias
// =====================================================================

// Globals: dos arrays independientes de idFamilia
window.posstockFamiliasIncluir = []; // si no está vacío: solo estas familias
window.posstockFamiliasExcluir = []; // si no está vacío: excluir estas familias

var _posstockFamiliasCache = null; // caché de la lista completa

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
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) {
                _posstockMostrarError(
                    "Error al cargar familias: " + resultado.error,
                );
                return;
            }
            _posstockFamiliasCache = resultado.familias;
            _posstockMostrarModalFamilias();
        },
        error: function () {
            _posstockMostrarError("Error de comunicación al cargar familias.");
        },
    });
}

/** Genera y abre el modal con las dos tablas usando los globals actuales. */
function _posstockMostrarModalFamilias() {
    var html =
        _htmlPanelFamilia(
            "incluir",
            "Familias a consultar",
            "Si hay familias aquí, solo se analizarán estas (y sus subfamilias).",
            "panel-success",
            "btn-success",
            window.posstockFamiliasIncluir,
        ) +
        _htmlPanelFamilia(
            "excluir",
            "Familias excluidas",
            "Estas familias (y sus subfamilias) serán excluidas del análisis.",
            "panel-danger",
            "btn-danger",
            window.posstockFamiliasExcluir,
        ) +
        '<div style="margin-top:8px;text-align:right;">' +
        '<button type="button" class="btn btn-primary btn-sm"' +
        ' onclick="posstockAplicarFiltroFamilias()">Aplicar filtro</button>' +
        "</div>";

    abrirModal("Filtrar familias — POSStock", html);

    // Poblar el datalist compartido
    var dl = document.getElementById("posstockFamiliasDatalist");
    if (dl && dl.options.length === 0) {
        _posstockFamiliasCache.forEach(function (f) {
            var opt = document.createElement("option");
            opt.value = f.ruta;
            opt.dataset.id = f.id;
            dl.appendChild(opt);
        });
    }
}

/** Genera el HTML de un panel (incluir o excluir) con su tabla y buscador. */
function _htmlPanelFamilia(
    lista,
    titulo,
    descripcion,
    panelCls,
    btnCls,
    idsActivos,
) {
    var filas = "";
    idsActivos.forEach(function (item) {
        filas += _htmlFilaFamilia(item.id, item.nombre);
    });

    return (
        '<div class="panel ' +
        panelCls +
        '" style="margin-bottom:10px;">' +
        '<div class="panel-heading small"><strong>' +
        titulo +
        "</strong></div>" +
        '<div class="panel-body" style="padding:8px;">' +
        '<p class="text-muted small" style="margin:0 0 6px;">' +
        descripcion +
        "</p>" +
        '<div style="max-height:160px;overflow-y:auto;border:1px solid #ddd;">' +
        '<table class="table table-condensed table-hover" style="margin:0;"' +
        ' id="posstockTabla_' +
        lista +
        '">' +
        '<thead><tr><th class="small">ID</th><th class="small">Familia</th><th></th></tr></thead>' +
        "<tbody>" +
        filas +
        "</tbody>" +
        "</table></div>" +
        '<div id="posstockFamiliaError_' +
        lista +
        '"></div>' +
        '<div class="input-group" style="margin-top:6px;">' +
        '<input type="text" class="form-control input-sm" list="posstockFamiliasDatalist"' +
        ' id="posstockBuscar_' +
        lista +
        '" placeholder="Buscar familia…">' +
        '<span class="input-group-btn">' +
        '<button type="button" class="btn btn-sm ' +
        btnCls +
        '"' +
        " onclick=\"posstockAgregarFamilia('" +
        lista +
        "')\">" +
        '<i class="glyphicon glyphicon-plus"></i> Agregar</button>' +
        "</span></div>" +
        "</div></div>" +
        '<datalist id="posstockFamiliasDatalist"></datalist>'
    );
}

/** Genera una fila de tabla para una familia en una lista. */
function _htmlFilaFamilia(id, nombre) {
    return (
        '<tr data-id="' +
        id +
        '">' +
        "<td>" +
        id +
        "</td>" +
        "<td>" +
        nombre +
        "</td>" +
        '<td><button type="button" class="btn btn-xs btn-link text-danger"' +
        ' onclick="posstockEliminarFamilia(this)">' +
        '<i class="glyphicon glyphicon-trash"></i></button></td>' +
        "</tr>"
    );
}

/**
 * Busca la familia escrita en el input, la añade a la tabla correspondiente
 * si existe en el catálogo y no está duplicada.
 */
function posstockAgregarFamilia(lista) {
    var input = document.getElementById("posstockBuscar_" + lista);
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
        var wrapErr = document.getElementById("posstockFamiliaError_" + lista);
        if (wrapErr) {
            wrapErr.innerHTML =
                '<div class="alert alert-warning alert-sm" role="alert">' +
                "Familia no encontrada. Escribe el nombre exacto de la ruta que aparece en la lista." +
                "</div>";
        }
        return;
    }

    // Verificar duplicado en la tabla
    var tabla = document.querySelector("#posstockTabla_" + lista + " tbody");
    if (tabla.querySelector('tr[data-id="' + encontrada.id + '"]')) {
        var wrapDup = document.getElementById("posstockFamiliaError_" + lista);
        if (wrapDup) {
            wrapDup.innerHTML =
                '<div class="alert alert-warning alert-sm" role="alert">' +
                "Esa familia ya está en la lista." +
                "</div>";
        }
        input.value = "";
        return;
    }

    var wrapOk = document.getElementById("posstockFamiliaError_" + lista);
    if (wrapOk) wrapOk.innerHTML = "";

    tabla.insertAdjacentHTML(
        "beforeend",
        _htmlFilaFamilia(encontrada.id, encontrada.nombre.trim()),
    );
    input.value = "";
}

/** Elimina una fila de la tabla de una lista. */
function posstockEliminarFamilia(boton) {
    boton.closest("tr").remove();
}

/**
 * Lee las dos tablas del modal, actualiza los globals y el badge,
 * y cierra el modal.
 */
function posstockAplicarFiltroFamilias() {
    window.posstockFamiliasIncluir = _leerTablaFamilias("incluir");
    window.posstockFamiliasExcluir = _leerTablaFamilias("excluir");
    _posstockActualizarBadgeFiltro();
    cerrarPopUp();
}

/** Lee las filas de una tabla y devuelve array de {id, nombre}. */
function _leerTablaFamilias(lista) {
    var filas = document.querySelectorAll(
        "#posstockTabla_" + lista + " tbody tr",
    );
    var resultado = [];
    filas.forEach(function (tr) {
        resultado.push({
            id: parseInt(tr.dataset.id, 10),
            nombre: tr.cells[1].textContent.trim(),
        });
    });
    return resultado;
}

/** Actualiza el badge del botón con el resumen activo. */
function _posstockActualizarBadgeFiltro() {
    var badge = document.getElementById("posstockFiltroLabel");
    if (!badge) return;
    var nInc = (window.posstockFamiliasIncluir || []).length;
    var nExc = (window.posstockFamiliasExcluir || []).length;
    if (nInc === 0 && nExc === 0) {
        badge.textContent = "Todas";
        badge.className = "label label-default";
    } else {
        var partes = [];
        if (nInc > 0) partes.push("Consultar: " + nInc);
        if (nExc > 0) partes.push("Excluir: " + nExc);
        badge.textContent = partes.join(" · ");
        badge.className = "label label-warning";
    }
}

window.posstockAbrirFiltroFamilias = posstockAbrirFiltroFamilias;
window.posstockAgregarFamilia = posstockAgregarFamilia;
window.posstockEliminarFamilia = posstockEliminarFamilia;
window.posstockAplicarFiltroFamilias = posstockAplicarFiltroFamilias;

// =====================================================================
//       POSSTOCK — Filtro de proveedores
// =====================================================================

// Global: lista de {id, nombre} de proveedores seleccionados (solo incluir)
window.posstockProveedoresIncluir = [];
// Global: analizar todos los artículos del proveedor (sin filtrar por actividad en el periodo)
window.posstockProveedorTodosProductos = false;

var _posstockProveedoresCache = null; // caché de la lista completa

/**
 * Abre el modal de selección de proveedores.
 * Carga la lista vía AJAX la primera vez; reutiliza caché en sucesivas.
 */
function posstockAbrirFiltroProveedores() {
    if (_posstockProveedoresCache) {
        _posstockMostrarModalProveedores();
        return;
    }
    $.ajax({
        data: { pulsado: "getProveedoresList" },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) {
                _posstockMostrarError(
                    "Error al cargar proveedores: " + resultado.error,
                );
                return;
            }
            _posstockProveedoresCache = resultado.proveedores;
            _posstockMostrarModalProveedores();
        },
        error: function () {
            _posstockMostrarError(
                "Error de comunicación al cargar proveedores.",
            );
        },
    });
}

/** Genera y abre el modal de selección de proveedores. */
function _posstockMostrarModalProveedores() {
    var filas = window.posstockProveedoresIncluir
        .map(function (p) {
            return _htmlFilaProveedor(p.id, p.nombre);
        })
        .join("");

    var html =
        '<div class="panel panel-success" style="margin-bottom:10px;">' +
        '<div class="panel-heading small"><strong>Proveedores a consultar</strong></div>' +
        '<div class="panel-body" style="padding:8px;">' +
        '<p class="text-muted small" style="margin:0 0 6px;">' +
        "Si hay proveedores aquí, solo se analizarán artículos de estos proveedores." +
        "</p>" +
        '<div style="max-height:180px;overflow-y:auto;border:1px solid #ddd;">' +
        '<table class="table table-condensed table-hover" style="margin:0;"' +
        ' id="posstockTablaProveedores">' +
        '<thead><tr><th class="small">ID</th><th class="small">Proveedor</th><th></th></tr></thead>' +
        "<tbody>" +
        filas +
        "</tbody>" +
        "</table></div>" +
        '<div id="posstockProveedorError"></div>' +
        '<div class="input-group" style="margin-top:6px;">' +
        '<input type="text" class="form-control input-sm" list="posstockProveedoresDatalist"' +
        ' id="posstockBuscarProveedor" placeholder="Buscar proveedor…">' +
        '<span class="input-group-btn">' +
        '<button type="button" class="btn btn-sm btn-success"' +
        ' onclick="posstockAgregarProveedor()">' +
        '<i class="glyphicon glyphicon-plus"></i> Agregar</button>' +
        "</span></div>" +
        "</div></div>" +
        '<datalist id="posstockProveedoresDatalist"></datalist>' +
        '<div style="margin-top:8px;display:flex;align-items:center;justify-content:space-between;">' +
        '<label class="small" style="margin:0;font-weight:normal;" title="Analiza todos los artículos asignados al proveedor, aunque no tengan movimientos en el periodo analizado.">' +
        '<input type="checkbox" id="posstockChkTodosProductos"' +
        (window.posstockProveedorTodosProductos ? " checked" : "") +
        '> Todos los artículos del proveedor <small class="text-muted">(ignora actividad en el periodo)</small>' +
        "</label>" +
        '<button type="button" class="btn btn-primary btn-sm"' +
        ' onclick="posstockAplicarFiltroProveedores()">Aplicar filtro</button>' +
        "</div>";

    abrirModal("Filtrar proveedor — POSStock", html);

    // Poblar el datalist
    var dl = document.getElementById("posstockProveedoresDatalist");
    if (dl && dl.options.length === 0) {
        _posstockProveedoresCache.forEach(function (p) {
            var opt = document.createElement("option");
            opt.value = p.nombre;
            opt.dataset.id = p.id;
            dl.appendChild(opt);
        });
    }
}

/** Genera una fila de tabla para un proveedor. */
function _htmlFilaProveedor(id, nombre) {
    return (
        '<tr data-id="' +
        id +
        '">' +
        "<td>" +
        id +
        "</td>" +
        "<td>" +
        nombre +
        "</td>" +
        '<td><button type="button" class="btn btn-xs btn-link text-danger"' +
        ' onclick="posstockEliminarProveedor(this)">' +
        '<i class="glyphicon glyphicon-trash"></i></button></td>' +
        "</tr>"
    );
}

/** Busca el proveedor escrito, lo añade si existe y no está duplicado. */
function posstockAgregarProveedor() {
    var input = document.getElementById("posstockBuscarProveedor");
    var texto = input.value.trim();
    if (!texto) return;

    var encontrado = null;
    _posstockProveedoresCache.forEach(function (p) {
        if (!encontrado && p.nombre.trim() === texto) encontrado = p;
    });

    var wrapErr = document.getElementById("posstockProveedorError");
    if (!encontrado) {
        if (wrapErr)
            wrapErr.innerHTML =
                '<div class="alert alert-warning alert-sm" role="alert">' +
                "Proveedor no encontrado. Selecciona un nombre exacto de la lista." +
                "</div>";
        return;
    }

    var tabla = document.querySelector("#posstockTablaProveedores tbody");
    if (tabla.querySelector('tr[data-id="' + encontrado.id + '"]')) {
        if (wrapErr)
            wrapErr.innerHTML =
                '<div class="alert alert-warning alert-sm" role="alert">' +
                "Ese proveedor ya está en la lista." +
                "</div>";
        input.value = "";
        return;
    }

    if (wrapErr) wrapErr.innerHTML = "";
    tabla.insertAdjacentHTML(
        "beforeend",
        _htmlFilaProveedor(encontrado.id, encontrado.nombre.trim()),
    );
    input.value = "";
}

/** Elimina una fila de la tabla de proveedores. */
function posstockEliminarProveedor(boton) {
    boton.closest("tr").remove();
}

/** Lee la tabla y el checkbox, actualiza los globals y el badge, y cierra el modal. */
function posstockAplicarFiltroProveedores() {
    var filas = document.querySelectorAll("#posstockTablaProveedores tbody tr");
    window.posstockProveedoresIncluir = [];
    filas.forEach(function (tr) {
        window.posstockProveedoresIncluir.push({
            id: parseInt(tr.dataset.id, 10),
            nombre: tr.cells[1].textContent.trim(),
        });
    });
    var chk = document.getElementById("posstockChkTodosProductos");
    window.posstockProveedorTodosProductos = chk ? chk.checked : false;
    _posstockActualizarBadgeProveedores();
    cerrarPopUp();
}

/** Actualiza el badge del botón de proveedores. */
function _posstockActualizarBadgeProveedores() {
    var badge = document.getElementById("posstockFiltroProveedorLabel");
    if (!badge) return;
    var n = (window.posstockProveedoresIncluir || []).length;
    if (n === 0) {
        badge.textContent = "Todos";
        badge.className = "label label-default";
    } else {
        badge.textContent = n === 1 ? "1 proveedor" : n + " proveedores";
        badge.className = "label label-warning";
    }
}

window.posstockAbrirFiltroProveedores = posstockAbrirFiltroProveedores;
window.posstockAgregarProveedor = posstockAgregarProveedor;
window.posstockEliminarProveedor = posstockEliminarProveedor;
window.posstockAplicarFiltroProveedores = posstockAplicarFiltroProveedores;

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
function cargarDatosPosstock(periodo, tipoIncidencia) {
    // Spinner visible, tabla y barra ocultas mientras se carga
    $("#posstockSpinner").show();
    $("#posstockTablaWrap").hide();
    $("#posstockNavegacion").hide();
    $("#posstockBotonesWrap").hide();
    $("#posstockProgreso").text("Calculando incidencias…");

    // El filtro de casos no aplica en la vista anual (cada botón ya filtra por caso)
    if (window.posstockTipoActivo === "anual") {
        $("#posstockFilaCasos").hide();
    }

    _posstockCargaLote(0, [], periodo, tipoIncidencia || "");
}

/**
 * Carga las incidencias en lotes siguiendo el patrón de mod_reorganizacion.
 * Se llama recursivamente hasta que actual >= total.
 *
 * @param {number} inicial       Offset del lote actual
 * @param {Array}  acumuladas    Incidencias acumuladas de lotes anteriores
 * @param {Object} periodo       Objeto con las 4 fechas y los labels
 * @param {string} tipoIncidencia
 */
function _posstockCargaLote(inicial, acumuladas, periodo, tipoIncidencia) {
    var parametros = {
        pulsado: "getPOSStockBatch",
        fecha_inicio_movimientos: periodo.fecha_inicio_movimientos,
        fecha_fin_movimientos: periodo.fecha_fin_movimientos,
        fecha_inicio_stock: periodo.fecha_inicio_stock,
        fecha_fin_stock: periodo.fecha_fin_stock,
        casos_incluir: _posstockGetCasosIncluir(tipoIncidencia),
        tipo_periodo: window.posstockTipoActivo || "",
        min_ventas_c5: (periodo && periodo.min_ventas_c5) || 3,
        inicial: inicial,
        pagina: 500,
        familias_incluir: (window.posstockFamiliasIncluir || [])
            .map(function (f) {
                return f.id;
            })
            .join(","),
        familias_excluir: (window.posstockFamiliasExcluir || [])
            .map(function (f) {
                return f.id;
            })
            .join(","),
        proveedores_incluir: (window.posstockProveedoresIncluir || [])
            .map(function (p) {
                return p.id;
            })
            .join(","),
        proveedor_todos_productos: window.posstockProveedorTodosProductos
            ? "1"
            : "0",
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

            // Mantener acumulado ordenado entre lotes (usa orden_clave del backend)
            var acum = acumuladas.concat(resultado.filas || []);
            acum.sort(_posstockSortComparator);

            var actual = resultado.actual;
            var elementos = resultado.elementos;

            // Continuar mientras el lote devuelva datos suficientes para haber más.
            if (elementos >= 150) {
                $("#posstockProgreso").text(
                    "Analizando artículos: " + actual + " procesados…",
                );
                _posstockCargaLote(actual, acum, periodo, tipoIncidencia);
            } else {
                // Último lote: ordenación final y pintado.
                acum.sort(_posstockSortComparator);

                $("#posstockSpinner").hide();

                // Actualizar labels de periodo
                $("#posstockLabelMovimientos").text(
                    periodo.label_movimientos || "",
                );
                $("#posstockLabelStock").text(periodo.label_stock || "");

                pintarTablaIncidencias(acum);

                $("#posstockNavegacion").show();
                $("#posstockBotonesWrap").show();
                $("#posstockTablaWrap").show();

                if (acum.length > 0) {
                    $("#posstockBtnExportar, #posstockBtnImprimir").show();
                } else {
                    $("#posstockBtnExportar, #posstockBtnImprimir").hide();
                }
            }
        },
        error: function () {
            _posstockMostrarError("Error de comunicación con el servidor.");
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
        ALTA: '<span class="label label-danger" style="background-color:#e8600a;">Alta</span>',
        MEDIA: '<span class="label label-warning">Media</span>',
        BAJA: '<span class="label label-info">Baja</span>',
    };

    if (!filas || filas.length === 0) {
        $("#posstockTablaWrap").html(
            '<div class="alert alert-success">Sin incidencias detectadas para este periodo.</div>',
        );
        return;
    }

    // Labels de visualización para el personal de tienda (el tipo interno se conserva
    // en data-tipo para filtros; aquí solo cambia lo que se muestra en la celda).
    var tipoLabels = {
        "Inventario en negativo": "Stock negativo",
        "Desajuste Puntual de Stock": "Descuadre temporal",
        "Venta Cero (Posible Rotura Física)": "Rotura de stock",
        "Entrada sin rotación previa": "Pedido sin rotación",
    };

    // Formatea YYYY-MM-DD → DD/MM para lectura rápida en tabla
    function _fmtF(d) {
        if (!d) return "—";
        var p = d.split("-");
        return p.length === 3 ? p[2] + "/" + p[1] : d;
    }

    // Ordenar usando la clave generada por el backend (orden_clave)
    filas.sort(_posstockSortComparator);

    var periodo = window.posstockPeriodoActivo || {};
    var anio = window.posstockAnioActivo || new Date().getFullYear();
    var ffMov = periodo.fecha_fin_movimientos || "";
    var fiInicio = anio + "-01-01";

    var html =
        '<table class="table table-condensed table-hover table-bordered small" id="posstockTabla">';
    html +=
        "<thead><tr>" +
        "<th>Artículo</th>" +
        "<th>Nombre</th>" +
        "<th>Tipo incidencia</th>" +
        "<th>Severidad</th>" +
        "<th>Detalle</th>" +
        "<th>Posible causa</th>" +
        "<th>Listado mayor</th>" +
        "</tr></thead><tbody>";

    filas.forEach(function (f) {
        var detalle = "";
        if (f.tipo === "Inventario en negativo") {
            // C1a — badges de diagnóstico primero (qué buscar), luego números de contexto
            var _badges = "";

            // Señal 1: sin recepciones → lo más accionable, va primero
            var nEnt =
                f.n_entradas !== undefined && f.n_entradas !== null
                    ? parseInt(f.n_entradas)
                    : null;
            if (nEnt !== null && nEnt === 0) {
                _badges +=
                    ' <span class="label label-danger"' +
                    ' title="No se registró ninguna recepción de proveedor en el periodo.' +
                    ' Probable recepción sin registrar.">Sin recepciones</span>';
            }

            // Señal 2: stock decimal → unidad o fraccionado mal configurado
            if (f.es_fraccionado) {
                _badges +=
                    ' <span class="label label-info"' +
                    ' title="El stock tiene valor decimal. Posible artículo de peso o fraccionado' +
                    ' con la unidad mal configurada.">Stock decimal</span>';
            }

            // Señal 3: problema arrastrado de periodos anteriores
            if (f.ya_negativo_inicio) {
                _badges +=
                    ' <span class="label label-warning"' +
                    ' title="El inventario ya estaba en negativo al inicio del periodo.' +
                    ' El problema viene de un rango anterior.">Arrastrado</span>';
            }

            // Números de contexto
            detalle =
                _badges +
                " Stock: <strong>" +
                (f.stock_actual !== undefined
                    ? parseFloat(f.stock_actual).toFixed(2)
                    : "—") +
                "</strong>" +
                (f.min_balance !== undefined
                    ? " | Mínimo: " + parseFloat(f.min_balance).toFixed(2)
                    : "");

            // Ventas del periodo
            if (f.n_ventas !== undefined && f.n_ventas !== null) {
                detalle += " | Ventas: " + parseInt(f.n_ventas);
            }

            // Última recepción (solo si hubo alguna)
            if (nEnt !== null && nEnt > 0) {
                var ultEnt = f.ultima_entrada
                    ? f.ultima_entrada.split("-").reverse().join("/")
                    : "—";
                detalle += " | Últ. recepción: " + ultEnt;
            }
        } else if (f.tipo === "Desajuste Puntual de Stock") {
            // C1b — negativo puntual recuperado al cierre
            var _badges1b = "";
            var nEnt1b =
                f.n_entradas !== undefined && f.n_entradas !== null
                    ? parseInt(f.n_entradas)
                    : null;

            if (!!f.timing_proximo) {
                _badges1b +=
                    ' <span class="label label-info" title="Entrada de proveedor registrada en los 3 días siguientes al momento del negativo: probable venta registrada antes que la recepción.">Timing recepción</span>';
            }
            if (f.es_fraccionado) {
                _badges1b +=
                    ' <span class="label label-info" title="El mínimo tiene decimales: probable artículo de peso o fraccionado.">Stock decimal</span>';
            }
            if (nEnt1b !== null && nEnt1b === 0) {
                _badges1b +=
                    ' <span class="label label-warning" title="Sin recepciones que justifiquen la recuperación. Revisar posibles movimientos duplicados, devoluciones o ajustes manuales.">Sin entradas</span>';
            }

            detalle =
                _badges1b +
                " Mín.: <strong>" +
                (f.min_balance !== undefined
                    ? parseFloat(f.min_balance).toFixed(2)
                    : "—") +
                "</strong>" +
                " | Cierre: " +
                (f.stock_actual !== undefined
                    ? parseFloat(f.stock_actual).toFixed(2)
                    : "—");

            if (f.n_ventas !== undefined && f.n_ventas !== null) {
                detalle += " | Ventas: " + parseInt(f.n_ventas);
            }
            if (nEnt1b !== null && nEnt1b > 0) {
                var ultEnt1b = f.ultima_entrada
                    ? f.ultima_entrada.split("-").reverse().join("/")
                    : "—";
                detalle += " | Últ. recepción: " + ultEnt1b;
            }
        } else if (f.tipo === "Entrada con stock alto") {
            // C2 — badges por señales + detalle enriquecido
            var _badgeC2 = "";
            var ncant = parseFloat(f.ncant) || 0;
            var ncantA =
                f.ncant_anterior !== undefined && f.ncant_anterior !== null
                    ? parseFloat(f.ncant_anterior)
                    : null;
            var dias =
                f.dias_desde_anterior !== undefined &&
                f.dias_desde_anterior !== null
                    ? parseInt(f.dias_desde_anterior)
                    : null;
            var vtr =
                f.ventas_entre_recepciones !== undefined &&
                f.ventas_entre_recepciones !== null
                    ? parseFloat(f.ventas_entre_recepciones)
                    : null;
            var cob =
                f.cobertura_dias !== undefined && f.cobertura_dias !== null
                    ? parseInt(f.cobertura_dias)
                    : null;

            if (f.c2_categoria === "tendencia") {
                // C2b — sobrestock progresivo: el artículo vende pero los pedidos superan el ritmo
                var nEvt = parseInt(f.n_eventos) || 1;
                var cobIni =
                    f.cobertura_inicio !== undefined &&
                    f.cobertura_inicio !== null
                        ? parseInt(f.cobertura_inicio) + " días"
                        : "—";
                var cobFin = cob !== null ? cob + " días" : "sin ventas";
                var fInicio = f.fecha_inicio || "—";
                var fFin = f.fecha || "—";
                var stockMax = parseFloat(f.stock_previo) || 0;
                _badgeC2 =
                    '<span class="label label-warning" title="La cobertura crece de ' +
                    cobIni +
                    " a " +
                    cobFin +
                    " en " +
                    nEvt +
                    ' entregas: las compras superan el ritmo de ventas de forma sistemática.">Tendencia creciente</span>';
                detalle =
                    _badgeC2 +
                    " <strong>" +
                    nEvt +
                    " entregas</strong>" +
                    " | Cobertura: " +
                    cobIni +
                    " → <strong>" +
                    cobFin +
                    "</strong>" +
                    " | Periodo: " +
                    fInicio +
                    " → " +
                    fFin +
                    " | Stock máx.: " +
                    stockMax.toFixed(2);
            } else if (f.c2_categoria === "acumulacion") {
                // Patrón de acumulación crónica (periódico, pan, etc. con devoluciones no gestionadas)
                var nEvt = parseInt(f.n_eventos) || 1;
                var cobFin = cob !== null ? cob + " días" : "sin ventas";
                var fInicio = f.fecha_inicio || "—";
                var fFin = f.fecha || "—";
                var stockMax = parseFloat(f.stock_previo) || 0;
                _badgeC2 =
                    '<span class="label label-danger" title="' +
                    nEvt +
                    ' recepciones consecutivas sin retorno: el stock se acumula sin salida. Revisar gestión de devoluciones.">Acumulación crónica</span>';
                detalle =
                    _badgeC2 +
                    " <strong>" +
                    nEvt +
                    " recepciones</strong> consolidadas" +
                    " | Periodo: " +
                    fInicio +
                    " → " +
                    fFin +
                    " | Stock máx.: " +
                    stockMax.toFixed(2) +
                    " | Cobertura final: <strong>" +
                    cobFin +
                    "</strong>";
            } else {
                // Duplicado probable (mismo día o día anterior + cantidad similar)
                var esDupProbable =
                    dias !== null &&
                    dias <= 1 &&
                    ncantA !== null &&
                    Math.abs(ncant - ncantA) / Math.max(ncant, ncantA) < 0.15;

                // Posible duplicado (gap ≤3 días + cantidad similar, señal más débil)
                var esDupPosible =
                    !esDupProbable &&
                    dias !== null &&
                    dias <= 3 &&
                    ncantA !== null &&
                    Math.abs(ncant - ncantA) / Math.max(ncant, ncantA) < 0.15;

                if (esDupProbable) {
                    _badgeC2 +=
                        ' <span class="label label-danger" title="Cantidad similar recibida hace ' +
                        dias +
                        ' día(s): muy probable albarán registrado dos veces.">Duplicado probable</span>';
                } else if (esDupPosible) {
                    _badgeC2 +=
                        ' <span class="label label-warning" title="Cantidad similar recibida hace ' +
                        dias +
                        ' días: verificar si el albarán se registró dos veces.">Posible duplicado</span>';
                }
                if (cob === null) {
                    _badgeC2 +=
                        ' <span class="label label-danger" title="El artículo no registra ventas en el periodo analizado.">Sin ventas</span>';
                }
                if (dias !== null && dias <= 14 && vtr !== null && vtr < 1) {
                    _badgeC2 +=
                        ' <span class="label label-warning" title="No hubo ventas entre la recepción anterior (' +
                        dias +
                        ' días antes) y esta: el pedido anterior no había rotado.">Pedido prematuro</span>';
                }
                if (!esDupProbable && f.c2_categoria === "severo") {
                    _badgeC2 +=
                        ' <span class="label label-warning" title="El stock previo supera ampliamente la entrada recibida.">Sobrestock severo</span>';
                }

                var previo = parseFloat(f.stock_previo) || 0;
                var ratio =
                    f.ratio !== undefined
                        ? parseFloat(f.ratio).toFixed(1) + "×"
                        : ncant > 0
                          ? (previo / ncant).toFixed(1) + "×"
                          : "—";
                var cobStr = cob !== null ? cob + " días" : "sin ventas";

                detalle =
                    _badgeC2 +
                    " Previo: " +
                    previo.toFixed(2) +
                    " | Entrada: <strong>" +
                    ncant.toFixed(2) +
                    " ud. (" +
                    ratio +
                    " previo)</strong>" +
                    " | Cobertura: <strong>" +
                    cobStr +
                    "</strong>";

                if (dias !== null) {
                    detalle += " | Anter.: " + dias + " días";
                }
                detalle += " | Fecha: " + (f.fecha || "—");
            }
        } else if (f.tipo === "Caída de rotación") {
            // C3a — badge + días sin stock disponible vs cadencia habitual + stock
            var avgCad3a = parseFloat(f.avg_cadencia_dias) || 0;
            var semVal3a = parseFloat(f.semanas_desde_ultima_venta) || 0;
            var diasSV3a = semVal3a > 0 ? Math.round(semVal3a * 7) : null;
            var desdeRep3a = !!f.desde_reposicion;
            var stkRaw3a =
                f.stock_actual !== undefined && f.stock_actual !== null
                    ? parseFloat(f.stock_actual)
                    : null;
            // Stock sin decimales innecesarios: enteros → "12", fracciones → "2.50"
            var stkStr3a =
                stkRaw3a !== null
                    ? stkRaw3a % 1 === 0
                        ? stkRaw3a.toFixed(0)
                        : stkRaw3a.toFixed(2)
                    : "—";
            // Badge: fast mover solo muestra "Riesgo caducidad" cuando el stock ha
            // estado presente continuamente (no tras reposición por agotamiento).
            // Si es desde_reposicion el artículo rotó bien antes → "Rotación caída".
            var badgeC3a;
            var esFast3a = avgCad3a > 0 && avgCad3a <= 7 && !desdeRep3a;
            if (esFast3a) {
                badgeC3a =
                    f.severidad === "ALTA"
                        ? '<span class="label label-danger" title="Alta rotación y más del doble del umbral sin venta: riesgo de caducidad o merma">Riesgo caducidad</span>'
                        : '<span class="label label-warning" title="Artículo de alta rotación por encima del umbral dinámico sin venta">Riesgo caducidad</span>';
            } else {
                badgeC3a =
                    f.severidad === "ALTA"
                        ? '<span class="label label-danger" title="Más del doble del umbral dinámico sin venta: rotación caída drásticamente">Caída severa</span>'
                        : '<span class="label label-warning" title="Por encima del umbral dinámico de rotación">Rotación caída</span>';
            }
            // Referencia temporal: "desde recepción" si el artículo se agotó y se repuso,
            // "últ. venta" si el stock ha estado presente continuamente.
            var refFechaStr3a = desdeRep3a
                ? "Desde recep.: " + _fmtF(f.fecha_primera_entrada)
                : "Últ. venta: " + _fmtF(f.ultima_venta);
            // Días sin venta con cadencia habitual al lado para comparación inmediata
            var sinVentaStr3a;
            if (diasSV3a !== null && diasSV3a > 0) {
                sinVentaStr3a = "<strong>" + diasSV3a + " d</strong> sin venta";
                if (avgCad3a > 0) {
                    sinVentaStr3a +=
                        ' <small class="text-muted">(normal ' +
                        avgCad3a.toFixed(1) +
                        " d)</small>";
                }
            } else {
                sinVentaStr3a = "<strong>—</strong>";
            }
            detalle =
                badgeC3a +
                " " +
                sinVentaStr3a +
                " | Stock: <strong>" +
                stkStr3a +
                "</strong>" +
                " | " +
                refFechaStr3a;
        } else if (f.tipo === "Venta Cero (Posible Rotura Física)") {
            var badgeKO = f.ko
                ? ' <span class="label label-danger" title="Stock negativo durante la rotura en curso: inventario en descubierto">KO</span>'
                : "";
            var badgeCR = f.cr
                ? ' <span class="label" style="background:#e67e22;" title="Rotura crítica: duración confirmada supera el umbral">CR</span>'
                : "";
            var badgeRK = f.rk
                ? ' <span class="label label-warning" title="Rotura confirmada significativa (≥ cadencia media)">RK</span>'
                : "";

            // Estado de la rotura: en curso o recuperada
            var badgeEstado = f.fecha_fin_rotura
                ? '<span class="label label-success">Recuperada ' +
                  _fmtF(f.fecha_fin_rotura) +
                  "</span>"
                : '<span class="label label-warning">En curso</span>';

            // Días de rotura + fecha de inicio
            var diasRoturaStr =
                f.dias_rotura !== undefined
                    ? "<strong>" + f.dias_rotura + " d</strong>"
                    : "<strong>—</strong>";

            // Badge de distribución (técnico, va al final)
            var _avgC5 = parseFloat(f.avg_dias_entre_ventas) || 0;
            var _c5mCfg = {
                // Alta rotación (>80 % días), gaps muy regulares → cuantil Gamma
                GammaReg: {
                    lbl: "Γ",
                    cls: "label-success",
                    comp: "Alta rotación, patrón muy regular",
                    tip: "Gamma — alta rotación con intervalos regulares. Umbral = cuantil Gamma ajustado a los gaps.",
                },
                // Rotación moderada o tipo peso → cuantil Gamma general
                Gamma: {
                    lbl: "Γ",
                    cls: "label-default",
                    comp: "Rotación moderada, gaps ajustados a Gamma",
                    tip: "Gamma — ajuste directo a la distribución de gaps histórica. Umbral = cuantil de probabilidad.",
                },
                // Gaps casi uniformes (varianza ≈ 0) → aproximación Normal (μ + 3σ)
                Normal: {
                    lbl: "N",
                    cls: "label-default",
                    comp: "Intervalos muy uniformes (aproximación Normal)",
                    tip: "Normal — gaps prácticamente constantes (varianza ≈ 0). Umbral = media + 3σ.",
                },
                // Demanda agrupada en rachas → Binomial Negativa
                BN: {
                    lbl: "BN",
                    cls: "label-info",
                    comp: "Demanda en rachas o lotes",
                    tip: "Binomial Negativa — sobredispersión detectada (ventas agrupadas por periodos). Umbral ajustado a la variabilidad extra.",
                },
                // Alta rotación, gaps con dispersión normal → Poisson
                Poisson:
                    _avgC5 > 0 && _avgC5 < 4
                        ? {
                              lbl: "Poi",
                              cls: "label-primary",
                              comp: "Alta rotación, gaps ~ exponencial",
                              tip: "Poisson — alta rotación con dispersión normal. Umbral: gap > −ln(p)/λ.",
                          }
                        : // Demanda esporádica → Poisson conservador
                          {
                              lbl: "Poi",
                              cls: "label-default",
                              comp: "Demanda esporádica, baja frecuencia",
                              tip: "Poisson — artículo de venta poco frecuente. Umbral conservador para eventos raros.",
                          },
            };
            var _c5m = _c5mCfg[f.modelo_usado] || _c5mCfg["Poisson"];
            var badgeModelo =
                ' <span class="label ' +
                _c5m.cls +
                '" title="' +
                _c5m.tip +
                '">' +
                _c5m.lbl +
                "</span>";

            var sdStr =
                f.sd_dias !== null && f.sd_dias !== undefined
                    ? " σ=" + f.sd_dias + " d"
                    : "";
            detalle =
                badgeKO +
                badgeCR +
                badgeRK +
                " " +
                badgeEstado +
                " " +
                diasRoturaStr +
                " | Desde: " +
                _fmtF(f.fecha_inicio_rotura) +
                " | Últ. venta: " +
                _fmtF(f.ultima_venta) +
                " | Cadencia: " +
                (f.avg_dias_entre_ventas !== undefined
                    ? f.avg_dias_entre_ventas + " d"
                    : "—") +
                sdStr +
                " (umbral " +
                (f.umbral_dias !== undefined ? f.umbral_dias + " d" : "—") +
                ")" +
                badgeModelo +
                ' <small class="text-muted">· ' +
                _c5m.comp +
                "</small>";
        } else if (f.tipo === "Entrada sin rotación previa") {
            // C3b — badge + stock + entradas + última salida o ausencia
            var stockC3b =
                f.stock_actual !== undefined && f.stock_actual !== null
                    ? parseFloat(f.stock_actual).toFixed(2)
                    : "—";
            var nEnt3b = parseInt(f.n_entradas) || 1;
            var qty3b =
                f.cantidad_recibida !== undefined &&
                f.cantidad_recibida !== null
                    ? parseFloat(f.cantidad_recibida).toFixed(2)
                    : null;
            var badgeC3b;
            if (!f.ultima_salida) {
                badgeC3b =
                    nEnt3b >= 3
                        ? '<span class="label label-danger" title="Pedido ' +
                          nEnt3b +
                          ' veces sin ninguna venta registrada">Pedidos repetidos sin venta</span>'
                        : '<span class="label label-danger" title="Artículo que nunca ha tenido ventas registradas">Sin ventas</span>';
            } else {
                badgeC3b =
                    nEnt3b >= 2
                        ? '<span class="label label-warning" title="Se repone ' +
                          nEnt3b +
                          ' veces pese a no tener rotación activa">Reposición sin rotación</span>'
                        : '<span class="label label-warning" title="Artículo con rotación muy baja o nula">Sin rotación</span>';
            }
            var nDev3b = parseInt(f.n_devoluciones) || 0;
            var qDev3b =
                f.cantidad_devuelta !== undefined &&
                f.cantidad_devuelta !== null
                    ? parseFloat(f.cantidad_devuelta)
                    : 0;
            var badgeDev3b = "";
            if (nDev3b > 0) {
                var ratioDevStr =
                    qty3b !== null && parseFloat(qty3b) > 0
                        ? " (" +
                          Math.round((qDev3b / parseFloat(qty3b)) * 100) +
                          "%)"
                        : "";
                var devTip =
                    qDev3b >= parseFloat(qty3b || 0) * 0.8
                        ? "Devuelto casi en su totalidad al proveedor"
                        : "Devolución parcial al proveedor";
                badgeDev3b =
                    ' <span class="label label-default" title="' +
                    devTip +
                    '">' +
                    "Dev." +
                    ratioDevStr +
                    "</span>";
            }
            var entStr3b =
                "Recepciones: " +
                nEnt3b +
                (qty3b !== null ? " (" + qty3b + " ud.)" : "");
            var movStr3b = f.ultima_salida
                ? "Últ. venta: " +
                  _fmtF(f.ultima_salida) +
                  " | <strong>" +
                  (f.semanas_desde_ultima_salida || "—") +
                  " sem.</strong> sin movimiento"
                : f.fecha_primera_entrada
                  ? "Primera recepción: " +
                    _fmtF(f.fecha_primera_entrada) +
                    " | <strong>Sin ventas en historial</strong>"
                  : "<strong>Sin ventas en historial</strong>";
            detalle =
                badgeC3b +
                badgeDev3b +
                " Stock: <strong>" +
                stockC3b +
                "</strong>" +
                " | " +
                entStr3b +
                " | " +
                movStr3b;
        } else if (f.tipo === "Stock Inactivo en Periodo") {
            // C4 — solo el stock; el resto ya está en tipo y causa
            detalle =
                "Stock: " +
                (f.stock_actual !== undefined
                    ? parseFloat(f.stock_actual).toFixed(2)
                    : "—");
        } else if (f.tipo === "Entrada no registrada") {
            // C7b — déficit sistemático estable entre recepciones
            var offsetB =
                f.offset_estimado !== undefined
                    ? parseFloat(f.offset_estimado)
                    : null;
            var dispB =
                f.dispersion !== undefined ? parseFloat(f.dispersion) : null;
            var nRecB = parseInt(f.n_recepciones) || 0;
            var _cruceScoreB =
                f.cruce_score !== undefined
                    ? "score=" + parseFloat(f.cruce_score).toFixed(2) + " — "
                    : "";
            var _cruceNivelB =
                f.cruce_nivel === "confirmado"
                    ? "cruce confirmado"
                    : f.cruce_nivel === "probable"
                      ? "cruce probable"
                      : "posible cruce";
            var cruceCls =
                f.cruce_nivel === "confirmado"
                    ? "label-success"
                    : f.cruce_nivel === "probable"
                      ? "label-danger"
                      : "label-warning";
            var badgeCruceB = "";
            if (f.posible_cruce_con) {
                if (f.cruce_tipo === "trio" && f.cruce_fuente_b) {
                    var cruceTipB =
                        _cruceScoreB +
                        _cruceNivelB +
                        ": este art. y art. " +
                        f.cruce_fuente_b +
                        " escaneados como art. " +
                        f.posible_cruce_con;
                    badgeCruceB =
                        ' <span class="label ' +
                        cruceCls +
                        '" title="' +
                        cruceTipB +
                        '">Trío →art. ' +
                        f.posible_cruce_con +
                        "</span>";
                } else if (f.cruce_tipo === "multiplo" && f.cruce_ratio_k) {
                    var cruceTipB =
                        _cruceScoreB +
                        _cruceNivelB +
                        " con art. " +
                        f.posible_cruce_con +
                        " (ratio \xd7" +
                        f.cruce_ratio_k +
                        "; pérdida fantasma por múltiplo)";
                    badgeCruceB =
                        ' <span class="label ' +
                        cruceCls +
                        '" title="' +
                        cruceTipB +
                        '">\xd7' +
                        f.cruce_ratio_k +
                        " art. " +
                        f.posible_cruce_con +
                        "</span>";
                } else {
                    var cruceTipB =
                        _cruceScoreB +
                        _cruceNivelB +
                        " con art. " +
                        f.posible_cruce_con +
                        " — posible error";
                    badgeCruceB =
                        ' <span class="label ' +
                        cruceCls +
                        '" title="' +
                        cruceTipB +
                        '">Art. ' +
                        f.posible_cruce_con +
                        "</span>";
                }
            }
            var deficitB = offsetB !== null ? Math.abs(offsetB) : null;
            detalle =
                '<span class="label label-danger" title="El stock cae a valores negativos estables: el sistema registra más stock del que existe físicamente entre recepciones.">Déficit estable</span>' +
                badgeCruceB +
                " Déficit: <strong>~" +
                (deficitB !== null ? deficitB.toFixed(1) : "—") +
                " ud.</strong>" +
                (dispB !== null ? " (±" + dispB.toFixed(1) + ")" : "") +
                " | " +
                nRecB +
                " rec." +
                " | " +
                _fmtF(f.fecha_primera) +
                "–" +
                _fmtF(f.fecha_ultima);
        } else if (f.tipo === "Merma acumulada") {
            // C7a — suelos positivos crecientes: merma no registrada
            var deltaA =
                f.delta_acumulado !== undefined && f.delta_acumulado !== null
                    ? parseFloat(f.delta_acumulado)
                    : null;
            var dispA =
                f.dispersion !== undefined ? parseFloat(f.dispersion) : null;
            var slopeA =
                f.tendencia !== undefined ? parseFloat(f.tendencia) : null;
            var nRecA = parseInt(f.n_recepciones) || 0;
            var _cruceScoreA =
                f.cruce_score !== undefined
                    ? "score=" + parseFloat(f.cruce_score).toFixed(2) + " — "
                    : "";
            var _cruceNivelA =
                f.cruce_nivel === "confirmado"
                    ? "cruce confirmado"
                    : f.cruce_nivel === "probable"
                      ? "cruce probable"
                      : "posible cruce";
            var cruceCls =
                f.cruce_nivel === "confirmado"
                    ? "label-success"
                    : f.cruce_nivel === "probable"
                      ? "label-danger"
                      : "label-warning";
            var badgeCruceA = "";
            if (f.posible_cruce_con) {
                if (f.cruce_tipo === "trio" && f.cruce_fuente_b) {
                    // C7d: este C7a es el destino; las fuentes son posible_cruce_con y cruce_fuente_b
                    var cruceTipA =
                        _cruceScoreA +
                        _cruceNivelA +
                        ": art. " +
                        f.posible_cruce_con +
                        " y art. " +
                        f.cruce_fuente_b +
                        " escaneados como este producto";
                    badgeCruceA =
                        ' <span class="label ' +
                        cruceCls +
                        '" title="' +
                        cruceTipA +
                        '">Trío art. ' +
                        f.posible_cruce_con +
                        "+" +
                        f.cruce_fuente_b +
                        "</span>";
                } else if (f.cruce_tipo === "multiplo" && f.cruce_ratio_k) {
                    // C7e: múltiplo k≥2
                    var cruceTipA =
                        _cruceScoreA +
                        _cruceNivelA +
                        " con art. " +
                        f.posible_cruce_con +
                        " (ratio \xd7" +
                        f.cruce_ratio_k +
                        "; pérdida fantasma por múltiplo)";
                    badgeCruceA =
                        ' <span class="label ' +
                        cruceCls +
                        '" title="' +
                        cruceTipA +
                        '">\xd7' +
                        f.cruce_ratio_k +
                        " art. " +
                        f.posible_cruce_con +
                        "</span>";
                } else {
                    // C7c: par simple
                    var cruceTipA =
                        _cruceScoreA +
                        _cruceNivelA +
                        " con art. " +
                        f.posible_cruce_con +
                        " — posible error";
                    badgeCruceA =
                        ' <span class="label ' +
                        cruceCls +
                        '" title="' +
                        cruceTipA +
                        '">Balanza: art. ' +
                        f.posible_cruce_con +
                        "</span>";
                }
            }
            detalle =
                '<span class="label label-warning" title="El suelo mínimo de stock sube recepción a recepción: el artículo pierde unidades de forma sistemática sin quedar registrado.">Merma progresiva</span>' +
                badgeCruceA +
                " Pérdida total: <strong>~" +
                (deltaA !== null ? deltaA.toFixed(0) : "—") +
                " ud.</strong>" +
                (slopeA !== null
                    ? " | <strong>+" + slopeA.toFixed(1) + " ud./rec.</strong>"
                    : "") +
                (dispA !== null ? " (±" + dispA.toFixed(1) + ")" : "") +
                " | " +
                nRecA +
                " rec." +
                " | " +
                _fmtF(f.fecha_primera) +
                "–" +
                _fmtF(f.fecha_ultima);
        } else if (
            f.tipo === "Agotamiento Estimado" ||
            f.tipo === "Punto de Pedido"
        ) {
            // C6a / C6b — recomendación primero, contexto después
            var stockC6 = parseFloat(f.stock_actual) || 0;
            // Si el stock es reconstruido y sigue siendo negativo se usa 0 como base de cálculo
            // para no sobreestimar el pedido. El valor real se muestra como referencia:
            // indica un posible cruce de albarán o entrada sin registrar.
            var _recNegativo = f.stock_reconstituido && stockC6 < 0;
            var stockC6Calculo = _recNegativo ? 0 : stockC6;
            var ropC6 = parseFloat(f.rop) || 0;
            var ssC6 = parseFloat(f.stock_seguridad) || 0;
            var dC6 = parseFloat(f.d_diaria) || 0;

            // Distribución usada y comportamiento del artículo
            var _c6mCfg = {
                Normal: {
                    lbl: "N",
                    cls: "label-success",
                    comp: "Rotación regular",
                    tip: "Normal — demanda estable y regular (gran consumo). ROP calculado con varianza empírica de la demanda.",
                    qTip: "Llevar stock hasta el ROP (demanda predecible).",
                },
                Gamma: {
                    lbl: "Γ",
                    cls: "label-warning",
                    comp: "Demanda asimétrica",
                    tip: "Gamma — demanda asimétrica o lead time variable. ROP = cuantil Gamma directo sobre la demanda en L.",
                    qTip: "Demanda asimétrica: cuantil Gamma ya incluye el SS necesario.",
                },
                BN: {
                    lbl: "BN",
                    cls: "label-info",
                    comp: "Compras en rachas",
                    tip: "Binomial Negativa — demanda con sobredispersión (ventas agrupadas en lotes o rachas). SS ampliado.",
                    qTip: "Demanda en rachas: se añade SS extra sobre el ROP por mayor incertidumbre.",
                },
                Binomial: {
                    lbl: "Bin",
                    cls: "label-primary",
                    comp: "Rotación acotada",
                    tip: "Binomial compuesta — distribución acotada (configurada por el usuario). SS según multiplicador σ.",
                    qTip: "Binomial: llevar stock hasta el ROP con SS por multiplicador σ.",
                },
                Poisson: {
                    lbl: "Poi",
                    cls: "label-default",
                    comp: "Artículo esporádico",
                    tip: "Poisson — demanda baja o poco frecuente. ROP conservador para artículos de baja rotación.",
                    qTip: "Poisson: llevar stock hasta el ROP (varianza ≈ media).",
                },
            };
            var _c6m = _c6mCfg[f.modelo_usado] || _c6mCfg["Poisson"];
            var esBN = f.modelo_usado === "BN";

            var qBase = Math.max(0, ropC6 - stockC6Calculo);
            var qRecomendada = Math.ceil(esBN ? qBase + ssC6 : qBase);
            var diasTrasPedido =
                dC6 > 0
                    ? Math.round((stockC6Calculo + qRecomendada) / dC6)
                    : null;

            var _esPeso = f.tipo_articulo === "peso";
            var _unidadC6 = _esPeso ? "kg" : "ud.";

            var badgeC6Modelo =
                ' <span class="label ' +
                _c6m.cls +
                '" title="' +
                _c6m.tip +
                '">' +
                _c6m.lbl +
                "</span>";
            var badgeC6Fuente =
                f.tipo === "Punto de Pedido"
                    ? (function () {
                          var dias = window.POSSTOCK_C6B_DIAS_HISTORICO || 90;
                          var aviso =
                              dias < 60
                                  ? " ⚠ ventana corta — puede no reflejar estacionalidad"
                                  : "";
                          return (
                              ' <span class="label label-default" title="ROP calculado sobre los últimos ' +
                              dias +
                              " días desde hoy (C6b)" +
                              aviso +
                              '">' +
                              dias +
                              "d</span>"
                          );
                      })()
                    : "";
            var badgeC6LT =
                f.lead_time_fuente === "proveedor"
                    ? ' <span class="label label-success" title="Lead time calculado desde intervalo entre albaranes del proveedor">LT prov.</span>'
                    : "";
            var _recMotivo = f.stock_rec_motivo;
            var _recTipBase =
                _recMotivo === "negativo"
                    ? "stockOn muy negativo (&lt; -2) — estimado desde última entrada de proveedor menos ventas posteriores"
                    : "stockOn sospechosamente alto (&gt; N\xD7ROP) — estimado desde última entrada de proveedor menos ventas posteriores";
            var _recTip = _recNegativo
                ? _recTipBase +
                  ". Stock estimado negativo: se asume stock=0 para el cálculo del pedido (posible cruce de albarán o entrada sin registrar)"
                : _recTipBase;
            var badgeC6Rec = f.stock_reconstituido
                ? ' <span class="label ' +
                  (_recNegativo ? "label-danger" : "label-warning") +
                  '" title="' +
                  _recTip +
                  '">~stk</span>'
                : "";
            var badgeC6Q =
                qRecomendada > 0
                    ? ' <span class="label label-warning" title="' +
                      _c6m.qTip +
                      '">Pedir ~' +
                      (_esPeso ? qRecomendada.toFixed(2) : qRecomendada) +
                      " " +
                      _unidadC6 +
                      "</span>"
                    : ' <span class="label label-success">Stock OK</span>';

            var _stockLabel = f.stock_reconstituido
                ? ' | <span title="' +
                  _recTip +
                  '">Stock ~est: <strong>' +
                  stockC6.toFixed(2) +
                  " " +
                  _unidadC6 +
                  "</strong>" +
                  (_recNegativo
                      ? ' <em class="text-muted">(pedido calc. desde 0)</em>'
                      : "") +
                  "</span>"
                : " | Stock: <strong>" +
                  stockC6.toFixed(2) +
                  " " +
                  _unidadC6 +
                  "</strong>";

            detalle =
                badgeC6Modelo +
                badgeC6Fuente +
                badgeC6LT +
                badgeC6Rec +
                badgeC6Q +
                ' <small class="text-muted">· ' +
                _c6m.comp +
                "</small>" +
                _stockLabel +
                " | Autonomía: <strong>" +
                (f.dias_autonomia !== undefined
                    ? f.dias_autonomia + " d"
                    : "—") +
                "</strong>" +
                " | LT: " +
                (f.lead_time_dias !== undefined
                    ? f.lead_time_dias + " d"
                    : "—") +
                " | ROP: " +
                ropC6.toFixed(2) +
                " " +
                _unidadC6 +
                (diasTrasPedido !== null
                    ? " | Cobertura tras pedido: <strong>" +
                      diasTrasPedido +
                      " d</strong>"
                    : "");
        }

        // C3b "nunca" y C5 en curso: el rango del mayor se extiende diasPost días
        // después del fin del periodo para cubrir la ventana de validación post-periodo.
        var fiMayor = fiInicio;
        var ffMayor = ffMov;
        var _needsPostWindow =
            (f.tipo === "Entrada sin rotación previa" && !f.ultima_salida) ||
            (f.tipo === "Venta Cero (Posible Rotura Física)" &&
                !f.fecha_fin_rotura);
        if (_needsPostWindow && ffMov) {
            var diasPost = window.POSSTOCK_C3B_DIAS_POST || 14;
            fiMayor = periodo.fecha_inicio_stock || fiInicio;
            var dtFin = new Date(ffMov);
            dtFin.setDate(dtFin.getDate() + diasPost);
            ffMayor = dtFin.toISOString().split("T")[0];
        }
        var urlMayor =
            "../../modulos/mod_producto/DetalleMayor.php" +
            "?idArticulo=" +
            f.idArticulo +
            "&fecha_inicial=" +
            fiMayor +
            "&fecha_final=" +
            ffMayor;

        // Tipo de incidencia para mostrar en celda:
        // C3a diferencia fast mover (cadencia ≤7d = riesgo caducidad/merma)
        // de slow mover (rotación caída = problema de surtido o estacionalidad).
        var tipoLabel;
        if (f.tipo === "Caída de rotación") {
            var _avgCadTipo = parseFloat(f.avg_cadencia_dias) || 0;
            tipoLabel =
                _avgCadTipo > 0 && _avgCadTipo <= 7 && !f.desde_reposicion
                    ? "Riesgo caducidad"
                    : "Rotación caída";
        } else if (f.tipo === "Entrada con stock alto") {
            if (f.c2_categoria === "acumulacion") {
                tipoLabel = "Acumulación crónica";
            } else if (f.c2_categoria === "tendencia") {
                tipoLabel = "Pedidos excesivos";
            } else {
                var _ncantC2 = parseFloat(f.ncant) || 0;
                var _ncantAC2 =
                    f.ncant_anterior !== undefined && f.ncant_anterior !== null
                        ? parseFloat(f.ncant_anterior)
                        : null;
                var _diasC2 =
                    f.dias_desde_anterior !== undefined &&
                    f.dias_desde_anterior !== null
                        ? parseInt(f.dias_desde_anterior)
                        : null;
                var _dupProb =
                    _diasC2 !== null &&
                    _diasC2 <= 1 &&
                    _ncantAC2 !== null &&
                    Math.abs(_ncantC2 - _ncantAC2) /
                        Math.max(_ncantC2, _ncantAC2) <
                        0.15;
                var _dupPos =
                    !_dupProb &&
                    _diasC2 !== null &&
                    _diasC2 <= 3 &&
                    _ncantAC2 !== null &&
                    Math.abs(_ncantC2 - _ncantAC2) /
                        Math.max(_ncantC2, _ncantAC2) <
                        0.15;
                tipoLabel = _dupProb
                    ? "Duplicado probable"
                    : _dupPos
                      ? "Posible duplicado"
                      : "Sobrestock entrada";
            }
        } else if (
            f.tipo === "Agotamiento Estimado" ||
            f.tipo === "Punto de Pedido"
        ) {
            // C6a / C6b — dinámica: "Reponer ahora" si hay cantidad a pedir, "ROP alcanzado" si stock suficiente
            var _ropTL = parseFloat(f.rop) || 0;
            var _stTLRaw = parseFloat(f.stock_actual) || 0;
            var _stTL = f.stock_reconstituido && _stTLRaw < 0 ? 0 : _stTLRaw;
            var _ssTL = parseFloat(f.stock_seguridad) || 0;
            var _qTL = Math.ceil(
                f.modelo_usado === "BN"
                    ? Math.max(0, _ropTL - _stTL) + _ssTL
                    : Math.max(0, _ropTL - _stTL),
            );
            tipoLabel = _qTL > 0 ? "Reponer ahora" : "ROP alcanzado";
        } else {
            tipoLabel = tipoLabels[f.tipo] || f.tipo;
        }

        html +=
            "<tr data-tipo='" +
            f.tipo.replace(/'/g, "&#39;") +
            "'>" +
            "<td>" +
            f.idArticulo +
            "</td>" +
            "<td>" +
            (f.nombre || "—") +
            "</td>" +
            "<td>" +
            tipoLabel +
            "</td>" +
            "<td>" +
            (badgeSev[f.severidad] || f.severidad) +
            "</td>" +
            "<td>" +
            detalle +
            "</td>" +
            "<td>" +
            (f.posible_causa || "") +
            "</td>" +
            "<td><a href='" +
            urlMayor +
            "' target='_blank'>" +
            "<i class='glyphicon glyphicon-list-alt'></i> Ver mayor" +
            "</a></td>" +
            "</tr>";
    });

    html += "</tbody></table>";
    $("#posstockTablaWrap").html(html);
}

/**
 * Descarga las incidencias del periodo activo como fichero CSV.
 * Usa un formulario oculto para forzar la descarga del fichero (AJAX no puede
 * disparar una descarga de navegador directamente).
 */
function exportarPOSStockCSV() {
    var periodo = window.posstockPeriodoActivo;
    if (!periodo) return;

    var params = {
        pulsado: "exportarPOSStockCSV",
        fecha_inicio_movimientos: periodo.fecha_inicio_movimientos,
        fecha_fin_movimientos: periodo.fecha_fin_movimientos,
        fecha_inicio_stock: periodo.fecha_inicio_stock,
        fecha_fin_stock: periodo.fecha_fin_stock,
        casos_incluir: _posstockGetCasosIncluir(
            window.posstockTipoIncidenciaActivo || "",
        ),
        tipo_periodo: window.posstockTipoActivo || "",
        min_ventas_c5: (periodo && periodo.min_ventas_c5) || 3,
        familias_incluir: (window.posstockFamiliasIncluir || [])
            .map(function (f) {
                return f.id;
            })
            .join(","),
        familias_excluir: (window.posstockFamiliasExcluir || [])
            .map(function (f) {
                return f.id;
            })
            .join(","),
        proveedores_incluir: (window.posstockProveedoresIncluir || [])
            .map(function (p) {
                return p.id;
            })
            .join(","),
        proveedor_todos_productos: window.posstockProveedorTodosProductos
            ? "1"
            : "0",
    };

    var form = document.createElement("form");
    form.method = "POST";
    form.action = "tareas.php";
    Object.keys(params).forEach(function (key) {
        var input = document.createElement("input");
        input.type = "hidden";
        input.name = key;
        input.value = params[key];
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

/**
 * Genera el PDF de incidencias del periodo activo con TCPDF (servidor)
 * y lo abre en una nueva pestaña.
 */
function imprimirPOSStockPDF() {
    var periodo = window.posstockPeriodoActivo;
    if (!periodo) return;

    var btn = document.getElementById("posstockBtnImprimir");
    if (btn) {
        btn.disabled = true;
        btn.textContent = "Generando PDF…";
    }

    $.ajax({
        data: {
            pulsado: "imprimirPOSStockPDF",
            fecha_inicio_movimientos: periodo.fecha_inicio_movimientos,
            fecha_fin_movimientos: periodo.fecha_fin_movimientos,
            fecha_inicio_stock: periodo.fecha_inicio_stock,
            fecha_fin_stock: periodo.fecha_fin_stock,
            casos_incluir: _posstockGetCasosIncluir(
                window.posstockTipoIncidenciaActivo || "",
            ),
            tipo_periodo: window.posstockTipoActivo || "",
            min_ventas_c5: (periodo && periodo.min_ventas_c5) || 3,
            familias_incluir: (window.posstockFamiliasIncluir || [])
                .map(function (f) {
                    return f.id;
                })
                .join(","),
            familias_excluir: (window.posstockFamiliasExcluir || [])
                .map(function (f) {
                    return f.id;
                })
                .join(","),
            proveedores_incluir: (window.posstockProveedoresIncluir || [])
                .map(function (p) {
                    return p.id;
                })
                .join(","),
            proveedor_todos_productos: window.posstockProveedorTodosProductos
                ? "1"
                : "0",
        },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) {
                _posstockMostrarError(
                    "Error al generar PDF: " + resultado.error,
                );
            } else {
                window.open(resultado.url, "_blank");
            }
        },
        error: function () {
            _posstockMostrarError("Error de comunicación al generar el PDF.");
        },
        complete: function () {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML =
                    '<i class="glyphicon glyphicon-print"></i> Imprimir PDF';
            }
        },
    });
}

window.cargarDatosPosstock = cargarDatosPosstock;
window.pintarTablaIncidencias = pintarTablaIncidencias;
window.exportarPOSStockCSV = exportarPOSStockCSV;
window.imprimirPOSStockPDF = imprimirPOSStockPDF;

// =====================================================================
//       POSSTOCK — Selectores de periodo y barra de navegación
// =====================================================================

// Tipos de incidencia disponibles para el filtro anual.
// caso4 se añade dinámicamente en tiempo de ejecución si POSSTOCK_INCLUIR_STOCK_INACTIVO === true.
var POSSTOCK_TIPOS_INCIDENCIA = [
    { v: "caso1", t: "Stock Negativo / Desajuste", short: "C1" },
    { v: "caso2", t: "Entrada con stock alto", short: "C2" },
    { v: "caso3a", t: "Caída de rotación", short: "C3a" },
    { v: "caso3b", t: "Entrada sin rotación previa", short: "C3b" },
    { v: "caso5", t: "Venta Cero (Rotura física)", short: "C5" },
    {
        v: "caso6a",
        t: "Agotamiento Estimado — C6a (ROP estacional)",
        short: "C6a",
    },
    {
        v: "caso6b",
        t: "Punto de Pedido — C6b (ROP histórico fijo)",
        short: "C6b",
        soloAnual: true,
    },
    { v: "caso7b", t: "Recepción no registrada — C7b", short: "C7b" },
    { v: "caso7a", t: "Merma no registrada — C7a", short: "C7a" },
];

/** Devuelve los tipos de incidencia aplicables incluyendo caso4 si está habilitado. */
function _posstockTiposActivos() {
    var tipos = POSSTOCK_TIPOS_INCIDENCIA.slice();
    if (window.POSSTOCK_INCLUIR_STOCK_INACTIVO) {
        tipos.push({ v: "caso4", t: "Stock Inactivo en Periodo", short: "C4" });
    }
    return tipos;
}

/**
 * Devuelve el valor de casos_incluir para enviar al backend.
 *
 * - Vista anual: tipoIncidenciaAnual es el caso concreto (string); se devuelve tal cual.
 * - Resto: lee los checkboxes marcados y devuelve una cadena separada por comas.
 *   Si no hay checkboxes (aún no inicializados), devuelve "" (todos los casos activos).
 */
function _posstockGetCasosIncluir(tipoIncidenciaAnual) {
    if (tipoIncidenciaAnual) return tipoIncidenciaAnual;
    var seleccionados = [];
    _posstockTiposActivos().forEach(function (ti) {
        var chk = document.getElementById("posstockChk_" + ti.v);
        if (chk) {
            // Checkbox renderizado → respetar su estado
            if (chk.checked) seleccionados.push(ti.v);
        } else if (!ti.soloAnual) {
            // Sin checkbox y no es exclusivo de vista anual → incluir por defecto
            // (ocurre antes de que se inicialicen los checkboxes)
            seleccionados.push(ti.v);
        }
        // soloAnual sin checkbox → NO incluir en vistas no anuales
    });
    return seleccionados.join(",");
}

/**
 * Renderiza los checkboxes de tipo de incidencia en #posstockChecksCasos
 * y muestra la fila. Solo se llama en vistas no anuales.
 * caso1 y caso6a aparecen marcados por defecto.
 * Los tipos con soloAnual:true se omiten (solo aparecen en el selector anual).
 */
function _posstockInicializarFiltroCasos() {
    var wrap = document.getElementById("posstockChecksCasos");
    if (!wrap) return;
    var tipos = _posstockTiposActivos().filter(function (ti) {
        return !ti.soloAnual;
    });
    var _defChecked = { caso1: true, caso6a: true };
    var html = "";
    tipos.forEach(function (ti) {
        var checked = _defChecked[ti.v] ? "checked" : "";
        html +=
            '<label class="checkbox-inline" style="margin-left:8px; font-weight:normal;">' +
            '<input type="checkbox" id="posstockChk_' +
            ti.v +
            '" value="' +
            ti.v +
            '" ' +
            checked +
            ' onchange="posstockRecargarPorCasos()"> ' +
            '<span class="label label-default">' +
            ti.short +
            "</span> " +
            ti.t +
            "</label>";
    });
    wrap.innerHTML = html;
    document.getElementById("posstockFilaCasos").style.display = "";
}

/**
 * Relanza la consulta al backend con los casos actualmente seleccionados.
 * Se llama desde onchange de los checkboxes de tipo de incidencia.
 */
function posstockRecargarPorCasos() {
    if (!window.posstockPeriodoActivo) return;
    cargarDatosPosstock(
        window.posstockPeriodoActivo,
        window.posstockTipoIncidenciaActivo || "",
    );
}
window.posstockRecargarPorCasos = posstockRecargarPorCasos;

var MESES = [
    "Ene",
    "Feb",
    "Mar",
    "Abr",
    "May",
    "Jun",
    "Jul",
    "Ago",
    "Sep",
    "Oct",
    "Nov",
    "Dic",
];

/**
 * Rellena el selector de número de periodo según el tipo elegido.
 * También habilita/deshabilita el botón Generar.
 */
function posstockActualizarNumero(mantenerNumero) {
    var tipo = document.getElementById("posstockTipo").value;
    var anio = parseInt(document.getElementById("posstockAnio").value, 10);
    var sel = document.getElementById("posstockNumero");

    // Cuando se cambia el año conservamos el número/tipo-incidencia actual para restaurarlo.
    var prevNumero = mantenerNumero && sel.value ? sel.value : null;

    sel.innerHTML = "";
    sel.disabled = true;
    document.getElementById("posstockBtnGenerar").disabled = true;
    document.getElementById("posstockAvisoVentana").style.display = "none";

    // Ocultar la barra de botones de navegación rápida al cambiar el tipo de periodo
    // para que los botones del tipo anterior no induzcan a error.
    var barra = document.getElementById("posstockBarraBotones");
    if (barra) barra.style.display = "none";
    var wrapBotones = document.getElementById("posstockBotonesPeriodo");
    if (wrapBotones) wrapBotones.innerHTML = "";

    // Cambiar label según contexto
    var labelEl = document.getElementById("posstockLabelNumero");
    if (labelEl)
        labelEl.textContent = tipo === "anual" ? "Tipo de análisis" : "Periodo";

    // Ocultar/mostrar filtro de casos según tipo de periodo
    var filaCasos = document.getElementById("posstockFilaCasos");
    if (filaCasos) {
        filaCasos.style.display = tipo === "anual" ? "none" : "";
    }

    if (!tipo || !anio) return;

    var anioActual = new Date().getFullYear();
    var ventanaDias = window.POSSTOCK_VENTANA_DIAS || 0;

    // Intenta restaurar el número previo: si existe la opción y no está disabled, la selecciona
    // y lanza posstockGenerar() automáticamente.
    function _intentarRestaurar() {
        if (!prevNumero) return;
        var opts = sel.options;
        for (var i = 0; i < opts.length; i++) {
            if (
                String(opts[i].value) === String(prevNumero) &&
                !opts[i].disabled
            ) {
                sel.value = prevNumero;
                document.getElementById("posstockBtnGenerar").disabled = false;
                posstockGenerar();
                return;
            }
        }
    }

    if (tipo === "anual") {
        // El "número" es el tipo de incidencia.
        // Para el año en curso con ventana activa todos los tipos excepto C6b están bloqueados.
        var anualEnVentana = anio === anioActual && ventanaDias > 0;
        sel.innerHTML = '<option value="">— seleccionar —</option>';
        _posstockTiposActivos().forEach(function (ti) {
            var bloqueado = anualEnVentana && ti.v !== "caso6b";
            var opt = document.createElement("option");
            opt.value = ti.v;
            opt.text = ti.t + (bloqueado ? " ⚠ (ventana)" : "");
            opt.disabled = bloqueado;
            if (bloqueado)
                opt.title =
                    "Periodo dentro de la ventana de consolidación (" +
                    ventanaDias +
                    " días)";
            sel.appendChild(opt);
        });
        sel.disabled = false;
        _intentarRestaurar();
        return;
    }

    if (anio === anioActual) {
        // Año en curso: AJAX para obtener solo periodos iniciados y marcar ventana
        $.ajax({
            data: {
                pulsado: "getInfoPeriodos",
                tipo: tipo,
                anio: anio,
                ventana_dias: ventanaDias,
            },
            url: "tareas.php",
            type: "post",
            success: function (response) {
                var resultado = JSON.parse(response);
                if (resultado.error) return;
                sel.innerHTML = '<option value="">— seleccionar —</option>';
                (resultado.periodos || []).forEach(function (p) {
                    var etiqueta = _posstockEtiquetaOpcion(
                        tipo,
                        p.numero,
                        anio,
                    );
                    var opt = document.createElement("option");
                    opt.value = p.numero;
                    opt.text = etiqueta + (p.en_ventana ? " ⚠ (ventana)" : "");
                    opt.disabled = p.en_ventana;
                    if (p.en_ventana)
                        opt.title =
                            "Dentro de la ventana de consolidación (" +
                            ventanaDias +
                            " días)";
                    sel.appendChild(opt);
                });
                sel.disabled = false;
                _intentarRestaurar();
            },
        });
    } else {
        // Año pasado/futuro: opciones estáticas, sin restricciones de fecha
        sel.innerHTML = '<option value="">— seleccionar —</option>';
        _posstockOpcionesNumeroPeriodo(tipo, anio).forEach(function (o) {
            sel.innerHTML += '<option value="' + o.v + '">' + o.t + "</option>";
        });
        sel.disabled = false;
        _intentarRestaurar();
    }
}

/** Genera todas las opciones {v, t} de un tipo de periodo para un año dado. */
function _posstockOpcionesNumeroPeriodo(tipo, anio) {
    var opciones = [];
    if (tipo === "semana") {
        var totalSem = _posstockTotalSemanas(anio);
        for (var s = 1; s <= totalSem; s++)
            opciones.push({ v: s, t: "Semana " + s });
    } else if (tipo === "quincena") {
        for (var q = 1; q <= 24; q++) {
            var mes = Math.ceil(q / 2);
            var mitad = q % 2 === 1 ? "1ª" : "2ª";
            opciones.push({ v: q, t: mitad + " quincena " + MESES[mes - 1] });
        }
    } else if (tipo === "mes") {
        for (var m = 1; m <= 12; m++)
            opciones.push({ v: m, t: MESES[m - 1] + " " + anio });
    } else if (tipo === "trimestre") {
        opciones = [
            { v: 1, t: "T1 (Ene–Mar)" },
            { v: 2, t: "T2 (Abr–Jun)" },
            { v: 3, t: "T3 (Jul–Sep)" },
            { v: 4, t: "T4 (Oct–Dic)" },
        ];
    } else if (tipo === "cuatrimestre") {
        opciones = [
            { v: 1, t: "C1 (Ene–Abr)" },
            { v: 2, t: "C2 (May–Ago)" },
            { v: 3, t: "C3 (Sep–Dic)" },
        ];
    } else if (tipo === "semestre") {
        opciones = [
            { v: 1, t: "1er semestre (Ene–Jun)" },
            { v: 2, t: "2º semestre (Jul–Dic)" },
        ];
    }
    return opciones;
}

/** Devuelve la etiqueta de dropdown de un periodo concreto. */
function _posstockEtiquetaOpcion(tipo, n, anio) {
    var lista = _posstockOpcionesNumeroPeriodo(tipo, anio);
    for (var i = 0; i < lista.length; i++) {
        if (lista[i].v === n) return lista[i].t;
    }
    return tipo + " " + n;
}

/**
 * Devuelve el Date correspondiente al fin de la semana 1 del año
 * (semana 1 = 01-Ene → primer domingo del año).
 * Si el 01-Ene ya es domingo, la semana 1 termina el propio 01-Ene.
 */
function _posstockFinSem1(anio) {
    var jan1 = new Date(anio, 0, 1);
    var dow = jan1.getDay(); // 0=dom, 1=lun … 6=sab
    if (dow === 0) return jan1;
    return new Date(anio, 0, 1 + (7 - dow));
}

/**
 * Calcula el número total de semanas del año con el sistema anclado al 01-Ene.
 * Semana 1 = 01-Ene → primer domingo. Semanas 2+ = lun→dom.
 */
function _posstockTotalSemanas(anio) {
    var finSem1 = _posstockFinSem1(anio);
    var dec31 = new Date(anio, 11, 31);
    var diasRest = Math.round((dec31 - finSem1) / 86400000);
    return 1 + Math.ceil(diasRest / 7);
}

/**
 * Llama a calcularPeriodoPosstock (AJAX), comprueba la ventana de consolidación
 * y si es válido llama a cargarDatosPosstock() y pinta la barra de botones.
 */
function posstockGenerar() {
    var tipo = document.getElementById("posstockTipo").value;
    var numero = document.getElementById("posstockNumero").value;
    var anio = document.getElementById("posstockAnio").value;

    if (!tipo || !numero || !anio) return;

    $.ajax({
        data: {
            pulsado: "calcularPeriodoPosstock",
            tipo: tipo,
            numero: numero,
            anio: anio,
        },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var periodo = JSON.parse(response);
            if (periodo.error) {
                _posstockMostrarError(
                    "Error al calcular periodo: " + periodo.error,
                );
                return;
            }

            // Para anual: numero es el tipo de incidencia (string); resto: entero
            var tipoInc = tipo === "anual" ? numero : "";

            // ── Restricción ventana_dias (0 = sin restricción) ───────
            var _enVentana = false;
            if (window.POSSTOCK_VENTANA_DIAS > 0) {
                var hoy = new Date();
                hoy.setHours(0, 0, 0, 0);
                var limite = new Date(hoy);
                limite.setDate(limite.getDate() - window.POSSTOCK_VENTANA_DIAS);
                var ffMov = new Date(periodo.fecha_fin_movimientos);
                if (ffMov >= limite) {
                    document.getElementById(
                        "posstockAvisoVentana",
                    ).style.display = "";
                    // C6b usa hoy como ancla y no depende del periodo analizado:
                    // se permite ejecutar aunque el periodo esté en la ventana de consolidación.
                    if (tipoInc !== "caso6b") return;
                    _enVentana = true;
                }
            }
            if (!_enVentana)
                document.getElementById("posstockAvisoVentana").style.display =
                    "none";
            window._posstockAnualEnVentana = _enVentana;

            // Guardar periodo activo y cargar datos
            window.posstockPeriodoActivo = periodo;
            window.posstockTipoActivo = tipo;
            window.posstockAnioActivo = parseInt(anio, 10);
            window.posstockTipoIncidenciaActivo = tipoInc;

            cargarDatosPosstock(periodo, tipoInc);
            posstockPintarBarra(
                tipo,
                tipo === "anual" ? numero : parseInt(numero, 10),
            );
        },
        error: function () {
            _posstockMostrarError(
                "Error de comunicación al calcular el periodo.",
            );
        },
    });
}

/**
 * Pinta la barra de botones de navegación entre periodos.
 * Consulta al backend (getInfoPeriodos) para obtener el estado en_ventana de cada
 * periodo, evitando duplicar la lógica de fechas en el frontend.
 *
 * @param {string}         tipo          Tipo de periodo activo
 * @param {number|string}  numeroActivo  Número (o tipo de incidencia si anual) activo
 */
function posstockPintarBarra(tipo, numeroActivo) {
    var wrap = document.getElementById("posstockBotonesPeriodo");
    wrap.innerHTML = "";

    // Para anual: botones por tipo de incidencia
    if (tipo === "anual") {
        var _barraEnVentana = window._posstockAnualEnVentana || false;
        _posstockTiposActivos().forEach(function (ti) {
            var cls, extras;
            if (_barraEnVentana && ti.v !== "caso6b") {
                cls = "btn btn-default btn-xs disabled";
                extras =
                    'disabled title="Periodo en ventana de consolidación — solo C6b disponible"';
            } else {
                cls =
                    ti.v === numeroActivo
                        ? "btn btn-primary btn-xs"
                        : "btn btn-default btn-xs";
                extras = "";
            }
            wrap.innerHTML +=
                '<button type="button" class="' +
                cls +
                '" ' +
                extras +
                " " +
                "onclick=\"posstockNavegar('" +
                ti.v +
                "')\" " +
                'id="posstockBtn_' +
                ti.v +
                '">' +
                ti.short +
                " " +
                ti.t +
                "</button> ";
        });
        document.getElementById("posstockBarraBotones").style.display = "";
        return;
    }

    // Consultar al backend el estado (en_ventana) de cada periodo
    var anio = window.posstockAnioActivo || new Date().getFullYear();
    $.ajax({
        data: {
            pulsado: "getInfoPeriodos",
            tipo: tipo,
            anio: anio,
            ventana_dias: window.POSSTOCK_VENTANA_DIAS || 0,
        },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) return; // fallo silencioso en la barra

            var periodos = resultado.periodos || [];
            wrap.innerHTML = "";
            periodos.forEach(function (p) {
                var n = p.numero;
                var enVentana = p.en_ventana;
                var etiqueta = posstockEtiquetaBoton(tipo, n);

                var cls, extras;
                if (enVentana) {
                    cls = "btn btn-default btn-xs disabled";
                    extras =
                        'disabled title="Dentro de la ventana de consolidación (' +
                        window.POSSTOCK_VENTANA_DIAS +
                        ' días)"';
                } else if (n === numeroActivo) {
                    cls = "btn btn-primary btn-xs";
                    extras = "";
                } else {
                    cls = "btn btn-default btn-xs";
                    extras = "";
                }

                wrap.innerHTML +=
                    '<button type="button" class="' +
                    cls +
                    '" ' +
                    extras +
                    " " +
                    'onclick="posstockNavegar(' +
                    n +
                    ')" ' +
                    'id="posstockBtn_' +
                    n +
                    '">' +
                    etiqueta +
                    "</button> ";
            });

            document.getElementById("posstockBarraBotones").style.display = "";
        },
    });
}

/** Devuelve la etiqueta de un botón de la barra según tipo y número. */
function posstockEtiquetaBoton(tipo, n) {
    if (tipo === "semana") return "S" + n;
    if (tipo === "mes") return MESES[n - 1];
    if (tipo === "trimestre") return "T" + n;
    if (tipo === "cuatrimestre") return "C" + n;
    if (tipo === "semestre") return n === 1 ? "1S" : "2S";
    if (tipo === "anual") return "Año";
    if (tipo === "quincena") {
        var mes = Math.ceil(n / 2);
        var mitad = n % 2 === 1 ? "a" : "b";
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

    // Para anual: numero es el tipo de incidencia (string)
    if (tipo === "anual") {
        // Si el periodo está en la ventana de consolidación solo se permite C6b
        if (window._posstockAnualEnVentana && numero !== "caso6b") return;
        window.posstockTipoIncidenciaActivo = numero;
        var botonesActuales = document.querySelectorAll("[id^='posstockBtn_']");
        botonesActuales.forEach(function (b) {
            if (!b.disabled) b.className = "btn btn-default btn-xs";
        });
        var btnActivo = document.getElementById("posstockBtn_" + numero);
        if (btnActivo) btnActivo.className = "btn btn-primary btn-xs";
        cargarDatosPosstock(window.posstockPeriodoActivo, numero);
        return;
    }

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
        data: {
            pulsado: "calcularPeriodoPosstock",
            tipo: tipo,
            numero: numero,
            anio: anio,
        },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var periodo = JSON.parse(response);
            if (periodo.error) {
                _posstockMostrarError(periodo.error);
                return;
            }
            window.posstockPeriodoActivo = periodo;
            cargarDatosPosstock(periodo);
        },
        error: function () {
            _posstockMostrarError(
                "Error de comunicación al navegar entre periodos.",
            );
        },
    });
}

// Habilitar botón Generar cuando se elige un número de periodo
// e inicializar los checkboxes de casos en la carga de página.
document.addEventListener("DOMContentLoaded", function () {
    document
        .getElementById("posstockNumero")
        .addEventListener("change", function () {
            document.getElementById("posstockBtnGenerar").disabled =
                this.value === "";
        });

    _posstockInicializarFiltroCasos();
});

window.posstockActualizarNumero = posstockActualizarNumero;
window.posstockGenerar = posstockGenerar;
window.posstockNavegar = posstockNavegar;
