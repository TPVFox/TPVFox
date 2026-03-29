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
    let opcion = document.getElementById("opcion" + checkID[0]).value;
    let fecha_inicial = document.getElementById("idFechaInicio").value;
    let fecha_final = document.getElementById("idFechaFinal").value;
    if (fecha_final === "") {
        let today = new Date();
        fecha_final =
            today.getFullYear() +
            "-" +
            (today.getMonth() + 1) +
            "-" +
            today.getDate();
    }
    if (fecha_inicial === "") {
        fecha_inicial = new Date().getFullYear() + "-01-01";
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
    $.ajax({
        data: { pulsado: "obtenerLoading" },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            abrirModal("Procesando", resultado.html);
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
        if (a.orden_clave > b.orden_clave) return 1;
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
                _posstockMostrarError(
                    "Error al cargar configuración: " + resultado.error,
                );
                return;
            }
            abrirModal("Configuración POSStock", resultado.html);
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
    var params = {
        pulsado: "guardarConfigPosstock",
        datosFormulario: JSON.stringify(datosFormulario),
    };

    $.ajax({
        data: params,
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) {
                var htmlError =
                    '<div class="alert alert-danger alert-sm">' +
                    resultado.error +
                    "</div>";
                $("#formConfigPosstock").prepend(htmlError);
                return;
            }
            // Actualizar variables JS con los nuevos valores guardados
            if (resultado.c3b_dias_post !== undefined)
                window.POSSTOCK_C3B_DIAS_POST = resultado.c3b_dias_post;
            if (resultado.c3a_multiplicador !== undefined)
                window.POSSTOCK_C3A_MULTIPLICADOR = resultado.c3a_multiplicador;
            if (resultado.c6b_dias_historico !== undefined)
                window.POSSTOCK_C6B_DIAS_HISTORICO =
                    resultado.c6b_dias_historico;
            if (resultado.mostrar_tecnico !== undefined)
                window.POSSTOCK_MOSTRAR_TECNICO = resultado.mostrar_tecnico;
            if (resultado.ventana_dias !== undefined)
                window.POSSTOCK_VENTANA_DIAS = resultado.ventana_dias;

            // Si hay un periodo activo, regenerar con la nueva configuración
            if (
                resultado.ventana_dias !== undefined &&
                window.posstockTipoActivo &&
                window.posstockPeriodoActivo
            ) {
                posstockActualizarNumero(true);
            }

            var htmlOk =
                '<div class="alert alert-success alert-sm">Configuración guardada.</div>';
            $("#posstockTablaWrap").html(htmlOk).show();
            cerrarModal();
        },
        error: function () {
            _posstockMostrarError(
                "Error de comunicación al guardar la configuración.",
            );
        },
    });
}

/** Muestra/oculta la descripción del modelo estadístico en el modal de config. */
function modalToggleModeloEstadistico() {
    var v = document.getElementById("modelo_estadistico");
    var bDesc = document.getElementById("modeloEstadisticoDesc");
    if (!v || !bDesc) return;
    if (window._modeloDescPosstock && window._modeloDescPosstock[v.value]) {
        bDesc.textContent = window._modeloDescPosstock[v.value];
    }
}

window.modalToggleModeloEstadistico = modalToggleModeloEstadistico;
window.modalTogglePoissonConfianza = modalToggleModeloEstadistico;
window.abrirModalConfigPosstock = abrirModalConfigPosstock;
window.guardarConfigPosstock = guardarConfigPosstock;

// =====================================================================
//       POSSTOCK — Filtro de familias
// =====================================================================

window.posstockFamiliasIncluir = [];
window.posstockFamiliasExcluir = [];
var _posstockFamiliasCache = null;

function posstockAbrirFiltroFamilias() {
    $.ajax({
        data: {
            pulsado: "getFamiliasPosstock",
            familias_incluir_json: JSON.stringify(
                window.posstockFamiliasIncluir || [],
            ),
            familias_excluir_json: JSON.stringify(
                window.posstockFamiliasExcluir || [],
            ),
        },
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
            abrirModal("Filtrar familias — POSStock", resultado.html);
        },
        error: function () {
            _posstockMostrarError("Error de comunicación al cargar familias.");
        },
    });
}

/** Agrega una familia a la tabla incluir/excluir del modal. */
function posstockAgregarFamilia(lista) {
    var input = document.getElementById("posstockBuscar_" + lista);
    var val = input ? input.value.trim() : "";
    var encontrado = null;

    (_posstockFamiliasCache || []).forEach(function (f) {
        if (f.nombre.toLowerCase() === val.toLowerCase()) encontrado = f;
    });
    if (!encontrado) {
        (_posstockFamiliasCache || []).forEach(function (f) {
            if (
                !encontrado &&
                f.nombre.toLowerCase().includes(val.toLowerCase())
            )
                encontrado = f;
        });
    }

    var wrapErr = document.getElementById("posstockFamiliaError_" + lista);
    if (!encontrado) {
        if (wrapErr) wrapErr.textContent = "Familia no encontrada: " + val;
        return;
    }
    if (wrapErr) wrapErr.textContent = "";

    var tabla = document.querySelector("#posstockTabla_" + lista + " tbody");
    var rows = tabla ? Array.from(tabla.querySelectorAll("tr")) : [];
    if (
        rows.some(function (r) {
            return parseInt(r.dataset.id) === encontrado.id;
        })
    ) {
        if (wrapErr) wrapErr.textContent = "Ya está en la lista.";
        return;
    }
    if (wrapErr) wrapErr.textContent = "";
    if (tabla)
        tabla.insertAdjacentHTML(
            "beforeend",
            _htmlFilaFamilia(encontrado.id, encontrado.nombre),
        );
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
    var rows = document.querySelectorAll(
        "#posstockTabla_" + lista + " tbody tr",
    );
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
        badge.className = "label label-success";
        badge.textContent = nInc + " incluidas";
    } else if (nExc > 0) {
        badge.className = "label label-warning";
        badge.textContent = nExc + " excluidas";
    } else {
        badge.className = "label label-default";
        badge.textContent = "Todas";
    }
}

