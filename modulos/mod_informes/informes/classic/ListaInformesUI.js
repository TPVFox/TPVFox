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
const _familiasSelCF = {};
let _catalogoTarget = "legacy";

/** Informes que soportan la opción 4 de selección de familias */
const _informesFamilias = [2, 4, 6];

/** Muestra u oculta el panel de selección según la opción elegida */
function _onOpcionChange(informeId) {
    const opcion = document.getElementById("opcion" + informeId).value;
    const panel = document.getElementById("panelFamilias" + informeId);
    if (panel) panel.style.display = opcion == 4 ? "" : "none";
}

/** Abre el modal con el catálogo de familias */
function abrirCatalogoFamilias(target = "legacy") {
    _catalogoTarget = target;
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
    if (_catalogoTarget === "cf") {
        if (_familiasSelCF[id]) return;
        _familiasSelCF[id] = nombre;
        _renderTagsFamiliasCF();
        return;
    }

    if (_familiasSel[id]) return; // ya existe
    _familiasSel[id] = nombre;
    _renderTagsFamilias();
}

/** Elimina una familia del panel */
function _eliminarFamiliaInforme(id) {
    delete _familiasSel[id];
    _renderTagsFamilias();
}

function _eliminarFamiliaCosteFluctuacion(id) {
    delete _familiasSelCF[id];
    _renderTagsFamiliasCF();
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

function _renderTagsFamiliasCF() {
    const ids = Object.keys(_familiasSelCF);
    const hidden = document.getElementById("cfFamiliasSel");
    const contenedor = document.getElementById("cfTagsFamilias");
    if (!contenedor) return;

    if (hidden) hidden.value = ids.join(",");

    if (ids.length === 0) {
        contenedor.innerHTML =
            '<span class="text-muted">Sin filtro de familias</span>';
        return;
    }

    let html = "";
    ids.forEach(function (id) {
        html +=
            '<span class="label label-primary" style="margin:2px 3px 2px 0; display:inline-block;">' +
            _familiasSelCF[id] +
            ' <a href="#" onclick="event.preventDefault(); _eliminarFamiliaCosteFluctuacion(' +
            id +
            ')" style="color:#fff;">' +
            "&times;</a></span>";
    });
    contenedor.innerHTML = html;
}

function abrirCatalogoFamiliasCosteFluctuacion() {
    abrirCatalogoFamilias("cf");
}

function limpiarFamiliasCosteFluctuacion() {
    Object.keys(_familiasSelCF).forEach(function (id) {
        delete _familiasSelCF[id];
    });
    _renderTagsFamiliasCF();
}

/** Versión local de colapsarTodo para el modal del catálogo */
function colapsarTodoCatalogo() {
    var filas = document.querySelectorAll(
        "#tablaFamiliasJerarquica tr[data-ruta]",
    );
    filas.forEach(function (f) {
        const nivel = _getNivelFilaCatalogo(f);
        if (nivel > 1) {
            f.style.display = "none";
        }

        // Restaurar iconos del estado colapsado sin tocar el texto de la celda.
        var icon = f.querySelector(".btn-desplegar-icono");
        if (!icon) return;
        if (icon.classList.contains("glyphicon-folder-open")) {
            icon.classList.remove("glyphicon-folder-open");
            icon.classList.add("glyphicon-folder-close");
        }
        if (icon.classList.contains("glyphicon-chevron-down")) {
            icon.classList.remove("glyphicon-chevron-down");
            icon.classList.add("glyphicon-chevron-right");
        }
    });
}

function _getNivelFilaCatalogo(fila) {
    const cls = Array.from(fila.classList || []).find((c) =>
        c.startsWith("nivel-"),
    );
    if (!cls) return 1;
    const n = parseInt(cls.replace("nivel-", ""), 10);
    return Number.isInteger(n) ? n : 1;
}

function _getIconoToggleCatalogo(elemento) {
    if (!elemento) return null;
    if (elemento.classList?.contains("btn-desplegar-icono")) {
        return elemento;
    }
    return elemento.querySelector?.(".btn-desplegar-icono") || null;
}

function toggleHijosDirectosCat(elemento) {
    const filaPadre = elemento?.closest?.("tr");
    if (!filaPadre) return;

    const icon = _getIconoToggleCatalogo(elemento);
    const nivelPadre = _getNivelFilaCatalogo(filaPadre);
    const filas = Array.from(
        document.querySelectorAll("#tablaFamiliasJerarquica tr[data-ruta]"),
    );
    const idxPadre = filas.indexOf(filaPadre);
    if (idxPadre < 0) return;

    const hijosDirectos = [];
    const descendientes = [];
    for (let i = idxPadre + 1; i < filas.length; i++) {
        const fila = filas[i];
        const nivel = _getNivelFilaCatalogo(fila);
        if (nivel <= nivelPadre) break;
        descendientes.push(fila);
        if (nivel === nivelPadre + 1) {
            hijosDirectos.push(fila);
        }
    }

    const abiertos = hijosDirectos.some((f) => f.style.display !== "none");

    if (abiertos) {
        descendientes.forEach(function (f) {
            f.style.display = "none";
        });
        if (icon) {
            if (icon.classList.contains("glyphicon-folder-open")) {
                icon.classList.remove("glyphicon-folder-open");
                icon.classList.add("glyphicon-folder-close");
            }
            if (icon.classList.contains("glyphicon-chevron-down")) {
                icon.classList.remove("glyphicon-chevron-down");
                icon.classList.add("glyphicon-chevron-right");
            }
        }
    } else {
        hijosDirectos.forEach(function (f) {
            f.style.display = "";
        });
        if (icon) {
            if (icon.classList.contains("glyphicon-folder-close")) {
                icon.classList.remove("glyphicon-folder-close");
                icon.classList.add("glyphicon-folder-open");
            }
            if (icon.classList.contains("glyphicon-chevron-right")) {
                icon.classList.remove("glyphicon-chevron-right");
                icon.classList.add("glyphicon-chevron-down");
            }
        }
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

                // Informe 7: fluct. coste mensual se renderiza en informes.php
                // con sus parámetros específicos.
                if (String(checkID[0]) === "7") {
                    const minRecepciones = parseInt(
                        document.getElementById("cfMinRecepciones")?.value ||
                            "3",
                        10,
                    );
                    const minMeses = parseInt(
                        document.getElementById("cfMinMeses")?.value || "3",
                        10,
                    );
                    const incluirEspecial = document.getElementById(
                        "cfIncluirEspecial",
                    )?.checked
                        ? 1
                        : 0;
                    const familiasCf =
                        document.getElementById("cfFamiliasSel")?.value || "";

                    url +=
                        "&min_recepciones=" +
                        encodeURIComponent(Math.max(1, minRecepciones || 1));
                    url +=
                        "&min_meses=" +
                        encodeURIComponent(Math.max(1, minMeses || 1));
                    url +=
                        "&incluir_proveedor_especial=" +
                        encodeURIComponent(incluirEspecial);
                    if (familiasCf) {
                        url += "&familias=" + encodeURIComponent(familiasCf);
                    }
                }
                window.open(url, "_blank");
            }, 5000);
        },
        error: function (request) {
            console.log(request);
        },
    });
}

