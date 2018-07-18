<?php
/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

include_once './../../inicial.php';
require_once $URLCom.'/modulos/mod_familia/clases/ClaseFamilias.php';
include_once $URLCom.'/controllers/Controladores.php';




$Controler = new ControladorComun;

// Mostramos formulario si no tiene acceso.
include_once ($URLCom .'/controllers/parametros.php');

$ClasesParametros = new ClaseParametros('parametros.xml');
$parametros = $ClasesParametros->getRoot();
$VarJS = $Controler->ObtenerCajasInputParametros($parametros);

$familias = new ClaseFamilias($BDTpv);
// Obtenemos la configuracion del usuario o la por defecto
?>
<!DOCTYPE html>
<html>
    <head>

        <?php
        include_once $URLCom.'/head.php';
       
        ?>
        <link rel="stylesheet" href="<?php echo $HostNombre;?>/jquery/jquery-ui.min.css" type="text/css">

<script src="<?php echo $HostNombre; ?>/jquery/jquery-ui.min.js"></script>

        <script type="text/javascript" src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
        <script type="text/javascript" src="<?php echo $HostNombre; ?>/controllers/global.js"></script>
        <script type="text/javascript" src="<?php echo $HostNombre; ?>/modulos/mod_producto/funciones.js"></script>
        <script type="text/javascript" src="<?php echo $HostNombre; ?>/modulos/mod_familia/familias.js"></script>
    </head>

    <body>
        <?php 
        //~ include '../../header.php'; 
         include_once $URLCom.'/modulos/mod_menu/menu.php';
        ?>

        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h2 class="text-center"> Familias de Productos </h2>
                </div>
            </div>
            <div class="row">
                <div class="col-md-2">
                    <div class="nav">
                        <h4> Familias</h4>
                        <h5> Opciones para una selección</h5>
                        <ul class="nav nav-pills nav-stacked"> 
                            <li><button class="btn btn-link" id="botonnuevo-hijo-0"
                                        data-alabuelo="0">Añadir</button></li>
                            <li><button class="btn btn-link" id="boton-cambiarpadre"
                                        data-alabuelo="0">Modificar</button></li>
                        </ul>
                    </div>
                    <div id="menuseleccion" class="nav" style="display: none">
                        <h5> Opciones para una selección</h5>
                        <ul class="nav nav-pills nav-stacked"> 
                            <li><button class="btn btn-link" id="boton-eliminarseccionados"
                                        data-alabuelo="0">Quitar seleccion todos</button></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-10">
                    <!-- Tabla de lineas de productos -->

                    <div class="row" id="tablafamilias">
                        <table id="tabla" class="table table-bordered table-hover table-striped" >
                            <thead>
                                <tr>
                                    <th>L</th>
                                    <th>Id Familia</th>
                                    <th>Nombre</th>
                                    <th >padre</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="seccion-0" >

                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <!-- Modal -->
        <div id="familiasModal" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header btn-primary">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h3 class="modal-title text-center">Familias</h3>
                    </div>
                    <div class="modal-body">
                        <div id="formularioFamiliaModal" >
                            <div class="row">
                                <div class="col-md-6">
                                    <input id="inputNombreModal" type="text" value="">
                                </div>                            
                                <div class="col-md-2">
                                    <select id="selectFamiliaPadre">
                                        <option value="-1">--- ningún padre ---</option>
                                    </select>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button id="btn-fam-grabar" class="btn btn-primary" >
                            <span class="glyphicon glyphicon-save"> </span>Grabar</button>&nbsp;
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal -->
        <div id="cambioPadreModal" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header btn-primary">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h3 class="modal-title text-center">Familias: cambiar padre</h3>
                    </div>
                    <div class="modal-body">
                        <div id="formularioFamiliaModal" >
                            <div class="row  ui-front">
                                Cambiar padre a: <input class="form-control" id="inputNombreFamiliaModal" 
                                                        value="--- Padre raíz ---"/>
                                <input id="inputIdFamiliaModal" type="hidden" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button id="btn-padre-grabar" class="btn btn-primary" >
                            <span class="glyphicon glyphicon-save"> </span>Grabar</button>&nbsp;
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