/** Genera <tr> mínima para una familia (usada al agregar desde el modal). */
function _htmlFilaFamilia(id, nombre) {
    return (
        '<tr data-id="' +
        id +
        '" data-nombre="' +
        nombre.replace(/"/g, "&quot;") +
        '">' +
        "<td>" +
        nombre +
        "</td>" +
        '<td><button type="button" class="btn btn-xs btn-danger" onclick="posstockEliminarFamilia(this)">' +
        '<i class="glyphicon glyphicon-remove"></i></button></td></tr>'
    );
}

window.posstockAbrirFiltroFamilias = posstockAbrirFiltroFamilias;
window.posstockAgregarFamilia = posstockAgregarFamilia;
window.posstockEliminarFamilia = posstockEliminarFamilia;
window.posstockAplicarFiltroFamilias = posstockAplicarFiltroFamilias;

// =====================================================================
//       POSSTOCK — Filtro de proveedores
// =====================================================================

window.posstockProveedoresIncluir = [];
window.posstockProveedorTodosProductos = false;
var _posstockProveedoresCache = null;

function posstockAbrirFiltroProveedores() {
    $.ajax({
        data: {
            pulsado: "getProveedoresList",
            proveedores_incluir_json: JSON.stringify(
                window.posstockProveedoresIncluir || [],
            ),
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
                    "Error al cargar proveedores: " + resultado.error,
                );
                return;
            }
            _posstockProveedoresCache = resultado.proveedores;
            abrirModal("Filtrar proveedor — POSStock", resultado.html);
        },
        error: function () {
            _posstockMostrarError(
                "Error de comunicación al cargar proveedores.",
            );
        },
    });
}

function posstockAgregarProveedor() {
    var input = document.getElementById("posstockBuscarProveedor");
    var val = input ? input.value.trim() : "";
    var encontrado = null;

    (_posstockProveedoresCache || []).forEach(function (p) {
        if (p.nombre.toLowerCase() === val.toLowerCase()) encontrado = p;
    });
    if (!encontrado) {
        (_posstockProveedoresCache || []).forEach(function (p) {
            if (
                !encontrado &&
                p.nombre.toLowerCase().includes(val.toLowerCase())
            )
                encontrado = p;
        });
    }

    var wrapErr = document.getElementById("posstockProveedorError");
    if (!encontrado) {
        if (wrapErr) wrapErr.textContent = "Proveedor no encontrado: " + val;
        return;
    }
    if (wrapErr) wrapErr.textContent = "";

    var tbody = document.querySelector("#posstockTablaProveedores tbody");
    var rows = tbody ? Array.from(tbody.querySelectorAll("tr")) : [];
    if (
        rows.some(function (r) {
            return parseInt(r.dataset.id) === encontrado.id;
        })
    ) {
        if (wrapErr) wrapErr.textContent = "Ya está en la lista.";
        return;
    }

    if (tbody)
        tbody.insertAdjacentHTML(
            "beforeend",
            _htmlFilaProveedor(encontrado.id, encontrado.nombre),
        );
    if (input) input.value = "";
}

function posstockEliminarProveedor(boton) {
    boton.closest("tr").remove();
}

