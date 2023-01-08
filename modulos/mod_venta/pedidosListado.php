<?php
include_once './../../inicial.php';
include_once $URLCom.'/modulos/mod_venta/funciones.php';
include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';
include_once $URLCom.'/controllers/Controladores.php';
include_once $URLCom.'/clases/cliente.php';
include_once ($URLCom.'/controllers/parametros.php');
include_once $URLCom.'/modulos/mod_venta/clases/pedidosVentas.php';
//Carga de clases necesarias
$ClasesParametros = new ClaseParametros('parametros.xml');
// Creamos el objeto de controlador.
$Controler = new ControladorComun; 
// Creamos el objeto de pedido
$Cpedido=new PedidosVentas($BDTpv);
$Ccliente=new Cliente($BDTpv);
$todoTemporal=$Cpedido->TodosTemporal();
if (isset($todoTemporal['error'])){
    
$errores[0]=array ( 'tipo'=>'Danger!',
                             'dato' => $todoTemporal['consulta'],
                             'class'=>'alert alert-danger',
                             'mensaje' => 'ERROR EN LA BASE DE DATOS!'
                             );
}
$todoTemporal=array_reverse($todoTemporal);
    
// ===========    Paginacion  ====================== //
$NPaginado = new PluginClasePaginacion(__FILE__);
$campos = array( 'a.Numpedcli','b.Nombre');

$NPaginado->SetCamposControler($campos);
$NPaginado->SetOrderConsulta('a.Numpedcli');
// --- Ahora contamos registro que hay para es filtro --- //
$filtro= $NPaginado->GetFiltroWhere('OR'); // mando operador para montar filtro ya que por defecto es AND

$CantidadRegistros=0;
// Obtenemos la cantidad registros 
$p= $Cpedido->TodosPedidosFiltro($filtro);
$CantidadRegistros = count($p['Items']);

// --- Ahora envio a NPaginado la cantidad registros --- //
$NPaginado->SetCantidadRegistros($CantidadRegistros);
$htmlPG = $NPaginado->htmlPaginado();
//GUardamos un array con los datos de los albaranes real pero solo el número de albaranes indicado
$p=$Cpedido->TodosPedidosFiltro($filtro.$NPaginado->GetLimitConsulta());
$pedidosDef=$p['Items'];
if (isset($p['error'])){
    $errores[0]=array ( 'tipo'=>'Danger!',
                             'dato' => $p['consulta'],
                             'class'=>'alert alert-danger',
                             'mensaje' => 'ERROR EN LA BASE DE DATOS!'
                             );
}
if (count($pedidosDef)==0){
    $errores[0]=array ( 'tipo'=>'Warning!',
                             'dato' => '',
                             'class'=>'alert alert-warning',
                             'mensaje' => 'No tienes pedidos guardados!'
                             );
}
?>
<!DOCTYPE html>
<html>
<head>
 <?php include_once $URLCom.'/head.php';?>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_venta/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/modulos/mod_venta/js/AccionesDirectas.js"></script>    
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
    <script src="<?php echo $HostNombre; ?>/lib/js/teclado.js"></script>
