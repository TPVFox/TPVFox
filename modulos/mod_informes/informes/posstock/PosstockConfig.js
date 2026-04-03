/**
 * PosstockConfig.js — Modal de configuración POSStock
 *
 * Responsabilidades:
 *   - Abrir el modal de configuración (AJAX)
 *   - Guardar configuración y actualizar variables JS
 *   - Toggle de descripción del modelo estadístico
 */

import { _posstockMostrarError } from "./PosstockLoader.js";

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

export {
    abrirModalConfigPosstock,
    guardarConfigPosstock,
    modalToggleModeloEstadistico,
};
