
<!DOCTYPE html>
<html>
<head>
<?php
	include_once './../../inicial.php';
	include_once $URLCom.'/head.php';
	include_once $URLCom.'/modulos/mod_compras/funciones.php';
	include_once $URLCom.'/plugins/paginacion/ClasePaginacion.php';
	include_once $URLCom.'/controllers/Controladores.php';
	include_once $URLCom.'/modulos/mod_compras/clases/pedidosCompras.php';
	include_once $URLCom.'/clases/Proveedores.php';
	
	// Creamos el objeto de controlador.
	$Controler = new ControladorComun; 

	// Creamos el objeto de pedido
	$Cpedido=new PedidosCompras($BDTpv);

	// Creamos el objeto de proveedor
	$Cproveedor=new Proveedores($BDTpv);
	
	//Obtenemos los registros temporarles
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
	$campos = array( 'a.Numpedpro','b.nombrecomercial');
	$NPaginado->SetOrderConsulta('a.Numpedpro');
	$NPaginado->SetCamposControler($Controler,$campos);
	// --- Ahora contamos registro que hay para es filtro --- //
	$filtro= $NPaginado->GetFiltroWhere('OR'); // mando operador para montar filtro ya que por defecto es AND

	$CantidadRegistros=0;
	// Obtenemos la cantidad registros 
	$p= $Cpedido->TodosPedidosLimite($filtro);
		
	$CantidadRegistros = count($p['Items']);
	
	// --- Ahora envio a NPaginado la cantidad registros --- //
	$NPaginado->SetCantidadRegistros($CantidadRegistros);
	$htmlPG = $NPaginado->htmlPaginado();
	//GUardamos un array con los datos de los albaranes real pero solo el número de albaranes indicado
	$p=$Cpedido->TodosPedidosLimite($filtro.$NPaginado->GetLimitConsulta());
	$pedidosDef=$p['Items'];
	 $pedidosDef=$p['Items'];
	if (isset($p['error'])){
		$errores[1]=array ( 'tipo'=>'Danger!',
								 'dato' => $p['consulta'],
								 'class'=>'alert alert-danger',
								 'mensaje' => 'ERROR EN LA BASE DE DATOS!'
								 );
	}
	if (count($pedidosDef)==0){
		$errores[0]=array ( 'tipo'=>'Warning!',
								 'dato' => '',
								 'class'=>'alert alert-warning',
								 'mensaje' => 'No tienes albaranes guardados!'
								 );
	}
	?>

</head>

<body>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_compras/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
<?php
	//~ include $URLCom.'/header.php';
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
				<h2>Compras: Editar y Añadir pedidos </h2>
			</div>
			<nav class="col-sm-4">
				<h4> Pedidos</h4> 
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
				<?php 
					if ($Usuario['group_id'] > '0'){
				?>
					<li><a href="#section2" onclick="metodoClick('AgregarPedido');";>Añadir</a></li>
					<?php 
				}
					?>
					<li><a href="#section2" onclick="metodoClick('Ver','pedido');";>Modificar</a></li>
				
				</ul>
				<div class="col-md-12">
					<h4 class="text-center"> Pedidos Abiertos</h4>
					<table class="table table-striped table-hover">
						<thead>
							<tr>
								<th WIDTH="4" >Nº Temp</th>
								<th WIDTH="4" >Nº Ped</th>
								<th WIDTH="110" >Pro.</th>
								<th WIDTH="4" >Total</th>
							</tr>
							
						</thead>
						<tbody>
							<?php 
							if (isset ($todoTemporal)){
								foreach ($todoTemporal as $pedidoTemp){
									if ($pedidoTemp['idPedpro']){
										$numPed=$pedidoTemp['Numpedpro'];
								}else{
									$numPed="";
								}
								?>
									<tr>
									<td><a href="pedido.php?tActual=<?php echo $pedidoTemp['id'];?>"><?php echo $pedidoTemp['id'];?></td>
									<td><?php echo $numPed;?></td>
									<td><?php echo $pedidoTemp['nombrecomercial'];?></td>
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
						<label>Buscar por nombre de proveedor o número de pedido</label>
						<input type="text" name="buscar" value="">
						<input type="submit" value="buscar">
					</div>
				</form>
						<div>
			<table class="table table-bordered table-hover">
				<thead>
					<tr>
						<th></th>
						
						<th>Nª PEDIDO</th>
						<th>FECHA</th>
						<th>PROVEEDOR</th>
						<th>BASE</th>
						<th>IVA</th>
						<th>TOTAL</th>
						<th>ESTADO</th>
					<?php
							$checkUser = 0;
							
							foreach($pedidosDef as $pedido){
								$checkUser = $checkUser + 1;
								$totaliva=$Cpedido->sumarIva($pedido['Numpedpro']);
						?>
						<tr>
						<td class="rowUsuario"><input type="checkbox" name="checkUsu<?php echo $checkUser;?>" value="<?php echo $pedido['id'];?>">
						<td><?php echo $pedido['Numpedpro'];?></td>
						<td><?php echo $pedido['FechaPedido'];?></td>
						<td><?php echo $pedido['nombrecomercial'];?></td>
						<td><?php echo $totaliva['totalbase'];?></td>
						<td><?php echo $totaliva['importeIva'];?></td>
						<td><?php echo $pedido['total'];?></td>
						<?php 
						if ($pedido['estado']=="Sin Guardar"){
							?>
							<td><?php echo $pedido['estado'];?></td>
							<?php
						}else{
							?>
						<td><?php echo $pedido['estado'];?>  <a class="glyphicon glyphicon-print" onclick='imprimir(<?php echo $pedido['id'];?>, "pedido", <?php echo $_SESSION['tiendaTpv']['idTienda'];?>)'></a></td>

							
							<?php
						}
						
						?>
						
						
						</tr>
						<?php
					
				}
					?>
					</tr>
				</thead>
				</table>
			</div>
		</div>
	</div>
    </div>
	</body>
</html>