function posstockAplicarFiltroProveedores() {
    var tbody = document.querySelector("#posstockTablaProveedores tbody");
    window.posstockProveedoresIncluir = tbody
        ? Array.from(tbody.querySelectorAll("tr")).map(function (r) {
              return {
                  id: parseInt(r.dataset.id),
                  nombre: r.dataset.nombre || "",
              };
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
        badge.className = "label label-success";
        badge.textContent = n + " " + (n === 1 ? "proveedor" : "proveedores");
    } else {
        badge.className = "label label-default";
        badge.textContent = "Todos";
    }
}

function _htmlFilaProveedor(id, nombre) {
    return (
        '<tr data-id="' +
        id +
        '" data-nombre="' +
        nombre.replace(/"/g, "&quot;") +
        '">' +
        "<td>" +
        nombre +
        "</td>" +
        '<td><button type="button" class="btn btn-xs btn-danger" onclick="posstockEliminarProveedor(this)">' +
        '<i class="glyphicon glyphicon-remove"></i></button></td></tr>'
    );
}

window.posstockAbrirFiltroProveedores = posstockAbrirFiltroProveedores;
window.posstockAgregarProveedor = posstockAgregarProveedor;
window.posstockEliminarProveedor = posstockEliminarProveedor;
window.posstockAplicarFiltroProveedores = posstockAplicarFiltroProveedores;

// =====================================================================
//       POSSTOCK — Agrupación por proveedor
// =====================================================================

var _posstockAgrupadoPorProv = false;

// ── localStorage — preferencias UI ────────────────────────────────────────
var _POSSTOCK_LS_KEY = 'posstock_ui_v1';

function _posstockGuardarPrefs() {
    try {
        localStorage.setItem(_POSSTOCK_LS_KEY, JSON.stringify({
            agruparProv:  _posstockAgrupadoPorProv,
            badgeStates:  window._posstockBadgeStates || {}
        }));
    } catch (e) {}
}

function _posstockCargarPrefs() {
    try {
        var raw = localStorage.getItem(_POSSTOCK_LS_KEY);
        return raw ? JSON.parse(raw) : {};
    } catch (e) { return {}; }
}

function posstockToggleAgruparProveedor() {
    _posstockAgrupadoPorProv = !_posstockAgrupadoPorProv;
    var label = document.getElementById("posstockAgruparProvLabel");
    if (label) {
        label.className = _posstockAgrupadoPorProv
            ? "label label-success"
            : "label label-default";
        label.textContent = _posstockAgrupadoPorProv ? "Sí" : "No";
    }
    _posstockGuardarPrefs();
    _posstockReordenarTabla();
}

// Aplica el estado guardado de "agrupar proveedor" al label del botón (sin toggle)
function _posstockRestaurarAgruparProv() {
    var label = document.getElementById("posstockAgruparProvLabel");
    if (label) {
        label.className = _posstockAgrupadoPorProv
            ? "label label-success"
            : "label label-default";
        label.textContent = _posstockAgrupadoPorProv ? "Sí" : "No";
    }
}

/**
 * Reordena las filas del tbody de posstockTabla.
 * - Modo normal:    orden por data-orden (lexicográfico, igual que el backend)
 * - Modo proveedor: agrupa por data-prov (alfabético), dentro de cada grupo
 *                   mantiene el orden por data-orden
 */
function _posstockReordenarTabla() {
    var tbody = document.querySelector("#posstockTabla tbody");
    if (!tbody) return;

    // Eliminar cabeceras de grupo previas
    Array.from(tbody.querySelectorAll("tr[data-prov-header]")).forEach(
        function (tr) {
            tr.remove();
        },
    );

    var filas = Array.from(tbody.querySelectorAll("tr"));

    if (_posstockAgrupadoPorProv) {
        // Ordenar: primero por proveedor (asc, vacíos al final), luego por orden_clave (asc)
        filas.sort(function (a, b) {
            var pa = a.dataset.prov || "";
            var pb = b.dataset.prov || "";
            if (pa === "" && pb !== "") return 1;
            if (pa !== "" && pb === "") return -1;
            if (pa !== pb) return pa.localeCompare(pb, "es");
            var oa = a.dataset.orden || "";
            var ob = b.dataset.orden || "";
            return oa < ob ? -1 : oa > ob ? 1 : 0;
        });

        // Reinsertar filas e inyectar cabecera al inicio de cada grupo
        var provActual = null;
        var sinProvHeader = false;

        function _crearCabeceraGrupo(provKey, label, icono) {
            var nGrupo = 0,
                costeGrupo = 0;
            filas.forEach(function (f) {
                if (
                    (f.dataset.prov || "") === provKey &&
                    f.style.display !== "none"
                ) {
                    nGrupo++;
                    costeGrupo += parseFloat(f.dataset.coste || "0");
                }
            });
            var costeTexto =
                costeGrupo > 0
                    ? " &nbsp;·&nbsp; Valor est.: <strong>~" +
                      costeGrupo.toLocaleString("es-ES", {
                          maximumFractionDigits: 0,
                      }) +
                      " €</strong>"
                    : "";
            var tr = document.createElement("tr");
            tr.setAttribute("data-prov-header", provKey || "__sinprov__");
            tr.style.cssText =
                "background:#f0f4fa;border-top:2px solid #c8d4e8;" +
                (nGrupo === 0 ? "display:none;" : "");
            tr.innerHTML =
                '<td colspan="7" style="font-weight:600;padding:4px 8px;font-size:12px;">' +
                '<i class="glyphicon glyphicon-' +
                icono +
                '" style="margin-right:5px;color:#5a7ab5;"></i>' +
                label +
                ' &nbsp;<span class="label label-default">' +
                nGrupo +
                " artículo" +
                (nGrupo !== 1 ? "s" : "") +
                "</span>" +
                costeTexto +
                "</td>";
            return tr;
        }

        filas.forEach(function (fila) {
            var prov = fila.dataset.prov || "";
            if (prov !== "" && prov !== provActual) {
                provActual = prov;
                tbody.appendChild(_crearCabeceraGrupo(prov, prov, "truck"));
            } else if (prov === "" && !sinProvHeader) {
                sinProvHeader = true;
                tbody.appendChild(
                    _crearCabeceraGrupo(
                        "",
                        "Proveedor no identificado",
                        "question-sign",
                    ),
                );
            }
            tbody.appendChild(fila);
        });

        // Fila de totales al inicio y al final del agrupado
        var nTotal = 0, costeTotal = 0, nProveedores = 0;
        var provsVistos = {};
        filas.forEach(function (f) {
            if (f.style.display === "none") return;
            nTotal++;
            costeTotal += parseFloat(f.dataset.coste || "0");
            var p = f.dataset.prov || "__sinprov__";
            if (!provsVistos[p]) { provsVistos[p] = true; nProveedores++; }
        });
        if (nTotal > 0) {
            var costeTotalTexto = costeTotal > 0
                ? " &nbsp;·&nbsp; Valor est. total: <strong>~" + costeTotal.toLocaleString("es-ES", { maximumFractionDigits: 0 }) + " €</strong>"
                : "";
            var totalInnerHTML = '<td colspan="7" style="font-weight:600;padding:4px 8px;font-size:12px;text-align:right;">'
                + 'Total: <span class="label label-primary">' + nTotal + ' artículo' + (nTotal !== 1 ? 's' : '') + '</span>'
                + ' &nbsp;en&nbsp; <span class="label label-default">' + nProveedores + ' proveedor' + (nProveedores !== 1 ? 'es' : '') + '</span>'
                + costeTotalTexto
                + '</td>';
            var trTotalTop = document.createElement("tr");
            trTotalTop.setAttribute("data-prov-header", "__total__");
            trTotalTop.style.cssText = "background:#e8edf5; border-bottom:2px solid #b0bdd6;";
            trTotalTop.innerHTML = totalInnerHTML;
            tbody.insertBefore(trTotalTop, tbody.firstChild);
            var trTotalBottom = document.createElement("tr");
            trTotalBottom.setAttribute("data-prov-header", "__total__");
            trTotalBottom.style.cssText = "background:#e8edf5; border-top:2px solid #b0bdd6;";
            trTotalBottom.innerHTML = totalInnerHTML;
            tbody.appendChild(trTotalBottom);
        }
    } else {
        // Restaurar orden original por data-orden
        filas.sort(function (a, b) {
            var oa = a.dataset.orden || "";
            var ob = b.dataset.orden || "";
            return oa < ob ? -1 : oa > ob ? 1 : 0;
        });
        filas.forEach(function (fila) {
            tbody.appendChild(fila);
        });
    }
}

// Resetear el estado de agrupación cuando se recarga la tabla
function _posstockResetAgruparProv() {
    // Restaurar desde localStorage en lugar de resetear siempre a false
    var prefs = _posstockCargarPrefs();
    _posstockAgrupadoPorProv = prefs.agruparProv || false;
    _posstockRestaurarAgruparProv();
}

window.posstockToggleAgruparProveedor = posstockToggleAgruparProveedor;

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
        pulsado: "getPOSStockBatch",
        fecha_inicio_movimientos: periodo.fecha_inicio_movimientos,
        fecha_fin_movimientos: periodo.fecha_fin_movimientos,
        fecha_inicio_stock: periodo.fecha_inicio_stock,
        fecha_fin_stock: periodo.fecha_fin_stock,
        casos_incluir: _posstockGetCasosIncluir(tipoIncidencia),
        tipo_periodo: window.posstockTipoActivo || "",
        min_ventas_c5: (periodo && periodo.min_ventas_c5) || 3,
        inicial: inicial,
        pagina: 250,
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

            var acum = acumuladas.concat(resultado.filas || []);
            var actual = resultado.actual;
            var elementos = resultado.elementos;
            // pagina_efectiva: el servidor puede reducir el lote (p.ej. con C7 activo).
            // Si hay más artículos, elementos === pagina_efectiva; si es el último lote, < pagina_efectiva.
            var paginaEfectiva = resultado.pagina_efectiva || parametros.pagina;

            acum.sort(_posstockSortComparator);

            if (paginaEfectiva > 0 && elementos >= paginaEfectiva) {
                $("#posstockProgreso").text(
                    "Analizando artículos: " + actual + " procesados…",
                );
                _posstockCargaLote(actual, acum, periodo, tipoIncidencia);
            } else {
                // Último lote: fase 2 C7c/d/e si hay artículos C7a+C7b detectados.
                // C7c/d/e necesita el conjunto COMPLETO de artículos C7a+C7b; hacerlo
                // por lotes produciría falsos negativos (el artículo cruzado puede estar
                // en un lote diferente).
                var idsC7a = [], idsC7b = [];
                acum.forEach(function (f) {
                    if (f.c7_subcaso === "C7a") idsC7a.push(f.idArticulo);
                    if (f.c7_subcaso === "C7b") idsC7b.push(f.idArticulo);
                });
                if (idsC7a.length > 0 && idsC7b.length > 0) {
                    $("#posstockProgreso").text("Analizando cruces C7…");
                    _posstockResolverC7cde(idsC7a, idsC7b, acum, parametros, periodo);
                } else {
                    _posstockFinalizarTabla(acum, periodo);
                }
            }
        },
        error: function () {
            _posstockMostrarError("Error de comunicación con el servidor.");
        },
    });
}

