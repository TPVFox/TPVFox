<?php 
/*
 * @version     0.1
 * @copyright   Copyright (C) 2017 Catalogo productos Soluciones Vigo.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ricardo Carpintero
 * @Descripcion	
 *  */
		// Objetivo de esta aplicacion es:
		// ventana popup
		//Buscador 
		//listar productos encontrados
		
		
//https://www.w3schools.com/bootstrap/bootstrap_modal.asp
?>


<!-- Modal -->
<div id="ventanaModal" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header btn-primary">
        <button type="button" id="closeX" class="close" data-dismiss="modal">&times;</button>
        <h3 class="modal-title text-center">	Titulo Provisorio...</h3>
      </div>
      <div class="modal-body" id="modalbody">
		  <?php // Ahora dentro cargamos otro fichero , segun el titulo ?>
      </div>
      <div class="modal-footer">
        <button type="button" id="closebutton" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
