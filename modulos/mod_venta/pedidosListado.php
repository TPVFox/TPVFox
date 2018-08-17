
<!DOCTYPE html>
<html>
<head>
<?php
    include_once './../../inicial.php';
    include $URLCom.'/head.php';
    include_once $URLCom.'/modulos/mod_venta/funciones.php';
	include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';
	include_once $URLCom.'/controllers/Controladores.php';
    include_once $URLCom.'/modulos/mod_venta/clases/pedidosVentas.php';
    include_once $URLCom.'/clases/cliente.php';
	
	$Cpedido=new PedidosVentas($BDTpv);
	$Ccliente=new Cliente($BDTpv);
	$Controler = new ControladorComun; 
	$todoTemporal=$Cpedido->TodosTemporal();
	if (isset($todoTemporal['error'])){
	$errores[0]=array ( 'tipo'=>'Danger!',
								 'dato' => $todoTemporal['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
	}
	$todoTemporal=array_reverse($todoTemporal);
	$Tienda = $_SESSION['tiendaTpv'];
		
	// ===========    Paginacion  ====================== //
	$NPaginado = new PluginClasePaginacion(__FILE__);
	$campos = array( 'a.Numpedcli','b.Nombre');

	$NPaginado->SetCamposControler($Controler,$campos);
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
?>

</head>

<body>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_venta/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script>     
<?php
 include_once $URLCom.'/modulos/mod_menu/menu.php';
//~ include '../../header.php';
if (isset($errores)){
		foreach($errores as $error){
				echo '<div class="'.$error['class'].'">'
				. '<strong>'.$error['tipo'].' </strong> '.$error['mensaje'].' <br>Sentencia: '.$error['dato']
				. '</div>';
		}
}
?>
		<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2>Ventas: Editar y Añadir pedidos </h2>
				</div>
					<nav class="col-sm-4">
				<h4> Pedidos</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
				<?php 
					if($ClasePermisos->getAccion("Crear")==1){
                        echo '<li><a href="#section2" onclick="metodoClick('."'".'AgregarPedido'."'".');";>Añadir</a></li>';
                    }
                    if($ClasePermisos->getAccion("Modificar")==1){
                        echo '<li><a href="#section2" onclick="metodoClick('."'".'Ver'."'".','."'".'pedido'."'".');";>Modificar</a></li>';
                    }
					?>
				</ul>	
					<div class="col-md-12">
		<h4 class="text-center"> Pedidos Abiertos</h4>
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th WIDTH="4">Nº Temp</th>
					<th WIDTH="4">Nº Ped</th>
					<th WIDTH="100">Cliente</th>
					<th WIDTH="4">Total</th>
				</tr>
				
			</thead>
			<tbody>
				<?php 
				if (isset ($todoTemporal)){
					foreach ($todoTemporal as $pedidoTemp){
						if ($pedidoTemp['idPedcli']){
							$numPed=$pedidoTemp['Numpedcli'];
					}else{
						$numPed="";
					}
					?>
						<tr>
						<td><a href="pedido.php?tActual=<?php echo $pedidoTemp['id'];?>"><?php echo $pedidoTemp['id'];?></td>
						<td><?php echo $numPed;?></td>
						<td><?php echo $pedidoTemp['Nombre'];?></td>
						<td><?php echo number_format($pedidoTemp['total'],2);?></td>
						</tr>
						<?php
					}
				}
				?>
			</tbody>
		</table>
		</div>
			</nav>
			<div class="col-md-8">
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
						<label>Buscar en número de pedido </label>
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
					$checkUser = 0;
					foreach($pedidosDef as $pedido){
						$checkUser = $checkUser + 1;
						
						$totaliva=$Cpedido->sumarIva($pedido['Numpedcli']);
						?>
						<tr>
						<td class="rowUsuario"><input type="checkbox" name="checkUsu<?php echo $checkUser;?>" value="<?php echo $pedido['id'];?>">
                         <td>
                             <?php 
                              if($ClasePermisos->getAccion("Modificar")==1){
                             ?>
                                <a class="glyphicon glyphicon-pencil" href='./pedido.php?id=<?php echo $pedido['id'];?>'>
                            <?php 
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            if($ClasePermisos->getAccion("Ver")==1){
                            ?>
                            <a class="glyphicon glyphicon-eye-open" href='./pedido.php?id=<?php echo $pedido['id'];?>&estado=ver'>
                            <?php 
                            }
                            ?>
                        </td>
						<td><?php echo $pedido['Numpedcli'];?></td>
						<td><?php echo $pedido['FechaPedido'];?></td>
						<td><?php echo $pedido['Nombre'];?></td>
						<td><?php echo $totaliva['totalbase'];?></td>
						<td><?php echo $totaliva['importeIva'];?></td>
						<td><?php echo $pedido['total'];?></td>
						<?php 
						if ($pedido['estado']=="Sin Guardar"){
							?>
							<td><?php echo $pedido['estado'];?></td>
							<?php
						}else{
							$tienda=json_encode($_SESSION['tiendaTpv']);
							
							?>
						<td><?php echo $pedido['estado'];?>  <a class="glyphicon glyphicon-print" onclick='imprimir(<?php echo $pedido['id'];?>, "pedido", <?php echo $tienda;?>)'></a></td>

							
							<?php
						}
						
						?>
						
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
