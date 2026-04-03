/**
 * PosstockFiltros.js — Filtros de familias y proveedores POSStock
 *
 * Responsabilidades:
 *   - Abrir modales de filtro de familias/proveedores (AJAX)
 *   - Agregar/eliminar filas en las tablas del modal
 *   - Aplicar filtros a window.posstockFamiliasIncluir/Excluir y window.posstockProveedoresIncluir
 *   - Actualizar badges de estado del filtro
 *   - _htmlFilaFamilia / _htmlFilaProveedor: generadores HTML puros (testables)
 */

import { _posstockMostrarError } from "./PosstockLoader.js";

// ── Filtro de familias ────────────────────────────────────────────────────────

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

/** Genera &lt;tr&gt; mínima para una familia (usada al agregar desde el modal). */
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

// ── Filtro de proveedores ─────────────────────────────────────────────────────

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

export {
    posstockAbrirFiltroFamilias,
    posstockAgregarFamilia,
    posstockEliminarFamilia,
    posstockAplicarFiltroFamilias,
    _leerTablaFamilias,
    _posstockActualizarBadgeFiltro,
    _htmlFilaFamilia,
    posstockAbrirFiltroProveedores,
    posstockAgregarProveedor,
    posstockEliminarProveedor,
    posstockAplicarFiltroProveedores,
    _posstockActualizarBadgeProveedores,
    _htmlFilaProveedor,
};
