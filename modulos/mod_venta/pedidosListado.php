
<!DOCTYPE html>
<html>
<head>
<?php
	include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	include 'clases/pedidosVentas.php';
	include '../../clases/cliente.php';
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
	$palabraBuscar=array();
	$stringPalabras='';
	$PgActual = 1; // por defecto.
	$LimitePagina = 30; // por defecto.
	$filtro = ''; // por defecto
	if (isset($_GET['pagina'])) {
		$PgActual = $_GET['pagina'];
	}
	if (isset($_GET['buscar'])) {  
		//recibo un string con 1 o mas palabras
		$stringPalabras = $_GET['buscar'];
		$palabraBuscar = explode(' ',$_GET['buscar']); 
	} 
	$Controler = new ControladorComun; 
	$vista = 'pedclit';
	$LinkBase = './pedidosListado.php?';
	$OtrosParametros = '';
	$paginasMulti = $PgActual-1;
	
	if ($paginasMulti > 0) {
		$desde = ($paginasMulti * $LimitePagina); 
		
	} else {
		$desde = 0;
	}
		$WhereLimite = array();
	$WhereLimite['filtro'] = '';
	$NuevoRango = '';
if ($stringPalabras !== '' ){
		$campo = array( 'a.Numpedcli','b.Nombre');
		$NuevoWhere = $Controler->ConstructorLike($campo, $stringPalabras, 'OR');
		$NuevoRango=$Controler->ConstructorLimitOffset($LimitePagina, $desde);
		$OtrosParametros=$stringPalabras;
		$WhereLimite['filtro']='WHERE '.$NuevoWhere;
}
$CantidadRegistros=count($Cpedido->TodosPedidosFiltro($WhereLimite['filtro']));
$WhereLimite['rango']=$NuevoRango;
$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros);

if ($stringPalabras !== '' ){
		$filtro = $WhereLimite['filtro']." ORDER BY  Numpedcli desc ".$WhereLimite['rango'];
	} else {
		$filtro= "ORDER BY  Numpedcli  desc LIMIT ".$LimitePagina." OFFSET ".$desde;
	}
	
	$pedidosDef=$Cpedido->TodosPedidosFiltro($filtro);
	if (isset($pedidosDef['error'])){
	$errores[0]=array ( 'tipo'=>'Danger!',
								 'dato' => $pedidosDef['consulta'],
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
include '../../header.php';
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
					<h2> Pedidos de clientes: Editar y Añadir pedidos </h2>
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
		<table class="table table-striped">
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
