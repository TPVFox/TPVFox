/**
 * ListaInformesUI.js — Informes clásicos (no POSStock)
 *
 * Responsabilidades:
 *   - Selección múltiple de familias para la opción 4
 *   - Catálogo de familias (modal)
 *   - metodoClick: validación pre-apertura de informe
 *   - AbrirModalLoading: disparo AJAX + apertura ventana informe
 */

import * as JSTpv from "./../../../../lib/js/tpvfox.js";

// ── Selección múltiple de familias para opción 4 ──────────────────────────────

/** IDs y nombres de familias seleccionadas: { id: nombre } */
const _familiasSel = {};

/** Informes que soportan la opción 4 de selección de familias */
const _informesFamilias = [2, 4, 6];

/** Muestra u oculta el panel de selección según la opción elegida */
function _onOpcionChange(informeId) {
    const opcion = document.getElementById("opcion" + informeId).value;
    const panel = document.getElementById("panelFamilias" + informeId);
    if (panel) panel.style.display = opcion == 4 ? "" : "none";
}

/** Abre el modal con el catálogo de familias */
function abrirCatalogoFamilias() {
    $.ajax({
        data: { pulsado: "getCatalogoFamilias" },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var res = JSON.parse(response);
            abrirModal("Seleccionar familias", res.html);
        },
        error: function (req) {
            console.log(req);
        },
    });
}

/** Añade una familia al panel. Llamada desde el modal del catálogo. */
function agregarFamiliaInforme(id, nombre) {
    if (_familiasSel[id]) return; // ya existe
    _familiasSel[id] = nombre;
    _renderTagsFamilias();
}

/** Elimina una familia del panel */
function _eliminarFamiliaInforme(id) {
    delete _familiasSel[id];
    _renderTagsFamilias();
}

/** Renderiza los tags de familias seleccionadas en todos los paneles activos */
function _renderTagsFamilias() {
    const ids = Object.keys(_familiasSel);
    const inputVal = ids.join(",");

    _informesFamilias.forEach(function (informeId) {
        const contenedor = document.getElementById("tagsFamilias" + informeId);
        const hidden = document.getElementById("familiasSel" + informeId);
        if (!contenedor) return;

        if (hidden) hidden.value = inputVal;

        if (ids.length === 0) {
            contenedor.innerHTML =
                '<span class="text-muted">Ninguna familia seleccionada</span>';
            return;
        }

        let html = "";
        ids.forEach(function (id) {
            html +=
                '<span class="label label-info" style="margin:2px 3px 2px 0; display:inline-block;">' +
                _familiasSel[id] +
                ' <a href="#" onclick="event.preventDefault(); _eliminarFamiliaInforme(' +
                id +
                ')" style="color:#fff;">' +
                "&times;</a></span>";
        });
        contenedor.innerHTML = html;
    });
}

/** Versión local de colapsarTodo para el modal del catálogo */
function colapsarTodoCatalogo() {
    var filas = document.querySelectorAll(
        "#catalogoFamiliasModal tr[data-ruta]",
    );
    filas.forEach(function (f) {
        var ruta = f.dataset.ruta;
        if (ruta && ruta.includes(".")) {
            f.style.display = "none";
        }
    });
}

function toggleHijosDirectosCat(rutaPadre, elemento) {
    var filas = document.querySelectorAll(
        "#catalogoFamiliasModal tr[data-ruta]",
    );
    var abiertos = false;
    var hijosDirectos = [];

    filas.forEach(function (f) {
        var ruta = f.dataset.ruta || "";
        if (
            ruta.startsWith(rutaPadre + ".") &&
            ruta.split(".").length === rutaPadre.split(".").length + 1
        ) {
            hijosDirectos.push(f);
            if (f.style.display !== "none") abiertos = true;
        }
    });

    var descendientes = [];
    filas.forEach(function (f) {
        var ruta = f.dataset.ruta || "";
        if (ruta.startsWith(rutaPadre + ".") && ruta !== rutaPadre) {
            descendientes.push(f);
        }
    });

    if (abiertos) {
        descendientes.forEach(function (f) {
            f.style.display = "none";
        });
        if (elemento) elemento.textContent = "▶";
    } else {
        hijosDirectos.forEach(function (f) {
            f.style.display = "";
        });
        if (elemento) elemento.textContent = "▼";
    }
}

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

    // Opción 4: validar que hay al menos una familia seleccionada
    let familias = "";
    if (opcion == 4) {
        const hidden = document.getElementById("familiasSel" + checkID[0]);
        familias = hidden ? hidden.value : "";
        if (!familias) {
            alert(
                "Selecciona al menos una familia antes de ejecutar el informe.",
            );
            return;
        }
    }

    AbrirModalLoading(fecha_inicial, fecha_final, opcion, familias);
}

function AbrirModalLoading(fecha_inicial, fecha_final, opcion, familias) {
    familias = familias || "";
    $.ajax({
        data: { pulsado: "obtenerLoading" },
        url: "tareas.php",
        type: "post",
        success: function (response) {
            var resultado = JSON.parse(response);
            abrirModal("Procesando", resultado.html);
            setTimeout(function () {
                var url =
                    "./informes.php?id=" +
                    checkID[0] +
                    "&Finicio=" +
                    fecha_inicial +
                    "&Ffinal=" +
                    fecha_final +
                    "&opcion=" +
                    opcion;
                if (familias)
                    url += "&familias=" + encodeURIComponent(familias);
                window.open(url, "_blank");
            }, 5000);
        },
        error: function (request) {
            console.log(request);
        },
    });
}

window.metodoClick = metodoClick;
window.abrirCatalogoFamilias = abrirCatalogoFamilias;
window.agregarFamiliaInforme = agregarFamiliaInforme;
window._eliminarFamiliaInforme = _eliminarFamiliaInforme;
window.colapsarTodoCatalogo = colapsarTodoCatalogo;
window.toggleHijosDirectosCat = toggleHijosDirectosCat;
window._onOpcionChange = _onOpcionChange;
window.AbrirModalLoading = AbrirModalLoading;

export {
    _familiasSel,
    _informesFamilias,
    _onOpcionChange,
    abrirCatalogoFamilias,
    agregarFamiliaInforme,
    _eliminarFamiliaInforme,
    _renderTagsFamilias,
    colapsarTodoCatalogo,
    toggleHijosDirectosCat,
    metodoClick,
    AbrirModalLoading,
};