function _fmtNum(value, decimals) {
    const n = Number(value);
    if (!Number.isFinite(n)) return "";
    return n.toLocaleString("es-ES", {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

function _escapeHtml(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function _fmtYm(ym) {
    if (!ym || !ym.includes("-")) return ym || "";
    const [y, m] = ym.split("-");
    const mes = parseInt(m, 10);
    const nombres = [
        "ene",
        "feb",
        "mar",
        "abr",
        "may",
        "jun",
        "jul",
        "ago",
        "sep",
        "oct",
        "nov",
        "dic",
    ];
    const mm =
        Number.isInteger(mes) && mes >= 1 && mes <= 12 ? nombres[mes - 1] : m;
    return `${mm}-${String(y).slice(-2)}`;
}

function ejecutarInformeCosteFluctuacion() {
    let fechaInicio = document.getElementById("idFechaInicio").value;
    let fechaFinal = document.getElementById("idFechaFinal").value;

    const hoy = new Date();
    const hoyStr = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, "0")}-${String(hoy.getDate()).padStart(2, "0")}`;

    // Si solo se indica una fecha, completar automáticamente el año completo
    // para facilitar consultas históricas (p.ej. 2025).
    if (!fechaInicio && fechaFinal) {
        const yearFinal = fechaFinal.slice(0, 4);
        fechaInicio = `${yearFinal}-01-01`;
    }
    if (fechaInicio && !fechaFinal) {
        const yearInicio = fechaInicio.slice(0, 4);
        if (yearInicio === String(hoy.getFullYear())) {
            fechaFinal = hoyStr;
        } else {
            fechaFinal = `${yearInicio}-12-31`;
        }
    }

    if (!fechaFinal) {
        fechaFinal = hoyStr;
    }
    if (!fechaInicio) {
        fechaInicio = `${hoy.getFullYear()}-01-01`;
    }

    const inicioDate = new Date(`${fechaInicio}T00:00:00`);
    const finalDate = new Date(`${fechaFinal}T00:00:00`);
    if (
        Number.isNaN(inicioDate.getTime()) ||
        Number.isNaN(finalDate.getTime())
    ) {
        alert("Fechas no validas. Revisa el rango indicado.");
        return;
    }
    if (inicioDate > finalDate) {
        alert("La fecha inicio no puede ser posterior a la fecha final.");
        return;
    }

    const minRecepciones = parseInt(
        document.getElementById("cfMinRecepciones")?.value || "3",
        10,
    );
    const minMeses = parseInt(
        document.getElementById("cfMinMeses")?.value || "3",
        10,
    );
    const incluirEspecial = document.getElementById("cfIncluirEspecial")
        ?.checked
        ? 1
        : 0;
    const agrupacion =
        document.getElementById("cfAgrupacion")?.value || "articulo";

    const payload = {
        pulsado: "getCosteFluctuacionData",
        fecha_inicio: fechaInicio,
        fecha_final: fechaFinal,
        min_recepciones: Math.max(1, minRecepciones || 1),
        min_meses: Math.max(1, minMeses || 1),
        incluir_proveedor_especial: incluirEspecial,
        familias: document.getElementById("cfFamiliasSel")?.value || "",
        agrupacion: agrupacion,
    };

    $.ajax({
        data: payload,
        url: "tareas.php",
        type: "post",
        success: function (response) {
            let res = {};
            try {
                res = JSON.parse(response);
            } catch (e) {
                alert("Respuesta no valida del servidor.");
                return;
            }

            if (res.error) {
                alert(res.error);
                return;
            }

            const data = res.coste_fluctuacion || {};
            const resumen = data.resumen || {};
            const filas = data.articulos || [];
            const mesesRango = resumen.meses_rango || [];

            const resumenEl = document.getElementById("cfResumen");
            const tabla = document.getElementById("cfTablaResultados");
            const tbody = document.getElementById("cfTablaBody");
            const headRow = document.getElementById("cfTablaHeadRow");

            if (!resumenEl || !tabla || !tbody || !headRow) return;

            resumenEl.style.display = "";
            resumenEl.innerHTML =
                `<strong>Periodo:</strong> ${fechaInicio} a ${fechaFinal}` +
                ` &nbsp; | &nbsp; ` +
                `<strong>Evaluados:</strong> ${resumen.articulos_total_evaluados || 0}` +
                ` &nbsp; | &nbsp; <strong>Con fluctuacion:</strong> ${resumen.articulos_con_fluctuacion || 0}` +
                ` &nbsp; | &nbsp; <strong>Meses en rango:</strong> ${(resumen.meses_rango || []).length}` +
                ` &nbsp; | &nbsp; <strong>Familias filtro:</strong> ${data.filtros?.familias_count ?? 0}` +
                ` &nbsp; | &nbsp; <strong>Agrupacion:</strong> ${data.filtros?.agrupacion ?? "articulo"}` +
                "<br><small><strong>Leyenda:</strong> '-' sin compras en el mes. " +
                "Valor en cursiva: mes con recepciones por debajo del minimo.</small>";

            if (!filas.length) {
                tabla.style.display = "none";
                tbody.innerHTML = "";
                resumenEl.className = "alert alert-warning";
                resumenEl.innerHTML +=
                    " &nbsp; | &nbsp; No hay articulos que cumplan los filtros.";
                return;
            }

            resumenEl.className = "alert alert-info";
            tabla.style.display = "";

            let headHtml = "";
            headHtml += "<th>ID</th>";
            if ((data.filtros?.agrupacion || "articulo") === "familia") {
                headHtml += "<th>Familia</th>";
            } else if (
                (data.filtros?.agrupacion || "articulo") === "subfamilia"
            ) {
                headHtml += "<th>Subfamilia</th>";
            } else {
                headHtml += "<th>Producto</th>";
            }
            mesesRango.forEach(function (ym) {
                const label = _fmtYm(ym);
                headHtml += `<th>${label}</th>`;
            });
            headRow.innerHTML = headHtml;

            let html = "";
            filas.forEach(function (a) {
                const mesesArticulo = {};
                (a.meses || []).forEach(function (m) {
                    mesesArticulo[m.ym] = m;
                });

                html += "<tr>";
                html += `<td>${a.idArticulo}</td>`;
                html += `<td>${_escapeHtml(a.articulo_name || "")}</td>`;

                mesesRango.forEach(function (ym) {
                    const m = mesesArticulo[ym];
                    if (!m || m.coste_promedio === null) {
                        html += "<td>-</td>";
                        return;
                    }

                    const coste = _fmtNum(m.coste_promedio, 4);
                    if (m.cumple_min_recepciones) {
                        html += `<td>${coste}</td>`;
                    } else {
                        html += `<td title="Recepciones del mes por debajo del minimo"><em>${coste}</em></td>`;
                    }
                });

                html += "</tr>";
            });
            tbody.innerHTML = html;
        },
        error: function (request) {
            console.log(request);
            alert("No se pudo calcular el informe de fluctuacion.");
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
window.abrirCatalogoFamiliasCosteFluctuacion =
    abrirCatalogoFamiliasCosteFluctuacion;
window._eliminarFamiliaCosteFluctuacion = _eliminarFamiliaCosteFluctuacion;
window.limpiarFamiliasCosteFluctuacion = limpiarFamiliasCosteFluctuacion;

export {
    _familiasSel,
    _informesFamilias,
    _onOpcionChange,
    abrirCatalogoFamilias,
    agregarFamiliaInforme,
    _eliminarFamiliaInforme,
    _renderTagsFamilias,
    _renderTagsFamiliasCF,
    colapsarTodoCatalogo,
    toggleHijosDirectosCat,
    metodoClick,
    AbrirModalLoading,
    abrirCatalogoFamiliasCosteFluctuacion,
    limpiarFamiliasCosteFluctuacion,
};