/**
 * Fase 2 de C7: llama a resolverPOSStockC7cde con los IDs de artículos C7a+C7b
 * detectados en todos los lotes. Cuando termina, aplica las anotaciones de cruce
 * a las filas acumuladas y procede a renderizar la tabla.
 */
function _posstockResolverC7cde(idsC7a, idsC7b, acum, parametrosLote, periodo) {
    $.ajax({
        data: {
            pulsado:                  "resolverPOSStockC7cde",
            fecha_inicio_movimientos: parametrosLote.fecha_inicio_movimientos,
            fecha_fin_movimientos:    parametrosLote.fecha_fin_movimientos,
            fecha_inicio_stock:       parametrosLote.fecha_inicio_stock,
            fecha_fin_stock:          parametrosLote.fecha_fin_stock,
            tipo_periodo:             parametrosLote.tipo_periodo || "",
            familias_incluir:         parametrosLote.familias_incluir || "",
            familias_excluir:         parametrosLote.familias_excluir || "",
            proveedores_incluir:      parametrosLote.proveedores_incluir || "",
            ids_c7a:                  idsC7a.join(","),
            ids_c7b:                  idsC7b.join(","),
        },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) {
                // Si C7cde falla, renderizar igualmente sin anotaciones de cruce
                _posstockFinalizarTabla(acum, periodo);
                return;
            }
            // Aplicar anotaciones de cruce a las filas ya acumuladas
            var cruces = resultado.cruces || {};
            acum.forEach(function (f) {
                var c = cruces[f.idArticulo];
                if (!c) return;
                f.posible_cruce_con = c.posible_cruce_con;
                f.cruce_score       = c.cruce_score;
                f.cruce_nivel       = c.cruce_nivel;
                if (c.posible_causa) f.posible_causa = c.posible_causa;
            });
            _posstockFinalizarTabla(acum, periodo);
        },
        error: function () {
            // Si hay error de red, renderizar sin cruces (no bloquear al usuario)
            _posstockFinalizarTabla(acum, periodo);
        },
    });
}

/**
 * Enriquece las filas acumuladas y renderiza la tabla. Punto de entrada común
 * tanto si hay cruces C7c/d/e como si no los hay.
 */
function _posstockFinalizarTabla(acum, periodo) {
    acum.sort(_posstockSortComparator);
    _posstockEnriquecerC1aConC7b(acum);
    window._posstockUltimasFilas  = acum;
    window._posstockUltimoPeriodo = periodo;
    $("#posstockProgreso").text("Generando tabla…");
    _posstockRenderizarTabla(acum, periodo);

    $("#posstockLabelMovimientos").text(periodo.label_movimientos || "");
    $("#posstockLabelStock").text(periodo.label_stock || "");
    $("#posstockNavegacion").show();
    $("#posstockBotonesWrap").show();
}

/**
 * C7b-003: decora las filas C1a con c7b_activo/c7b_offset_estimado/c7b_tipo_articulo
 * cuando existe una fila C7b del mismo artículo en el lote acumulado.
 */
function _posstockEnriquecerC1aConC7b(filas) {
    var c7bPorArticulo = {};
    filas.forEach(function (f) {
        if (f.c7_subcaso === "C7b") {
            c7bPorArticulo[f.idArticulo] = {
                offset_estimado: f.offset_estimado,
                tipo_articulo: f.tipo_articulo || "unidad",
            };
        }
    });
    // C7a-007: enriquecer filas C2 ("Entrada con stock alto") con datos de merma acumulada
    var c7aPorArticulo = {};
    filas.forEach(function (f) {
        if (f.c7_subcaso === "C7a" || f.c7_subcaso === "C7a_posible") {
            c7aPorArticulo[f.idArticulo] = {
                delta_acumulado: f.delta_acumulado,
                tipo_articulo: f.tipo_articulo || "unidad",
            };
        }
    });
    if (Object.keys(c7aPorArticulo).length > 0) {
        filas.forEach(function (f) {
            if (
                f.tipo === "Entrada con stock alto" &&
                c7aPorArticulo[f.idArticulo]
            ) {
                f.c7a_activo = true;
                f.c7a_delta_acumulado = c7aPorArticulo[f.idArticulo].delta_acumulado;
                f.c7a_tipo_articulo   = c7aPorArticulo[f.idArticulo].tipo_articulo;
            }
        });
    }

    if (Object.keys(c7bPorArticulo).length === 0) return;
    filas.forEach(function (f) {
        if (
            f.tipo === "Inventario en negativo" &&
            c7bPorArticulo[f.idArticulo]
        ) {
            f.c7b_activo = true;
            f.c7b_offset_estimado =
                c7bPorArticulo[f.idArticulo].offset_estimado;
            f.c7b_tipo_articulo = c7bPorArticulo[f.idArticulo].tipo_articulo;
        }
    });
    // Si caso7b no estaba marcado por el usuario, eliminar sus filas del array
    // (fueron añadidas solo para poder enriquecer C1a con el badge o habilitar cruces)
    var caso7bMarcado = (function () {
        // En modo anual el usuario eligió el tipo explícitamente: conservar todas las filas.
        if (window.posstockTipoActivo === "anual") return true;
        var chk = document.getElementById("posstockChk_caso7b");
        return chk ? chk.checked : true; // si no hay checkbox dejar todas
    })();
    if (!caso7bMarcado) {
        var i = filas.length;
        while (i--) {
            if (
                filas[i].c7_subcaso === "C7b" ||
                filas[i].c7_subcaso === "C7b_posible" ||
                filas[i].c7_subcaso === "C7b_ruido_peso"
            ) {
                filas.splice(i, 1);
            }
        }
    }
    // Ídem para caso7a: si fue inyectado para habilitar cruces pero el usuario no lo marcó,
    // eliminar sus filas del array.
    var caso7aMarcado = (function () {
        if (window.posstockTipoActivo === "anual") return true;
        var chk = document.getElementById("posstockChk_caso7a");
        return chk ? chk.checked : true;
    })();
    if (!caso7aMarcado) {
        var i = filas.length;
        while (i--) {
            if (
                filas[i].c7_subcaso === "C7a" ||
                filas[i].c7_subcaso === "C7a_posible"
            ) {
                filas.splice(i, 1);
            }
        }
    }
}

