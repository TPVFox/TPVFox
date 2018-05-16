<?php
/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

include_once './../../inicial.php';

//~ require_once './../../lib/btemplate/bTemplate.php';

include_once './../../clases/cliente.php';

require_once './clases/claseTarifaCliente.php';

$idcliente = $_GET['id'];

$cliente = new Cliente($BDTpv);
$uncliente = $cliente->DatosClientePorId($idcliente);

$tarifaCliente = (new TarifaCliente($BDTpv))->leer($uncliente['idClientes']);

if (isset($tarifaCliente['error'])) {
    // hay un error en la consulta
    // ¿ que hacemos?
    echo $tarifaCliente['error'] . '<--<br>-->' . $tarifaCliente['consulta'];
} else {
    $datos = $tarifaCliente['datos'];
//    var_dump($datos);
}
include ("./../../controllers/Controladores.php");
$Controler = new ControladorComun;

// Mostramos formulario si no tiene acceso.
include_once ($RutaServidor . $HostNombre . '/controllers/parametros.php');

$ClasesParametros = new ClaseParametros('parametros.xml');
$parametros = $ClasesParametros->getRoot();
$VarJS = $Controler->ObtenerCajasInputParametros($parametros);

// Obtenemos la configuracion del usuario o la por defecto
?>
<!DOCTYPE html>
<html>
    <head>

        <?php
        include './../../head.php';
        ?>
        <script type="text/javascript" src="<?php echo $HostNombre; ?>/lib/js/jquery.bootpag.min.js"></script>
        <script type="text/javascript" src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
        <script type="text/javascript" src="<?php echo $HostNombre; ?>/modulos/mod_cliente/funciones.js"></script>
        <script type="text/javascript" src="<?php echo $HostNombre; ?>/modulos/mod_cliente/tarifacliente.js"></script>
        <script type="text/javascript">
            // Objetos cajas de tpv
