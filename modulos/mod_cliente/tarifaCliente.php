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

require_once 'claseTarifaCliente.php';

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
    var_dump($datos);
}
include ("./../../controllers/Controladores.php");
$Controler = new ControladorComun; 

// Mostramos formulario si no tiene acceso.
include_once ($RutaServidor.$HostNombre.'/controllers/parametros.php');

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
<script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
<script src="<?php echo $HostNombre; ?>/modulos/mod_cliente/funciones.js"></script>
<script type="text/javascript">
// Objetos cajas de tpv
<?php echo $VarJS;?>
var cliente = <?php echo json_encode($uncliente);?>;
</script>
</head>

<body>
<?php

	include '../../header.php';?>

 <div class="container">
        <h2 class="text-center"> Tarifa de Cliente </h2>

        <a  href="ListaClientes.php">Volver Atrás</a>

        <input type="text" style="display:none;" name="idTemporal" value=0>
        <div class="row" >
            <div class="col-md-8">
                <div class="form-group">
					<?php 
					//~ var_dump($uncliente);
					//~ echo '<pre>';
					//~ print_r($uncliente);
					//~ echo '</pre>';
					?>
                    <label>Cliente:</label>
                    <input type="text" id="id_cliente" name="idCliente" 
                           disabled value="<?php echo $uncliente['idClientes'];?>" size="2" placeholder='id' >
                    <input type="text" id="Cliente" name="Cliente" disabled 
                           placeholder="Nombre de cliente" value="<?php echo $uncliente['Nombre'];?>" size="60" >
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
                        <td><input id="idArticulo" type="text" name="idArticulo" 
                                   class="fox-buscar"
                                   placeholder="idArticulo" data-obj= "cajaidArticulo" 
                                   size="13" value=""  onkeydown="controlEventos(event)" />
                        </td>
                        <td><input id="Referencia" type="text" name="Referencia" 
                                   class="fox-buscar"
                                   placeholder="Referencia" data-obj="cajaReferencia" 
                                   size="13" value="" onkeydown="controlEventos(event)" />
                        </td>
                        <td><input id="Codbarras" type="text" name="Codbarras" 
                                   class="fox-buscar"
                                   placeholder="Codbarras" data-obj= "cajaCodBarras" 
                                   size="13" value="" data-objeto="cajaCodBarras" 
                                   onkeydown="controlEventos(event)" />
                        </td>
                        <td><input id="Descripcion" type="text" name="Descripcion" 
                                   class="fox-buscar"
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
                        <td><input type="text" placeholder="id" name="idArticulo" id="inputIdArticulo" /></td>
                        <td><input type="text" disabled name="descripcion" id="inputDescripcion" /></td>
                        <td><input type="text" placeholder="Precio sin iva" name="precioSiva" id="inputPrecioSin" /></td>
                        <td><input type="text" placeholder="% iva" name="ivaArticulo" id="inputIVA" /></td>
                        <td><input type="text" placeholder="precio con iva" name="precioCiva" id="inputPrecioCon" /></td>
                        <td><button id="btn-grabar-tc" class="btn btn-primary btn-sm">grabar</button> 
                            <button id="btn-cancelar-tc" class="btn btn-danger btn-sm">cancelar</button></td>
                    </tr>
                </table>
                <input type="hidden"  name="idClientes" id="idClientes" />
            </div>
        </div>

        <div class="row">
            <table id="tabla" class="table table-striped" >
                <thead>
                    <tr>
                        <th>L</th>
                        <th>Id Articulo</th>
                        <th>Descripcion</th>
                        <th>Precio S/IVA</th>
                        <th>% IVA</th>
                        <th>Precio C/IVA</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    
                   
                </tbody>
            </table>
        </div>

    </div>


    <!-- Modal -->
    <?php // Incluimos paginas modales
		include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
		?>
 

</body>
</html>