/** Llama a PHP para renderizar la tabla HTML y la inyecta en #posstockTablaWrap. */
function _posstockRenderizarTabla(filas, periodo) {
    $.ajax({
        data: {
            pulsado: "renderizarTablaPosstock",
            filas_json: JSON.stringify(filas),
            fecha_fin_movimientos: periodo.fecha_fin_movimientos || "",
            fecha_inicio_stock: periodo.fecha_inicio_stock || "",
            anio: window.posstockAnioActivo || new Date().getFullYear(),
            c3b_dias_post: window.POSSTOCK_C3B_DIAS_POST || 14,
            c6b_dias_historico: window.POSSTOCK_C6B_DIAS_HISTORICO || 90,
            mostrar_tecnico: window.POSSTOCK_MOSTRAR_TECNICO ? 1 : 0,
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
            _posstockResetAgruparProv();
            if (filas.length > 0) {
                $("#posstockBtnExportar, #posstockBtnImprimir").show();
                _posstockIniciarFiltroBadges();
            } else {
                $("#posstockBtnExportar, #posstockBtnImprimir").hide();
            }
        },
        error: function () {
            _posstockMostrarError(
                "Error al renderizar la tabla de incidencias.",
            );
        },
    });
}

window.cargarDatosPosstock = cargarDatosPosstock;

// =====================================================================
//       POSSTOCK — Filtro por badges
// =====================================================================

// Mapeo badge → grupo. Los badges dinámicos se detectan por regex en _posstockBadgeGrupo.
var _POSSTOCK_BADGE_GRUPOS = {
    // C1 · Stock negativo
    'Recepción no registrada': 'C1', 'Timing recepción': 'C1',
    'Sin entradas': 'C1',            'Stock decimal': 'C1',
    // C2 · Sobrestock entrada
    'Duplicado probable': 'C2',      'Posible duplicado': 'C2',
    'Acumulación crónica': 'C2',     'Tendencia creciente': 'C2',
    'Sobrestock severo': 'C2',       'Sin ventas': 'C2',
    'Pedido prematuro': 'C2',
    // C3 · Rotación (C3a caída + C3b sin rotación previa)
    'Caída severa': 'C3',            'Rotación caída': 'C3',
    'Riesgo caducidad': 'C3',
    'Sin rotación': 'C3',            'Reposición sin rotación': 'C3',
    'Pedidos repetidos sin venta': 'C3',
    // C5 · Rotura
    'En curso': 'C5',
    'KO': 'C5',                      'CR': 'C5',
    'RK': 'C5',                      'Stock no fiable': 'C5',
    'Error de pesaje': 'C5',
    // C7b · Déficit
    'Déficit estable': 'C7b',        'Déficit posible': 'C7b',
    'Déficit histórico': 'C7b',
    // C7a · Merma tendencial
    'Merma estable': 'C7a',          'Merma posible': 'C7a',
    'Merma histórica': 'C7a',        'Alta varianza': 'C7a',
    // C7 · Evolución (compartido C7a / C7b)
    'Nuevo': 'C7ev',                 'Mejorando': 'C7ev',
    'Sin datos recientes': 'C7ev',
    // C9 · Backstaging
    'Merma confirmada': 'C9',        'Merma probable': 'C9',
    'Lote abierto': 'C9',
    'Sobreventa no compensada': 'C9','Pico de merma': 'C9',
    'Conservación OK': 'C9',         'Stock desajustado': 'C9',
    // Dist · Distribución estadística (C5 / C6)
    'Γ': 'Dist',  'N': 'Dist',  'BN': 'Dist',  'Bin': 'Dist',  'Poi': 'Dist',
    // C6 · Punto de pedido
    'Stock OK': 'C6',                'LT prov.': 'C6',
    '~stk': 'C6',
};

// Etiquetas de grupo para la barra visual
var _POSSTOCK_GRUPOS_LABEL = {
    'C1':   'C1 · Neg.',
    'C2':   'C2 · Sobrestock',
    'C3':   'C3 · Rotación',
    'C5':   'C5 · Rotura',
    'C7b':  'C7b · Déficit',
    'C7a':  'C7a · Merma',
    'C7ev': 'C7 · Evolución',
    'C9':   'C9 · Backstaging',
    'Dist': 'Distribución',
    'C6':   'C6 · Pedido',
    'Otros': 'Otros',
};
var _POSSTOCK_GRUPOS_ORDEN = ['C1','C2','C3','C5','C7b','C7a','C7ev','C9','Dist','C6','Otros'];

// Orden explícito dentro de cada grupo.
// '|' = separador visual de subgrupo (estado / severidad / calidad dato / etc.)
var _POSSTOCK_GRUPO_ORDEN_BADGES = {
    'C1':   ['Recepción no registrada', 'Timing recepción', '|', 'Sin entradas', 'Stock decimal'],
    'C2':   ['Duplicado probable', 'Posible duplicado',
             '|', 'Acumulación crónica', 'Tendencia creciente',
             '|', 'Sobrestock severo', 'Sin ventas', 'Pedido prematuro'],
    'C3':   ['Caída severa', 'Rotación caída', 'Riesgo caducidad',
             '|', 'Sin rotación', 'Reposición sin rotación', 'Pedidos repetidos sin venta'],
    'C5':   ['En curso',
             '|', 'KO', 'CR', 'RK',
             '|', 'Stock no fiable', 'Error de pesaje'],
    'C7b':  ['Déficit estable', 'Déficit posible', '|', 'Déficit histórico'],
    'C7a':  ['Merma estable', 'Merma posible', '|', 'Merma histórica', '|', 'Alta varianza'],
    'C7ev': ['Nuevo', 'Mejorando', 'Sin datos recientes'],
    'C9':   ['Merma confirmada', 'Merma probable', 'Merma posible',
             '|', 'Lote abierto', 'Sobreventa no compensada', 'Pico de merma',
             '|', 'Conservación OK', 'Stock desajustado'],
    'Dist': ['Γ', 'N', 'BN', 'Bin', 'Poi'],
    'C6':   ['Stock OK', '|', 'LT prov.', '~stk'],
};