<?php echo $VarJS; ?>
            var cliente = <?php echo json_encode($uncliente); ?>;
        </script>
    </head>

    <body>
        <?php include '../../header.php'; ?>

        <div class="container">
            <h2 class="text-center"> Tarifa de Cliente </h2>

            <a  href="ListaClientes.php">Volver Atrás</a>

            <input type="text" style="display:none;" name="idTemporal" value=0>
            <div class="row" >
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Cliente:</label>
                        <input type="text" id="id_cliente" name="idCliente" 
                               disabled value="<?php echo $uncliente['idClientes']; ?>" size="2" placeholder='id' >
                        <input type="text" id="Cliente" name="Cliente" disabled 
                               placeholder="Nombre de cliente" value="<?php echo $uncliente['Nombre']; ?>" size="60" >
                    </div>
                </div>
            </div>
            <!-- Tabla de lineas de productos -->
            <div class="row">
                <table id="tabla" class="table table-striped" >
                    <thead>
                        <tr>
                            <th>Id Articulo</th>
                            <th>Referencia</th>
                            <th>Cod Barras</th>
                            <th>Descripcion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="Row0" style=>  
                            <td><input id="cajaidArticulo" type="text" name="idArticulo" 
                                       placeholder="idArticulo" data-obj= "cajaidArticulo" 
                                       size="13" value=""  onkeydown="controlEventos(event)" />
                            </td>
                            <td><input id="cajaReferencia" type="text" name="Referencia" 
                                       placeholder="Referencia" data-obj="cajaReferencia" 
                                       size="13" value="" onkeydown="controlEventos(event)" />
                            </td>
                            <td><input id="cajaCodbarras" type="text" name="Codbarras" 
                                       placeholder="Codbarras" data-obj= "cajaCodBarras" 
                                       size="13" value="" data-objeto="cajaCodBarras" 
                                       onkeydown="controlEventos(event)" />
                            </td>
                            <td><input id="cajaDescripcion" type="text" name="Descripcion" 
                                       placeholder="Descripcion" data-obj="cajaDescripcion" 
                                       size="20" value="" onkeydown="controlEventos(event)" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="row">
                <div id="formulario" style="display: none">                   
                    <table>
                        <tr>
                            <td>L</td>
                            <td><input type="text" placeholder="id" name="idArticulo" 
                                       disabled="disabled" id="inputIdArticulo" /></td>
                            <td><input type="text" disabled="disabled" name="descripcion" 
                                       id="inputDescripcion" /></td>
                            <td><input type="text" placeholder="Precio sin iva" class="al-editiva"
                                       name="precioSiva" id="inputPrecioSin" style="text-align: right"
                                       data-obj="inputPrecioSin" data-result="inputPrecioCon" 
                                       data-factor="*" data-percent="inputIVA"
                                       /></td>
                            <td><input type="text" placeholder="% iva" name="ivaArticulo" 
                                       disabled="disabled" id="inputIVA" style="text-align: right" /></td>
                            <td><input type="text" placeholder="precio con iva" class="al-editiva"
                                       name="precioCiva" id="inputPrecioCon" style="text-align: right"
                                       data-obj="inputPrecioCon" data-result="inputPrecioSin" 
                                       data-factor="/" data-percent="inputIVA"
                                       /></td>
                            <td><button id="btn-grabar-tc" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-ok"></span> grabar</button> 
                                <button id="btn-cancelar-tc" class="btn btn-danger btn-sm"><span class="glyphicon glyphicon-remove"></span> cancelar</button></td>
                        </tr>
                    </table>
                </div>

            </div>

            <div class="row">
                <table id="tabla" class="table table-striped" >
                    <thead>
                        <tr>
                            <th>L</th>
                            <th>Id Articulo</th>
                            <th>Descripcion</th>
                            <th style="text-align: right">Precio S/IVA</th>
                            <th style="text-align: right">% IVA</th>
                            <th style="text-align: right">Precio C/IVA</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($datos as $tarifaCliente) {
                            echo '<tr>';
                            echo '<td> L </td>';
                            echo '<td>' . $tarifaCliente['idArticulo'] . '</td>';
                            echo '<td>' . $tarifaCliente['descripcion'] . '</td>';
                            echo '<td style="text-align: right">' . number_format($tarifaCliente['pvpSiva'],2, '.', '') . '</td>';
                            echo '<td style="text-align: right">' . number_format($tarifaCliente['ivaArticulo'],2, '.', '') . '</td>';
                            echo '<td style="text-align: right">' . number_format($tarifaCliente['pvpCiva'],2, '.', '') . '</td>';
                            echo '<td><button name="btn-grabar-tc" data-idarticulo=' . $tarifaCliente['idArticulo'] . ' data-idcliente=' . $tarifaCliente['idClientes']
                            . ' class="btn btn-primary btn-sm art-modificar"><span class="glyphicon'
                            . ' glyphicon-pencil"></span> modificar</button> '
                            . ' <button name="btn-cancelar-tc" data-idarticulo=' . $tarifaCliente['idArticulo'] . ' data-idcliente=' . $tarifaCliente['idClientes']
                            . ' class="btn btn-danger btn-sm art-eliminar"><span class="glyphicon '
                            . ' glyphicon-trash"></span> eliminar</button></td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        </div>
        <!-- Modal -->
        <div id="busquedaModal" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header btn-primary">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h3 class="modal-title text-center">Buscar Artículos</h3>
                    </div>
                    <div class="modal-body">
                        <input id="cajaBusqueda" type="text" value="">
                        <button id="buscarArticulos" class="art-buscar">Buscar</button>
                        <input id="campoabuscar"   type="hidden">
                        <input id="paginabuscar"   type="hidden" value="1">

                        <div class="articulos-page-selection-top"></div>

                        <p>contenido.</p>

                        <div class="articulos-page-selection-bottom"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>

            </div>
        </div>

    </body>
</html>










