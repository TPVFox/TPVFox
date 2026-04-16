/**
 * PosstockExport.js — Exportar CSV e imprimir PDF
 *
 * Responsabilidades:
 *   - exportarPOSStockCSV: form POST directo para descarga
 *   - imprimirPOSStockPDF: AJAX → abrir ventana con PDF generado
 *   - _posstockGetArticulosFiltrados: devuelve IDs de filas visibles con filtro badge activo
 */

import { _posstockMostrarError, _posstockGetCasosIncluir } from "./PosstockLoader.js";

function _posstockGetArticulosFiltrados() {
    var states = window._posstockBadgeStates || {};
    var hayFiltro = Object.keys(states).some(function (b) {
        return states[b] !== 0;
    });
    if (!hayFiltro) return "";

    var filas = window._posstockFilasVisibles;
    if (!filas || !filas.length) return "";

    var ids = filas
        .map(function (tr) {
            return tr.getAttribute("data-idarticulo") || "";
        })
        .filter(Boolean);
    return ids.join(",");
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

// ── Generar hoja de conteo físico desde POSStock ──────────────────────────────
//
// Envía los IDs de los artículos visibles (o todos si no hay filtro de badge)
// al generador de PDF de conteo. Abre un modal ligero para elegir modo (normal/ciega).

function imprimirConteoPosstockPDF(modo) {
    var filas = window._posstockFilasVisibles;
    if (!filas || !filas.length) {
        _posstockMostrarError("No hay artículos visibles para generar la hoja de conteo.");
        return;
    }

    var ids = filas
        .map(function (tr) { return tr.getAttribute("data-idarticulo") || ""; })
        .filter(Boolean)
        // Deduplicar: un artículo puede aparecer en varios casos
        .filter(function (id, idx, arr) { return arr.indexOf(id) === idx; });

    if (!ids.length) {
        _posstockMostrarError("No se pudieron obtener los IDs de los artículos.");
        return;
    }

    var btn = document.getElementById("posstockBtnConteo");
    if (btn) { btn.disabled = true; btn.textContent = "Generando…"; }

    $.ajax({
        url:  "tareas.php",
        type: "POST",
        data: {
            pulsado:       "imprimirInventarioConteoPDF",
            ids_articulos: ids.join(","),
            solo_activos:  "0",   // los IDs ya vienen de POSStock, no filtrar por estado
            modo:          modo || "normal",
        },
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) {
                _posstockMostrarError("Error al generar hoja de conteo: " + resultado.error);
            } else {
                window.open(resultado.url, "_blank");
            }
        },
        error: function () {
            _posstockMostrarError("Error de comunicación al generar la hoja de conteo.");
        },
        complete: function () {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="glyphicon glyphicon-list-alt"></i> Conteo';
            }
        },
    });
}

// Muestra un mini-dropdown para elegir modo antes de generar
function _posstockAbrirModoConteo(event) {
    event.stopPropagation();
    var existing = document.getElementById("_posstockModoConteoMenu");
    if (existing) { existing.remove(); return; }

    var btn  = document.getElementById("posstockBtnConteo");
    var rect = btn.getBoundingClientRect();

    var menu = document.createElement("div");
    menu.id  = "_posstockModoConteoMenu";
    menu.style.cssText = "position:fixed;z-index:9999;background:#fff;border:1px solid #ccc;"
        + "border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.2);min-width:160px;"
        + "top:" + (rect.bottom + 4) + "px;left:" + rect.left + "px;";
    menu.innerHTML =
        '<a style="display:block;padding:8px 14px;cursor:pointer;font-size:13px;" '
        + 'onclick="imprimirConteoPosstockPDF(\'normal\');document.getElementById(\'_posstockModoConteoMenu\').remove();">'
        + 'Normal (con stock)</a>'
        + '<a style="display:block;padding:8px 14px;cursor:pointer;font-size:13px;border-top:1px solid #eee;" '
        + 'onclick="imprimirConteoPosstockPDF(\'ciega\');document.getElementById(\'_posstockModoConteoMenu\').remove();">'
        + 'Ciega (sin stock para operario)</a>'
        + '<a style="display:block;padding:8px 14px;cursor:pointer;font-size:13px;border-top:2px solid #337ab7;color:#337ab7;" '
        + 'onclick="imprimirHojaRapidaPosstockPDF();document.getElementById(\'_posstockModoConteoMenu\').remove();">'
        + '<i class="glyphicon glyphicon-th-list"></i> Hoja r\u00e1pida (l\u00ednea a l\u00ednea)</a>';

    document.body.appendChild(menu);
    document.addEventListener("click", function _cerrar() {
        var m = document.getElementById("_posstockModoConteoMenu");
        if (m) m.remove();
        document.removeEventListener("click", _cerrar);
    });
}

// ── Hoja rápida de conteo desde POSStock ─────────────────────────────────────

function imprimirHojaRapidaPosstockPDF() {
    var filas = window._posstockFilasVisibles;
    if (!filas || !filas.length) {
        _posstockMostrarError("No hay artículos visibles para generar la hoja rápida.");
        return;
    }

    var ids = filas
        .map(function (tr) { return tr.getAttribute("data-idarticulo") || ""; })
        .filter(Boolean)
        .filter(function (id, idx, arr) { return arr.indexOf(id) === idx; });

    if (!ids.length) {
        _posstockMostrarError("No se pudieron obtener los IDs de los artículos.");
        return;
    }

    var btn = document.getElementById("posstockBtnConteo");
    if (btn) { btn.disabled = true; btn.textContent = "Generando…"; }

    $.ajax({
        url:  "tareas.php",
        type: "POST",
        data: {
            pulsado:       "imprimirHojaRapidaConteoPDF",
            ids_articulos: ids.join(","),
            solo_activos:  "0",
            mostrar_stock: "1",
        },
        success: function (response) {
            var resultado = JSON.parse(response);
            if (resultado.error) {
                _posstockMostrarError("Error al generar hoja rápida: " + resultado.error);
            } else {
                window.open(resultado.url, "_blank");
            }
        },
        error: function () {
            _posstockMostrarError("Error de comunicación al generar la hoja rápida.");
        },
        complete: function () {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="glyphicon glyphicon-list-alt"></i> Conteo';
            }
        },
    });
}

window.exportarPOSStockCSV              = exportarPOSStockCSV;
window.imprimirPOSStockPDF              = imprimirPOSStockPDF;
window.imprimirConteoPosstockPDF        = imprimirConteoPosstockPDF;
window._posstockAbrirModoConteo         = _posstockAbrirModoConteo;
window.imprimirHojaRapidaPosstockPDF    = imprimirHojaRapidaPosstockPDF;

export {
    _posstockGetArticulosFiltrados,
    exportarPOSStockCSV,
    imprimirPOSStockPDF,
    imprimirConteoPosstockPDF,
    imprimirHojaRapidaPosstockPDF,
};