// Mapeo data-tipo (valor exacto del HTML) → grupo del filtro.
// Se usa para asignar dinámicamente los badges ambiguos (mismo texto en varios casos).
var _POSSTOCK_TIPO_A_GRUPO = {
    'Inventario en negativo':          'C1',
    'Desajuste Puntual de Stock':      'C1',
    'Entrada con stock alto':          'C2',
    'Caída de rotación':               'C3',
    'Entrada sin rotación previa':     'C3',
    'Venta Cero (Posible Rotura Física)': 'C5',
    'Posible error de pesaje':         'C5',
    'Entrada no registrada':           'C7b',
    'Merma acumulada':                 'C7a',
    'Merma backstaging':               'C9',
    'Agotamiento Estimado':            'C6',
    'Punto de Pedido':                 'C6',
    'Stock Inactivo en Periodo':       'C6',
};

/**
 * Determina el grupo de un badge. Se llama con el mapa pre-calculado de
 * badge → grupos reales presentes en la tabla (_badgeGruposReales), de modo
 * que badges ambiguos (mismo texto en C7a y C9) se asignan al grupo correcto
 * según las filas que realmente hay en la tabla en ese momento.
 */
function _posstockBadgeGrupo(badge, badgeGruposReales) {
    // 1. Si en la tabla actual el badge solo aparece en filas de un grupo → ese grupo
    var reales = badgeGruposReales ? (badgeGruposReales[badge] || []) : [];
    if (reales.length === 1) return reales[0];

    // 2. Mapa estático para badges no ambiguos o cuando aparecen en varios grupos
    if (_POSSTOCK_BADGE_GRUPOS[badge]) return _POSSTOCK_BADGE_GRUPOS[badge];

    // 3. Patrones dinámicos
    if (/^Recuperada\b/i.test(badge))   return 'C5';
    if (/^Pedir\s*~/i.test(badge))      return 'C6';
    if (/^\d+d$/.test(badge))           return 'C6';
    return 'Otros';
}

/**
 * Badges: 3 estados por badge.
 *   0 = neutral  (btn-default) — no filtra
 *   1 = incluido (btn-success) — la fila DEBE tener al menos uno de los incluidos (OR)
 *   2 = excluido (btn-danger)  — la fila se oculta si tiene este badge (prioridad)
 * Ciclo al hacer clic: 0 → 1 → 2 → 0
 *
 * window._posstockBadgeStates = { badge: 0|1|2, '__sinbadge__': 0|1|2 }
 *
 * El estado se persiste en localStorage (clave posstock_ui_v1) y se restaura
 * al cambiar de periodo, de modo que los filtros se mantienen entre semanas/meses.
 */
function _posstockIniciarFiltroBadges() {
    var filas = document.querySelectorAll(
        "#posstockTablaWrap tbody tr[data-badges]"
    );
    if (!filas.length) return;

    // Recopilar badges únicos y, por cada badge, qué grupos (data-tipo → grupo) tiene en la tabla
    var badgesSet = {};
    var badgeGruposReales = {};  // badge → array de grupos únicos presentes en la tabla
    var haySinBadge = false;
    filas.forEach(function (tr) {
        var val  = tr.getAttribute("data-badges") || "";
        var tipo = tr.getAttribute("data-tipo")   || "";
        var grupoFila = _POSSTOCK_TIPO_A_GRUPO[tipo] || null;
        if (val === "") {
            haySinBadge = true;
        } else {
            val.split("|").forEach(function (b) {
                b = b.trim();
                if (!b) return;
                badgesSet[b] = true;
                if (grupoFila) {
                    if (!badgeGruposReales[b]) badgeGruposReales[b] = {};
                    badgeGruposReales[b][grupoFila] = true;
                }
            });
        }
    });
    // Convertir a arrays
    Object.keys(badgeGruposReales).forEach(function (b) {
        badgeGruposReales[b] = Object.keys(badgeGruposReales[b]);
    });

    var badges = Object.keys(badgesSet).sort();
    if (!badges.length && !haySinBadge) return;

    // Restaurar estados guardados o inicializar a neutral
    var prefs = _posstockCargarPrefs();
    var savedStates = prefs.badgeStates || {};
    window._posstockBadgeStates = {};
    badges.forEach(function (b) {
        window._posstockBadgeStates[b] = savedStates[b] || 0;
    });
    if (haySinBadge) {
        window._posstockBadgeStates['__sinbadge__'] = savedStates['__sinbadge__'] || 0;
    }

    // ── Construir barra ──────────────────────────────────────────────────
    // Override del margen global de .btn que añade el template del proyecto
    if (!document.getElementById('posstockFiltroBadgesStyle')) {
        var st = document.createElement('style');
        st.id = 'posstockFiltroBadgesStyle';
        st.textContent = '#posstockFiltroBadges .btn { margin: 0 !important; }';
        document.head.appendChild(st);
    }
    var barra = document.createElement("div");
    barra.id = "posstockFiltroBadges";
    barra.style.cssText = "margin-bottom:10px; border:1px solid #ddd; border-radius:4px; background:#f9f9f9; padding:6px 8px;";

    // Cabecera: etiqueta + botón reset en la misma línea
    var cabecera = document.createElement("div");
    cabecera.style.cssText = "display:flex; align-items:center; gap:6px; margin-bottom:6px;";
    var lblFiltro = document.createElement("span");
    lblFiltro.className = "text-muted small";
    lblFiltro.style.fontWeight = "bold";
    lblFiltro.textContent = "Filtrar por badge:";
    cabecera.appendChild(lblFiltro);
    var btnReset = document.createElement("button");
    btnReset.type = "button";
    btnReset.className = "btn btn-xs btn-default";
    btnReset.setAttribute("data-badge-filtro", "__todos__");
    btnReset.title = "Quitar todos los filtros de badge";
    btnReset.textContent = "Todos";
    btnReset.onclick = function () {
        Object.keys(window._posstockBadgeStates).forEach(function (b) {
            window._posstockBadgeStates[b] = 0;
        });
        _aplicarFiltroBadges(filas);
        _actualizarBotonesEstado(barra);
        _posstockGuardarPrefs();
    };
    cabecera.appendChild(btnReset);
    barra.appendChild(cabecera);

    // Agrupar badges por caso — se pasa badgeGruposReales para resolver ambigüedades
    var porGrupo = {};
    badges.forEach(function (b) {
        var g = _posstockBadgeGrupo(b, badgeGruposReales);
        if (!porGrupo[g]) porGrupo[g] = [];
        porGrupo[g].push(b);
    });

    // Fila de grupos: cada grupo es un bloque inline-flex con etiqueta + botones
    var filaGrupos = document.createElement("div");
    filaGrupos.style.cssText = "display:flex; flex-wrap:wrap; gap:6px; align-items:flex-start;";

    _POSSTOCK_GRUPOS_ORDEN.forEach(function (grupo) {
        if (!porGrupo[grupo]) return;
        var bloque = document.createElement("div");
        bloque.style.cssText = "display:inline-flex; align-items:stretch; background:#fff; border:1px solid #e0e0e0; border-radius:3px; overflow:hidden;";
        var etq = document.createElement("span");
        etq.className = "text-muted";
        etq.style.cssText = "display:flex; align-items:center; font-size:10px; font-weight:bold; white-space:nowrap; padding:0 6px 0 7px; border-right:1px solid #e0e0e0; background:#f5f5f5;";
        etq.textContent = _POSSTOCK_GRUPOS_LABEL[grupo] || grupo;
        var cuerpo = document.createElement("div");
        cuerpo.style.cssText = "display:flex; align-items:center; flex-wrap:wrap; gap:3px; padding:3px 7px;";
        bloque.appendChild(etq);

        // Renderizar badges en el orden explícito, insertando separadores de subgrupo ('|')
        var ordenExplicito = _POSSTOCK_GRUPO_ORDEN_BADGES[grupo] || [];
        var yaRenderizados = {};
        ordenExplicito.forEach(function (b) {
            if (b === '|') {
                var sep = document.createElement("span");
                sep.style.cssText = "display:inline-block; width:1px; height:14px; background:#ddd; margin:0 2px; align-self:center; flex-shrink:0;";
                cuerpo.appendChild(sep);
                return;
            }
            if (porGrupo[grupo].indexOf(b) !== -1) {
                cuerpo.appendChild(_crearBtnBadge(b, filas, barra));
                yaRenderizados[b] = true;
            }
        });
        // Badges dinámicos no listados explícitamente (ej. "Recuperada 01/03", "Pedir ~5 kg")
        porGrupo[grupo].forEach(function (b) {
            if (!yaRenderizados[b]) {
                cuerpo.appendChild(_crearBtnBadge(b, filas, barra));
            }
        });
        bloque.appendChild(cuerpo);
        filaGrupos.appendChild(bloque);
    });

    // Bloque "Sin badge"
    if (haySinBadge) {
        var bloqueSin = document.createElement("div");
        bloqueSin.style.cssText = "display:inline-flex; align-items:center; gap:3px; background:#fff; border:1px solid #e0e0e0; border-radius:3px; padding:3px 7px;";
        bloqueSin.appendChild(_crearBtnBadge('__sinbadge__', filas, barra));
        filaGrupos.appendChild(bloqueSin);
    }

    barra.appendChild(filaGrupos);

    // Insertar antes de la tabla
    var wrap = document.getElementById("posstockTablaWrap");
    var tabla = wrap ? wrap.querySelector("table") : null;
    if (tabla) wrap.insertBefore(barra, tabla);

    // Aplicar estado restaurado (también inicializa window._posstockFilasVisibles)
    _aplicarFiltroBadges(filas);
    _actualizarBotonesEstado(barra);
}

