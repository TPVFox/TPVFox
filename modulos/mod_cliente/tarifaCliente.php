<?php
/*
 * @Copyright 2018, Alagoro Software. 
 * @licencia   GNU General Public License version 2 or later; see LICENSE.txt
 * @Autor Alberto Lago Rodríguez. Alagoro. alberto arroba alagoro punto com
 * @Descripción	
 */

include_once './../../inicial.php';

//~ require_once './../../lib/btemplate/bTemplate.php';

include_once  $URLCom.'/clases/cliente.php';
include_once  $URLCom.'/modulos/mod_cliente/clases/claseTarifaCliente.php';

$idcliente = $_GET['id'];

$cliente = new Cliente($BDTpv);
$uncliente = $cliente->DatosClientePorId($idcliente);

$tarifaCliente = (new TarifaCliente())->leer($uncliente['idClientes']);

if (isset($tarifaCliente['error'])) {
    // hay un error en la consulta
    // ¿ que hacemos?
    echo $tarifaCliente['error'] . '<--<br>-->' . $tarifaCliente['consulta'];
} else {
    $datos = isset($tarifaCliente['datos'])? $tarifaCliente['datos']:[];

}
include_once $URLCom.'/controllers/Controladores.php';

$Controler = new ControladorComun;

// Mostramos formulario si no tiene acceso.
include_once ($URLCom .'/controllers/parametros.php');

$ClasesParametros = new ClaseParametros('parametros.xml');
$parametros = $ClasesParametros->getRoot();
$VarJS = $Controler->ObtenerCajasInputParametros($parametros);

//~ echo '<pre>';
//~ print_r($datos);
//~ echo '</pre>';

// Obtenemos la configuracion del usuario o la por defecto
?>
<!DOCTYPE html>
<html>
    <head>

        <?php
        include_once  $URLCom.'/head.php';
        ?>
        <script type="text/javascript" src="<?php echo $HostNombre; ?>/lib/js/jquery.bootpag.min.js"></script>
        <script type="text/javascript" src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
        <script type="text/javascript" src="<?php echo $HostNombre; ?>/modulos/mod_cliente/funciones.js"></script>
        <script type="text/javascript">
            // Objetos cajas de tpv
