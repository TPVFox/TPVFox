/**
 * ui-helper.js
 * -------------
 * Este archivo contiene un script para manejar la funcionalidad de mostrar/ocultar paneles en la interfaz de usuario.
 * 
 * Uso:
 * - Este script busca todos los elementos con el atributo `data-toggle-panel`.
 * - Al hacer clic en uno de estos elementos, alterna la visibilidad del panel objetivo (por id).
 * - Permite personalizar clases CSS y textos para los estados expandido/colapsado mediante atributos:
 *      - data-toggle-panel: id del panel a mostrar/ocultar.
 *      - data-expand-class: clase CSS para aplicar al mostrar el panel.
 *      - data-collapse-class: clase CSS para aplicar al ocultar el panel.
 *      - data-text-show: texto del botón cuando el panel está oculto.
 *      - data-text-hide: texto del botón cuando el panel está visible.
 * 
 * Ejemplo de uso en HTML:
 *   <button 
 *      data-toggle-panel="miPanel"
 *      data-expand-class="panel-abierto"
 *      data-collapse-class="panel-cerrado"
 *      data-text-show="Mostrar"
 *      data-text-hide="Ocultar">
 *      Mostrar
 *   </button>
 *   <div id="miPanel" style="display:none;">Contenido del panel</div>
 */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-toggle-panel]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-toggle-panel');
            var target = document.getElementById(targetId);
            if (!target) return;

            var expandClass = this.getAttribute('data-expand-class') || '';
            var collapseClass = this.getAttribute('data-collapse-class') || '';
            var textShow = this.getAttribute('data-text-show') || 'Mostrar';
            var textHide = this.getAttribute('data-text-hide') || 'Ocultar';
            var isHidden = target.style.display === 'none' || window.getComputedStyle(target).display === 'none';

            if (isHidden) {
                target.style.display = '';
                if (expandClass) target.className = expandClass;
                this.textContent = textHide;
            } else {
                target.style.display = 'none';
                if (collapseClass) target.className = collapseClass;
                this.textContent = textShow;
            }
        });
    });
});