function _crearBtnBadge(badge, filas, barra) {
    var btn = document.createElement("button");
    btn.type = "button";
    btn.className = "btn btn-xs btn-default";
    btn.setAttribute("data-badge-filtro", badge);
    btn.textContent = badge === '__sinbadge__' ? 'Sin badge' : badge;
    btn.title = "Clic: neutro → incluir (verde) → excluir (rojo) → neutro";
    btn.onclick = function () {
        var cur = window._posstockBadgeStates[badge] || 0;
        window._posstockBadgeStates[badge] = (cur + 1) % 3;
        _aplicarFiltroBadges(filas);
        _actualizarBotonesEstado(barra);
        _posstockGuardarPrefs();
    };
    return btn;
}

/**
 * Aplica show/hide a las filas según el estado de 3 niveles de cada badge.
 * Lógica: exclusiones tienen prioridad; inclusiones son OR entre sí.
 * Actualiza window._posstockFilasVisibles para CSV/PDF filtrado.
 */
function _aplicarFiltroBadges(filas) {
    var states = window._posstockBadgeStates || {};
    var incluidosBadges = Object.keys(states).filter(function (b) {
        return b !== '__sinbadge__' && states[b] === 1;
    });
    var excluidosBadges = Object.keys(states).filter(function (b) {
        return b !== '__sinbadge__' && states[b] === 2;
    });
    var sinBadgeState = states['__sinbadge__'] || 0;
    var hayInclusiones = incluidosBadges.length > 0 || sinBadgeState === 1;

    filas.forEach(function (tr) {
        if (tr.hasAttribute("data-prov-header")) return;
        var val = tr.getAttribute("data-badges") || "";
        var rowBadges = val === ''
            ? []
            : val.split("|").map(function (b) { return b.trim(); });
        var esSinBadge = val === '';
        var mostrar = true;

        // 1. Exclusiones (prioridad máxima)
        if (esSinBadge) {
            if (sinBadgeState === 2) mostrar = false;
        } else {
            if (excluidosBadges.some(function (b) { return rowBadges.indexOf(b) !== -1; })) {
                mostrar = false;
            }
        }

        // 2. Inclusiones OR (solo si hay al menos un incluido activo)
        if (mostrar && hayInclusiones) {
            if (esSinBadge) {
                mostrar = sinBadgeState === 1;
            } else {
                mostrar = incluidosBadges.some(function (b) { return rowBadges.indexOf(b) !== -1; });
            }
        }

        tr.style.display = mostrar ? "" : "none";
    });

    // Actualizar caché de filas visibles para CSV/PDF
    window._posstockFilasVisibles = Array.from(filas).filter(function (tr) {
        return tr.style.display !== "none" && !tr.hasAttribute("data-prov-header");
    });
}

/** Actualiza el estado visual de los botones de la barra según _posstockBadgeStates. */
function _actualizarBotonesEstado(barra) {
    var states = window._posstockBadgeStates || {};
    var hayFiltro = Object.keys(states).some(function (b) { return states[b] !== 0; });
    barra.querySelectorAll("[data-badge-filtro]").forEach(function (btn) {
        var b = btn.getAttribute("data-badge-filtro");
        if (b === "__todos__") {
            btn.className = 'btn btn-xs ' + (hayFiltro ? 'btn-warning' : 'btn-default');
            return;
        }
        var estado = states[b] || 0;
        btn.className = 'btn btn-xs ' + (
            estado === 1 ? 'btn-success active' :
            estado === 2 ? 'btn-danger  active' :
            'btn-default'
        );
    });
}


// =====================================================================
//       POSSTOCK — Exportar / Imprimir
// =====================================================================

