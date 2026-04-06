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

function _posstockCerrarModal() {
    if (typeof window.cerrarModal === "function") {
        window.cerrarModal();
        return;
    }
    if (typeof window.cerrarPopUp === "function") {
        window.cerrarPopUp();
        return;
    }
    if (window.$) {
        window.$("#ventanaModal").modal("hide");
    }
}

// ── Filtro de familias ────────────────────────────────────────────────────────

window.posstockFamiliasIncluir = [];
window.posstockFamiliasExcluir = [];
let _posstockFamiliasCache = null;

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
            let resultado = JSON.parse(response);
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
    let input = document.getElementById("posstockBuscar_" + lista);
    let val = input ? input.value.trim() : "";
    let encontrado = null;

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

    let wrapErr = document.getElementById("posstockFamiliaError_" + lista);
    if (!encontrado) {
        if (wrapErr) wrapErr.textContent = "Familia no encontrada: " + val;
        return;
    }
    if (wrapErr) wrapErr.textContent = "";

    const tabla = document.querySelector("#posstockTabla_" + lista + " tbody");
    let filas = tabla ? Array.from(tabla.querySelectorAll("tr")) : [];
    if (
        filas.some(function (r) {
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
    _posstockCerrarModal();
}

function _leerTablaFamilias(lista) {
    let filas = document.querySelectorAll(
        "#posstockTabla_" + lista + " tbody tr",
    );
    return Array.from(filas).map(function (r) {
        return { id: parseInt(r.dataset.id), nombre: r.dataset.nombre || "" };
    });
}

function _posstockActualizarBadgeFiltro() {
    let badge = document.getElementById("posstockFiltroLabel");
    if (!badge) return;
    const nInc = (window.posstockFamiliasIncluir || []).length;
    const nExc = (window.posstockFamiliasExcluir || []).length;
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
let _posstockProveedoresCache = null;

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
            const resultado = JSON.parse(response);
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
    const input = document.getElementById("posstockBuscarProveedor");
    const val = input ? input.value.trim() : "";
    let encontrado = null;

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

    const wrapErr = document.getElementById("posstockProveedorError");
    if (!encontrado) {
        if (wrapErr) wrapErr.textContent = "Proveedor no encontrado: " + val;
        return;
    }
    if (wrapErr) wrapErr.textContent = "";

    let tbody = document.querySelector("#posstockTablaProveedores tbody");
    const filas = tbody ? Array.from(tbody.querySelectorAll("tr")) : [];
    if (
        filas.some(function (r) {
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
    const tbody = document.querySelector("#posstockTablaProveedores tbody");
    window.posstockProveedoresIncluir = tbody
        ? Array.from(tbody.querySelectorAll("tr")).map(function (r) {
              return {
                  id: parseInt(r.dataset.id),
                  nombre: r.dataset.nombre || "",
              };
          })
        : [];
    const chk = document.getElementById("posstockChkTodosProductos");
    window.posstockProveedorTodosProductos = chk ? chk.checked : false;
    _posstockActualizarBadgeProveedores();
    _posstockCerrarModal();
}

function _posstockActualizarBadgeProveedores() {
    const badge = document.getElementById("posstockFiltroProveedorLabel");
    if (!badge) return;
    const n = (window.posstockProveedoresIncluir || []).length;
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
