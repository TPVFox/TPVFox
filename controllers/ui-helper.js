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
            if (!target) return;

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
                // Mostrar este panel y agrandarlo
                target.style.display = '';
                if (expandClass) target.className = expandClass;
                this.textContent = textHide;

                // Ocultar el otro panel si existe y expandirlo
                if (otherPanel) {
                    otherPanel.style.display = '';
                    if (otherExpandClass) otherPanel.className = otherExpandClass;
                }
            } else {
                // Ocultar este panel
                target.style.display = 'none';
                if (collapseClass) target.className = collapseClass;
                this.textContent = textShow;

                // Expandir el otro panel si existe
                if (otherPanel) {
                    otherPanel.style.display = '';
                    if (otherCollapseClass) otherPanel.className = otherCollapseClass;
                }
            }
        });
    });
});