function _posstockGetArticulosFiltrados() {
    // Solo aplicar filtro si hay algún badge en estado no-neutral
    var states = window._posstockBadgeStates || {};
    var hayFiltro = Object.keys(states).some(function (b) { return states[b] !== 0; });
    if (!hayFiltro) return '';

    var filas = window._posstockFilasVisibles;
    if (!filas || !filas.length) return '';

    var ids = filas.map(function (tr) { return tr.getAttribute('data-idarticulo') || ''; })
                   .filter(Boolean);
    return ids.join(',');
}

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
            .map(function (f) { return f.id; })
            .join(","),
        familias_excluir: (window.posstockFamiliasExcluir || [])
            .map(function (f) { return f.id; })
            .join(","),
        proveedores_incluir: (window.posstockProveedoresIncluir || [])
            .map(function (p) { return p.id; })
            .join(","),
        proveedor_todos_productos: window.posstockProveedorTodosProductos ? "1" : "0",
        articulos_filtrados: _posstockGetArticulosFiltrados(),
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
            articulos_filtrados: _posstockGetArticulosFiltrados(),
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
    document
        .querySelectorAll("#posstockFilaCasos input[type=checkbox]")
        .forEach(function (chk) {
            if (chk.checked) seleccionados.push(chk.value);
        });
    // Si no hay checkboxes visibles (modo anual) devolver cadena vacía = todos
    if (seleccionados.length === 0) return "";
    // C7b-003: si caso1 está seleccionado, incluir siempre caso7b para poder
    // enriquecer las filas C1a con el badge "Recepción no registrada", aunque
    // el usuario no haya marcado caso7b. Las filas C7b sobrantes se filtran en
    // _posstockEnriquecerC1aConC7b antes de renderizar.
    if (
        seleccionados.indexOf("caso1") !== -1 &&
        seleccionados.indexOf("caso7b") === -1
    ) {
        seleccionados.push("caso7b");
    }
    // C7c/d/e solo se resuelven cuando el usuario selecciona explícitamente
    // AMBOS subcasos (C7a + C7b). No se inyecta C7a automáticamente:
    // ejecutar la cascada estadística de C7a solo para habilitar cruces es
    // un coste desproporcionado cuando el usuario solo quiere ver C7b.
    return seleccionados.join(",");
}

/** Relanza la consulta con los casos actualmente seleccionados. */
function posstockRecargarPorCasos() {
    if (!window.posstockPeriodoActivo) return;
    cargarDatosPosstock(
        window.posstockPeriodoActivo,
        window.posstockTipoIncidenciaActivo || "",
    );
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
    var sel = document.getElementById("posstockNumero");

    var prevNumero = mantenerNumero && sel.value ? sel.value : null;

    sel.innerHTML = "";
    sel.disabled = true;
    document.getElementById("posstockBtnGenerar").disabled = true;
    document.getElementById("posstockAvisoVentana").style.display = "none";

    // Ocultar barra al cambiar tipo
    var barra = document.getElementById("posstockBarraBotones");
    if (barra) barra.style.display = "none";
    var wrapBotones = document.getElementById("posstockBotonesPeriodo");
    if (wrapBotones) wrapBotones.innerHTML = "";

    // Cambiar label según contexto
    var labelEl = document.getElementById("posstockLabelNumero");
    if (labelEl)
        labelEl.textContent = tipo === "anual" ? "Tipo de análisis" : "Periodo";

    // Ocultar/mostrar filtro de casos
    var filaCasos = document.getElementById("posstockFilaCasos");
    if (filaCasos) filaCasos.style.display = tipo === "anual" ? "none" : "";

    if (!tipo || !anio) return;

    $.ajax({
        data: {
            pulsado: "getOpcionesPeriodo",
            tipo: tipo,
            anio: anio,
            ventana_dias: window.POSSTOCK_VENTANA_DIAS || 0,
            incluir_stock_inactivo: window.POSSTOCK_INCLUIR_STOCK_INACTIVO
                ? "1"
                : "0",
        },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) return;
            sel.innerHTML =
                '<option value="">— seleccionar —</option>' +
                (resultado.html || "");
            sel.disabled = false;

            // Intentar restaurar número previo
            if (prevNumero) {
                for (var i = 0; i < sel.options.length; i++) {
                    if (
                        String(sel.options[i].value) === String(prevNumero) &&
                        !sel.options[i].disabled
                    ) {
                        sel.value = prevNumero;
                        document.getElementById("posstockBtnGenerar").disabled =
                            false;
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

            var tipoInc = tipo === "anual" ? numero : "";
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
                    if (tipoInc !== "caso6b") return;
                    _enVentana = true;
                }
            }
            if (!_enVentana)
                document.getElementById("posstockAvisoVentana").style.display =
                    "none";
            window._posstockAnualEnVentana = _enVentana;

            window.posstockPeriodoActivo = periodo;
            window.posstockTipoActivo = tipo;
            window.posstockAnioActivo = parseInt(anio, 10);
            window.posstockTipoIncidenciaActivo = tipoInc;

            cargarDatosPosstock(periodo, tipoInc);
            _posstockCargarBarraPeriodos(
                tipo,
                tipo === "anual" ? numero : parseInt(numero, 10),
                _enVentana,
            );
        },
        error: function () {
            _posstockMostrarError(
                "Error de comunicación al calcular el periodo.",
            );
        },
    });
}

/** Solicita a PHP el HTML de la barra de botones y lo inyecta. */
function _posstockCargarBarraPeriodos(tipo, numeroActivo, enVentana) {
    $.ajax({
        data: {
            pulsado: "getVistaBarraPeriodos",
            tipo: tipo,
            anio: window.posstockAnioActivo || new Date().getFullYear(),
            numero_activo: numeroActivo,
            ventana_dias: window.POSSTOCK_VENTANA_DIAS || 0,
            en_ventana: enVentana ? "1" : "0",
            incluir_stock_inactivo: window.POSSTOCK_INCLUIR_STOCK_INACTIVO
                ? "1"
                : "0",
        },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) return;
            var wrap = document.getElementById("posstockBotonesPeriodo");
            if (wrap) {
                wrap.innerHTML = resultado.html || "";
                document.getElementById("posstockBarraBotones").style.display =
                    "";
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

// Habilitar botón Generar cuando se elige número de periodo
document.addEventListener("DOMContentLoaded", function () {
    document
        .getElementById("posstockNumero")
        .addEventListener("change", function () {
            document.getElementById("posstockBtnGenerar").disabled =
                this.value === "";
        });
});

window.posstockActualizarNumero = posstockActualizarNumero;
window.posstockGenerar = posstockGenerar;
window.posstockNavegar = posstockNavegar;
