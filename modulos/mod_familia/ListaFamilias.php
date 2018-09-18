<?php
/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

include_once './../../inicial.php';
require_once $URLCom . '/modulos/mod_familia/clases/ClaseFamilias.php';
include_once $URLCom . '/controllers/Controladores.php';
require_once $URLCom . '/modulos/mod_familia/funciones.php';
$Controler = new ControladorComun;

// Mostramos formulario si no tiene acceso.
include_once ($URLCom . '/controllers/parametros.php');

$VarJS = ""; 
$familias = new ClaseFamilias($BDTpv);
// Obtenemos la configuracion del usuario o la por defecto
$idpadre=0;
$familiasPrincipales = $familias->leerUnPadre($idpadre);

$familiasPrincipales['datos'] = $familias->cuentaHijos($familiasPrincipales['datos']);
$familiasPrincipales['datos'] = $familias->cuentaProductos($familiasPrincipales['datos']);
?>
<!DOCTYPE html>
<html>
    <head>

        <?php
        include_once $URLCom . '/head.php';
        ?>
        
        <link rel="stylesheet" href="<?php echo $HostNombre; ?>/jquery/jquery-ui.min.css" type="text/css">
        <link rel="stylesheet" href="<?php echo $HostNombre; ?>/modulos/mod_familia/familias.css" type="text/css">

        <script src="<?php echo $HostNombre; ?>/jquery/jquery-ui.min.js"></script>
 <script src="<?php echo $HostNombre; ?>/lib/js/autocomplete.js"></script>  
        <script type="text/javascript" src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
        <script type="text/javascript" src="<?php echo $HostNombre; ?>/controllers/global.js"></script>

        <script type="text/javascript" src="<?php echo $HostNombre; ?>/modulos/mod_familia/funciones.js"></script>
    </head>

    <body>
        <?php
        include_once $URLCom . '/modulos/mod_menu/menu.php';
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
                        <ul class="nav nav-pills nav-stacked"> 
                                <li><a class="" 
                                            id="btn-expandirtodo" onclick="expandirTodos()">expandir Nivel</a></li>
                                <li><a class="" 
                                            id="btn-compactartodo" onclick="compactarTodos()">compactar Todo</a></li>
                            
                            <?php
                            if ($ClasePermisos->getAccion("crear") == 1) {
                                ?>
                                <li><a class="" id="botonnuevo-hijo-0"
                                            data-alabuelo="0" href="familia.php?id=0">Añadir</a></li>
                                    <?php
                                }
                                ?>
                            <?php
                            if ($ClasePermisos->getAccion("eliminar") == 1) {
                                ?>
                                <li><a class="" id="btn-eliminar" style="display: none"
                                            data-alabuelo="0" onclick="EliminarFamiliasSeleccionadas()">Eliminar marcados</a></li>
                                    <?php
                                }
                                ?>
                        </ul>
                    </div>
                    <div id="menuseleccion" class="nav" style="display: none">
                        <h5> Opciones para una selección</h5>
                        <ul> 
                            <li><button class="" id="boton-eliminarseccionados"
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
                                    <th>Productos</th>
                                </tr>
                            </thead>
                            <tbody id="seccion-0" >
                            <?php 
                            echo familias2Html($familiasPrincipales['datos']);
                            
                            ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

    </body>
</html>
