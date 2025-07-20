/**
 * ui-helper.js
 * -------------
 * Script para alternar la visibilidad y el tamaño de paneles en la interfaz.
 *
 * IMPLEMENTACIÓN:
 * 1. Incluye este archivo JS en tu HTML, preferiblemente antes de </body>:
 *      <script src="/controllers/ui-helper.js"></script>
 * 2. Añade un botón con los siguientes atributos:
 *      - data-toggle-panel="ID_DEL_PANEL_A_MOSTRAR_OCULTAR"
 *      - data-other-panel="ID_DEL_OTRO_PANEL" (opcional, para modificar otro panel)
 *      - data-expand-class="CLASE_CUANDO_SE_MUESTRA"
 *      - data-collapse-class="CLASE_CUANDO_SE_OCULTA"
 *      - data-other-expand-class="CLASE_OTRO_PANEL_MOSTRAR"
 *      - data-other-collapse-class="CLASE_OTRO_PANEL_OCULTAR"
 *      - data-text-show="Texto cuando está oculto"
 *      - data-text-hide="Texto cuando está visible"
 * 3. Los paneles deben tener el id correspondiente y clases compatibles con Bootstrap o tu framework CSS.
 *
 * Ejemplo de botón:
 * <button
 *   data-toggle-panel="configCol"
 *   data-other-panel="plusCol"
 *   data-expand-class="col-12 col-lg-4 mb-4"
 *   data-collapse-class="col-12"
 *   data-other-expand-class="col-12 col-lg-8"
 *   data-other-collapse-class="col-12"
 *   data-text-show="Mostrar configuración"
 *   data-text-hide="Ocultar configuración">
 *   Ocultar configuración
 * </button>
 *
 * Ejemplo de paneles:
 * <div class="col-12 col-lg-4 mb-4" id="configCol">...</div>
 * <div class="col-12 col-lg-8" id="plusCol">...</div>
 */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-toggle-panel]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-toggle-panel');
            var target = document.getElementById(targetId);
            if (!target) {
                console.log('[ui-helper] Panel objetivo no encontrado:', targetId);
                return;
            }

            var otherPanelId = this.getAttribute('data-other-panel');
            var otherPanel = otherPanelId ? document.getElementById(otherPanelId) : null;

            var expandClass = this.getAttribute('data-expand-class') || '';
            var collapseClass = this.getAttribute('data-collapse-class') || '';
            var otherExpandClass = this.getAttribute('data-other-expand-class') || '';
            var otherCollapseClass = this.getAttribute('data-other-collapse-class') || '';
            var textShow = this.getAttribute('data-text-show') || 'Mostrar';
            var textHide = this.getAttribute('data-text-hide') || 'Ocultar';
            var isHidden = target.style.display === 'none' || window.getComputedStyle(target).display === 'none';

            if (isHidden) {
                console.log('[ui-helper] Mostrando panel:', targetId);
                target.style.display = '';
                if (expandClass) {
                    target.className = expandClass;
                    console.log('[ui-helper] Clase expandida aplicada a', targetId, ':', expandClass);
                }
                this.textContent = textHide;
                console.log('[ui-helper] Texto del botón cambiado a:', textHide);

                if (otherPanel) {
                    otherPanel.style.display = '';
                    if (otherExpandClass) {
                        otherPanel.className = otherExpandClass;
                        console.log('[ui-helper] Clase expandida aplicada a', otherPanelId, ':', otherExpandClass);
                    }
                    console.log('[ui-helper] Otro panel mostrado:', otherPanelId);
                }
            } else {
                console.log('[ui-helper] Ocultando panel:', targetId);
                target.style.display = 'none';
                if (collapseClass) {
                    target.className = collapseClass;
                    console.log('[ui-helper] Clase colapsada aplicada a', targetId, ':', collapseClass);
                }
                this.textContent = textShow;
                console.log('[ui-helper] Texto del botón cambiado a:', textShow);

                if (otherPanel) {
                    otherPanel.style.display = '';
                    if (otherCollapseClass) {
                        otherPanel.className = otherCollapseClass;
                        console.log('[ui-helper] Clase colapsada aplicada a', otherPanelId, ':', otherCollapseClass);
                    }
                    console.log('[ui-helper] Otro panel expandido:', otherPanelId);
                }
            }
        });
    });
});

// Mostrar/ocultar la fila de filtros de cualquier tabla filtrable
$(document).on('click', '.toggle-filtros', function() {
    var $tabla = $(this).closest('table');
    var $filtros = $tabla.find('tr.filtros');
    $filtros.toggle();
    console.log('Toggle filtros:', $tabla.attr('id') || $tabla[0], 'Visible:', $filtros.is(':visible'));
    if ($filtros.is(':hidden')) {
        $tabla.find('.filtro-col').val('');
        $tabla.find('tbody tr').show();
        console.log('Filtros ocultos, limpiando inputs y mostrando todas las filas');
    }
});

// Filtro rápido por columna para cualquier tabla con clase .tabla-filtrable
$(document).on('input', '.tabla-filtrable .filtro-col', function() {
    var $tabla = $(this).closest('table');
    var $inputs = $tabla.find('.filtro-col');
    console.log('Filtrando tabla:', $tabla.attr('id') || $tabla[0]);
    $tabla.find('tbody tr').each(function() {
        var $row = $(this);
        var mostrar = true;
        $inputs.each(function(idx, input) {
            var val = $(input).val().toLowerCase();
            var cell = $row.find('td').eq(idx).text().toLowerCase();
            if (val && cell.indexOf(val) === -1) {
                mostrar = false;
            }
        });
        $row.toggle(mostrar);
        if (mostrar) {
            console.log('Mostrando fila:', $row.index());
        }
    });
});