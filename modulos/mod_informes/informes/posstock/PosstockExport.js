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

window.exportarPOSStockCSV = exportarPOSStockCSV;
window.imprimirPOSStockPDF = imprimirPOSStockPDF;

export {
    _posstockGetArticulosFiltrados,
    exportarPOSStockCSV,
    imprimirPOSStockPDF,
};
