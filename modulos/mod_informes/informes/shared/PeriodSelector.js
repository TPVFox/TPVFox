/**
 * PeriodSelector.js — Selectores de periodo y barra de navegación POSStock
 *
 * Responsabilidades:
 *   - posstockActualizarNumero: rellena el selector de número de periodo (AJAX)
 *   - posstockGenerar: calcula el periodo (AJAX) y lanza carga de datos
 *   - _posstockCargarBarraPeriodos: inyecta la barra de botones de navegación
 *   - posstockNavegar: navega a otro periodo desde la barra
 *   - DOMContentLoaded: habilitar botón Generar al elegir número de periodo
 */

import { cargarDatosPosstock, _posstockMostrarError } from "../posstock/PosstockLoader.js";

/**
 * Rellena el selector de número de periodo llamando a PHP (getOpcionesPeriodo).
 * También habilita/deshabilita el botón Generar.
 */
function posstockActualizarNumero(mantenerNumero) {
    let tipo = document.getElementById("posstockTipo").value;
    let anio = parseInt(document.getElementById("posstockAnio").value, 10);
    let sel = document.getElementById("posstockNumero");

    const prevNumero = mantenerNumero && sel.value ? sel.value : null;

    sel.innerHTML = "";
    sel.disabled = true;
    document.getElementById("posstockBtnGenerar").disabled = true;
    document.getElementById("posstockAvisoVentana").style.display = "none";

    const barra = document.getElementById("posstockBarraBotones");
    if (barra) barra.style.display = "none";
    const wrapBotones = document.getElementById("posstockBotonesPeriodo");
    if (wrapBotones) wrapBotones.innerHTML = "";

    const labelEl = document.getElementById("posstockLabelNumero");
    if (labelEl)
        labelEl.textContent = tipo === "anual" ? "Tipo de análisis" : "Periodo";

    const filaCasos = document.getElementById("posstockFilaCasos");
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
            let resultado = JSON.parse(response);
            if (resultado.error) return;
            sel.innerHTML =
                '<option value="">— seleccionar —</option>' +
                (resultado.html || "");
            sel.disabled = false;

            if (prevNumero) {
                for (let i = 0; i < sel.options.length; i++) {
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
    let tipo = document.getElementById("posstockTipo").value;
    const numero = document.getElementById("posstockNumero").value;
    let anio = document.getElementById("posstockAnio").value;

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
            let periodo = JSON.parse(response);
            if (periodo.error) {
                _posstockMostrarError(
                    "Error al calcular periodo: " + periodo.error,
                );
                return;
            }

            const tipoInc = tipo === "anual" ? numero : "";
            let _enVentana = false;

            if (window.POSSTOCK_VENTANA_DIAS > 0) {
                const hoy = new Date();
                hoy.setHours(0, 0, 0, 0);
                const limite = new Date(hoy);
                limite.setDate(limite.getDate() - window.POSSTOCK_VENTANA_DIAS);
                const ffMov = new Date(periodo.fecha_fin_movimientos);
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
            const resultado = JSON.parse(response);
            if (resultado.error) return;
            const wrap = document.getElementById("posstockBotonesPeriodo");
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
 */
function posstockNavegar(numero) {
    const tipo = window.posstockTipoActivo;
    const anio = window.posstockAnioActivo;
    if (!tipo || !anio) return;

    if (tipo === "anual") {
        if (window._posstockAnualEnVentana && numero !== "caso6b") return;
        window.posstockTipoIncidenciaActivo = numero;
        document.querySelectorAll("[id^='posstockBtn_']").forEach(function (b) {
            if (!b.disabled) b.className = "btn btn-default btn-xs";
        });
        const btnActivo = document.getElementById("posstockBtn_" + numero);
        if (btnActivo) btnActivo.className = "btn btn-primary btn-xs";
        cargarDatosPosstock(window.posstockPeriodoActivo, numero);
        return;
    }

    let btn = document.getElementById("posstockBtn_" + numero);
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
            const periodo = JSON.parse(response);
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
    const sel = document.getElementById("posstockNumero");
    if (sel) {
        sel.addEventListener("change", function () {
            const btn = document.getElementById("posstockBtnGenerar");
            if (btn) btn.disabled = this.value === "";
        });
    }
});

window.posstockActualizarNumero = posstockActualizarNumero;
window.posstockGenerar = posstockGenerar;
window.posstockNavegar = posstockNavegar;

export {
    posstockActualizarNumero,
    posstockGenerar,
    _posstockCargarBarraPeriodos,
    posstockNavegar,
};