<?php echo $VarJS; ?>
            var cliente = <?php echo json_encode($uncliente); ?>;
        </script>
    </head>

    <body>
        <?php
         //~ include '../../header.php'; 
          include_once $URLCom.'/modulos/mod_menu/menu.php';
         ?>

        <div class="container">
            <h2 class="text-center"> Tarifa de Cliente </h2>
			<?php 
			if(count($datos)>0){
				?>
				  <input type="text" class="btn btn-info pull-right"    onclick="imprimirTarifa(<?php echo $idcliente; ?>)" value="Imprimir Tarifas">
				<?php
			}
			?>
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
            <div class="col-md-12 form-inline">
                <div class="col-md-2 form-group">
					<label> IdArticulo:</label>
					<input id="cajaidArticulo" type="text" name="idArticulo" placeholder="idArticulo"
					data-obj= "cajaidArticulo" size="6" value=""  onkeydown="controlEventos(event)" />
					
				</div>
				<div class="col-md-2 form-group">
					<label> Referencia:</label>
					<input id="cajaReferencia" type="text" name="Referencia" placeholder="Referencia"
					data-obj="cajaReferencia" size="10" value="" onkeydown="controlEventos(event)" />
				</div>
				<div class="col-md-2 form-group">
					<label> Codbarras:	</label>
					<input id="cajaCodbarras" type="text" name="Codbarras"  placeholder="Codbarras"
					 data-obj= "cajaCodBarras" size="12" value="" data-objeto="cajaCodBarras" 
					 onkeydown="controlEventos(event)" />
				</div>
				<div class="col-md-6 form-group">
					<label>Descripcion:	</label>
					<input id="cajaDescripcion" type="text" name="Descripcion" placeholder="Descripcion" 
					data-obj="cajaDescripcion" size="20" value="" onkeydown="controlEventos(event)" />
				</div>
			</div>
            <div class="row">
                <div>                   
                    <table id="tabla" class="table">
						<thead>
							<tr>
								<th></th>
								<th>Id Articulo</th>
								<th>Descripcion</th>
								<th style="text-align: right">Precio S/IVA</th>
								<th style="text-align: right">% IVA</th>
								<th style="text-align: right">Precio C/IVA</th>
								<th><!--cjas btn --></th>
							</tr>
							
							<tr id="formulario" style="display:none;">
								<td></td>
								<td>
									<input type="text" placeholder="id" name="idArticulo" size ="6"
										   disabled="disabled" id="inputIdArticulo" />
								</td>
								<td>
									<input type="text" disabled="disabled" name="descripcion" size="25"
										   id="inputDescripcion" />
								</td>
								<td>
									<input type="text" placeholder="Precio sin iva" class="al-editiva" size="10"
										   name="precioSiva" id="inputPrecioSin" style="text-align: right"
										   data-obj="inputPrecioSin" data-result="inputPrecioCon" 
										   data-factor="*" data-percent="inputIVA" onkeydown="controlEventos(event)"
										   />
								</td>
								<td>
									<input type="text" placeholder="% iva" name="ivaArticulo" size="3"
										   readonly id="inputIVA" style="text-align: right" />
								</td>
								<td>
									<input type="text" placeholder="precio con iva" class="al-editiva" size="10"
										   name="precioCiva" id="inputPrecioCon" style="text-align: right"
										   data-obj="inputPrecioCon" data-result="inputPrecioSin" 
										   data-factor="/" data-percent="inputIVA" onkeydown="controlEventos(event)"
										   />
								</td>
								<td>
									<button id="btn-grabar-tc" data-obj="btn_grabar_tc" onclick="controlEventos(event)" class="btn btn-primary btn-sm">
										<span class="glyphicon glyphicon-ok"></span> grabar
									</button> 
									<button id="btn-cancelar-tc" data-obj="btn_cancelar_tc" onclick="controlEventos(event)"class="btn btn-danger btn-sm">
										<span class="glyphicon glyphicon-remove"></span> cancelar
									</button>
								</td>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($datos as $tarifaCliente) {
								echo '<tr>';
								echo '<td>  </td>';
								echo '<td>' . $tarifaCliente['idArticulo'] . '</td>';
								echo '<td>' . $tarifaCliente['descripcion'] . '</td>';
								echo '<td style="text-align: right">' . number_format($tarifaCliente['pvpSiva'],2, '.', '') . '</td>';
								echo '<td style="text-align: right">' . number_format($tarifaCliente['ivaArticulo'],2, '.', '') . '</td>';
								echo '<td style="text-align: right">' . number_format($tarifaCliente['pvpCiva'],2, '.', '') . '</td>';
								echo '<td>'
									.'<button name="btn_modificar" id="btn_modificar_' 
									. $tarifaCliente['idArticulo'] . '" data-obj="btn_modificar"'
									. 'onclick="controlEventos(event)"'
									. ' class="btn btn-primary btn-sm">'
									.' <span class="glyphicon glyphicon-pencil"></span>'
									.' modificar</button> '
									.' <button name="btn-cancelar-tc" id="btn_cancelar_' 
									. $tarifaCliente['idArticulo'] . '" data-obj="btn_cancelar" '
									. 'onclick="controlEventos(event)"'
									.' class="btn btn-danger btn-sm">'
									.' <span class="glyphicon glyphicon-trash"></span>'.
									' eliminar</button> ' 
									.'</td>';
								echo '</tr>';
							}
							?>
						</tbody>
					</table>
				</div>

        </div>
        <!-- Modal -->
        <?php // Incluimos paginas modales
		echo '<script src="'.$HostNombre.'/plugins/modal/func_modal.js"></script>';
		include $RutaServidor.'/'.$HostNombre.'/plugins/modal/busquedaModal.php';
		?>

    </body>
</html>