</head>
<body>
<?php
include_once $URLCom.'/modulos/mod_menu/menu.php';
if (isset($errores)){
    foreach($errores as $error){
            echo '<div class="'.$error['class'].'">'
            . '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br> '.$error['dato']
            . '</div>';
            if ($error['tipo']=='Danger!'){
                exit;
            }
    }
}
?>
<div class="container">
	<div class="row">
		<div class="col-md-12 text-center">
            <h2>Pedidos de clientes </h2>
        </div>
        <nav class="col-sm-3">
            <h4> Pedidos</h4>
            <h5> Opciones para una selección</h5>
            <ul class="nav nav-pills nav-stacked"> 
            <?php 
                if($ClasePermisos->getAccion("Crear")==1){
                    echo '<li><a href="#section2" onclick="metodoClick('."'".'AgregarPedido'."'".');";>Añadir</a></li>';
                }
                if($ClasePermisos->getAccion("Modificar")==1){
                     echo '<li><a href="#section2" onclick="metodoClick('."'".'editar'."'".','."'".'pedido'."'".');";>Modificar</a></li>';
                }
            ?>
            </ul>	
            <div class="col-md-12">
            <h4 class="text-center"> Pedidos Abiertos</h4>
            <table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>Nº Ped</th>
					<th>Cliente</th>
					<th>Total</th>
				</tr>
				
			</thead>
			<tbody>
				<?php 
				if (isset ($todoTemporal)){
                    foreach ($todoTemporal as $temporal){
			    		$numTemporal="";
						if ($temporal['Numpedcli']){
							$numTemporal=$temporal['Numpedcli'];
						}					
						$url = 'pedido.php?tActual='.$temporal['id'];
						$tdl = '<td style="cursor:pointer" onclick="redireccionA('
								."'".$url."'".')" title="Pedido con numero temporal:'
								.$temporal['id'].'">';
						$td_temporal = $tdl.$numTemporal.'</td>'
											 .$tdl.$temporal['Nombre'].'</td>'
											 .$tdl.number_format($temporal['total'],2).'</td>';
						?>
						<tr>
							<?php echo $td_temporal;
							// Solo mostramos la opcion de eliminar temporal si tiene permisos.
							if($ClasePermisos->getAccion("EliminarTemporal")==1){
							?>
							<td>
								<a onclick="eliminarTemporal(<?php echo $temporal['id']; ?>, 'ListadoPedidos')">
									<span class="glyphicon glyphicon-trash"></span>
								</a>
							</td>
							<?php
							}
							?>
						</tr>
						<?php
					}
				}
				?>
			</tbody>
            </table>
            </div>
        </nav>
        <div class="col-md-9">
            <p>
             -Pedidos encontrados BD local filtrados:
                <?php echo $CantidadRegistros; ?>
            </p>
            <?php 	// Mostramos paginacion 
                echo $htmlPG;
                //enviamos por get palabras a buscar, las recogemos al inicio de la pagina
            ?>
            <form action="./pedidosListado.php" method="GET" name="formBuscar">
                <div class="form-group ClaseBuscar">
                <label>Buscar por nombre de cliente o número de pedido</label>
                    <input type="text" name="buscar" value="">
                    <input type="submit" value="buscar">
                </div>
            </form>
            <div>
			<table class="table table-bordered table-hover">
				<thead>
					<tr>
						<th></th>
						<th></th>
                        <th></th>
						<th>Nª PEDIDO</th>
						<th>FECHA</th>
						<th>CLIENTE</th>
						<th>BASE</th>
						<th>IVA</th>
						<th>TOTAL</th>
						<th>ESTADO</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					foreach($pedidosDef as $k=>$pedido){
						$totaliva=$Cpedido->sumarIva($pedido['Numpedcli']);
						?>
						<tr>
                            <td class="row">
                                <input class="Check" type="checkbox" name="check_<?php echo $k;?>" value="<?php echo $pedido['id'];?>">
                            </td>

                            <td>
                                <?php 
                                 if($ClasePermisos->getAccion("Modificar")==1){
                            echo '<a class="glyphicon glyphicon-pencil" href="./pedido.php?id='.$pedido['id'].'&accion=editar"></a>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if($ClasePermisos->getAccion("Ver")==1){
                                   echo '<a class="glyphicon glyphicon-eye-open" href="./pedido.php?id='.$pedido['id'].'&estado=ver"></a>';
                                }
                                ?>
                            </td>
						<td><?php echo $pedido['Numpedcli'];?></td>
						<td><?php echo $pedido['Fecha'];?></td>
						<td><?php echo $pedido['Nombre'];?></td>
						<td><?php echo $totaliva['totalbase'];?></td>
						<td><?php echo $totaliva['importeIva'];?></td>
						<td><?php echo $pedido['total'];?></td>
                            <td>
                            <?php 
                            echo $pedido['estado'];
                            if ($pedido !== 'Sin Guardar'){
                                $onclick=" onclick='imprimir(".$pedido['id'].',"pedido",'.json_encode($_SESSION['tiendaTpv']).")'";
                                echo '<a class="glyphicon glyphicon-print" '.$onclick.'></a>';
                            }
                            ?>
                            </td>
						</tr>
						<?php
					}
					?>
                </tbody>
            </table>
			</div>
		</div>
	</div>
</div>
</body>
</html>
