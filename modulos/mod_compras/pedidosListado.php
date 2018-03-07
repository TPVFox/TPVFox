
<!DOCTYPE html>
<html>
<head>
<?php

include './../../head.php';
	include './funciones.php';
	include ("./../../plugins/paginacion/paginacion.php");
	include ("./../../controllers/Controladores.php");
	include 'clases/pedidosCompras.php';
	//~ echo '<pre>';
	//~ print_r($_SESSION);
	//~ echo '</pre>';
	$Cpedido=new PedidosCompras($BDTpv);
	include '../../clases/Proveedores.php';
	$Cproveedor=new Proveedores($BDTpv);
	//MUestra un array con todos los temporales
	$todoTemporal=$Cpedido->TodosTemporal();
	$palabraBuscar=array();
	$stringPalabras='';
	$PgActual = 1; // por defecto.
	$LimitePagina = 10; // por defecto.
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
	
	$vista = 'pedprot';
	$LinkBase = './pedidosListado.php?';
	$OtrosParametros = '';
	$paginasMulti = $PgActual-1;
	if ($paginasMulti > 0) {
		$desde = ($paginasMulti * $LimitePagina); 
		
	} else {
		$desde = 0;
	}
if ($stringPalabras !== '' ){
		$campoBD='Numpedpro';
		
		$campo = array( 'a.Numpedpro','b.nombrecomercial');
		$NuevoWhere = $Controler->ConstructorLike($campo, $stringPalabras, 'OR');
		$NuevoRango=$Controler->ConstructorLimitOffset($LimitePagina, $desde);
		
		//~ $WhereLimite= $Controler->paginacionFiltroBuscar($stringPalabras,$LimitePagina,$desde,$campoBD);
		//~ $filtro=$WhereLimite['filtro'];
		$OtrosParametros=$stringPalabras;
		$WhereLimite['filtro']='WHERE '.$NuevoWhere;
	}
//~ $CantidadRegistros = $Controler->contarRegistro($BDTpv,$vista,$filtro);
$CantidadRegistros=count($Cpedido->TodosPedidosLimite($WhereLimite['filtro']));
$WhereLimite['rango']=$NuevoRango;
//$CantidadRegistros = $Controler->contarRegistro($BDTpv,$vista,$NuevoWhere);
$htmlPG = paginado ($PgActual,$CantidadRegistros,$LimitePagina,$LinkBase,$OtrosParametros);
if ($stringPalabras !== '' ){
		$filtro = $WhereLimite['filtro']." ORDER BY  Numpedpro desc ".$WhereLimite['rango'];
} else {
		$filtro= "ORDER BY  Numpedpro desc LIMIT ".$LimitePagina." OFFSET ".$desde;
}
	//MUestra un array con un número determinado de registros
	$pedidosDef=$Cpedido->TodosPedidosLimite($filtro);
?>

</head>

<body>
	<script src="<?php echo $HostNombre; ?>/modulos/mod_compras/funciones.js"></script>
    <script src="<?php echo $HostNombre; ?>/controllers/global.js"></script> 
<?php

	include '../../header.php';
	?>
		<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
					<h2> Pedidos Compras: Editar y Añadir pedidos </h2>
				</div>
					<nav class="col-sm-4">
				<h4> Pedidos</h4>
				<h5> Opciones para una selección</h5>
				<ul class="nav nav-pills nav-stacked"> 
				<?php 
					if ($Usuario['group_id'] === '1'){
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
							//$numPedido=$Cpedido->datosPedidos($pedidoTemp['idPedpro']);
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
							//	$htmlImprimir=montarHTMLimprimir($pedido['id'], $BDTpv, "pedido");
								
							
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

